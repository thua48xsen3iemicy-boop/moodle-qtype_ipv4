<?php
// Moodle plugin, GPL v3 or later.
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();

/** Teacher results include enrolled learners who have not created an attempt. */
final class participant_results {
    public static function rows(\context_module $context): array {
        global $DB;
        require_capability('mod/practicum:viewreports', $context);
        $cm = get_coursemodule_from_id('practicum', $context->instanceid, 0, false, MUST_EXIST);
        $fields = 'u.id, ' . implode(', ', array_map(fn($field) => 'u.' . $field,
            \core_user\fields::get_name_fields()));
        // Use capabilities rather than hard-coded role names, including custom learner roles.
        // Include suspended enrolments too: the teacher asked for the whole course roster.
        $users = get_enrolled_users($context, 'mod/practicum:attempt', 0, $fields);
        $reporters = get_enrolled_users($context, 'mod/practicum:viewreports', 0, 'u.id');
        $users = array_diff_key($users, $reporters);
        $byuser = [];
        foreach ($DB->get_records('practicum_attempts', ['practicumid' => $cm->instance], 'id DESC') as $attempt) {
            if (!$attempt->timefinish && $attempt->deadline &&
                    time() >= $attempt->deadline + attempts::SUBMISSION_GRACE) {
                $attempt = attempts::process($attempt->id);
            }
            // Keep historical results even after unenrolment or a change of role.
            if (!isset($users[$attempt->userid])) {
                $users[$attempt->userid] = \core_user::get_user($attempt->userid, '*', MUST_EXIST);
            }
            $byuser[$attempt->userid][] = $attempt;
        }
        $rows = [];
        foreach ($users as $user) {
            foreach ($byuser[$user->id] ?? [null] as $attempt) {
                $rows[] = ['user' => $user, 'attempt' => $attempt];
            }
        }
        return $rows;
    }
}
