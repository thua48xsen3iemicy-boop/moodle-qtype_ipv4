<?php
// Moodle plugin, GPL v3 or later.
namespace qtype_linuxcmd;
defined('MOODLE_INTERNAL') || die();

/** Generates self-contained, versioned command exercises. Never executes shell input. */
final class exercise {
    public const TYPES = ['mkdir', 'mkdirparents', 'touch', 'copyfile', 'copytree',
        'rename', 'move', 'rmdir', 'removefile', 'removetree'];
    public const MODES = ['absolute', 'relative', 'mixed'];

    public static function validate(string $type, string $mode): void {
        if (!in_array($type, self::TYPES, true) || !in_array($mode, self::MODES, true)) {
            throw new \invalid_parameter_exception('Invalid Linux exercise settings');
        }
    }

    public static function labels(): array {
        $labels = [];
        foreach (self::TYPES as $type) {
            $labels[$type] = get_string('type_' . $type, 'qtype_linuxcmd');
        }
        return $labels;
    }

    public static function generate(string $type, string $mode): array {
        self::validate($type, $mode);
        $n = random_int(10, 9999);
        $root = '/srv/practice/team' . $n;
        $cwd = $root . ['/work/drafts', '/documents', '/downloads'][random_int(0, 2)];
        $report = $root . '/documents/report' . $n . '.txt';
        $photo = $root . '/downloads/photo' . $n . '.jpg';
        $manual = $root . '/downloads/manual' . $n . '.pdf';
        $drafts = $root . '/documents/drafts';
        $dirs = [$root, $root . '/work', $root . '/work/drafts', $root . '/documents',
            $drafts, $root . '/downloads', $root . '/backup', $root . '/empty'];
        $files = [$report, $photo, $manual, $drafts . '/plan' . $n . '.txt'];
        [$command, $flags, $source, $target] = match ($type) {
            'mkdir' => ['mkdir', [], $root . '/projects', null],
            'mkdirparents' => ['mkdir', ['p'], $root . '/projects/site/images', null],
            'touch' => ['touch', [], $root . '/backup/todo' . $n . '.txt', null],
            'copyfile' => ['cp', [], $report, $root . '/backup/' . basename($report)],
            'copytree' => ['cp', ['r'], $drafts, $root . '/backup/drafts'],
            'rename' => ['mv', [], $report, $root . '/documents/final-report' . $n . '.txt'],
            'move' => ['mv', [], $photo, $root . '/backup/' . basename($photo)],
            'rmdir' => ['rmdir', [], $root . '/empty', null],
            'removefile' => ['rm', [], $manual, null],
            'removetree' => ['rm', ['r'], $drafts, null],
        };
        $sourcemode = $mode === 'absolute' ? 'absolute' : 'relative';
        $targetmode = $mode === 'relative' ? 'relative' : 'absolute';
        $data = ['version' => 1, 'type' => $type, 'mode' => $mode, 'root' => $root,
            'cwd' => $cwd, 'dirs' => $dirs, 'files' => $files, 'command' => $command,
            'flags' => $flags, 'source' => $source, 'target' => $target,
            'sourcemode' => $sourcemode, 'targetmode' => $targetmode];
        $data['answer'] = $command . ($flags ? ' -' . implode('', $flags) : '') . ' ' .
            self::operand($source, $cwd, $sourcemode) .
            ($target !== null ? ' ' . self::operand($target, $cwd, $targetmode) : '');
        return $data;
    }

    private static function operand(string $path, string $cwd, string $mode): string {
        if ($mode === 'absolute') {
            return $path;
        }
        $from = explode('/', trim($cwd, '/'));
        $to = explode('/', trim($path, '/'));
        while ($from && $to && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }
        return implode('/', array_merge(array_fill(0, count($from), '..'), $to)) ?: '.';
    }

