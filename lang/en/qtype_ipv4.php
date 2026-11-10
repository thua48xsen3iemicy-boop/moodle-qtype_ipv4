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

$string['group_address'] = 'Addresses and interfaces';
$string['exercise_addr_add'] = 'Add IPv4 address';
$string['task_addr_add'] = 'Assign {address}/{prefix} to {interface}.';
$string['exercise_addr_del'] = 'Delete IPv4 address';
$string['task_addr_del'] = 'Interface {interface} has primary address {extra}/{prefix} and secondary address {address}/{prefix}. Remove only the secondary address {address}/{prefix}.';
$string['exercise_addr_extra'] = 'Add secondary IPv4 address';
$string['task_addr_extra'] = 'Interface {interface} already has {extra}/{prefix}. Add {address}/{prefix}, preserving the existing address.';
$string['exercise_addr_v6'] = 'Add IPv6 address';
$string['task_addr_v6'] = 'Assign {ipv6}/64 to {interface}.';
$string['exercise_link_up'] = 'Enable interface';
$string['task_link_up'] = 'Administratively enable {interface}.';
$string['exercise_link_down'] = 'Disable interface';
$string['task_link_down'] = 'Administratively disable {interface}.';
$string['exercise_link_mtu'] = 'Change MTU';
$string['task_link_mtu'] = 'Set the MTU of {interface} to {mtu}.';
$string['group_routing'] = 'Routing';
$string['exercise_route_default'] = 'Add default gateway';
$string['task_route_default'] = 'Add a default route via {gateway}. Interface {interface} is up and has {address}/24; both gateways, if mentioned, are on this link.';
$string['exercise_route_network'] = 'Add network route';
$string['task_route_network'] = 'Add a route to {network}/24 via {gateway}. Interface {interface} is up and has {address}/24; both gateways, if mentioned, are on this link.';
$string['exercise_route_host'] = 'Add host route';
$string['task_route_host'] = 'Add a route to the single host {host} via {gateway}. Interface {interface} is up and has {address}/24; both gateways, if mentioned, are on this link.';
$string['exercise_route_del'] = 'Delete route';
$string['task_route_del'] = 'The route {network}/24 via {gateway} exists. Delete it. Interface {interface} is up and has {address}/24; both gateways, if mentioned, are on this link.';
$string['exercise_route_replace'] = 'Change gateway';
$string['task_route_replace'] = 'The route {network}/24 via {oldgateway} exists. Replace its gateway with {gateway}. Interface {interface} is up and has {address}/24; both gateways, if mentioned, are on this link.';
$string['exercise_route_metric'] = 'Add backup default route';
$string['task_route_metric'] = 'A default route via {oldgateway} with metric 100 exists. Add another via {gateway} with metric {metric}, preserving the first route. Interface {interface} is up and has {address}/24; both gateways, if mentioned, are on this link.';
$string['exercise_route_table'] = 'Add route to a table';
$string['task_route_table'] = 'Add {network}/24 via {gateway} to routing table {table}. Interface {interface} is up and has {address}/24; both gateways, if mentioned, are on this link.';
$string['exercise_route_blackhole'] = 'Discard traffic to a network';
$string['task_route_blackhole'] = 'Add a blackhole route to {network}/24.';
$string['group_virtual'] = 'Virtual interfaces';
$string['exercise_virtual_dummy'] = 'Create dummy interface';
$string['task_virtual_dummy'] = 'Create a dummy interface named {dummy}.';
$string['exercise_virtual_veth'] = 'Create veth pair';
$string['task_virtual_veth'] = 'Create a veth pair named {vetha} and {vethb}.';
$string['exercise_virtual_vlan'] = 'Create VLAN';
$string['task_virtual_vlan'] = 'Create VLAN {vlan} on {interface}, named {vlanname}.';
$string['exercise_virtual_bridge'] = 'Create bridge';
$string['task_virtual_bridge'] = 'Create a bridge named {bridge}.';
$string['exercise_virtual_master'] = 'Attach interface to bridge';
$string['task_virtual_master'] = 'The bridge {bridge} exists. Attach {interface} to it.';
$string['exercise_virtual_del'] = 'Delete virtual interface';
$string['task_virtual_del'] = 'Delete the existing dummy interface {dummy}.';
$string['group_diagnostic'] = 'Diagnostics';
$string['exercise_diag_address'] = 'Read interface address';
$string['task_diag_address'] = 'Given output: {addroutput}
Enter the IPv4 address of {interface}, without prefix.';
$string['exercise_diag_prefix'] = 'Read interface prefix';
$string['task_diag_prefix'] = 'Given output: {addroutput}
Enter the prefix length of {interface} as a number, without /.';
$string['exercise_diag_gateway'] = 'Find default gateway';
$string['task_diag_gateway'] = 'Routing table:
{routes}
Enter the default gateway address.';
$string['exercise_diag_longest'] = 'Choose longest matching route';
$string['task_diag_longest'] = 'Routing table:
{routes}
Which gateway is used for {target}? Enter its IPv4 address.';
$string['exercise_diag_fix_link'] = 'Repair disabled interface';
$string['task_diag_fix_link'] = 'Given output: {linkoutput}
Make {interface} administratively UP using one command.';
$string['exercise_diag_fix_gateway'] = 'Repair incorrect gateway';
$string['task_diag_fix_gateway'] = 'Interface {interface} is up with {address}/24. The route {network}/24 uses the wrong gateway {oldgateway}. Replace it with {gateway}, preserving other routes.';
$string['group_subnet'] = 'Subnets';
$string['exercise_network'] = 'Calculate network address';
$string['exercise'] = 'Exercise (search by topic or name)';
$string['chooseexercise'] = 'Select at least one exercise and set the question count to at least the number of selected exercises.';
$string['generatedintro'] = 'Complete the generated exercise below.';
$string['entercommand'] = 'Linux ip command';
$string['enterresponse'] = 'Enter your answer.';
$string['commandhelp'] = 'Enter one ip command. You may abbreviate the object and action (for example ip a a), reorder operands, and use sudo or -4/-6. Write dev, via and mtu in full. Do not use shell operators.';
$string['exercise_virtual_setup_vlan'] = 'Create, address and enable VLAN';
$string['task_virtual_setup_vlan'] = 'Using three commands, in this order: create VLAN {vlan} on {interface} named {vlanname}; assign {address}/24 to it; enable it.';
$string['exercise_diag_fix_prefix'] = 'Repair incorrect prefix';
$string['commandshelp'] = 'Enter one ip command per line, in the requested order. Supported abbreviations and reordered operands are accepted. Do not use shell operators.';
$string['task_diag_fix_prefix'] = 'Interface {interface} has {address}/16 and {extra}/24. The first address should have prefix /24. Using two commands, first delete {address}/16, then add {address}/24. Preserve {extra}/24.';
$string['type_addr_add'] = $string['exercise_addr_add'];
$string['type_addr_del'] = $string['exercise_addr_del'];
$string['type_addr_extra'] = $string['exercise_addr_extra'];
$string['type_link_up'] = $string['exercise_link_up'];
$string['type_link_down'] = $string['exercise_link_down'];
$string['type_link_mtu'] = $string['exercise_link_mtu'];
$string['type_route_default'] = $string['exercise_route_default'];
$string['type_route_network'] = $string['exercise_route_network'];
$string['type_route_host'] = $string['exercise_route_host'];
$string['type_route_del'] = $string['exercise_route_del'];
$string['type_route_replace'] = $string['exercise_route_replace'];
$string['type_route_metric'] = $string['exercise_route_metric'];
$string['type_route_table'] = $string['exercise_route_table'];
$string['type_route_blackhole'] = $string['exercise_route_blackhole'];
$string['type_virtual_dummy'] = $string['exercise_virtual_dummy'];
$string['type_virtual_veth'] = $string['exercise_virtual_veth'];
$string['type_virtual_vlan'] = $string['exercise_virtual_vlan'];
$string['type_virtual_bridge'] = $string['exercise_virtual_bridge'];
$string['type_virtual_master'] = $string['exercise_virtual_master'];
$string['type_virtual_setup_vlan'] = $string['exercise_virtual_setup_vlan'];
$string['type_virtual_del'] = $string['exercise_virtual_del'];
$string['type_diag_address'] = $string['exercise_diag_address'];
$string['type_diag_prefix'] = $string['exercise_diag_prefix'];
$string['type_diag_gateway'] = $string['exercise_diag_gateway'];
$string['type_diag_longest'] = $string['exercise_diag_longest'];
$string['type_diag_fix_prefix'] = $string['exercise_diag_fix_prefix'];
$string['type_diag_fix_link'] = $string['exercise_diag_fix_link'];
$string['type_diag_fix_gateway'] = $string['exercise_diag_fix_gateway'];

$string['type_addr_v6'] = $string['exercise_addr_v6'];
