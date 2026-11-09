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
$string['pluginname_help'] = 'Выберите тип задания, префиксы и режим ответа. Условие сохраняется на всю попытку.';
$string['pluginname_link'] = 'question/type/ipv4';
$string['pluginnameadding'] = 'Добавление вопроса «IPv4 и подсети»';
$string['pluginnameediting'] = 'Редактирование вопроса «IPv4 и подсети»';
$string['pluginnamesummary'] = 'Задания по адресам IPv4 и подсетям с префиксами /1–/30.';
$string['prefixes'] = 'Допустимые префиксы';
$string['prefixes_help'] = 'Выберите хотя бы один префикс. При каждой новой попытке случайно выбирается один из отмеченных префиксов.';
$string['answermode'] = 'Режим ответа';
$string['modetext'] = 'Ручной ввод ответа';
$string['modechoice'] = 'Выбор ответа (2 для Да/Нет, иначе 4)';
$string['chooseprefix'] = 'Выберите хотя бы один префикс.';
$string['task'] = 'Определите адрес сети для {$a->ip}/{$a->prefix}.';
$string['enteranswer'] = 'Ответ';
$string['selectanswer'] = 'Выберите ответ.';
$string['invalidipv4'] = 'Введите IPv4 без префикса, например 10.37.19.0: четыре десятичных октета от 0 до 255 без ведущих нулей.';
$string['correctansweris'] = 'Правильный ответ: {$a}';
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
$string['generatedname'] = 'IPv4 — задание {$a}';
$string['generatedtext'] = 'Решите задание по IPv4.';
$string['batchadded'] = 'В тест добавлено вопросов IPv4: {$a}.';
$string['batchintro'] = 'Выберите тест и количество вопросов. IP-адреса и префиксы генерируются случайно для каждого учащегося при старте попытки и сохраняются при обновлении страницы или продолжении. Вопросы добавятся в конец теста; существующие останутся на месте. Каждый вопрос оценивается в один балл. Случайные совпадения условий возможны.';

$string['tasktype'] = 'Тип задания';
$string['answer_yes'] = 'Да';
$string['answer_no'] = 'Нет';
$string['invalidanswer'] = 'Введите ответ в формате задания: IPv4, адрес/префикс, маска, число или Да/Нет.';
$string['type_network'] = 'Найти адрес сети';
$string['task_network'] = 'Определите адрес сети для {$a->ip}/{$a->prefix}.';
$string['type_broadcast'] = 'Найти broadcast';
$string['task_broadcast'] = 'Найдите широковещательный адрес сети для {$a->ip}/{$a->prefix}.';
$string['type_firsthost'] = 'Найти первый адрес узла';
$string['task_firsthost'] = 'Найдите первый адрес, назначаемый узлу в {$a->network}/{$a->prefix}.';
$string['type_lasthost'] = 'Найти последний адрес узла';
$string['task_lasthost'] = 'Найдите последний адрес, назначаемый узлу в {$a->network}/{$a->prefix}.';
$string['type_prefixmask'] = 'Перевести префикс в маску';
$string['task_prefixmask'] = 'Какая маска соответствует префиксу /{$a->prefix}?';
$string['type_maskprefix'] = 'Перевести маску в префикс';
$string['task_maskprefix'] = 'Какой префикс соответствует маске {$a->mask}?';
$string['type_samesubnet'] = 'Определить принадлежность одной подсети';
$string['task_samesubnet'] = 'Находятся ли {$a->ip} и {$a->other} в одной подсети при префиксе /{$a->prefix}?';
$string['type_assignable'] = 'Проверить возможность назначения адреса узлу';
$string['task_assignable'] = 'Можно ли назначить {$a->ip} узлу в подсети {$a->network}/{$a->prefix}?';
$string['type_nextip'] = 'Найти следующий IPv4-адрес';
$string['task_nextip'] = 'Какой IPv4-адрес идёт сразу после {$a->ip}?';
$string['type_previousip'] = 'Найти предыдущий IPv4-адрес';
$string['task_previousip'] = 'Какой IPv4-адрес идёт непосредственно перед {$a->ip}?';
$string['type_nextsubnet'] = 'Найти следующую подсеть';
$string['task_nextsubnet'] = 'Какая подсеть /{$a->prefix} идёт сразу после {$a->network}/{$a->prefix}? Ответ укажите с префиксом.';
$string['type_hostcount'] = 'Посчитать адреса для узлов';
$string['task_hostcount'] = 'Сколько адресов можно назначить узлам в обычной подсети /{$a->prefix}?';
