<?php
// Moodle plugin, GPL v3 or later.
namespace qtype_linuxcmd;
defined('MOODLE_INTERNAL') || die();

#[\PHPUnit\Framework\Attributes\CoversClass(permissions::class)]
final class permissions_test extends \basic_testcase {
    public function test_known_modes_include_uppercase_special_bits(): void {
        foreach ([0644 => 'rw-r--r--', 0755 => 'rwxr-xr-x', 04755 => 'rwsr-xr-x',
                04644 => 'rwSr--r--', 02750 => 'rwxr-s---', 02640 => 'rw-r-S---',
                01777 => 'rwxrwxrwt', 01666 => 'rw-rw-rwT', 07000 => '--S--S--T',
                07777 => 'rwsrwsrwt', 0000 => '---------'] as $mode => $letters) {
            $this->assertSame($letters, permissions::letters($mode));
            $d = exercise::generate('modeletters', 'random');
            $d['goal'] = $mode; $d['answer'] = $letters;
            $this->assertTrue(exercise::correct($letters, $d));
            $this->assertFalse(exercise::correct('-' . $letters, $d));
            $d['answerkind'] = 'octal';
            $this->assertTrue(exercise::correct(sprintf('%04o', $mode), $d));
            $this->assertFalse(exercise::correct('8' . sprintf('%04o', $mode), $d));
            $this->assertFalse(exercise::correct(sprintf('%04o', $mode ^ 1), $d));
        }
    }

    public function test_chmod_checks_effect_format_and_preservation(): void {
        $d = exercise::generate('chmodsymbolic', 'relative');
        $d['initial'] = 0644; $d['goal'] = 0750;
        $path = basename($d['source']);
        foreach (['u=rwx,g=rx,o=', 'a=,ug+rx,u+w', 'u+x,g+x,o-r'] as $spec) {
            $this->assertTrue(exercise::correct('chmod ' . $spec . ' ' . $path, $d), $spec);
        }
        foreach (['750', 'u+x', 'a=rwx', '+x', 'u+X', 'g=u', 'u+r,,o-r', 'u+r,', 'u+q'] as $spec) {
            $this->assertFalse(exercise::correct('chmod ' . $spec . ' ' . $path, $d), $spec);
        }
        $d['type'] = 'chmodnumeric';
        $this->assertTrue(exercise::correct('chmod 0750 -- ' . $path, $d));
        $this->assertFalse(exercise::correct('chmod u=rwx,g=rx,o= ' . $path, $d));
        foreach (['suid', 'nosuid', 'sgid', 'sticky', 'nosticky'] as $type) {
            $d = exercise::generate($type, 'relative');
            $this->assertTrue(exercise::correct($d['answer'], $d));
            $this->assertFalse(exercise::correct(str_replace('chmod ', 'chmod u-x,', $d['answer']), $d));
            $path = exercise::operand($d['source'], $d['cwd'], 'relative');
            $this->assertFalse(exercise::correct('chmod a=rw ' . $path, $d));
            $this->assertFalse(exercise::correct('chmod u+s,g+s,o+t ' . $path, $d));
        }
        $this->assertSame(02755, permissions::apply_symbolic('g+s,g=rx', 0755, true));
        $this->assertSame(0755, permissions::apply_symbolic('g+s,g=rx', 0755));
        $this->assertSame(04755, permissions::apply_symbolic('u+s', 0755));
        $this->assertSame(02755, permissions::apply_symbolic('g+s', 0755));
        $this->assertSame(01777, permissions::apply_symbolic('o+t', 0777));
        $this->assertSame(0755, permissions::apply_symbolic('u-s', 04755));
        $this->assertSame(0777, permissions::apply_symbolic('o-t', 01777));
    }

    public function test_ownership_recursion_flags_and_paths_are_enforced(): void {
        foreach (['chown', 'chownboth', 'chownrecursive', 'chgrp', 'chgrprecursive'] as $type) {
            $d = exercise::generate($type, 'relative');
            $this->assertTrue(exercise::correct($d['answer'], $d));
            $this->assertTrue(exercise::correct(str_replace('-R', '--recursive', $d['answer']), $d));
            $this->assertFalse(exercise::correct($d['answer'] . ' extra', $d));
            $this->assertFalse(exercise::correct(str_replace($d['owner'], 'nobody',
                str_replace($d['group'], 'nogroup', $d['answer'])), $d));
            $words = exercise::words($d['answer']);
            $last = count($words) - 1;
            foreach ([$d['source'], 'missing/../' . basename($d['source']),
                    basename($d['source']) . '/./../' . basename($d['source']) . '/bad'] as $badpath) {
                $bad = $words; $bad[$last] = $badpath;
                $this->assertFalse(exercise::correct(implode(' ', $bad), $d));
            }
            $this->assertFalse(exercise::correct($d['command'] . ' --reference=other ' . basename($d['source']), $d));
            $this->assertFalse(exercise::correct($d['command'] . ' -f ' . implode(' ', array_slice($words, 1)), $d));
            if ($d['flags']) {
                $this->assertFalse(exercise::correct(str_replace(' -R', '', $d['answer']), $d));
            } else {
                $this->assertFalse(exercise::correct(str_replace($d['command'], $d['command'] . ' -R', $d['answer']), $d));
            }
            if ($type === 'chown') {
                $this->assertFalse(exercise::correct(str_replace($d['owner'], $d['owner'] . ':', $d['answer']), $d));
                $this->assertFalse(exercise::correct(str_replace($d['owner'], $d['owner'] . ':' . $d['group'], $d['answer']), $d));
            }
        }
    }

    public function test_all_permission_tasks_reject_shell_input_and_render(): void {
        foreach (permissions::TYPES as $type) {
            $d = exercise::generate($type, 'random');
            foreach (['; id', ' && id', "\nid", ' > out', ' extra'] as $suffix) {
                $this->assertFalse(exercise::correct($d['answer'] . $suffix, $d));
            }
            $this->assertFalse(exercise::correct('sudo ' . $d['answer'], $d));
            $this->assertFalse(exercise::correct('$(id)', $d));
            $this->assertStringNotContainsString('[[', exercise::text($d));
        }
    }
}
