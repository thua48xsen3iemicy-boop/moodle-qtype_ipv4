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

/** Tests the IPv4 question implementation. */
#[\PHPUnit\Framework\Attributes\CoversClass(\qtype_ipv4\network::class)]
final class network_test extends \advanced_testcase {
    public function test_calculation_and_validation(): void {
        foreach ([8 => '10.0.0.0', 16 => '10.37.0.0', 24 => '10.37.19.0'] as $prefix => $expected) {
            $this->assertSame($expected, network::address('10.37.19.214', $prefix));
        }
        $this->assertSame('0.0.0.0', network::address('0.0.0.0', 8));
        $this->assertSame('255.255.255.0', network::address('255.255.255.255', 24));
        $this->assertSame('10.37.19.0', network::normalise(" 10.37.19.0 \t"));
        foreach (['', '1.2.3', '1.2.3.4/24', '256.0.0.0', '01.2.3.4', '-1.2.3.4',
                '1.2.3.4.5', '1. 2.3.4', '1.2.3.4 trailing', '::1'] as $invalid) {
            $this->assertNull(network::normalise($invalid), $invalid);
        }
    }

    public function test_distractor_invariants_and_variation(): void {
        foreach ([8, 16, 24] as $prefix) {
            $seen = [];
            $orders = [];
            foreach (['10.37.19.214', '0.0.0.0', '255.255.255.255'] as $ip) {
                $correct = network::address($ip, $prefix);
                for ($repeat = 0; $repeat < 100; $repeat++) {
                    $answers = network::choices($ip, $prefix);
                    $this->assertCount(4, array_unique($answers));
                    $this->assertSame(1, count(array_keys($answers, $correct, true)));
                    $inside = 0;
                    $outside = 0;
                    foreach ($answers as $answer) {
                        $this->assertSame($answer, network::normalise($answer));
                        if ($answer === $correct) {
                            continue;
                        }
                        if (network::address($answer, $prefix) === $correct) {
                            $inside++;
                        } else {
                            $outside++;
                            $this->assertSame($answer, network::address($answer, $prefix));
                        }
                    }
                    $this->assertSame(2, $inside);
                    $this->assertSame(1, $outside);
                    if ($ip === '10.37.19.214') {
                        $orders[implode(',', $answers)] = true;
                        sort($answers);
                        $seen[implode(',', $answers)] = true;
                    }
                }
            }
            $this->assertGreaterThan(1, count($seen));
            $this->assertGreaterThan(1, count($orders));
        }
    }

    public function test_generation_obeys_settings(): void {
        foreach ([[8], [16], [24], [8, 24]] as $allowed) {
            for ($i = 0; $i < 50; $i++) {
                $exercise = network::generate($allowed);
                $this->assertContains($exercise['prefix'], $allowed);
                $this->assertNotNull(network::normalise($exercise['ip']));
                $this->assertNotSame($exercise['ip'], network::address($exercise['ip'], $exercise['prefix']));
            }
        }
    }

    public function test_empty_prefixes_rejected(): void {
        $this->expectException(\InvalidArgumentException::class);
        network::generate([]);
    }

    public function test_unsupported_prefix_rejected(): void {
        $this->expectException(\InvalidArgumentException::class);
        network::address('10.0.0.1', 31);
    }
}
