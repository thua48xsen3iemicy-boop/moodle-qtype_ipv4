<?php
// GPL v3 or later.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/mod/practicum/backup/moodle2/backup_practicum_stepslib.php');
class backup_practicum_activity_task extends backup_activity_task {
    protected function define_my_settings() {}
    protected function define_my_steps() {$this->add_step(new backup_practicum_activity_structure_step('practicum_structure','practicum.xml'));}
    public static function encode_content_links($content) {return $content;}
}
