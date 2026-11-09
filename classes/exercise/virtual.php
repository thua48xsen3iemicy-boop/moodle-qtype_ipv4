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

/** Independently registered virtual exercises. */
final class virtual {
    /** Task identifiers and canonical commands. */
    public static function commands(): array {
        return [
            'virtual_dummy' => 'ip link add name {dummy} type dummy',
            'virtual_veth' => 'ip link add name {vetha} type veth peer name {vethb}',
            'virtual_vlan' => 'ip link add link {interface} name {vlanname} type vlan id {vlan}',
            'virtual_bridge' => 'ip link add name {bridge} type bridge',
            'virtual_master' => 'ip link set dev {interface} master {bridge}',
            'virtual_setup_vlan' => "ip link add link {interface} name {vlanname} type vlan id {vlan}\nip address add {address}/24 dev {vlanname}\nip link set dev {vlanname} up",
            'virtual_del' => 'ip link del dev {dummy}',
        ];
    }
}
