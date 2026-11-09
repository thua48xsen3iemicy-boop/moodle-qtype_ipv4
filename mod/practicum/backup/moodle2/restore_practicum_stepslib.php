<?php
// GPL v3 or later.
defined('MOODLE_INTERNAL') || die();
class restore_practicum_activity_structure_step extends restore_questions_activity_structure_step {
    protected $currentattempt;
    protected function define_structure() {
        $paths=[new restore_path_element('practicum','/activity/practicum')];
        if ($this->get_setting_value('userinfo')) {
            $a=new restore_path_element('practicum_attempt','/activity/practicum/attempts/attempt');
            $paths[]=$a;$this->add_question_usages($a,$paths);
        }
        return $this->prepare_activity_structure($paths);
    }
    protected function process_practicum($data) {
        global $DB;$data=(object)$data;$data->course=$this->get_courseid();
        $id=$DB->insert_record('practicum',$data);$this->apply_activity_instance($id);
    }
    protected function process_practicum_attempt($data) {
        $data=(object)$data;$data->practicumid=$this->get_new_parentid('practicum');
        $data->userid=$this->get_mappingid('user',$data->userid);
        foreach(['timestart','timefinish','deadline'] as $field) {
            if ($data->$field) {$data->$field=$this->apply_date_offset($data->$field);}
        }
        $this->currentattempt=$data;
    }
    protected function inform_new_usage_id($newusageid) {
        global $DB;$data=$this->currentattempt;$oldid=$data->id;$data->usageid=$newusageid;
        $id=$DB->insert_record('practicum_attempts',$data);$this->set_mapping('practicum_attempt',$oldid,$id);
    }
    protected function after_execute() {
        parent::after_execute();$this->add_related_files('mod_practicum','intro',null);
    }
}
