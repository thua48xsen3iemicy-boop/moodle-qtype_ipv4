<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();
/** Adapter reuses qtype_ipv4 calculation, rendering and persisted attempt state. */
final class ipv4_network implements task_type {
    public function label(): string { return get_string('ipv4network', 'practicum'); }
    public function validate(array $settings): void {
        if (empty($settings['prefixes']) || array_diff($settings['prefixes'], [8, 16, 24])) {
            throw new \invalid_parameter_exception('Invalid prefixes');
        }
    }
    public function create_question(array $settings, int $contextid): \question_definition {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $this->validate($settings);
        $category = question_get_default_category($contextid, true);
        $key = 'practicum_ipv4_v1_' . sha1(json_encode($settings['prefixes']));
        $lock = \core\lock\lock_config::get_lock_factory('mod_practicum')->get_lock('template:' . $contextid, 10);
        if (!$lock) { throw new \moodle_exception('busy', 'practicum'); }
        try {
            $id = $DB->get_field_sql('SELECT qv.questionid FROM {question_bank_entries} qbe
                JOIN {question_versions} qv ON qv.questionbankentryid=qbe.id
                WHERE qbe.questioncategoryid=? AND qbe.idnumber=? ORDER BY qv.version DESC',
                [$category->id, $key], IGNORE_MULTIPLE);
            if ($id) { return \question_bank::load_question($id); }
        $form = (object)[
            'idnumber' => $key,
            'category' => $category->id . ',' . $contextid, 'name' => $this->label(),
            'questiontext' => ['text' => $this->label(), 'format' => FORMAT_PLAIN],
            'generalfeedback' => ['text' => get_string('ipv4explanation', 'practicum'), 'format' => FORMAT_PLAIN],
            'defaultmark' => 1, 'penalty' => 0, 'status' => 'ready',
            'prefixes' => implode(',', $settings['prefixes']), 'answermode' => 'text',
        ];
        $record = \question_bank::get_qtype('ipv4')->save_question((object)['qtype' => 'ipv4'], $form);
        return \question_bank::load_question($record->id);
        } finally { $lock->release(); }
    }
}
