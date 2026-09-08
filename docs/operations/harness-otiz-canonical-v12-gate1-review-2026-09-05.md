# HARNESS-OTIZ-CANONICAL-V12-001 v0.1 — independent technical Gate 1 review

Date: 2026-09-05. Reviewer: `/root/selection_contract_reconciliation`.
Reviewed repository HEAD: `82d283c9dc5714ec3ecd98d197e3acfc4a433daa` plus the
root-authored draft identified below. The reviewer authored none of the reviewed
specification, existing harness, inherited contracts, schema owner, engine,
runner, Makefile, support, or protected E2E artifacts.

Verdict: **APPROVED**.

This is technical Gate 1 approval for the bounded test-only OTIZ canonical-v12
compatibility amendment. It permits preparation of an unapplied exact patch and
focused RED for fresh independent Gate 3 review. It is not Gate 3, permission to
apply an unreviewed patch, GREEN, Gate 5, parent OpenSpec completion, integration
approval or launch approval. It adds no product/financial behavior and requires
no new product-owner decision.

## Technical assessment

- The inherited public contract already requires successful canonical migration
  before the compatibility harness. On that accepted input, its internal
  `make migrate` is deterministically a repeat and must return exactly status 0,
  empty stderr and the single stdout line
  `{"ok":true,"schemaVersion":12,"appliedVersions":[]}` plus LF. Comparing the
  whole process result closes the current arbitrary-subset/range acceptance and
  rejects extra output, missing/extra JSON keys, nonempty applied versions,
  another terminal version or process failure.
- This exact repeat is constructible through the existing Makefile: the migrate
  recipe suppresses command echo and invokes the real canonical runner against
  the configured disposable test database. No output-tail extraction or
  implementation-derived expected range is needed. The existing
  `hoccMigrationResult()` may be removed or left unused by the proposed patch;
  it must not remain the acceptance oracle for the migration result.
- The two additions to `hoccCanonicalState()` are exactly
  `fm2_pilot_object_details` and
  `fm2_pilot_object_detail_quarantine`. The existing observer records each
  table's SHOW CREATE definition and all ordered rows, so both definitions and
  any pre-existing synthetic rows are preserved across the two successful OTIZ
  runs, injected failure and final cleanup. The harness creates no rows in these
  tables and does not add them to the auto-increment restore allowlist because
  neither has an auto-increment column.
- Existing sentinel installation/removal, financial transcript, permissions,
  private-table cleanup, injected exact regression failure and original
  canonical restoration remain unchanged. Only the migration prerequisite,
  two preservation identities and v1–v12 labels are within scope. No importer,
  production code, user, real data, finance assertion, hash rule or protected
  E2E dependency may change.
- Missing/unreadable v12 tables remain setup failure through the existing
  canonical-state observer. Any definition/row/counter mutation or changed
  child transcript remains regression failure. No skip, xfail, broad version
  comparison, automatic fixture insertion or AI-authored restoration path is
  admitted.

## Unprepared-input caveat

The public seam has a caller precondition: canonical v12 has already completed.
If someone invokes this harness directly on an unprepared database, its existing
internal `make migrate` can durably apply missing canonical migrations before the
harness observes nonempty `appliedVersions` and reports setup failure. That
behavior is not a newly authorized repair operation and is not an accepted test
input; it is the existing real migration child acting before prerequisite proof.

Gate 3 must therefore demonstrate RED on an independently prepared exact v12
database with an external exact no-op migration proof. It must not use an
unprepared database whose in-harness mutation manufactures the predecessor.
The final characterization/full-verify order must retain canonical migration
before this harness. If a future contract requires standalone nonmutating
precondition inspection, that is a separate seam and gate.

## Required delivery sequence

After this approval, capture the healthy prepared-v12 old-harness RED caused by
expected 11 versus actual exact repeat 12. Prepare the exact unapplied test-only
patch. A fresh independent Gate 3 reviewer must inspect this specification, RED
and patch before application. Minimal GREEN then runs the real harness, both
successful OTIZ invocations and injected-failure cleanup, relevant
characterization, architecture/lint/diff checks, followed by fresh Gate 5.

The sibling eleven-target fixture patch has its own Gate 3 and must not absorb
this harness amendment. Parent tasks 4.1/4.2 and full `make verify` remain
incomplete until both independently gated corrections and all real regressions
are green.

## Exact reviewed SHA-256

```text
f0c2e1119ef37f149fc0355c5c6de36edc0ca6afe31f8e4655ccd8d0dd39afc2  specs/HARNESS-OTIZ-CANONICAL-V12-001.md
dd2c8cd847332b950318206777cae7a3b823958aea0018aef9e5225170f44a30  tests/Verification/harness_otiz_canonical_compat_001_test.php
fa623b9ddef906f3d621e58f0b1e0015d62acc25e5b9f70a7adee79a9ab284b8  specs/HARNESS-OTIZ-CANONICAL-COMPAT-001.md
4d07510c7b504595e6a15f8be234d1b71c0e8969d02e7a1d1edcc317bb990d24  specs/HARNESS-CANONICAL-MIGRATION-STAGE-001.md
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
02e1476203b4646a271f23e3047735ec1ced4de573cbad5b0145df4fe1a88fd8  reviews/code/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001-schema-engine-v1.md
edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd  tools/verification/run.sh
df42826cf5fdd1af6a711f268a2dc79cc0b5b14bd6b0808665839b779ef6ac15  Makefile
c5ae5aefbe8ce031fdea764b57cb7a60d7d9ce5169caf9b4a14f2bcb176afc83  tests/Verification/harness_otiz_isolation_001_test.php
dddec91ba654b1503e4051cd732325a9fed7ff166a1d3ff2cb101b9593f6c0b3  tests/Support/ProductionMigrationRunnerCatalogContract.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```
