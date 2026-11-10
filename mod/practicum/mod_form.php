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
        $m->addElement('select', 'selectionmode', get_string('selectionmode', 'practicum'), [
            'advanced' => get_string('selectionadvanced', 'practicum'),
            'each' => get_string('selectioneach', 'practicum'),
            'random' => get_string('selectionrandom', 'practicum'),
        ]);
        $m->setDefault('selectionmode', empty($this->current->blocks) ? 'each' : 'advanced');
        $m->addElement('static', 'selectionhint', '', get_string('selectionhint', 'practicum'));
        $m->addElement('advcheckbox', 'selectalltypes', get_string('selectalltypes', 'practicum'));
        $m->addElement('autocomplete', 'selectedtypes', get_string('selectedtypes', 'practicum'),
            \mod_practicum\local\registry::labels(), ['multiple' => true]);
        $m->hideIf('selectedtypes', 'selectalltypes', 'checked');
        $m->addElement('text', 'sharedcount', get_string('sharedcount', 'practicum'));
        $m->setType('sharedcount', PARAM_INT);
        $m->setDefault('sharedcount', 6);
        $sharedprefix = $m->addElement('select', 'sharedprefix', get_string('prefixes', 'practicum'), $prefixes);
        $sharedprefix->setMultiple(true);
        $m->setDefault('sharedprefix', [8, 16, 24]);
        $prefixselect = $m->createElement('select', 'blockprefix', get_string('prefixes', 'practicum'), $prefixes);
        $prefixselect->setMultiple(true);
        $pathmodes = [];
        foreach (\qtype_linuxcmd\exercise::MODES as $pathmode) {
            $pathmodes[$pathmode] = get_string('mode_' . $pathmode, 'qtype_linuxcmd');
        }
        $m->addElement('select', 'sharedpathmode', get_string('pathmode', 'qtype_linuxcmd'), $pathmodes);
        $m->setDefault('sharedpathmode', 'relative');
        foreach (['selectionhint', 'selectalltypes', 'selectedtypes', 'sharedcount', 'sharedprefix', 'sharedpathmode'] as $field) {
            $m->hideIf($field, 'selectionmode', 'eq', 'advanced');
        }
        $elements = [
            $m->createElement('autocomplete', 'blocktype', get_string('tasktype', 'practicum'), \mod_practicum\local\registry::labels()),
            $m->createElement('text', 'blockcount', get_string('count', 'practicum')),
            $prefixselect,
            $m->createElement('select', 'blockpathmode', get_string('pathmode', 'qtype_linuxcmd'), $pathmodes),
        ];
        $blocks = empty($this->current->blocks) ? [] : json_decode($this->current->blocks, true);
        $repeats = $this->repeat_elements($elements, max(1, count($blocks)),
            ['blockcount' => ['type' => PARAM_INT, 'default' => 15],
             'blockprefix' => ['default' => [8, 16, 24]],
             'blockpathmode' => ['default' => 'relative']], 'blockrepeats', 'addblock', 1,
            get_string('addblock', 'practicum'), true);
        for ($i = 0; $i < $repeats; $i++) {
            foreach (['blocktype', 'blockcount', 'blockprefix', 'blockpathmode'] as $field) {
                $m->hideIf($field . '[' . $i . ']', 'selectionmode', 'neq', 'advanced');
            }
            $m->addHelpButton('blockpathmode[' . $i . ']', 'pathmode', 'qtype_linuxcmd');
            $m->hideIf('blockprefix[' . $i . ']', 'blocktype[' . $i . ']', 'in',
                array_map(fn($type) => 'linux_' . $type, \qtype_linuxcmd\exercise::TYPES));
            $m->hideIf('blockpathmode[' . $i . ']', 'blocktype[' . $i . ']', 'in',
                array_map(fn($type) => 'ipv4_' . $type, \qtype_ipv4\exercise::ALL_TYPES));
        }
        $m->hideIf('addblock', 'selectionmode', 'neq', 'advanced');
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
    public function data_preprocessing(&$defaults) {
        if (!empty($defaults['blocks'])) {
            foreach (json_decode($defaults['blocks'], true) as $i => $block) {
                if (isset($block['selection'])) {
                    $defaults['selectionmode'] = $block['selection'];
                    $defaults['selectedtypes'] = $block['types'];
                    $defaults['sharedcount'] = $block['count'];
                    $defaults['sharedprefix'] = $block['prefixes'];
                    $defaults['sharedpathmode'] = $block['pathmode'];
                    continue;
                }
                $defaults['selectionmode'] = 'advanced';
                $defaults['blocktype'][$i] = $block['type'];
                $defaults['blockcount'][$i] = $block['count'];
                $defaults['blockprefix'][$i] = $block['prefixes'] ?? [8, 16, 24];
                $defaults['blockpathmode'][$i] = $block['pathmode'] ?? 'relative';
            }
        }
    }
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        try { \mod_practicum\local\blueprint::from_form((object)$data); }
        catch (\Throwable $e) {
            $field = ($data['selectionmode'] ?? 'advanced') === 'advanced' ? 'blockcount[0]' : 'sharedcount';
            $errors[$field] = get_string('invalidblocks', 'practicum');
        }
        if ($data['maxattempts'] < 0) { $errors['maxattempts'] = get_string('invalidblocks', 'practicum'); }
        return $errors;
    }
}
