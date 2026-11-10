<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Grammar, semantic grading and generated conditions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_ipv4;
defined('MOODLE_INTERNAL') || die();

/** Exercise-level checks independent of the question engine. */
#[\PHPUnit\Framework\Attributes\CoversClass(exercise\catalog::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(exercise\command::class)]
final class command_test extends \basic_testcase {
    public function test_all_generated_tasks_have_valid_answers_and_localised_conditions(): void {
        $seen = [];
        foreach (exercise\catalog::groups() as $ids) {
            foreach ($ids as $id) {
                $this->assertNotContains($id, $seen);
                $seen[] = $id;
                if (in_array($id, exercise::SUBNET_TYPES, true)) {
                    continue;
                }
                for ($i = 0; $i < 50; $i++) {
                    $data = exercise\catalog::generate($id);
                    $this->assertTrue(exercise\catalog::correct($data, $data['answer']), $id . ': ' . $data['answer']);
                    $this->assertFalse(exercise\catalog::correct($data, ''), $id);
                    $this->assertFalse(exercise\catalog::correct($data, $data['answer'] . ' garbage'), $id);
                    $task = exercise\catalog::task($data);
                    $this->assertNotEmpty($task);
                    $this->assertStringNotContainsString('{', $task, $id);
                    $this->assertStringNotContainsString('[[', $task, $id);
                }
            }
        }
        $this->assertCount(41, $seen);
    }

    public function test_equivalent_address_and_link_commands(): void {
        $canonical = exercise\command::parse('ip address add 192.0.2.10/24 dev eth1');
        foreach (['sudo ip a a dev eth1 192.0.2.10/24',
                ' ip -4 addr add 192.0.2.10/24 dev eth1 ',
                "ip address add\tdev\teth1 local 192.0.2.10/24"] as $command) {
            $actual = exercise\command::parse($command);
            ksort($canonical);
            ksort($actual);
            $this->assertSame($canonical, $actual, $command);
        }
        $expected = exercise\command::parse('ip link set dev eth1 up');
        $this->assertEquals($expected, exercise\command::parse('ip l s eth1 up'));
        $this->assertEquals($expected, exercise\command::parse('ip link set up dev eth1'));
        $this->assertEquals(exercise\command::parse('ip -6 addr add 2001:db8::a/64 dev eth1'),
                exercise\command::parse('ip addr add dev eth1 2001:0DB8:0:0:0:0:0:A/64'));
    }

    public function test_route_equivalence_and_wrong_operands(): void {
        $data = exercise\catalog::generate('route_default');
        $p = $data['params'];
        foreach (['ip r a via ' . $p['gateway'] . ' default',
                'ip -4 route add 0.0.0.0/0 dev ' . $p['interface'] . ' via ' . $p['gateway'],
                'sudo ip route add table main default metric 0 via ' . $p['gateway']] as $answer) {
            $this->assertTrue(exercise\catalog::correct($data, $answer), $answer);
        }
        foreach ([$data['answer'] . ' dev eth99', $data['answer'] . ' metric 99',
                $data['answer'] . ' table 100', str_replace($p['gateway'], $p['oldgateway'], $data['answer']),
                str_replace(' add ', ' del ', $data['answer']), $data['answer'] . ' via ' . $p['gateway']] as $answer) {
            $this->assertFalse(exercise\catalog::correct($data, $answer), $answer);
        }
        $data = exercise\catalog::generate('route_host');
        $this->assertTrue(exercise\catalog::correct($data, str_replace('/32', '', $data['answer'])));
        $data = exercise\catalog::generate('route_metric');
        $this->assertFalse(exercise\catalog::correct($data, preg_replace('/ metric [0-9]+/', '', $data['answer'])));
    }

    public function test_virtual_interfaces_and_multistep_order(): void {
        $data = exercise\catalog::generate('virtual_veth');
        $p = $data['params'];
        $this->assertTrue(exercise\catalog::correct($data,
                'ip link add ' . $p['vethb'] . ' type veth peer name ' . $p['vetha']));
        $data = exercise\catalog::generate('virtual_vlan');
        $this->assertFalse(exercise\catalog::correct($data,
                str_replace('id ' . $data['params']['vlan'], 'id 1', $data['answer'])));
        foreach (['virtual_setup_vlan', 'diag_fix_prefix'] as $id) {
            $data = exercise\catalog::generate($id);
            $this->assertTrue(exercise\catalog::correct($data, str_replace("\n", "\r\n", $data['answer'])));
            $this->assertFalse(exercise\catalog::correct($data, implode("\n", array_reverse(explode("\n", $data['answer'])))));
            $this->assertFalse(exercise\catalog::correct($data, explode("\n", $data['answer'])[0]));
        }
    }

    public function test_rejects_shell_syntax_unknown_flags_and_conflicting_fields(): void {
        foreach (['ip link set eth1 up; id', 'ip link set eth1 up && id',
                'ip link set eth1 up | cat', 'ip link set eth1 $(id)',
                "ip link set eth1 up\nip link set eth2 down", 'ip -batch file',
                'ip netns exec test ip link set eth1 up', 'ip link set eth1 up down',
                'ip addr add 192.0.2.1/24 dev eth1 dev eth2',
                'ip -6 addr add 192.0.2.1/24 dev eth1',
                'ip -4 addr add 2001:db8::1/64 dev eth1',
                'ip route add 192.0.2.2/24 via 10.0.0.1',
                'ip route add default via 10.0.0.1 unexpected',
                'ip link add dummy1 type dummy unknown',
                'ip address add 192.0.2.1/33 dev eth1',
                'ip route add default via 300.0.0.1',
                'ip route add default metric -1',
                'ip link set eth1 mtu 1e3', 'ip route add default metric 0200',
                'ip addr add 192.0.2.1/24 d eth1', 'ip route add default v 192.0.2.1'] as $answer) {
            $this->assertNull(exercise\command::parse($answer), $answer);
        }
    }
}
