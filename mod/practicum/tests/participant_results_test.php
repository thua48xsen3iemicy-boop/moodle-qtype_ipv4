<?php
// Moodle plugin, GPL v3 or later.
namespace mod_practicum;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/practicum/lib.php');

/** Course membership, attempt history and teacher-only roster access. */
#[\PHPUnit\Framework\Attributes\CoversClass(local\participant_results::class)]
final class participant_results_test extends \advanced_testcase {
    public static function modes(): array {
        return ['training' => ['training'], 'exam' => ['exam']];
    }

    private function activity(string $mode = 'exam'): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $activity = $generator->create_module('practicum',
            ['course' => $course->id, 'mode' => $mode, 'maxattempts' => 0]);
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        return [$course, $activity, $teacher, \context_module::instance($activity->cmid)];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('modes')]
    public function test_enrolled_students_include_not_started_without_creating_attempts(string $mode): void {
        global $DB;
        [$course, $activity, $teacher, $context] = $this->activity($mode);
        $generator = $this->getDataGenerator();
        $students = [];
        for ($i = 0; $i < 3; $i++) {
            $students[] = $generator->create_user();
            $generator->enrol_user($students[$i]->id, $course->id, 'student');
        }
        $outsider = $generator->create_user();
        $othercourse = $generator->create_course();
        $generator->enrol_user($outsider->id, $othercourse->id, 'student');
        $this->setUser($students[1]);
        $active = local\attempts::start($activity, $context, $students[1]->id);
        $this->setUser($students[2]);
        $finished = local\attempts::start($activity, $context, $students[2]->id);
        local\attempts::process($finished->id, null, true);
        $this->setUser($teacher);
        $rows = local\participant_results::rows($context);
        $this->assertCount(3, $rows);
        $byuser = [];
        foreach ($rows as $row) {
            $byuser[$row['user']->id] = $row['attempt'];
        }
        $this->assertEqualsCanonicalizing(array_map(fn($user) => $user->id, $students), array_keys($byuser));
        $this->assertNull($byuser[$students[0]->id]);
        $this->assertEquals($active->id, $byuser[$students[1]->id]->id);
        $this->assertEquals(0, $byuser[$students[1]->id]->timefinish);
        $this->assertGreaterThan(0, $byuser[$students[2]->id]->timefinish);
        $this->assertEquals(0, $byuser[$students[2]->id]->grade);
        $this->assertEquals(2, $DB->count_records('practicum_attempts', ['practicumid' => $activity->id]));
        $this->assertFalse($DB->record_exists('practicum_attempts', ['userid' => $students[0]->id]));
    }

    public function test_suspended_enrolment_and_custom_learner_role_are_included(): void {
        [$course, $activity, $teacher, $context] = $this->activity();
        $generator = $this->getDataGenerator();
        $suspended = $generator->create_user();
        $generator->enrol_user($suspended->id, $course->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $custom = $generator->create_user();
        $role = create_role('Practicum learner', 'practicumlearner', '');
        assign_capability('mod/practicum:attempt', CAP_ALLOW, $role, $context->id);
        $generator->enrol_user($custom->id, $course->id, $role);
        $this->setUser($teacher);
        $rows = local\participant_results::rows($context);
        $this->assertEqualsCanonicalizing([$suspended->id, $custom->id],
            array_map(fn($row) => $row['user']->id, $rows));
        foreach ($rows as $row) {
            $this->assertNull($row['attempt']);
        }
    }

    public function test_multiple_attempts_remain_visible_after_unenrolment(): void {
        global $DB;
        [$course, $activity, $teacher, $context] = $this->activity();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $first = local\attempts::start($activity, $context, $student->id);
        local\attempts::process($first->id, null, true);
        $second = local\attempts::start($activity, $context, $student->id);
        $enrolment = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        enrol_get_plugin('manual')->unenrol_user($enrolment, $student->id);
        $this->setUser($teacher);
        $rows = local\participant_results::rows($context);
        $this->assertCount(2, $rows);
        $this->assertEquals([$second->id, $first->id], array_map(fn($row) => $row['attempt']->id, $rows));
        $this->assertEquals([$student->id, $student->id], array_map(fn($row) => $row['user']->id, $rows));
    }

    public function test_roster_finalizes_expired_work_without_changing_the_plan(): void {
        global $DB;
        [$course, $activity, $teacher, $context] = $this->activity();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $attempt = local\attempts::start($activity, $context, $student->id);
        $DB->set_field('practicum_attempts', 'deadline', time() - local\attempts::SUBMISSION_GRACE - 1,
            ['id' => $attempt->id]);
        $this->setUser($teacher);
        $rows = local\participant_results::rows($context);
        $this->assertCount(1, $rows);
        $this->assertGreaterThan(0, $rows[0]['attempt']->timefinish);
        $this->assertSame($attempt->plan, $rows[0]['attempt']->plan);
    }

    public function test_student_cannot_request_the_teacher_roster(): void {
        [$course, $activity, $teacher, $context] = $this->activity();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        local\participant_results::rows($context);
    }
}
