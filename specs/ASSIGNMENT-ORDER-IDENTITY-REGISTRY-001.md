# ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001 — сохранить идентичности распоряжений

Версия0.1, 2026-09-05. **DRAFT / INDEPENDENT GATE 1 REQUIRED**.

## Простыми словами

Перед появлением выбора состава без PDF система переносит существующие номера
внутренних записей в общий registry. Исторические распоряжения, даты и составы
не меняются. Этот срез готовит metadata engine; сам по себе он не включает
новый workflow и не разрешает продолжать работу несовместимому старому writer.

## 1. Actor, scope и public seam

Actor — технический deployment operator, без новых пользовательских полномочий.
Изолированный controlled database, application writers остановлены оператором.
Нет production import, real documents, PII или внешней системы в verification.
DDL/backfill выполняет только production migration owner:

```php
namespace FMonitor2\InstallationProcess;
final class AssignmentOrderIdentityRegistryMigration
{
    /** @return array{applied:bool,reason?:string} */
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array { /* normative facade */ }
    public static function isBackfillComplete(\mysqli $connection, string $tablePrefix = ''): bool { /* read-only */ }
}
```

No runtime/HTTP entrypoint вызывает migration. `isBackfillComplete` означает
только exact family + historical receipt, не full selection/writer readiness.
Canonical runner не регистрирует engine до отдельного writer-cutover Gate5.
На base `bebb23e` registry runner contiguous1–12; номер следующей migration
выбирается и фиксируется отдельным registration amendment на актуальном HEAD.

## 2. Input/result и errors

Connection — caller-owned open mysqli, выбранная database, charset utf8mb4,
нет активной caller transaction. Prefix0..25 ASCII bytes `[A-Za-z0-9_]*`.
Неверный prefix или caller transaction →
`InvalidArgumentException('Invalid registry migration configuration.')` до DDL/DML.
Connection не закрывается engine. Database default — existing canonical
utf8mb4/collation contract; unsupported defaults — schema conflict.

Success с любой созданной family table или новым backfill:
`['applied'=>true]`. Complete repeat: `['applied'=>false]`.
Existing schema/data conflict, inconsistent receipt или initial capacity
exhaustion: `['applied'=>false,'reason'=>'SCHEMA_MIGRATION_CONFLICT']`.
SQL/lock infrastructure failure →
`DatabaseUnavailable('Assignment order identity registry unavailable.')`,
без previous exception/SQL/path. Во всех случаях attempt-all release owned
transaction и migration lock; cleanup failure не скрывается success.
`isBackfillComplete` read-only, возвращает false для absent/incompatible/incomplete
family или read/query failure, ничего не создаёт и не ремонтирует.

No domain audit: metadata backfill не является новой selection/registration.
Не менять исходные order/member/artifact/process/original rows или их counters.

## 3. Exact family schema

Обе таблицы InnoDB. Text hashes/discriminator — ASCII `ascii_bin`; остальное
numeric/datetime. Table default utf8mb4 с validated canonical database collation.
Все fields NOT NULL, кроме явно разрешённых; никаких дополнительных колонок,
индексов, defaults, generated expressions, triggers или FK actions нет.

### Registry: `{prefix}fm2_assignment_order_identities`

| Column, ordinal order | Exact SQL type / extra |
| --- | --- |
| assignment_order_id | BIGINT UNSIGNED AUTO_INCREMENT |
| installation_case_id | BIGINT UNSIGNED |
| order_version | SMALLINT UNSIGNED |
| source_kind | VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin |
| allocated_at_utc | DATETIME(6) |

Keys/constraints (каждое non-PRIMARY name предваряется полным prefix):

