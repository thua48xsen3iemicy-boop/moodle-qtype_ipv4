<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.
namespace qtype_ipv4;
defined('MOODLE_INTERNAL') || die();

/** Exercise calculations, generation boundaries and response formats. */
#[\PHPUnit\Framework\Attributes\CoversClass(exercise::class)]
final class exercise_test extends \advanced_testcase {
    public function test_reference_examples(): void {
        $examples = [
            ['network', '10.37.19.214', 16, '10.37.0.0'],
            ['broadcast', '10.37.19.214', 24, '10.37.19.255'],
            ['firsthost', '10.37.19.0', 24, '10.37.19.1'],
            ['lasthost', '10.37.19.0', 24, '10.37.19.254'],
            ['prefixmask', '10.37.19.214', 16, '255.255.0.0'],
            ['maskprefix', '10.37.19.214', 16, '/16'],
            ['samesubnet', '10.37.19.214', 24, 'no'],
            ['assignable', '10.37.19.0', 16, 'yes'],
            ['nextip', '10.37.19.255', 24, '10.37.20.0'],
            ['previousip', '10.37.20.0', 24, '10.37.19.255'],
            ['nextsubnet', '10.37.19.64', 26, '10.37.19.128/26'],
            ['hostcount', '10.37.19.0', 26, '62'],
        ];
        foreach ($examples as [$type, $ip, $prefix, $expected]) {
            $this->assertSame($expected, exercise::answer([
                'type' => $type, 'ip' => $ip, 'prefix' => $prefix,
                'network' => network::address($ip, $prefix), 'other' => '10.37.20.8',
            ]));
        }
        foreach (['10.37.0.0', '10.37.255.255', '10.38.0.1'] as $ip) {
            $this->assertSame('no', exercise::answer([
                'type' => 'assignable', 'ip' => $ip, 'network' => '10.37.0.0', 'prefix' => 16,
            ]));
        }
        $this->assertSame('255.255.255.192', network::mask(26));
        $this->assertSame('0.0.0.0', network::address('10.37.19.214', 1));
        $this->assertSame('10.37.19.215', network::broadcast('10.37.19.214', 30));
    }

    public function test_all_types_and_prefixes_have_unique_valid_choices(): void {
        foreach (exercise::TYPES as $type) {
            foreach (network::PREFIXES as $prefix) {
                for ($i = 0; $i < 20; $i++) {
                    $data = exercise::generate($type, [$prefix]);
                    $correct = exercise::answer($data);
                    $this->assertSame($correct, exercise::normalise($correct, $type));
                    $this->assertContains($correct, $data['choices']);
                    $this->assertSame($data, json_decode(json_encode($data), true));
                    $this->assertCount(in_array($type, ['samesubnet', 'assignable']) ? 2 : 4,
                        array_unique($data['choices']));
                    foreach ($data['choices'] as $choice) {
                        $this->assertSame($choice, exercise::normalise($choice, $type));
                    }
                }
            }
        }
    }

    public function test_response_formats(): void {
        $this->assertSame('yes', exercise::normalise(' ДА ', 'assignable'));
        $this->assertSame('no', exercise::normalise('Нет', 'samesubnet'));
        $this->assertSame('/26', exercise::normalise('26', 'maskprefix'));
        $this->assertSame('/26', exercise::normalise(' /26 ', 'maskprefix'));
        $this->assertNull(exercise::normalise('062', 'hostcount'));
        $this->assertNull(exercise::normalise('62 hosts', 'hostcount'));
        $this->assertNull(exercise::normalise('10.37.19.128', 'nextsubnet'));
        $this->assertNull(exercise::normalise('10.37.19.128/33', 'nextsubnet'));
        $this->assertNull(exercise::normalise('010.37.19.128', 'nextip'));
    }

    public function test_overflow_is_rejected(): void {
        $this->expectException(\InvalidArgumentException::class);
        network::offset('255.255.255.255', 1);
    }
}
