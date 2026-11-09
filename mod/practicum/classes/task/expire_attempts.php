<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_practicum\task;
defined('MOODLE_INTERNAL') || die();
class expire_attempts extends \core\task\scheduled_task {
    public function get_name() { return get_string('expire', 'practicum'); }
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/practicum/lib.php');
        $records = $DB->get_records_select('practicum_attempts',
            'timefinish = 0 AND deadline > 0 AND deadline <= ?', [time()], 'id', 'id', 0, 100);
        foreach ($records as $record) { \mod_practicum\local\attempts::process($record->id); }
    }
}
