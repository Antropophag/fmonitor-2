# ASSIGNMENT-ORDER-SELECTION-SCHEMA-001

Версия0.1, 2026-09-06. DRAFT / independent Gate1 required.

## Простыми словами

Миграция готовит пять таблиц для выбора состава без PDF. Она не выбирает людей,
не создаёт распоряжение и не включает новый workflow. Повтор сохраняет факты и
счётчики; после прерванного создания дополняются только безопасные пустые таблицы.

## 1. Scope и authority

Actor — deployment operator в controlled DB с остановленными application writers.
Owner — InstallationProcess. Registry engine b6f619f и его existing Gate5 остаются
prerequisite; registry migration не повторно реализуется и не вызывается apply.
Selection v0.8 sections2–4/7–8/9.2 задают исходные значения; настоящий spec отдельно
фиксирует physical schema/coherence без объявления полного selection Gate1.

Canonical frontier сейчас13. Этот engine отключён: нет номера migration,
canonical runner/bootstrap/runtime/HTTP/factory callers или ready manifest.
All-writer exclusion, source reader, renderer, effective composition, opening и
canonical registration остаются отдельными gates. Никакого domain DML/repair.

## 2. Exact public seam

Namespace `FMonitor2\InstallationProcess`:

```php
final class AssignmentOrderSelectionSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array { /* normative facade */ }
    public static function isReady(\mysqli $connection, string $tablePrefix = ''): bool { /* normative facade */ }
}
enum AssignmentOrderSelectionSchemaPhase: string
{
    case LOCK_ACQUIRED='lock_acquired';
    case SELECTIONS_CREATED='selections_created';
    case MEMBERS_CREATED='members_created';
    case REQUESTS_CREATED='requests_created';
    case EVENTS_CREATED='events_created';
    case AUDITS_CREATED='audits_created';
    case FAMILY_VERIFIED='family_verified';
}
interface AssignmentOrderSelectionSchemaObserver
{
    public function observe(AssignmentOrderSelectionSchemaPhase $phase): void;
}
final class AssignmentOrderSelectionSchemaMigrationVerification
{
    public static function apply(\mysqli $connection, string $tablePrefix,
        AssignmentOrderSelectionSchemaObserver $observer): array { /* normative facade */ }
    public static function snapshot(\mysqli $connection, string $tablePrefix): AssignmentOrderSelectionSchemaSnapshot { /* normative facade */ }
}
final readonly class AssignmentOrderSelectionSchemaSnapshot
{
    /** @param list<AssignmentOrderSelectionSchemaTableSnapshot> $tables */
    public function __construct(public string $schemaSha256, public array $tables) {}
}
final readonly class AssignmentOrderSelectionSchemaTableSnapshot
{
    public function __construct(public string $tableName, public string $shapeSha256,
        public string $rowCount, public string $rowsSha256, public ?string $nextId) {}
}
```

Facade и verification вызывают один engine; production связывает inert observer.
Connection caller-owned: selected DB, utf8mb4 connection, REPEATABLE-READ session,
без active transaction; engine не меняет session isolation/charset и не закрывает
connection. Prefix0..25 `[A-Za-z0-9_]*`, PHP64. Invalid prefix для всех seams —
`InvalidArgumentException('Invalid selection schema migration configuration.')`
до SQL; invalid active/charset/isolation/database для apply — та же exception,
без lock/transaction control. Native connection failure — fixed unavailable.

apply returns только `['applied'=>true]` при создании хотя бы одной таблицы,
`['applied'=>false]` для compatible complete repeat, либо
`['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT']` при schema/data/prerequisite
conflict. SQL/observer/lock/cleanup failure бросает existing DatabaseUnavailable
с message `Assignment order selection schema unavailable.`, code0, previous=null.

isReady после prefix validation возвращает false при любых остальных invalid,
absent/incomplete/conflicting/unavailable states и не меняет caller transaction.
snapshot после prefix validation возвращает только полный compatible snapshot;
все остальные states дают fixed unavailable. isReady/snapshot выполняют только
read-only proof и owned read transaction, без migration lock, DDL/DML/repair.

## 3. Exact physical manifest

