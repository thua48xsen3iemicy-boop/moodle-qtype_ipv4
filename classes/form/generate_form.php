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
        $elements = [];
        foreach (\qtype_ipv4\network::PREFIXES as $prefix) {
            $elements[] = $mform->createElement('advcheckbox', 'prefix' . $prefix, '', '/' . $prefix);
            $mform->setDefault('prefix' . $prefix, in_array($prefix, [8, 16, 24]) ? 1 : 0);
        }
        $mform->addGroup($elements, 'allowedprefixes', get_string('prefixes', 'qtype_ipv4'), ' ', false);
        $mform->addElement('select', 'answermode', get_string('answermode', 'qtype_ipv4'), [
            'choice' => get_string('modechoice', 'qtype_ipv4'), 'text' => get_string('modetext', 'qtype_ipv4'),
        ]);
        foreach (\qtype_ipv4\exercise\catalog::groups() as $group => $ids) {
            $header = 'exercisegroup_' . $group;
            $mform->addElement('header', $header, get_string('group_' . $group, 'qtype_ipv4'));
            $mform->setExpanded($header, $group === 'subnet');
            foreach ($ids as $id) {
                $mform->addElement('advcheckbox', 'task_' . $id, get_string('type_' . $id, 'qtype_ipv4'));
                $mform->setDefault('task_' . $id, $id === 'network' ? 1 : 0);
            }
        }
        $this->add_action_buttons(true, get_string('generate', 'qtype_ipv4'));
    }

    /** Read selected task IDs from known fields only. */
    public static function selected_exercises(array $data): array {
        $selected = [];
        foreach (\qtype_ipv4\exercise\catalog::groups() as $ids) {
            foreach ($ids as $id) {
                if (!empty($data['task_' . $id])) {
                    $selected[] = $id;
                }
            }
        }
        return $selected;
    }

    public function validation($data, $files) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $errors = parent::validation($data, $files);
        $exercises = self::selected_exercises($data);
        if (array_intersect($exercises, \qtype_ipv4\exercise::SUBNET_TYPES) &&
                !array_filter(\qtype_ipv4\network::PREFIXES, fn($p) => !empty($data['prefix' . $p]))) {
            $errors['allowedprefixes'] = get_string('chooseprefix', 'qtype_ipv4');
        }
        if ((int)$data['count'] < 1 || (int)$data['count'] > \qtype_ipv4\batch_service::MAX_COUNT) {
            $errors['count'] = get_string('invalidcount', 'qtype_ipv4', \qtype_ipv4\batch_service::MAX_COUNT);
        }
        if (!$exercises || (int)$data['count'] < count($exercises)) {
            $errors['count'] = get_string('chooseexercise', 'qtype_ipv4');
        }
        if (!isset($this->_customdata['quizzes'][$data['cmid']])) {
            $errors['cmid'] = get_string('invalidquiz', 'qtype_ipv4');
        } else {
            $cm = get_coursemodule_from_id('quiz', $data['cmid'], $this->_customdata['courseid'], false, MUST_EXIST);
            if (quiz_has_attempts($cm->instance)) {
                $errors['cmid'] = get_string('batchhasattempts', 'qtype_ipv4');
            }
        }
        return $errors;
    }
}
