## Context

См. `proposal.md`, capability spec и два object-detail evidence documents.
Existing verifier статически ищет source tokens; он не запускает operator seam.
Importer требует guarded target generation и отдельный legacy-like source, а
apply сейчас выполняет DDL до transactional DML.

## Goals / Non-Goals

**Goals:**

- Executable serial oracle через настоящий CLI child process.
- Independently calculated payload/hash/count assertions и full-family snapshots.
- Private source database plus isolated target generation/prefix across two DB
  connections, deterministic rerun и bounded cleanup.
- Canonical characterization-stage integration без изменения importer.

**Non-Goals:**

- Canonical migration, DDL-denied importer и schema compatibility RED.
- Target application module или product command: actor остаётся operator.
- Concurrent race, transitions, reconciliation, integrity hardening и cutover.

## Decisions

### 1. Real CLI is the public observed seam

Verifier запускает `import-production-object-details.php` отдельным process с
private manifest/env и literal args. Direct function extraction или duplicate
implementation не докажет wiring, guard и transaction boundary.

### 2. Harness owns fixtures, importer remains untouched

Test setup создаёт минимальные legacy metadata/dictionary/main tables, guarded
target generation/cases и текущую exact object-detail family. Это допустимый
test DDL owner; runtime DDL не считается одобренным и удаляется в следующем
schema-ownership slice.

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
и consumers не меняются. Architecture baseline не должен расти: production DDL,
SQL ownership и rapid-pilot mutation counts остаются прежними.

## Risks / Trade-offs

- [Fixture accidentally reproduces importer logic] → literal worked example и
  independent hash construction reviewed before GREEN.
- [Auto-committed runtime DDL pollutes failure evidence] → precreate exact family,
  fingerprint it and clean private namespace; DDL denial deferred explicitly.
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

Rollback removes verifier registration and test-only files; production importer,
schema and data are unchanged by this characterization slice.
