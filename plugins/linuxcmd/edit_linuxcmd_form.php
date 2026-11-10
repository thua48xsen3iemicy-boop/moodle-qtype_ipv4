<?php
// Moodle plugin, GPL v3 or later.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/edit_question_form.php');

class qtype_linuxcmd_edit_form extends question_edit_form {
    protected function definition_inner($mform) {
        $mform->addElement('select', 'tasktype', get_string('tasktype', 'qtype_linuxcmd'), \qtype_linuxcmd\exercise::labels());
        $modes = [];
        foreach (\qtype_linuxcmd\exercise::MODES as $mode) {
            $modes[$mode] = get_string('mode_' . $mode, 'qtype_linuxcmd');
        }
        $mform->addElement('select', 'pathmode', get_string('pathmode', 'qtype_linuxcmd'), $modes);
        $mform->addHelpButton('pathmode', 'pathmode', 'qtype_linuxcmd');
        $mform->setDefault('tasktype', 'copyfile');
        $mform->setDefault('pathmode', 'relative');
    }
    public function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        if (!empty($question->options)) {
            $question->tasktype = $question->options->tasktype;
            $question->pathmode = $question->options->pathmode;
        }
        return $question;
    }
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        try { \qtype_linuxcmd\exercise::validate($data['tasktype'], $data['pathmode']); }
        catch (\invalid_parameter_exception $e) { $errors['tasktype'] = get_string('invalidsettings', 'qtype_linuxcmd'); }
        return $errors;
    }
    public function qtype() { return 'linuxcmd'; }
}
