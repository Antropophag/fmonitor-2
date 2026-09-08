# Autonomous restart handoff — 2026-09-06 12:40 UTC

Owner requested a suitable session restart point. Finish this checkpoint only;
start no new implementation in the old session. Goal stays ACTIVE, neither
complete nor blocked. This document supersedes the unfinished-work descriptions
in the08:30 handoff; that handoff's approvals, prohibitions and **Owner steering
after checkpoint: control cost and scope** remain binding in full.

## Restore first

Repository `/Users/antropophag/code/fmonitor-2`, branch
`codex/remove-pilot-work-navigation-v2`. Call get_goal first; only if absent,
restore exactly WITHOUT token budget:

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery gates, append-only evidence, exact-SHA verification, CI, clean deployment/restart/golden-path и отсутствие launch blockers

Implementation checkpoint9c48b442dd18769ba6b933df15901a1347df0612;
corrective storage tests checkpointff14abc34811e987ddd1f63a345cba037f00c085.
This handoff/review-record commit follows; exact final HEAD is in closing message.
Check actual HEAD and clean status before acting. No push/deploy/PR mutation.

Read AGENTS.md, PRODUCT.md, CONTEXT.md, pilot spec/data model and development
process fully. Read this handoff and the08:30 handoff, especially owner steering;
use referenced older records for their actual relevant scope, do not restart
completed reviews or reread the entire history for every task. Apply local
rapid-pilot/AGENTS before work there. OpenSpec apply skill remains applicable.

Deadline Wednesday2026-09-09 09:00 Europe/Moscow; schedule risk is HIGH.
No product decision is currently required. Owner approved **every denied user
original invocation gets its own audit**, preserving any existing terminal:
`original-denied-attempt-owner-approval-2026-09-06.md`, SHA256
b9e6eb2a07a580d4d0daf5a57714af6435f2c309a73fd45619ff35b855e09147.
Do not ask again. Existing new_order/REPLACE_PENDING and exact E2E admission
approvals in1842Z (and1751Z) also remain valid. Technical decisions autonomous.

## Critical path and cost discipline

Next concrete missing behavior is native safe orphan cleanup: inactive stages
and unreferenced content only, with active upload exclusion and owned locks.
Then remaining original declaration/fixture/evidence/worker gaps, combined command
Gate5, selection/registry/source/read/render/cutover, HTTP upload/read/download,
original application to composition and separate opening, fictional TESTUSER/
generation/bootstrap, first full exact-SHA VERIFY_OK, permitted CI/publication,
exact-SHA Actions, clean deploy/restart/persistence/login/golden path and complete
requirements audit with zero launch blockers. Neither mode of selection is optional.

Do not substitute additional tests/documents for working portal behavior.
Before each package specify result, completion evidence and time/token-delta cap.
Use get_goal counters at boundaries, not a persistent token budget. Stop expanding
on overrun; reassess and split only along independently reviewable boundaries.
Maintenance RED exceeded120k substantially (see red-budget checkpoint). Owner/
repository implementation then used91,091 against90k in8.5min: stopped source
work, recorded overrun. Follow-up closure+three requested storage tests observed
27,125 at12:36:15 against30k. This restart is the next context/cost boundary.
Avoid speculative harness tuning, broad rereads or unmotivated repeat reviews.

After restart propose one bounded storage GREEN package, initially30min/60k
observed token delta, reconsider estimate after reading the existing file; do not
open fixture/evidence/HTTP/selection work inside it. Completion requires the
approved storage tests plus relevant historical maintenance/lease regressions,
architecture-check and independent scoped Gate5; overruns do not waive gates.

## Completed since08:30 (reuse evidence)

DATA-INTEGRITY race/TCP/negative-membership corrections closed. Exact corrected
source4ed122cac564ae22c4505d3799a1059b04fb64a9; concurrent correction fix8415d1f.
Gate5 DATA-INTEGRITY-001-v2 APPROVED, SHA256
435da9828458e43222a43c487e169e41e1810813af6f9c293d81480a680ca8d9;
its immutable evidence clarification SHA256
0a0da1eefb26ee991126d1ec8fdba830e7e82eb8052148ae183fd52a64fc0d16.
Archive `/Users/antropophag/.local/state/fmonitor2-verification/original-data-corrected-green-lw1ys4ll`:
main47 checks had3 synthetic-env setup failures and1 historical-whitespace failure;
retained supplement on same clean SHA resolves affected checks/scoped diff.
Do not hide old failures. Parent tasks1.40/4.9/5.9 completed. No combined approval.

