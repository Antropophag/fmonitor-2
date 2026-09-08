# Проверка собранного HTTP ownership изменения — 2026-09-07

Исходный рабочий стенд продолжает работать на source78182e1. Изменения этого
архитектурного пакета пока не установлены. Их цель — вернуть SQL и управление
административной сессией явным владельцам без изменения принятого интерфейса.

Queue/assets и user-session extraction прошли отдельные независимые review:
`RESTORE-PILOT-HTTP-QUEUE-ASSETS-2026-09-07.md` и
`RESTORE-PILOT-HTTP-ARCHITECTURE-OWNERSHIP-session.md` в reviews/code.
Root дополнительно повторил real GET objects RBAC admission и public SHLZ CSS
manifest tests после устранения промежуточного503 в общем рабочем дереве:
оба PASS/exit0. Это снимает указанное в раннем queue review ограничение общей
HTTP-цепочки. Stand и факты владельца при этих тестах не использовались.

Root отклонил разбиение `select` на части в HTML ради обхода SQL detector.
Renderer восстановлен читаемым и побайтно эквивалентным прежнему. Сам detector
получил отдельное исправление: PHP `$select` вне строк и публичные SHLZ Select
идентификаторы внутри строк больше не считаются SQL. Raw fingerprints и baseline
не изменены. RED public CLI tests:3 ложных сигнала; GREEN:10 focused и15 select
checks. Независимый reviewer дополнительно выполнил все43 architecture tests и
полный checker: PASS7 rules. Тесты сохраняют обнаружение SELECT/INSERT/UPDATE/DELETE
в той же строке и внутри того же литерала, что и HTML/component atoms.
Review: `reviews/code/ARCHITECTURE-SELECT-MARKUP-2026-09-07.md`.

Все18 builtin calls нового MariaDbObjectQueue квалифицированы; global-call
aggregate PASS. Baseline SHA256 остаётся
`9a67b19242bc1609d00c8a9e923246096b9730a89c988af6390ceb6541b5a6c8`.
Coordinator308→249, router289→282; новые owners меньше hotspot threshold.

Это не завершение OpenSpec change: `pilot_object_list_001_test.php` пока имеет
старое ожидание отсутствия текущей pagination copy («Показано»). Соответствующие
all-green задачи остаются открытыми, тест не ослаблен/не пропущен. Полный
`make verify`, production integration, remote publication и CI не объявляются
выполненными. Новые ручные замечания (имена авторов, фильтр стройконтроля и
открытие PDF-шаблона) выполняются отдельными параллельными исправлениями.

Дополнение при исправлении подписи ОТиЗ: detector также ошибочно принимал
буквальные HTML `<select>`/`</select>` за SQL. Добавлено узкое правило только для
quoted PHP detection view; native HTML RED воспроизведён, SQL в том же литерале
сохранён отрицательным тестом. Полный architecture test suite44/44 PASS;
два независимых supplemental review APPROVED. Неправильная старая подпись не
сохранялась в исходнике ради baseline, исходный HTML не обфусцировался.
