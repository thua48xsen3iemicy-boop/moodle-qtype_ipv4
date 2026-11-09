<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');
class mod_practicum_mod_form extends moodleform_mod {
    public function definition() {
        $m = $this->_form;
        $m->addElement('text', 'name', get_string('name'), ['size' => 60]);
        $m->setType('name', PARAM_TEXT);
        $m->addRule('name', null, 'required');
        $this->standard_intro_elements();
        $m->addElement('select', 'mode', get_string('mode', 'practicum'),
            ['training' => get_string('training', 'practicum'), 'exam' => get_string('exam', 'practicum')]);
        $m->addElement('duration', 'timelimit', get_string('timelimit', 'practicum'), ['optional' => true]);
        $m->hideIf('timelimit', 'mode', 'eq', 'training');
        $m->addElement('text', 'maxattempts', get_string('maxattempts', 'practicum'));
        $m->setType('maxattempts', PARAM_INT);
        $m->setDefault('maxattempts', 1);
        $m->hideIf('maxattempts', 'mode', 'eq', 'training');
        $m->addElement('header', 'blocksheading', get_string('blocks', 'practicum'));
        $prefixes = [];
        foreach (\qtype_ipv4\network::PREFIXES as $prefix) {
            $prefixes[$prefix] = '/' . $prefix;
        }
        $prefixselect = $m->createElement('select', 'blockprefix', get_string('prefixes', 'practicum'), $prefixes);
        $prefixselect->setMultiple(true);
        $elements = [
            $m->createElement('select', 'blocktype', get_string('tasktype', 'practicum'), \mod_practicum\local\registry::labels()),
            $m->createElement('text', 'blockcount', get_string('count', 'practicum')),
            $prefixselect,
        ];
        $blocks = empty($this->current->blocks) ? [] : json_decode($this->current->blocks, true);
        $this->repeat_elements($elements, max(1, count($blocks)),
            ['blockcount' => ['type' => PARAM_INT, 'default' => 15],
             'blockprefix' => ['default' => [8, 16, 24]]], 'blockrepeats', 'addblock', 1,
            get_string('addblock', 'practicum'), true);
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
    public function data_preprocessing(&$defaults) {
        if (!empty($defaults['blocks'])) {
            foreach (json_decode($defaults['blocks'], true) as $i => $block) {
                $defaults['blocktype'][$i] = $block['type'];
                $defaults['blockcount'][$i] = $block['count'];
                $defaults['blockprefix'][$i] = $block['prefixes'];
            }
        }
    }
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        try { \mod_practicum\local\blueprint::from_form((object)$data); }
        catch (\Throwable $e) { $errors['blockcount[0]'] = get_string('invalidblocks', 'practicum'); }
        if ($data['maxattempts'] < 0) { $errors['maxattempts'] = get_string('invalidblocks', 'practicum'); }
        return $errors;
    }
}
