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
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->dirroot . '/question/type/ipv4/edit_ipv4_form.php');

/** Question-bank and editing integration. */
#[\PHPUnit\Framework\Attributes\CoversClass(\qtype_ipv4::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\qtype_ipv4_edit_form::class)]
final class question_type_test extends \advanced_testcase {
    public function test_form_selection_is_saved_and_empty_selection_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        $record = $generator->create_question('ipv4', 'network', ['category' => $category->id]);
        $data = \question_bank::load_question_data($record->id);
        $submission = (array)\test_question_maker::get_question_form_data('ipv4', 'network');
        $submission['category'] = "$category->id,$category->contextid";
        $submission['prefix8'] = 0;
        $submission['prefix16'] = 1;
        $submission['prefix24'] = 0;
        $submission['answermode'] = 'text';
        \qtype_ipv4_edit_form::mock_submit($submission);
        $form = \qtype_ipv4_test_helper::get_question_editing_form($category, $data);
        $this->assertTrue($form->is_validated());
        $fromform = $form->get_data();
        $this->assertSame('16', $fromform->prefixes);
        $this->assertSame('text', $fromform->answermode);
        $saved = \question_bank::get_qtype('ipv4')->save_question($data, $fromform);
        $loaded = \question_bank::load_question_data($saved->id);
        $this->assertSame('16', $loaded->options->prefixes);
        $this->assertSame('text', $loaded->options->answermode);
        $submission['prefix16'] = 0;
        \qtype_ipv4_edit_form::mock_submit($submission);
        $form = \qtype_ipv4_test_helper::get_question_editing_form($category, $loaded);
        $this->assertFalse($form->is_validated());
    }

    public function test_moodle_xml_roundtrip(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        $record = $generator->create_question('ipv4', 'network',
                ['category' => $category->id, 'prefixes' => '8,24', 'answermode' => 'choice']);
        $data = \question_bank::load_question_data($record->id);
        $format = new \qformat_xml();
        $xml = $format->writequestion($data);
        $parsed = (new \core\xml_parser())->parse($xml);
        $imported = \question_bank::get_qtype('ipv4')->import_from_xml($parsed['question'], null, $format);
        $this->assertSame('ipv4', $imported->qtype);
        $this->assertSame('8,24', $imported->prefixes);
        $this->assertSame('choice', $imported->answermode);
        $this->assertSame($data->name, $imported->name);
    }
}
