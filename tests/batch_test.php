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
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/** Integration of bulk creation with quiz slots, permissions and student attempts. */
#[\PHPUnit\Framework\Attributes\CoversClass(batch_service::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\qtype_ipv4\form\generate_form::class)]
final class batch_test extends \advanced_testcase {
    private function make_quiz(): \stdClass {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        return $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'questionsperpage' => 0]);
    }

    public function test_add_fifteen_templates_and_start_two_student_attempts(): void {
        global $DB;
        $quiz = $this->make_quiz();
        $this->assertSame(15, batch_service::add($quiz->course, $quiz->cmid, 15, [16, 24], 'choice'));
        $slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id], 'slot');
        $this->assertCount(15, $slots);
        $this->assertSame(15.0, (float)$DB->get_field('quiz', 'sumgrades', ['id' => $quiz->id]));
        $context = \context_module::instance($quiz->cmid);
        $structure = \mod_quiz\question\bank\qbank_helper::get_question_structure($quiz->id, $context);
        foreach ($structure as $slot => $item) {
            $question = \question_bank::load_question($item->questionid);
            $this->assertSame('16,24', $question->prefixes);
            $this->assertSame('choice', $question->answermode);
            // Only the template exists before a student starts.
            $this->assertNull($question->ip);
        }
        $snapshots = [];
        for ($student = 0; $student < 2; $student++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $quiz->course, 'student');
            $this->setUser($user);
            $settings = \mod_quiz\quiz_settings::create($quiz->id, $user->id);
            $attempt = quiz_prepare_and_start_new_attempt($settings, 1, null, false, [], [], $user->id);
            $usage = \question_engine::load_questions_usage_by_activity($attempt->uniqueid);
            $this->assertCount(15, $usage->get_slots());
            foreach ($usage->get_slots() as $slot) {
                $question = $usage->get_question($slot);
                $this->assertNotNull(network::normalise($question->ip));
                $this->assertContains($question->prefix, [16, 24]);
                $this->assertCount(4, $question->choices);
                $snapshots[$student][$slot] = [$question->ip, $question->prefix, $question->choices];
            }
            $reloaded = \question_engine::load_questions_usage_by_activity($attempt->uniqueid);
            foreach ($reloaded->get_slots() as $slot) {
                $question = $reloaded->get_question($slot);
                $this->assertSame($snapshots[$student][$slot], [$question->ip, $question->prefix, $question->choices]);
            }
        }
        $this->assertNotSame($snapshots[0], $snapshots[1]);
        $this->setAdminUser();
        try {
            batch_service::add($quiz->course, $quiz->cmid, 1, [24], 'text');
            $this->fail('A quiz with student attempts must not be changed');
        } catch (\moodle_exception $error) {
            $this->assertSame('cannoteditafterattempts', $error->errorcode);
        }
        $this->assertSame(15, $DB->count_records('quiz_slots', ['quizid' => $quiz->id]));
    }

    public function test_appends_without_changing_existing_questions(): void {
        global $DB;
        $quiz = $this->make_quiz();
        batch_service::add($quiz->course, $quiz->cmid, 1, [8], 'text');
        $first = $DB->get_record('quiz_slots', ['quizid' => $quiz->id]);
        batch_service::add($quiz->course, $quiz->cmid, 2, [24], 'choice');
        $this->assertEquals($first, $DB->get_record('quiz_slots', ['id' => $first->id]));
        $this->assertSame(3, $DB->count_records('quiz_slots', ['quizid' => $quiz->id]));
        $this->assertSame(3.0, (float)$DB->get_field('quiz', 'sumgrades', ['id' => $quiz->id]));
    }

    public function test_each_exercise_type_is_saved_in_bulk(): void {
        $quiz = $this->make_quiz();
        foreach (exercise::TYPES as $type) {
            $this->assertSame(1, batch_service::add($quiz->course, $quiz->cmid, 1, [26], 'choice', $type));
        }
        $context = \context_module::instance($quiz->cmid);
        $structure = \mod_quiz\question\bank\qbank_helper::get_question_structure($quiz->id, $context);
        $types = [];
        foreach ($structure as $item) {
            $question = \question_bank::load_question($item->questionid);
            $types[] = $question->tasktype;
            $this->assertSame('26', $question->prefixes);
        }
        $this->assertSame(exercise::TYPES, $types);
    }

    public function test_student_cannot_generate_questions(): void {
        global $DB;
        $quiz = $this->make_quiz();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $quiz->course, 'student');
        $this->setUser($user);
        $this->assertSame([], batch_service::available_quizzes(get_course($quiz->course)));
        try {
            batch_service::add($quiz->course, $quiz->cmid, 15, [24], 'text');
            $this->fail('Student must not add questions');
        } catch (\required_capability_exception $error) {
            $this->assertSame(0, $DB->count_records('quiz_slots', ['quizid' => $quiz->id]));
        }
    }

    public function test_invalid_settings_do_not_write_questions(): void {
        global $DB;
        $quiz = $this->make_quiz();
        $before = $DB->count_records('question');
        foreach ([[0, [24], 'text'], [101, [24], 'text'], [15, [], 'text'],
                [15, [31], 'text'], [15, [24], 'invalid']] as [$count, $prefixes, $mode]) {
            try {
                batch_service::add($quiz->course, $quiz->cmid, $count, $prefixes, $mode);
                $this->fail('Invalid settings must be rejected');
            } catch (\invalid_parameter_exception $error) {
                $this->assertSame($before, $DB->count_records('question'));
                $this->assertSame(0, $DB->count_records('quiz_slots', ['quizid' => $quiz->id]));
            }
        }
    }

    public function test_quiz_in_another_course_is_rejected(): void {
        global $DB;
        $quiz = $this->make_quiz();
        $other = $this->getDataGenerator()->create_course();
        try {
            batch_service::add($other->id, $quiz->cmid, 15, [24], 'text');
            $this->fail('A quiz from another course must be rejected');
        } catch (\moodle_exception $error) {
            $this->assertSame(0, $DB->count_records('quiz_slots', ['quizid' => $quiz->id]));
        }
    }

    public function test_form_validates_count_prefixes_and_course_quiz(): void {
        $quiz = $this->make_quiz();
        $custom = ['courseid' => $quiz->course, 'quizzes' => [$quiz->cmid => $quiz->name]];
        $data = ['courseid' => $quiz->course, 'cmid' => $quiz->cmid, 'count' => 15,
            'prefix8' => 0, 'prefix16' => 0, 'prefix24' => 1, 'answermode' => 'choice'];
        \qtype_ipv4\form\generate_form::mock_submit($data);
        $form = new \qtype_ipv4\form\generate_form(null, $custom);
        $this->assertTrue($form->is_validated());
        $this->assertSame(15, $form->get_data()->count);
        $data['count'] = 101;
        $data['prefix24'] = 0;
        $data['cmid'] = 999999;
        \qtype_ipv4\form\generate_form::mock_submit($data);
        $form = new \qtype_ipv4\form\generate_form(null, $custom);
        $errors = $form->validation($data, []);
        $this->assertArrayHasKey('count', $errors);
        $this->assertArrayHasKey('allowedprefixes', $errors);
        $this->assertArrayHasKey('cmid', $errors);
    }
}
