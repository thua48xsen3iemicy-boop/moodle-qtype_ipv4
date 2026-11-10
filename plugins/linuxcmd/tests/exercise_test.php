<?php
// Moodle plugin, GPL v3 or later.
namespace qtype_linuxcmd;
defined('MOODLE_INTERNAL') || die();

#[\PHPUnit\Framework\Attributes\CoversClass(exercise::class)]
final class exercise_test extends \basic_testcase {
    public function test_known_relative_and_mixed_paths(): void {
        $root = '/home/student/practice';
        $d = ['version' => 1, 'type' => 'copyfile', 'mode' => 'relative', 'root' => $root,
            'cwd' => $root . '/documents/drafts',
            'dirs' => [$root, $root . '/documents', $root . '/documents/drafts', $root . '/backup'],
            'files' => [$root . '/documents/report.txt'], 'command' => 'cp', 'flags' => [],
            'source' => $root . '/documents/report.txt', 'target' => $root . '/backup/report.txt',
            'sourcemode' => 'relative', 'targetmode' => 'relative'];
        $this->assertTrue(exercise::correct('cp ../report.txt ../../backup/', $d));
        $this->assertTrue(exercise::correct('cp .././report.txt ../../backup/report.txt', $d));
        $this->assertFalse(exercise::correct('cp report.txt ../backup/', $d));
        $this->assertFalse(exercise::correct('cp ../Report.txt ../../backup/', $d));
        $this->assertFalse(exercise::correct('cp ../report.txt ../../backup/report.txt/', $d));
        $d['targetmode'] = 'absolute';
        $this->assertTrue(exercise::correct('cp ../report.txt /home/student/practice/backup/', $d));
        $this->assertFalse(exercise::correct('cp ../report.txt ../../backup/', $d));
        $this->assertFalse(exercise::correct('cp /home/student/practice/documents/report.txt /home/student/practice/backup/', $d));
    }

    public function test_every_operation_and_path_mode_roundtrips(): void {
        foreach (exercise::TYPES as $type) {
            foreach (exercise::MODES as $mode) {
                for ($i = 0; $i < 20; $i++) {
                    $data = exercise::generate($type, $mode);
                    $data = json_decode(json_encode($data), true);
                    $this->assertTrue(exercise::correct($data['answer'], $data), $data['answer']);
                    $this->assertFalse(exercise::correct('', $data));
                    $this->assertFalse(exercise::correct('sudo ' . $data['answer'], $data));
                    $this->assertFalse(exercise::correct($data['answer'] . '; pwd', $data));
                    $this->assertStringNotContainsString('[[', exercise::text($data));
                }
            }
        }
    }

    public function test_equivalent_paths_options_and_destination_directories(): void {
        $d = exercise::generate('copytree', 'absolute');
        $this->assertTrue(exercise::correct('cp --recursive "' . $d['source'] . '/" "' . dirname($d['target']) . '/"', $d));
        $this->assertTrue(exercise::correct('cp -R -- ' . $d['source'] . ' ' . $d['target'], $d));
        $this->assertTrue(exercise::correct('cp ' . $d['source'] . ' -r ' . $d['target'], $d));
        $this->assertFalse(exercise::correct('cp ' . $d['source'] . ' ' . $d['target'], $d));
        $this->assertTrue(exercise::correct($d['answer'] . '/', $d));
        $this->assertFalse(exercise::correct('cp -r ' . $d['source'] . '/. ' . dirname($d['target']), $d));
        $this->assertTrue(exercise::correct('cp -r ' . $d['source'] . '/. ' . $d['target'], $d));
        $d = exercise::generate('copyfile', 'absolute');
        $this->assertTrue(exercise::correct('cp ' . $d['source'] . ' ' . dirname($d['target']) . '/', $d));
        $this->assertTrue(exercise::correct('cp ' . dirname($d['source']) . '/./' . basename($d['source']) . ' ' . $d['target'], $d));
        $this->assertFalse(exercise::correct('cp ' . $d['source'] . '/ ' . $d['target'], $d));
        $this->assertFalse(exercise::correct('cp ' . $d['source'] . ' ' . $d['target'] . '/', $d));
        $this->assertFalse(exercise::correct('cp ' . $d['source'] . ' ' . dirname($d['target']) . '/missing/../' . basename($d['target']), $d));
        $this->assertFalse(exercise::correct('cp ' . $d['source'] . '/../' . basename($d['source']) . ' ' . $d['target'], $d));
        $d = exercise::generate('mkdirparents', 'absolute');
        $this->assertTrue(exercise::correct('mkdir --parents -- ' . $d['source'], $d));
        $this->assertFalse(exercise::correct('mkdir ' . $d['source'], $d));
        $this->assertFalse(exercise::correct('mkdir -p ' . $d['root'] . '/unrelated/../projects/site/images', $d));
        foreach (['rmdir', 'removetree'] as $type) {
            $d = exercise::generate($type, 'absolute');
            $this->assertFalse(exercise::correct($d['answer'] . '/.', $d));
        }
    }

    public function test_required_path_style_is_not_only_a_location_check(): void {
        $d = exercise::generate('copyfile', 'relative');
        $this->assertFalse(exercise::correct('cp ' . $d['source'] . ' ' . $d['target'], $d));
        $d = exercise::generate('copyfile', 'mixed');
        $words = exercise::words($d['answer']);
        $this->assertFalse(str_starts_with($words[1], '/'));
        $this->assertTrue(str_starts_with($words[2], '/'));
        $this->assertFalse(exercise::correct('cp ' . $d['source'] . ' ' . $d['target'], $d));
        $this->assertFalse(exercise::correct('mv ' . $words[1] . ' ' . $words[2], $d));
        $this->assertFalse(exercise::correct($d['answer'] . ' another.txt', $d));
    }

    public function test_literal_word_grammar_never_accepts_shell_programs(): void {
        foreach (['cp a b && pwd', 'cp $(pwd) b', 'cp `pwd` b', 'cp $HOME/a b',
                'cp a b > out', 'cp a* b', "cp a b\npwd", 'cp "a b', 'cp a b\\'] as $answer) {
            $this->assertNull(exercise::words($answer), $answer);
        }
        $this->assertSame(['cp', 'a b', 'c d'], exercise::words('cp "a b" c\\ d'));
        $this->assertSame(['cp', '$HOME', 'b'], exercise::words("cp '\$HOME' b"));
        $this->assertSame(['cp', 'a\\q', 'b'], exercise::words('cp "a\\q" b'));
        $this->assertSame('/a/c', exercise::resolve('../c', '/a/b'));
        $this->assertSame('/a', exercise::resolve('/../../a', '/b'));
    }
}
