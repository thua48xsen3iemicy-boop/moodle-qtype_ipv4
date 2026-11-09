<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$tasks = [['classname' => \mod_practicum\task\expire_attempts::class, 'blocking' => 0, 'minute' => '*', 'hour' => '*', 'day' => '*', 'month' => '*', 'dayofweek' => '*']];
