<?php
// Moodle plugin, GPL v3 or later.
namespace qtype_linuxcmd;
defined('MOODLE_INTERNAL') || die();

/** Version-2 permission scenarios. Pure data evaluation; never executes learner input. */
final class permissions {
    public const TYPES = ['chown', 'chownboth', 'chownrecursive', 'chgrp', 'chgrprecursive',
        'chmodnumeric', 'chmodsymbolic', 'suid', 'nosuid', 'sgid', 'sticky', 'nosticky',
        'modeletters', 'modeoctal', 'fileaccess', 'diraccess', 'specialmeaning'];

    public static function generate(string $type, string $mode): array {
        $n = random_int(10, 9999);
        $root = '/srv/practice/team' . $n;
        $directory = in_array($type, ['chownrecursive', 'chgrprecursive', 'sgid', 'sticky', 'nosticky', 'diraccess'], true);
        $source = $root . ($directory ? '/shared' : '/report' . $n . '.txt');
        $style = ($mode === 'random' ? random_int(1, 3) === 3 : in_array($mode, ['absolute', 'reverse'], true))
            ? 'absolute' : 'relative';
        $initial = $directory ? 0755 : 0644;
        $goal = random_int(0, 0777);
        $d = ['version' => 2, 'type' => $type, 'mode' => $style, 'sourcemode' => $style,
            'targetmode' => $style, 'root' => $root, 'cwd' => $root, 'source' => $source, 'target' => null,
            'dirs' => $directory ? [$root, $source, $source . '/nested'] : [$root],
            'files' => $directory ? [$source . '/item.txt', $source . '/nested/child.txt'] : [$source],
            'owner' => 'alice' . $n, 'group' => 'editors' . $n, 'oldowner' => 'student' . $n,
            'oldgroup' => 'trainees' . $n, 'initial' => $initial, 'command' => '', 'flags' => [],
            'answerkind' => 'command'];
        $spec = '';
        switch ($type) {
            case 'chown': case 'chownboth': case 'chownrecursive':
                $d['command'] = 'chown';
                $spec = $d['owner'] . ($type === 'chownboth' ? ':' . $d['group'] : '');
                break;
            case 'chgrp': case 'chgrprecursive':
                $d['command'] = 'chgrp'; $spec = $d['group']; break;
            case 'chmodnumeric': case 'chmodsymbolic':
                $d['command'] = 'chmod';
                $spec = $type === 'chmodnumeric' ? sprintf('%03o', $goal) : self::assignment($goal);
                break;
            case 'suid': $d['initial'] = 0755; $goal = 04755; $spec = 'u+s'; break;
            case 'nosuid': $d['initial'] = 04755; $goal = 0755; $spec = 'u-s'; break;
            case 'sgid': $goal = 02755; $spec = 'g+s'; break;
            case 'sticky': $d['initial'] = 0777; $goal = 01777; $spec = 'o+t'; break;
            case 'nosticky': $d['initial'] = 01777; $goal = 0777; $spec = 'o-t'; break;
            case 'modeletters': case 'modeoctal':
                // Includes all special-bit combinations and upper/lower s/t cases.
                $goal = random_int(0, 07777);
                $d['answerkind'] = $type === 'modeletters' ? 'letters' : 'octal';
                $d['answer'] = $type === 'modeletters' ? self::letters($goal) : sprintf('%04o', $goal);
                break;
            case 'fileaccess': case 'diraccess':
                $d['permission'] = ['r', 'w', 'x'][random_int(0, 2)];
                $d['answerkind'] = 'word'; $d['answer'] = $d['permission']; break;
            case 'specialmeaning':
                $d['special'] = ['suid', 'sgid', 'sgidbinary', 'sticky'][random_int(0, 3)];
                $d['answerkind'] = 'word'; $d['answer'] = $d['special'] === 'sgidbinary' ? 'sgid' : $d['special']; break;
        }
        if (in_array($type, ['suid', 'nosuid', 'sgid', 'sticky', 'nosticky'], true)) { $d['command'] = 'chmod'; }
        if (in_array($type, ['chownrecursive', 'chgrprecursive'], true)) { $d['flags'] = ['R']; }
        $d['goal'] = $goal;
        $d['octal'] = sprintf('%04o', $goal);
        $d['letters'] = self::letters($goal);
        $d['initialoctal'] = sprintf('%04o', $d['initial']);
        $d['initialletters'] = self::letters($d['initial']);
        if ($d['answerkind'] === 'command') {
            $d['answer'] = $d['command'] . ($d['flags'] ? ' -R' : '') . ' ' . $spec . ' ' .
                exercise::operand($source, $root, $style);
        }
        return $d;
    }

    public static function letters(int $mode): string {
        $out = '';
        foreach ([[0400, 'r'], [0200, 'w'], [0100, 'x'], [0040, 'r'], [0020, 'w'],
                [0010, 'x'], [0004, 'r'], [0002, 'w'], [0001, 'x']] as [$bit, $letter]) {
            $out .= $mode & $bit ? $letter : '-';
        }
        foreach ([[04000, 2, 's', 'S'], [02000, 5, 's', 'S'], [01000, 8, 't', 'T']] as [$bit, $pos, $lower, $upper]) {
            if ($mode & $bit) { $out[$pos] = $out[$pos] === 'x' ? $lower : $upper; }
        }
        return $out;
    }

