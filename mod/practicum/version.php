<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$plugin->component = 'mod_practicum';
$plugin->version = 2026111301;
$plugin->requires = 2026100500;
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.10.2';
$plugin->dependencies = ['qtype_ipv4' => 2026111001, 'qtype_linuxcmd' => 2026111003];