    /** Lexical resolution is valid here: scenarios explicitly contain no symlinks. */
    public static function resolve(string $path, string $cwd): string {
        $parts = [];
        foreach (explode('/', str_starts_with($path, '/') ? $path : $cwd . '/' . $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }
        return '/' . implode('/', $parts);
    }

    /** Small literal shell-word grammar: quotes/escapes, no expansions or operators. */
    public static function words(string $answer): ?array {
        if (strlen($answer) > 4096 || preg_match('/[\x00-\x1f\x7f]/', str_replace("\t", ' ', $answer))) {
            return null;
        }
        $words = [];
        $word = '';
        $quote = '';
        $started = false;
        for ($i = 0, $n = strlen($answer); $i < $n; $i++) {
            $c = $answer[$i];
            if ($quote === "'") {
                if ($c === "'") { $quote = ''; } else { $word .= $c; }
                continue;
            }
            if ($c === '\\') {
                if (++$i >= $n) { return null; }
                $next = $answer[$i];
                // In double quotes Bash only removes backslashes before these characters.
                $word .= $quote === '"' && !str_contains('$`"\\', $next) ? '\\' . $next : $next;
                $started = true;
                continue;
            }
            if ($quote === '"') {
                if ($c === '"') { $quote = ''; }
                else if ($c === '$' || $c === '`') { return null; }
                else { $word .= $c; }
                continue;
            }
            if ($c === "'" || $c === '"') {
                $quote = $c;
                $started = true;
            } else if ($c === ' ' || $c === "\t") {
                if ($started) { $words[] = $word; $word = ''; $started = false; }
            } else if (str_contains(';|&<>$`(){}*?[]~#', $c)) {
                return null;
            } else {
                $word .= $c;
                $started = true;
            }
        }
        if ($quote !== '') { return null; }
        if ($started) { $words[] = $word; }
        return $words ?: null;
    }

    /** Check the operation, literal paths, path styles and allowed option semantics. */
    public static function correct(string $answer, array $data): bool {
        $words = self::words(trim($answer));
        if (!$words || array_shift($words) !== $data['command']) { return false; }
        $flags = [];
        $operands = [];
        $options = true;
        foreach ($words as $word) {
            if ($options && $word === '--') { $options = false; continue; }
            if ($options && str_starts_with($word, '-') && $word !== '-') {
                $newflags = match ($word) {
                    '--recursive' => ['r'], '--parents' => ['p'],
                    default => preg_match('/^-[rRp]+$/D', $word) ? str_split(substr($word, 1)) : null,
                };
                if ($newflags === null) { return false; }
                foreach ($newflags as $flag) { $flags[] = $flag === 'R' ? 'r' : $flag; }
            } else {
                if ($word === '') { return false; }
                $operands[] = $word;
            }
        }
        $flags = array_values(array_unique($flags));
        sort($flags);
        $required = $data['flags'];
        sort($required);
        // mkdir -p also has the requested effect for a single new directory.
        if ($flags !== $required && !($data['type'] === 'mkdir' && $flags === ['p'])) { return false; }
        if (count($operands) !== ($data['target'] === null ? 1 : 2)) { return false; }
        foreach ($operands as $i => $path) {
            $mode = $i === 0 ? $data['sourcemode'] : $data['targetmode'];
            if (str_starts_with($path, '/') !== ($mode === 'absolute')) { return false; }
            // Reject paths that traverse missing components or a regular file.
            if (!self::traversable($path, $data['cwd'], $data)) { return false; }
        }
        if (self::resolve($operands[0], $data['cwd']) !== $data['source']) { return false; }
        $sourcebasename = basename(rtrim($operands[0], '/'));
        if (in_array($data['type'], ['rmdir', 'removetree'], true) &&
                in_array($sourcebasename, ['.', '..'], true)) {
            return false;
        }
        if ($data['target'] !== null) {
            $target = self::resolve($operands[1], $data['cwd']);
            if (in_array($target, $data['dirs'], true)) {
                // cp -r source/. existing/ copies contents directly, without source's basename.
                if ($data['type'] !== 'copytree' || !in_array($sourcebasename, ['.', '..'], true)) {
                    $target .= '/' . basename($data['source']);
                }
            } else if (str_ends_with($operands[1], '/') && $data['type'] !== 'copytree') {
                // Copying a file or moving requires an existing directory with a trailing slash.
                return false;
            }
            if ($target !== $data['target']) { return false; }
        } else if (in_array($data['type'], ['touch', 'removefile'], true) && str_ends_with($operands[0], '/')) {
            return false;
        }
        if ($data['type'] === 'copyfile' && str_ends_with($operands[0], '/')) { return false; }
        if (in_array($data['type'], ['rename', 'move'], true) && str_ends_with($operands[0], '/')) { return false; }
        return true;
    }

    private static function traversable(string $path, string $cwd, array $data): bool {
        $parts = explode('/', str_starts_with($path, '/') ? $path : $cwd . '/' . $path);
        $current = '/';
        $dirs = $data['dirs'];
        // The scenario's root ancestors also exist.
        for ($parent = dirname($data['root']); $parent !== '/'; $parent = dirname($parent)) {
            $dirs[] = $parent;
        }
        $dirs[] = '/';
        $last = count($parts) - 1;
        foreach ($parts as $i => $part) {
            if ($part === '') { continue; }
            if (!in_array($current, $dirs, true)) {
                if ($data['type'] !== 'mkdirparents') { return false; }
                // -p must not create an unrelated directory on a detour through .. .
                if ($current !== $data['source'] && !str_starts_with($data['source'], $current . '/')) {
                    return false;
                }
            }
            if (in_array($current, $data['files'], true)) { return false; }
            if ($part === '.') { continue; }
            if ($part === '..') { $current = dirname($current); continue; }
            $current = rtrim($current, '/') . '/' . $part;
            if ($i < $last && in_array($current, $data['files'], true)) { return false; }
        }
        return true;
    }

    public static function text(array $data): string {
        $a = (object)$data;
        $text = get_string('scenario', 'qtype_linuxcmd', $a) . "\n\n" .
            get_string('task_' . $data['type'], 'qtype_linuxcmd', $a) . "\n\n";
        if ($data['target'] === null) {
            $text .= get_string('rule_' . $data['sourcemode'], 'qtype_linuxcmd');
        } else {
            $text .= get_string('rule_' . $data['mode'], 'qtype_linuxcmd');
        }
        return $text . "\n" . get_string('onecommand', 'qtype_linuxcmd');
    }
}
