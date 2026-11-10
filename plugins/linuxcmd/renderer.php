<?php
// Moodle plugin, GPL v3 or later.
defined('MOODLE_INTERNAL') || die();

class qtype_linuxcmd_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $name = $qa->get_qt_field_name('answer');
        $current = $qa->get_last_qt_var('answer');
        $html = html_writer::div($question->format_questiontext($qa), 'qtext');
        foreach (explode("\n\n", $question->task_text()) as $paragraph) {
            $html .= html_writer::tag('p', nl2br(s($paragraph)));
        }
        $html .= html_writer::tag('details', html_writer::tag('summary', get_string('structure', 'qtype_linuxcmd')) .
            html_writer::tag('pre', s(implode("\n", array_merge(
                array_map(fn($p) => $p . '/', $question->exercise['dirs']), $question->exercise['files'])))));
        $html .= html_writer::tag('label', get_string('enteranswer', 'qtype_linuxcmd'), ['for' => $name]);
        $attrs = ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $current ?? '',
            'class' => 'form-control font-monospace', 'size' => 80, 'maxlength' => 4096,
            'autocomplete' => 'off', 'spellcheck' => 'false'];
        if ($options->readonly) { $attrs['readonly'] = 'readonly'; }
        $html .= html_writer::empty_tag('input', $attrs);
        if ($options->correctness && $question->is_gradable_response(['answer' => $current])) {
            $html .= $this->feedback_image($question->grade_response(['answer' => $current])[0]);
        }
        if ($qa->get_state() === question_state::$invalid) {
            $html .= html_writer::div($question->get_validation_error(['answer' => $current]), 'validationerror');
        }
        return $html;
    }
    public function correct_response(question_attempt $qa) {
        return get_string('correctansweris', 'qtype_linuxcmd', s($qa->get_question()->get_correct_response()['answer']));
    }
}
