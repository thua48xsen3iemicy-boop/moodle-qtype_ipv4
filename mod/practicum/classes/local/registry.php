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
        foreach (\qtype_linuxcmd\exercise::TYPES as $exercise) {
            if ($type === 'linux_' . $exercise) {
                return new linux_task($exercise);
            }
        }
        if ($type === 'ipv4_network') {
            return new ipv4_network();
        }
        foreach (\qtype_ipv4\exercise::TYPES as $exercise) {
            if ($type === 'ipv4_' . $exercise) {
                return new ipv4_task($exercise);
            }
        }
        throw new \invalid_parameter_exception('Unknown task type');
    }
    public static function labels(): array {
        $labels = [];
        foreach (\qtype_ipv4\exercise::TYPES as $type) {
            $id = 'ipv4_' . $type;
            $labels[$id] = self::get($id)->label();
        }
        foreach (\qtype_linuxcmd\exercise::TYPES as $type) {
            $id = 'linux_' . $type;
            $labels[$id] = self::get($id)->label();
        }
        return $labels;
    }
}
