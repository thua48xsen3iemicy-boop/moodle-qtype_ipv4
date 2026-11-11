<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
$id = required_param('id', PARAM_INT);
$attempt = $DB->get_record('practicum_attempts', ['id' => $id], '*', MUST_EXIST);
$instance = $DB->get_record('practicum', ['id' => $attempt->practicumid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('practicum', $instance->id, $instance->course, false, MUST_EXIST);
require_login(get_course($cm->course), false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/practicum:view', $context);
$owner = $attempt->userid == $USER->id;
if (!$owner) { require_capability('mod/practicum:viewreports', $context); }
else { require_capability('mod/practicum:attempt', $context); }
if (data_submitted()) {
    require_sesskey();
    if (!$owner) { throw new required_capability_exception($context, 'mod/practicum:attempt', 'nopermissions', ''); }
    $post = (array)data_submitted();
    $attempt = \mod_practicum\local\attempts::process($id, $post, optional_param('finish', false, PARAM_BOOL));
    redirect(\mod_practicum\local\attempt_navigation::after_submit($attempt, $post));
}
$attempt = \mod_practicum\local\attempts::process($id);
$usage = question_engine::load_questions_usage_by_activity($attempt->usageid);
$PAGE->set_url('/mod/practicum/attempt.php', ['id' => $id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($instance->name));
foreach ($usage->get_slots() as $slot) { $usage->render_question_head_html($slot); }
question_engine::initialise_js();
$options = new question_display_options();
$options->readonly = (bool)$attempt->timefinish || !$owner;
$feedback = $attempt->mode === 'training' || (bool)$attempt->timefinish;
$options->correctness = $options->feedback = $options->generalfeedback = $options->rightanswer =
    $feedback ? question_display_options::VISIBLE : question_display_options::HIDDEN;
if ($attempt->deadline && !$attempt->timefinish) {
    $remaining = max(0, $attempt->deadline - time());
    $PAGE->requires->js_init_code("setTimeout(function() { window.location.reload(); }, " . ($remaining * 1000 + 100) . ");");
}
echo $OUTPUT->header();
if ($attempt->mode === 'training' || $attempt->timefinish) {
    $plan = json_decode($attempt->plan, true);
    $settings = array_column($plan, 'settings');
    // Plans are shuffled, so the topic marker can occur in any slot.
    foreach ($settings as $setting) {
        $topic = \mod_practicum\local\learning_topic::from_blocks([$setting]);
        if ($topic !== '') {
            echo \mod_practicum\local\learning_topic::render($topic, true);
            break;
        }
    }
}
if ($attempt->timefinish) { echo $OUTPUT->heading(get_string('result', 'practicum', format_float($attempt->grade, 2))); }
if ($attempt->deadline && !$attempt->timefinish) {
    echo html_writer::tag('p', get_string('deadline', 'practicum', userdate($attempt->deadline)));
}
echo html_writer::start_tag('form', ['method' => 'post', 'id' => 'practicum-attempt']);
echo html_writer::input_hidden_params(new moodle_url('/mod/practicum/attempt.php', ['id' => $id, 'sesskey' => sesskey()]));
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slots', 'value' => implode(',', $usage->get_slots())]);
foreach ($usage->get_slots() as $slot) { echo $usage->render_question($slot, $options, $slot); }
if (!$options->readonly) {
    echo html_writer::tag('button', get_string('save', 'practicum'), ['type' => 'submit', 'class' => 'btn btn-secondary']);
    echo ' ' . html_writer::tag('button', get_string('finish', 'practicum'),
        ['type' => 'submit', 'name' => 'finish', 'value' => 1, 'class' => 'btn btn-primary']);
}
echo html_writer::end_tag('form');
echo html_writer::link(new moodle_url('/mod/practicum/view.php', ['id' => $cm->id]), get_string('back', 'practicum'));
echo $OUTPUT->footer();
