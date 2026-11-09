<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$string['pluginname'] = 'Practicum';
$string['modulename'] = 'Practicum';
$string['modulenameplural'] = 'Practicums';
$string['modulename_help'] = 'Generated exercises for practice and assessment.';
$string['pluginadministration'] = 'Practicum administration';
$string['mode'] = 'Mode';
$string['training'] = 'Training (ungraded, immediate feedback)';
$string['exam'] = 'Assessment (graded)';
$string['timelimit'] = 'Time limit';
$string['maxattempts'] = 'Assessment attempts (0 = unlimited)';
$string['blocks'] = 'Task blocks';
$string['tasktype'] = 'Task type';
$string['count'] = 'Number (0 removes block)';
$string['prefixes'] = 'Allowed prefixes';
$string['addblock'] = 'Add block';
$string['invalidblocks'] = 'Choose valid blocks, 1–100 questions in total.';
$string['ipv4network'] = 'IPv4: determine the network address';
$string['ipv4explanation'] = 'Keep the network octets and set the host octets to zero.';
$string['start'] = 'Start or continue';
$string['attempt'] = 'Attempt';
$string['review'] = 'Review';
$string['continue'] = 'Continue';
$string['result'] = 'Result: {$a}/100';
$string['deadline'] = 'Submit before: {$a}. Late responses are not accepted. Save your answers regularly.';
$string['save'] = 'Save answers';
$string['finish'] = 'Finish attempt';
$string['back'] = 'Back to practicum';
$string['expire'] = 'Finish expired practicum attempts';
$string['nomoreattempts'] = 'No assessment attempts remaining.';
$string['busy'] = 'The attempt is being updated. Try again.';
$string['practicum:addinstance'] = 'Add a practicum';
$string['practicum:view'] = 'View practicum';
$string['practicum:attempt'] = 'Attempt practicum';
$string['practicum:viewreports'] = 'View all attempts';
$string['privacy:metadata'] = 'Stores ownership, generated plans, answers, attempt timing and assessment grades.';
$string['grade'] = 'Grade';

$string['ipv4task'] = 'IPv4: {$a}';
$string['explanation_network'] = 'Apply the subnet mask to the IPv4 address: all host bits become zero.';
$string['explanation_broadcast'] = 'Set all host bits in the network address to one.';
$string['explanation_firsthost'] = 'The first host address is the network address plus one.';
$string['explanation_lasthost'] = 'The last host address is the broadcast address minus one.';
$string['explanation_prefixmask'] = 'The first N mask bits are one and the remaining bits are zero, where N is the prefix.';
$string['explanation_maskprefix'] = 'The prefix is the number of one bits in the contiguous subnet mask.';
$string['explanation_samesubnet'] = 'Apply the same mask to both addresses and compare the network addresses.';
$string['explanation_assignable'] = 'The address must belong to the subnet and differ from its network and broadcast addresses.';
$string['explanation_nextip'] = 'Add one to the address, carrying to the previous octet after 255.';
$string['explanation_previousip'] = 'Subtract one from the address, borrowing from the previous octet before zero.';
$string['explanation_nextsubnet'] = 'The next subnet starts immediately after the current broadcast; retain the prefix.';
$string['explanation_hostcount'] = 'Assignable hosts in a conventional subnet: 2^(32 − prefix) − 2, excluding network and broadcast.';
