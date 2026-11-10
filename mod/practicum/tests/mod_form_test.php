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
        $this->assertSame(['linux_move'], $defaults['blocktype']);
        $this->assertSame([3], $defaults['blockcount']);
    }
}
