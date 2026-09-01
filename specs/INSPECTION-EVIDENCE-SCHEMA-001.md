# INSPECTION-EVIDENCE-SCHEMA-001 — canonical ownership inspection-evidence schema

## Простыми словами (non-normative)

После этого среза четыре таблицы доказательств инспекции создаёт и строго
проверяет canonical migration v8, а checklist runtime только использует их и
ничего не создаёт и не чинит при HTTP-запросе. Существующие записи и два уже
наблюдаемых additive upgrade сохраняются; правила выполнения, исправления и
авторизации этим срезом не меняются.

Статус: **DRAFT — READY_FOR_INDEPENDENT_REVIEW**  
Решение владельца: **PENDING**  
Gate: **1 (executable specification), predecessors landed; не пройден до fresh independent review и явного APPROVED владельца**

## 1. Actor, цель и граница поведения

Actor — deployment operator, запускающий production canonical migration runner `bin/fmonitor2-migrate.php` с уже действующим валидированным `FMONITOR_PROCESS_TABLE_PREFIX`.

Подтверждённый публичный seam этого slice — один запуск canonical runner и его exit code/stdout, а для runtime-negative acceptance — существующие checklist HTTP/sync/photo entry points через `PilotE2ECoordinator` и публичные методы `ChecklistSync`. Только migration владеет DDL четырёх таблиц. Checklist runtime остаётся consumer и не создаёт, не изменяет и не ремонтирует schema.

Цель — перенести без изменения продуктовой семантики единоличное владение family:

- `${prefix}fm2_checklist_revisions`;
- `${prefix}fm2_checklist_operations`;
- `${prefix}fm2_checklist_operation_installers`;
- `${prefix}fm2_checklist_photos`.

Этот slice наследует append-only требования `PRODUCT.md`, `CONTEXT.md` и pilot contracts. Он не утверждает новую модель исправления/отзыва факта выполнения, причину или историю отзыва фото, dimensions/caption, authorization policy, FK, CHECK либо иное product rule. Эти вопросы остаются нерешёнными; текущие наблюдаемые correction/photo outcomes только сохраняются как regression characterization.

## 2. Preconditions, input и ordering

Runner SHALL применить inspection-evidence migration как literal v8, строго после успешно проверенных prerequisites:

1. v1 production process schema;
2. v2 workforce catalog;
3. v3 process user capabilities;
4. v4 process command capabilities;
5. v5 Bitrix workforce history;
6. v6 canonical identity/access schema;
7. v7 canonical checklist-template schema.

Inspection-evidence migration имеет exact literal schema version `8` и
регистрируется ровно один раз непосредственно после v7. Exact landed catalogue
v1–v7 не перенумеровывается и не получает вставленных predecessors. Изменение
catalogue после фиксации возвращает artifact в Gate 1, а не решается
implementation-agent. Gate 2 MUST NOT начинаться до fresh independent Gate 1
review и явного owner approval этого literal v8 contract.

Input migration — открытое MariaDB connection и validated prefix `[A-Za-z0-9_]{0,25}` из prerequisite composed-runner contract. Longest basename этой family — `fm2_checklist_operation_installers`, exact 34 ASCII bytes. MariaDB identifier boundary допустила бы 30-byte family-local prefix (`34 + 30 = 64`), но весь inherited release-supporting catalogue обязан использовать единый более строгий 25-byte ceiling. Prefix длиной 26 поэтому отклоняется runner до DB connection/access, хотя эта family сама допускает более длинное имя. Migration рассматривает только четыре exact `${prefix}`-имени; похожие unprefixed и иначе prefixed таблицы не входят во входное состояние и не читаются/не изменяются.

## 3. Exact final fingerprints

До любой owned-family DDL/DML migration SHALL прочитать `DEFAULT_CHARACTER_SET_NAME` и `DEFAULT_COLLATION_NAME` exact target database row из `information_schema.SCHEMATA`. Charset MUST быть exact `utf8mb4`. Collation MUST:

1. быть non-empty ASCII identifier, проходящий exact allowlist regex `\A[A-Za-z0-9_]+\z`;
2. иметь exact matching row в `information_schema.COLLATIONS` для `utf8mb4`
   либо быть документированным MariaDB UCA alias, полученным удалением префикса
   `utf8mb4_`, даже если metadata alias сообщает nullable character-set;
3. до первого target DDL успешно пройти безопасное trial application к
   `utf8mb4`; unknown/non-applicable alias отклоняется с zero mutation;