    private static function assignment(int $mode): string {
        $parts = [];
        foreach (['u' => 6, 'g' => 3, 'o' => 0] as $who => $shift) {
            $value = '';
            foreach ([4 => 'r', 2 => 'w', 1 => 'x'] as $bit => $letter) {
                if (($mode >> $shift) & $bit) { $value .= $letter; }
            }
            $parts[] = $who . '=' . $value;
        }
        return implode(',', $parts);
    }

    /** Explicit classes only: no umask-dependent omitted who, X, copies or shell syntax. */
    public static function apply_symbolic(string $spec, int $mode, bool $directory = false): ?int {
        foreach (explode(',', $spec) as $clause) {
            if (!preg_match('/^([ugoa]+)([+=-])([rwxst]*)$/D', $clause, $m)) { return null; }
            $who = str_contains($m[1], 'a') ? 'ugo' : $m[1];
            $bits = 0; $mask = 0;
            foreach (['u' => [6, 04000], 'g' => [3, 02000], 'o' => [0, 01000]] as $class => [$shift, $special]) {
                if (!str_contains($who, $class)) { continue; }
                $mask |= 7 << $shift;
                // GNU chmod preserves directory SUID/SGID on = unless s is explicit.
                if (!$directory || $class === 'o' || $m[2] !== '=' || str_contains($m[3], 's')) {
                    $mask |= $special;
                }
                foreach ([4 => 'r', 2 => 'w', 1 => 'x'] as $bit => $letter) {
                    if (str_contains($m[3], $letter)) { $bits |= $bit << $shift; }
                }
                if (($class !== 'o' && str_contains($m[3], 's')) ||
                        ($class === 'o' && str_contains($m[3], 't'))) { $bits |= $special; }
            }
            $mode = match ($m[2]) { '+' => $mode | $bits, '-' => $mode & ~$bits, '=' => ($mode & ~$mask) | $bits };
        }
        return $mode;
    }

    public static function correct(string $answer, array $d): bool {
        $answer = trim($answer);
        if ($d['answerkind'] !== 'command') {
            return match ($d['answerkind']) {
                'octal' => preg_match('/^[0-7]{3,4}$/D', $answer) && octdec($answer) === $d['goal'],
                'letters' => $answer === $d['answer'],
                default => strtolower($answer) === $d['answer'],
            };
        }
        $words = exercise::words($answer);
        if (!$words || array_shift($words) !== $d['command']) { return false; }
        $recursive = false; $args = []; $options = true;
        foreach ($words as $word) {
            if ($options && $word === '--') { $options = false; continue; }
            if ($options && in_array($word, ['-R', '--recursive'], true)) { $recursive = true; continue; }
            if ($options && str_starts_with($word, '-')) { return false; }
            $args[] = $word;
        }
        if (count($args) !== 2 || $recursive !== (bool)$d['flags']) { return false; }
        [$spec, $path] = $args;
        if ($path === '' || str_starts_with($path, '/') !== ($d['sourcemode'] === 'absolute') ||
                !exercise::traversable($path, $d['cwd'], $d) ||
                exercise::resolve($path, $d['cwd']) !== $d['source'] ||
                (in_array($d['source'], $d['files'], true) && str_ends_with($path, '/'))) { return false; }
        if ($d['command'] === 'chown') {
            return $spec === $d['owner'] . ($d['type'] === 'chownboth' ? ':' . $d['group'] : '');
        }
        if ($d['command'] === 'chgrp') { return $spec === $d['group']; }
        $numeric = (bool)preg_match('/^[0-7]{3,4}$/D', $spec);
        if (($d['type'] === 'chmodnumeric' && !$numeric) || ($d['type'] === 'chmodsymbolic' && $numeric)) { return false; }
        // Numeric exercises use regular files; special-bit directory tasks preserve bits by explicit symbolic clauses.
        if ($numeric && in_array($d['source'], $d['dirs'], true)) { return false; }
        $result = $numeric ? octdec($spec) : self::apply_symbolic($spec, $d['initial'], in_array($d['source'], $d['dirs'], true));
        return $result !== null && $result === $d['goal'];
    }

    public static function text(array $d): string {
        $a = (object)$d;
        $text = $d['answerkind'] === 'command' ?
            get_string('permission_intro', 'qtype_linuxcmd', $a) . "\n\n" : '';
        $text .= get_string('task_' . $d['type'], 'qtype_linuxcmd', $a);
        if (in_array($d['type'], ['fileaccess', 'diraccess'], true)) {
            $text .= ' ' . get_string($d['type'] . '_' . $d['permission'], 'qtype_linuxcmd');
        } else if ($d['type'] === 'specialmeaning') {
            $text .= ' ' . get_string('meaning_' . $d['special'], 'qtype_linuxcmd');
        }
        if ($d['answerkind'] === 'command') {
            $text .= "\n\n" . get_string('rule_' . $d['sourcemode'], 'qtype_linuxcmd') . "\n" .
                get_string('permission_command', 'qtype_linuxcmd');
        }
        return $text;
    }
}
