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

/** Course menu entry for filling a quiz without creating questions individually. */
final class navigation {
    public static function extend_secondary(\core\hook\navigation\secondary_extend $hook): void {
        global $PAGE;
        if ($PAGE->context->contextlevel !== CONTEXT_COURSE ||
                !batch_service::available_quizzes($PAGE->course)) {
            return;
        }
        $hook->secondaryview->add(get_string('batchadd', 'qtype_ipv4'),
                new \moodle_url('/question/type/ipv4/generate.php', ['courseid' => $PAGE->course->id]),
                \navigation_node::TYPE_CUSTOM, null, 'qtype_ipv4_generate');
    }
}
