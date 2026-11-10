<?php
// Moodle plugin, GPL v3 or later.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/questionbase.php');

/** Literal command response graded without running a shell. */
class qtype_linuxcmd_question extends question_graded_automatically {
    public $tasktype = 'copyfile';
    public $pathmode = 'relative';
    public $exercise = [];

    public function start_attempt(question_attempt_step $step, $variant) {
        $data = \qtype_linuxcmd\exercise::generate($this->tasktype, $this->pathmode);
        $step->set_qt_var('_linuxcmd', json_encode($data, JSON_THROW_ON_ERROR));
        $this->apply_attempt_state($step);
    }
    public function apply_attempt_state(question_attempt_step $step) {
        $this->exercise = json_decode($step->get_qt_var('_linuxcmd'), true, 512, JSON_THROW_ON_ERROR);
        $this->tasktype = $this->exercise['type'];
        $this->pathmode = $this->exercise['mode'];
    }
    public function get_expected_data() { return ['answer' => PARAM_RAW_TRIMMED]; }
    public function get_correct_response() { return ['answer' => $this->exercise['answer']]; }
    public function is_complete_response(array $response) { return trim($response['answer'] ?? '') !== ''; }
    public function is_gradable_response(array $response) { return $this->is_complete_response($response); }
    public function get_validation_error(array $response) {
        return $this->is_complete_response($response) ? '' : get_string('enteranswer', 'qtype_linuxcmd');
    }
    public function grade_response(array $response) {
        $fraction = \qtype_linuxcmd\exercise::correct((string)($response['answer'] ?? ''), $this->exercise) ? 1.0 : 0.0;
        return [$fraction, question_state::graded_state_for_fraction($fraction)];
    }
    public function summarise_response(array $response) { return isset($response['answer']) ? trim($response['answer']) : null; }
    public function is_same_response(array $prevresponse, array $newresponse) {
        return trim($prevresponse['answer'] ?? '') === trim($newresponse['answer'] ?? '');
    }
    public function task_text(): string { return \qtype_linuxcmd\exercise::text($this->exercise); }
    public function get_question_summary() { return parent::get_question_summary() . ' ' . $this->task_text(); }
}
