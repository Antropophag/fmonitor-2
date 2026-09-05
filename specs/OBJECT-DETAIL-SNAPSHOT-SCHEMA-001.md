# OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 — canonical object-detail ownership

Статус: **DRAFT / GATE 1 NOT APPROVED**. Версия: 0.2. Дата: 2026-09-05.
Canonical version candidate — 12 после свежего чтения registry 1–11 на
`670e19f6d86fe0172d71eaa36736ec6d857b936e`. Версия не зарезервирована и
не зарегистрирована; перед approval требуется повторная проверка frontier.
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
Metadata comparison использует lowercase COLUMN_TYPE; для BIGINT допускается
отсутствие либо display width `(20)`, без изменения unsigned semantics.
`COLUMN_DEFAULT` MUST быть SQL NULL, `EXTRA` пустой, generated expression
отсутствует; nullable/default/auto-increment drift конфликтует.
Collation проверяется существующим public
`IdentityAccessDefinitionSchemaMigration::databaseCollation`: database charset
utf8mb4, ASCII identifier grammar, registered utf8mb4 member либо поддержанный
MariaDB alias и успешный explicit utf8mb4 COLLATE trial до family DDL.
SHOW CREATE formatting, cardinality и
estimated row counts не являются fingerprint fields.

Исходный importer опускал COLLATE и тем самым выбирал charset-default,
не обязательно database-default. Existing table с отличающейся collation
считается conflict; automatic conversion запрещена.

## 3. Observable migration outcomes

1. Обе tables absent: после family-wide read-only preflight создать details,
   затем quarantine; обе пусты, successful version возвращается только после
   проверки полной exact family.
2. Обе exact: повтор не выполняет DDL/DML; все rows и metadata сохраняются.
3. Exact details + absent quarantine: создать только quarantine, сохранить
   every details column byte-equivalent.
4. Exact quarantine + absent details: создать только details, сохранить
   every quarantine column byte-equivalent.
5. Любой incompatible member: `SCHEMA_MIGRATION_CONFLICT` до первой family
   mutation, включая случай absent sibling. Не создавать отсутствующую table,
   не менять rows, counters или decoys.

Conflict evidence на operator seam определяет exact configured table;
credentials и source data не выводятся. Exact API/mapping приведены ниже.

Разрыв между двумя independently committed CREATE оставляет только exact
partial state. Следующий вызов выполняет preflight заново и завершает missing
member. После ошибочного DDL или unavailable verification нельзя публиковать
успешную version. Canonical runner на inspected SHA не имеет persisted migration
ledger: он вычисляет результат обходом registry. Этот slice не создаёт ledger.
Детальный concurrent-run acceptance остаётся Gate 1 open item; действующий
runner не доказывает serialization, и draft не утверждает обратное.

### Public API candidate и exact results

```php
namespace FMonitor2\InstallationProcess;
final class ObjectDetailSnapshotSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array;
    public static function isCompleteCompatible(\mysqli $connection, string $tablePrefix = ''): bool;
}
```

`apply` возвращает только один из двух shapes (порядок keys фиксирован):
`{applied: bool, schemaVersion: 12, tablesCreated: list<string>}` либо
`{applied: false, schemaVersion: 12, reason: SCHEMA_MIGRATION_CONFLICT,
conflictingTables: list<string>}`. Table lists — exact prefixed names,
отсортированные SORT_STRING. `applied` true ровно при создании хотя бы одной
table. Exact repeat: empty tablesCreated и applied false.

`isCompleteCompatible` — read-only: true только для полной exact family;
absent/conflict/inspection unavailable дают false. Она не выполняет DDL/DML.
Invalid prefix для `apply` бросает `InvalidArgumentException` с fixed message
`Invalid table prefix.` до query; compatibility query возвращает false.
SQL/inspection failures `apply` дают `DatabaseUnavailable` без success result.

CLI использует existing CanonicalMigrationApplication mapping:
conflict — exit 2, exact JSON
`{"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":12}`;
database unavailable — exit 69,
`{"ok":false,"reason":"DATABASE_UNAVAILABLE"}`;
unexpected failure — exit 70,
`{"ok":false,"reason":"MIGRATION_FAILED"}`.
Каждый JSON завершается одним LF; stderr не содержит credentials/source data.
На базе с exact v1–11 и отсутствующей family успех — exit 0,
`{"ok":true,"schemaVersion":12,"appliedVersions":[12]}`;
повтор — тот же JSON с `appliedVersions:[]`.

Failure после первой CREATE не откатывает existing table. Retry заново
инспектирует обе tables, сохраняет первую и создаёт только вторую. Если обе
CREATE завершились, но последняя verification недоступна, первый run выдаёт
техническую ошибку; следующий exact repeat возвращает applied false. Проверка
failure должна использовать детерминированную public verification composition;
её exact API остаётся Gate 1 open item, без production runtime selector.

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
Пустой prefix разрешён, как в current runner; exact grammar
`^[A-Za-z0-9_]*$`, byte length 0..25.

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

- Подтвердить candidate version 12 по свежему registry и reconcile OpenSpec
  scheduling перед approval; не регистрировать её из этого draft.
- Независимо проверить public signature/results, CLI mapping и metadata/
  collation rules; определить deterministic verification API для interruptions
  и concurrent-run acceptance без предположения существующего runner lock.
- Сверить no-source schema path и importer characterization dependency;
  сохранить отдельный blocker на незавершённые importer behavior gates.
- Получить independent Gate 1 review и требуемое owner approval до RED.
- Затем demonstrated RED → independent Gate 3 → minimal GREEN → regression/
  architecture → independent Gate 5. Полный verify обязателен для integration.

Done требует data-free canonical creation, preservation/retry/conflict,
DDL-free runtime, approved importer regression, всех Gates/reviews и точной
проверки на integration SHA. Наличие этого draft не является Done.
