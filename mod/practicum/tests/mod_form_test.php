<?php
// Moodle plugin, GPL v3 or later.
namespace mod_practicum;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../mod_form.php');

/** Settings must reopen as category selections, without expanding the visible type list. */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_practicum_mod_form::class)]
final class mod_form_test extends \basic_testcase {
    public function test_category_checkboxes_and_extra_types_survive_editing(): void {
        $saved = local\blueprint::from_form((object)[
            'selectionmode' => 'each', 'selectedgroups' => ['files' => 1, 'routing' => 1],
            'selectedtypes' => ['ipv4_network'], 'sharedcount' => 1, 'sharedprefix' => [24],
        ]);
        $defaults = ['blocks' => $saved];
        $form = (new \ReflectionClass(\mod_practicum_mod_form::class))->newInstanceWithoutConstructor();
        $form->data_preprocessing($defaults);
        $this->assertSame(['files' => 1, 'routing' => 1], $defaults['selectedgroups']);
        $this->assertSame(['ipv4_network'], $defaults['selectedtypes']);
        $this->assertSame($saved, local\blueprint::from_form((object)$defaults));
    }

    public function test_once_mode_and_categories_roundtrip_without_entering_a_count(): void {
        $saved = local\blueprint::from_form((object)[
            'selectionmode' => 'once', 'selectedgroups' => ['subnet' => 1], 'sharedprefix' => [24],
        ]);
        $defaults = ['blocks' => $saved];
        $form = (new \ReflectionClass(\mod_practicum_mod_form::class))->newInstanceWithoutConstructor();
        $form->data_preprocessing($defaults);
        $this->assertSame('once', $defaults['selectionmode']);
        $this->assertSame(['subnet' => 1], $defaults['selectedgroups']);
        $this->assertSame([], $defaults['selectedtypes']);
        $this->assertSame($saved, local\blueprint::from_form((object)$defaults));
    }

    public function test_multiselect_rows_keep_their_grouping_counts_and_prefixes_on_edit(): void {
        $saved = local\blueprint::from_form((object)[
            'blocktype' => [['ipv4_network', 'linux_move'], ['ipv4_hostcount', 'ipv4_broadcast'], ['linux_touch']],
            'blockcount' => [2, 5, 3], 'blockprefix' => [[26], [16], []],
        ]);
        $defaults = ['blocks' => $saved];
        $form = (new \ReflectionClass(\mod_practicum_mod_form::class))->newInstanceWithoutConstructor();
        $form->data_preprocessing($defaults);
        $this->assertSame('advanced', $defaults['selectionmode']);
        $this->assertSame([['ipv4_network', 'linux_move'], ['ipv4_hostcount', 'ipv4_broadcast'], ['linux_touch']],
            $defaults['blocktype']);
        $this->assertSame([2, 5, 3], $defaults['blockcount']);
        $this->assertSame([26], $defaults['blockprefix'][0]);
        $this->assertSame([16], $defaults['blockprefix'][1]);
        $this->assertSame($saved, local\blueprint::from_form((object)$defaults));
    }

    public function test_legacy_single_type_blocks_reopen_as_single_multiselect_items(): void {
        $saved = local\blueprint::from_form((object)[
            'blocktype' => ['ipv4_network', 'linux_move'], 'blockcount' => [2, 3], 'blockprefix' => [[26], []],
        ]);
        $defaults = ['blocks' => $saved];
        $form = (new \ReflectionClass(\mod_practicum_mod_form::class))->newInstanceWithoutConstructor();
        $form->data_preprocessing($defaults);
        $this->assertSame([['ipv4_network'], ['linux_move']], $defaults['blocktype']);
        $this->assertSame($saved, local\blueprint::from_form((object)$defaults));
    }

    public function test_old_selections_and_advanced_blocks_remain_editable(): void {
        $form = (new \ReflectionClass(\mod_practicum_mod_form::class))->newInstanceWithoutConstructor();
        $saved = local\blueprint::from_form((object)[
            'selectionmode' => 'random', 'selectedtypes' => ['linux_move', 'ipv4_network'],
            'sharedcount' => 6, 'sharedprefix' => [26],
        ]);
        $defaults = ['blocks' => $saved];
        $form->data_preprocessing($defaults);
        $this->assertSame([], $defaults['selectedgroups']);
        $this->assertSame(['linux_move', 'ipv4_network'], $defaults['selectedtypes']);
        $this->assertSame($saved, local\blueprint::from_form((object)$defaults));
        $defaults = ['blocks' => json_encode([['type' => 'linux_move', 'count' => 3, 'pathmode' => 'relative']])];
        $form->data_preprocessing($defaults);
        $this->assertSame('advanced', $defaults['selectionmode']);
        $this->assertSame([['linux_move']], $defaults['blocktype']);
        $this->assertSame([3], $defaults['blockcount']);
    }
}
