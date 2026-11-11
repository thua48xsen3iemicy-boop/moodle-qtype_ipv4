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
        foreach (\qtype_ipv4\exercise::SUBNET_TYPES as $type) {
            foreach (['text', 'choice'] as $mode) {
                $question = new \qtype_ipv4_question();
                $question->tasktype = $type;
                $question->prefixes = '26';
                $question->answermode = $mode;
                $step = new \question_attempt_step();
                $question->start_attempt($step, 1);
                $restored = new \qtype_ipv4_question();
                $restored->answermode = 'changed';
                $restored->apply_attempt_state($step);
                $this->assertSame($question->ip, $restored->ip);
                $this->assertSame(26, $restored->prefix);
                $this->assertSame($type, $restored->tasktype);
                $this->assertSame($question->exercise, $restored->exercise);
                $this->assertSame($question->choices, $restored->choices);
                $this->assertSame($mode, $restored->answermode);
                $correct = $restored->get_correct_response();
                $this->assertSame(1.0, $restored->grade_response($correct)[0]);
                $this->assertSame(1.0, $restored->grade_response(['answer' => ' ' . $correct['answer'] . ' '])[0]);
                foreach ($restored->choices as $answer) {
                    $this->assertSame($answer === $correct['answer'] ? 1.0 : 0.0,
                            $restored->grade_response(['answer' => $answer])[0]);
                }
                $this->assertFalse($restored->is_complete_response(['answer' => 'bad']));
                $this->assertFalse($restored->is_complete_response([]));
                $this->assertNotEmpty($restored->get_validation_error(['answer' => 'bad']));
            }
        }
    }

    public function test_old_attempt_state_remains_readable(): void {
        $step = new \question_attempt_step();
        $step->set_qt_var('_ipv4', json_encode([
            'ip' => '10.37.19.214', 'prefix' => 16, 'mode' => 'text',
            'choices' => ['10.37.0.0', '10.38.0.0', '10.37.0.1', '10.37.255.255'],
        ]));
        $question = new \qtype_ipv4_question();
        $question->tasktype = 'hostcount';
        $question->apply_attempt_state($step);
        $this->assertSame('network', $question->tasktype);
        $this->assertSame(['answer' => '10.37.0.0'], $question->get_correct_response());
    }

    public function test_new_network_questions_require_the_correct_prefix_in_both_modes(): void {
        foreach (\qtype_ipv4\network::PREFIXES as $prefix) {
            foreach (['text', 'choice'] as $mode) {
                $question = new \qtype_ipv4_question();
                $question->prefixes = (string)$prefix;
                $question->answermode = $mode;
                $step = new \question_attempt_step();
                $question->start_attempt($step, 1);
                $correct = \qtype_ipv4\network::address($question->ip, $prefix) . '/' . $prefix;
                $this->assertSame('cidr', $question->exercise['networkformat']);
                $this->assertSame(['answer' => $correct], $question->get_correct_response());
                $this->assertSame(1.0, $question->grade_response(['answer' => $correct])[0]);
                $bare = explode('/', $correct)[0];
                $this->assertFalse($question->is_complete_response(['answer' => $bare]));
                $this->assertSame(0.0, $question->grade_response(['answer' => $bare])[0]);
                $wrongprefix = $prefix === 30 ? 29 : $prefix + 1;
                $this->assertSame(0.0, $question->grade_response(['answer' => $bare . '/' . $wrongprefix])[0]);
                $this->assertSame(0.0, $question->grade_response([
                    'answer' => \qtype_ipv4\network::offset($bare, 1) . '/' . $prefix,
                ])[0]);
                foreach ($question->choices as $choice) {
                    $this->assertTrue(str_ends_with($choice, '/' . $prefix));
                }
                $this->assertStringNotContainsString('[[', $question->task_text());
                $this->assertStringNotContainsString('[[', $question->get_validation_error(['answer' => $bare]));
                $restored = new \qtype_ipv4_question();
                $restored->apply_attempt_state($step);
                $this->assertSame($question->exercise, $restored->exercise);
                $this->assertSame(1.0, $restored->grade_response(['answer' => $correct])[0]);
                $this->assertSame(0.0, $restored->grade_response(['answer' => $bare])[0]);
            }
        }
    }

    public function test_legacy_network_attempts_keep_their_original_bare_address_contract(): void {
        foreach (['text', 'choice'] as $mode) {
            $snapshot = ['type' => 'network', 'ip' => '10.37.19.214', 'prefix' => 16, 'mode' => $mode,
                'choices' => ['10.37.0.0', '10.38.0.0', '10.37.0.1', '10.37.255.255']];
            $step = new \question_attempt_step();
            $step->set_qt_var('_ipv4', json_encode($snapshot));
            $question = new \qtype_ipv4_question();
            $question->apply_attempt_state($step);
            $this->assertSame($snapshot, $question->exercise);
            $this->assertSame(['answer' => '10.37.0.0'], $question->get_correct_response());
            $this->assertSame(1.0, $question->grade_response(['answer' => '10.37.0.0'])[0]);
            $this->assertSame(0.0, $question->grade_response(['answer' => '10.37.0.0/16'])[0]);
            $this->assertSame(get_string('task_network', 'qtype_ipv4', (object)$snapshot), $question->task_text());
        }
    }

    public function test_database_reload_continue_finish_review_and_regrade(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        foreach (\qtype_ipv4\exercise::TYPES as $type) {
            foreach (['text', 'choice'] as $mode) {
                $record = $generator->create_question('ipv4', 'network',
                        ['category' => $category->id, 'answermode' => $mode, 'prefixes' => '26', 'tasktype' => $type]);
                $quba = \question_engine::make_questions_usage_by_activity('core_question', \context_system::instance());
                $quba->set_preferred_behaviour('deferredfeedback');
                $slot = $quba->add_question(\question_bank::load_question($record->id));
                $quba->start_all_questions();
                $original = $quba->get_question($slot);
                $snapshot = [$original->ip, $original->prefix, $original->choices, $original->answermode, $original->exercise];
                $correct = $original->get_correct_response();
                \question_engine::save_questions_usage_by_activity($quba);
                $id = $quba->get_id();
                $quba = \question_engine::load_questions_usage_by_activity($id);
                $loaded = $quba->get_question($slot);
                $this->assertSame($snapshot, [$loaded->ip, $loaded->prefix, $loaded->choices, $loaded->answermode, $loaded->exercise]);
                $quba->process_action($slot, $correct);
                \question_engine::save_questions_usage_by_activity($quba);
                $quba = \question_engine::load_questions_usage_by_activity($id);
                $this->assertSame($correct['answer'], $quba->get_question_attempt($slot)->get_last_qt_var('answer'));
                $quba->finish_all_questions();
                \question_engine::save_questions_usage_by_activity($quba);
                $quba = \question_engine::load_questions_usage_by_activity($id);
                $this->assertSame(1.0, $quba->get_question_fraction($slot));
                $reviewed = $quba->get_question($slot);
                $this->assertSame($snapshot, [$reviewed->ip, $reviewed->prefix, $reviewed->choices, $reviewed->answermode, $reviewed->exercise]);
                $quba->regrade_all_questions();
                $this->assertSame(1.0, $quba->get_question_fraction($slot));
                $this->assertSame($snapshot[2], $quba->get_question($slot)->choices);
            }
        }
    }
}
