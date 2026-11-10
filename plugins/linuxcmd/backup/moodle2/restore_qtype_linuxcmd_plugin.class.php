<?php
// Moodle plugin, GPL v3 or later.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/backup/moodle2/restore_qtype_extrafields_plugin.class.php');
class restore_qtype_linuxcmd_plugin extends restore_qtype_extrafields_plugin {
    public function process_linuxcmd($data) {
        $this->really_process_extra_question_fields($data);
    }
}
