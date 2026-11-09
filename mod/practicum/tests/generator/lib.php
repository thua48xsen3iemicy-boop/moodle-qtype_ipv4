<?php
// GPL v3 or later.
defined('MOODLE_INTERNAL') || die();
class mod_practicum_generator extends testing_module_generator {
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;
        $record->mode = $record->mode ?? 'training';
        $record->timelimit = $record->timelimit ?? 0;
        $record->maxattempts = $record->maxattempts ?? 1;
        $record->blocktype = $record->blocktype ?? ['ipv4_network'];
        $record->blockcount = $record->blockcount ?? [3];
        $record->blockprefix = $record->blockprefix ?? ['8,16,24'];
        return parent::create_instance($record, $options);
    }
}
