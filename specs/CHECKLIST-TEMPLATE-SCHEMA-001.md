# CHECKLIST-TEMPLATE-SCHEMA-001 — передать schema checklist-template canonical migration

## Простыми словами

Сейчас две таблицы шаблонов чек-листа могут создаваться самим приложением уже
во время работы. Эта спека переносит их создание в штатную миграцию базы №7:
deployment заранее создаёт или строго проверяет таблицы, а runtime только
пользуется ими и ничего не «чинит» сам. Существующие данные и поведение
привязки чек-листов должны сохраниться; новая логика чек-листов в этот срез не
входит.

- Статус: `GATE_1_APPROVED`
- Версия: `0.1`
- Дата: `2026-09-01`
- Актор: оператор deployment FMonitor 2.0
- Публичный migration seam: `ChecklistTemplateSchemaMigration.apply(connection, tablePrefix = '')`
- Публичный runner seam: `php bin/fmonitor2-migrate.php`
- Runtime consumers: `LegacyChecklistTemplateMySqlTarget.apply(...)`, `ChecklistTemplateAssociationTarget.associate(...)`
- OpenSpec change: `canonicalize-checklist-template-schema`

## 1. Единичный срез

Одна strict additive migration становится единственным production-владельцем двух существующих таблиц immutable checklist-template family. Она создаёт их в порядке snapshot → association, принимает только точный существующий fingerprint, завершает совместимое частичное состояние и до первого DDL preflight-проверяет всю family.

После migration snapshot import и association linking сохраняют существующие hash/idempotency, uniqueness, timestamps и immutable binding outcomes, но больше не выполняют schema-on-demand. Bootstrap, HTTP/request consumers, importers, linkers и cron также не выполняют `CREATE`, `ALTER` или `DROP` этой family.

Срез не добавляет foreign keys, `CHECK`, product enum, новую checklist/template semantics, payload transformation или новое binding rule. Существующие строки не переписываются и не получают выведенных задним числом фактов.

## 2. Preconditions, prefix и имена

`connection` — открытое MariaDB connection с выбранной database и подтверждённым connection charset `utf8mb4`. Ранее зарегистрированные canonical migrations успешно применены. Migration инспектирует и изменяет только два target name этого документа.

`tablePrefix` обязан соответствовать `/^[A-Za-z0-9_]{0,25}$/D`; пустое значение допустимо. Невалидный или 26-byte prefix вызывает `InvalidArgumentException` до первого DB query и не раскрывается в exception text. Собственная family-local граница равна 29: `fm2_checklist_template_associations` имеет 35 ASCII bytes, `29 + 35 = 64`. Более строгий composed предел 25 наследуется от полного release-supporting catalogue и не изменяет эту локальную арифметику.

Полные имена получаются только буквальным добавлением raw validated prefix:

```text
{prefix}fm2_checklist_template_snapshots
{prefix}fm2_checklist_template_associations
```

Production использует пустой prefix. Непустой prefix предназначен для изолированных integration tests. Разные допустимые prefixes являются независимыми namespaces: migration и runtime consumers не читают, не инспектируют и не изменяют одноимённые suffixes под другим prefix.

Обе таблицы используют exact `ENGINE=InnoDB`, table charset `utf8mb4` и database-default `utf8mb4_*` collation. Каждая character column обязана иметь `CHARACTER_SET_NAME=utf8mb4` и exact database-default collation; alternate explicit collation несовместима. Совместимость требует точного порядка/типа/nullability/extra columns, keys/index names/order, engine, charset/collation и отсутствия дополнительных columns, indexes, foreign keys или `CHECK`.

До DDL migration читает `DEFAULT_CHARACTER_SET_NAME` и
`DEFAULT_COLLATION_NAME` выбранной database, требует charset `utf8mb4`,
проверяет имя collation по `/^[A-Za-z0-9_]+$/D` и подтверждает membership через
`information_schema.COLLATIONS`: допустима exact строка для `utf8mb4` либо
документированный UCA alias, полученный удалением префикса `utf8mb4_`, даже
если metadata alias содержит nullable character-set. Exact reported default
обязан до первого target DDL успешно пройти безопасное trial application к
`utf8mb4`; unknown alias отклоняется с zero mutation. CREATE явно
задаёт `DEFAULT CHARSET=utf8mb4 COLLATE <validated database default>` с
безопасным identifier quoting; omission `COLLATE` запрещён. Invalid,
несуществующий или non-utf8mb4 database default отклоняется до первого DDL с
zero mutation.

## 3. Exact final schema fingerprint