| Name | Exact definition |
| --- | --- |
| PRIMARY | (assignment_order_id) |
| fm2_aoir_uq_case_version | UNIQUE(installation_case_id,order_version) |
| fm2_aoir_uq_id_case | UNIQUE(assignment_order_id,installation_case_id) |
| fm2_aoir_ix_case_source | INDEX(installation_case_id,source_kind,order_version) |
| fm2_aoir_fk_case | FOREIGN KEY(installation_case_id) REFERENCES `{prefix}fm2_installation_cases`(id) ON UPDATE RESTRICT ON DELETE RESTRICT |
| fm2_aoir_ck_case | CHECK(installation_case_id BETWEEN 1 AND 9223372036854775807) |
| fm2_aoir_ck_version | CHECK(order_version BETWEEN 1 AND 65535) |
| fm2_aoir_ck_source | CHECK(source_kind IN ('legacy_order','selection')) |

AUTO_INCREMENT ID намеренно не участвует в CHECK: MariaDB запрещает такое
определение. Positive/signed-PHP range сохраняется public migration preflight,
registry allocator pre-insert/pre-commit validation и read integrity checks;
нет успеха с ID0 или >PHP_INT_MAX. Physical type остаётся BIGINT UNSIGNED для
совместимости будущих selection FKs. Прямая чужая SQL-запись invalid ID не
считается допустимым command и делает integrity check false/conflict.
Источник: [MariaDB constraints](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint).

### Receipt: `{prefix}fm2_assignment_order_id_receipts`

| Column, ordinal order | Exact SQL type |
| --- | --- |
| singleton_id | TINYINT UNSIGNED |
| format_version | SMALLINT UNSIGNED |
| legacy_max_id | BIGINT UNSIGNED |
| legacy_next_id | BIGINT UNSIGNED |
| preserved_next_id | BIGINT UNSIGNED |
| legacy_row_count | BIGINT UNSIGNED |
| legacy_tuple_sha256 | CHAR(64) CHARACTER SET ascii COLLATE ascii_bin |
| legacy_prepared_sha256 | CHAR(64) CHARACTER SET ascii COLLATE ascii_bin |

| Name | Exact definition |
| --- | --- |
| PRIMARY | (singleton_id) |
| fm2_aoir_receipt_ck_one | CHECK(singleton_id=1 AND format_version=1) |
| fm2_aoir_receipt_ck_bounds | CHECK(legacy_max_id BETWEEN 0 AND 9223372036854775807 AND legacy_next_id BETWEEN 1 AND 9223372036854775807 AND preserved_next_id BETWEEN 1 AND 9223372036854775807 AND legacy_row_count BETWEEN 0 AND 9223372036854775807) |
| fm2_aoir_receipt_ck_frontier | CHECK(preserved_next_id>=legacy_next_id AND preserved_next_id>legacy_max_id) |
| fm2_aoir_receipt_ck_tuple | CHECK(legacy_tuple_sha256 REGEXP '^[0-9a-f]{64}$') |
| fm2_aoir_receipt_ck_prepared | CHECK(legacy_prepared_sha256 REGEXP '^[0-9a-f]{64}$') |

Receipt — одна immutable completion row. Нет timestamp/actor/domain event,
mutable progress cursor или UPDATE receipt. Данные backfill и receipt
фиксируются одной transaction. Любые registry rows без receipt — conflict,
кроме полностью пустого registry до первого backfill. Partial committed rows
не усыновляются как якобы собственный незавершённый batch.

Shape oracle сравнивает ordinal column metadata/type/nullable/charset/collation/
default/extra; exact ordered index columns/unique/name; FK source/target/actions;
named CHECK semantics. MariaDB display parentheses/keyword case не значимы,
но literals, AND/OR grouping, operands и predicate operators значимы.
Server-generated duplicate FK support index не допускается: declared indexes
уже покрывают leading installation_case_id.

## 4. Prefix и lock identity

