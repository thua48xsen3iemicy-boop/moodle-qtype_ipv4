<?php
// GPL v3 or later.
defined('MOODLE_INTERNAL') || die();
class backup_practicum_activity_structure_step extends backup_questions_activity_structure_step {
    protected function define_structure() {
        $activity = new backup_nested_element('practicum', ['id'],
            ['name','intro','introformat','mode','blocks','timelimit','maxattempts','timecreated','timemodified']);
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt',['id'],
            ['userid','usageid','mode','plan','timestart','timefinish','deadline','grade']);
        $activity->add_child($attempts);$attempts->add_child($attempt);
        $this->add_question_usages($attempt,'usageid');
        $activity->set_source_table('practicum',['id'=>backup::VAR_ACTIVITYID]);
        if ($this->get_setting_value('userinfo')) {
            $attempt->set_source_table('practicum_attempts',['practicumid'=>backup::VAR_PARENTID]);
        }
        $attempt->annotate_ids('user','userid');
        $activity->annotate_files('mod_practicum','intro',null);
        return $this->prepare_activity_structure($activity);
    }
}
