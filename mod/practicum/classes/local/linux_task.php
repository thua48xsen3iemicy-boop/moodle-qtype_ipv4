<?php
// Moodle plugin, GPL v3 or later.
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();

/** Bridge to persisted Linux questions; no learner command is executed. */
final class linux_task implements task_type {
    private string $type;
    public function __construct(string $type) {
        \qtype_linuxcmd\exercise::validate($type, 'relative');
        $this->type = $type;
    }
    public function label(): string {
        return get_string(in_array($this->type, \qtype_linuxcmd\permissions::TYPES, true) ?
            'permissiontask' : 'linuxtask', 'practicum', get_string('type_' . $this->type, 'qtype_linuxcmd'));
    }
    public function validate(array $settings): void {
        \qtype_linuxcmd\exercise::validate($this->type, $settings['pathmode'] ?? 'relative');
    }
    public function template_key(array $settings): string {
        $this->validate($settings);
        return (in_array($this->type, \qtype_linuxcmd\permissions::TYPES, true) ?
            'practicum_linuxcmd_v2_' : 'practicum_linuxcmd_v1_') . sha1(json_encode([
            'type' => $this->type, 'pathmode' => $settings['pathmode'] ?? 'relative',
        ], JSON_THROW_ON_ERROR));
    }
    public function create_question(array $settings, int $contextid): \question_definition {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $key = $this->template_key($settings);
        $category = question_get_default_category($contextid, true);
        $lock = \core\lock\lock_config::get_lock_factory('mod_practicum')->get_lock('template:' . $contextid, 10);
        if (!$lock) { throw new \moodle_exception('busy', 'practicum'); }
        try {
            $id = $DB->get_field_sql('SELECT qv.questionid FROM {question_bank_entries} qbe
                JOIN {question_versions} qv ON qv.questionbankentryid=qbe.id
                WHERE qbe.questioncategoryid=? AND qbe.idnumber=? ORDER BY qv.version DESC',
                [$category->id, $key], IGNORE_MULTIPLE);
            if ($id) { return \question_bank::load_question($id); }
            $form = (object)[
                'idnumber' => $key, 'category' => $category->id . ',' . $contextid, 'name' => $this->label(),
                'questiontext' => ['text' => $this->label(), 'format' => FORMAT_PLAIN],
                'generalfeedback' => ['text' => (in_array($this->type, \qtype_linuxcmd\permissions::TYPES, true) ?
                    get_string('permissionfeedback', 'qtype_linuxcmd') : get_string('linuxfeedback', 'practicum')), 'format' => FORMAT_PLAIN],
                'defaultmark' => 1, 'penalty' => 0, 'status' => 'ready', 'tasktype' => $this->type,
                'pathmode' => $settings['pathmode'] ?? 'relative',
            ];
            $record = \question_bank::get_qtype('linuxcmd')->save_question((object)['qtype' => 'linuxcmd'], $form);
            return \question_bank::load_question($record->id);
        } finally { $lock->release(); }
    }
}
