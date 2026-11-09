<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * IPv4 and subnet questions.
 * @package qtype_ipv4
 * @copyright 2026 qtype_ipv4 contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/** Data for Moodle's question generator. */
class qtype_ipv4_test_helper extends question_test_helper {
    public function get_test_questions() {
        return ['network'];
    }

    public function get_ipv4_question_form_data_network() {
        return (object)[
            'name' => 'IPv4 network', 'questiontext' => ['text' => 'Find the network.', 'format' => FORMAT_HTML],
            'defaultmark' => 1, 'penalty' => 0.3333333, 'prefixes' => '8,16,24', 'answermode' => 'choice',
        ];
    }
}
