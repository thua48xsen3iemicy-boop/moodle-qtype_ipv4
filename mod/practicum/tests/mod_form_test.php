<?php
// Moodle plugin, GPL v3 or later.
namespace mod_practicum;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../mod_form.php');

/** Settings must reopen as category selections, without expanding the visible type list. */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_practicum_mod_form::class)]
final class mod_form_test extends \basic_testcase {
    public function test_learning_topic_survives_editing_and_can_be_replaced_with_custom_questions(): void {
        $saved = local\blueprint::from_form((object)[
            'learningtopic' => local\learning_topic::IPV4_ORDER, 'topiccount' => 3,
        ]);
        $defaults = ['blocks' => $saved];
        $form = (new \ReflectionClass(\mod_practicum_mod_form::class))->newInstanceWithoutConstructor();
        $form->data_preprocessing($defaults);
        $this->assertSame(local\learning_topic::IPV4_ORDER, $defaults['learningtopic']);
        $this->assertSame(3, $defaults['topiccount']);
        $this->assertSame($saved, local\blueprint::from_form((object)$defaults));
        $defaults['learningtopic'] = '';
        $defaults['selectionmode'] = 'once';
        $defaults['selectedtypes'] = ['ipv4_network'];
        $defaults['sharedprefix'] = [24];
        $custom = json_decode(local\blueprint::from_form((object)$defaults), true);
        $this->assertSame('', local\learning_topic::from_blocks($custom));
        $this->assertCount(1, local\blueprint::plan($custom));
    }

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
