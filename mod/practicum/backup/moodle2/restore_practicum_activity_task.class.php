<?php
// GPL v3 or later.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/mod/practicum/backup/moodle2/restore_practicum_stepslib.php');
class restore_practicum_activity_task extends restore_activity_task {
    protected function define_my_settings() {}
    protected function define_my_steps() {$this->add_step(new restore_practicum_activity_structure_step('practicum_structure','practicum.xml'));}
    public static function define_decode_contents() {return [new restore_decode_content('practicum',['intro'],'practicum')];}
    public static function define_decode_rules() {return [];}
}
