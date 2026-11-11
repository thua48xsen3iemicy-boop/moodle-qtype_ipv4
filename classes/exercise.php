<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.
namespace qtype_ipv4;
defined('MOODLE_INTERNAL') || die();

/** Generation and canonical answers for each supported exercise. */
final class exercise {
    public const SUBNET_TYPES = ['network', 'broadcast', 'firsthost', 'lasthost', 'prefixmask', 'maskprefix',
        'samesubnet', 'assignable', 'nextip', 'previousip', 'nextsubnet', 'hostcount'];

    public const COMMAND_TYPES = [
        'addr_add',
        'addr_del',
        'addr_extra',
        'addr_v6',
        'link_up',
        'link_down',
        'link_mtu',
        'route_default',
        'route_network',
        'route_host',
        'route_del',
        'route_replace',
        'route_metric',
        'route_table',
        'route_blackhole',
        'virtual_dummy',
        'virtual_veth',
        'virtual_vlan',
        'virtual_bridge',
        'virtual_master',
        'virtual_setup_vlan',
        'virtual_del',
        'diag_address',
        'diag_prefix',
        'diag_gateway',
        'diag_longest',
        'diag_fix_prefix',
        'diag_fix_link',
        'diag_fix_gateway',
    ];
    public const TYPES = self::SUBNET_TYPES;
    public const ALL_TYPES = [...self::TYPES, ...self::COMMAND_TYPES];

    public static function labels(): array {
        $labels = [];
        foreach (self::TYPES as $type) {
            $labels[$type] = get_string('type_' . $type, 'qtype_ipv4');
        }
        return $labels;
    }

    public static function generate(string $type, array $prefixes): array {
        if (!in_array($type, self::ALL_TYPES, true)) {
            throw new \InvalidArgumentException('Unknown exercise type');
        }
        if (in_array($type, self::COMMAND_TYPES, true)) {
            $data = \qtype_ipv4\exercise\catalog::generate($type);
            return $data + ['type' => $type, 'ip' => $data['params']['address'],
                'prefix' => $data['params']['prefix'], 'choices' => []];
        }
        $data = network::generate($prefixes);
        $data['type'] = $type;
        if ($type === 'network') {
            $data['networkformat'] = 'cidr';
        }
        if (in_array($type, ['nextip', 'previousip'], true) && random_int(0, 1)) {
            $octets = explode('.', $data['ip']);
            $octets[3] = $type === 'nextip' ? '255' : '0';
            $data['ip'] = implode('.', $octets);
        }
        $ip = $data['ip'];
        $prefix = $data['prefix'];
        $base = network::address($ip, $prefix);
        $broadcast = network::broadcast($ip, $prefix);
        $data['network'] = $base;
        $data['mask'] = network::mask($prefix);
        // Generate both positive and negative cases, including .0 host addresses.
        if ($type === 'samesubnet') {
            $inside = network::offset($base, 1);
            if ($inside === $ip) {
                $inside = network::offset($base, 2);
            }
            $data['other'] = random_int(0, 1) ? $inside :
                ($base === '0.0.0.0' ? network::offset($broadcast, 2) : network::offset($base, -2));
        }
        if ($type === 'assignable') {
            $candidates = [$base, $broadcast, network::offset($base, 1), network::offset($broadcast, -1)];
            $zero = explode('.', $ip);
            $zero[3] = '0';
            $candidates[] = implode('.', $zero);
            $data['ip'] = $candidates[random_int(0, count($candidates) - 1)];
        }
        if ($type === 'nextsubnet') {
            // Ensure a following subnet exists, even for /1.
            if (network::broadcast($base, $prefix) === '255.255.255.255') {
                $data['network'] = '0.0.0.0';
            }
        }
        $answer = self::answer($data);
        if (in_array($type, ['samesubnet', 'assignable'], true)) {
            $choices = ['yes', 'no'];
        } else if ($type === 'network') {
            $choices = array_map(fn($address) => $address . '/' . $prefix, network::choices($ip, $prefix));
        } else if ($type === 'hostcount') {
            $size = 2 ** (32 - $prefix);
            $choices = [(string)($size - 2), (string)$size, (string)($size - 1), (string)($size + 2)];
        } else if ($type === 'maskprefix') {
            $alternatives = array_values(array_diff(network::PREFIXES, [$prefix]));
            shuffle($alternatives);
            $choices = ['/' . $prefix];
            foreach (array_slice($alternatives, 0, 3) as $p) {
                $choices[] = '/' . $p;
            }
        } else if ($type === 'prefixmask') {
            $alternatives = array_values(array_diff(network::PREFIXES, [$prefix]));
            shuffle($alternatives);
            $choices = [$answer];
            foreach (array_slice($alternatives, 0, 3) as $p) {
                $choices[] = network::mask($p);
            }
        } else {
            $suffix = $type === 'nextsubnet' ? '/' . $prefix : '';
            $address = explode('/', $answer)[0];
            $choices = [$answer];
            foreach ([1, -1, 2, -2, 3, -3] as $delta) {
                try {
                    $choices[] = network::offset($address, $delta) . $suffix;
                } catch (\InvalidArgumentException $e) {
                    // Skip addresses outside IPv4.
                }
                if (count($choices) === 4) {
                    break;
                }
            }
        }
        shuffle($choices);
        $data['choices'] = $choices;
        return $data;
    }