ATTEMPT-AUDIT v0.4 fully closed at3d4e852866ace2ef3abf776c8ffa027b7eca9d91.
Read `original-attempt-audit-delivery-2026-09-06.md`; Gate5 APPROVED and all52
commands PASS on clean exact SHA. Archive `original-attempt-audit-final-green-id4dwftx`,
evidence SHA2564be7c8ace5036d5f7ff0f019730becbefa026f3164327bd8a964effd33d736e7.
Denial audits per invocation, stream/storage failure audit-only writer, safe-log
isolation and validated extra backing audit rows are implemented. Canonical
migration frontier is now **13**, original familyv3. Do not reuse13 for selection.
Quoted CHECK literal drift fix2470126 has separate RED/Gate3. Long prefix25
maintenance table names use deterministic PhysicalNames aliases; no discovery
fallback. New OpenSpec record-original-submission-attempt-audits all7 tasks done,
not archived. Parent replace-pilot-registration-with-original-upload remains active.

## Maintenance exact present state

Spec `ASSIGNMENT-ORDER-ORIGINAL-MAINTENANCE-001.md` v0.2 SHA256
 d4712f8e82cc7c2f9865bd61924ef24b0cd2640ddbbde3d96be4f5dc9cc7c72e;
parent ORIGINAL-UPLOADv74. Gate1v01/v02 approved in operations records despite
historical DRAFT heading. Parent task1.41 done;5.10 incomplete.

OWNER Gate3v1 and REPOSITORY Gate3v1 approved. Minimal implementation9c48b44:
- MaintenanceContracts copies exact parent interfaces/DTOs, including backed
  OrphanKind enum (do not rely on earlier verbal summary calling it unbacked).
- MaintenanceValues validates closed cursor/page/result grammar. Owner handles
  shape/auth/lookup/clock/page/items/atomic result audit, immutable replay and
  count-preserving failed commit. Items owns exactly one release per returned lock.
- MariaDbOriginalMaintenanceRepository/Rows own complete matching request/audit
  snapshot validation and native atomic commit, borrowed-transaction refusal,
  request collision and acknowledgement uncertainty. No schema/grant changes.
- Real/production factories bind configured principal, guarded log, native ports;
  invalid authorization rejected before resource I/O. Ordinary verification factory
  takes Dependencies. Runtime wires new contracts/owner/factories.

Exact clean9c48b44 owner34/repository17 PASS, archive
`original-maintenance-owner-repository-green-o0_21y48`, evidence SHA256
1c0456de9fad24bab3d726141da2eb05805e079343b83c78ad9c3985c8086248.
Architecture7rules PASS, log SHA256
e136384c4f780880dae297b69446fff60e3d8e6848597a6d20df1daf65bb9450.
Scoped Gate5 record is
`reviews/code/ASSIGNMENT-ORDER-ORIGINAL-MAINTENANCE-OWNER-REPOSITORY-001-v1.md`.
Use the final verdict recorded below; approval never includes filesystem stubs.

**Current normal filesystem maintenance is unfinished:** real factory now binds
new owner, but FileStorage::listOrphans/acquireDigestLock/deleteLocked still throw.
No integration readiness is claimed. Legacy MariaDbMaintenanceService remains in
source, no longer factory-selected; its direct FileState/SQL logic is evidence,
not an alternative application owner to revive. Existing historic maintenance
suite must pass again after storage implementation; do not weaken its oracles.

Storage Gate3v1 CHANGES_REQUESTED solely for3 throwing-observer cases; remaining
oracles approved in their actual scope. Added only those atff14abc, test SHA256
21cacb45499f6333dcbfbbc2aaae34a997cfa58ac3b50a091c33a32566947118.
RED archive `original-maintenance-storage-observer-red-jvzstr_r`:3PASS/11FAIL,
including all3 new intended failures at missing list; valid stage control and
invalid factory authorization controls pass. No storage source changed.
Gate3v2 exact final verdict below determines permission for storage GREEN.

## Next implementation details (avoid rediscovery)

FileStorage currently constructor(root,clock,faults); append optional observer.
Add public PrivateStorageFactory and3 actual StorageEvent cases as specified.
FileState owns `.aoou-state.json` with `.aoou-state.lock`, atomic metadata writes.
Stage currently publishes metadata before creation and holds no stage exclusion;
fix by obtaining the same `.lock-<sha256(id)>` native exclusion before publication,
hold until close/abort, release all failed acquisitions exactly once. Content
finalize already holds that lock domain via its content lease. Keep upload
observer transcript and resource lifecycle contracts unchanged.

