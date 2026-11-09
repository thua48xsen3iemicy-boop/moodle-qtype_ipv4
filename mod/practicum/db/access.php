<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$capabilities = [
    'mod/practicum:addinstance' => ['riskbitmask' => RISK_XSS, 'captype' => 'write', 'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW], 'clonepermissionsfrom' => 'moodle/course:manageactivities'],
    'mod/practicum:view' => ['captype' => 'read', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['student' => CAP_ALLOW, 'teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]],
    'mod/practicum:attempt' => ['captype' => 'write', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['student' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]],
    'mod/practicum:viewreports' => ['captype' => 'read', 'contextlevel' => CONTEXT_MODULE,
        'archetypes' => ['teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]],
];
