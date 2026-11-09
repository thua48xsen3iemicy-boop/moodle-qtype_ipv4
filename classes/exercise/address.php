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

/** Independently registered address exercises. */
final class address {
    /** Task identifiers and canonical commands. */
    public static function commands(): array {
        return [
            'addr_add' => 'ip address add {address}/{prefix} dev {interface}',
            'addr_del' => 'ip address del {address}/{prefix} dev {interface}',
            'addr_extra' => 'ip address add {address}/{prefix} dev {interface}',
            'addr_v6' => 'ip -6 address add {ipv6}/64 dev {interface}',
            'link_up' => 'ip link set dev {interface} up',
            'link_down' => 'ip link set dev {interface} down',
            'link_mtu' => 'ip link set dev {interface} mtu {mtu}',
        ];
    }
}
