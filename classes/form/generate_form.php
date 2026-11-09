<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_ipv4\form;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/** One form for choosing a quiz and adding N generated exercises. */
class generate_form extends \moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('select', 'cmid', get_string('targetquiz', 'qtype_ipv4'), $this->_customdata['quizzes']);
        $mform->addRule('cmid', null, 'required', null, 'client');
        $mform->addElement('text', 'count', get_string('questioncount', 'qtype_ipv4'), ['size' => 5]);
        $mform->setType('count', PARAM_INT);
        $mform->setDefault('count', 15);
        $mform->addRule('count', null, 'required', null, 'client');
        $mform->addElement('select', 'tasktype', get_string('tasktype', 'qtype_ipv4'),
            \qtype_ipv4\exercise::labels());
        $mform->setDefault('tasktype', 'network');
        $elements = [];
        foreach (\qtype_ipv4\network::PREFIXES as $prefix) {
            $elements[] = $mform->createElement('advcheckbox', 'prefix' . $prefix, '', '/' . $prefix);
            $mform->setDefault('prefix' . $prefix, in_array($prefix, [8, 16, 24]) ? 1 : 0);
        }
        $mform->addGroup($elements, 'allowedprefixes', get_string('prefixes', 'qtype_ipv4'), ' ', false);
        $mform->addElement('select', 'answermode', get_string('answermode', 'qtype_ipv4'), [
            'choice' => get_string('modechoice', 'qtype_ipv4'), 'text' => get_string('modetext', 'qtype_ipv4'),
        ]);
        $this->add_action_buttons(true, get_string('generate', 'qtype_ipv4'));
    }

    public function validation($data, $files) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $errors = parent::validation($data, $files);
        if (!array_filter(\qtype_ipv4\network::PREFIXES, fn($p) => !empty($data['prefix' . $p]))) {
            $errors['allowedprefixes'] = get_string('chooseprefix', 'qtype_ipv4');
        }
        if ((int)$data['count'] < 1 || (int)$data['count'] > \qtype_ipv4\batch_service::MAX_COUNT) {
            $errors['count'] = get_string('invalidcount', 'qtype_ipv4', \qtype_ipv4\batch_service::MAX_COUNT);
        }
        if (!isset($this->_customdata['quizzes'][$data['cmid']])) {
            $errors['cmid'] = get_string('invalidquiz', 'qtype_ipv4');
        } else {
            $cm = get_coursemodule_from_id('quiz', $data['cmid'], $this->_customdata['courseid'], false, MUST_EXIST);
            if (quiz_has_attempts($cm->instance)) {
                $errors['cmid'] = get_string('batchhasattempts', 'qtype_ipv4');
            }
        }
        if (!in_array($data['tasktype'] ?? 'network', \qtype_ipv4\exercise::TYPES, true)) {
            $errors['tasktype'] = get_string('invalidanswer', 'qtype_ipv4');
        }
        return $errors;
    }
}
