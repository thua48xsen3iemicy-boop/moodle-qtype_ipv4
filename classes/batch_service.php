<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_ipv4;
defined('MOODLE_INTERNAL') || die();

/** Create question templates and attach them to a quiz using Moodle APIs. */
final class batch_service {
    /** Maximum number appended in one request. */
    public const MAX_COUNT = 100;

    /** Quizzes in this course the current user may populate. */
    public static function available_quizzes(\stdClass $course): array {
        $quizzes = [];
        foreach (get_fast_modinfo($course)->get_instances_of('quiz') as $cm) {
            $context = \context_module::instance($cm->id);
            if (has_capability('mod/quiz:manage', $context) && has_capability('moodle/question:add', $context) &&
                    has_any_capability(['moodle/question:useall', 'moodle/question:usemine'], $context)) {
                $quizzes[$cm->id] = $cm->name;
            }
        }
        return $quizzes;
    }

    /** Append templates atomically; concrete exercises are generated when students start attempts. */
    public static function add(int $courseid, int $cmid, int $count, array $prefixes, string $mode): int {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        if ($count < 1 || $count > self::MAX_COUNT || !$prefixes || array_diff($prefixes, network::PREFIXES) ||
                !in_array($mode, ['text', 'choice'], true)) {
            throw new \invalid_parameter_exception('Invalid IPv4 batch settings');
        }
        $cm = get_coursemodule_from_id('quiz', $cmid, $courseid, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        require_capability('mod/quiz:manage', $context);
        require_capability('moodle/question:add', $context);
        $factory = \core\lock\lock_config::get_lock_factory('qtype_ipv4');
        $lock = $factory->get_lock('quiz_' . $cm->instance, 10);
        if (!$lock) {
            throw new \moodle_exception('batchbusy', 'qtype_ipv4');
        }
        try {
            $quizobj = \mod_quiz\quiz_settings::create($cm->instance);
            $quizobj->get_structure()->check_can_be_edited();
            $transaction = $DB->start_delegated_transaction();
            try {
                $category = question_get_default_category($context->id, true);
                $quiz = $quizobj->get_quiz();
                $quiz->cmid = $cm->id;
                $offset = $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
                $qtype = \question_bank::get_qtype('ipv4');
                for ($i = 1; $i <= $count; $i++) {
                    $form = (object)[
                        'category' => $category->id . ',' . $category->contextid,
                        'name' => get_string('generatedname', 'qtype_ipv4', $offset + $i),
                        'questiontext' => ['text' => get_string('generatedtext', 'qtype_ipv4'), 'format' => FORMAT_PLAIN],
                        'generalfeedback' => ['text' => '', 'format' => FORMAT_PLAIN],
                        'defaultmark' => 1, 'penalty' => 0.3333333,
                        'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY,
                        'prefixes' => implode(',', array_unique($prefixes)), 'answermode' => $mode,
                    ];
                    $question = $qtype->save_question((object)['qtype' => 'ipv4'], $form);
                    quiz_require_question_use($question->id);
                    quiz_add_quiz_question($question->id, $quiz);
                }
                quiz_delete_previews($quiz);
                $quizobj->get_grade_calculator()->recompute_quiz_sumgrades();
                $transaction->allow_commit();
            } catch (\Throwable $error) {
                $transaction->rollback($error);
            }
        } finally {
            $lock->release();
        }
        return $count;
    }
}
