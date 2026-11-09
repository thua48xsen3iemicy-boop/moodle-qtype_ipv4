<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Generated networking exercises.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_ipv4\exercise;
defined('MOODLE_INTERNAL') || die();

/** Independently registered routing exercises. */
final class routing {
    /** Task identifiers and canonical commands. */
    public static function commands(): array {
        return [
            'route_default' => 'ip route add default via {gateway}',
            'route_network' => 'ip route add {network}/24 via {gateway}',
            'route_host' => 'ip route add {host}/32 via {gateway}',
            'route_del' => 'ip route del {network}/24 via {gateway}',
            'route_replace' => 'ip route replace {network}/24 via {gateway}',
            'route_metric' => 'ip route add default via {gateway} metric {metric}',
            'route_table' => 'ip route add {network}/24 via {gateway} table {table}',
            'route_blackhole' => 'ip route add blackhole {network}/24',
        ];
    }
}
