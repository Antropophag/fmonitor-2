# INSPECTION-PLANNING-SCHEMA-001 — canonical ownership planning schema

## Простыми словами (non-normative)

После этого среза две таблицы планирования инспекций создаёт и проверяет только
canonical migration v9. HTTP, календарь, очередь и bootstrap перестают создавать
schema при чтении или запросе. Существующие планы и события сохраняются; cadence,
перенос, отмена, назначение и продуктовая авторизация этим срезом не утверждаются.

Статус: **GATE_1_APPROVED**  
Решение владельца: **APPROVED 2026-09-02 для reviewed SHA-256 `c947d2bd...558dc4`**  
Gate: **1 пройден; Gate 2 RED разрешён**

## 1. Actor, seam и scope

Actor — deployment operator. Публичный seam — `bin/fmonitor2-migrate.php`, его
exit code и stdout. Runtime-negative seam — реальные scheduling POST, Calendar,
construction-control/queue projections и Compose bootstrap.

Migration единолично владеет:

- `${prefix}fm2_pilot_inspection_schedules`;
- `${prefix}fm2_pilot_inspection_schedule_events`.

Slice не меняет строки или product behavior и не утверждает cadence,
reschedule/cancel, assignment-race, visibility либо authorization semantics.

## 2. Preconditions, input и ordering

Runner SHALL применить literal migration v9 после exact landed catalogue:

1. v1 production process schema;
2. v2 workforce catalog;
3. v3 process user capabilities;
4. v4 process command capabilities;
5. v5 Bitrix workforce history;
6. v6 canonical identity/access;
7. v7 canonical checklist-template;
8. v8 canonical inspection-evidence.

Input — открытое MariaDB connection и уже validated prefix
`[A-Za-z0-9_]{0,25}`. Composed runner MUST отклонить 26-byte, non-ASCII или
invalid-character prefix до DB connection/access с configuration failure и без
раскрытия prefix. Longest family basename имеет 36 bytes, поэтому direct-family
арифметика 28/29 остаётся только evidence, не supported composed contract.

## 3. Database default и exact fingerprints

До target mutation migration SHALL получить exact database charset/collation.
Charset MUST быть `utf8mb4`. Collation MUST быть безопасным ASCII identifier,
соответствовать `utf8mb4` через metadata либо MariaDB UCA alias, пройти safe
trial application и затем явно emitted как `COLLATE <validated default>`.
Missing/unknown/non-applicable/non-utf8mb4 default даёт zero-mutation setup
failure. Обе tables MUST быть InnoDB с exact database-default collation.

Fingerprint сравнивается через `information_schema`, а не `SHOW CREATE` text.
Для columns нормативны order, `COLUMN_TYPE`, nullability, SQL NULL default,
`EXTRA`, `IS_GENERATED=NEVER`, NULL generation expression и character
charset/collation. Для indexes нормативны name, uniqueness, ordered columns,
NULL sub-parts, ascending order, BTREE и visible/`IGNORED=NO`. Лишние indexes,
UNIQUE, FK или CHECK несовместимы, кроме server-generated JSON validation CHECK,
описанного ниже. Cardinality, table row estimates и next AUTO_INCREMENT не
являются structural fingerprint и MUST сохраняться.

### 3.1 `${prefix}fm2_pilot_inspection_schedules`

