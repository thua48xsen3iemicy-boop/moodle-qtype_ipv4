<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_ipv4;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/ipv4/question.php');

/** Tests the IPv4 question implementation. */
#[\PHPUnit\Framework\Attributes\CoversClass(\qtype_ipv4_question::class)]
final class question_test extends \advanced_testcase {
    public function test_state_and_grading_for_both_modes(): void {
        foreach (['text', 'choice'] as $mode) {
            $question = new \qtype_ipv4_question();
            $question->prefixes = '24';
            $question->answermode = $mode;
            $step = new \question_attempt_step();
            $question->start_attempt($step, 1);
            $restored = new \qtype_ipv4_question();
            $restored->answermode = 'changed';
            $restored->apply_attempt_state($step);
            $this->assertSame($question->ip, $restored->ip);
            $this->assertSame(24, $restored->prefix);
            $this->assertSame($question->choices, $restored->choices);
            $this->assertSame($mode, $restored->answermode);
            $correct = $restored->get_correct_response();
            $this->assertSame(1.0, $restored->grade_response($correct)[0]);
            $this->assertSame(1.0, $restored->grade_response(['answer' => ' ' . $correct['answer'] . ' '])[0]);
            foreach ($restored->choices as $answer) {
                $this->assertSame($answer === $correct['answer'] ? 1.0 : 0.0,
                        $restored->grade_response(['answer' => $answer])[0]);
            }
            $this->assertFalse($restored->is_complete_response(['answer' => '10.0.0.0/8']));
            $this->assertFalse($restored->is_complete_response([]));
            $this->assertNotEmpty($restored->get_validation_error(['answer' => 'bad']));
        }
    }

    public function test_database_reload_continue_finish_review_and_regrade(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        foreach (['text', 'choice'] as $mode) {
            $record = $generator->create_question('ipv4', 'network',
                    ['category' => $category->id, 'answermode' => $mode, 'prefixes' => '16,24']);
            $quba = \question_engine::make_questions_usage_by_activity('core_question', \context_system::instance());
            $quba->set_preferred_behaviour('deferredfeedback');
            $slot = $quba->add_question(\question_bank::load_question($record->id));
            $quba->start_all_questions();
            $original = $quba->get_question($slot);
            $snapshot = [$original->ip, $original->prefix, $original->choices, $original->answermode];
            $correct = $original->get_correct_response();
            \question_engine::save_questions_usage_by_activity($quba);
            $id = $quba->get_id();
            $quba = \question_engine::load_questions_usage_by_activity($id);
            $loaded = $quba->get_question($slot);
            $this->assertSame($snapshot, [$loaded->ip, $loaded->prefix, $loaded->choices, $loaded->answermode]);
            $quba->process_action($slot, $correct);
            \question_engine::save_questions_usage_by_activity($quba);
            $quba = \question_engine::load_questions_usage_by_activity($id);
            $this->assertSame($correct['answer'], $quba->get_question_attempt($slot)->get_last_qt_var('answer'));
            $quba->finish_all_questions();
            \question_engine::save_questions_usage_by_activity($quba);
            $quba = \question_engine::load_questions_usage_by_activity($id);
            $this->assertSame(1.0, $quba->get_question_fraction($slot));
            $reviewed = $quba->get_question($slot);
            $this->assertSame($snapshot, [$reviewed->ip, $reviewed->prefix, $reviewed->choices, $reviewed->answermode]);
            $quba->regrade_all_questions();
            $this->assertSame(1.0, $quba->get_question_fraction($slot));
            $this->assertSame($snapshot[2], $quba->get_question($slot)->choices);
        }
    }
}
