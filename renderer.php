<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/** Accessible native text and radio controls. */
class qtype_ipv4_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $name = $qa->get_qt_field_name('answer');
        $current = $qa->get_last_qt_var('answer');
        $task = $question->task_text();
        $html = html_writer::div($question->format_questiontext($qa), 'qtext');
        $html .= html_writer::tag('p', nl2br(s($task)));
        if ($question->answermode === 'choice') {
            $html .= html_writer::start_tag('fieldset', ['class' => 'ablock']);
            $html .= html_writer::tag('legend', get_string('selectanswer', 'qtype_ipv4'), ['class' => 'h6']);
            foreach ($question->choices as $index => $choice) {
                $id = $name . '_' . $index;
                $attrs = ['type' => 'radio', 'name' => $name, 'id' => $id, 'value' => $choice];
                if ($current === $choice) {
                    $attrs['checked'] = 'checked';
                }
                if ($options->readonly) {
                    $attrs['disabled'] = 'disabled';
                }
                $html .= html_writer::div(html_writer::empty_tag('input', $attrs) . ' ' .
                        html_writer::tag('label', s(\qtype_ipv4\exercise::display($choice)), ['for' => $id]));
            }
            $html .= html_writer::end_tag('fieldset');
        } else {
            $iscommand = in_array($question->exerciseparams['kind'] ?? '', ['command', 'commands'], true);
            if ($iscommand) {
                $hint = ($question->exerciseparams['kind'] ?? '') === 'commands' ? 'commandshelp' : 'commandhelp';
                $html .= html_writer::tag('p', s(get_string($hint, 'qtype_ipv4')), ['class' => 'small']);
            }
            $label = $question->exerciseparams === null ? 'enteranswer' : ($iscommand ? 'entercommand' : 'enterresponse');
            $html .= html_writer::tag('label', get_string($label, 'qtype_ipv4'), ['for' => $name]);
            $attrs = ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $current ?? '',
                'class' => 'form-control', 'size' => 18, 'autocomplete' => 'off', 'spellcheck' => 'false'];
            if ($iscommand) {
                // Boost limits inputs with a size attribute to their character width.
                unset($attrs['size']);
                $attrs['class'] .= ' w-100';
            }
            if ($options->readonly) {
                $attrs['readonly'] = 'readonly';
            }
            if (($question->exerciseparams['kind'] ?? '') === 'commands') {
                unset($attrs['type'], $attrs['value'], $attrs['size']);
                $attrs['rows'] = 4;
                $html .= html_writer::tag('textarea', s($current ?? ''), $attrs);
            } else {
                $html .= html_writer::empty_tag('input', $attrs);
            }
        }
        if ($options->correctness && $question->is_gradable_response(['answer' => $current])) {
            [$fraction] = $question->grade_response(['answer' => $current]);
            $html .= $this->feedback_image($fraction);
        }
        if ($qa->get_state() === question_state::$invalid) {
            $html .= html_writer::div($question->get_validation_error(['answer' => $current]), 'validationerror');
        }
        return $html;
    }

    public function correct_response(question_attempt $qa) {
        return nl2br(get_string('correctansweris', 'qtype_ipv4',
                s(\qtype_ipv4\exercise::display($qa->get_question()->get_correct_response()['answer']))));
    }
}
