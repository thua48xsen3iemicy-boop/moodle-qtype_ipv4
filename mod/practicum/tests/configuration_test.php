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
            } else if ($type === 'network') {
                $this->assertSame('text', $adapter->answer_mode());
                $this->assertNotSame($oldkey, $adapter->template_key($settings));
                $newkey = 'practicum_ipv4_v4_' . sha1(json_encode([
                    'type' => 'network', 'prefixes' => [26], 'networkformat' => 'cidr',
                ], JSON_THROW_ON_ERROR));
                $this->assertSame($newkey, $adapter->template_key($settings));
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
        $this->assertSame(['type' => 'linux_copytree', 'count' => 2, 'pathmode' => 'random'], $blocks[0]);
        $this->assertSame([26], $blocks[1]['prefixes']);
        $this->assertSame('random', $blocks[2]['pathmode']);
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
        $this->assertCount(count(\qtype_linuxcmd\exercise::TYPES) * count(\qtype_linuxcmd\exercise::MODES), array_unique($keys));
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
                $this->assertSame('random', $slot['settings']['pathmode']);
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

    public function test_new_forms_automatically_randomise_paths_and_legacy_plans_stay_fixed(): void {
        foreach (['advanced', 'each', 'random'] as $selection) {
            $blocks = json_decode(local\blueprint::from_form((object)[
                'selectionmode' => $selection, 'selectedtypes' => ['linux_copyfile'],
                'sharedcount' => 4, 'blocktype' => ['linux_copyfile'], 'blockcount' => [4],
            ]), true);
            foreach (local\blueprint::plan($blocks) as $slot) {
                $this->assertSame('random', $slot['settings']['pathmode']);
            }
        }
        foreach (['absolute', 'relative', 'mixed'] as $mode) {
            $settings = ['type' => 'linux_copyfile', 'count' => 1, 'pathmode' => $mode];
            $slot = local\blueprint::plan([$settings])[0];
            $this->assertSame($settings, $slot['settings']);
            $key = 'practicum_linuxcmd_v1_' . sha1(json_encode([
                'type' => 'copyfile', 'pathmode' => $mode,
            ], JSON_THROW_ON_ERROR));
            $this->assertSame($key, local\registry::get('linux_copyfile')->template_key($settings));
            $this->assertNotSame($key, local\registry::get('linux_copyfile')->template_key(['pathmode' => 'random']));
        }
    }

    public function test_categories_cover_every_type_once_with_readable_labels(): void {
        $groups = local\registry::groups();
        $all = array_merge(...array_values($groups));
        $this->assertCount(count(local\registry::labels()), $all);
        $this->assertCount(count($all), array_unique($all));
        foreach ($all as $type) {
            $this->assertTrue(isset(local\registry::labels()[$type]));
        }
        foreach (local\registry::group_labels() as $group => $label) {
            $this->assertTrue(isset($groups[$group]));
            $this->assertStringNotContainsString('[[', $label);
        }
        foreach ($groups['files'] as $type) {
            $this->assertStringNotContainsString('Linux:', local\registry::labels()[$type]);
        }
    }

    public function test_each_category_can_be_selected_without_clicking_individual_types(): void {
        foreach (local\registry::groups() as $group => $types) {
            foreach (['each', 'random'] as $mode) {
                $blocks = json_decode(local\blueprint::from_form((object)[
                    'selectionmode' => $mode, 'selectedgroups' => [$group => 1],
                    'sharedcount' => 2, 'sharedprefix' => [24],
                ]), true);
                $this->assertSame($types, $blocks[0]['types']);
                $this->assertSame([$group], $blocks[0]['groups']);
                $this->assertSame([], $blocks[0]['individualtypes']);
                $plan = local\blueprint::plan($blocks);
                $this->assertCount($mode === 'each' ? 2 * count($types) : 2, $plan);
                foreach ($plan as $slot) {
                    $this->assertTrue(in_array($slot['type'], $types, true));
                }
            }
        }
    }

    public function test_categories_combine_with_extra_types_without_duplicates_and_roundtrip(): void {
        $form = (object)[
            'selectionmode' => 'each', 'selectedgroups' => ['files' => 1, 'routing' => 1, 'subnet' => 0],
            'selectedtypes' => ['linux_move', 'ipv4_network', 'ipv4_network'],
            'sharedcount' => 1, 'sharedprefix' => [26],
        ];
        $blocks = json_decode(local\blueprint::from_form($form), true);
        $groups = local\registry::groups();
        $this->assertCount(count($groups['files']) + count($groups['routing']) + 1, $blocks[0]['types']);
        $this->assertSame(['files', 'routing'], $blocks[0]['groups']);
        $this->assertSame(['linux_move', 'ipv4_network'], $blocks[0]['individualtypes']);
        $this->assertCount(count($blocks[0]['types']), local\blueprint::plan($blocks));
        $form->selectedgroups = array_fill_keys($blocks[0]['groups'], 1);
        $form->selectedtypes = $blocks[0]['individualtypes'];
        $this->assertSame($blocks, json_decode(local\blueprint::from_form($form), true));
        // Concrete snapshots do not change if form categories are later edited.
        $snapshot = json_encode($blocks);
        $form->selectedgroups = ['diagnostic' => 1];
        local\blueprint::from_form($form);
        $this->assertSame($snapshot, json_encode($blocks));
    }

    public function test_categories_enforce_expanded_total_limit(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'selectionmode' => 'each', 'selectedgroups' => ['files' => 1], 'sharedcount' => 11,
        ]);
    }

    public function test_unknown_category_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'selectionmode' => 'random', 'selectedgroups' => ['unknown' => 1], 'sharedcount' => 2,
        ]);
    }

    public function test_malformed_category_selection_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'selectionmode' => 'each', 'selectedgroups' => 'files', 'sharedcount' => 1,
        ]);
    }

    public function test_select_all_overrides_category_and_individual_choices(): void {
        $blocks = json_decode(local\blueprint::from_form((object)[
            'selectionmode' => 'each', 'selectalltypes' => 1, 'selectedgroups' => ['files' => 1],
            'selectedtypes' => ['linux_move'], 'sharedcount' => 1, 'sharedprefix' => [24],
        ]), true);
        $this->assertSame(array_keys(local\registry::labels()), $blocks[0]['types']);
        $this->assertFalse(isset($blocks[0]['groups']));
        $this->assertFalse(isset($blocks[0]['individualtypes']));
    }

    public function test_once_includes_every_subnet_type_exactly_once_without_a_count(): void {
        $types = local\registry::groups()['subnet'];
        $this->assertCount(12, $types);
        // Omitted and stale counts must never multiply or limit the category.
        foreach ([null, 6, 12, 100, -1] as $count) {
            $blocks = json_decode(local\blueprint::from_form((object)[
                'selectionmode' => 'once', 'selectedgroups' => ['subnet' => 1],
                'sharedcount' => $count, 'sharedprefix' => [8, 16, 24],
            ]), true);
            $this->assertSame(1, $blocks[0]['count']);
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $plan = local\blueprint::plan($blocks);
                $this->assertCount(12, $plan);
                $counts = array_count_values(array_column($plan, 'type'));
                foreach ($types as $type) {
                    $this->assertSame(1, $counts[$type] ?? 0);
                }
                foreach ($plan as $slot) {
                    $this->assertSame([8, 16, 24], $slot['settings']['prefixes']);
                }
            }
        }
    }

    public function test_once_combines_categories_and_extra_types_without_repeating_types(): void {
        $blocks = json_decode(local\blueprint::from_form((object)[
            'selectionmode' => 'once', 'selectedgroups' => ['subnet' => 1, 'files' => 1],
            'selectedtypes' => ['ipv4_network', 'linux_move', 'ipv4_network'], 'sharedprefix' => [26],
        ]), true);
        $plan = local\blueprint::plan($blocks);
        $this->assertCount(22, $plan);
        foreach (array_count_values(array_column($plan, 'type')) as $count) {
            $this->assertSame(1, $count);
        }
        foreach ($plan as $slot) {
            if (str_starts_with($slot['type'], 'linux_')) {
                $this->assertSame('random', $slot['settings']['pathmode']);
            }
        }
        $all = json_decode(local\blueprint::from_form((object)[
            'selectionmode' => 'once', 'selectalltypes' => 1, 'sharedprefix' => [24],
        ]), true);
        $this->assertCount(count(local\registry::labels()), local\blueprint::plan($all));
    }

    public function test_once_requires_a_selected_category_or_type(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)['selectionmode' => 'once']);
    }

    public function test_saved_once_block_cannot_repeat_its_types(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::validate([[
            'selection' => 'once', 'types' => ['linux_move'], 'count' => 2, 'pathmode' => 'random',
        ]]);
    }

    public function test_once_still_enforces_the_total_question_limit(): void {
        $block = json_decode(local\blueprint::from_form((object)[
            'selectionmode' => 'once', 'selectalltypes' => 1, 'sharedprefix' => [24],
        ]), true)[0];
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::validate([$block, $block]);
    }

    public function test_category_labels_display_current_type_counts(): void {
        foreach (local\registry::groups() as $group => $types) {
            $this->assertTrue(str_ends_with(local\registry::group_labels()[$group], ': ' . count($types)));
        }
    }

    public function test_multiselect_blocks_apply_counts_per_type_and_keep_different_blocks(): void {
        $blocks = json_decode(local\blueprint::from_form((object)[
            'selectionmode' => 'advanced',
            'blocktype' => [['ipv4_network', 'ipv4_hostcount', 'linux_move', 'ipv4_network'], ['ipv4_network'], []],
            'blockcount' => [2, 3, 0], 'blockprefix' => [[26], [16], ['ignored']],
        ]), true);
        $this->assertCount(2, $blocks);
        $this->assertSame('block', $blocks[0]['layout']);
        $this->assertSame(['ipv4_network', 'ipv4_hostcount', 'linux_move'], $blocks[0]['types']);
        $plan = local\blueprint::plan($blocks);
        $this->assertCount(9, $plan);
        $counts = array_count_values(array_column($plan, 'type'));
        $this->assertSame(5, $counts['ipv4_network']);
        $this->assertSame(2, $counts['ipv4_hostcount']);
        $this->assertSame(2, $counts['linux_move']);
        $networkprefixes = [];
        foreach ($plan as $slot) {
            $this->assertSame(1, $slot['version']);
            if ($slot['type'] === 'linux_move') {
                $this->assertSame('random', $slot['settings']['pathmode']);
                $this->assertFalse(isset($slot['settings']['prefixes']));
            } else if ($slot['type'] === 'ipv4_network') {
                $networkprefixes[] = $slot['settings']['prefixes'][0];
            } else {
                $this->assertSame([26], $slot['settings']['prefixes']);
            }
        }
        $prefixcounts = array_count_values($networkprefixes);
        $this->assertSame(2, $prefixcounts[26]);
        $this->assertSame(3, $prefixcounts[16]);
    }

    public function test_file_only_multiselect_blocks_ignore_ipv4_prefixes(): void {
        $blocks = json_decode(local\blueprint::from_form((object)[
            'blocktype' => [['linux_move', 'linux_touch']], 'blockcount' => [2],
            'blockprefix' => [['invalid-but-unused']],
        ]), true);
        $this->assertSame([], $blocks[0]['prefixes']);
        $this->assertCount(4, local\blueprint::plan($blocks));
    }

    public function test_multiselect_blocks_enforce_expanded_total_limit(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'blocktype' => [['linux_move', 'linux_touch']], 'blockcount' => [51],
        ]);
    }

    public function test_positive_block_count_requires_a_type(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)['blocktype' => [[]], 'blockcount' => [2]]);
    }

    public function test_multiselect_blocks_reject_unknown_types(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'blocktype' => [['linux_move', 'shell_exec']], 'blockcount' => [2],
        ]);
    }

    public function test_mixed_multiselect_blocks_require_valid_ipv4_prefixes(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::from_form((object)[
            'blocktype' => [['linux_move', 'ipv4_network']], 'blockcount' => [2], 'blockprefix' => [[]],
        ]);
    }

    public function test_linux_rejects_unknown_path_mode(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::validate([['type' => 'linux_move', 'count' => 1, 'pathmode' => 'shell']]);
    }
}
