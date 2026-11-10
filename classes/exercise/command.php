<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Limited iproute2 command grammar for text answers. No shell execution.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_ipv4\exercise;
defined('MOODLE_INTERNAL') || die();

/** Parse the documented teaching grammar, rejecting unknown/duplicate operands. */
final class command {
    /** iproute2 uses ordered prefix matching, rather than requiring unique prefixes. */
    private static function keyword(string $token, array $words): ?string {
        if ($token === '') {
            return null;
        }
        foreach ($words as $word) {
            if (str_starts_with($word, $token)) {
                return $word;
            }
        }
        return null;
    }

    /** Canonicalise a CIDR (a bare address means a host prefix). */
    private static function cidr(string $value, bool $network = false): ?string {
        if ($value === 'default') {
            return '0.0.0.0/0';
        }
        $parts = explode('/', $value);
        if (count($parts) > 2) {
            return null;
        }
        $packed = @inet_pton($parts[0]);
        if ($packed === false) {
            return null;
        }
        $bits = strlen($packed) * 8;
        if (isset($parts[1]) && !preg_match('/^(0|[1-9][0-9]*)$/D', $parts[1])) {
            return null;
        }
        $prefix = isset($parts[1]) ? (int)$parts[1] : $bits;
        if ($prefix > $bits) {
            return null;
        }
        if ($network) {
            for ($i = 0; $i < strlen($packed); $i++) {
                $mask = max(0, min(8, $prefix - $i * 8));
                $byte = ord($packed[$i]);
                $masked = $byte & (255 << (8 - $mask));
                // ip route rejects nonzero host bits in destination prefixes.
                if ($byte !== $masked) {
                    return null;
                }
            }
        }
        return inet_ntop($packed) . '/' . $prefix;
    }

    /** Return a semantic record or null for unsupported syntax. */
    public static function parse(string $input): ?array {
        $input = trim($input);
        if (strlen($input) > 1024 || !preg_match('/^[A-Za-z0-9_.:\/ \t-]+$/D', $input)) {
            return null;
        }
        $tokens = preg_split('/[ \t]+/', $input);
        if (($tokens[0] ?? '') === 'sudo') {
            array_shift($tokens);
        }
        if (array_shift($tokens) !== 'ip') {
            return null;
        }
        $family = null;
        if (in_array($tokens[0] ?? '', ['-4', '-6'], true)) {
            $family = array_shift($tokens);
        }
        $object = self::keyword(array_shift($tokens) ?? '', ['address', 'addrlabel', 'link', 'route', 'rule']);
        if (!in_array($object, ['address', 'link', 'route'], true)) {
            return null;
        }
        $action = self::keyword(array_shift($tokens) ?? '',
                $object === 'link' ? ['add', 'set', 'change', 'delete'] : ['add', 'change', 'chg', 'delete', 'replace']);
        if ($action === null || ($object === 'address' && in_array($action, ['replace', 'change', 'chg'], true))) {
            return null;
        }
        if ($object === 'link' && $action === 'change') {
            $action = 'set';
        }
        if ($action === 'chg') {
            $action = 'change';
        }
        $result = ['object' => $object, 'action' => $action];
        $allowed = match ($object) {
            'address' => ['local', 'dev'],
            'route' => ['via', 'dev', 'oif', 'metric', 'priority', 'preference', 'table', 'blackhole', 'to'],
            'link' => $action === 'add' ? ['link', 'name', 'dev', 'type'] : ['dev', 'up', 'down', 'mtu', 'master'],
        };
        while ($tokens) {
            $token = array_shift($tokens);
            // Type-specific arguments are parsed only after `type`, just like ip link.
            if ($object === 'link' && isset($result['type'])) {
                if ($result['type'] === 'vlan' && $token === 'id' && !isset($result['id'])) {
                    $value = array_shift($tokens);
                    if ($value === null || !preg_match('/^[1-9][0-9]*$/D', $value) || (int)$value < 1 || (int)$value > 4094) {
                        return null;
                    }
                    $result['id'] = (string)(int)$value;
                    continue;
                }
                if ($result['type'] === 'veth' && $token === 'peer' && !isset($result['peer'])) {
                    if (($tokens[0] ?? '') === 'name') {
                        array_shift($tokens);
                    }
                    $value = array_shift($tokens);
                    if (!self::ifname($value)) {
                        return null;
                    }
                    $result['peer'] = $value;
                    continue;
                }
                return null;
            }
            // Not every ip operand can be abbreviated. Match the actual per-object grammar.
            $exact = match ($object) {
                'address' => ['local', 'dev'],
                'route' => ['via', 'dev', 'oif', 'preference', 'blackhole', 'to'],
                'link' => ['name', 'dev', 'up', 'down', 'mtu'],
            };
            $short = array_values(array_diff($allowed, $exact));
            $key = in_array($token, $allowed, true) ? $token : self::keyword($token, $short);
            // In ip link, `t` is txqueuelen, before type. It is outside this grammar.
            if ($object === 'link' && $token === 't') {
                $key = null;
            }
            if ($key === 'blackhole' || $key === 'up' || $key === 'down') {
                $field = $key === 'blackhole' ? 'routetype' : 'state';
                if (isset($result[$field])) {
                    return null;
                }
                $result[$field] = $key;
                continue;
            }
            if ($key !== null) {
                $key = match ($key) {
                    'local', 'to' => 'destination', 'oif' => 'dev',
                    'priority', 'preference' => 'metric', default => $key,
                };
                if ($object === 'link' && $action === 'add' && $key === 'dev') {
                    $key = 'name';
                }
                if (isset($result[$key]) || !$tokens) {
                    return null;
                }
                $value = array_shift($tokens);
            } else {
                $key = $object === 'link' ? ($action === 'add' ? 'name' : 'dev') : 'destination';
                if (isset($result[$key])) {
                    return null;
                }
                $value = $token;
            }
            if ($key === 'destination') {
                $value = self::cidr($value, $object === 'route');
                if ($value === null) {
                    return null;
                }
            } else if ($key === 'via') {
                $packed = @inet_pton($value);
                if ($packed === false) {
                    return null;
                }
                $value = inet_ntop($packed);
            } else if (in_array($key, ['metric', 'mtu', 'table'], true)) {
                if ($key === 'table' && $value === 'main') {
                    $value = '254';
                }
                if (!preg_match('/^(0|[1-9][0-9]*)$/D', $value) || strlen($value) > 10 || (float)$value > 4294967295) {
                    return null;
                }
                $value = (string)(int)$value;
            } else if ($key === 'type') {
                if (!in_array($value, ['dummy', 'veth', 'vlan', 'bridge'], true)) {
                    return null;
                }
            } else if (!self::ifname($value)) {
                return null;
            }
            $result[$key] = $value;
        }
        if ($object !== 'link') {
            $v6 = str_contains($result['destination'] ?? '', ':');
            if (($family === '-4' && $v6) || ($family === '-6' && !$v6) ||
                    (isset($result['via']) && str_contains($result['via'], ':') !== $v6)) {
                return null;
            }
        }
        if (($result['table'] ?? '') === '254') {
            unset($result['table']);
        }
        if (($result['metric'] ?? '') === '0') {
            unset($result['metric']);
        }
        return $result;
    }

    /** Linux interface names used by these exercises. */
    private static function ifname(?string $value): bool {
        return $value !== null && strlen($value) <= 15 &&
                (bool)preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.:-]*$/D', $value);
    }
}
