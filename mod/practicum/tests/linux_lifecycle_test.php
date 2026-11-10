<?php
// Moodle plugin, GPL v3 or later.
namespace mod_practicum;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/practicum/lib.php');

#[\PHPUnit\Framework\Attributes\CoversClass(local\linux_task::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(local\attempts::class)]
final class linux_lifecycle_test extends \advanced_testcase {
    public function test_linux_backup_restore_keeps_options_and_attempt_state(): void {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $g = $this->getDataGenerator();
        $course = $g->create_course();
        $activity = $g->create_module('practicum', [
            'course' => $course->id, 'mode' => 'exam', 'selectionmode' => 'once',
            'selectedgroups' => ['files' => 1, 'permissions' => 1],
        ]);
        $student = $g->create_user();
        $g->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $attempt = local\attempts::start($activity, \context_module::instance($activity->cmid), $student->id);
        $usage = \question_engine::load_questions_usage_by_activity($attempt->usageid);
        $snapshots = [];
        foreach ($usage->get_slots() as $slot) {
            $snapshots[$slot] = $usage->get_question($slot)->exercise;
            $usage->process_action($slot, $usage->get_question($slot)->get_correct_response());
        }
        \question_engine::save_questions_usage_by_activity($usage);
        local\attempts::process($attempt->id, null, true);
        $this->setAdminUser();
        $target = $g->create_course();
        $backup = new \backup_controller(\backup::TYPE_1COURSE, $course->id, \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id);
        $backup->get_plan()->get_setting('users')->set_value(true);
        $id = $backup->get_backupid();
        $backup->execute_plan();
        $results = $backup->get_results();
        $backup->destroy();
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname(
            $results['backup_destination'], make_backup_temp_directory($id));
        $restore = new \restore_controller($id, $target->id, \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL, $USER->id, \backup::TARGET_CURRENT_ADDING);
        $this->assertTrue($restore->execute_precheck());
        $restore->execute_plan();
        $restore->destroy();
        $restored = $DB->get_record('practicum', ['course' => $target->id], '*', MUST_EXIST);
        $this->assertSame($activity->blocks, $restored->blocks);
        $restoredattempt = $DB->get_record('practicum_attempts', ['practicumid' => $restored->id], '*', MUST_EXIST);
        $restoredusage = \question_engine::load_questions_usage_by_activity($restoredattempt->usageid);
        $this->assertCount(count($snapshots), $restoredusage->get_slots());
        foreach ($snapshots as $slot => $snapshot) {
            $this->assertSame($snapshot, $restoredusage->get_question($slot)->exercise);
            $this->assertSame($snapshot['type'], $restoredusage->get_question($slot)->tasktype);
            $this->assertSame($snapshot['mode'], $restoredusage->get_question($slot)->pathmode);
        }
        $this->assertEquals(count($snapshots), $restoredusage->get_total_mark());
    }

    public function test_mixed_families_reload_finish_and_grade_in_both_modes(): void {
        global $DB;
        $this->resetAfterTest();
        foreach (['training', 'exam'] as $mode) {
            $this->setAdminUser();
            $g = $this->getDataGenerator();
            $course = $g->create_course();
            $types = ['ipv4_network'];
            $pathmodes = ['relative'];
            foreach (\qtype_linuxcmd\exercise::TYPES as $type) {
                foreach (in_array($type, \qtype_linuxcmd\permissions::TYPES, true) ?
                        ['random'] : \qtype_linuxcmd\exercise::MODES as $pathmode) {
                    $types[] = 'linux_' . $type;
                    $pathmodes[] = $pathmode;
                }
            }
            $activity = $g->create_module('practicum', [
                'course' => $course->id, 'mode' => $mode, 'blocktype' => $types,
                'blockcount' => array_fill(0, count($types), 1),
                'blockprefix' => array_fill(0, count($types), [26]), 'blockpathmode' => $pathmodes,
            ]);
            // Exercise every saved mode, including legacy blocks no longer emitted by the form.
            $blocks = [['type' => 'ipv4_network', 'count' => 1, 'prefixes' => [26]]];
            foreach (array_slice($types, 1, null, true) as $i => $type) {
                $blocks[] = ['type' => $type, 'count' => 1, 'pathmode' => $pathmodes[$i]];
            }
            $activity->blocks = json_encode($blocks, JSON_THROW_ON_ERROR);
            $DB->set_field('practicum', 'blocks', $activity->blocks, ['id' => $activity->id]);
            $student = $g->create_user();
            $g->enrol_user($student->id, $course->id, 'student');
            $this->setUser($student);
            $context = \context_module::instance($activity->cmid);
            $attempt = local\attempts::start($activity, $context, $student->id);
            $usage = \question_engine::load_questions_usage_by_activity($attempt->usageid);
            $this->assertCount(count($types), $usage->get_slots());
            $snapshots = [];
            foreach ($usage->get_slots() as $slot) {
                $q = $usage->get_question($slot);
                $snapshots[$slot] = $q->exercise;
                $this->assertStringNotContainsString('[[', $q->task_text());
                $response = $q->get_correct_response();
                if ($mode === 'training') { $response['-submit'] = 1; }
                $usage->process_action($slot, $response);
            }
            \question_engine::save_questions_usage_by_activity($usage);
            $DB->set_field('practicum', 'blocks', json_encode([
                ['type' => 'linux_rmdir', 'count' => 1, 'pathmode' => 'absolute'],
            ]), ['id' => $activity->id]);
            $changed = $DB->get_record('practicum', ['id' => $activity->id]);
            $this->assertEquals($attempt->id, local\attempts::start($changed, $context, $student->id)->id);
            $reloaded = \question_engine::load_questions_usage_by_activity($attempt->usageid);
            foreach ($reloaded->get_slots() as $slot) {
                $this->assertSame($snapshots[$slot], $reloaded->get_question($slot)->exercise);
            }
            $finished = local\attempts::process($attempt->id, null, true);
            $this->assertEquals(100, $finished->grade);
            if ($mode === 'exam') {
                $grade = $DB->get_record_sql('SELECT g.* FROM {grade_grades} g
                    JOIN {grade_items} i ON i.id=g.itemid
                    WHERE i.itemmodule=? AND i.iteminstance=? AND g.userid=?',
                    ['practicum', $activity->id, $student->id]);
                $this->assertEquals(100, $grade->rawgrade);
            }
        }
    }
}
