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
        $m->addElement('select', 'learningtopic', get_string('learningtopic', 'practicum'), [
            '' => get_string('custompractice', 'practicum'),
            \mod_practicum\local\learning_topic::IPV4_ORDER => get_string('topic_ipv4_order', 'practicum'),
        ]);
        $m->addHelpButton('learningtopic', 'learningtopic', 'practicum');
        $m->addElement('text', 'topiccount', get_string('topiccount', 'practicum'));
        $m->setType('topiccount', PARAM_INT);
        $m->setDefault('topiccount', 4);
        $m->hideIf('topiccount', 'learningtopic', 'eq', '');
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
            'once' => get_string('selectiononce', 'practicum'),
            'advanced' => get_string('selectionadvanced', 'practicum'),
            'each' => get_string('selectioneach', 'practicum'),
            'random' => get_string('selectionrandom', 'practicum'),
        ]);
        $m->setDefault('selectionmode', empty($this->current->blocks) ? 'each' : 'advanced');
        $m->addElement('static', 'selectionhint', '', get_string('selectionhint', 'practicum'));
        $m->hideIf('selectionhint', 'selectionmode', 'eq', 'once');
        $m->addElement('static', 'oncehint', '', get_string('selectiononcehint', 'practicum'));
        $m->hideIf('oncehint', 'selectionmode', 'neq', 'once');
        $m->addElement('static', 'advancedhint', '', get_string('selectionadvancedhint', 'practicum'));
        $m->hideIf('advancedhint', 'selectionmode', 'neq', 'advanced');
        $m->addElement('advcheckbox', 'selectalltypes', get_string('selectalltypes', 'practicum'));
        $categories = [];
        foreach (\mod_practicum\local\registry::group_labels() as $id => $label) {
            $categories[] = $m->createElement('advcheckbox', $id, '', $label);
        }
        $m->addGroup($categories, 'selectedgroups', get_string('selectedgroups', 'practicum'), '<br>');
        $m->addHelpButton('selectedgroups', 'selectedgroups', 'practicum');
        $m->hideIf('selectedgroups', 'selectalltypes', 'checked');
        $m->addElement('autocomplete', 'selectedtypes', get_string('selectedtypes', 'practicum'),
            \mod_practicum\local\registry::labels(), ['multiple' => true]);
        $m->hideIf('selectedtypes', 'selectalltypes', 'checked');
        $m->addElement('text', 'sharedcount', get_string('sharedcount', 'practicum'));
        $m->setType('sharedcount', PARAM_INT);
        $m->setDefault('sharedcount', 6);
        $m->hideIf('sharedcount', 'selectionmode', 'eq', 'once');
        $sharedprefix = $m->addElement('select', 'sharedprefix', get_string('prefixes', 'practicum'), $prefixes);
        $sharedprefix->setMultiple(true);
        $m->setDefault('sharedprefix', [8, 16, 24]);
        $prefixselect = $m->createElement('select', 'blockprefix', get_string('prefixes', 'practicum'), $prefixes);
        $prefixselect->setMultiple(true);
        foreach (['selectionhint', 'selectalltypes', 'selectedgroups', 'selectedtypes', 'sharedcount', 'sharedprefix'] as $field) {
            $m->hideIf($field, 'selectionmode', 'eq', 'advanced');
        }
        $elements = [
            $m->createElement('autocomplete', 'blocktype', get_string('tasktype', 'practicum'),
                \mod_practicum\local\registry::labels(), ['multiple' => true]),
            $m->createElement('text', 'blockcount', get_string('blockcount', 'practicum')),
            $prefixselect,
        ];
        $blocks = empty($this->current->blocks) ? [] : json_decode($this->current->blocks, true);
        $repeats = $this->repeat_elements($elements, max(1, count($blocks)),
            ['blockcount' => ['type' => PARAM_INT, 'default' => 15],
             'blockprefix' => ['default' => [8, 16, 24]]], 'blockrepeats', 'addblock', 1,
            get_string('addblock', 'practicum'), true);
        for ($i = 0; $i < $repeats; $i++) {
            foreach (['blocktype', 'blockcount', 'blockprefix'] as $field) {
                $m->hideIf($field . '[' . $i . ']', 'selectionmode', 'neq', 'advanced');
            }
            $m->addHelpButton('blockprefix[' . $i . ']', 'blockprefix', 'practicum');
        }
        $m->hideIf('addblock', 'selectionmode', 'neq', 'advanced');
        foreach (['selectionmode', 'selectionhint', 'oncehint', 'selectalltypes', 'selectedgroups',
                'selectedtypes', 'sharedcount', 'sharedprefix', 'addblock'] as $field) {
            $m->hideIf($field, 'learningtopic', 'neq', '');
        }
        for ($i = 0; $i < $repeats; $i++) {
            foreach (['blocktype', 'blockcount', 'blockprefix'] as $field) {
                $m->hideIf($field . '[' . $i . ']', 'learningtopic', 'neq', '');
            }
        }
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
    public function data_preprocessing(&$defaults) {
        $defaults['learningtopic'] = '';
        if (!empty($defaults['blocks'])) {
            $blocks = json_decode($defaults['blocks'], true);
            $topic = \mod_practicum\local\learning_topic::from_blocks($blocks);
            if ($topic !== '') {
                $defaults['learningtopic'] = $topic;
                $defaults['topiccount'] = $blocks[0]['count'];
                return;
            }
            foreach (json_decode($defaults['blocks'], true) as $i => $block) {
                if (isset($block['selection']) && ($block['layout'] ?? '') !== 'block') {
                    $defaults['selectionmode'] = $block['selection'];
                    $defaults['selectedtypes'] = $block['individualtypes'] ?? $block['types'];
                    $defaults['selectedgroups'] = array_fill_keys($block['groups'] ?? [], 1);
                    $defaults['sharedcount'] = $block['count'];
                    $defaults['sharedprefix'] = $block['prefixes'];
                    continue;
                }
                $defaults['selectionmode'] = 'advanced';
                $defaults['blocktype'][$i] = $block['types'] ?? [$block['type']];
                $defaults['blockcount'][$i] = $block['count'];
                $defaults['blockprefix'][$i] = $block['prefixes'] ?? [8, 16, 24];
            }
        }
    }
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        try { \mod_practicum\local\blueprint::from_form((object)$data); }
        catch (\Throwable $e) {
            $field = match ($data['selectionmode'] ?? 'advanced') {
                'advanced' => 'blockcount[0]',
                'once' => 'selectionmode',
                default => 'sharedcount',
            };
            if (!empty($data['learningtopic'])) {
                $field = 'topiccount';
            }
            $errors[$field] = get_string('invalidblocks', 'practicum');
        }
        if ($data['maxattempts'] < 0) { $errors['maxattempts'] = get_string('invalidblocks', 'practicum'); }
        return $errors;
    }
}
