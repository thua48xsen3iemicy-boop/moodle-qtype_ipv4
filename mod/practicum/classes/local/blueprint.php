<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();
/** Immutable planning input. New task families register through registry. */
final class blueprint {
    public static function from_form(object $data): string {
        $blocks = [];
        foreach (($data->blockcount ?? []) as $i => $count) {
            if ((int)$count === 0) { continue; }
            $blocks[] = ['type' => $data->blocktype[$i], 'count' => (int)$count,
                'prefixes' => array_map('intval', explode(',', $data->blockprefix[$i]))];
        }
        self::validate($blocks);
        return json_encode($blocks, JSON_THROW_ON_ERROR);
    }
    public static function validate(array $blocks): void {
        $total = 0;
        foreach ($blocks as $block) {
            registry::get($block['type'])->validate($block);
            if ($block['count'] < 1 || $block['count'] > 100) {
                throw new \invalid_parameter_exception('Invalid block count');
            }
            $total += $block['count'];
        }
        if ($total < 1 || $total > 100) {
            throw new \invalid_parameter_exception('Total must be between 1 and 100');
        }
    }
    public static function plan(array $blocks): array {
        self::validate($blocks);
        $plan = [];
        foreach ($blocks as $block) {
            for ($i = 0; $i < $block['count']; $i++) {
                $plan[] = ['type' => $block['type'], 'version' => 1, 'settings' => $block];
            }
        }
        shuffle($plan);
        return $plan;
    }
}