```sql
CREATE TABLE `${prefix}fm2_pilot_inspection_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `installation_case_id` bigint(20) unsigned NOT NULL,
  `legacy_object_id` bigint(20) unsigned NOT NULL,
  `control_engineer_user_id` bigint(20) unsigned NOT NULL,
  `inspection_date` date NOT NULL,
  `scheduled_by_user_id` bigint(20) unsigned NOT NULL,
  `scheduled_at` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_planned_inspection` (`installation_case_id`,`control_engineer_user_id`,`inspection_date`),
  KEY `calendar_date` (`inspection_date`,`id`),
  KEY `engineer_day` (`control_engineer_user_id`,`inspection_date`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Все defaults — SQL NULL. Character metadata применяется только к
`scheduled_at`; остальные columns имеют NULL character metadata.

### 3.2 `${prefix}fm2_pilot_inspection_schedule_events`

```sql
CREATE TABLE `${prefix}fm2_pilot_inspection_schedule_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` bigint(20) unsigned NOT NULL,
  `installation_case_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(80) NOT NULL,
  `payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `actor_user_id` bigint(20) unsigned NOT NULL,
  `occurred_at` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `schedule_id` (`schedule_id`,`id`),
  KEY `installation_case_id` (`installation_case_id`,`id`),
  CONSTRAINT `<server-generated>` CHECK (json_valid(`payload_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Все defaults — SQL NULL. `event_type` и `occurred_at` используют validated
database-default collation; `payload_json` обязан иметь exact `utf8mb4_bin` и
semantic `json_valid(payload_json)` CHECK. Presentation name server-generated
CHECK не нормативно. Migration SHALL через `TABLE_CONSTRAINTS` связать CHECK
ровно с target table и прочитать `CHECK_CLAUSE` из `CHECK_CONSTRAINTS`. Для
сравнения SHALL удалить только ASCII whitespace, backticks и сбалансированные
внешние parentheses, затем привести ASCII letters к lower case. Результат MUST
быть exact `json_valid(payload_json)`. Ровно один такой CHECK разрешён;
отсутствие, changed expression, второй semantic-equivalent CHECK или любой
extra CHECK несовместимы. Gate 2 MUST отдельно доказать эти четыре cases.

## 4. Family-wide preflight и deterministic result

Для каждого member разрешены только `absent` или exact final. Migration SHALL
прочитать metadata всей family до первой DDL. Если любой existing member
несовместим, result MUST быть:

```text
applied = false
schemaVersion = 9
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = [все exact prefixed conflicts, binary ascending]
```

Runner останавливается на v9, возвращает exit `2` и stdout
`{"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":9}`.
Ни одна planning table, row, counter или decoy не изменяется.

Если все existing members exact, migration создаёт только absent members в
порядке schedules → events и возвращает `applied`, `schemaVersion=9` и
`tablesCreated` в binary ascending order. Exact complete repeat возвращает
`applied=false`, empty `tablesCreated`; rows, JSON bytes, IDs, schema and next
AUTO_INCREMENT остаются byte-equivalent. Никакого seed/backfill нет.

## 5. Acceptance scenarios

### Scenario A — clean composed runner

**GIVEN** exact populated/compatible v1–v8 и обе planning tables absent.  
**WHEN** operator запускает runner.  
**THEN** создаётся exact empty family, exit `0`, `schemaVersion=9`, а
`appliedVersions` содержит `9` после v1–v8.

### Scenario B — populated repeat

**GIVEN** exact family с двумя schedules/events, Unicode JSON и next id `3`.  
**WHEN** runner повторяется.  
**THEN** `appliedVersions=[]`; schema, ordered rows, payload bytes и next ids
неизменны, target DDL/DML отсутствует.

### Scenario C — schedules-only partial

**GIVEN** exact populated schedules, events absent.  
**WHEN** применяется v9.  
**THEN** создаётся только empty events; schedules rows/next id неизменны.

### Scenario D — events-only partial

**GIVEN** exact populated events, schedules absent.  
**WHEN** применяется v9.  
**THEN** создаётся только empty schedules; event JSON/rows/next id неизменны.

### Scenario E — conflict before completion

**GIVEN** один member absent, другой имеет changed column/index/collation/CHECK.  
**WHEN** применяется v9.  
**THEN** missing sibling не создаётся, conflict exact, вся planning family,
rows и counters неизменны.

### Scenario F — prefix isolation

**GIVEN** configured family compatible, а unprefixed/other-prefix decoys
несовместимы и populated.  
**WHEN** применяется v9.  
**THEN** читается/изменяется только configured family; decoys byte-equivalent.

### Scenario G — composed prefix pre-access

**GIVEN** unreachable DB configuration и otherwise-valid 26-byte prefix.  
**WHEN** запускается public runner.  
**THEN** он возвращает stable configuration failure до попытки DB access; при
25-byte prefix exact family может быть создана.

## 6. Runtime-no-DDL preservation

После v9 scheduling POST, Calendar, object queue, construction-control и Compose
bootstrap MUST NOT выполнять `CREATE`, `ALTER`, `DROP`, `RENAME`, `TRUNCATE`
или repair для этой family. Под DML-only principal существующая scheduling
characterization и projections сохраняют observable outcomes и schema
fingerprint. Missing/incompatible family MUST fail closed как deployment/setup
failure до planning DML и не ремонтироваться runtime. Observable outcomes:

- scheduling POST возвращает HTTP `503`, exact UTF-8 body
  `Не удалось запланировать инспекцию. Повторите попытку.\n`, без redirect,
  schedule/event DML или schema mutation;
- Calendar GET возвращает HTTP `503`, exact UTF-8 body
  `Календарь временно недоступен. Обновите страницу или вернитесь к объектам монтажа.\n`,
  без partial calendar HTML или mutation;
- object queue GET `/pilot/objects` возвращает HTTP `503`, `Content-Type:
  text/plain; charset=UTF-8` и body, совпадающий с exact regex
  `\AService unavailable\. Reference: [0-9a-f]{12}\n\z`; reference обязан быть
  fresh opaque correlation id и не является stable expected literal; partial
  queue HTML, planning/product DML и schema mutation отсутствуют;
- construction-control GET возвращает HTTP `503`, exact UTF-8 body
  `Контроль объектов временно недоступен. Повторите попытку.\n`, без partial
  enhanced response или mutation;
- Compose bootstrap завершается non-zero до ready-manifest publication и до
  fixture/import/product DML, не раскрывает credentials, prefix, SQL или table
  identifiers в operator-visible stderr.

Incompatible означает также failure exact schema precondition, а не только SQL
failure при read. Каждый consumer MUST вызвать один read-only readiness seam до
planning read/write; это не даёт runtime права repair.

Runtime preservation не превращает pilot `INSERT IGNORE`, event payload,
admission или projection behavior в target requirements. Product scheduling
остаётся в GRILL-001.

## 7. Audit, security, idempotency и Done

Migration не создаёт actor/product audit facts или migration-ledger row:
фактический canonical runner не имеет ledger. `schemaVersion` и
`appliedVersions` — observation текущего запуска, не durable audit. Repeat и
exact-compatible partial recovery после прерывания между двумя auto-committed
CREATE идемпотентны. Одновременные public runner invocations не поддерживаются
и MUST быть исключены deployment orchestration; добавление cross-runner
lock/ledger является отдельным change. Gate 2 не должно изображать
несуществующую concurrency guarantee. Runtime principal без DDL privilege MUST
продолжать работать на exact migrated family.

Done требует approved Gate 1, demonstrated RED, independent test approval,
minimal GREEN, focused/full verification, architecture check и independent code
review. Ни этот draft, ни OpenSpec validation не разрешают Gate 2.
