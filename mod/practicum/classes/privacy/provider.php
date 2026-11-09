<?php
// GPL v3 or later.
namespace mod_practicum\privacy;
defined('MOODLE_INTERNAL') || die();
use core_privacy\local\request\{contextlist, approved_contextlist, userlist, approved_userlist, writer};
use core_privacy\local\metadata\collection;
class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider, \core_privacy\local\request\core_userlist_provider {
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('practicum_attempts', [
            'userid'=>'privacy:metadata', 'usageid'=>'privacy:metadata', 'plan'=>'privacy:metadata',
            'mode'=>'privacy:metadata','timestart'=>'privacy:metadata','timefinish'=>'privacy:metadata',
            'deadline'=>'privacy:metadata','grade'=>'privacy:metadata'], 'privacy:metadata');
        return $collection->add_subsystem_link('core_question', [], 'privacy:metadata');
    }
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list=new contextlist();
        $list->add_from_sql('SELECT c.id FROM {context} c JOIN {course_modules} cm ON cm.id=c.instanceid
            JOIN {modules} m ON m.id=cm.module JOIN {practicum_attempts} a ON a.practicumid=cm.instance
            WHERE c.contextlevel=:level AND m.name=:name AND a.userid=:userid',
            ['level'=>CONTEXT_MODULE,'name'=>'practicum','userid'=>$userid]);
        return $list;
    }
    private static function module(\context $context) {
        return $context instanceof \context_module ? get_coursemodule_from_id('practicum', $context->instanceid) : false;
    }
    public static function get_users_in_context(userlist $list) {
        if ($cm=self::module($list->get_context())) {
            $list->add_from_sql('userid','SELECT userid FROM {practicum_attempts} WHERE practicumid = ?',[$cm->instance]);
        }
    }
    public static function export_user_data(approved_contextlist $list) {
        global $CFG,$DB;
        require_once($CFG->dirroot.'/mod/practicum/lib.php');
        foreach($list->get_contexts() as $context) {
            if (!$cm=self::module($context)) {continue;}
            foreach($DB->get_records('practicum_attempts',['practicumid'=>$cm->instance,'userid'=>$list->get_user()->id]) as $a) {
                $path=[get_string('attempt','practicum'),(string)$a->id];
                writer::with_context($context)->export_data($path,$a);
                \core_question\privacy\provider::export_question_usage($a->userid,$context,$path,$a->usageid,new \question_display_options(),true);
            }
        }
    }
    private static function remove(\context $context, ?array $users = null): void {
        global $CFG,$DB;
        require_once($CFG->dirroot.'/mod/practicum/lib.php');
        if (!$cm=self::module($context)) {return;}
        foreach($DB->get_records('practicum_attempts',['practicumid'=>$cm->instance]) as $a) {
            if ($users!==null && !in_array($a->userid,$users)) {continue;}
            \question_engine::delete_questions_usage_by_activity($a->usageid);
            $DB->delete_records('practicum_attempts',['id'=>$a->id]);
        }
    }
    public static function delete_data_for_all_users_in_context(\context $context) {self::remove($context);}
    public static function delete_data_for_user(approved_contextlist $list) {
        foreach($list->get_contexts() as $context) {self::remove($context,[$list->get_user()->id]);}
    }
    public static function delete_data_for_users(approved_userlist $list) {self::remove($list->get_context(),$list->get_userids());}
}
