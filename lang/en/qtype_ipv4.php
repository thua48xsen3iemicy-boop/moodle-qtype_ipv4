<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'IPv4 and subnets';
$string['pluginname_help'] = 'Determine the network address from a generated IPv4 address and prefix. The exercise is saved for the entire attempt.';
$string['pluginname_link'] = 'question/type/ipv4';
$string['pluginnameadding'] = 'Adding an IPv4 and subnets question';
$string['pluginnameediting'] = 'Editing an IPv4 and subnets question';
$string['pluginnamesummary'] = 'Find a network address using /8, /16 or /24, by entering IPv4 or choosing one of four answers.';
$string['prefixes'] = 'Allowed prefixes';
$string['prefixes_help'] = 'Select at least one prefix. Each new attempt randomly selects one of these prefixes.';
$string['answermode'] = 'Answer mode';
$string['modetext'] = 'Enter an IPv4 address';
$string['modechoice'] = 'Choose one of four answers';
$string['chooseprefix'] = 'Select at least one prefix.';
$string['task'] = 'Determine the network address for {$a->ip}/{$a->prefix}.';
$string['enteranswer'] = 'Network address (IPv4 without prefix)';
$string['selectanswer'] = 'Select one network address.';
$string['invalidipv4'] = 'Enter an IPv4 address without a prefix, for example 10.37.19.0. Use four decimal octets from 0 to 255, without leading zeroes.';
$string['correctansweris'] = 'The correct network address is: {$a}';
$string['privacy:metadata'] = 'The plugin stores question settings only. Attempt data and responses are managed by the core question subsystem.';
