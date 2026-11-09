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
require_once($CFG->libdir . '/questionlib.php');

/** Question-bank persistence through the standard extra fields API. */
class qtype_ipv4 extends question_type {
    public function extra_question_fields() {
        return ['qtype_ipv4_options', 'prefixes', 'answermode'];
    }

    public function save_question_options($question) {
        $prefixes = array_filter(explode(',', $question->prefixes), 'strlen');
        if (!$prefixes || array_diff($prefixes, ['8', '16', '24']) ||
                !in_array($question->answermode, ['text', 'choice'], true)) {
            throw new invalid_parameter_exception('Invalid IPv4 question settings');
        }
        $question->prefixes = implode(',', array_unique($prefixes));
        parent::save_question_options($question);
    }

    public function import_from_xml($data, $question, qformat_xml $format, $extra = null) {
        if (($data['@']['type'] ?? '') !== 'ipv4') {
            return false;
        }
        $question = $format->import_headers($data);
        $question->qtype = 'ipv4';
        $question->prefixes = $format->getpath($data, ['#', 'prefixes', 0, '#'], '8,16,24');
        $question->answermode = $format->getpath($data, ['#', 'answermode', 0, '#'], 'text');
        return $question;
    }

    public function get_random_guess_score($questiondata) {
        return $questiondata->options->answermode === 'choice' ? 0.25 : 0;
    }
}
