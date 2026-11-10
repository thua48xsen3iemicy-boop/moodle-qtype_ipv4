<?php
// Moodle plugin, GPL v3 or later.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/questionlib.php');

/** Immutable templates store settings; question engine steps store generated scenarios. */
class qtype_linuxcmd extends question_type {
    public function extra_question_fields() { return ['qtype_linuxcmd_options', 'tasktype', 'pathmode']; }
    public function save_question_options($question) {
        \qtype_linuxcmd\exercise::validate($question->tasktype, $question->pathmode);
        parent::save_question_options($question);
    }
    public function import_from_xml($data, $question, qformat_xml $format, $extra = null) {
        if (($data['@']['type'] ?? '') !== 'linuxcmd') { return false; }
        $question = $format->import_headers($data);
        $question->qtype = 'linuxcmd';
        $question->tasktype = $format->getpath($data, ['#', 'tasktype', 0, '#'], 'copyfile');
        $question->pathmode = $format->getpath($data, ['#', 'pathmode', 0, '#'], 'relative');
        return $question;
    }
    public function get_random_guess_score($questiondata) { return 0; }
}
