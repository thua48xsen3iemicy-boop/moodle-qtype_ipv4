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
        $this->assertCount(count(\qtype_ipv4\exercise::ALL_TYPES) + count(\qtype_linuxcmd\exercise::TYPES), $labels);
        foreach (\qtype_ipv4\exercise::ALL_TYPES as $type) {
            $adapter = local\registry::get('ipv4_' . $type);
            $this->assertSame($adapter->label(), $labels['ipv4_' . $type]);
            $this->assertStringNotContainsString('[[', $adapter->label());
            $adapter->validate(['prefixes' => [1, 26, 30]]);
            $key = $adapter->template_key(['prefixes' => [26]]);
            $this->assertSame($key, $adapter->template_key(['prefixes' => [26]]));
            $this->assertNotSame($key, $adapter->template_key(['prefixes' => [24]]));
            $keys[] = $key;
        }
        $this->assertCount(count(\qtype_ipv4\exercise::ALL_TYPES), array_unique($keys));
        $this->assertInstanceOf(local\ipv4_network::class, local\registry::get('ipv4_network'));
    }

    public function test_boolean_choice_templates_do_not_reuse_old_text_templates(): void {
        foreach (\qtype_ipv4\exercise::ALL_TYPES as $type) {
            $adapter = local\registry::get('ipv4_' . $type);
            $settings = ['prefixes' => [26]];
            $oldkey = 'practicum_ipv4_v2_' . sha1(json_encode([
                'type' => $type, 'prefixes' => [26],
            ], JSON_THROW_ON_ERROR));
            if (in_array($type, ['samesubnet', 'assignable'], true)) {
                $this->assertSame('choice', $adapter->answer_mode());
                $this->assertNotSame($oldkey, $adapter->template_key($settings));
            } else {
                $this->assertSame('text', $adapter->answer_mode());
                $this->assertSame($oldkey, $adapter->template_key($settings));
            }
        }
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

    public function test_linux_and_ipv4_blocks_keep_separate_settings(): void {
        $blocks = json_decode(local\blueprint::from_form((object)[
            'blocktype' => ['linux_copytree', 'ipv4_network', 'linux_mkdir'],
            'blockcount' => [2, 1, 1], 'blockprefix' => [[], [26], ['invalid-but-unused']],
            'blockpathmode' => ['mixed', 'unused', 'absolute'],
        ]), true);
        $this->assertSame(['type' => 'linux_copytree', 'count' => 2, 'pathmode' => 'mixed'], $blocks[0]);
        $this->assertSame([26], $blocks[1]['prefixes']);
        $this->assertSame('absolute', $blocks[2]['pathmode']);
        $this->assertCount(4, local\blueprint::plan($blocks));
        $keys = [];
        foreach (\qtype_linuxcmd\exercise::TYPES as $type) {
            foreach (\qtype_linuxcmd\exercise::MODES as $mode) {
                $task = local\registry::get('linux_' . $type);
                $key = $task->template_key(['pathmode' => $mode]);
                $this->assertSame($key, $task->template_key(['pathmode' => $mode, 'prefixes' => [8]]));
                $keys[] = $key;
            }
        }
        $this->assertCount(30, array_unique($keys));
    }

    public function test_equal_selection_preserves_counts_and_family_settings(): void {
        $blocks = json_decode(local\blueprint::from_form((object)[
            'selectionmode' => 'each', 'selectedtypes' => ['ipv4_network', 'linux_move', 'ipv4_network'],
            'sharedcount' => 3, 'sharedprefix' => [26], 'sharedpathmode' => 'mixed',
        ]), true);
        $plan = local\blueprint::plan($blocks);
        $this->assertCount(6, $plan);
        $counts = array_count_values(array_column($plan, 'type'));
        $this->assertSame(3, $counts['ipv4_network']);
        $this->assertSame(3, $counts['linux_move']);
        foreach ($plan as $slot) {
            $this->assertSame($slot['type'], $slot['settings']['type']);
            if ($slot['type'] === 'linux_move') {
                $this->assertSame('mixed', $slot['settings']['pathmode']);
                $this->assertFalse(isset($slot['settings']['prefixes']));
            } else {
                $this->assertSame([26], $slot['settings']['prefixes']);
            }
        }
        // Stored configuration can be used again without changing an existing plan.
        $snapshot = json_encode($plan);
        local\blueprint::plan($blocks);
        $this->assertSame($snapshot, json_encode($plan));
    }

    public function test_random_selection_has_exact_total_and_only_allowed_types(): void {
        $blocks = json_decode(local\blueprint::from_form((object)[
            'selectionmode' => 'random', 'selectedtypes' => ['ipv4_network', 'linux_move'],
            'sharedcount' => 10, 'sharedprefix' => [26], 'sharedpathmode' => 'absolute',
        ]), true);
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $plan = local\blueprint::plan($blocks);
            $this->assertCount(10, $plan);
            foreach ($plan as $slot) {
                $this->assertTrue(in_array($slot['type'], $blocks[0]['types'], true));
                local\registry::get($slot['type'])->validate($slot['settings']);
            }
        }
    }

    public function test_select_all_includes_every_registered_type(): void {
        $blocks = json_decode(local\blueprint::from_form((object)[
            'selectionmode' => 'each', 'selectalltypes' => 1, 'sharedcount' => 1,
            'sharedprefix' => [24], 'sharedpathmode' => 'relative',
        ]), true);
        $this->assertSame(array_keys(local\registry::labels()), $blocks[0]['types']);
        $this->assertCount(count(local\registry::labels()), local\blueprint::plan($blocks));
    }

    public function test_equal_selection_enforces_total_limit(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'selectionmode' => 'each', 'selectedtypes' => ['ipv4_network', 'linux_move'],
            'sharedcount' => 51, 'sharedprefix' => [24], 'sharedpathmode' => 'relative',
        ]);
    }

    public function test_random_selection_requires_topics(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)['selectionmode' => 'random', 'sharedcount' => 10]);
    }

    public function test_simple_selection_rejects_malformed_prefix(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'selectionmode' => 'random', 'selectedtypes' => ['ipv4_network'],
            'sharedcount' => 6, 'sharedprefix' => ['26garbage'],
        ]);
    }

    public function test_random_selection_rejects_unknown_type(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'selectionmode' => 'random', 'selectedtypes' => ['shell_exec'], 'sharedcount' => 10,
        ]);
    }

    public function test_linux_rejects_unknown_path_mode(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'blocktype' => ['linux_move'], 'blockcount' => [1], 'blockpathmode' => ['shell'],
        ]);
    }
}
