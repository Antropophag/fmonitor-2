# PRODUCTION-MIGRATION-RUNNER-001 — применить утверждённую production-схему одной deployment-командой

- Статус: `APPROVED`
- Версия: `0.5`
- Дата: `2026-08-28`
- Актор: оператор развёртывания FMonitor 2.0
- Публичный seam: отдельный процесс `php bin/fmonitor2-migrate.php`

## 1. Цель и граница среза

Дать оператору одну запускаемую команду, которая подключается к явно настроенной MariaDB и строго по порядку применяет все четыре уже утверждённые additive migrations:

1. `MIGRATION-PROCESS-001` — шесть process-таблиц;
2. `WORKFORCE-CATALOG-001` — кадровый каталог;
3. `PROCESS-USER-DIRECTORY-001` — таблица process capabilities;
4. `PROCESS-COMMAND-AUTHORIZATION-001` — расширение capability contract для prepare/confirm/open.

Runner не определяет новую схему и не дублирует DDL: нормативные catalog contracts, compatibility checks, безопасный повтор и partial recovery наследуются из четырёх перечисленных спецификаций. Он оркестрирует их public deployment seams и делает результат пригодным для автоматического развёртывания.

Это наименьший первый срез пути к запускаемому пилоту. Создание монтажных дел для явно выбранных legacy-объектов, заполнение workforce/capabilities, HTTP-аутентификация и operator UI имеют другие права, входы и аудит, поэтому не входят в эту deployment-команду и получают отдельные SSD + TDD-срезы.

## 2. Запуск и конфигурация

Команда не принимает аргументы командной строки:

```text
php bin/fmonitor2-migrate.php
```

Единственный источник конфигурации — environment процесса:

| Переменная | Контракт |
|---|---|
| `FMONITOR_DB_HOST` | непустой host/socket MariaDB |
| `FMONITOR_DB_PORT` | десятичное целое `1..65535` |
| `FMONITOR_DB_NAME` | непустое имя существующей database |
| `FMONITOR_DB_USER` | непустой DB principal |
| `FMONITOR_DB_PASSWORD` | присутствует; пустое значение допустимо только как явная конфигурация тестовой DB |
| `FMONITOR_PROCESS_TABLE_PREFIX` | присутствует; пустая строка допустима, иначе утверждённый prefix contract `0..32`, `/^[A-Za-z0-9_]*$/` |

Значения не trim-ятся и не получают defaults. Отсутствующая переменная отличается от присутствующей пустой. Runner не читает `.env`, legacy application config, URL query, stdin или argv и не печатает конфигурацию.

До первого соединения runner проверяет присутствие и формат всех переменных, кроме содержимого password. После соединения обязательно подтверждает charset `utf8mb4` до schema inspection.

## 3. Порядок и остановка

На одном соединении runner вызывает утверждённые migration seams строго `v1 → v2 → v3 → v4`. Следующая версия вызывается только если предыдущая вернула совместимый успех: как применённая либо как безопасный no-op.

Если версия возвращает `SCHEMA_MIGRATION_CONFLICT`, runner немедленно останавливается и не вызывает более поздние версии. Ранее успешно завершённые DDL не откатываются и не маскируются: MariaDB DDL не объявляется транзакционным. После исправления конфликтующей таблицы повтор той же команды проверяет уже совместимые ранние версии как no-op и продолжает с остановленной версии.

Runner не создаёт собственную schema-version table, не изменяет legacy-таблицы и не вставляет product data.

## 4. Нормативный машинный вывод

Runner записывает в `stdout` ровно одну UTF-8 JSON-строку с завершающим `\n`. При успехе:

```json
{"ok":true,"schemaVersion":4,"appliedVersions":[1,2,3,4]}
```

`appliedVersions` содержит в возрастающем порядке только версии, которые в этом запуске реально изменили schema. Совместимый повтор возвращает:

```json
{"ok":true,"schemaVersion":4,"appliedVersions":[]}
```

Успех завершается exit code `0`, ничего не пишет в `stderr` и означает: каждая версия v1–v4 в этом запуске подтвердила совместимость конечной схемы. `schemaVersion = 4` не берётся из отдельной mutable metadata-строки.

Порядок JSON keys и отсутствие дополнительных keys являются частью executable CLI contract.