4. после валидации быть safely identifier-quoted и явно emitted в каждом `CREATE TABLE ... DEFAULT CHARACTER SET utf8mb4 COLLATE <validated_database_default>` и в каждом character-column addition.

Пропуск `COLLATE` в production DDL запрещён: server/session default не заменяет target database default. Missing SCHEMATA row, non-`utf8mb4` database charset, malformed/unknown/non-`utf8mb4` collation дают preflight infrastructure/configuration failure до первой owned-family mutation; вся family, rows и `AUTO_INCREMENT` states остаются неизменными.

Во всех четырёх таблицах SHALL быть `ENGINE=InnoDB`, table charset `utf8mb4`, а table и каждая character column SHALL иметь exact validated target database-default collation. Alternate explicit collation несовместима. SQL blocks ниже задают column/key shape; их final `DEFAULT CHARSET=utf8mb4` читается вместе с обязательным emitted `COLLATE <validated_database_default>` из предыдущего абзаца. Порядок columns ниже нормативен. Кроме перечисленных keys SHALL не быть других indexes, UNIQUE, FK или CHECK constraints.

Exact fingerprint comparison SHALL use `information_schema.COLUMNS`, `STATISTICS`, `TABLES`, `TABLE_CONSTRAINTS`/`REFERENTIAL_CONSTRAINTS` и `CHECK_CONSTRAINTS`, а не normalized `SHOW CREATE` text. Для каждой column normative metadata включает ordinal, `COLUMN_TYPE`, `IS_NULLABLE`, и exact `COLUMN_DEFAULT`: SQL `NULL` означает absence of default, а string value (including literal string `NULL`) не равна SQL `NULL`. Каждая column MUST иметь `IS_GENERATED = 'NEVER'` и SQL `NULL` в `GENERATION_EXPRESSION`. `EXTRA` MUST быть exact `auto_increment` только для `operations.id` и `photos.id`, и exact empty string для всех остальных columns. Каждая character column MUST иметь `CHARACTER_SET_NAME='utf8mb4'` и exact validated collation; non-character columns MUST иметь SQL `NULL` для обоих metadata fields. Каждый index fingerprint включает exact name, uniqueness, ordered columns, `SUB_PART` (SQL `NULL` для всех listed keys), `COLLATION='A'`, `INDEX_TYPE='BTREE'` и MariaDB visibility metadata `IGNORED='NO'`; иное значение несовместимо.

### 3.1 `${prefix}fm2_checklist_revisions`

```sql
CREATE TABLE `${prefix}fm2_checklist_revisions` (
  `installation_case_id` bigint(20) unsigned NOT NULL,
  `revision_no` bigint(20) unsigned NOT NULL DEFAULT 0,
  `updated_at` varchar(40) NOT NULL,
  PRIMARY KEY (`installation_case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

`COLUMN_DEFAULT` равен string `0` для `revision_no` и SQL `NULL` (не string `NULL`) для двух остальных columns.

### 3.2 `${prefix}fm2_checklist_operations`

```sql
CREATE TABLE `${prefix}fm2_checklist_operations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `installation_case_id` bigint(20) unsigned NOT NULL,
  `client_operation_id` char(36) NOT NULL,
  `device_installation_id` char(36) NOT NULL,
  `operation_type` varchar(40) NOT NULL,
  `section_id` tinyint(3) unsigned NOT NULL,
  `item_id` smallint(5) unsigned NULL,
  `actor_user_id` bigint(20) unsigned NOT NULL,
  `device_time` varchar(40) NOT NULL,
  `server_received_at` varchar(40) NOT NULL,
  `base_revision` bigint(20) unsigned NOT NULL,
  `accepted_revision` bigint(20) unsigned NOT NULL,
  `payload_json` text NOT NULL,
  `template_snapshot_id` bigint(20) unsigned NULL,
  `template_snapshot_version` varchar(80) NULL,
  `template_content_sha256` char(64) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_operation_id` (`client_operation_id`),
  KEY `installation_case_id` (`installation_case_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Для всех 16 columns `COLUMN_DEFAULT` равен SQL `NULL` (не string `NULL`).

### 3.3 `${prefix}fm2_checklist_operation_installers`

