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
    /** Allowed octet-aligned prefixes. */
    public const PREFIXES = [8, 16, 24];

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
        $octets = explode('.', trim($ip));
        for ($i = intdiv($prefix, 8); $i < 4; $i++) {
            $octets[$i] = 0;
        }
        return implode('.', $octets);
    }

    /** Four distinct shuffled answers: network, neighbouring network, two within-network errors. */
    public static function choices(string $ip, int $prefix): array {
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
        $ip = $first . '.' . random_int(1, 254) . '.' . random_int(1, 254) . '.' . random_int(1, 254);
        return ['ip' => $ip, 'prefix' => $prefix, 'choices' => self::choices($ip, $prefix)];
    }
}