## 5. Исполняемый пример A — чистая database

В чистой MariaDB database присутствуют все шесть обязательных environment values, prefix:

```text
FMONITOR_PROCESS_TABLE_PREFIX=pilot_
```

До запуска в database нет таблиц с этим prefix. Один вызов создаёт независимо утверждённый конечный catalog:

- `pilot_fm2_installation_cases`;
- `pilot_fm2_assignment_orders`;
- `pilot_fm2_order_installers`;
- `pilot_fm2_order_artifacts`;
- `pilot_fm2_process_tasks`;
- `pilot_fm2_process_events`;
- `pilot_fm2_workforce_catalog`;
- `pilot_fm2_process_user_capabilities` с capability enum v4.

Точный stdout, stderr и exit code:

```text
stdout: {"ok":true,"schemaVersion":4,"appliedVersions":[1,2,3,4]}\n
stderr: <empty>
exit: 0
```

Тест определяет ожидаемый catalog из канонического expected-value manifest раздела 5.1, а не из SQL production classes, production-created snapshot или stdout runner.

### 5.1. Канонический expected-value manifest конечного catalog

Нормативное значение конечного fingerprint — композиция **литеральных строк** следующих уже утверждённых таблиц спецификаций:

| Часть fingerprint | Канонический источник ожидаемых литералов |
|---|---|
| шесть v1 tables: порядок колонок, `COLUMN_TYPE`, nullability, `AUTO_INCREMENT`, character/non-character contract | все шесть таблиц `specs/MIGRATION-PROCESS-001.md`, раздел 3 |
| v1 primary/unique/non-unique keys и порядок их колонок | маркированный список ключей `MIGRATION-PROCESS-001`, раздел 3 |
| v1 foreign keys и `DELETE_RULE = RESTRICT` | список шести связей и следующий абзац `MIGRATION-PROCESS-001`, раздел 3 |
| v1 checks | только implicit MariaDB `json_valid(payload_json)` у JSON-колонки events; иных CHECK нет |
| `fm2_workforce_catalog`: все 8 column tuples, PK, index, employment CHECK, отсутствие FK | `WORKFORCE-CATALOG-001`, раздел 2 |
| `fm2_process_user_capabilities`: все 3 column tuples, PK, index, отсутствие FK | `PROCESS-USER-DIRECTORY-001`, раздел 2 |
| конечные v4 CHECK | `PROCESS-COMMAND-AUTHORIZATION-001`, раздел 2: exact capability enum из четырёх строк и сохранённый exact engineer-position CHECK |

Для Gate 2 «column tuple» означает независимо записанный literal:

```text
(table basename, ordinal position, column name, MariaDB COLUMN_TYPE,
 nullability YES|NO, EXTRA, CHARACTER_SET_NAME|null)
```

«Key tuple» означает `(table basename, PRIMARY|UNIQUE|INDEX, ordered column names)`. Сюда входят явно нормативные indexes и supporting indexes, которые MariaDB создаёт для утверждённых foreign keys; имя такого index не сравнивается, состав и отсутствие extra indexes сравниваются. «Foreign-key tuple» означает `(child table basename, ordered child columns, parent table basename, ordered parent columns, DELETE_RULE=RESTRICT)`.

«Check tuple» сравнивается после ограниченной тестовой нормализации: убрать identifier backticks и whitespace, привести SQL keywords к lower-case; string literals не менять и не сортировать. Допускается снять только пары скобок, которые охватывают **всё** выражение, сохраняя его parse tree. Глобальное удаление внутренних скобок запрещено.

Для engineer-position CHECK допустима ровно одна логическая форма. MariaDB может сериализовать правую часть `B AND C` со скобками либо без них; всё выражение также может иметь необязательную внешнюю обёртку. Поэтому допустимы ровно следующие четыре текстовые структуры:

```text
A OR B AND C
A OR (B AND C)
(A OR B AND C)
(A OR (B AND C))

A = capability <> 'construction_control_engineer'
B = position_snapshot IS NOT NULL
C = trim(position_snapshot) <> ''
```

