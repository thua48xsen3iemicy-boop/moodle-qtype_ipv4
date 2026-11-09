<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();
/** Contract for task families; no execution infrastructure is assumed. */
interface task_type {
    public function label(): string;
    public function validate(array $settings): void;
    public function create_question(array $settings, int $contextid): \question_definition;
}
