<?php
// GPL v3 or later. Isolated policy checks; NOT Moodle/core/DB integration.
namespace mod_practicum\local {
    function time() { return $GLOBALS['clock']; }
}
namespace core\lock {
    class lock_config {
        public static function get_lock_factory($type) { return new self(); }
        public function get_lock($key, $timeout) { return new policy_lock(); }
    }
    class policy_lock {
        public function release() { $GLOBALS['released']++; }
    }
}
namespace {
    define('MOODLE_INTERNAL', true);
    define('MUST_EXIST', true);
    class context_module {}
    class moodle_exception extends \Exception {
        public function __construct($code, $component) { parent::__construct($code); }
    }
    function require_capability($capability, $context, $userid) {}
    function practicum_update_grades($instance, $userid) { $GLOBALS['grades']++; }
    class policy_db {
        public object $attempt;
        public function __construct() {
            $this->attempt = (object)['id' => 1, 'practicumid' => 1, 'userid' => 2, 'usageid' => 3,
                'mode' => 'exam', 'plan' => 'frozen plan', 'deadline' => 1000, 'timefinish' => 0, 'grade' => null];
        }
        public function get_record($table, $conditions, ...$options) {
            if ($table === 'practicum') { return (object)['id' => 1]; }
            foreach ($conditions as $field => $value) {
                if ($this->attempt->$field != $value) { return false; }
            }
            return clone $this->attempt;
        }
        public function count_records($table, $conditions) { return 1; }
        public function update_record($table, $record) { $this->attempt = clone $record; }
        public function start_delegated_transaction() {
            return new class {
                public function allow_commit() {}
                public function rollback($error) { throw $error; }
            };
        }
    }
    class policy_usage {
        public array $answers = [];
        public int $actions = 0;
        public int $finishes = 0;
        public function process_all_actions($timestamp, $post) { $this->answers = $post; $this->actions++; }
        public function process_all_autosaves($timestamp, $post) { $this->process_all_actions($timestamp, $post); }
        public function finish_all_questions() { $this->finishes++; }
        public function get_total_mark() { return count(array_filter($this->answers, fn($v) => $v === 'correct')); }
        public function get_slots() { return [1, 2, 3]; }
    }
    class question_engine {
        public static policy_usage $usage;
        public static function load_questions_usage_by_activity($id) { return self::$usage; }
        public static function save_questions_usage_by_activity($usage) {}
    }
    require dirname(__DIR__) . '/mod/practicum/classes/local/attempts.php';
    use mod_practicum\local\attempts;
    $assertions = 0;
    function check($condition) {
        $GLOBALS['assertions']++;
        if (!$condition) { throw new \Exception('Assertion ' . $GLOBALS['assertions'] . ' failed'); }
    }
    function reset_policy($now) {
        $GLOBALS['clock'] = $now;
        $GLOBALS['DB'] = new policy_db();
        $GLOBALS['released'] = $GLOBALS['grades'] = 0;
        question_engine::$usage = new policy_usage();
    }
    // Arrival before deadline, at deadline, inside transport window and at its exact end.
    foreach ([999 => false, 1000 => true, 1029 => true, 1030 => true] as $now => $finished) {
        reset_policy($now);
        $result = attempts::process(1, ['correct', 'correct']);
        check((bool)$result->timefinish === $finished);
        check(question_engine::$usage->actions === ($now < 1030 ? 1 : 0));
        check($GLOBALS['released'] === 1);
        if ($finished) {
            check(abs($result->grade - ($now < 1030 ? 200 / 3 : 0)) < 0.00001);
            check($GLOBALS['grades'] === 1);
            check(attempts::process(1, ['correct', 'correct', 'correct'], true) == $result);
            check($GLOBALS['grades'] === 1);
        }
    }
    // GET/cron during grace cannot beat the final browser POST to finishing the attempt.
    foreach ([1000, 1029] as $now) {
        reset_policy($now);
        check(attempts::process(1)->timefinish === 0);
        check(question_engine::$usage->finishes === 0);
        check(attempts::process(1, ['correct'], true)->grade > 0);
    }
    // Autosaved partial work survives cron, late POSTs and repeated finishing.
    reset_policy(999);
    attempts::process(1, ['correct', '', 'wrong'], false, true);
    $GLOBALS['clock'] = 1030;
    $result = attempts::process(1);
    check(abs($result->grade - 100 / 3) < 0.00001);
    check(attempts::process(1, ['wrong'], true) == $result);
    check(question_engine::$usage->answers === ['correct', '', 'wrong']);
    check(question_engine::$usage->finishes === 1);
    check($GLOBALS['grades'] === 1);
    // An expired readonly form finishes saved answers without posting empty response fields.
    reset_policy(1001);
    question_engine::$usage->answers = ['correct'];
    check(attempts::process(1, null, true)->grade > 0);
    check(question_engine::$usage->actions === 0);
    // Active start calls return the same usage/plan; exhausted starts cannot regenerate.
    reset_policy(999);
    $instance = (object)['id' => 1, 'mode' => 'exam', 'maxattempts' => 1];
    for ($i = 0; $i < 3; $i++) {
        $resumed = attempts::start($instance, new context_module(), 2);
        check($resumed->usageid === 3 && $resumed->plan === 'frozen plan');
    }
    attempts::process(1, null, true);
    for ($i = 0; $i < 3; $i++) {
        try {
            attempts::start($instance, new context_module(), 2);
            check(false);
        } catch (moodle_exception $error) {
            check($error->getMessage() === 'nomoreattempts');
        }
    }
    check($GLOBALS['DB']->attempt->usageid === 3);
    // Untimed training remains unfinished by time and does not write gradebook.
    reset_policy(5000);
    $GLOBALS['DB']->attempt->deadline = 0;
    $GLOBALS['DB']->attempt->mode = 'training';
    check(attempts::process(1, ['correct'])->timefinish === 0);
    check(attempts::process(1, null, true)->grade > 0);
    check($GLOBALS['grades'] === 0);
    echo "$assertions lifecycle policy assertions passed (stand-ins; NOT native Moodle integration).\n";
}
