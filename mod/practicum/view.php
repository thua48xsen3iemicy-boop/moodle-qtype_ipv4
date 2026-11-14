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
$topic = \mod_practicum\local\learning_topic::from_blocks(json_decode($instance->blocks, true));
echo \mod_practicum\local\learning_topic::render($topic);
if ($topic !== '') {
    echo $OUTPUT->heading(get_string('topicpractice', 'practicum'), 3);
    echo html_writer::tag('p', get_string('topicpracticehint', 'practicum'));
}
echo html_writer::tag('p', get_string($instance->mode, 'practicum'));
if ($instance->timelimit > 0) {
    echo html_writer::tag('p', get_string('attempttimelimit', 'practicum', format_time($instance->timelimit)));
}
$active = $DB->get_record('practicum_attempts',
    ['practicumid' => $instance->id, 'userid' => $USER->id, 'timefinish' => 0]);
if ($active && $active->deadline && time() >= $active->deadline + \mod_practicum\local\attempts::SUBMISSION_GRACE) {
    $active = \mod_practicum\local\attempts::process($active->id);
    if ($active->timefinish) {
        $active = false;
    }
}
$used = $DB->count_records('practicum_attempts',
    ['practicumid' => $instance->id, 'userid' => $USER->id, 'mode' => 'exam']);
if (has_capability('mod/practicum:attempt', $context) &&
        ($active || $instance->mode !== 'exam' || !$instance->maxattempts || $used < $instance->maxattempts)) {
    echo $OUTPUT->single_button(new moodle_url('/mod/practicum/view.php', ['id' => $id, 'start' => 1]),
        get_string($active ? 'continue' : 'start', 'practicum'), 'post');
} else if (has_capability('mod/practicum:attempt', $context)) {
    echo html_writer::tag('p', get_string('nomoreattempts', 'practicum'));
}
$rows = [];
if (has_capability('mod/practicum:viewreports', $context)) {
    $rows = \mod_practicum\local\participant_results::rows($context);
} else {
    foreach ($DB->get_records('practicum_attempts',
            ['practicumid' => $instance->id, 'userid' => $USER->id], 'id DESC') as $attempt) {
        if (!$attempt->timefinish && $attempt->deadline &&
                time() >= $attempt->deadline + \mod_practicum\local\attempts::SUBMISSION_GRACE) {
            $attempt = \mod_practicum\local\attempts::process($attempt->id);
        }
        $rows[] = ['user' => $USER, 'attempt' => $attempt];
    }
}
$table = new html_table();
$table->attributes['class'] = 'generaltable practicum-results';
$table->head = [get_string('user'), get_string('mode', 'practicum'), get_string('status'),
    get_string('grade', 'practicum'), get_string('attempt', 'practicum')];
foreach ($rows as $row) {
    $user = $row['user'];
    $attempt = $row['attempt'];
    if ($attempt === null) {
        $table->data[] = [fullname($user), get_string($instance->mode, 'practicum'),
            get_string('notstarted', 'practicum'), '—', '—'];
        continue;
    }
    $table->data[] = [fullname($user), get_string($attempt->mode, 'practicum'),
        get_string($attempt->timefinish ? 'attemptcompleted' : 'attemptinprogress', 'practicum'),
        $attempt->timefinish ? format_float($attempt->grade, 2) : '—',
        html_writer::link(new moodle_url('/mod/practicum/attempt.php', ['id' => $attempt->id]),
            get_string($attempt->timefinish ? 'review' : 'continue', 'practicum'))];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