Нормативен `specs/fixtures/assignment-order-selection-schema-v1.json`, SHA256
`bd25c93c80d30c8d2146c7e54aa970caf4bef0d389006a8d270f81ea9991c28c`.
Он задаёт каждый ordinal column/type/nullability/default/extra/charset/collation,
ordered key columns/uniqueness, FK/action и named CHECK. @prefix заменяется полным
validated prefix; @database — текущая database; @collation — validated canonical
utf8mb4 database collation по existing IdentityAccessDefinitionSchemaMigration
policy. Table engine InnoDB. No extra columns/indexes/FKs/CHECKs/generated fields,
views/triggers/alternate FK actions. No explicit defaults; nullable implicit
NULL эквивалентен native SQL NULL metadata, не строковому default 'NULL'. Integer
display width нормализуется; retryable DDL допускает TINYINT(1), canonical tinyint.
Все index columns FULL/ASC/not ignored; prefix lengths/DESC/ignored не эквивалентны.

Порядок строго selections → members → requests → events → audits. Paired registry
FK имеет explicit supporting index(id,case), чтобы не зависеть от server naming.
FKs RESTRICT/RESTRICT; previous/replaces self-FKs не создают physical order row.
Event/audit AUTO_INCREMENT columns НЕ участвуют в CHECK; signed-PHP bounds и
capacity sentinel проверяются data proof. Счётчики не устанавливаются ALTER-ом.

Base lengths31/38/39/37/37, prefix25 даёт56/63/64/62/62. Все non-PRIMARY names из
manifest предваряются prefix; PRIMARY нет. Максимальное полное имя64. Имена не
усекаются/не хешируются/не выбираются сервером.

## 4. Fingerprint и row snapshot codec

Canonical shape — JSON объекта table из manifest в том же key order; @prefix
раскрыт, @database/@collation остаются markers ТОЛЬКО после проверки фактического
current DB/exact collation. indexes/foreignKeys/checks сортируются binary по
полным names. Columns сохраняют ordinal order. bool/null — JSON values.

CHECK expression заменяется canonical AST: удалить только redundant enclosing
parentheses; split top-level OR, затем AND (AND внутри BETWEEN не separator),
сохраняя порядок operands; nodes `["or",...children]`/`["and",...children]`.
Leaf `["atom",tokens]`: без whitespace/backticks/redundant atom parentheses,
case-insensitive SQL tokens lowercase; quoted literals/escapes/commas/operators
точно сохранены. Grouping AND/OR и literals significant: простое удаление всех
parentheses из всего predicate запрещено. Expressions вне expected AST conflict.
No algebraic commutative reorder или расширенная SQL equivalence угадайка.

Encoding compact UTF8 `JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE`, no finalLF.
shapeSha256 — SHA256 table JSON; schemaSha256 — SHA256 JSON array пяти shapes в
creation order. Metadata catalog сам служит независимым observation seam.

| Table suffix | prefix empty shape SHA256 | prefix 25 literal `ppppppppppppppppppppppppp` shape SHA256 |
|---|---|---|
| selections | 3d82bb6b567c380a142205c7c6ec1bb76a7d1ebd2ff3da33f6ef2ba3c7bbdc6c | 33bdb7e74e099afa009462fe193a34a029c35475927c34e444c52ac4071bf646 |
| members | 27c7abcf2091b421fbaee25118b608cc8ee67a48b85bc0c43b27a4c287bc9309 | c5fe9a5775ee08e8a7b961916bf7db74e6d7877307e2b574d1adf536332e9888 |
| requests | 54e72dfee77802b7337c18d8dc02b9e0bad21cbdf16e6f336d1c37fbc6b3bcaa | 3366814b3b01ea862584560452668eef84c02e7adc280875e11731a89accda31 |
| events | af0934ead1010273d76c339372b5b826a4e7a31f7049a7beb674c6c905ad88eb | bb7901749678cf0439a29c3822d48dd9474875b8c90379ee8e759ae8df25aa74 |
| audits | 800005376cb7c1cd062c5a500bbb65e0fa5ccee8f446a5130bfa036ec7ef16dd | 74e6ef71c1ab827ffc669580a2278cbbcdb4fa6c2667d95e65b8bead900571bb |