Ни один table/index/constraint identifier не превышает64 ASCII bytes при
prefix25. Base `fm2_assignment_order_identities` —31 bytes (56 с prefix25),
`fm2_assignment_order_id_receipts` —32 (57). Максимальный non-PRIMARY constraint
base `fm2_aoir_receipt_ck_prepared` —28 (53); `fm2_aoir_uq_case_version` —24 (49).
Все имена таблиц/индексов/constraints раздела3 проверяются independent literal
length inventory при Gate1. Имена используются буквально, без server-chosen FK names.

Named migration lock:
`fm2_aoir_` + first48 lowercase hex SHA256(databaseName + NUL + tablePrefix).
Длина57 bytes. `GET_LOCK(name,5)` ровно1 — acquired; 0/NULL/query failure —
DatabaseUnavailable до mutation. RELEASE_LOCK выполняется ровно один раз после
acquisition; result не1/error → DatabaseUnavailable. Prefixes используют разные
locks; два мигратора одной family сериализованы. Lock не объявляется защитой от
старого application writer, который этот protocol не знает.

## 5. Historical preflight и canonical bytes

До первого DDL/DML проверить обе existing family tables целиком, exact
predecessor physical order/case shape из `MIGRATION-PROCESS-001` section3
(только две именованные строки таблицы и соответствующие keys/FKs) и
`PRODUCTION-MIGRATION-RUNNER-001` section5.1 normalization,
legacy IDs/case/version/prepared_at и orphan/duplicate conditions.
Нормативны ordinal fields/types/nullability/AUTO_INCREMENT, InnoDB/utf8mb4,
PK/unique/secondary/FK ordered tuples и RESTRICT actions. Source index names
не сравниваются, support index previous_assignment_order_id обязателен;
source CHECK отсутствуют. Extra/missing columns/indexes/FKs — conflict.
Ожидаемые tuples транскрибируются из этих spec tables, не извлекаются из PHP
implementation или результата migration. Любой conflict
не создаёт отсутствующий sibling. Полностью absent family разрешена.

IDs/case IDs в `1..9223372036854775807`, version1..65535, unique ID и case/version,
ровно один existing case. Values читаются lossless decimal strings; PHP float
или overflowed int не участвуют в сравнении/hash/frontier.
`prepared_at` — valid RFC3339 с seconds, optional1..6 fractional digits и
known numeric offset либо Z; unknown offset `-00:00`, invalid calendar/time,
offset за пределами ±14:00, extra text/whitespace запрещены. Preserve exact raw
source bytes. allocated_at_utc — тот же instant UTC с ровно6 fractional digits,
не migration time и не document date.

Исторические строки сортируются numeric id ascending. Tuple bytes — для каждой
строки ASCII `id,caseId,version` + LF. Prepared bytes — ASCII
`id,utcYYYY-MM-DDTHH:MM:SS.ffffffZ` + LF. Hash — lowercase SHA256 всех bytes,
для пустого набора SHA256 empty string. Здесь case/version фиксируются
десятичными цифрами без ведущих нулей; UTF-8 text не попадает в hash.

Capture legacy AUTO_INCREMENT как positive decimal; NULL/zero — conflict.
Initial frontier = max(legacy_next_id, legacy_max_id+1,
existing_empty_registry_AUTO_INCREMENT). Empty legacy max=0. Если family новая,
последний operand=1. Initial frontier >9223372036854775807 → conflict до DDL/DML.
Не lowering уже существующий registry frontier, даже после aborted migration.

## 6. Backfill и interruption

После whole-family preflight создать только отсутствующие exact empty tables;
при их наличии shape не изменяется. До backfill установить registry next ID на
вычисленный frontier, если текущее значение меньше. Этот DDL не меняет legacy
frontier и не создаёт business facts. После этого begin one owned transaction:
прочитать/lock source snapshot и перепроверить capture, вставить exact registry
rows как legacy_order и completion receipt; commit once. Ни один stage/caller
не выполняет самостоятельный commit.

Если source capture изменился после DDL, rollback DML и DatabaseUnavailable;
допустимы уже созданные пустые family tables/frontier. Внешнее условие остановки
writers не отменяет эту проверку. Legacy source не repair/update-ится.

