# VERIFICATION-NATIVE-SUITES-001

Версия0.1,2026-09-07. Gate1 required.

## Простыми словами

Общая проверка должна запускать уже утверждённые native-тесты состава, шаблона,
оригинала и browser-клиента. Сейчас они проходят отдельно, но отсутствуют в
canonical runner. Этот slice меняет только delivery tooling.

## Public seam and actor

Release engineer вызывает `bash tools/verification/run.sh unit|db` как прежде.
Добавляется read-only `bash tools/verification/run.sh list unit|db`: stdout по
одной строке `php<TAB>relative/path` или `node<TAB>relative/path`, LF. Порядок:
лексикографический PHP, затем лексикографический Node; без дубликатов.
List не вызывает PHP/Node/DB, не выполняет tests, не пишет файлы. Unknown suite
или malformed list arguments дают ненулевой exit и SETUP_FAILURE.
OS access достаточно; новых product grants/audits/domain facts runner не создаёт.

## Membership

Прежняя classification `tests/InstallationProcess/*test.php` сохраняется буквально:
FMONITOR_TEST_DB/new mysqli в source определяет db; остальные unit. Никаких
прежних exclusion/skip правил не добавляется, protected E2E не редактируется.

Все `tests/AssignmentOrderComposition/*test.php` обнаруживаются автоматически.
Три exact in-memory files идут в unit:
- selection_command_outcomes_001_test.php
- selection_command_recovery_001_test.php
- selection_command_tracer_001_test.php

Остальные files этой family идут в db; discovery не зависит от наличия прямого
SQL в test entrypoint. На исходном9488814 это19 native PHP tests (всего22).
Все `tests/Verification/*_test.mjs` идут в unit через Node. Сейчас это
`original_upload_client_001_test.mjs`. Изменений package dependencies нет.
Оба PHP directories и Verification directory обязательны; отсутствующий каталог
даёт SETUP_FAILURE и ненулевой exit, не пустой success.

## Execution and errors

unit/db запускают тот же ordered набор, который выдал list. Перед файлом stdout
содержит `VERIFY <path>`. При ненулевом exit interpreter runner печатает
`REGRESSION_FAILURE: <path>`, продолжает оставшиеся files и завершает suite
ненулевым exit. Отсутствующий Node тоже failure, не skip. При нескольких failures
все провалившиеся пути остаются видимыми. При полном успехе exit0.
DB prerequisite и прежние characterization/e2e/lint/red semantics сохраняются.
`make verify` уже вызывает unit/db и наследует новые проверки без новых shortcuts.
Эта задача не разрешает публикацию, изменение QualityGraph, deployment, или
объявление literal VERIFY_OK, пока полная проверка фактически не завершилась.

## Independent examples and proof

Изолированный fixture tree содержит existing PHP unit u_test.php и DB d_test.php
(с marker FMONITOR_TEST_DB), три exact selection command filenames, новый
selection_native_extra_test.php, original_upload_client_001_test.mjs.
List unit =4 PHP +1 Node; list db =2 PHP. Все указаны один раз, PHP отсортированы.
Добавленный native filename обнаруживается автоматически.

Для проверки scheduler permitted изолированный tool harness исполняет копию
runner в этом синтетическом tree с trace-only PHP/Node executables в собственном
PATH. Они не подменяют production/native application и не касаются реальной DB.
Ожидаемые вызовы и exitcodes задаёт fixture, а не runner implementation. Fail
одного PHP и одного Node должен оставить оба пути в итоговых diagnostics; PHP
failure не предотвращает Node запуск. Отдельный реальный run всех22 PHP +Node
через canonical commands с синтетической MariaDB обязателен для GREEN/evidence.

Gate1→RED на отсутствующем list/discovery→independent Gate3→GREEN→independent Gate5.
Architecture/diff/lint и full make verify после integration, без ослабления tests.
