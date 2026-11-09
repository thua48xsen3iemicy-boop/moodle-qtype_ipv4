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

/** Course backup preserves question-bank options. */
#[\PHPUnit\Framework\Attributes\CoversClass(\backup_qtype_ipv4_plugin::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_qtype_ipv4_plugin::class)]
final class backup_test extends \advanced_testcase {
    public function test_backup_restore_question_settings(): void {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $bank = $generator->get_plugin_generator('mod_qbank')->create_instance(['course' => $course->id]);
        $questions = $generator->get_plugin_generator('core_question');
        $category = $questions->create_question_category(['contextid' => \context_module::instance($bank->cmid)->id]);
        $original = $questions->create_question('ipv4', 'network',
                ['category' => $category->id, 'prefixes' => '8,26', 'answermode' => 'choice', 'tasktype' => 'nextsubnet']);
        $target = $generator->create_course();
        $backup = new \backup_controller(\backup::TYPE_1COURSE, $course->id, \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO, \backup::MODE_IMPORT, $USER->id);
        $id = $backup->get_backupid();
        $backup->execute_plan();
        $backup->destroy();
        $restore = new \restore_controller($id, $target->id, \backup::INTERACTIVE_NO,
                \backup::MODE_IMPORT, $USER->id, \backup::TARGET_CURRENT_ADDING);
        $this->assertTrue($restore->execute_precheck());
        $restore->execute_plan();
        $restore->destroy();
        $options = $DB->get_records_select('qtype_ipv4_options', 'questionid <> ?', [$original->id]);
        $this->assertCount(1, $options);
        $restored = reset($options);
        $this->assertSame('8,26', $restored->prefixes);
        $this->assertSame('nextsubnet', $restored->tasktype);
        $this->assertSame('choice', $restored->answermode);
    }
}
