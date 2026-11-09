<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.
defined('MOODLE_INTERNAL') || die();

/** Preserve existing questions while extending settings. */
function xmldb_qtype_ipv4_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2026110902) {
        $table = new xmldb_table('qtype_ipv4_options');
        $field = new xmldb_field('prefixes', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '8,16,24');
        $dbman->change_field_precision($table, $field);
        $field = new xmldb_field('tasktype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'network');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026110902, 'qtype', 'ipv4');
    }
    return true;
}
