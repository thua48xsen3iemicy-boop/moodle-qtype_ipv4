<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();
/** Preserve the original adapter class and stable ipv4_network plan id. */
final class ipv4_network extends ipv4_task {
    public function __construct() {
        parent::__construct('network');
    }
}
