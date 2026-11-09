<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/edit_question_form.php');

/** Editing controls for allowed prefixes and answer mode. */
class qtype_ipv4_edit_form extends question_edit_form {
    protected function definition_inner($mform) {
        $elements = [];
        foreach ([8, 16, 24] as $prefix) {
            $elements[] = $mform->createElement('advcheckbox', 'prefix' . $prefix, '', '/' . $prefix);
            $mform->setDefault('prefix' . $prefix, 1);
        }
        $mform->addGroup($elements, 'allowedprefixes', get_string('prefixes', 'qtype_ipv4'), ' ', false);
        $mform->addHelpButton('allowedprefixes', 'prefixes', 'qtype_ipv4');
        $mform->addElement('select', 'answermode', get_string('answermode', 'qtype_ipv4'), [
            'text' => get_string('modetext', 'qtype_ipv4'),
            'choice' => get_string('modechoice', 'qtype_ipv4'),
        ]);
        $mform->setDefault('answermode', 'text');
        $mform->addElement('hidden', 'prefixes', '8,16,24');
        $mform->setType('prefixes', PARAM_TEXT);
    }

    public function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        if (!empty($question->options)) {
            $question->answermode = $question->options->answermode;
            foreach ([8, 16, 24] as $prefix) {
                $question->{'prefix' . $prefix} = (int)in_array((string)$prefix,
                        explode(',', $question->options->prefixes), true);
            }
        }
        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['prefix8']) && empty($data['prefix16']) && empty($data['prefix24'])) {
            $errors['allowedprefixes'] = get_string('chooseprefix', 'qtype_ipv4');
        }
        return $errors;
    }

    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $prefixes = [];
            foreach ([8, 16, 24] as $prefix) {
                if (!empty($data->{'prefix' . $prefix})) {
                    $prefixes[] = $prefix;
                }
            }
            $data->prefixes = implode(',', $prefixes);
        }
        return $data;
    }

    public function qtype() {
        return 'ipv4';
    }
}
