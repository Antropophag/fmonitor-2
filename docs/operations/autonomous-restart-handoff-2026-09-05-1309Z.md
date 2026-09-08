# FMonitor 2.0 — autonomous restart handoff

Recorded: 2026-09-05T13:09Z. Author: `/root`.
Stop requested explicitly by owner to restart the session. This is not a
completion claim, launch approval, or a reduced goal.

## 1. Full persistent goal and restart policy

Immediately establish/reuse this persistent goal in the new session:

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до
> проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery
> gates, append-only evidence, exact-SHA verification, CI, clean
> deployment/restart/golden-path и отсутствие launch blockers.

Call get_goal first; create_goal with the full objective if no unfinished goal
exists. No token budget was requested. Do not mark complete at a partial GREEN,
commit, review, VERIFY_OK or golden-path substep. Continue safe READY work when
one slice is blocked. Ask only for genuinely new authority/product decisions;
do not resubmit already approved decisions or infer approval from automatic
goal continuation messages.

Read AGENTS.md, PRODUCT.md, CONTEXT.md, docs/fmonitor-2-pilot-spec.md,
docs/fmonitor-2-pilot-data-model.md and docs/development-process.md before product
work. Follow local rapid-pilot instructions. Re-read actual state before relying
on this record; append corrections rather than rewriting prior evidence.

Models: owner requested root GPT-6 Astra default and subagents GPT-5.6 Sol low.
Observed ~/.codex/config.toml: model=gpt-6-astra, reasoning=medium. This session
used explicit spawn model=gpt-5.6-sol, reasoning_effort=low, with a limited/none
fork (full-history fork cannot override model). No global subagent-model config
key was installed. Use explicit overrides for each new agent.

## 2. Frozen factual state

- Repository: `/Users/antropophag/code/fmonitor-2`.
- Branch: `codex/remove-pilot-work-navigation-v2`.
- HEAD immediately before this handoff commit:
  `da26f9e9bb291bbd287960cc17a20910be421d6f`.
- Worktree clean before writing this record; ahead 500, behind 0 relative to
  recorded origin. The containing handoff commit adds one commit; use actual
  HEAD after restart, not the pre-handoff SHA above.
- Live remote integration HEAD:
  `75a642476224abe9ec99905777164b4279e743a7`.
- Live Quality Graph branch `origin/codex/quality-graph-governance-lineage`:
  `f07548135fe930e7a8fb9bb97271c9f05a8ebfc1`; verified not an ancestor of HEAD.
- Only open PR: draft #10, `codex/session-route-admission`, head
  `3ae214f75b898d171c68bb127dec10f17e03117a`.
- No tracked `.github/workflows/*` in this integration checkout.
- GitHub has historical Quality Graph runs on OTHER SHAs: latest observed run
  33793416872 at `3c06e81d24c4421d36443e0961c79daa3a1b2851` completed failure;
  the next four were failure/failure/cancelled/failure. Earlier shorthand
  “CI not started” means no new bootstrap CI for this integration SHA, not that
  GitHub has never run any workflow.
- Docker test MariaDB healthy: `fmonitor2-test-test-db-1`, localhost:23306.
  Leave it available; it is not the final clean deployment proof.
- Only the main git worktree remains. No test concurrency workers found.
- Last running agent `/root/seed_original_planning_review` was INTERRUPTED for
  restart. Its unfinished task was a read-only authority/dependency review of
  importer characterization under the latest table-transfer approval. It wrote
  no new artifact or verdict. Do not assume that review completed.
- `/root/descriptor_observability_audit` is errored (security filter), not live.
  Other visible reviewer handles are completed. New session must create/task its
  own independent reviewers, not rely on old handles.

## 3. Non-negotiable gates and restrictions

- Each behavior: approved executable spec → demonstrated RED → independent
  Gate 3 → minimal GREEN → independent Gate 5. Reviewer is not test/code author.
- OpenSpec does not replace executable specs, RED or review records.
- Append-only facts/evidence; state changes owned by an explicit public
  application seam. No new domain logic in rapid-pilot.
- No production secrets, real documents/personal data, production source import
  or writes to read-only ../fmonitor. Only public ../shlz-ui exports.
- Do not edit protected blocked PILOT-E2E-FLOW-001 without owner-approved Gate 1.
- Do not change/merge draft PR #10.
- Do not push the current integration branch before the authorized publication
  stage. Nothing was pushed in this continuation.
