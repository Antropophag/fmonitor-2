# PILOT-CASE-IMPORT-001 — явно включить выбранные legacy-объекты в пилот

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-28`
- Актор: уполномоченный оператор развёртывания пилота
- Публичный seam: отдельный процесс `php bin/fmonitor2-import-cases.php --object-id=<id> [...]`

## 1. Цель и граница

Оператор явно выбирает уже загруженные legacy-объекты монтажа для пилота. Команда проверяет каждый выбранный ID по утверждённым доступным legacy-фактам и атомарно создаёт только пустую process-запись `fm2_installation_cases`:

```text
process_state = needs_assignment_order
actual_start_date = null
opened_at = null
opened_by_user_id = null
lock_version = 1
```

Импорт не переносит legacy `installator*`, инженера, даты открытия, чек-листы, документы, задачи или события и не объявляет legacy-факты доказанными назначениями. Следующее пользовательское действие выполняется только существующим `InstallationProcess.prepareAssignmentOrder(...)`.

Команда принимает только явно перечисленные IDs. Запрос «импортировать все», автоматический scan `fm_maintable`, фильтр диапазона/даты и web-вызов отсутствуют.

## 2. Предусловия deployment

- `PRODUCTION-MIGRATION-RUNNER-001` успешно подтвердил schema v4;
- process и legacy tables находятся в одной явно настроенной MariaDB database, но могут иметь разные допустимые prefixes;
- DB principal имеет `SELECT` на необходимые legacy/process catalog rows и `SELECT, INSERT` на `fm2_installation_cases`; `UPDATE`/`DELETE` legacy или process facts ему не требуются;
- оператор до запуска сверил, что выбранные объекты относятся к ещё не открытым работам пилота.

Последний пункт является явной операторской аттестацией отбора, а не историческим фактом, который importer способен восстановить. Утверждённый production mapping `LEGACY-OBJECT-SNAPSHOT-001` трактует `workdatestart` как плановую дату, тогда как discovery-документы отмечают legacy-неоднозначность фактического открытия. Срез не фабрикует `opened_at` из неоднозначных полей. Он fail closed по всем доступным утверждённым противопоказаниям раздела 5; отдельный reconciliation старых открытых объектов не входит в пилотный bootstrap.

## 3. CLI input

```text
php bin/fmonitor2-import-cases.php \
  --object-id=4512 \
  --object-id=4513
