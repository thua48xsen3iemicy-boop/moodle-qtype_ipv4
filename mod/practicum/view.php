<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('practicum', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/practicum:view', $context);
$instance = $DB->get_record('practicum', ['id' => $cm->instance], '*', MUST_EXIST);
$PAGE->set_url('/mod/practicum/view.php', ['id' => $id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
if (optional_param('start', 0, PARAM_BOOL) && data_submitted()) {
    require_sesskey();
    $attempt = \mod_practicum\local\attempts::start($instance, $context, $USER->id);
    redirect(new moodle_url('/mod/practicum/attempt.php', ['id' => $attempt->id]));
}
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name));
echo format_module_intro('practicum', $instance, $id);
echo html_writer::tag('p', get_string($instance->mode, 'practicum'));
if (has_capability('mod/practicum:attempt', $context)) {
    echo $OUTPUT->single_button(new moodle_url('/mod/practicum/view.php', ['id' => $id, 'start' => 1]),
        get_string('start', 'practicum'), 'post');
}
$params = ['practicumid' => $instance->id];
if (!has_capability('mod/practicum:viewreports', $context)) { $params['userid'] = $USER->id; }
$table = new html_table();
$table->head = [get_string('user'), get_string('mode', 'practicum'), get_string('grade', 'practicum'), get_string('attempt', 'practicum')];
foreach ($DB->get_records('practicum_attempts', $params, 'id DESC') as $attempt) {
    $user = core_user::get_user($attempt->userid);
    $table->data[] = [fullname($user), get_string($attempt->mode, 'practicum'),
        $attempt->timefinish ? format_float($attempt->grade, 2) : '—',
        html_writer::link(new moodle_url('/mod/practicum/attempt.php', ['id' => $attempt->id]),
            get_string($attempt->timefinish ? 'review' : 'continue', 'practicum'))];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
