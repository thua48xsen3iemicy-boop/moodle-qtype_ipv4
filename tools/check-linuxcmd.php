<?php
// GPL v3 or later. Standalone checks; NOT a Moodle integration/PHPUnit run.
define('MOODLE_INTERNAL', true);
define('PARAM_RAW_TRIMMED', 'raw');
$root = dirname(__DIR__);
$strings = [];
foreach (['qtype_linuxcmd' => '/plugins/linuxcmd/lang/en/qtype_linuxcmd.php',
        'practicum' => '/mod/practicum/lang/en/practicum.php', 'qtype_ipv4' => '/lang/en/qtype_ipv4.php'] as $component => $file) {
    $string = [];
    require($root . $file);
    $strings[$component] = $string;
}
function get_string($key, $component, $a = null) {
    global $strings;
    $text = $strings[$component][$key] ?? '[[' . $key . ']]';
    if (is_object($a)) {
        foreach (get_object_vars($a) as $name => $value) {
            if (is_scalar($value)) { $text = str_replace('{$a->' . $name . '}', (string)$value, $text); }
        }
    } else if ($a !== null) { $text = str_replace('{$a}', (string)$a, $text); }
    return $text;
}
class invalid_parameter_exception extends Exception {}
class basic_testcase {
    public static int $assertions = 0;
    private function check($ok, $message = '') { self::$assertions++; if (!$ok) { throw new Exception($message ?: 'Assertion failed'); } }
    public function assertTrue($actual, $message = '') { $this->check($actual === true, $message); }
    public function assertFalse($actual, $message = '') { $this->check($actual === false, $message); }
    public function assertNull($actual, $message = '') { $this->check($actual === null, $message); }
    public function assertSame($expected, $actual) { $this->check($expected === $actual, var_export([$expected, $actual], true)); }
    public function assertNotSame($expected, $actual) { $this->check($expected !== $actual); }
    public function assertCount($expected, $actual) { $this->check(count($actual) === $expected); }
    public function assertInstanceOf($expected, $actual) { $this->check($actual instanceof $expected); }
    public function assertStringNotContainsString($needle, $haystack) { $this->check(!str_contains($haystack, $needle), $haystack); }
}
class advanced_testcase extends basic_testcase {
    public ?string $expectedexception = null;
    public function expectException($class) { $this->expectedexception = $class; }
}
class question_definition {}
class question_graded_automatically extends question_definition {}
class question_attempt_step {
    private array $values = [];
    public function set_qt_var($key, $value) { $this->values[$key] = $value; }
    public function get_qt_var($key) { return $this->values[$key]; }
}
class question_state {
    public static function graded_state_for_fraction($fraction) { return $fraction === 1.0 ? 'right' : 'wrong'; }
}
spl_autoload_register(function($class) use ($root) {
    foreach (['qtype_linuxcmd\\' => '/plugins/linuxcmd/classes/', 'qtype_ipv4\\' => '/classes/',
            'mod_practicum\\' => '/mod/practicum/classes/'] as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            require_once($root . $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php');
            return;
        }
    }
});
// The questionbase include resolves to this empty local fixture, not Moodle core.
$fixture = sys_get_temp_dir() . '/linuxcmd-harness-' . getmypid();
mkdir($fixture . '/question/type', 0700, true);
file_put_contents($fixture . '/question/type/questionbase.php', '<?php');
$CFG = (object)['dirroot' => $fixture];
try {
    require($root . '/plugins/linuxcmd/question.php');
    $CFG->dirroot = $root;
    // question_test's require uses the install layout; pre-load the class and use a fixture link.
    mkdir($fixture . '/question/type/linuxcmd');
    file_put_contents($fixture . '/question/type/linuxcmd/question.php', '<?php');
    $CFG->dirroot = $fixture;
    foreach (['exercise', 'question'] as $suite) {
        require($root . '/plugins/linuxcmd/tests/' . $suite . '_test.php');
        $class = '\\qtype_linuxcmd\\' . $suite . '_test';
        $test = new $class();
        foreach (get_class_methods($test) as $method) {
            if (str_starts_with($method, 'test_')) { $test->$method(); echo "$suite::$method OK\n"; }
        }
    }
    require($root . '/mod/practicum/tests/configuration_test.php');
    foreach (get_class_methods('\\mod_practicum\\configuration_test') as $method) {
        if (!str_starts_with($method, 'test_')) { continue; }
        $test = new \mod_practicum\configuration_test();
        try {
            $test->$method();
            if ($test->expectedexception) { throw new Exception('Expected exception was not thrown'); }
        } catch (Throwable $error) {
            if (!$test->expectedexception || !is_a($error, $test->expectedexception)) { throw $error; }
            $test->assertTrue(true);
        }
        echo "configuration::$method OK\n";
    }
    // Only preprocessing is exercised here; this does not render a Moodle form.
    mkdir($fixture . '/course');
    file_put_contents($fixture . '/course/moodleform_mod.php', '<?php class moodleform_mod {}');
    require($root . '/mod/practicum/tests/mod_form_test.php');
    $formtest = new \mod_practicum\mod_form_test();
    foreach (get_class_methods($formtest) as $method) {
        if (str_starts_with($method, 'test_')) { $formtest->$method(); echo "form::$method OK\n"; }
    }
    $check = new basic_testcase();
    $blocks = json_decode(\mod_practicum\local\blueprint::from_form((object)[
        'blocktype' => ['linux_copytree', 'ipv4_network'], 'blockcount' => [2, 1],
        'blockpathmode' => ['mixed'], 'blockprefix' => [[], [26]],
    ]), true);
    $check->assertCount(3, \mod_practicum\local\blueprint::plan($blocks));
    $check->assertCount(count(\qtype_ipv4\exercise::ALL_TYPES) + count(\qtype_linuxcmd\exercise::TYPES), \mod_practicum\local\registry::labels());
    $keys = [];
    foreach (\qtype_linuxcmd\exercise::TYPES as $type) {
        foreach (\qtype_linuxcmd\exercise::MODES as $mode) {
            $task = \mod_practicum\local\registry::get('linux_' . $type);
            $keys[] = $task->template_key(['pathmode' => $mode]);
        }
    }
    $check->assertCount(count(\qtype_linuxcmd\exercise::TYPES) * count(\qtype_linuxcmd\exercise::MODES), array_unique($keys));
    try {
        \mod_practicum\local\registry::get('linux_move')->validate(['pathmode' => 'bad']);
        throw new Exception('Invalid settings were accepted');
    } catch (invalid_parameter_exception $expected) { $check->assertTrue(true); }
    echo basic_testcase::$assertions . " assertions passed (isolated; Moodle core/DB not loaded).\n";
} finally {
    if (is_file($fixture . '/course/moodleform_mod.php')) {
        unlink($fixture . '/course/moodleform_mod.php');
        rmdir($fixture . '/course');
    }
    unlink($fixture . '/question/type/linuxcmd/question.php');
    rmdir($fixture . '/question/type/linuxcmd');
    unlink($fixture . '/question/type/questionbase.php');
    rmdir($fixture . '/question/type');
    rmdir($fixture . '/question');
    rmdir($fixture);
}
