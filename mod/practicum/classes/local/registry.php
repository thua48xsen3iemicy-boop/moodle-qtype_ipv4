<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();
/** Explicit allowlist: never instantiate class names received from a request. */
final class registry {
    public static function get(string $type): task_type {
        return match ($type) {
            'ipv4_network' => new ipv4_network(),
            default => throw new \invalid_parameter_exception('Unknown task type'),
        };
    }
    public static function labels(): array {
        return ['ipv4_network' => self::get('ipv4_network')->label()];
    }
}