С учётом SQL precedence (`AND` сильнее `OR`) и после снятия только необязательных whole-expression parentheses все четыре сериализации дают exact tree `OR(A, AND(B, C))`. Не являются эквивалентными и обязаны сделать Gate 2 красным: `(A OR B) AND C`, `A OR B OR C`, `AND(A, OR(B, C))`, отсутствие любого из A/B/C, замена литералов/операторов, дополнительная ветвь или extra CHECK. В частности, нормализатор не может получить green простым удалением всех `(` и `)` либо сравнением набора токенов.

Имена constraint/index, автоматически выбранные MariaDB, не являются expected value, кроме нормативного имени v4 capability CHECK; семантика, состав и отсутствие extra constraints являются expected value.

### 5.2. Единая exact grammar completed-v4 compatibility

После успешного v4 следующий runner сначала проходит seam v3. Поэтому v3 compatibility boundary обязана признать exact completed-v4 table совместимым no-op, но не расширяет множество допустимых схем. V3-recognition и собственный v4 repeat используют одну и ту же literal-aware классификацию двух CHECK; отдельная упрощённая string normalization на границе v3 запрещена.

Capability CHECK допускает только grammar:

```text
[WHOLE_OPEN] capability IN (
  'assignment_order.prepare',
  'assignment_order.confirm_registration',
  'installation.open',
  'construction_control_engineer'
) [WHOLE_CLOSE]
```

Здесь identifier backticks, SQL whitespace, case SQL keywords и ноль либо одна сбалансированная пара `WHOLE_OPEN`/`WHOLE_CLOSE`, охватывающая всё выражение, являются presentation differences. Четыре string literals должны присутствовать ровно по одному; их порядок внутри `IN` семантически незначим. Байты внутри quoted literals, включая case, whitespace, `_` и `.`, сравниваются точно и никогда не lower-case/trim/whitespace-collapse. Идентификатор обязан быть только `capability`; иные operands/operators/functions, duplicate/missing/extra literal или дополнительная ветвь запрещены.

Engineer-position CHECK классифицируется тем же literal-aware parser по exact v0.4 contract раздела 5.1: допустимы только четыре presentation structures, а parse tree всегда `OR(A, AND(B, C))`. Литералы `'construction_control_engineer'` и `''` сохраняются дословно. Whole-expression parentheses снимаются только после проверки их сбалансированности и того, что внешняя пара действительно охватывает всё выражение; внутренние parentheses не удаляются глобально.

Completed-v4 state совместим только если одновременно:

1. найден ровно один capability CHECK этой grammar с нормативным catalog name `ck_fm2_process_user_capability`;
2. найден ровно один engineer-position CHECK exact v0.4 tree; его безопасное MariaDB-generated/catalog name может отличаться;
3. иных CHECK нет;
4. columns, charset, keys/indexes и отсутствие foreign keys точно соответствуют v3/v4 manifest раздела 5.1.

Это правило действует одинаково, когда completed-v4 table инспектирует v3 seam в начале повторного runner и когда её инспектирует v4 seam. Несовпадение на первой границе возвращает runner conflict именно версии `3`; v4 не вызывается.

Gate 2 sensitivity обязана включать независимые CLI fixtures:

- valid completed-v4 table с engineer CHECK `(A OR (B AND C))` даёт repeat success `appliedVersions = []`;
- capability literal `'assignment_order. prepare'`, case-altered `'Installation.open'` и engineer literal `'Construction_control_engineer'` — три независимых near-match schema — каждый даёт exact `SCHEMA_MIGRATION_CONFLICT`, `schemaVersion = 3`, exit `2`, не меняет schema/rows и не доходит до v4;
- перестановка четырёх exact capability literals остаётся совместимой, доказывая semantic `IN`, а не сравнение одной production-сериализации.

Expected literals и parse trees берутся только из этого раздела; production normalization/output не является oracle.

Gate 2 обязан **дословно транскрибировать** эти tuples из перечисленных нормативных разделов в test-owned constant/fixture до запуска runner. Запрещено заполнять expected manifest чтением `information_schema` после production migration, `SHOW CREATE TABLE`, production PHP constants/methods или DDL-файла. Observed `information_schema` rows нормализуются в тот же tuple-format и сравниваются с literal manifest. Before/after fingerprint примера B остаётся отдельной проверкой сохранности и не заменяет это сравнение.

