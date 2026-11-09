<?php
// GPL v3 or later.
require_once(__DIR__.'/../../config.php');
$id=required_param('id',PARAM_INT);$course=get_course($id);require_login($course);
$PAGE->set_url('/mod/practicum/index.php',['id'=>$id]);$PAGE->set_context(context_course::instance($id));
$PAGE->set_title(get_string('modulenameplural','practicum'));$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();echo $OUTPUT->heading(get_string('modulenameplural','practicum'));
foreach(get_fast_modinfo($course)->get_instances_of('practicum') as $cm) {
    if ($cm->uservisible) {echo html_writer::div(html_writer::link($cm->url,format_string($cm->name)));}
}
echo $OUTPUT->footer();
