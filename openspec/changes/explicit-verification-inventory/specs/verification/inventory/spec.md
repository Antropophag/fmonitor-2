# VERIFICATION-INVENTORY-001

Простыми словами: состав проверок становится явным, ошибки регистрации видны сразу, а журнал показывает длительность каждого теста. Существующие проверки не исключаются.

## ADDED Requirements

### Requirement: Explicit complete inventory
Runner SHALL читать suites.tsv (group TAB runtime TAB path), а не определять зависимости по содержимому исходников. Допустимые группы unit/db/characterization/e2e; runtime php/node/python3. List SHALL выводить runtime TAB path в порядке каталога, без исполнения интерпретаторов и изменения файлов. Существующие 117 unit и 120 DB записей SHALL сохраняться в прежнем порядке; characterization и E2E также сохраняются. Новый тест этого среза регистрируется в characterization.

#### Scenario: Explicit assignment overrides source text
- **WHEN** файл с текстом FMONITOR_TEST_DB зарегистрирован как unit, а файл без этого текста как db
- **THEN** list и выполнение соответствуют каталогу независимо от наличия rg.

#### Scenario: Invalid or incomplete catalog
- **WHEN** каталог отсутствует, запись некорректна, путь отсутствует, пара group/path повторяется или обнаружен незарегистрированный test.php в InstallationProcess/AssignmentOrderComposition либо _test.mjs в Verification
- **THEN** команда завершается ненулевым кодом с SETUP_FAILURE до запуска тестов; отсутствующий обязательный каталог тоже ошибка.

#### Scenario: Empty inventory
- **WHEN** обязательные каталоги существуют, но не содержат тестов и каталог пуст
- **THEN** list unit/db пуст, команды не исполняют тестов, Bash 3.2 работает.

### Requirement: Observable execution without changing outcomes
Runner SHALL сохранять VERIFY строки, ошибки и порядок; после каждого top-level теста выводить VERIFY_TIMING suite=<group> runtime=<runtime> file=<path> seconds=<неотрицательное целое> exit=<код>. Unit и DB SHALL выполнить оставшиеся записи после ошибки и вернуть отказ; list не содержит timing. Прежний DB preflight остаётся обязательным. Нет новых сетевых вызовов, секретов, бизнес-записей или изменения авторизации; конкурентные запуски не пишут общий timing-файл.

#### Scenario: Failure timing and continuation
- **WHEN** PHP тест завершается кодом 7, следующий Node тест успешен
- **THEN** обе записи исполняются и имеют timing с exit=7 и exit=0, итог ненулевой.

#### Scenario: Successful duration
- **WHEN** тест завершается успешно
- **THEN** записаны его путь, группа, интерпретатор, неотрицательная длительность и exit=0.
