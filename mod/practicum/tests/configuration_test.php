<?php
// Moodle plugin, GPL v3 or later.
namespace mod_practicum;
defined('MOODLE_INTERNAL') || die();

/** Selection, immutable template identity and legacy configuration contracts. */
#[\PHPUnit\Framework\Attributes\CoversClass(local\registry::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(local\ipv4_task::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(local\blueprint::class)]
final class configuration_test extends \advanced_testcase {
    public function test_every_qtype_exercise_is_selectable_and_has_distinct_template(): void {
        $labels = local\registry::labels();
        $keys = [];
        $this->assertCount(count(\qtype_ipv4\exercise::TYPES), $labels);
        foreach (\qtype_ipv4\exercise::TYPES as $type) {
            $adapter = local\registry::get('ipv4_' . $type);
            $this->assertSame($adapter->label(), $labels['ipv4_' . $type]);
            $this->assertStringNotContainsString('[[', $adapter->label());
            $adapter->validate(['prefixes' => [1, 26, 30]]);
            $key = $adapter->template_key(['prefixes' => [26]]);
            $this->assertSame($key, $adapter->template_key(['prefixes' => [26]]));
            $this->assertNotSame($key, $adapter->template_key(['prefixes' => [24]]));
            $keys[] = $key;
        }
        $this->assertCount(count($labels), array_unique($keys));
        $this->assertInstanceOf(local\ipv4_network::class, local\registry::get('ipv4_network'));
    }

    public function test_multiselect_and_legacy_prefixes_create_valid_mixed_plan(): void {
        $blocks = json_decode(local\blueprint::from_form((object)[
            'blocktype' => ['ipv4_network', 'ipv4_hostcount', 'ipv4_assignable'],
            'blockcount' => [1, 2, 1], 'blockprefix' => ['8,16,24', ['26', '30'], [16]],
        ]), true);
        $this->assertSame([8, 16, 24], $blocks[0]['prefixes']);
        $this->assertSame([26, 30], $blocks[1]['prefixes']);
        $plan = local\blueprint::plan($blocks);
        $this->assertCount(4, $plan);
        foreach ($plan as $slot) {
            $this->assertSame(1, $slot['version']);
            $this->assertSame($slot['type'], $slot['settings']['type']);
        }
    }

    public function test_invalid_prefix_text_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'blocktype' => ['ipv4_hostcount'], 'blockcount' => [1], 'blockprefix' => [['26garbage']],
        ]);
    }

    public function test_unsupported_prefix_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\registry::get('ipv4_hostcount')->validate(['prefixes' => [31]]);
    }

    public function test_empty_prefix_selection_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\registry::get('ipv4_network')->validate(['prefixes' => []]);
    }
}
