<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/questionbase.php');

/** Automatically graded network-address exercise. */
class qtype_ipv4_question extends question_graded_automatically {
    /** @var string comma-separated allowed prefixes */
    public $prefixes = '8,16,24';
    /** @var string text or choice */
    public $answermode = 'text';
    /** @var string generated IPv4 */
    public $ip;
    /** @var int generated prefix */
    public $prefix;
    /** @var string[] saved answer order */
    public $choices = [];
    /** @var string exercise kind */
    public $tasktype = 'network';
    /** @var array persisted exercise parameters */
    public $exercise = [];

    /** @var array|null additional command/diagnostic state */
    public $exerciseparams = null;

    public function start_attempt(question_attempt_step $step, $variant) {
        $data = \qtype_ipv4\exercise::generate($this->tasktype, array_map('intval', explode(',', $this->prefixes)));
        $data['mode'] = isset($data['kind']) ? 'text' : $this->answermode;
        $step->set_qt_var('_ipv4', json_encode($data, JSON_THROW_ON_ERROR));
        $this->apply_attempt_state($step);
    }

    public function apply_attempt_state(question_attempt_step $step) {
        // Never regenerate here: reload, review and regrade must use the original exercise.
        $data = json_decode($step->get_qt_var('_ipv4'), true, 512, JSON_THROW_ON_ERROR);
        $this->exercise = $data;
        $this->tasktype = $data['type'] ?? $data['exercise'] ?? 'network';
        $this->exerciseparams = isset($data['kind']) ? $data : null;
        $this->ip = $data['ip'] ?? $data['params']['address'];
        $this->prefix = (int)($data['prefix'] ?? $data['params']['prefix']);
        $this->choices = $data['choices'] ?? [];
        $this->answermode = $data['mode'] ?? 'text';
    }

    public function get_expected_data() {
        return ['answer' => PARAM_RAW_TRIMMED];
    }

    public function get_correct_response() {
        $data = $this->exercise ?: ['ip' => $this->ip, 'prefix' => $this->prefix, 'type' => $this->tasktype];
        return ['answer' => \qtype_ipv4\exercise::answer($data)];
    }

    public function is_complete_response(array $response) {
        $answer = \qtype_ipv4\exercise::normalise((string)($response['answer'] ?? ''), $this->tasktype);
        return $answer !== null && ($this->answermode !== 'choice' || in_array($answer, $this->choices, true));
    }

    public function is_gradable_response(array $response) {
        return $this->is_complete_response($response);
    }

    public function get_validation_error(array $response) {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return get_string($this->answermode === 'choice' ? 'selectanswer' : 'invalidanswer', 'qtype_ipv4');
    }

    public function grade_response(array $response) {
        if ($this->exerciseparams !== null) {
            $fraction = \qtype_ipv4\exercise\catalog::correct($this->exerciseparams,
                    (string)($response['answer'] ?? '')) ? 1.0 : 0.0;
            return [$fraction, question_state::graded_state_for_fraction($fraction)];
        }
        $correct = $this->is_complete_response($response) &&
                \qtype_ipv4\exercise::normalise((string)$response['answer'], $this->tasktype) ===
                    $this->get_correct_response()['answer'];
        $fraction = $correct ? 1.0 : 0.0;
        return [$fraction, question_state::graded_state_for_fraction($fraction)];
    }

    public function summarise_response(array $response) {
        return isset($response['answer']) ? trim($response['answer']) : null;
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return trim($prevresponse['answer'] ?? '') === trim($newresponse['answer'] ?? '');
    }

    public function task_text(): string {
        if ($this->exerciseparams !== null) {
            return \qtype_ipv4\exercise\catalog::task($this->exerciseparams);
        }
        $data = $this->exercise + ['ip' => $this->ip, 'prefix' => $this->prefix];
        return get_string('task_' . $this->tasktype, 'qtype_ipv4', (object)$data);
    }

    public function get_question_summary() {
        return parent::get_question_summary() . ' ' . $this->task_text();
    }
}