Тем самым ожидаемый конечный catalog включает не только восемь имён/column names, но и типы, nullability, extras, character contracts, все indexes, все foreign keys/delete rules, все документированные CHECK и отсутствие любых дополнительных schema objects в перечисленных категориях.

## 6. Исполняемый пример B — безопасный повтор и сохранение данных

После примера A test вставляет независимые sentinel rows:

- workforce installer `1042` с ФИО `Иванов Иван Иванович`;
- capability `(18, assignment_order.prepare)`;
- пустое монтажное дело legacy object `4512`, `process_state = needs_assignment_order`, `lock_version = 1`.

Второй отдельный процесс с той же конфигурацией возвращает:

```text
stdout: {"ok":true,"schemaVersion":4,"appliedVersions":[]}\n
stderr: <empty>
exit: 0
```

Полный catalog fingerprint остаётся совместимым с v1–v4, а три sentinel facts сохраняются без изменения. Runner не читает и не преобразует их как import data.

## 7. Исполняемый пример C — конфликт и восстановление partial application

В независимой чистой database до запуска существует несовместимая таблица:

```text
pilot_fm2_process_user_capabilities (unexpected_column INT NOT NULL)
```

Первый запуск успевает совместимо применить v1 и v2, затем v3 возвращает conflict. Точный результат:

```text
stdout: {"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":3}\n
stderr: <empty>
exit: 2
```

После отказа:

- семь v1/v2-owned таблиц, созданных до конфликта, остаются на месте и совместимы;
- конфликтующая таблица побайтно/структурно не меняется;
- v4 не выполняется;
- legacy tables и любые unrelated tables не меняются.

После того как test удаляет только свою конфликтующую fixture-таблицу, повтор runner возвращает:

```text
stdout: {"ok":true,"schemaVersion":4,"appliedVersions":[3,4]}\n
stderr: <empty>
exit: 0
```

Ранние версии проходят как no-op; данные в их таблицах сохраняются.

## 8. Конфигурационные и инфраструктурные отказы

Все отказы также дают ровно одну JSON-строку в `stdout`, пустой `stderr` и не включают exception class/message.

### Некорректная конфигурация

Отсутствующая обязательная переменная, пустые host/name/user, invalid port или invalid prefix:

```text
{"ok":false,"reason":"CONFIGURATION_INVALID"}\n
exit: 64
```

Отказ происходит до connection attempt и schema query. Ответ не перечисляет имена отсутствующих полей: deployment environment может сопоставить единый reason со своей конфигурацией, не раскрывая её через общий лог.

### MariaDB недоступна

Ошибка DNS/socket/authentication/connect либо неподтверждённый `utf8mb4`:

```text
{"ok":false,"reason":"DATABASE_UNAVAILABLE"}\n
exit: 69
```

DDL/DML runner не выполняет.

Отдельный deterministic Gate 2 case проверяет именно успешный TCP/MySQL handshake с последующим отказом подтверждения charset. Test поднимает локальный одноразовый MySQL-wire fault proxy на loopback: proxy прозрачно пересылает handshake и authentication к своей изолированной MariaDB fixture, затем на первый client command `SET NAMES utf8mb4` либо эквивалентную charset-negotiation command возвращает protocol `ERR` и записывает все последующие client commands. Это внешний database-boundary fault, а не PHP dependency injection и не test-only ветка runner.

Для этого case connection/authentication завершаются до fault; captured proxy transcript содержит попытку установить exact `utf8mb4` и не содержит `information_schema`, `CREATE`, `ALTER`, `INSERT`, `UPDATE` или `DELETE` после ответа `ERR`. Runner возвращает ровно:

```text
stdout: {"ok":false,"reason":"DATABASE_UNAVAILABLE"}\n
stderr: <empty>
exit: 69
```

Proxy credentials, endpoint и injected driver error literal отсутствуют в output. Fixture может обслуживать только один runner connection и закрываться после результата, но handshake/authentication действительно проходят через real isolated MariaDB server. Если конкретный mysqli выполняет charset negotiation не SQL `COM_QUERY`, proxy перехватывает соответствующую wire command; нормативно важны запрос exact `utf8mb4`, отказ до schema inspection и caller-result выше.

### Непредусмотренная ошибка migration