```sql
CREATE TABLE `${prefix}fm2_checklist_operation_installers` (
  `client_operation_id` char(36) NOT NULL,
  `installer_tab_id` bigint(20) unsigned NOT NULL,
  `fio_snapshot` varchar(300) NOT NULL,
  `position_snapshot` varchar(300) NOT NULL,
  `employment_status_snapshot` varchar(40) NOT NULL,
  `dismissal_effective_at_snapshot` varchar(40) NULL,
  `workforce_source_updated_at_snapshot` varchar(40) NOT NULL,
  `assignment_source` varchar(40) NOT NULL,
  PRIMARY KEY (`client_operation_id`,`installer_tab_id`),
  KEY `installer_tab_id` (`installer_tab_id`,`client_operation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Для всех восьми columns `COLUMN_DEFAULT` равен SQL `NULL` (не string `NULL`), включая final `assignment_source`.

`assignment_source` в final fingerprint не имеет default. Literal для upgrade existing rows не становится разрешением принимать новые rows без явного source.

### 3.4 `${prefix}fm2_checklist_photos`

```sql
CREATE TABLE `${prefix}fm2_checklist_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `installation_case_id` bigint(20) unsigned NOT NULL,
  `section_id` tinyint(3) unsigned NOT NULL,
  `upload_operation_id` char(36) NOT NULL,
  `sha256` char(64) NOT NULL,
  `mime_type` varchar(40) NOT NULL,
  `byte_size` int(10) unsigned NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `storage_name` varchar(255) NOT NULL,
  `actor_user_id` bigint(20) unsigned NOT NULL,
  `device_time` varchar(40) NOT NULL,
  `server_received_at` varchar(40) NOT NULL,
  `revoked_at` varchar(40) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `upload_operation_id` (`upload_operation_id`),
  UNIQUE KEY `installation_case_id` (`installation_case_id`,`section_id`,`sha256`),
  KEY `installation_case_id_2` (`installation_case_id`,`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Для всех 13 columns `COLUMN_DEFAULT` равен SQL `NULL` (не string `NULL`).

Имена `installation_case_id` и MariaDB-generated `installation_case_id_2` являются частью существующего fingerprint; migration не переименовывает их.

## 4. Разрешённые input forms и family-wide preflight

Для каждой таблицы разрешено только `absent`, exact final из раздела 3 или применимая exact predecessor form ниже. Preflight SHALL получить metadata всей четырёхтабличной family до первой DDL/DML mutation.

### 4.1 Operations predecessor

Exact predecessor `fm2_checklist_operations` совпадает с разделом 3.2 по engine, collation, первым 13 columns и всем keys, но полностью не содержит последних трёх columns:

- `template_snapshot_id`;
- `template_snapshot_version`;
- `template_content_sha256`.

Нельзя считать predecessor форму с одним или двумя из этих columns либо с иным position/type/nullability/default. Разрешённый upgrade добавляет все три columns в указанном порядке как nullable без default. Для каждой существующей row их значения после upgrade exact `NULL`; ни один прежний column value не изменяется.

### 4.2 Operation-installers predecessor

Exact predecessor `fm2_checklist_operation_installers` совпадает с разделом 3.3 по engine, collation, первым семи columns и обоим keys, но полностью не содержит последнего `assignment_source`.

Разрешённый upgrade добавляет `assignment_source varchar(40) NOT NULL` последним column, записывает для каждой существующей row exact literal `pilot_backfill_current_order`, затем оставляет final column без default. Primary/secondary keys и все прежние personnel snapshot bytes не изменяются.

Оба predecessors могут присутствовать одновременно. Любая из остальных двух таблиц и любая не-upgrade-таблица в том же input могут быть absent или exact final. Это полный набор compatible partial combinations; произвольный repair не разрешён.

### 4.3 Conflict result

Любая existing owned table вне разрешённых форм — включая лишний/отсутствующий/reordered column, changed type/nullability, SQL-NULL-vs-string default, `EXTRA`, generated status/expression, per-character-column charset/collation, key name/order/uniqueness/direction/`SUB_PART`/type/visibility, extra constraint/index, FK, CHECK, engine, table charset или collation — конфликт.

До первой mutation migration SHALL вернуть:

```text
applied = false
schemaVersion = 8
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = [все несовместимые exact prefixed names, binary ascending]
```

Ни одна owned table, row, index и `AUTO_INCREMENT` state не изменяется; отсутствующие compatible tables при этом не создаются. Runner останавливается на evidence step, возвращает exit `2` и exact stdout:

```json
{"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":8}
```

## 5. Accepted outcomes

### Scenario A — clean deployment

**GIVEN** все exact prerequisites through v7 и все четыре evidence tables absent.  
**WHEN** operator запускает canonical runner.  
**THEN** создаются exact fingerprints раздела 3, migration возвращает `applied=true`, `schemaVersion=8`, `tablesCreated` в binary ascending order, а runner возвращает exit `0`, `schemaVersion=8` и включает `8` в ascending `appliedVersions`.

### Scenario B — repeat

**GIVEN** exact final family с sentinel rows и `AUTO_INCREMENT` next values operations/photos.  
**WHEN** runner запускается повторно.  
**THEN** evidence migration возвращает `applied=false`, `schemaVersion=8`, пустые created/upgraded lists; runner не включает `8` в `appliedVersions`; `SHOW CREATE`, rows и оба next values побайтно прежние; DDL/DML отсутствует.

### Scenario C — operations additive upgrade

**GIVEN** exact operations predecessor с sentinel operation row, остальные tables exact final.  
**WHEN** применяется evidence migration.  
**THEN** добавлены только три nullable template columns, sentinel получает три `NULL`, все прежние bytes/indexes и operations `AUTO_INCREMENT` next value сохранены, result сообщает upgrade operations и `applied=true`.

### Scenario D — installer additive upgrade

**GIVEN** exact installer predecessor с sentinel personnel snapshot row, остальные tables exact final.  
**WHEN** применяется evidence migration.  
**THEN** добавлен только final `assignment_source`, sentinel получает exact `pilot_backfill_current_order`, прежние bytes/keys сохранены и final column не имеет default; result сообщает upgrade installers и `applied=true`.

### Scenario E — compatible partial and both upgrades

**GIVEN** revisions absent, photos absent, operations и installers находятся в двух exact predecessor forms с sentinel rows и next values.  
**WHEN** применяется evidence migration.  
**THEN** полный preflight проходит; только отсутствующие tables создаются и только две разрешённые additions выполняются; final family exact разделу 3, sentinel evidence и next values сохранены.

### Scenario F — incompatible family is atomic before mutation

**GIVEN** revisions absent, operations exact predecessor с sentinel, installers имеет extra index, photos имеет changed collation.  
**WHEN** применяется evidence migration.  
**THEN** conflict names installers и photos в binary ascending order; revisions не создаётся, operations не upgrade-ится, sentinel и вся family остаются побайтно прежними.

### Scenario G — prefix isolation

**GIVEN** `${prefix}` family compatible, а unprefixed и `decoy_` families содержат same suffixes с произвольной несовместимой schema/rows.  
**WHEN** применяется evidence migration для `${prefix}`.  
**THEN** preflight/result относятся только к четырём `${prefix}` names; definitions, rows и next values обеих decoy families неизменны.

## 6. Runtime-no-DDL и сохранение поведения

После регистрации evidence migration production `ChecklistSync`, `PilotE2ECoordinator`, checklist read/sync, item completion/installer attribution/correction, offline duplicate/conflict/prefetch, photo upload/revoke и downstream completion/native OTIZ paths MUST NOT выполнять `CREATE`, `ALTER`, `DROP`, `RENAME`, `TRUNCATE` или иной schema repair для owned family.

На exact final schema существующие observable outcomes SHALL остаться прежними: accepted facts остаются append-only, template identity копируется в новые operations, installer snapshot/source сохраняются, duplicate/conflict behavior не меняется, current crew не переписывает historical attribution, а photo upload/revoke и section readiness имеют прежнее поведение. Этот пункт является preservation contract, а не утверждением полноты correction или photo-revocation domain semantics.

Если хотя бы одна required owned table отсутствует или не соответствует final consumer precondition, runtime SHALL fail closed до checklist evidence DML и file persistence, SHALL не вызывать migration/repair и SHALL сигнализировать существующий `PilotHttpInfrastructureUnavailable`; HTTP adapter сохраняет существующий deployment/infrastructure outcome `503` (для JSON checklist endpoints — `{"status":"retryable"}`). Rows, files и schema при таком отказе неизменны.

## 7. Authorization и audit

Migration не является пользовательской process-командой и не вводит capability. Authorization обеспечивается deployment-доступом к runner configuration/database по существующему operator contract. Checklist authorization остаётся без изменений.

Migration не создаёт domain event и не меняет historical audit facts. Наблюдаемый deployment audit — deterministic runner result: version, applied/no-op либо conflict и ordered affected table lists. Upgrade не подменяет `actor_user_id`, timestamps, payload, installer snapshots, photo metadata или revoked state.

## 8. Обязательная Gate 2 acceptance/RED matrix

После landing всех predecessors, фиксации literal versions, fresh independent Gate 1 review и owner approval Gate 2 SHALL создать executable test, не подменяющий expected values текстом implementation. Матрица минимум:

| ID | Input / action | Required observation |
|---|---|---|
| G2-01 | Clean target DB, landed prerequisites, все 4 tables absent | Exact four final fingerprints; explicit validated DB-default collation; ordered result; runner applies exact literal `8` after v1–v7 |
| G2-02 | Exact final family with sentinel rows and non-default next `AUTO_INCREMENT`; repeat runner | No DDL/DML; byte-identical metadata/rows/next values; version absent from `appliedVersions` |
| G2-03 | Exhaustive compatible partial state product: revisions/photos each `absent|final`, operations/installers each `absent|predecessor|final` | All 36 combinations converge only by permitted creates/additions/backfill; existing bytes, keys and next values preserved |
| G2-04 | Operations predecessor independently and together with installer predecessor, populated and empty variants | Exactly three ordered nullable columns added; existing rows get SQL `NULL`; installer addition/backfill exact; final additions have no defaults |
| G2-05 | Operations contains exactly 1 or exactly 2 of its 3 additive columns, for every positional subset; installers malformed around `assignment_source` | Every malformed state conflicts before mutation; no opportunistic completion/backfill |
| G2-06 | One mutation at a time of column order/type/nullability, SQL `NULL` versus string default, `IS_GENERATED`, `GENERATION_EXPRESSION` or `EXTRA` | Exact owned table reported conflicting; zero family mutation |
| G2-07 | One mutation at a time of table engine/charset/collation or character-column charset/collation | Exact owned table reported conflicting; zero family mutation |
| G2-08 | One mutation at a time of index name/order/uniqueness/direction/`SUB_PART`/`INDEX_TYPE`/visibility; added/removed index | Exact owned table reported conflicting; zero family mutation |
| G2-09 | Added FK, changed FK metadata if present, added CHECK, or any extra constraint | Exact owned table reported conflicting; zero family mutation |
| G2-10 | At least two simultaneous conflicts of different classes while another table is absent and another is predecessor | All conflicts returned binary ascending; absent table not created, predecessor not upgraded/backfilled; rows and next values unchanged |
| G2-11 | Valid DB-default `utf8mb4` collation with prefix lengths 0 and 25; otherwise valid prefix length 26 | 0/25 accepted and exact names created/checked; 26 rejected by inherited catalogue preflight before DB connection/access |
| G2-12 | Missing SCHEMATA row, non-`utf8mb4` DB default charset, malformed/unknown collation, or collation belonging to another charset | Infrastructure/configuration failure before owned DDL/DML; all tables/rows/next values unchanged; no unvalidated interpolation |
| G2-13 | Runner catalogue with landed predecessor versions, then deliberately missing/out-of-order/renumbered predecessor in isolated fixtures | Ascending exact sequential execution succeeds only for landed catalogue; invalid sequencing fails before evidence mutation |
| G2-14 | Exact final schema; runtime DB principal denied `CREATE`, `ALTER`, `DROP`, `RENAME`, `TRUNCATE` but allowed required DML | Checklist sync/item/photo golden runtime actions retain existing success outcomes, proving no request-path DDL |
| G2-15 | Each required table absent in turn, then each table incompatible in turn; invoke checklist JSON and file-producing photo paths | Existing infrastructure result/HTTP `503` (JSON `{"status":"retryable"}`); zero checklist-evidence DML, zero file persistence, zero schema repair |
| G2-16 | Compatible `${prefix}` family plus incompatible unprefixed and `decoy_` families | Only exact `${prefix}` family inspected/mutated; decoy metadata/rows/next values byte-identical |

Gate 2 RED evidence валидно только если test environment/setup успешен, prerequisites применены, fixture доказанно создал exact intended input, а failure — assertion failure из-за отсутствующего target behavior. Parse/setup/connection/permission/fixture failure, падение predecessor migration или уже GREEN test не являются RED. RED artifact MUST сохранить command, exit, failing assertion и setup proof и пройти independent test review до implementation.

## 9. Gate 1 decision

Predecessors v1–v7 landed. До fresh independent review и отдельной явной записи
владельца `APPROVED` этот документ остаётся `DRAFT — READY_FOR_INDEPENDENT_REVIEW`.
Автор документа не утверждает собственную спецификацию и не разрешает Gate 2
test edits.