- No Quality Graph integration or bootstrap CI PR before the FIRST literal
  VERIFY_OK from full make verify at an exact SHA. That proof has not been
  obtained in this continuation.
- Never turn real failures into skips or tolerated deviations.
- Run architecture-check on boundary changes; full make verify before claiming
  integration. Passing focused suites is not full verification.

## 4. Owner decisions already obtained — do not ask again

### Original workflow and production safe log

PDF original may be uploaded after optional template or directly. No manual
registration number. Upload/correction do not open work or apply composition;
opening is separate. Original date differs from upload time; history append-only.

Owner approved mandatory ProductionConfig.safeLogFile: pre-existing canonical
non-symlink regular file, effective-user-owned, exact 0600; no create/repair;
validate before DB/private storage, real append-only diagnostics, fixed redacted
construction error. Evidence:
`docs/operations/assignment-order-original-production-safe-log-owner-resolution-2026-09-05.md`.
Spec amendment `bcdaedb7cd7cb7b80684d29f432a3d20a40e0177`, technical Gate 1
`b510efc57b7dc4f7c3965f0b98e33bcfcedb721c`.

### Original read permissions

Owner explicitly approved FKR operator/manager for accessible objects, engineer
for assigned objects, plus OTIZ for ALL orders, including prior revisions.
Administration alone does not grant read or mutation. Evidence:
`docs/operations/original-http-reader-owner-decision-2026-09-05.md`.
Commit `3ee7bcef1c3cb090c22b773b651c8d512e86e07c`; independent planning review
`670e19f6d86fe0172d71eaa36736ec6d857b936e`.
These grants are PLANNED, not yet runtime implementation.

### Object-detail schema transfer

Owner explicitly said «подтверждаю перенос создания таблиц в штатные миграции».
Evidence `docs/operations/object-detail-schema-owner-approval-2026-09-05.md`,
commit `e8f17b63a3c93e8f4be5664c309b435fde3318e9`.
Technical Gate 1 of exact v0.4: `eaef9ebbd14cd34e1fe7faf85bde0a6968c0d0db`.
The dated approval supersedes the historical DRAFT header in the unchanged
reviewed spec bytes. Version 12 was selected after revalidating registry 1–11.
Do not request this table-transfer approval again.

## 5. Completed original-command prerequisites and remaining blocker

- Original schema v2 implementation `4c7df544a186cd0ee8b4e58f37b8868b7735789c`,
  independent Gate 5 `a54446f294e29362103e23d38c34823b06b5b1be` remain historical
  approved prerequisites (shared content identity and preservation).
- Original command includes immutable storage, authorization/audit/replay,
  CAS/unknown outcomes, lease/maintenance and real-worker barriers. Relevant
  implementation lineage includes 6c4fb5b, 87d3bd3, 6be7fa6, 12fee6f; inspect
  actual path history rather than treating any one as current HEAD.
- Incremental PDF correction implementation:
  `f3afea2d5a0b60eed0d82bebde03174087d8b255`.
  Independent parser Gate 5 APPROVED:
  `fba48d1686aa8b1d9d8f73840c3961af130a88f1`.
- Production safe-log implementation:
  `813d224ae4ba99a8d685fcfce48d161b27e7a3e4`.
- Safe-log parent-symlink correction:
  `65988fb6520039def9d00f28a531651e796ef6be`, bounded Gate 5
  `aecd7bb73d1c8b1f52b73764c79cdb2d7289234a`.
- STILL BLOCKING: G5-SAFELOG-2 descriptor-integrity finding from review
  `3be68a81a220cf24c001f4f2bdaeda06e305625a`.
  Read `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-production-safe-log-v55.md`
  and `docs/operations/assignment-order-original-production-safe-log-descriptor-race-gate1-gap-2026-09-05.md`.
  Opened descriptor attributes are not fully revalidated in the reported
  validation/open interval. A deterministic verification-seam question remains.
  Prior delegated test/audit attempts hit the automatic security filter. Do not
  evade that filter, waive the finding, or treat failed/errored agents as approval.
  The precise need for a new owner decision/test seam was NOT established by the
  interrupted audit; current-state safe investigation and proper gates remain
  necessary. Complete combined original-command Gate 5 is NOT APPROVED.

## 6. New v12 data-free object-detail schema engine — reviewed, not whole Done

Change: `canonicalize-object-detail-snapshot-schema`.
Spec: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4.

Production now registered at canonical runner v12:

