<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
$quizzes = \qtype_ipv4\batch_service::available_quizzes($course);
if (!$quizzes) {
    throw new moodle_exception('noeditablequizzes', 'qtype_ipv4');
}
$PAGE->set_url(new moodle_url('/question/type/ipv4/generate.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_show_course_index(false);
$PAGE->set_title(get_string('batchadd', 'qtype_ipv4'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_secondary_active_tab('qtype_ipv4_generate');
$form = new \qtype_ipv4\form\generate_form(null, ['courseid' => $courseid, 'quizzes' => $quizzes]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}
if ($data = $form->get_data()) {
    require_sesskey();
    $prefixes = [];
    foreach (\qtype_ipv4\network::PREFIXES as $prefix) {
        if (!empty($data->{'prefix' . $prefix})) {
            $prefixes[] = $prefix;
        }
    }
    $count = \qtype_ipv4\batch_service::add($courseid, (int)$data->cmid, (int)$data->count, $prefixes, $data->answermode ?? 'text',
            \qtype_ipv4\form\generate_form::selected_exercises((array)$data));
    redirect(new moodle_url('/mod/quiz/edit.php', ['cmid' => $data->cmid]),
            get_string('batchadded', 'qtype_ipv4', $count), null, \core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('batchadd', 'qtype_ipv4'));
echo $OUTPUT->notification(get_string('batchintro', 'qtype_ipv4'), \core\output\notification::NOTIFY_INFO);
$form->display();
echo $OUTPUT->footer();
