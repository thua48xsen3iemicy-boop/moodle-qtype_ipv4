<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();
/** Adapter reuses qtype_ipv4 calculation, rendering and persisted attempt state. */
class ipv4_task implements task_type {
    private string $type;

    public function __construct(string $type) {
        if (!in_array($type, \qtype_ipv4\exercise::ALL_TYPES, true)) {
            throw new \invalid_parameter_exception('Unknown IPv4 exercise');
        }
        $this->type = $type;
    }
    public function label(): string {
        return \qtype_ipv4\exercise\catalog::options()[$this->type];
    }

    /** Boolean tasks use native radio buttons; other exercises use text entry. */
    public function answer_mode(): string {
        return in_array($this->type, ['samesubnet', 'assignable'], true) ? 'choice' : 'text';
    }

    public function template_key(array $settings): string {
        $this->validate($settings);
        $identity = ['type' => $this->type, 'prefixes' => $settings['prefixes']];
        $version = 'v2';
        if ($this->answer_mode() === 'choice') {
            // New templates only: do not edit old text questions used by existing attempts.
            $version = 'v3';
            $identity['answermode'] = 'choice';
        }
        return 'practicum_ipv4_' . $version . '_' . sha1(json_encode($identity, JSON_THROW_ON_ERROR));
    }
    public function validate(array $settings): void {
        if (empty($settings['prefixes']) || !is_array($settings['prefixes']) ||
                array_filter($settings['prefixes'], fn($p) => !is_int($p) ||
                    !in_array($p, \qtype_ipv4\network::PREFIXES, true))) {
            throw new \invalid_parameter_exception('Invalid prefixes');
        }
    }
    public function create_question(array $settings, int $contextid): \question_definition {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        $this->validate($settings);
        $category = question_get_default_category($contextid, true);
        $key = $this->template_key($settings);
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
                'generalfeedback' => ['text' => in_array($this->type, \qtype_ipv4\exercise::COMMAND_TYPES, true) ?
                    get_string('commandexplanation', 'practicum') : get_string('explanation_' . $this->type, 'practicum'), 'format' => FORMAT_PLAIN],
                'defaultmark' => 1, 'penalty' => 0, 'status' => 'ready',
                'prefixes' => implode(',', $settings['prefixes']), 'answermode' => $this->answer_mode(), 'tasktype' => $this->type,
            ];
            $record = \question_bank::get_qtype('ipv4')->save_question((object)['qtype' => 'ipv4'], $form);
            return \question_bank::load_question($record->id);
        } finally { $lock->release(); }
    }
}
