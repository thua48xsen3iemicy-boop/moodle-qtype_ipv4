<?php
// Moodle plugin, GPL v3 or later.
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();

/** Return to the checked question using question engine's own control names and ids. */
final class attempt_navigation {
    public static function after_submit(object $attempt, array $post): \moodle_url {
        $url = new \moodle_url('/mod/practicum/attempt.php', ['id' => $attempt->id]);
        // An explicit empty fragment prevents browsers inheriting the previous question anchor.
        $url->set_anchor('');
        if ($attempt->timefinish || $attempt->mode !== 'training') {
            return $url;
        }
        $usage = \question_engine::load_questions_usage_by_activity($attempt->usageid);
        foreach ($usage->get_slots() as $slot) {
            $question = $usage->get_question_attempt($slot);
            if (array_key_exists($question->get_behaviour_field_name('submit'), $post)) {
                $url->set_anchor($question->get_outer_question_div_unique_id());
                break;
            }
        }
        return $url;
    }
}
