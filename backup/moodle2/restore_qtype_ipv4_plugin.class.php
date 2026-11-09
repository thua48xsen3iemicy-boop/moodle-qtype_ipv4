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
require_once($CFG->dirroot . '/backup/moodle2/restore_qtype_extrafields_plugin.class.php');

/** Standard backup/restore support for the extra options table. */
class restore_qtype_ipv4_plugin extends restore_qtype_extrafields_plugin {
    public function process_ipv4($data) {
        $data['tasktype'] = $data['tasktype'] ?? 'network';
        $this->really_process_extra_question_fields($data);
    }
}