Driver/DDL error, не представленный утверждённым `SCHEMA_MIGRATION_CONFLICT`:

```text
{"ok":false,"reason":"MIGRATION_FAILED"}\n
exit: 70
```

Runner прекращает последовательность. Уже подтверждённый MariaDB DDL может сохраниться; повтор обязан снова пройти compatibility checks. Этот результат не объявляет rollback и не предлагает автоматический retry.

## 9. Security, authorization и audit

Runner является offline deployment seam и должен запускаться DB principal, которому оператор отдельно выдал минимальные schema privileges для утверждённых таблиц. HTTP/session actor, process capabilities и legacy roles к нему неприменимы; web request не может вызвать runner.

Ни stdout, ни stderr при любом результате не содержат host, port, database/user/password, prefix, table names, SQL, artifact root, filesystem paths, driver details или stack trace. Значения environment не попадают в exception messages, argv и process title по инициативе runner.

Schema deployment не является предметным действием сотрудника ФКР: runner не создаёт `fm2_process_events`, security-audit events, installation cases, workforce rows или capability rows. Операционный аудит запуска и exit code остаётся обязанностью deployment platform.

## 10. Публичный seam теста и независимость

Gate 2 запускает настоящий отдельный PHP process через CLI seam с изолированной real MariaDB database/prefix. Test наблюдает exit code, exact stdout/stderr и MariaDB catalog с новым независимым connection. Charset-failure case использует внешний loopback wire proxy раздела 8 и не изменяет CLI/config seam.

Тест не `require`-ит runner как библиотеку, не вызывает migration classes вместо CLI, не парсит production SQL для ожидаемого catalog и не вычисляет ожидаемые JSON из фактических migration results. Secrets fixtures намеренно отличаются от prefix/table values; чувствительность redaction проверяется отсутствием каждого literal во всём captured output.

## 11. Не входит в срез и обязательный порядок продолжения

- advisory lock/concurrent запуск двух deployment runners;
- автоматический rollback уже завершённого DDL;
- создание/import `fm2_installation_cases` для объектов пилота;
- импорт workforce и назначение process capabilities;
- HTTP/controller/authentication/session/UI;
- автоматический запуск migrations из web bootstrap;
- изменение существующих migration catalog contracts.

Следующие независимые вертикальные срезы:

1. `PILOT-CASE-IMPORT-001`: offline-команда явного списка legacy object IDs; eligibility/read-only checks, идемпотентное создание только пустых `needs_assignment_order` дел и операционный отчёт без переноса legacy `installator*`.
2. `PILOT-HTTP-AUTH-001`: доверенная production identity boundary → active legacy user ID, deny-by-default и anti-spoof contract без process mutation.
3. `PILOT-OBJECT-CARD-001`: authenticated GET одного явно импортированного объекта через read-model/public process seam.
4. Отдельные POST-срезы prepare, registration confirmation, artifact download и opening с CSRF, Post/Redirect/Get и точным mapping предметных отказов.

## 12. Решения и доказательства

- `docs/development-process.md`: один public seam и один vertical behavior slice за red-green cycle.
- `specs/MIGRATION-PROCESS-001.md`: v1 catalog, compatibility, idempotency и partial recovery.
- `specs/WORKFORCE-CATALOG-001.md`: v2 catalog.
- `specs/PROCESS-USER-DIRECTORY-001.md`: v3 capability table.
- `specs/PROCESS-COMMAND-AUTHORIZATION-001.md`: v4 forward migration и exact command capabilities.
- `specs/PRODUCTION-COMPOSITION-001.md`: production factory не применяет migrations автоматически; deployment выполняет v1–v4 заранее.

## 13. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил автономно принимать лучшие решения до работающего пилота и соблюдать обязательный SSD + TDD workflow. Широкая задача декомпозирована; версия 0.5 утверждает только первый наблюдаемый deployment seam без расширения прав на import или HTTP, добавляет независимый literal catalog manifest contract, наблюдаемый database-boundary отказ подтверждения `utf8mb4`, exact structural equivalence engineer-position CHECK и единую literal-aware completed-v4 grammar на обеих compatibility boundaries.

Gate 2 разрешён для версии `0.5`; прежний test требует обновления и нового независимого Gate 3 review.
