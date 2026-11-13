<?php
$string['linuxtask'] = 'File and path operations › {$a}';
$string['linuxfeedback'] = 'Absolute paths start with /. Relative paths start at the working directory; .. names its parent. cp preserves the source; mv moves or renames it. Use mkdir -p for missing parent directories and -r for recursive copying or removal. The example answer follows the required path styles; equivalent literal commands are also accepted.';
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
$string['deadline'] = 'Time ends at {$a}. Answers are saved automatically while this page is open and submitted when time runs out. Check the save status; you can also save manually.';
$string['timeleft'] = 'Time remaining:';
$string['saving'] = 'Saving answers…';
$string['saved'] = 'Answers saved.';
$string['saveerror'] = 'Could not confirm saving. Check your connection. Retrying automatically; keep this page open.';
$string['timeup'] = 'Submitting answers…';
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
$string['explanation_network'] = 'Apply the subnet mask to the IPv4 address: all host bits become zero. Include the prefix in address/prefix notation.';
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

$string['commandexplanation'] = 'Compare your response with the reference answer. Commands are checked using the supported teaching syntax and are never executed on the server.';
$string['selectionmode'] = 'Question selection';
$string['selectionadvanced'] = 'Set count for each topic';
$string['selectioneach'] = 'Same count for selected topics';
$string['selectionrandom'] = 'Random questions from selected topics';
$string['selectedtypes'] = 'Individual question types';
$string['selectalltypes'] = 'Select all question types';
$string['sharedcount'] = 'Question count';

$string['fileoperations'] = 'File and path operations';
$string['selectedgroups'] = 'Question categories';
$string['selectedgroups_help'] = 'Select a category to include all its question types. You can combine categories and individual types below; duplicates count once. Category membership is refreshed when settings are saved; existing attempts stay unchanged.';
$string['selectionhint'] = 'Select whole categories and/or individual types. Same count: this many questions per type, including types within categories. Random: this many questions in total, choosing each type independently; types may repeat. Prefixes apply to IPv4; path styles are automatic. Maximum 100 questions in total.';

$string['selectiononce'] = 'One question per type (no repeats)';
$string['categorywithcount'] = '{$a->name} — types: {$a->count}';
$string['selectiononcehint'] = 'Select categories and/or individual types: each selected type appears exactly once. The question count is automatic. Category counts show the number of types. Addresses and masks are generated randomly using the allowed prefixes. Maximum 100 questions in total.';

// Ready-made learning topics.
$string['learningtopic'] = 'Activity content';
$string['custompractice'] = 'Custom question set';
$string['learningtopic_help'] = 'A ready-made topic combines theory with existing Practicum questions. Add a separate Practicum for each topic. Training includes an inline theory reference; assessment shows theory on the start page and after submission.';
$string['topic_ipv4_order'] = 'IPv4 basics: previous and next address';
$string['topiccount'] = 'Questions of each type (1–50)';
$string['topicpractice'] = 'Practice';
$string['topicpracticehint'] = 'Find the previous and next IPv4 address. Enter four octets without a mask. In training, check answers and read explanations; start another attempt for new conditions.';
$string['theory_ipv4_order_v1'] = <<<'HTML'
<p>Learn how IPv4 addresses are written and how to find adjacent addresses, including carries across octets. Subnet masks and subnet calculations are outside this topic.</p>
<h4>Four octets, one number</h4>
<p>An IPv4 address has 32 bits, written as four decimal numbers separated by dots: <code>192.168.1.42</code>. Each part is an octet: 8 bits, or one byte. Each octet is an integer from 0 to 255.</p>
<p>A bit has two possible values, 0 and 1. Eight bits therefore give <strong>2⁸ = 256 values</strong>, starting at zero: <code>00000000₂ = 0</code> and <code>11111111₂ = 255</code>. The number 256 needs nine binary digits: <code>100000000₂</code>. That is why <code>192.168.1.256</code> is not a valid IPv4 address.</p>
<p>Think of the address as a four-digit counter in base 256. The rightmost octet changes fastest; the leftmost has the greatest weight. For example, <code>10.0.1.0</code> is exactly one greater than <code>10.0.0.255</code>.</p>
<h4>Finding the next address</h4>
<ol><li>Start at the rightmost octet.</li><li>If it is below 255, add 1 and stop.</li><li>If it equals 255, replace it with 0 and carry 1 to the octet on its left. Repeat as needed.</li></ol>
<ul><li><code>192.168.1.42 → 192.168.1.43</code> — ordinary increment.</li><li><code>192.168.1.255 → 192.168.2.0</code> — carry across one octet.</li><li><code>10.7.255.255 → 10.8.0.0</code> — carry across two octets.</li><li><code>10.255.255.255 → 11.0.0.0</code> — carry to the first octet.</li></ul>
<h4>Finding the previous address</h4>
<ol><li>Start at the rightmost octet.</li><li>If it is above 0, subtract 1 and stop.</li><li>If it equals 0, replace it with 255 and borrow 1 from the octet on its left. If that octet is also zero, continue left.</li></ol>
<ul><li><code>192.168.1.42 → 192.168.1.41</code> — ordinary decrement.</li><li><code>192.168.2.0 → 192.168.1.255</code> — borrow across one octet.</li><li><code>10.8.0.0 → 10.7.255.255</code> — borrow across two octets.</li><li><code>11.0.0.0 → 10.255.255.255</code> — borrow from the first octet.</li></ul>
<h4>Limits and common mistakes</h4>
<p>The minimum is <code>0.0.0.0</code>, the maximum is <code>255.255.255.255</code>. The minimum has no previous IPv4 address; the maximum has no next address. There is no wraparound.</p>
<p><strong>The next address in numeric order and the next usable host address are different tasks.</strong> Here we add or subtract one across the full IPv4 range. Do not skip endings <code>.0</code> or <code>.255</code>. Whether an address can be assigned to a device depends on its subnet and special rules, covered separately. For example, <code>192.168.1.254</code> is followed by <code>192.168.1.255</code>.</p>
<p>Do not change every octet at once, and never write 256 or −1. Check your result by reversing the operation: the previous address of your next address must equal the original.</p>
<h4>Check your understanding</h4>
<p>What follows <code>172.16.255.255</code>? What precedes <code>172.17.0.0</code>?</p>
<details><summary>Show explanation</summary><p>The next address is <code>172.17.0.0</code>: both trailing 255 octets become 0, and 16 becomes 17. The previous address is <code>172.16.255.255</code>: borrow from 17, turning both zero octets into 255. These operations undo each other.</p></details>
HTML;

$string['blockcount'] = 'Questions per type (0 — remove block)';
$string['blockprefix'] = 'Block prefixes';
$string['blockprefix_help'] = 'Prefixes apply to selected IPv4 exercises in this block. File and path operations ignore them. Both families can be combined in one block.';
$string['selectionadvancedhint'] = 'Select multiple types in each task selector. The count applies to each selected type: for example, 3 types with 5 questions each produce 15 questions. Add another block for different counts or prefixes. Maximum 100 questions in total.';

$string['permissions'] = 'Linux permissions';
$string['permissiontask'] = 'Linux permissions: {$a}';
