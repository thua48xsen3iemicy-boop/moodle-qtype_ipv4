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
        $topic = $data->learningtopic ?? '';
        if ($topic !== '') {
            $blocks = learning_topic::blocks($topic, (int)($data->topiccount ?? 4));
            self::validate($blocks);
            return json_encode($blocks, JSON_THROW_ON_ERROR);
        }
        $blocks = [];
        $mode = $data->selectionmode ?? 'advanced';
        if (!in_array($mode, ['advanced', 'each', 'random', 'once'], true)) {
            throw new \invalid_parameter_exception('Invalid selection mode');
        }
        if ($mode !== 'advanced') {
            $groups = $data->selectedgroups ?? [];
            $individualtypes = $data->selectedtypes ?? [];
            if (!is_array($groups) || !is_array($individualtypes)) {
                throw new \invalid_parameter_exception('Invalid selection');
            }
            $groups = array_keys(array_filter($groups));
            if (!empty($data->selectalltypes)) {
                $groups = [];
                $individualtypes = array_keys(registry::labels());
            }
            $types = registry::expand_selection($groups, $individualtypes);
            if (!$types) {
                throw new \invalid_parameter_exception('Select at least one type or category');
            }
            $prefixes = $data->sharedprefix ?? [];
            foreach ($prefixes as $prefix) {
                if (!preg_match('/^([1-9]|[12][0-9]|30)$/D', (string)$prefix)) {
                    throw new \invalid_parameter_exception('Invalid prefix');
                }
            }
            $blocks = [['selection' => $mode, 'types' => array_values(array_unique($types)),
                'count' => $mode === 'once' ? 1 : (int)($data->sharedcount ?? 0),
                'prefixes' => array_values(array_unique(array_map('intval', $prefixes))),
                'pathmode' => 'random']];
            if ($groups) {
                $blocks[0]['groups'] = $groups;
                $blocks[0]['individualtypes'] = array_values(array_unique($individualtypes));
            }
            self::validate($blocks);
            return json_encode($blocks, JSON_THROW_ON_ERROR);
        }
        foreach (($data->blockcount ?? []) as $i => $count) {
            if ((int)$count === 0) { continue; }
            if (str_starts_with($data->blocktype[$i], 'linux_')) {
                $blocks[] = ['type' => $data->blocktype[$i], 'count' => (int)$count,
                    'pathmode' => 'random'];
                continue;
            }
            $selected = $data->blockprefix[$i] ?? [];
            if (!is_array($selected)) {
                // Accept legacy comma-separated submissions and saved configurations.
                $selected = explode(',', $selected);
            }
            foreach ($selected as $prefix) {
                if (!preg_match('/^([1-9]|[12][0-9]|30)$/D', (string)$prefix)) {
                    throw new \invalid_parameter_exception('Invalid prefix');
                }
            }
            $blocks[] = ['type' => $data->blocktype[$i], 'count' => (int)$count,
                'prefixes' => array_values(array_unique(array_map('intval', $selected)))];
        }
        self::validate($blocks);
        return json_encode($blocks, JSON_THROW_ON_ERROR);
    }
    public static function validate(array $blocks): void {
        $total = 0;
        foreach ($blocks as $block) {
            if (isset($block['selection'])) {
                if (!in_array($block['selection'], ['each', 'random', 'once'], true) ||
                        (($block['selection'] === 'once') && ($block['count'] ?? null) !== 1) ||
                        empty($block['types']) || !is_array($block['types']) ||
                        count(array_unique($block['types'])) !== count($block['types'])) {
                    throw new \invalid_parameter_exception('Invalid selection');
                }
                foreach ($block['types'] as $type) {
                    registry::get($type)->validate($block);
                }
            } else {
                registry::get($block['type'])->validate($block);
            }
            if ($block['count'] < 1 || $block['count'] > 100) {
                throw new \invalid_parameter_exception('Invalid block count');
            }
            $total += $block['count'] * (in_array($block['selection'] ?? '', ['each', 'once'], true) ? count($block['types']) : 1);
        }
        if ($total < 1 || $total > 100) {
            throw new \invalid_parameter_exception('Total must be between 1 and 100');
        }
    }
    public static function plan(array $blocks): array {
        self::validate($blocks);
        $plan = [];
        foreach ($blocks as $block) {
            $types = $block['types'] ?? [$block['type']];
            $count = $block['count'] * (in_array($block['selection'] ?? '', ['each', 'once'], true) ? count($types) : 1);
            for ($i = 0; $i < $count; $i++) {
                $type = ($block['selection'] ?? '') === 'random'
                    ? $types[random_int(0, count($types) - 1)] : $types[$i % count($types)];
                $settings = isset($block['selection']) ? ['type' => $type, 'count' => $block['count']] : $block;
                if (str_starts_with($type, 'linux_')) {
                    $settings['pathmode'] = $block['pathmode'];
                } else {
                    $settings['prefixes'] = $block['prefixes'];
                }
                $plan[] = ['type' => $type, 'version' => 1, 'settings' => $settings];
            }
        }
        shuffle($plan);
        return $plan;
    }
}
