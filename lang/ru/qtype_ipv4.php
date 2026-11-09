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

$string['group_address'] = 'Адреса и интерфейсы';
$string['exercise_addr_add'] = 'Назначить IPv4-адрес';
$string['task_addr_add'] = 'Назначьте {address}/{prefix} интерфейсу {interface}.';
$string['exercise_addr_del'] = 'Удалить IPv4-адрес';
$string['task_addr_del'] = 'На {interface} назначены основной адрес {extra}/{prefix} и дополнительный адрес {address}/{prefix}. Удалите только дополнительный адрес {address}/{prefix}.';
$string['exercise_addr_extra'] = 'Назначить дополнительный IPv4-адрес';
$string['task_addr_extra'] = 'На {interface} уже назначен {extra}/{prefix}. Добавьте {address}/{prefix}, сохранив существующий адрес.';
$string['exercise_addr_v6'] = 'Назначить IPv6-адрес';
$string['task_addr_v6'] = 'Назначьте {ipv6}/64 интерфейсу {interface}.';
$string['exercise_link_up'] = 'Включить интерфейс';
$string['task_link_up'] = 'Административно включите интерфейс {interface}.';
$string['exercise_link_down'] = 'Выключить интерфейс';
$string['task_link_down'] = 'Административно выключите интерфейс {interface}.';
$string['exercise_link_mtu'] = 'Изменить MTU';
$string['task_link_mtu'] = 'Установите MTU интерфейса {interface} равным {mtu}.';
$string['group_routing'] = 'Маршрутизация';
$string['exercise_route_default'] = 'Добавить шлюз по умолчанию';
$string['task_route_default'] = 'Добавьте маршрут по умолчанию через {gateway}. Интерфейс {interface} включён, на нём назначен {address}/24; оба шлюза, если указаны, находятся в этой сети.';
$string['exercise_route_network'] = 'Добавить маршрут до сети';
$string['task_route_network'] = 'Добавьте маршрут до {network}/24 через {gateway}. Интерфейс {interface} включён, на нём назначен {address}/24; оба шлюза, если указаны, находятся в этой сети.';
$string['exercise_route_host'] = 'Добавить маршрут до узла';
$string['task_route_host'] = 'Добавьте маршрут до единственного узла {host} через {gateway}. Интерфейс {interface} включён, на нём назначен {address}/24; оба шлюза, если указаны, находятся в этой сети.';
$string['exercise_route_del'] = 'Удалить маршрут';
$string['task_route_del'] = 'Существует маршрут {network}/24 через {gateway}. Удалите его. Интерфейс {interface} включён, на нём назначен {address}/24; оба шлюза, если указаны, находятся в этой сети.';
$string['exercise_route_replace'] = 'Изменить шлюз маршрута';
$string['task_route_replace'] = 'Существует маршрут {network}/24 через {oldgateway}. Замените шлюз на {gateway}. Интерфейс {interface} включён, на нём назначен {address}/24; оба шлюза, если указаны, находятся в этой сети.';
$string['exercise_route_metric'] = 'Добавить резервный маршрут';
$string['task_route_metric'] = 'Есть маршрут по умолчанию через {oldgateway} с метрикой 100. Добавьте второй через {gateway} с метрикой {metric}, сохранив первый. Интерфейс {interface} включён, на нём назначен {address}/24; оба шлюза, если указаны, находятся в этой сети.';
$string['exercise_route_table'] = 'Добавить маршрут в таблицу';
$string['task_route_table'] = 'Добавьте {network}/24 через {gateway} в таблицу маршрутизации {table}. Интерфейс {interface} включён, на нём назначен {address}/24; оба шлюза, если указаны, находятся в этой сети.';
$string['exercise_route_blackhole'] = 'Отбрасывать трафик до сети';
$string['task_route_blackhole'] = 'Добавьте маршрут blackhole до {network}/24.';
$string['group_virtual'] = 'Виртуальные интерфейсы';
$string['exercise_virtual_dummy'] = 'Создать dummy-интерфейс';
$string['task_virtual_dummy'] = 'Создайте dummy-интерфейс с именем {dummy}.';
$string['exercise_virtual_veth'] = 'Создать пару veth';
$string['task_virtual_veth'] = 'Создайте пару veth с именами {vetha} и {vethb}.';
$string['exercise_virtual_vlan'] = 'Создать VLAN';
$string['task_virtual_vlan'] = 'Создайте VLAN {vlan} на {interface} с именем {vlanname}.';
$string['exercise_virtual_bridge'] = 'Создать bridge';
$string['task_virtual_bridge'] = 'Создайте мост с именем {bridge}.';
$string['exercise_virtual_master'] = 'Включить интерфейс в мост';
$string['task_virtual_master'] = 'Мост {bridge} уже существует. Включите {interface} в этот мост.';
$string['exercise_virtual_del'] = 'Удалить виртуальный интерфейс';
$string['task_virtual_del'] = 'Удалите существующий dummy-интерфейс {dummy}.';
$string['group_diagnostic'] = 'Диагностика';
$string['exercise_diag_address'] = 'Определить адрес интерфейса';
$string['task_diag_address'] = 'Дан вывод: {addroutput}
Введите IPv4-адрес интерфейса {interface} без префикса.';
$string['exercise_diag_prefix'] = 'Определить префикс интерфейса';
$string['task_diag_prefix'] = 'Дан вывод: {addroutput}
Введите длину префикса интерфейса {interface} числом без /.';
$string['exercise_diag_gateway'] = 'Определить шлюз по умолчанию';
$string['task_diag_gateway'] = 'Таблица маршрутизации:
{routes}
Введите адрес шлюза по умолчанию.';
$string['exercise_diag_longest'] = 'Выбрать маршрут по наиболее длинному префиксу';
$string['task_diag_longest'] = 'Таблица маршрутизации:
{routes}
Через какой шлюз пойдёт пакет к {target}? Введите IPv4-адрес шлюза.';
$string['exercise_diag_fix_link'] = 'Исправить выключенный интерфейс';
$string['task_diag_fix_link'] = 'Дан вывод: {linkoutput}
Одной командой переведите {interface} в административное состояние UP.';
$string['exercise_diag_fix_gateway'] = 'Исправить неверный шлюз';
$string['task_diag_fix_gateway'] = 'Интерфейс {interface} включён, адрес {address}/24. У маршрута {network}/24 неверный шлюз {oldgateway}. Замените его на {gateway}, сохранив остальные маршруты.';
$string['group_subnet'] = 'Подсети';
$string['exercise_network'] = 'Вычислить адрес сети';
$string['exercise'] = 'Задание (поиск по теме или названию)';
$string['chooseexercise'] = 'Выберите хотя бы одно задание. Количество вопросов должно быть не меньше количества выбранных видов заданий.';
$string['generatedintro'] = 'Выполните задание по приведённому ниже условию.';
$string['entercommand'] = 'Команда Linux ip';
$string['enterresponse'] = 'Введите ответ.';
$string['commandhelp'] = 'Введите одну команду ip. Можно сокращать объект и действие (например ip a a), переставлять аргументы, использовать sudo и -4/-6. dev, via и mtu пишите полностью. Не используйте операторы оболочки.';
$string['exercise_virtual_setup_vlan'] = 'Создать VLAN, назначить адрес и включить';
$string['task_virtual_setup_vlan'] = 'Тремя командами в указанном порядке: создайте VLAN {vlan} на {interface} с именем {vlanname}; назначьте ему {address}/24; включите его.';
$string['exercise_diag_fix_prefix'] = 'Исправить неверный префикс';
$string['commandshelp'] = 'Введите по одной команде ip на строку в указанном порядке. Допускаются поддерживаемые сокращения и перестановка аргументов. Не используйте операторы оболочки.';
$string['task_diag_fix_prefix'] = 'На {interface} назначены {address}/16 и {extra}/24. У первого адреса должен быть префикс /24. Двумя командами сначала удалите {address}/16, затем добавьте {address}/24. Сохраните {extra}/24.';
$string['type_addr_add'] = $string['exercise_addr_add'];
$string['type_addr_del'] = $string['exercise_addr_del'];
$string['type_addr_extra'] = $string['exercise_addr_extra'];
$string['type_link_up'] = $string['exercise_link_up'];
$string['type_link_down'] = $string['exercise_link_down'];
$string['type_link_mtu'] = $string['exercise_link_mtu'];
$string['type_route_default'] = $string['exercise_route_default'];
$string['type_route_network'] = $string['exercise_route_network'];
$string['type_route_host'] = $string['exercise_route_host'];
$string['type_route_del'] = $string['exercise_route_del'];
$string['type_route_replace'] = $string['exercise_route_replace'];
$string['type_route_metric'] = $string['exercise_route_metric'];
$string['type_route_table'] = $string['exercise_route_table'];
$string['type_route_blackhole'] = $string['exercise_route_blackhole'];
$string['type_virtual_dummy'] = $string['exercise_virtual_dummy'];
$string['type_virtual_veth'] = $string['exercise_virtual_veth'];
$string['type_virtual_vlan'] = $string['exercise_virtual_vlan'];
$string['type_virtual_bridge'] = $string['exercise_virtual_bridge'];
$string['type_virtual_master'] = $string['exercise_virtual_master'];
$string['type_virtual_setup_vlan'] = $string['exercise_virtual_setup_vlan'];
$string['type_virtual_del'] = $string['exercise_virtual_del'];
$string['type_diag_address'] = $string['exercise_diag_address'];
$string['type_diag_prefix'] = $string['exercise_diag_prefix'];
$string['type_diag_gateway'] = $string['exercise_diag_gateway'];
$string['type_diag_longest'] = $string['exercise_diag_longest'];
$string['type_diag_fix_prefix'] = $string['exercise_diag_fix_prefix'];
$string['type_diag_fix_link'] = $string['exercise_diag_fix_link'];
$string['type_diag_fix_gateway'] = $string['exercise_diag_fix_gateway'];

$string['type_addr_v6'] = $string['exercise_addr_v6'];