Crash после table creation/frontier change: empty compatible family повторяется.
Crash внутри transaction: MariaDB rollback оставляет пустые rows/receipt;
повтор не создаёт дублей. Lost acknowledgement после commit: следующий вызов
доказывает complete receipt и возвращает applied false. Unknown commit текущего
вызова не превращается в success: DatabaseUnavailable, без mutation retry.

При существующем receipt никаких DDL/frontier repair не выполняется.
Historical physical/registry subset id<=legacy_max_id обязан совпасть с receipt
count/tuple/prepared hashes и registry source_kind=legacy_order. Registry row
allocatedAt обязан совпадать с сохранённым source instant. Extra identity ниже
historical upper bound — conflict. Current frontier >=preserved_next_id и выше
всех registry IDs; при позднем ID=PHP_INT_MAX допустим exhaustion sentinel
next=9223372036854775808. Это complete history, но не возможность новой allocation.
Later rows выше historical max не меняют receipt; full live source ownership
и writer readiness проверяет отдельный registry-aware release contract.

## 7. Independently fixed acceptance

Synthetic cases4512/4513. Legacy rows, insertion order7 then2:

| ID | Case | Version | prepared_at | Expected registry allocatedAt UTC |
| --- | --- | --- | --- | --- |
| 2 | 4512 | 1 | 2026-08-27T12:30:00+03:00 | 2026-08-27 09:30:00.000000 |
| 7 | 4513 | 3 | 2026-08-28T10:15:00Z | 2026-08-28 10:15:00.000000 |

Legacy next ID81. Exact receipt: singleton1, format1, max7, legacy_next81,
preserved_next81, count2; literal tuple hash
`8159e7f3e55b317c01056ec6c7172e2c9bca8a798c0bc38408b6b6df1d71be86`,
prepared hash `a5506e2a71f414d4667cc95d1446155f69d9156ecf87e51bf9d7ec485997be53`.
Empty hash для обеих columns:
`e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`. Registry содержит только2/7, оба legacy_order; source snapshots,
legacy frontier81, source order/member/artifact/event rows unchanged.

Mandatory matrix: fresh empty next81; populated example; complete repeat;
prefix0/25 success and26 fail-before-access; each one-table-empty partial;
wrong sibling shape preventing creation; conflicting receipt/hash/preparedAt;
nonempty registry without receipt; malformed/orphan/out-of-range source;
initial frontier max/max+1 boundaries; late legitimate registry identity above7
preserves frozen receipt; forbidden historical change below/equal7;
two migrators same prefix, independent prefixes, lock timeout/release failure;
interrupt each DDL/transaction phase, commit response-loss classification;
real DDL/DML denied principal and attempt-all owned cleanup/foreign-decoy preservation.

### 7.1 Explicit verification composition

Namespace тот же, все declarations syntax-valid placeholders:

```php
enum AssignmentOrderIdentityRegistryPhase: string
{
    case LOCK_ACQUIRED='lock_acquired';
    case REGISTRY_CREATED='registry_created';
    case RECEIPTS_CREATED='receipts_created';
    case FRONTIER_READY='frontier_ready';
    case BEFORE_BACKFILL_COMMIT='before_backfill_commit';
    case BACKFILL_COMMITTED='backfill_committed';
}
interface AssignmentOrderIdentityRegistryObserver
{
    public function observe(AssignmentOrderIdentityRegistryPhase $phase): void;
}
final class AssignmentOrderIdentityRegistryMigrationVerification
{
    /** @return array{applied:bool,reason?:string} */
    public static function apply(\mysqli $connection, string $tablePrefix,
        AssignmentOrderIdentityRegistryObserver $observer): array { /* same engine */ }
    public static function snapshot(\mysqli $connection, string $tablePrefix): AssignmentOrderIdentityRegistrySnapshot { /* read-only */ }
}
final readonly class AssignmentOrderIdentityRegistrySnapshot
{
    /** @param list<AssignmentOrderIdentityRegistryRow> $identities */
    public function __construct(public array $identities,
        public ?AssignmentOrderIdentityRegistryReceipt $receipt,
        public string $nextId) {}
}
final readonly class AssignmentOrderIdentityRegistryRow
{
    public function __construct(public string $id, public string $caseId,
        public int $version, public string $sourceKind, public string $allocatedAtUtc) {}
}
final readonly class AssignmentOrderIdentityRegistryReceipt
{
    public function __construct(public int $formatVersion, public string $legacyMaxId,
        public string $legacyNextId, public string $preservedNextId,
        public string $legacyRowCount, public string $tupleSha256,
        public string $preparedSha256) {}
}
```