Family SHA empty-prefix `514247fd114cecd991da76aa4aa5b86c2e33164cce3fd085f64bbd38903b2bcb`;
prefix25 `36b6198ef1a3f704786c411cdc629b8b3c586037f19eab1b5cdfafc88645836f`.

Rows sorted by primary key: numeric components numeric ascending, request UUID
binary. Each row encodes JSON array of cells in ordinal column order + LF.
Every non-null cell is exact string, including canonical decimal numeric cells;
no float/overflowed int. DATETIME strings exact SQL YYYY-MM-DD HH:MM:SS.ffffff.
rowsSha256 hashes concatenated row lines; empty hash e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855.
rowCount canonical decimal string. nextId only events/audits, otherwise null.

## 5. Registry prerequisite и data coherence

В owned RR read-only snapshot проверить current canonical collation, exact
registry/receipt metadata и public isBackfillComplete===true. Missing/incomplete/
incompatible registry либо false public completion proof — conflict, не readiness.
Native catalog/snapshot query errors — unavailable. Public bool false не трактуется
как доказательство конкретной причины недоступности. Engine не вызывает registry
apply, не пишет/repair-ит receipt/identities/legacy/frontier.

Всю existing family проверить ДО первого DDL. Absent либо empty exact leading
prefix допустим только при отсутствии registry rows source_kind=selection.
Gap, any incompatible existing table либо nonempty partial → conflict без DDL.
Полная family допускает empty/populated только после следующего whole proof:

- Все numeric values lossless canonical decimals. Non-auto IDs1..PHP_INT_MAX;
  version1..65535, revision1..4294967295, expected revision0..4294967295. AUTO row
  IDs1..PHP_INT_MAX, nextId1..PHP_INT_MAX+1 и строго выше max row ID (empty max0).
  Last valid ID+exhausted sentinel — valid history, не способность к allocation.
- Каждому selection header соответствует ровно одна registry selection identity
  с exact id/case/version/allocatedAt. И наоборот, вся registry selection family
  имеет header. Global legacy/source readiness здесь не объявляется.
- Per-case selection revisions contiguous1..N; previous null для1, затем exact
  предыдущая selection identity той же case. Order version возрастает на1 после
  предыдущей selection; первая version>1 имеет immediate registry legacy predecessor.
  replace_pending требует revision>1/replaces=previous; new_order требует replaces null.
- Header/member snapshot texts valid UTF8, unchanged by trim Unicode separators
  plus U+0009..000D, nonempty; code-point bounds300/80. Dates real calendar;
  selected_at exact UTC seconds с .000000; selection_date — его Moscow date.
  Members1..500 unique ordered positive IDs, employed, valid period covering
  selection_date. SourceUpdatedAt valid RFC3339 instant<=40bytes, known offset,
  real calendar/time; fraction digits допустимы в пределах field size. Current
  HR/user/PTO/opening/original state не переоценивается.
- composition_identity exact composition-<id>-v<version>; SHA256 exact canonical
  `{caseId,compositionIdentity,engineerUserId,installers,orderId}` с numeric IDs.
- Каждый request содержит canonical installer JSON sorted unique0..500 IDs и
  exact operation fingerprint canonical intent section4 selectionv0.8. Actor/
  object positive, engineer positive/null, enum/status/reason closure exact.
  invalid_command/authorization_denied/request_id_conflict не terminal requests:
  их respective shape/no-terminal audit правила не допускают таких rows.
- Selected request/selection/event — взаимно one-to-one. Request intent mode,
  actor/engineer/IDs и expectedRevision=selectionRevision-1 совпадают; object ID
  равен immutable case legacy_installation_object_id. Все success fields exact,
  terminalAt=selectedAt. Nonselected success columns все null и reason допустим.
- Event имеет exact selected request/header echoes, type и occurredAt/actor,
  previous/replaces/hash; без extra event/orphan. Каждому terminal request ровно
  один matching audit с exact actor/object/mode/status/reason/terminal time.
  Дополнительные audits только rejected/authorization_denied (без требования
  request existence) или conflict/request_id_conflict (request обязан существовать).
  Их safe fields/UUID/time валидны; повторные denial audits разрешены. Нет других
  audit orphans/дубликатов terminal backing. Audit counter gaps сохраняются.

