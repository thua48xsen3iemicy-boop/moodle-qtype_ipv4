<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();
/** Lifecycle owner: question engine persists generated state, module persists policy snapshot. */
final class attempts {
    /** Transport allowance for the final form, not extra working time in the UI. */
    public const SUBMISSION_GRACE = 30;

    public static function start(object $instance, \context_module $context, int $userid): object {
        global $DB;
        require_capability('mod/practicum:attempt', $context, $userid);
        $factory = \core\lock\lock_config::get_lock_factory('mod_practicum');
        $lock = $factory->get_lock($instance->id . ':' . $userid, 10);
        if (!$lock) { throw new \moodle_exception('busy', 'practicum'); }
        try {
            if ($active = $DB->get_record('practicum_attempts',
                    ['practicumid' => $instance->id, 'userid' => $userid, 'timefinish' => 0])) {
                return $active;
            }
            $used = $DB->count_records('practicum_attempts', ['practicumid' => $instance->id, 'userid' => $userid, 'mode' => 'exam']);
            if ($instance->mode === 'exam' && $instance->maxattempts && $used >= $instance->maxattempts) {
                throw new \moodle_exception('nomoreattempts', 'practicum');
            }
            $tx = $DB->start_delegated_transaction();
            try {
                $plan = blueprint::plan(json_decode($instance->blocks, true, 512, JSON_THROW_ON_ERROR));
                $usage = \question_engine::make_questions_usage_by_activity('mod_practicum', $context);
                $usage->set_preferred_behaviour($instance->mode === 'training' ? 'immediatefeedback' : 'deferredfeedback');
                foreach ($plan as $item) {
                    $usage->add_question(registry::get($item['type'])->create_question($item['settings'], $context->id));
                }
                $usage->start_all_questions();
                \question_engine::save_questions_usage_by_activity($usage);
                $attempt = (object)['practicumid' => $instance->id, 'userid' => $userid, 'usageid' => $usage->get_id(),
                    'mode' => $instance->mode, 'plan' => json_encode($plan, JSON_THROW_ON_ERROR),
                    'timestart' => time(), 'timefinish' => 0, 'deadline' => 0, 'grade' => null];
                if ($instance->timelimit > 0) {
                    $attempt->deadline = $attempt->timestart + $instance->timelimit;
                }
                $attempt->id = $DB->insert_record('practicum_attempts', $attempt);
                $tx->allow_commit();
                return $attempt;
            } catch (\Throwable $error) { $tx->rollback($error); }
        } finally { $lock->release(); }
    }
    public static function process(int $id, ?array $post = null, bool $finish = false, bool $autosave = false): object {
        global $DB;
        // Judge arrival before waiting for a concurrent autosave's lock.
        $received = time();
        $lock = \core\lock\lock_config::get_lock_factory('mod_practicum')->get_lock('attempt:' . $id, 10);
        if (!$lock) { throw new \moodle_exception('busy', 'practicum'); }
        try {
            $attempt = $DB->get_record('practicum_attempts', ['id' => $id], '*', MUST_EXIST);
            if ($attempt->timefinish) { return $attempt; }
            $expired = $attempt->deadline && $received >= $attempt->deadline;
            $closed = $attempt->deadline && $received >= $attempt->deadline + self::SUBMISSION_GRACE;
            $tx = $DB->start_delegated_transaction();
            try {
                $usage = \question_engine::load_questions_usage_by_activity($attempt->usageid);
                if ($post !== null && !$closed) {
                    if ($autosave) {
                        // Native autosaves preserve sequence checks if a save acknowledgement is lost.
                        $usage->process_all_autosaves($received, $post);
                    } else {
                        $usage->process_all_actions($received, $post);
                    }
                }
                // GET/cron must not race the browser's final POST at the deadline.
                if ($finish || $closed || ($expired && $post !== null)) {
                    $usage->finish_all_questions();
                    $attempt->timefinish = time();
                    $attempt->grade = 100 * $usage->get_total_mark() / count($usage->get_slots());
                    $DB->update_record('practicum_attempts', $attempt);
                }
                \question_engine::save_questions_usage_by_activity($usage);
                if ($attempt->timefinish && $attempt->mode === 'exam') {
                    $instance = $DB->get_record('practicum', ['id' => $attempt->practicumid], '*', MUST_EXIST);
                    \practicum_update_grades($instance, $attempt->userid);
                }
                $tx->allow_commit();
                return $attempt;
            } catch (\Throwable $error) { $tx->rollback($error); }
        } finally { $lock->release(); }
    }
}