Snapshot only for structurally valid existing family; otherwise same fixed
DatabaseUnavailable. IDs/count/frontiers — canonical decimal strings, versions
PHP ints, UTC instant exact `YYYY-MM-DDTHH:MM:SS.ffffffZ`; identities sorted ID
numeric ascending, receipt null for empty table. Snapshot validates raw types,
not history completion: it can show malformed receipt values as typed fields
without declaring them valid. Public standard MariaDB catalog отдельно служит
schema/preservation observation seam, как в MIGRATION-PROCESS-001. No private
method/reflection/production-constants expected manifest.

Production facade всегда связывает inert observer; нет env/HTTP fault selector.
Engine/DDL/transaction owner один и тот же в обеих compositions. LOCK_ACQUIRED
после actual GET_LOCK=1, до reads/preflight; *_CREATED только после подтверждённого
успешного DDL соответствующей таблицы, в порядке registry затем receipts,
не на repeat. FRONTIER_READY только для fresh empty backfill после всех DDL и
перед begin; BEFORE_BACKFILL_COMMIT после staging всех rows+receipt в одной
transaction, перед единственным commit; BACKFILL_COMMITTED только после
подтверждённого commit, до lock release. Complete repeat emits только lock phase.

Observer Throwable → same DatabaseUnavailable после attempt-all cleanup.
До commit staged facts rollback; после commit они остаются и следующий repeat
подтверждает receipt. Это отдельно доказывает response-loss boundary, не
подменяет его false success. Public caller connection остаётся открытым.

Interruption fixtures используют только этот schema observer: controlled throw
на каждой фазе и отдельный child termination на BEFORE_BACKFILL_COMMIT через
task-owned pipe barrier. Parent ждёт phase не более5s и обычное completion30s,
затем TERM/reap1s, при необходимости KILL/reap1s; все deadlines monotonic.
Setup/deadline failure остаётся failure, cleanup пытается удалить только exact
owned DB/user/resources и сохраняет foreign decoy. Standalone test может иметь
несколько последовательно ограниченных invocations, без гонки по sleep.

Concurrent migration test: first child удерживает lock на LOCK_ACQUIRED phase
barrier; second same-prefix получает lock timeout за5s±2s и DatabaseUnavailable
без DDL; после release first завершает, subsequent repeat no-op. Separate-prefix
child завершается до release first и изменяет только свою family. Release-failure
verification observer на BACKFILL_COMMITTED освобождает собственный named lock
через тот же supplied connection; engine RELEASE_LOCK then non1 обязан вернуть
DatabaseUnavailable, committed receipt/history остаются. Этот schema fixture
не относится к отклонённым safe-log mechanisms.

Gate1 ещё требует независимого review exact bytes; initial spec не разрешает RED.

## 8. Release boundary

Этот standalone engine contract не выполняет cutover. No engine/metadata GREEN
может закрыть parent selection Gate1 или разрешить production registration.
Необходимы exact all-writer exclusion/allocator protocol, selection-family schema,
original-reader amendment, same-identity optional-render contract, canonical
registration и same-SHA full verification. До этого source application writers
не переключаются на registry и migration не запускается штатным runtime.