Алгоритм конечный, linear в прочитанных rows/bytes плюс numeric ordering; каждое
семейство читается один раз в PK order и hash-ится без recursive chain traversal.
No arbitrary row ceiling; память O(rows), чтение не повторяется per field.
Read inability/SQL error не выдаётся за malformed-data conflict. This immutable
coherence proof не доказывает историческую business eligibility/external original
применимость и не является all-writer readiness.

## 6. Lifecycle, lock и interruption

Named lock `fm2_aoss_` + first48 lowerhex SHA256(databaseName+NUL+prefix),57bytes.
GET_LOCK(name,5) ровно1 acquired; 0/NULL/error → unavailable до mutation. Другой
prefix независим. После acquisition observer LOCK_ACQUIRED, затем один owned
read-only consistent snapshot/preflight. Rollback этого read transaction checked
до DDL. Connection остаётся caller-owned, чужая transaction не завершается.

При allowed partial создать only missing suffix по manifest, checked native true.
После каждого durable CREATE — соответствующий *_CREATED phase; на repeat этих
phases нет. DDL implicit commits: rollback DDL не обещается, созданные tables не
удаляются. Затем fresh owned read-only full proof, checked rollback и FAMILY_VERIFIED;
только после этого selected success/repeat. Если final proof после DDL не проходит,
unavailable; сохранённые tables доступны следующему exact recovery.

finally пытается rollback только реально acquired read transaction и RELEASE_LOCK
ровно once после acquisition. Release result !=1/false/error либо observer/native
failure → fixed unavailable, без повторного DDL. Все prepared/results/resources
закрываются; caller connection/charset/isolation/frontiers сохраняются. Observer
может бросить на любой phase; после durable DDL эффект остаётся, retry создаёт
only suffix. Child termination после *_CREATED освобождает connection lock; no
write transaction/data facts. No application writer protection выводится из lock.

isReady/snapshot используют тот же full proof в own read-only transaction без
migration lock; caller active state →false/unavailable, zero control/mutation.
Snapshot выдаётся только после successful read transaction release.

## 7. Независимый example и минимальная matrix

Нормативен `specs/fixtures/assignment-order-selection-example-v1.json`, SHA256
`d7ba5056b7de298631152a181e898895f5a18bfb19de0479bd96878fc8eb209b`.
Setup: case4512/object4512, empty physical order history с frontier81; approved
registry apply создаёт immutable empty receipt (count/max0, next/preserved81,
оба hashes empty). Затем synthetic schema fixture строит example rows: registry
81v1/82v2 selection, selected new_order и replace_pending, rejection без engineer,
2denials одного request и request-id conflict audit. Registry next90, event9,
audit11. Fixture DDL/DML — только setup/negative mutation в task-owned database,
не production selection command и не обход его Gate1.

Expected counts: selections2/members2/requests3/events2/audits6; exact row hashes
в example.expected. Empty family rows hashes empty, event/audit next1. Registry/
receipt/physical case/order/member/artifact and foreign-decoy snapshots unchanged
by apply/readiness/snapshot, включая AUTO_INCREMENT counters.

Mandatory bounded tests: clean/repeat; prefix0/25 and26 beforeSQL; each empty
leading partial; gap; nonempty partial; wrong metadata/literal/AND-OR grouping;
missing/incomplete registry; example populated repeat, one corrupt composition/
request/event/audit/registry echo each; nextId boundaries and no counter reset;
same-prefix lock timeout5s±2/other-prefix progress; observer exception each phase,
child stop after durable CREATE; native denied DDL и repeat with denied DML;
caller transaction protection; release failure via observer releasing its own
lock; snapshot/read unavailable; attempt-all cleanup and decoy preservation.

Expected manifest/rows берутся из spec fixtures, не PHP production constants.
Прежние registry tests/approvals переиспользуются; regression нужен только для
реально затронутой shared code. No new>=150-line production file/baseline growth.
Gates1→RED→independent Gate3→minimal disabled GREEN→architecture/lint/OpenSpec→
independent Gate5. Tasks integration/Done не закрываются engine-only approval.
