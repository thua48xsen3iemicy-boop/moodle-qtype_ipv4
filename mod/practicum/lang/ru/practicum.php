<?php
$string['linuxtask'] = 'Linux: {$a}';
$string['linuxfeedback'] = 'Абсолютный путь начинается с /. Относительный путь отсчитывается от текущего каталога; .. означает родительский каталог. cp сохраняет источник, mv перемещает или переименовывает. Для вложенных каталогов mkdir используйте -p, для копирования и удаления непустого каталога — -r. Пример ответа учитывает требуемый вид путей; другие эквивалентные записи тоже принимаются.';
// Moodle plugin, GPL v3 or later.
/** @package mod_practicum
 * @copyright 2026 Practicum contributors
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$string['pluginname'] = 'Практикум';
$string['modulename'] = 'Практикум';
$string['modulenameplural'] = 'Практикумы';
$string['modulename_help'] = 'Генерируемые задания для тренировки и контроля знаний.';
$string['pluginadministration'] = 'Управление практикумом';
$string['mode'] = 'Режим';
$string['training'] = 'Тренировка (без оценки в журнале)';
$string['exam'] = 'Контрольная (на оценку)';
$string['timelimit'] = 'Ограничение времени';
$string['maxattempts'] = 'Попыток контрольной (0 — без ограничения)';
$string['blocks'] = 'Блоки заданий';
$string['tasktype'] = 'Вид задания';
$string['count'] = 'Количество (0 — удалить блок)';
$string['prefixes'] = 'Допустимые префиксы';
$string['addblock'] = 'Добавить блок';
$string['invalidblocks'] = 'Настройте блоки: суммарно от 1 до 100 заданий.';
$string['ipv4network'] = 'IPv4: определение адреса сети';
$string['ipv4explanation'] = 'Сохраните сетевые октеты, а октеты части узла замените нулями.';
$string['start'] = 'Начать или продолжить';
$string['attempt'] = 'Попытка';
$string['review'] = 'Результат';
$string['continue'] = 'Продолжить';
$string['result'] = 'Результат: {$a}/100';
$string['deadline'] = 'Сдать до: {$a}. Ответы после срока не принимаются. Регулярно сохраняйте ответы.';
$string['save'] = 'Сохранить ответы';
$string['finish'] = 'Завершить попытку';
$string['back'] = 'Вернуться в практикум';
$string['expire'] = 'Завершение просроченных попыток практикума';
$string['nomoreattempts'] = 'Доступные попытки контрольной закончились.';
$string['busy'] = 'Попытка обновляется. Повторите действие позже.';
$string['practicum:addinstance'] = 'Добавлять практикум';
$string['practicum:view'] = 'Просматривать практикум';
$string['practicum:attempt'] = 'Проходить практикум';
$string['practicum:viewreports'] = 'Просматривать все попытки';
$string['privacy:metadata'] = 'Хранит владельца, планы заданий, ответы, время попыток и оценки контрольных.';
$string['grade'] = 'Оценка';

$string['ipv4task'] = 'IPv4: {$a}';
$string['explanation_network'] = 'Примените маску к IPv4-адресу: биты части узла должны стать нулями.';
$string['explanation_broadcast'] = 'В адресе сети замените все биты части узла единицами.';
$string['explanation_firsthost'] = 'Первый адрес узла — адрес сети плюс один.';
$string['explanation_lasthost'] = 'Последний адрес узла — широковещательный адрес минус один.';
$string['explanation_prefixmask'] = 'Первые N бит маски равны единице, остальные — нулю; N — префикс.';
$string['explanation_maskprefix'] = 'Префикс равен числу единичных бит в непрерывной маске.';
$string['explanation_samesubnet'] = 'Примените одинаковую маску к обоим адресам и сравните полученные адреса сети.';
$string['explanation_assignable'] = 'Адрес должен принадлежать указанной подсети и отличаться от адреса сети и broadcast.';
$string['explanation_nextip'] = 'Прибавьте один к адресу. После октета 255 выполняется перенос в предыдущий октет.';
$string['explanation_previousip'] = 'Вычтите один из адреса. Перед октетом 0 выполняется заём из предыдущего октета.';
$string['explanation_nextsubnet'] = 'Следующая подсеть начинается сразу после broadcast текущей подсети; префикс сохраняется.';
$string['explanation_hostcount'] = 'Для обычной подсети число узлов равно 2^(32 − префикс) − 2: исключены адрес сети и broadcast.';
