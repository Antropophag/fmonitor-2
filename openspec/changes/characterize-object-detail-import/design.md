## Context

См. `proposal.md`, capability spec и два object-detail evidence documents.
Existing verifier статически ищет source tokens; он не запускает operator seam.
Importer требует guarded target generation и отдельный legacy-like source, а
apply сейчас выполняет DDL до transactional DML.

## Goals / Non-Goals

**Goals:**

- Executable serial oracle через настоящий CLI child process.
- Independently calculated payload/hash/count assertions и full-family snapshots.
- Новый disposable MariaDB server на каждый run, private source и target
  `fmonitor2_demo` с isolated generation/prefix, deterministic rerun и cleanup.
- Canonical characterization-stage integration без изменения importer.

**Non-Goals:**

- Реализация canonical migration и production no-DDL correction (принадлежит
  `canonicalize-object-detail-snapshot-schema`, использующему shared RED oracle).
- Target application module или product command: actor остаётся operator.
- Concurrent race, transitions, reconciliation, integrity hardening и cutover.

## Decisions

### 1. Real CLI is the public observed seam

Verifier запускает `import-production-object-details.php` отдельным process с
private manifest/env и literal args. Direct function extraction или duplicate
implementation не докажет wiring, guard и transaction boundary.

### 2. Harness owns fixtures, importer remains untouched

Test setup создаёт минимальные synthetic metadata/dictionary/main tables и guarded
target generation/cases. Family создаётся только public v12 migration owner;
duplicate family CREATE в harness запрещён. Negative fixtures удаляют exact
member либо меняют captured_at на VARCHAR(41), как в v0.2 spec. Importer child
получает только exact SELECT/INSERT grants; privileged setup principal отделён.
Runtime DDL удаляется отдельно gated schema-ownership fix.

### 3. Expected evidence is independently constructed

Expected canonical material/hash строится test-side из literal fields по
нормативно записанному алгоритму. Full raw rows и schema fingerprints снимаются
до/после repeat/conflict, чтобы aggregate output не мог скрыть mutation.

### 4. One verifier owns several serial scenarios

Один bounded verifier переиспользует fixture builders для clean/repeat,
conflict, incomplete metadata и unknown dictionary scenarios. Concurrency не
добавляется: она существенно увеличивает harness и остаётся UNKNOWN.

### 5. Isolation follows existing characterization conventions

Names используют validated random suffix и collision refusal; ownership marker
и explicit name set ограничивают cleanup. Cleanup выполняется в `finally`, decoy
namespace проверяется после success/failure. Transcript нормализует только
test-owned random identifiers, не behavioral values.

### 6. Ownership and dependency boundaries

Verification code владеет только oracle fixtures и зависит от public CLI/DB
contracts. Rapid-pilot остаётся observed strangler adapter; application modules
и consumers не меняются. Architecture baseline не должен расти. Его уменьшение
принадлежит schema-transfer change после фактического удаления двух CREATE.

## Risks / Trade-offs

- [Fixture accidentally reproduces importer logic] → literal worked example и
  independent hash construction reviewed before GREEN.
- [Auto-committed runtime DDL pollutes failure evidence] → canonical precreate,
  DDL-denied child, full independent snapshots; no-DDL RED обязателен отдельно
  от missing-verifier meta-test.
- [Failure leaks schema artifacts] → collision refusal plus `finally` cleanup and
  second clean run.
- [Exception text varies] → pin stable domain category/token and exit outcome,
  not stack trace formatting.
- [Characterization blesses pilot semantics] → PILOT_ONLY label and exhaustive
  exclusions in spec/review.

## Migration Plan

1. Write stable executable spec and obtain Gate 1 approval.
2. Add a RED meta-test that proves the executable oracle is absent/inadequate;
   capture intended assertion failure and obtain fresh Gate 3 review.
3. Implement only verifier/runner registration, then run focused and canonical
   characterization suites twice.
4. Run architecture/regression verification and obtain fresh Gate 5 review.

Shared axis v0.2 требует apply/dry-run exact family precondition после generation
guard и до source connection/DML. Refusal: exit 2, fixed JSON
OBJECT_DETAIL_SCHEMA_REQUIRED, empty stderr. Zero source access наблюдается
готовым owned loopback listener с independently proven control connection.
Точный protocol, bounded execution/cleanup следуют executable spec. Serial
cases идут под DDL-denied principal; engine GREEN не закрывает этот oracle.

Rollback removes verifier registration and test-only files; production importer,
schema and data are unchanged by this characterization slice.
