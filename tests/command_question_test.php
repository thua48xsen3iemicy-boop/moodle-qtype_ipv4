<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * New exercises through the real Moodle question engine and XML.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_ipv4;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');

/** Persistence, rendering, grading and export of the exercise catalogue. */
#[\PHPUnit\Framework\Attributes\CoversClass(\qtype_ipv4_question::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\qtype_ipv4::class)]
final class command_question_test extends \advanced_testcase {
    public function test_every_exercise_survives_database_reload_and_regrade(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        $usage = \question_engine::make_questions_usage_by_activity('core_question', \context_system::instance());
        $usage->set_preferred_behaviour('deferredfeedback');
        foreach (exercise\catalog::groups() as $ids) {
            foreach ($ids as $id) {
                $record = $generator->create_question('ipv4', 'network',
                        ['category' => $category->id, 'tasktype' => $id]);
                $usage->add_question(\question_bank::load_question($record->id));
                $data = \question_bank::load_question_data($record->id);
                $format = new \qformat_xml();
                $xml = $format->writequestion($data);
                $parsed = (new \core\xml_parser())->parse($xml);
                $imported = \question_bank::get_qtype('ipv4')->import_from_xml($parsed['question'], null, $format);
                $this->assertSame($id, $imported->tasktype);
                $this->assertSame(in_array($id, exercise::SUBNET_TYPES, true) ? 'choice' : 'text', $imported->answermode);
            }
        }
        $usage->start_all_questions();
        $snapshots = [];
        foreach ($usage->get_slots() as $slot) {
            $question = $usage->get_question($slot);
            $snapshots[$slot] = [$question->tasktype, $question->exerciseparams, $question->task_text()];
            $usage->process_action($slot, $question->get_correct_response());
        }
        \question_engine::save_questions_usage_by_activity($usage);
        $usageid = $usage->get_id();
        $usage = \question_engine::load_questions_usage_by_activity($usageid);
        foreach ($usage->get_slots() as $slot) {
            $question = $usage->get_question($slot);
            $this->assertSame($snapshots[$slot], [$question->tasktype, $question->exerciseparams, $question->task_text()]);
            $this->assertSame($question->get_correct_response()['answer'],
                    $usage->get_question_attempt($slot)->get_last_qt_var('answer'));
        }
        $usage->finish_all_questions();
        \question_engine::save_questions_usage_by_activity($usage);
        $usage = \question_engine::load_questions_usage_by_activity($usageid);
        $usage->regrade_all_questions();
        foreach ($usage->get_slots() as $slot) {
            $question = $usage->get_question($slot);
            $this->assertSame(1.0, $usage->get_question_fraction($slot), $question->tasktype);
            $this->assertSame($snapshots[$slot], [$question->tasktype, $question->exerciseparams, $question->task_text()]);
            $options = new \question_display_options();
            $options->readonly = true;
            $html = $usage->render_question($slot, $options);
            $this->assertStringContainsString('name="' . $usage->get_question_attempt($slot)->get_qt_field_name('answer'), $html);
            if (($question->exerciseparams['kind'] ?? '') === 'commands') {
                $this->assertStringContainsString('<textarea', $html);
            }
        }
    }

    public function test_old_xml_without_exercise_defaults_to_subnet(): void {
        $format = new \qformat_xml();
        $xml = '<question type="ipv4"><name><text>Legacy</text></name>' .
                '<questiontext format="html"><text>Network</text></questiontext>' .
                '<prefixes>24</prefixes><answermode>choice</answermode></question>';
        $parsed = (new \core\xml_parser())->parse($xml);
        $imported = \question_bank::get_qtype('ipv4')->import_from_xml($parsed['question'], null, $format);
        $this->assertSame('network', $imported->tasktype);
    }
}
