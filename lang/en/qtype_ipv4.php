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
$string['pluginname_help'] = 'Choose an exercise, prefixes and response mode. Generated data persists throughout the attempt.';
$string['pluginname_link'] = 'question/type/ipv4';
$string['pluginnameadding'] = 'Adding an IPv4 and subnets question';
$string['pluginnameediting'] = 'Editing an IPv4 and subnets question';
$string['pluginnamesummary'] = 'IPv4 and subnet exercises with prefixes /1–/30.';
$string['prefixes'] = 'Allowed prefixes';
$string['prefixes_help'] = 'Select at least one prefix. Each new attempt randomly selects one of these prefixes.';
$string['answermode'] = 'Answer mode';
$string['modetext'] = 'Text entry';
$string['modechoice'] = 'Choice (2 for Yes/No, otherwise 4)';
$string['chooseprefix'] = 'Select at least one prefix.';
$string['task'] = 'Determine the network address for {$a->ip}/{$a->prefix}.';
$string['enteranswer'] = 'Answer';
$string['selectanswer'] = 'Select an answer.';
$string['invalidipv4'] = 'Enter an IPv4 address without a prefix, for example 10.37.19.0. Use four decimal octets from 0 to 255, without leading zeroes.';
$string['correctansweris'] = 'Correct answer: {$a}';
$string['privacy:metadata'] = 'The plugin stores question settings only. Attempt data and responses are managed by the core question subsystem.';

$string['batchadd'] = 'Add IPv4 questions to a quiz';
$string['targetquiz'] = 'Quiz';
$string['questioncount'] = 'Number of questions';
$string['generate'] = 'Done — add questions';
$string['invalidcount'] = 'Enter a number from 1 to {$a}.';
$string['invalidquiz'] = 'Select a quiz in this course that you are allowed to edit.';
$string['batchhasattempts'] = 'Students have already started attempts at this quiz. Choose a new quiz to change its questions.';
$string['noeditablequizzes'] = 'There are no quizzes in this course that you are allowed to populate.';
$string['batchbusy'] = 'This quiz is being updated. Please try again.';
$string['generatedname'] = 'IPv4 — exercise {$a}';
$string['generatedtext'] = 'Solve the IPv4 exercise.';
$string['batchadded'] = 'Added {$a} IPv4 questions to the quiz.';
$string['batchintro'] = 'Choose a quiz and how many questions to add. Each student receives randomly generated IP addresses and prefixes when starting an attempt. Refreshing or resuming preserves them. Questions already in the quiz stay in place; these questions are appended. Each question is worth one mark. Random coincidences are possible.';

$string['tasktype'] = 'Exercise type';
$string['answer_yes'] = 'Yes';
$string['answer_no'] = 'No';
$string['invalidanswer'] = 'Enter the requested format: IPv4, address/prefix, mask, number or Yes/No.';
$string['type_network'] = 'Network address';
$string['task_network'] = 'Find the network address for {$a->ip}/{$a->prefix}.';
$string['type_broadcast'] = 'Broadcast address';
$string['task_broadcast'] = 'Find the broadcast address for {$a->ip}/{$a->prefix}.';
$string['type_firsthost'] = 'First host address';
$string['task_firsthost'] = 'Find the first assignable host in {$a->network}/{$a->prefix}.';
$string['type_lasthost'] = 'Last host address';
$string['task_lasthost'] = 'Find the last assignable host in {$a->network}/{$a->prefix}.';
$string['type_prefixmask'] = 'Prefix to subnet mask';
$string['task_prefixmask'] = 'Which subnet mask corresponds to /{$a->prefix}?';
$string['type_maskprefix'] = 'Subnet mask to prefix';
$string['task_maskprefix'] = 'Which prefix corresponds to {$a->mask}?';
$string['type_samesubnet'] = 'Same subnet';
$string['task_samesubnet'] = 'Are {$a->ip} and {$a->other} in the same /{$a->prefix} subnet?';
$string['type_assignable'] = 'Assignable host address';
$string['task_assignable'] = 'Can {$a->ip} be assigned to a host in {$a->network}/{$a->prefix}?';
$string['type_nextip'] = 'Next IPv4 address';
$string['task_nextip'] = 'Which IPv4 address immediately follows {$a->ip}?';
$string['type_previousip'] = 'Previous IPv4 address';
$string['task_previousip'] = 'Which IPv4 address immediately precedes {$a->ip}?';
$string['type_nextsubnet'] = 'Next subnet';
$string['task_nextsubnet'] = 'Which /{$a->prefix} subnet immediately follows {$a->network}/{$a->prefix}? Include the prefix.';
$string['type_hostcount'] = 'Host count';
$string['task_hostcount'] = 'How many assignable host addresses are in a conventional /{$a->prefix} subnet?';
