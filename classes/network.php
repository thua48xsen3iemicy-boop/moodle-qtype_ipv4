<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_ipv4;
defined('MOODLE_INTERNAL') || die();

/** Pure, octet-based IPv4 operations (also safe on 32-bit PHP). */
final class network {
    /** Conventional subnets with network and broadcast addresses. */
    public const PREFIXES = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16,
        17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30];

    /** Parse canonical dotted decimal; reject prefixes, internal whitespace and leading zeroes. */
    public static function normalise(string $value): ?string {
        $value = trim($value);
        if (!preg_match('/^(?:0|[1-9][0-9]{0,2})(?:\.(?:0|[1-9][0-9]{0,2})){3}$/D', $value)) {
            return null;
        }
        foreach (explode('.', $value) as $octet) {
            if ((int)$octet > 255) {
                return null;
            }
        }
        return $value;
    }

    /** Return the network address. */
    public static function address(string $ip, int $prefix): string {
        if (self::normalise($ip) === null || !in_array($prefix, self::PREFIXES, true)) {
            throw new \InvalidArgumentException('Invalid IPv4 address or unsupported prefix');
        }
        $octets = array_map('intval', explode('.', trim($ip)));
        foreach ($octets as $i => &$octet) {
            $bits = max(0, min(8, $prefix - $i * 8));
            $octet = $octet & (256 - (1 << (8 - $bits)));
        }
        return implode('.', $octets);
    }

    /** Dotted decimal subnet mask. */
    public static function mask(int $prefix): string {
        return self::address('255.255.255.255', $prefix);
    }

    /** Last address of a subnet. */
    public static function broadcast(string $ip, int $prefix): string {
        $base = explode('.', self::address($ip, $prefix));
        $mask = explode('.', self::mask($prefix));
        return implode('.', array_map(fn($a, $m) => (int)$a + 255 - (int)$m, $base, $mask));
    }

    /** Add a small offset, carrying across octets without signed 32-bit operations. */
    public static function offset(string $ip, int $delta): string {
        if (self::normalise($ip) === null) {
            throw new \InvalidArgumentException('Invalid IPv4');
        }
        $octets = array_map('intval', explode('.', $ip));
        for ($i = 3; $i >= 0; $i--) {
            $value = $octets[$i] + $delta;
            $octets[$i] = (($value % 256) + 256) % 256;
            $delta = (int)floor($value / 256);
        }
        if ($delta !== 0) {
            throw new \InvalidArgumentException('IPv4 overflow');
        }
        return implode('.', $octets);
    }

    /** Four distinct shuffled answers: network, neighbouring network, two within-network errors. */
    public static function choices(string $ip, int $prefix): array {
        if ($prefix % 8 !== 0) {
            $correct = self::address($ip, $prefix);
            $answers = [$correct, self::offset($correct, 1), self::broadcast($ip, $prefix),
                self::offset(self::broadcast($ip, $prefix), -1)];
            shuffle($answers);
            return $answers;
        }
        $correct = self::address($ip, $prefix);
        $base = array_map('intval', explode('.', $correct));
        $boundary = intdiv($prefix, 8);
        $neighbour = $base;
        $index = $boundary - 1;
        // At the edges choose the only valid direction, otherwise vary it.
        $delta = $base[$index] === 0 ? 1 : ($base[$index] === 255 ? -1 : (random_int(0, 1) ? 1 : -1));
        $neighbour[$index] += $delta;
        $first = $base;
        $first[3] = 1;
        $broadcast = $base;
        for ($i = $boundary; $i < 4; $i++) {
            $broadcast[$i] = 255;
        }
        // A boundary under a mistakenly longer prefix: /25, /17 or /9.
        $wrongboundary = $base;
        $wrongboundary[$boundary] = 128;
        $pool = [implode('.', $first), implode('.', $broadcast), implode('.', $wrongboundary)];
        shuffle($pool);
        $answers = [$correct, implode('.', $neighbour), $pool[0], $pool[1]];
        shuffle($answers);
        return $answers;
    }

    /** Generate a unicast exercise with a nontrivial host part. */
    public static function generate(array $prefixes): array {
        if (!$prefixes || array_diff($prefixes, self::PREFIXES)) {
            throw new \InvalidArgumentException('Choose at least one supported prefix');
        }
        $prefix = (int)$prefixes[random_int(0, count($prefixes) - 1)];
        // Synthetic unicast examples; no traffic is sent to these addresses.
        do {
            $first = random_int(1, 223);
        } while ($first === 127);
        do {
            $ip = $first . '.' . random_int(1, 254) . '.' . random_int(1, 254) . '.' . random_int(1, 254);
        } while ($ip === self::address($ip, $prefix) || $ip === self::broadcast($ip, $prefix));
        return ['ip' => $ip, 'prefix' => $prefix, 'choices' => self::choices($ip, $prefix)];
    }
}