    public static function answer(array $data): string {
        if (isset($data['kind'])) {
            return $data['answer'];
        }
        $ip = $data['ip'];
        $prefix = (int)$data['prefix'];
        $base = network::address($ip, $prefix);
        $broadcast = network::broadcast($ip, $prefix);
        switch ($data['type'] ?? 'network') {
            case 'network': return $base . (self::network_requires_prefix($data) ? '/' . $prefix : '');
            case 'broadcast': return $broadcast;
            case 'firsthost': return network::offset($base, 1);
            case 'lasthost': return network::offset($broadcast, -1);
            case 'prefixmask': return network::mask($prefix);
            case 'maskprefix': return '/' . $prefix;
            case 'samesubnet': return network::address($data['other'], $prefix) === $base ? 'yes' : 'no';
            case 'assignable':
                return network::address($ip, $prefix) === $data['network'] &&
                    $ip !== $data['network'] && $ip !== network::broadcast($data['network'], $prefix) ? 'yes' : 'no';
            case 'nextip': return network::offset($ip, 1);
            case 'previousip': return network::offset($ip, -1);
            case 'nextsubnet':
                return network::offset(network::broadcast($data['network'], $prefix), 1) . '/' . $prefix;
            case 'hostcount': return (string)(2 ** (32 - $prefix) - 2);
        }
        throw new \InvalidArgumentException('Unknown exercise type');
    }

    /** Old saved network scenarios without this marker keep their original answer contract. */
    public static function network_requires_prefix(array $data): bool {
        return ($data['type'] ?? 'network') === 'network' && ($data['networkformat'] ?? '') === 'cidr';
    }

    public static function normalise(string $value, string $type, bool $networkprefixrequired = false): ?string {
        $value = trim($value);
        if (in_array($type, self::COMMAND_TYPES, true)) {
            return $value === '' ? null : $value;
        }
        if (in_array($type, ['samesubnet', 'assignable'], true)) {
            $value = strtr($value, ['Д' => 'д', 'А' => 'а', 'Н' => 'н', 'Е' => 'е', 'Т' => 'т']);
            $value = strtolower($value);
            return ['yes' => 'yes', 'no' => 'no', 'да' => 'yes', 'нет' => 'no'][$value] ?? null;
        }
        if ($type === 'hostcount') {
            return preg_match('/^(0|[1-9][0-9]{0,9})$/D', $value) ? $value : null;
        }
        if ($type === 'maskprefix') {
            return preg_match('~^/?([1-9]|[12][0-9]|30)$~D', $value, $match) ? '/' . $match[1] : null;
        }
        if ($type === 'nextsubnet' || ($type === 'network' && $networkprefixrequired)) {
            $parts = explode('/', $value);
            return count($parts) === 2 && network::normalise($parts[0]) !== null &&
                preg_match('/^([1-9]|[12][0-9]|30)$/D', $parts[1]) ? $value : null;
        }
        return network::normalise($value);
    }

    public static function display(string $answer): string {
        return in_array($answer, ['yes', 'no'], true) ? get_string('answer_' . $answer, 'qtype_ipv4') : $answer;
    }
}