- ObjectDetailSnapshotSchemaMigration.php — production facade, fixed no-op observer;
- ObjectDetailSnapshotEngineSchemaMigration.php — sole family DDL/locking owner;
- MariaDbObjectDetailSnapshotSchemaFingerprint.php — exact read-only metadata;
- ObjectDetailSnapshotSchemaMigrationVerification.php — explicit verification API;
- phase enum, observer interface and no-op observer.

Exact two-table family, empty creation, populated repeat/coexistence preservation,
both partial recoveries, whole-family conflict preflight, prefix 25/26, validated
database-default collation, named lock/release, phase timing, interruption,
real DDL denial, two-creator and independent-prefix coverage are present.
Native mysqli CREATE=false originally emitted a false success event; qualifying
RED `dc4b701cee160166edad31ad27c592e4bf20a980`, Gate 3
`1e7e43a4375faab3c08d07236c2d40fc54e20c0a`, fix
`b7bc649cb6d918dffa947ff0068bddfe17d31d5b` require query === true before event.

LATEST combined engine Gate 5:
`07f58c7dd10700b3a951f792e94b972839dfe876`, verdict
APPROVED_SCHEMA_ENGINE_ONLY, reviewing combined SHA
`fd0487410d595f506adee7c3457698bf2a10c34e`.
Record: `reviews/code/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001-schema-engine-v1.md`.
All six family tests + composed runner, lint, architecture (7 rules), diff and
cleanup passed. Reviewer noted no dedicated live GET_LOCK NULL/query-error or
connected RELEASE_LOCK non-1 fixtures; reviewed strict paths are fail-closed.

Tests:

```text
tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
tests/InstallationProcess/object_detail_snapshot_schema_lock_001_test.php
tests/InstallationProcess/object_detail_snapshot_schema_observer_001_test.php
tests/InstallationProcess/object_detail_snapshot_schema_ddl_denial_001_test.php
tests/InstallationProcess/object_detail_snapshot_schema_concurrency_001_test.php
tests/InstallationProcess/object_detail_snapshot_schema_native_false_001_test.php
tests/InstallationProcess/production_migration_runner_001_test.php
```

Use documented local test credential via
`FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php <test>`;
it is test-only, not a production secret. Docker/PHP paths if needed:
`/Applications/Docker.app/Contents/Resources/bin`, `/opt/homebrew/bin`.

Composed runner fixture uses explicit columnsV12()/indexesV12(). Default shared
catalogue methods retain v1–v11 output for untouched callers (combined JSON
hash `380b1de99c8a1b8d3c204126c2b6805bfbaaeab9c9b59a63862b1b8899e87c4f`).
Other old global fixtures may still expect v11; update only via scoped reviewed
test amendments. Protected E2E must not be changed indirectly or directly.

Some late-added coverage was already GREEN on implemented code. Its pre-v12
missing-seam run is explicitly RETROSPECTIVE sensitivity evidence, not invented
forward TDD history; preserve those review classifications.

OpenSpec completed tasks: 1.1, 1.2, 1.3, 2.1, 3.1 (5/14). The rest remain open.
Task 2.0 accounting still needs requirement-by-requirement reconciliation; do
not blindly check it. Whole migration transfer is NOT Done because:

- `rapid-pilot/import-production-object-details.php` still has both runtime
  CREATE TABLE IF NOT EXISTS statements;
- importer schema precondition/no-DDL and preserved serial DML regression are
  not yet delivered;
- architecture debt baseline was not decreased for those unremoved statements;
- outside consumers/full make verify/integration proof remain incomplete.

## 7. Immediate interrupted work: importer regression and authority boundary

Existing change `characterize-object-detail-import`, spec
`specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md` v0.1, still says
DRAFT / OWNER_APPROVAL_REQUIRED; old technical consistency was ready for owner
review. Its four planning artifacts need reconciliation (proposal even mentions
verifier under rapid-pilot while executable seam is tests/Verification).

Read-only task interrupted for restart asked whether the latest explicit
schema-transfer authorization plus inherited process invariants already covers
the required PRIVATE SYNTHETIC regression oracle, or whether a genuinely new
product decision needs owner input. No verdict exists. Resolve this accurately:
do not infer exact-hash owner approval, waive Gate 1, unnecessarily request the
already approved schema transfer again, or promote UNKNOWN importer semantics.

Actual importer guard facts, verified by reading source:

- endpoint host may be nonempty and port 1..65535, but DB name must equal
  `fmonitor2_demo`;
