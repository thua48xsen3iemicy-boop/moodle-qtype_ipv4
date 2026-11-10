<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Exercise registry and attempt generation.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_ipv4\exercise;
defined('MOODLE_INTERNAL') || die();

/** One registry for forms, persistence and generation. */
final class catalog {
    /** Grouped task identifiers. New modules are registered here. */
    public static function groups(): array {
        return [
            'subnet' => \qtype_ipv4\exercise::SUBNET_TYPES,
            'address' => array_keys(address::commands()),
            'routing' => array_keys(routing::commands()),
            'virtual' => array_keys(virtual::commands()),
            'diagnostic' => array_keys(diagnostic::commands()),
        ];
    }

    /** Searchable labels retain their group when shown in a single selector. */
    public static function options(): array {
        $options = [];
        foreach (self::groups() as $group => $ids) {
            foreach ($ids as $id) {
                $options[$id] = get_string('group_' . $group, 'qtype_ipv4') . ' › ' .
                        get_string('type_' . $id, 'qtype_ipv4');
            }
        }
        return $options;
    }

    /** Validate a persisted identifier without depending on translated labels. */
    public static function valid(string $id): bool {
        foreach (self::groups() as $ids) {
            if (in_array($id, $ids, true)) {
                return true;
            }
        }
        return false;
    }

    /** Generate synthetic, internally consistent parameters and a reference answer. */
    public static function generate(string $id): array {
        if (in_array($id, \qtype_ipv4\exercise::SUBNET_TYPES, true) || !self::valid($id)) {
            throw new \InvalidArgumentException('Unknown command or diagnostic exercise');
        }
        $n = random_int(1, 200);
        $interface = 'eth' . random_int(1, 9);
        $vlan = random_int(2, 4000);
        $p = [
            'interface' => $interface, 'address' => "10.$n.0." . random_int(10, 99),
            'extra' => "10.$n.0." . random_int(100, 199), 'prefix' => 24,
            'gateway' => "10.$n.0.1", 'oldgateway' => "10.$n.0.2", 'specificgateway' => "10.$n.0.3",
            'network' => "198.51.$n.0", 'host' => '203.0.113.' . random_int(1, 254),
            'ipv6' => '2001:db8:' . dechex($n) . '::' . dechex(random_int(2, 254)),
            'mtu' => [1280, 1400, 1450, 1500][random_int(0, 3)],
            'metric' => random_int(2, 9) * 100, 'table' => random_int(100, 200),
            'dummy' => 'dummy' . $n, 'bridge' => 'br' . $n,
            'vetha' => 'veth' . $n . 'a', 'vethb' => 'veth' . $n . 'b',
            'vlan' => $vlan, 'vlanname' => $interface . '.' . $vlan,
        ];
        if ($id === 'diag_prefix') {
            $p['prefix'] = [16, 24, 25, 26, 27][random_int(0, 4)];
        }
        $p['addroutput'] = $interface . '    inet ' . $p['address'] . '/' . $p['prefix'] . ' scope global ' . $interface;
        $p['linkoutput'] = '2: ' . $interface . ': <BROADCAST,MULTICAST> mtu 1500 state DOWN';
        $p['target'] = "198.51.$n." . random_int(130, 190);
        $p['routes'] = '10.' . $n . '.0.0/24 dev ' . $interface . ' scope link src ' . $p['address'] . "\n" .
                'default via ' . $p['gateway'] . ' dev ' . $interface . "\n" .
                '198.51.0.0/16 via ' . $p['oldgateway'] . ' dev ' . $interface . "\n" .
                $p['network'] . '/24 via ' . $p['oldgateway'] . ' dev ' . $interface . "\n" .
                "198.51.$n.128/25 via " . $p['specificgateway'] . ' dev ' . $interface;
        $commands = address::commands() + routing::commands() + virtual::commands() + diagnostic::commands();
        $replace = [];
        foreach ($p as $key => $value) {
            $replace['{' . $key . '}'] = (string)$value;
        }
        return ['exercise' => $id, 'params' => $p, 'answer' => strtr($commands[$id], $replace),
            'kind' => str_contains($commands[$id], "\n") ? 'commands' :
                    (str_starts_with($commands[$id], 'ip ') ? 'command' : 'value')];
    }

    /** Localise the saved parameters at render/review time. */
    public static function task(array $data): string {
        $replace = [];
        foreach ($data['params'] as $key => $value) {
            $replace['{' . $key . '}'] = (string)$value;
        }
        return strtr(get_string('task_' . $data['exercise'], 'qtype_ipv4'), $replace);
    }

    /** Compare supported command semantics or a diagnostic scalar, never execute input. */
    public static function correct(array $data, string $response): bool {
        if (strlen($response) > 4096) {
            return false;
        }
        if ($data['kind'] === 'commands') {
            $expected = explode("\n", $data['answer']);
            $actual = preg_split('/\r?\n/', trim($response));
            if (count($expected) !== count($actual)) {
                return false;
            }
            foreach ($expected as $index => $line) {
                $single = $data;
                $single['kind'] = 'command';
                $single['answer'] = $line;
                if (!self::correct($single, $actual[$index])) {
                    return false;
                }
            }
            return true;
        }
        if ($data['kind'] !== 'command') {
            return trim($response) === $data['answer'];
        }
        $expected = command::parse($data['answer']);
        $actual = command::parse($response);
        if ($expected === null || $actual === null) {
            return false;
        }
        if ($expected['object'] === 'route' && $expected['action'] === 'replace' && $actual['action'] === 'change') {
            $actual['action'] = 'replace';
        }
        // The route condition establishes a unique egress device. Explicitly naming it is equivalent.
        if ($expected['object'] === 'route' && isset($expected['via']) && !isset($expected['dev']) && isset($actual['dev'])) {
            if ($actual['dev'] !== $data['params']['interface']) {
                return false;
            }
            unset($actual['dev']);
        }
        // For a newly created veth pair the endpoint names may be exchanged.
        if (($expected['type'] ?? '') === 'veth' && ($actual['type'] ?? '') === 'veth' &&
                ($actual['name'] ?? '') === $expected['peer'] && ($actual['peer'] ?? '') === $expected['name']) {
            [$actual['name'], $actual['peer']] = [$actual['peer'], $actual['name']];
        }
        ksort($expected);
        ksort($actual);
        return $actual === $expected;
    }
}
