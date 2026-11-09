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
    public static function add(int $courseid, int $cmid, int $count, array $prefixes, string $mode,
            string|array $tasktype = 'network'): int {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $exercises = array_values(array_unique(is_array($tasktype) ? $tasktype : [$tasktype]));
        $needsnetwork = (bool)array_intersect($exercises, exercise::SUBNET_TYPES);
        if (!$exercises || count($exercises) > $count ||
                array_filter($exercises, static fn($id) => !exercise\catalog::valid($id)) ||
                $count < 1 || $count > self::MAX_COUNT ||
                ($needsnetwork && (!$prefixes || array_diff($prefixes, network::PREFIXES))) ||
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
                $categories = [];
                foreach (exercise\catalog::groups() as $group => $ids) {
                    if (!array_intersect($ids, $exercises)) {
                        continue;
                    }
                    $idnumber = 'qtype_ipv4_' . $group;
                    $child = $DB->get_record('question_categories',
                            ['contextid' => $category->contextid, 'idnumber' => $idnumber]);
                    if (!$child) {
                        $child = (object)[
                            'contextid' => $category->contextid, 'parent' => $category->id,
                            'name' => get_string('group_' . $group, 'qtype_ipv4'),
                            'info' => '', 'infoformat' => FORMAT_PLAIN, 'sortorder' => 999,
                            'stamp' => make_unique_id_code(), 'idnumber' => $idnumber,
                        ];
                        $child->id = $DB->insert_record('question_categories', $child);
                    }
                    foreach ($ids as $id) {
                        $categories[$id] = $child;
                    }
                }
                for ($i = 1; $i <= $count; $i++) {
                    $exercise = $exercises[($i - 1) % count($exercises)];
                    $targetcategory = $categories[$exercise];
                    $form = (object)[
                        'category' => $targetcategory->id . ',' . $targetcategory->contextid,
                        'name' => get_string('type_' . $exercise, 'qtype_ipv4') . ' ' . ($offset + $i),
                        'questiontext' => ['text' => get_string('generatedintro', 'qtype_ipv4'), 'format' => FORMAT_PLAIN],
                        'generalfeedback' => ['text' => '', 'format' => FORMAT_PLAIN],
                        'defaultmark' => 1, 'penalty' => 0.3333333,
                        'status' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY,
                        'prefixes' => $needsnetwork ? implode(',', array_unique($prefixes)) : '8,16,24',
                        'answermode' => in_array($exercise, exercise::SUBNET_TYPES, true) ? $mode : 'text',
                        'tasktype' => $exercise,
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
