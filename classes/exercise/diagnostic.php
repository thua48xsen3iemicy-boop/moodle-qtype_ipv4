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

/** Generated reading and repair exercises. */
final class diagnostic {
    public static function commands(): array {
        return [
            'diag_address' => '{address}',
            'diag_prefix' => '{prefix}',
            'diag_gateway' => '{gateway}',
            'diag_longest' => '{specificgateway}',
            'diag_fix_prefix' => "ip address del {address}/16 dev {interface}\nip address add {address}/24 dev {interface}",
            'diag_fix_link' => 'ip link set dev {interface} up',
            'diag_fix_gateway' => 'ip route replace {network}/24 via {gateway}',
        ];
    }
}