Page must snapshot/validate/sort binary tuples, use exact cursor and retain
candidate/cutoff snapshot for delete. Lock must be instance-owned, active and
exact identity; release permanently invalidates even on failure. Revalidate
current metadata under lock before physical deletion; stale newer/reused identity
must remain. Delete done observer failure cannot undo/retry completed deletion.
Fixture paths: `fixture-<sha256(id)>.stage|.pdf`; upload paths `.stage-<id>` and
`content-<sha>.pdf`. No raw caller path derivation. Native stage IDs currently
reuse count-based stage-0001; reviewed stale-page test covers replacement time.

PrivateOrphanFixture still concrete where interface promised; rename implementation
and expose exact interface without broad behavior rewrite in this package.
Its replay/path/primitive defects, evidence and worker declaration parity remain
separate launch blockers. Parent section16 and current maintenance spec exact
signatures govern, including parameter names. No new>=150-line production file.

## Operations and immutable prohibitions

No literal full VERIFY_OK yet. Latest full make verify remains historical060e880
with unchanged downstream E2E action «Сформировать распоряжение» failure.
Never alter/merge draft PR10. Never Quality Graph/bootstrap CI PR/publication
before first full literal VERIFY_OK on exact SHA. No failures converted to skips,
allowed failures or early exits. Protected E2E hash remains
8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b;
only exact recorded admission amendment authorized.

Every behavior retains spec→RED→independent Gate3→minimal GREEN→independent Gate5.
Reviewers separately tasked, never own their tests/code. Use one compact reviewer
per bounded gate, prescribed gpt-5.6-sol low/fork none; reuse source evidence.
No speculative native-interception/permission-failure probes. SQL adapters
MariaDb-prefixed, DDL under InstallationProcess *SchemaMigration, no baseline
ratchet. `../fmonitor` read-only; `../shlz-ui` public exports only. No real data or
secrets in repo; do not delete5 anonymous volumes without ownership proof.

Read-only ls-remote in this session confirmed integration75a642476224abe9ec99905777164b4279e743a7,
QG lineagef07548135fe930e7a8fb9bb97271c9f05a8ebfc1. Revalidate before integration.
Docker synthetic DB `fmonitor2-test-test-db-1`,127.0.0.1:23306. PATH prepend
`/opt/homebrew/bin:/Applications/Docker.app/Contents/Resources/bin`.
Synthetic test env explicitly FMONITOR_TEST_DB_HOST=127.0.0.1,
FMONITOR_TEST_DB_PORT=23306,FMONITOR_TEST_DB_ADMIN_USER=root,
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local (test-only known value).
Fixtures own/drop their random DB/root; do not rerun broad setup without reason.

Standalone registry engineb6f619f Gate5-approved but not registered; selection
v0.8 Gate1 NOT READY, five-table migration planning only. Read selection planning/
compatibility next-package reviews when that critical-path package is reached.
All-old-writer exclusion requires actual stopped writers/no active connection
and exact readiness evidence, not a marker. Existing opening/render FKs constrain
new artifacts. Full goal never ends at a component approval.

## Final restart verdicts and mechanics

Both independent reviews completed, reviewer `/root/data_transport_gate1` idle:
- Scoped owner/repository Gate5v1 **APPROVED**, record SHA256
  5fd5d4e25282961a1fb6d36c340b5234f04e13c5b47c9d709c969bd2367bbc90.
- Storage Gate3v2 **APPROVED**, record SHA256
  2899faa3b2a001e300d5f27ab41cf0e1fb7a1c72265ea8cb6d843d4d7d880904.
  Corrective RED evidence SHA256
  e36903e5ccc19c95efd79ee9419ab49223f0c2e8d300be303e066ce1757b32da.

Next session may implement approved storage GREEN directly; no new owner question
or repeated Gate1/Gate3 is needed for the same exact scope. Parent4.10 remains
unchecked because its wording also covers any exact legacy compatibility patches;
only the current new-test Gate3 is proven. Parent5.10 remains unchecked until
storage implementation, relevant regressions and whole-maintenance Gate5 finish.

Known current tool sessions58246(repository),2306(architecture),64488(clean
capture),95328(storage RED) all terminal. Process inventory showed no matching
maintenance test/architecture runner. No running child-agent work remains. The
reviewer made no code/test edits or commits. Root commits only completed review
records and this handoff now; no new implementation after restart request.

Last observed aggregate goal counter1,982,277 at12:40:33UTC, elapsed14,676seconds.
That observation includes checkpoint/review work after the earlier27,125 sample;
do not misreport that sample as final package cost. Persistent goal has no token
budget and remains ACTIVE. User can restart immediately after the closing clean
HEAD message; the old session does not begin another work package.