```

- требуется от `1` до `100` occurrences `--object-id=`;
- value — canonical decimal positive integer `1..9223372036854775807`, regex `/^[1-9][0-9]*$/`, без sign, whitespace и leading zero;
- IDs уникальны; duplicate является invalid input, а не скрытым deduplication;
- порядок IDs в output равен порядку argv;
- любой иной argument, positional value или более 100 IDs отклоняется;
- argv не принимает DB credentials, SQL, table names, actor ID, timestamps или eligibility override.

`stdin` не является command channel: importer никогда его не читает, не ждёт EOF, не валидирует и не отражает его содержимое. Пустой, непустой либо остающийся открытым stdin не меняет parsing, DB effects, output или время завершения команды. Все управляющие входы находятся только в argv/env; передавать credentials или IDs через stdin бессмысленно и не поддерживается.

ID объекта не считается секретом, но output не раскрывает его legacy-реквизиты.

## 4. Environment и соединение

Обязательны и не получают defaults:

| Переменная | Контракт |
|---|---|
| `FMONITOR_DB_HOST` | непустая строка |
| `FMONITOR_DB_PORT` | canonical decimal `1..65535` |
| `FMONITOR_DB_NAME` | непустая строка |
| `FMONITOR_DB_USER` | непустая строка |
| `FMONITOR_DB_PASSWORD` | обязана присутствовать; explicit empty допустим |
| `FMONITOR_PROCESS_TABLE_PREFIX` | обязана присутствовать; empty допустим; иначе `0..32`, `/^[A-Za-z0-9_]*$/` |
| `FMONITOR_LEGACY_TABLE_PREFIX` | тот же prefix contract; explicit empty допустим |

Значения не trim-ятся. Команда не читает `.env`, legacy config или environment времени. До schema query подтверждается connection charset `utf8mb4`. Config/input failure происходит до connection attempt.

## 5. Exact eligibility и mapping

Importer одним параметризованным lookup выбранных exact IDs читает только:

```text
id, ordadr_address, entrance, regnumber, workdatestart,
workdateendadjusted, plan_finish_date, workdatefinish, ptoactdate
```

Нормализация whitespace/date/zero-date и fallback `workdateendadjusted → plan_finish_date` строго наследуются из `LEGACY-OBJECT-SNAPSHOT-001`. `workdatefinish` и `ptoactdate` используют тот же optional-date/zero contract. Для нового дела ID допустим, только если одновременно:

1. exact legacy row существует;
2. `address`, `entrance`, `objectRegistrationNumber`, `plannedStartDate`, `plannedFinishDate` непусты;
3. `plannedStartDate >= 2026-10-01` как календарная дата;
4. `ptoActDate = null`;
5. `workdatefinish = null`;
6. process case с этим `legacy_installation_object_id` ещё не существует.

Условие 6 отличает новый import от идемпотентного повторения: уже существующее дело не перечитывает legacy eligibility и классифицируется `alreadyPresent`, независимо от его последующего process state. Bootstrap никогда не исправляет и не возвращает дело к `needs_assignment_order`.

Rejected reason codes имеют стабильный приоритет и порядок:

```text
LEGACY_OBJECT_NOT_FOUND
LEGACY_OBJECT_REQUIRED_DATA_MISSING
PILOT_PLANNED_START_BEFORE_CUTOFF
ORDER_HAS_PTO_ACT
LEGACY_INSTALLATION_ALREADY_COMPLETED
```

Для существующего legacy row собираются все применимые причины в этом порядке. Missing required fields не печатаются: детальная коррекция данных остаётся в legacy source; process command позднее использует утверждённый предметный field-level отказ.

Нераспознаваемая непустая дата, отсутствующая required legacy column или incompatible target schema — техническая schema/integration failure, не ложный eligibility reason.

## 6. Атомарный successful example

Fixture содержит:

- `4512`: exact valid legacy example `LEGACY-OBJECT-SNAPSHOT-001 A`, кроме `workdatefinish = null`;
- `4513`: valid literals `Москва, ул. Вторая, д. 7`, entrance `1`, regnumber `77-000124`, planned start `2026-10-01`, adjusted finish `2026-11-30`, zero PTO/completion;
- process cases для обоих отсутствуют.

CLI запускается с фиксированным system interval:

```text
t_before = 2026-08-28T18:00:00+00:00
t_after  = 2026-08-28T18:00:02+00:00
```

Точный результат:

```text
stdout: {"ok":true,"selected":[4512,4513],"imported":[4512,4513],"alreadyPresent":[]}\n
stderr: <empty>
exit: 0
```

Новое соединение видит ровно две строки с IDs, assigned MariaDB surrogate `id`, exact state/null fields/revision раздела 1. Для каждой строки `created_at = updated_at`, значение является RFC3339 seconds с explicit `+00:00` и instant находится включительно между соответствующими externally captured `t_before/t_after`. Обе строки одного запуска имеют один exact timestamp. Тест не ожидает конкретный surrogate ID/auto-increment.

Ни одна иная process table не получает строк. Все legacy rows, включая `installator*`/`responsstroicontrol`, побайтно неизменны.

## 7. Идемпотентность и progressed case

После примера раздела 6 повтор exact команды возвращает:

```text
{"ok":true,"selected":[4512,4513],"imported":[],"alreadyPresent":[4512,4513]}\n
exit: 0
```

Все process/legacy tables, timestamps, revision и auto-increment остаются неизменны. Если между запусками дело `4512` штатной process-командой стало `assignment_order_prepared` или `working`, importer всё равно возвращает его в `alreadyPresent` и не читает/не применяет к нему текущие legacy eligibility facts.

## 8. Rejection all-or-nothing

Независимый запуск выбирает `[4512, 4600, 4601]`: `4512` допустим; `4600` отсутствует; `4601` имеет planned start `2026-09-30`, blank entrance, nonzero PTO и nonzero completion. Результат:

```json
{"ok":false,"reason":"PILOT_CASES_NOT_ELIGIBLE","rejected":[{"installationObjectId":4600,"reasonCodes":["LEGACY_OBJECT_NOT_FOUND"]},{"installationObjectId":4601,"reasonCodes":["LEGACY_OBJECT_REQUIRED_DATA_MISSING","PILOT_PLANNED_START_BEFORE_CUTOFF","ORDER_HAS_PTO_ACT","LEGACY_INSTALLATION_ALREADY_COMPLETED"]}]}
```

Exit `2`, empty stderr. `4512` не создаётся: eligibility всего batch проверяется до первого INSERT, transaction откатывает весь batch при любом rejected ID. Existing `alreadyPresent` IDs не являются rejection и могут соседствовать с новыми valid IDs.

## 9. Transaction, repeat и concurrency

Importer выполняет один transaction на batch:

1. блокирует/читает target case keys и выбранные legacy rows согласованно до решения;
2. классифицирует `alreadyPresent` и проверяет eligibility всех новых IDs;
3. при любом rejection завершает без INSERT;
4. вставляет все valid new cases с одним timestamp;
5. подтверждает commit и только затем печатает success.

Unique `(legacy_installation_object_id)` остаётся последней защитой. Два одновременно запущенных процесса с одним valid ID оба завершаются `0`; после завершения существует ровно одна пустая case row. Ровно один result содержит ID в `imported`, другой — в `alreadyPresent`; ни один не возвращает generic failure/duplicate и timestamps сохранённой строки не переписываются.

Deadlock/lock timeout может быть безопасно повторён внутри команды ограниченно, только после подтверждённого rollback и повторного чтения всего batch. Неизвестный outcome `COMMIT` не преобразуется в success или retry: команда перечитывает exact IDs новым connection; если все ожидаемые новые rows существуют exact empty shape с timestamp текущей операции, возвращает success, иначе `IMPORT_OUTCOME_UNKNOWN`, exit `75`. Для correlation timestamp операции генерируется один раз; отдельная operation table этим срезом не создаётся.

## 10. Authorization и audit

Это offline deployment/import seam, не команда сотрудника ФКР. Авторизация обеспечивается двумя обязательными основаниями одновременно:

- OS/deployment policy разрешает запуск файла только operator identity;
- отдельный least-privilege MariaDB principal имеет права раздела 2.

Process capabilities не дают право запуска importer, а importer не принимает actor ID и не имперсонирует пользователя. Web server principal не получает execute/DB INSERT authority для этого CLI.

Создание пустой технической case row не является утверждением распоряжения, назначения или открытия, поэтому `fm2_process_events`, security audit и task rows не создаются. CLI success result и deployment platform audit являются операционным свидетельством выбора IDs. Полноценный append-only migration event потребовал бы actor identity/schema contract и остаётся отдельным срезом; его отсутствие нельзя заменять ложным user event.

## 11. Failure/output/redaction contract

Каждый результат — ровно одна UTF-8 JSON line в stdout с указанным key order, trailing `\n`, empty stderr:

| Exit | JSON |
|---:|---|
| `0` | success sections 6/7 |
| `2` | eligibility rejection section 8 |
| `64` | `{"ok":false,"reason":"CONFIGURATION_INVALID"}` для env или CLI grammar |
| `69` | `{"ok":false,"reason":"DATABASE_UNAVAILABLE"}` для connect/charset failure |
| `70` | `{"ok":false,"reason":"IMPORT_FAILED"}` для confirmed rollback после unexpected query/transaction failure |
| `75` | `{"ok":false,"reason":"IMPORT_OUTCOME_UNKNOWN"}` |
| `78` | `{"ok":false,"reason":"SCHEMA_UNAVAILABLE"}` для missing/incompatible required process/legacy schema |

До mutation importer подтверждает exact compatible `fm2_installation_cases` contract v1 и наличие/читаемость только требуемых legacy columns. Полный legacy DDL не объявлен production-контрактом; importer не угадывает типы остальных колонок. Он не применяет migrations автоматически.

Output никогда не содержит DB/environment values, prefixes/table names, SQL, legacy реквизиты, filesystem paths, driver/exception details, stack trace или rejected raw values. Eligibility output содержит только выбранные numeric IDs и stable reason codes. Schema/DB failures не перечисляют columns/tables. Invalid config/input не делает DB attempt.

## 12. Публичный Gate 2 seam и независимость

Gate 2 запускает отдельные PHP CLI processes против real isolated MariaDB database с разными process/legacy prefixes и наблюдает rows новым connection. Fixture expected values заданы литералами разделов 6–8; test не вызывает importer class/private method, production date normalizer или `MariaDbLegacyInstallationObject` как oracle.

Обязательная чувствительность:

- argv grammar: no IDs, zero/negative/leading-zero/overflow/duplicate/101 IDs/unknown arg;
- stdin boundary: непустые немедленные и delayed bytes игнорируются; процесс не ждёт EOF открытого stdin и даёт тот же exact result, что без этих bytes;
- все env absence/empty/port/prefix cases, включая одновременно отсутствующие password и оба prefixes;
- required fields, exact cutoff boundary `2026-10-01`, day-before, PTO/completion zero variants;
- mixed batch all-or-nothing, repeat after progress, two-process race;
- least-privilege success, schema missing/incompatible, confirmed rollback and commit-unknown reconciliation;
- exact stdout/stderr/exit/newline/key order and absence of distinct secret/raw-value literals.

## 13. Не входит в срез

- массовый discovery/auto-selection пилотных объектов;
- восстановление исторического факта открытия из неоднозначных legacy полей;
- перенос legacy исполнителей, инженера, checklist/history/documents;
- создание tasks/events или изменение legacy projection;
- workforce/capability population;
- HTTP/session/UI и пользовательская команда импорта;
- удаление/unimport дела или destructive down;
- изменение process state существующего дела.

## 14. Решения и доказательства

- `PRODUCT.md`, `docs/fmonitor-2-pilot-spec.md`: explicit pilot boundary — ещё не открытые объекты с плановым началом с `2026-10-01`.
- `docs/fmonitor-2-pilot-data-model.md`, раздел 7: создать case rows без переноса четырёх legacy slots как доказанных назначений.
- `LEGACY-OBJECT-SNAPSHOT-001`: утверждённый read-only mapping required object facts, fallback и zero-date semantics.
- `MIGRATION-PROCESS-001`: exact target case schema и unique identity.
- `PRODUCTION-MIGRATION-RUNNER-001`: env/error/redaction conventions и обязательная предварительная schema v4.
- `docs/fmonitor-2-maintable-field-census.md`: legacy completion/PTO risks и невозможность выдать mutable legacy slots за историю.

## 15. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил автономно довести проект до работающего пилота, явно выбрал operator-selected legacy IDs и потребовал SSD + TDD. Версия 0.2 утверждает минимальный offline bootstrap без массового отбора и исторической фабрикации и детерминированно исключает stdin из command channel.

Gate 2 разрешён для версии `0.2`; прежние tests, ожидающие rejection stdin, требуют обновления и нового независимого Gate 3 review.
