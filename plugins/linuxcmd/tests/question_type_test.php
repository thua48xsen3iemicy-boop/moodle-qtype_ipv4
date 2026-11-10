<?php
// Moodle plugin, GPL v3 or later.
namespace qtype_linuxcmd;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/format/xml/format.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\qtype_linuxcmd::class)]
final class question_type_test extends \advanced_testcase {
    public function test_settings_and_moodle_xml_roundtrip(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        $form = (object)[
            'category' => $category->id . ',' . $category->contextid, 'name' => 'Linux XML',
            'questiontext' => ['text' => 'Linux', 'format' => FORMAT_PLAIN],
            'generalfeedback' => ['text' => '', 'format' => FORMAT_PLAIN],
            'defaultmark' => 1, 'penalty' => 0, 'status' => 'ready',
            'tasktype' => 'copytree', 'pathmode' => 'mixed',
        ];
        $type = \question_bank::get_qtype('linuxcmd');
        $saved = $type->save_question((object)['qtype' => 'linuxcmd'], $form);
        $loaded = \question_bank::load_question($saved->id);
        $this->assertSame('copytree', $loaded->tasktype);
        $this->assertSame('mixed', $loaded->pathmode);
        $data = \question_bank::load_question_data($saved->id);
        $format = new \qformat_xml();
        $parsed = (new \core\xml_parser())->parse($format->writequestion($data));
        $imported = $type->import_from_xml($parsed['question'], null, $format);
        $this->assertSame('linuxcmd', $imported->qtype);
        $this->assertSame('copytree', $imported->tasktype);
        $this->assertSame('mixed', $imported->pathmode);
    }
}
