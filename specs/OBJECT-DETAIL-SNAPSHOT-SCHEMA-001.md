# OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 — canonical object-detail ownership

Статус: **DRAFT / GATE 1 NOT APPROVED**. Версия: 0.1. Дата: 2026-09-05.
Literal canonical migration version и production PHP signature не назначены.
Этот draft не разрешает RED, implementation или изменение importer.

## Простыми словами

Пустая база портала должна получать таблицы технических сведений об объектах
при штатной миграции, даже когда нет внешнего источника данных. Импорт и экраны
не создают таблицы во время работы. Уже сохранённые сведения сохраняются без
изменения; заполнение fictional TEST-USER данных выполняет другой slice.

## 1. Основание и границы

Actor — deployment operator. Public composed seam —
`php bin/fmonitor2-migrate.php` с существующим trusted database config.
Migration family принадлежит InstallationProcess canonical migration module.
Она не требует пользовательской роли или source credentials; DB principal
должен иметь необходимые metadata/DDL permissions для configured namespace.
Отсутствие таких прав является технической ошибкой, не успешной миграцией.

Источники: `docs/operations/object-detail-schema-evidence.md`, approved OpenSpec
`canonicalize-object-detail-snapshot-schema`, synthetic/native owner decision,
и dependency review
`docs/operations/object-detail-schema-import-characterization-dependency-review-2026-09-05.md`.
Evidence DDL фиксирует storage shape, не утверждает target import semantics.

## 2. Exact table manifest

Family содержит только две configured-prefix tables. Порядок columns нормативен.
Все columns NOT NULL, без explicit default, generated expression и EXTRA;
числовой object_id не имеет AUTO_INCREMENT.

| Table | Columns in order |
|---|---|
| `fm2_pilot_object_details` | `object_id BIGINT UNSIGNED`, `schema_version VARCHAR(80)`, `content_sha256 CHAR(64)`, `payload_json LONGTEXT`, `captured_at VARCHAR(40)` |
| `fm2_pilot_object_detail_quarantine` | `object_id BIGINT UNSIGNED`, `code VARCHAR(80)`, `schema_version VARCHAR(80)`, `content_sha256 CHAR(64)`, `captured_at VARCHAR(40)` |

Каждая table имеет только unique visible ascending BTREE PRIMARY(object_id)
без prefix length. Secondary indexes, FKs, CHECKs и JSON constraints отсутствуют.
Engine InnoDB; charset utf8mb4. Target collation — validated database-default
utf8mb4 collation, явно emitted в DDL; numeric column не имеет collation.
Exact metadata normalization и allowlist/public lookup для collation должны
быть закреплены перед Gate 1 approval. SHOW CREATE formatting, cardinality и
estimated row counts не являются fingerprint fields.

Исходный importer опускал COLLATE и тем самым выбирал charset-default,
не обязательно database-default. Existing table с отличающейся collation
считается conflict; automatic conversion запрещена.

## 3. Observable migration outcomes

1. Обе tables absent: после family-wide read-only preflight создать details,
   затем quarantine; обе пусты, ledger/version публикуется только после
   проверки полной exact family.
2. Обе exact: повтор не выполняет DDL/DML; все rows и metadata сохраняются.
3. Exact details + absent quarantine: создать только quarantine, сохранить
   every details column byte-equivalent.
4. Exact quarantine + absent details: создать только details, сохранить
   every quarantine column byte-equivalent.
5. Любой incompatible member: `SCHEMA_MIGRATION_CONFLICT` до первой family
   mutation, включая случай absent sibling. Не создавать отсутствующую table,
   не менять ledger, rows, counters или decoys.

Conflict evidence на operator seam определяет exact configured table;
credentials и source data не выводятся. Exact CLI JSON/status/exit mapping
и direct migration Result должны быть включены в следующую редакцию Gate 1.

Разрыв между двумя independently committed CREATE оставляет только exact
partial state. Следующий вызов выполняет preflight заново и завершает missing
member. После ошибочного DDL или unavailable verification нельзя публиковать
успешную ledger version. Детальный failure/retry transcript и concurrent-run
serialization наследуются canonical runner только после сверки exact contract;
этот draft не объявляет их доказанными.

## 4. Preservation examples

Fictional details row:
`object_id=7001`, `schema_version='fixture-v1'`, `content_sha256=64 characters 'a'`,
`payload_json='{"fixture":true}'`, `captured_at='2026-09-05T09:00:00Z'`.
Fictional quarantine row с тем же object_id:
`code='FIXTURE_ABSENT'`, `schema_version='fixture-v1'`,
`content_sha256=64 characters 'b'`, тот же captured_at.

Эти значения — opaque preservation sentinels, не valid imported evidence.
Exact repeat и partial recovery MUST сохранить каждый literal byte обеих rows.
Совместное наличие одного object_id в обеих tables не исправляется и не
удаляется. Migration не валидирует JSON или content hash по данным, не
пересчитывает fields и не создаёт process/domain audit events.

## 5. Prefix, namespace и isolation

Composed config принимает существующий ASCII prefix grammar с ceiling 25
bytes; 26 bytes, invalid characters и non-ASCII отклоняются до connection и
schema access. Longest family basename 34 bytes не расширяет этот ceiling.
Exact empty-prefix policy наследуется runner и фиксируется перед approval.

Тесты используют только enumerated isolated DB/prefix и fictional sentinel
rows. Другие prefix/unprefixed tables и filesystem decoys неизменны. Cleanup
охватывает только доказанно owned names. Ни один source import не нужен для
проверки чистой или populated schema migration.

## 6. Runtime boundary и separate importer gate

После integration importer и consumers MUST NOT выполнять family DDL или
repair. Importer проверяет exact migrated schema до source reads и data writes.
Absent/incompatible schema даёт fail-closed deployment precondition.
Для изменения importer и утверждения сохранности его serial extraction/replay/
conflict поведения требуется отдельный approved characterization oracle.
Этот draft не разрешает выполнять или считать approved его DML acceptance.

## 7. Gate 1 completion checklist

- Назначить literal migration version/catalogue position по свежему registry.
- Определить exact public signature/result и CLI mapping, metadata
  normalization/collation policy, failure/publication/concurrency examples.
- Сверить no-source schema path и importer characterization dependency;
  сохранить отдельный blocker на незавершённые importer behavior gates.
- Получить independent Gate 1 review и требуемое owner approval до RED.
- Затем demonstrated RED → independent Gate 3 → minimal GREEN → regression/
  architecture → independent Gate 5. Полный verify обязателен для integration.

Done требует data-free canonical creation, preservation/retry/conflict,
DDL-free runtime, approved importer regression, всех Gates/reviews и точной
проверки на integration SHA. Наличие этого draft не является Done.
