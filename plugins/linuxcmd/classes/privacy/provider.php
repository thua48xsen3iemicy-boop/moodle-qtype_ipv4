<?php
// Moodle plugin, GPL v3 or later.
namespace qtype_linuxcmd\privacy;
defined('MOODLE_INTERNAL') || die();
/** Responses and attempt steps are owned by the core question engine. */
class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string { return 'privacy:metadata'; }
}