- assertGeneration reads `{prefix}fm2_pilot_generation_sentinel` singleton 1
  fields generation/fingerprint/manifest_nonce and compares manifest values and
  actual @@hostname;
- after that it opens the source connection, reads six fields, then currently
  performs runtime DDL before target DML;
- therefore a fully task-owned disposable MariaDB instance can host a private
  `fmonitor2_demo` target and private synthetic source, preserving guards without
  using an existing/shared demo database or real source. This is a proposed
  constructibility direction, NOT implemented/approved harness.

Next safe work: complete this authority/constructibility review, make the
regression/no-DDL contract exact, get necessary Gate 1, demonstrate RED, fresh
Gate 3, minimally remove importer DDL and add fail-closed precondition, then
fresh code review and reduce only the actual removed architecture debt.

## 8. User-facing original workflow remains incomplete

Created planning packages:

- `expose-assignment-order-original-http` — planning approved, reader policy
  approved; executable HTTP spec v0.2 is still DRAFT. Routes/error mapping are
  candidates; scope predicates, transport/read APIs, etc. still open.
- `select-assignment-order-composition-without-template` — planning approved,
  executable selection v0.3 is DRAFT / CHANGES_REQUIRED. Task 1.1 only complete.

Independent inventory `16ba3e65401ab6b3acb883a96f590bc1e9eb7c3d` proves no public
production creator persists chosen composition without renderer. HTTP upload
intent still calls coupled prepare; fixture/private SQL is not direct upload.

Selection latest review: `ca5f89a4f7042da359889aaa9c0a8c99ed2c236a`, record
`docs/operations/selection-command-contract-v03-readiness-review-2026-09-05.md`.
P0s: mandatory physical order_date/member validity vs selectionDate meaning;
undefined exact latest-selection/accepted/effective source predicates and public
projection handoff. P1s: exact result/ports/replay encoding, grant migration and
audit/persistence still incomplete. Proposed NEW_ORDER/REPLACE_PENDING and
`assignment_order.composition.select` are NOT approved runtime contracts.

Projection gap: newer pending order hides old registered assignment because
MariaDbInstallerDirectoryReader uses MAX(version) across all orders. Independent
SQL diagnosis recorded in `412efcb75bd88b454b2ba1ebbf9800937e96856b`.
Do not implement an isolated MAX(registered) patch as target applicability;
selection must preserve effective public directory/inspection projections.

`apply-assignment-order-original-to-composition` and
`open-installation-from-assignment-order-original` remain required downstream
changes not delivered (recheck whether a new session/user created them).
TEST-USER planning was corrected to optional template → original → separate
opening, but seed implementation is not done. `seed-test-user-fixtures`,
`separate-pilot-generation-metadata` and session lifecycle integration retain
their own unfinished gates. Do not call old register/open journey target GREEN.

## 9. Path to actual launch readiness and CI

Resolve outstanding original-command safe-log finding and obtain full combined
command Gate 5; close real regressions and get first full literal VERIFY_OK on
an exact SHA. Only then integrate Quality Graph remote f075481..., close real
receipt/parity/phase-B publisher with fresh Gate 5, repeat exact verify as needed,
create exactly one bootstrap CI PR and obtain Actions on the same SHA.

Then prove fictional TEST-USER, clean Compose deployment, restart/persistence,
real public-route golden path, authorization/fail-closed/migrations/private
storage/session/no-runtime-DDL, reproducible run/verify/restart/diagnostics docs,
and final requirement-by-requirement audit. Do not complete the goal until the
exact SHA reproducibly starts from zero, passes CI/restart/golden path and all
required Gates/evidence exist without launch blockers.

## 10. Critical current SHA-256 anchors

```text
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
bb7fa50459ea0c4a8e1983d5c9193be4d026d11e25f97e224fb2b8ccbd871a3f  specs/ASSIGNMENT-ORDER-ORIGINAL-HTTP-001.md
2b30da75a41dc894d43d85a53e8f06979775787f447eaf1b3f82b9675fbe84b9  specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md
2ae45e43084c56858d589eb98362c61415b13a92cf1ffba0781a9936c9b82eff  app/InstallationProcess/ObjectDetailSnapshotEngineSchemaMigration.php
6d4e6d6e89bf462524a8793e12d3ab69bd1d15125a5956ac52412fa2ad5f36e6  bin/fmonitor2-migrate.php
```

Stop state: no active task writer, no unfinished test process observed, no
temporary git worktree, test MariaDB deliberately left healthy. Existing goal
remains active because owner requested session handoff, not completion/blocking.
