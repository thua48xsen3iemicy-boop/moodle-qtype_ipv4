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

$string['batchadd'] = 'Добавить IPv4 в тест';
$string['targetquiz'] = 'Тест';
$string['questioncount'] = 'Количество вопросов';
$string['generate'] = 'Готово — добавить вопросы';
$string['invalidcount'] = 'Введите число от 1 до {$a}.';
$string['invalidquiz'] = 'Выберите тест этого курса, который вам разрешено редактировать.';
$string['batchhasattempts'] = 'Учащиеся уже начали попытки в этом тесте. Для изменения состава вопросов выберите новый тест.';
$string['noeditablequizzes'] = 'В этом курсе нет тестов, в которые вы можете добавлять вопросы.';
$string['batchbusy'] = 'Этот тест сейчас обновляется. Повторите действие позже.';
$string['generatedname'] = 'IPv4 — адрес сети {$a}';
$string['generatedtext'] = 'Определите адрес сети.';
$string['batchadded'] = 'В тест добавлено вопросов IPv4: {$a}.';
$string['batchintro'] = 'Выберите тест и количество вопросов. IP-адреса и префиксы генерируются случайно для каждого учащегося при старте попытки и сохраняются при обновлении страницы или продолжении. Вопросы добавятся в конец теста; существующие останутся на месте. Каждый вопрос оценивается в один балл. Случайные совпадения условий возможны.';