### 3.1 `{prefix}fm2_checklist_template_snapshots`

Columns в exact ordinal order:

| # | Column | Exact MariaDB definition |
|---:|---|---|
| 1 | `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| 2 | `snapshot_version` | `VARCHAR(80) NOT NULL` |
| 3 | `captured_at` | `DATETIME NOT NULL` |
| 4 | `valid_from` | `DATETIME NOT NULL` |
| 5 | `validity_scope` | `VARCHAR(120) NOT NULL` |
| 6 | `source_label` | `VARCHAR(160) NOT NULL` |
| 7 | `content_sha256` | `CHAR(64) NOT NULL` |
| 8 | `payload_json` | `LONGTEXT NOT NULL` |
| 9 | `created_at` | `DATETIME NOT NULL` |

Exact indexes:

```text
PRIMARY KEY (id)
UNIQUE KEY uq_hash (content_sha256)
UNIQUE KEY uq_valid_from (valid_from)
```

Других indexes, foreign keys или `CHECK` нет. `payload_json` остаётся `LONGTEXT`, не `JSON`; implicit `json_valid` constraint поэтому отсутствует.

Для всех девяти columns `COLUMN_DEFAULT` равен SQL `NULL` (не строке
`'NULL'`), `IS_GENERATED=NEVER` и `GENERATION_EXPRESSION` равен SQL `NULL`, а
не пустой строке. `EXTRA` пуст у
всех columns, кроме exact `auto_increment` у `id`.

### 3.2 `{prefix}fm2_checklist_template_associations`

Columns в exact ordinal order:

| # | Column | Exact MariaDB definition |
|---:|---|---|
| 1 | `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` |
| 2 | `association_version` | `VARCHAR(80) NOT NULL` |
| 3 | `subject_kind` | `VARCHAR(40) NOT NULL` |
| 4 | `subject_id` | `VARCHAR(160) NOT NULL` |
| 5 | `effective_at` | `DATETIME NOT NULL` |
| 6 | `template_snapshot_id` | `BIGINT UNSIGNED NOT NULL` |
| 7 | `template_snapshot_version` | `VARCHAR(80) NOT NULL` |
| 8 | `template_content_sha256` | `CHAR(64) NOT NULL` |
| 9 | `created_at` | `DATETIME NOT NULL` |

Exact indexes:

```text
PRIMARY KEY (id)
UNIQUE KEY uq_subject (subject_kind, subject_id)
KEY snapshot_id (template_snapshot_id)
```

Других indexes, foreign keys или `CHECK` нет. В частности, `template_snapshot_id` намеренно не получает FK: этот ownership-only срез не вводит новые rejection/cascade semantics для существующих pilot data.

Для всех девяти columns `COLUMN_DEFAULT` равен SQL `NULL` (не строке
`'NULL'`), `IS_GENERATED=NEVER` и `GENERATION_EXPRESSION` равен SQL `NULL`, а
не пустой строке. `EXTRA` пуст у
всех columns, кроме exact `auto_increment` у `id`.

## 4. Migration observable outcomes

Checklist-template migration имеет literal schema version `7`. Exact landed
predecessor catalogue — canonical migrations v1–v6 включительно: process v1,
workforce catalogue v2, process user capabilities v3, capability enum v4,
workforce history v5 и identity/access v6. Иной predecessor, вставленный перед
v7, возвращает artifact в Gate 1; reviewed migrations не перенумеровываются
молча.

Migration result всегда называет полные prefixed table names. `tablesCreated` следует dependency order раздела 2. `conflictingTables` содержит каждое несовместимое target ровно один раз в том же нормативном порядке: snapshots, затем associations.

### 4.1 Clean family

Когда обе target tables отсутствуют, seam создаёт обе таблицы и возвращает:

```text
applied = true
schemaVersion = 7
tablesCreated = [
  {prefix}fm2_checklist_template_snapshots,
  {prefix}fm2_checklist_template_associations
]
```

Обе таблицы пусты и имеют exact fingerprint раздела 3. Никакие другие таблицы/строки не меняются.

### 4.2 Exact completed repeat

Когда обе таблицы exact и могут содержать строки:

```text
applied = false
schemaVersion = 7
tablesCreated = []
```

DDL/DML не выполняются. Complete catalog fingerprint, row bytes, row order-independent content, auto-increment counters и timestamps до/после равны.

### 4.3 Compatible partial family

Оба направления partial state являются совместимыми:

- exact snapshots существует, associations отсутствует → создать только associations;
- snapshots отсутствует, exact associations существует → создать только snapshots.

Результат имеет `applied = true`, `schemaVersion = 7`, а `tablesCreated` содержит только созданное полное имя. Существующая таблица, все её rows, indexes и auto-increment state остаются byte-for-byte прежними. Association без snapshot rows может существовать, потому что текущий schema contract намеренно не содержит FK; migration не изобретает либо не проверяет product binding facts.

### 4.4 Incompatible family preflight

Перед первым DDL migration инспектирует обе существующие target tables. Любое отличие fingerprint раздела 3 возвращает:

```text
applied = false
schemaVersion = 7
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = [<все несовместимые prefixed targets в нормативном порядке>]
```

Проверяются как минимум column order/name/type/unsigned/nullability,
`COLUMN_DEFAULT`, `EXTRA`, `IS_GENERATED`, `GENERATION_EXPRESSION`, character
charset/collation, engine/table collation, exact primary/unique/non-unique
indexes с именами и ordered columns, отсутствие extra indexes, foreign keys и
checks. Near-match `UNIQUE(id)` не заменяет `PRIMARY(id)`; unnamed либо иначе
названный index не заменяет exact named index.

При одном или двух конфликтах ни одна отсутствующая table не создаётся, ни одна compatible table не изменяется и DDL/DML отсутствуют во всей family. Sentinel rows, timestamps, indexes и `AUTO_INCREMENT` всех существующих targets до/после равны.

Unexpected DB/driver exception не маскируется как compatibility conflict и следует общему migration/runner failure contract. Destructive `down()` отсутствует.

## 5. Runtime consumers без DDL

Перед первым runtime DML оба mutating consumers требуют exact deployed family:

- `LegacyChecklistTemplateMySqlTarget.apply(...)`;
- `ChecklistTemplateAssociationTarget.associate(...)`, включая `associateActiveBaseline(...)` через этот seam.

Отсутствующая либо несовместимая target family отклоняется до `INSERT`, transaction или repair стабильным technical precondition `CHECKLIST_TEMPLATE_SCHEMA_REQUIRED`. Consumer не создаёт, не изменяет, не удаляет и не заменяет schema. Ошибка не содержит SQL, DB credentials, prefix, table/column names или driver text.

После exact migration сохраняются текущие observable data outcomes:

1. Snapshot import ищет existing row по exact `valid_from` под lock. Совпадающий `content_sha256` возвращает прежний `snapshotId`, `created=false`; иной hash возвращает `CHECKLIST_TEMPLATE_CAPTURE_CONFLICT` без изменения row.
2. Новый snapshot сохраняет прежние exact значения `snapshot_version`, `captured_at`, `valid_from`, `validity_scope`, `source_label`, `content_sha256`, `payload_json`, `created_at` и возвращает `created=true`.
3. Association сохраняет policy validation и snapshot version/hash match. Первый `(subject_kind, subject_id)` создаётся; exact replay возвращает ту же association identity с `created=false`.
4. Попытка перепривязать тот же unique subject к иным effective/template facts остаётся `CHECKLIST_TEMPLATE_ASSOCIATION_CONFLICT`; отсутствующий либо mismatched snapshot остаётся `CHECKLIST_TEMPLATE_SNAPSHOT_MISMATCH`; policy rejection остаётся `DEFINITION_VERSION_UNPROVEN`.
5. Ни один repeat/conflict outcome не меняет ранее сохранённый immutable snapshot или association.

Runtime no-DDL outcome проверяется не только source search: MariaDB privilege fixture предоставляет consumer principal `SELECT`/`INSERT` на exact migrated tables без `CREATE`/`ALTER`/`DROP`; успешные import/link/replay outcomes обязаны сохраниться. Отдельная before/after schema fingerprint доказывает нулевую schema mutation.

## 6. Canonical runner composition

Этот seam регистрируется ровно один раз как canonical v7 после exact landed
catalogue v1–v6 и непосредственно после identity/access v6. Более поздняя
migration inspection evidence вызывается только после него. `7` является exact
test literal.

Runner вызывает следующий step только после applied/no-op success. На clean compatible database success добавляет `7` в ascending `appliedVersions`; completed repeat не добавляет `7`. Partial completion добавляет `7`. Conflict этого seam останавливает runner с exit `2`, пустым stderr и exact stdout:

```json
{"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":7}
```

Runner не раскрывает `conflictingTables` наружу. Ранний conflict сохраняет номер ранней версии и не вызывает template step; template conflict не вызывает более поздние versions. Unexpected template-step exception даёт общий exit `70`, stdout `{"ok":false,"reason":"MIGRATION_FAILED"}\n`, пустой stderr.

Из-за полного release-supporting catalogue runner до DB connection сужает `FMONITOR_PROCESS_TABLE_PREFIX` до `/^[A-Za-z0-9_]{0,25}$/D`. Missing/invalid configuration, включая length 26 и прежде допустимые lengths 27–32, даёт общий exit `64` `CONFIGURATION_INVALID` без DB attempt. Собственная 29-byte family-local арифметика association basename остаётся evidence, но не является composed configuration support. Остальные runner configuration/output rules не меняются.

Если catalogue меняется после фиксации v7, artifact возвращается в Gate 1; implementation не перенумеровывает reviewed contract молча.

## 7. Independent executable Gate 2 matrix

Один dedicated MariaDB test транскрибирует expected columns/indexes из раздела 3 в test-owned literals и не читает production DDL/constants как oracle. Он проверяет через public migration seam и canonical CLI:

1. clean family → exact two-table fingerprint и runner application с literal `7`;
2. populated repeat → `applied=false`, `tablesCreated=[]`, unchanged schema/rows/auto-increment;
3. exact snapshots-only partial → создать associations и сохранить snapshot sentinel;
4. exact associations-only partial → создать snapshots и сохранить association sentinel;
5. по отдельности changed column, SQL/string default, generated expression,
   index name/order/kind, engine, alternate collation, extra FK и extra CHECK →
   exact conflict;
6. два simultaneous conflicts → оба full prefixed names в нормативном порядке;
7. conflict плюс missing sibling → zero family mutation, missing sibling остаётся absent;
8. два разных valid prefixes в одной database → isolation, включая одинаковые numeric IDs/hash/subjects и decoy conflict под чужим prefix;
9. lengths 25/26 и invalid characters → 25 accepted, 26 rejected до DB connection/access; invalid, unknown и non-utf8mb4 database default → zero mutation before DDL;
10. migrated snapshot import, association create, exact replay и immutable conflict outcomes под DDL-denied runtime principal;
11. absent и incompatible runtime schema → `CHECKLIST_TEMPLATE_SCHEMA_REQUIRED`, zero DDL/DML;
12. architecture rule запрещает family-targeted runtime `CREATE`/`ALTER`/`DROP` и не увеличивает общий debt baseline.

Existing behavior verifiers `rapid-pilot/verify-native-checklist-template-binding.php` и `rapid-pilot/verify-active-baseline-case-connector.php` остаются regression evidence, но их reduced hand-built tables не являются exact schema oracle. Relevant import/link verification также сохраняется.

До implementation действительный RED обязан показывать отсутствие canonical v7/tables либо runtime DDL под DDL-denied principal. Setup/connection/configuration failure, assertion against hand-built reduced schema или failure ранее неготовой prerequisite migration не является RED evidence.

## 8. Authorization, audit и preservation

Migration выполняется deployment DB principal; отдельного product actor/capability и domain event нет. Runner audit — его exact machine-readable result и внешний deployment log. Runtime import/link сохраняют существующие authorization/audit boundaries; этот ownership slice не расширяет caller permissions и не создаёт новый audit fact.

Migration не меняет legacy tables, process facts, workforce/identity facts, checklist operations/photos, files или artifact store. Сохранность доказывается schema/row/auto-increment fingerprints exact family и sentinel fingerprints выбранных prerequisite/decoy tables.

## 9. Done definition

Срез завершён только если:

- владелец явно утвердил этот Gate 1 artifact;
- отдельный RED agent доказал missing ownership, а fresh reviewer записал `APPROVED` в `reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md`;
- strict migration зарегистрирована в exact runner order;
- оба runtime DDL owners заменены fail-closed precondition;
- clean/repeat/both partial/conflict/prefix/preservation/runtime-no-DDL matrix green;
- relevant binding/import/link regressions, `make architecture-check` и `make verify` выполнены без ослабления expectations;
- fresh code reviewer записал `APPROVED` в `reviews/code/CHECKLIST-TEMPLATE-SCHEMA-001.md`;
- OpenSpec tasks отмечены только после фактического прохождения соответствующих gates.

## 10. Owner decision

После fresh independent technical Gate 1 review нужно одно решение: утвердить
или отклонить этот executable contract. До verdict `READY_FOR_OWNER_APPROVAL`
Gate 2/implementation не начинаются и OpenSpec task `1.1` остаётся unchecked.

- Владелец продукта: пользователь проекта
- Дата решения: `2026-09-01`
- Решение: `APPROVED`
- Комментарий: «Согласовано» — явное утверждение владельца после независимого verdict `READY_FOR_OWNER_APPROVAL`.
