<?php
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_practicum\local;
defined('MOODLE_INTERNAL') || die();
/** Versioned learning content paired with existing question adapters. */
final class learning_topic {
    public const IPV4_ORDER = 'ipv4_order_v1';

    /** Store the topic in existing backed-up configuration, not in question identities. */
    public static function blocks(string $topic, int $count): array {
        if ($topic !== self::IPV4_ORDER || $count < 1 || $count > 50) {
            throw new \invalid_parameter_exception('Invalid learning topic or count');
        }
        return [
            ['type' => 'ipv4_nextip', 'count' => $count, 'prefixes' => [24], 'learningtopic' => $topic],
            ['type' => 'ipv4_previousip', 'count' => $count, 'prefixes' => [24]],
        ];
    }

    public static function from_blocks(array $blocks): string {
        $topic = $blocks[0]['learningtopic'] ?? '';
        return $topic === self::IPV4_ORDER ? $topic : '';
    }

    /** Render only bundled, localised content. No teacher HTML or learner input. */
    public static function render(string $topic, bool $collapsed = false): string {
        if ($topic !== self::IPV4_ORDER) {
            return '';
        }
        $title = get_string('topic_ipv4_order', 'practicum');
        $body = get_string('theory_ipv4_order_v1', 'practicum');
        if ($collapsed) {
            return \html_writer::tag('details', \html_writer::tag('summary', $title) . $body,
                ['class' => 'practicum-theory mb-4']);
        }
        return \html_writer::tag('section', \html_writer::tag('h3', $title) . $body,
            ['class' => 'practicum-theory mb-4', 'aria-label' => $title]);
    }
}
