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

$string['pluginname'] = 'IPv4 и подсети';
$string['pluginname_help'] = 'Определите адрес сети по сгенерированному IPv4-адресу и префиксу. Условие сохраняется на всю попытку.';
$string['pluginname_link'] = 'question/type/ipv4';
$string['pluginnameadding'] = 'Добавление вопроса «IPv4 и подсети»';
$string['pluginnameediting'] = 'Редактирование вопроса «IPv4 и подсети»';
$string['pluginnamesummary'] = 'Определение адреса сети с префиксом /8, /16 или /24: ввод IPv4 или выбор одного из четырёх вариантов.';
$string['prefixes'] = 'Допустимые префиксы';
$string['prefixes_help'] = 'Выберите хотя бы один префикс. При каждой новой попытке случайно выбирается один из отмеченных префиксов.';
$string['answermode'] = 'Режим ответа';
$string['modetext'] = 'Ручной ввод IPv4';
$string['modechoice'] = 'Выбор одного из четырёх вариантов';
$string['chooseprefix'] = 'Выберите хотя бы один префикс.';
$string['task'] = 'Определите адрес сети для {$a->ip}/{$a->prefix}.';
$string['enteranswer'] = 'Адрес сети (IPv4 без префикса)';
$string['selectanswer'] = 'Выберите один адрес сети.';
$string['invalidipv4'] = 'Введите IPv4 без префикса, например 10.37.19.0: четыре десятичных октета от 0 до 255 без ведущих нулей.';
$string['correctansweris'] = 'Правильный адрес сети: {$a}';
$string['privacy:metadata'] = 'Плагин хранит только настройки вопросов. Данные попыток и ответы обрабатываются штатной подсистемой вопросов Moodle.';
