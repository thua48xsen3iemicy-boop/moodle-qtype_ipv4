<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/engine/lib.php');
function practicum_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO, FEATURE_GRADE_HAS_GRADE, FEATURE_USES_QUESTIONS, FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_ASSESSMENT,
        default => null,
    };
}
function practicum_add_instance($data, $form = null) {
    global $DB;
    $data->blocks = \mod_practicum\local\blueprint::from_form($data);
    $data->timecreated = $data->timemodified = time();
    $data->id = $DB->insert_record('practicum', $data);
    practicum_grade_item_update($data);
    return $data->id;
}
function practicum_update_instance($data, $form = null) {
    global $DB;
    $data->blocks = \mod_practicum\local\blueprint::from_form($data);
    $data->id = $data->instance;
    $data->timemodified = time();
    $DB->update_record('practicum', $data);
    practicum_grade_item_update($data);
    return true;
}
function practicum_delete_instance($id) {
    global $DB;
    if (!$instance = $DB->get_record('practicum', ['id' => $id])) { return false; }
    foreach ($DB->get_records('practicum_attempts', ['practicumid' => $id]) as $attempt) {
        \question_engine::delete_questions_usage_by_activity($attempt->usageid);
    }
    $DB->delete_records('practicum_attempts', ['practicumid' => $id]);
    practicum_grade_item_update($instance, null, true);
    return $DB->delete_records('practicum', ['id' => $id]);
}
function practicum_grade_item_update($instance, $grades = null, $deleted = false) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/practicum', $instance->course, 'mod', 'practicum', $instance->id, 0, $grades,
        ['itemname' => $instance->name, 'gradetype' => GRADE_TYPE_VALUE, 'grademax' => 100,
         'grademin' => 0] + ($deleted ? ['deleted' => 1] : []));
}
function practicum_update_grades($instance, $userid = 0, $nullifnone = true) {
    global $DB;
    $params = ['pid' => $instance->id];
    $filter = '';
    if ($userid) { $filter = ' AND userid = :uid'; $params['uid'] = $userid; }
    $grades = $DB->get_records_sql("SELECT userid, MAX(grade) AS rawgrade FROM {practicum_attempts}
        WHERE practicumid = :pid AND mode = 'exam' AND timefinish > 0 $filter GROUP BY userid", $params);
    practicum_grade_item_update($instance, $grades ?: null);
}
