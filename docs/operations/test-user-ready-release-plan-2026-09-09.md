# TEST-USER-READY release plan — 2026-09-09

Status: living operational plan  
Target: a controlled TEST-USER launch through the Compose `make up` contour on
Wednesday, 2026-09-09  
Last reconciled: 2026-09-02, against the working tree, `openspec list --json`,
current task files, executable specs, reviews, evidence and append-only owner
decisions. Narrative snapshots are not completion evidence.

## Release outcome

On 2026-09-09 a fictional test user can enter the Compose-hosted pilot and
complete the approved golden path against a freshly created, deterministic test
dataset. Authorization is fail-closed, generated artifacts are correct, user
state survives an ordinary restart, and the whole repository verification ends
with literal `VERIFY_OK`.

This is not a production cutover. It does not import real personal data, use
production credentials, integrate with 1С ДО, or authorize deletion/reset of
unowned resources.

## Current position — 2026-09-02

Release state is **AMBER**: foundations and the ordered schema frontier through
classification provenance v11 are operational, but the test-user journey is not
release-green.

| Slice | Actual state | Release consequence |
|---|---|---|
| Canonical classification provenance v11 | OpenSpec 11/11; GREEN evidence and independent code review exist | Ordered frontier can advance; integration evidence must remain green |
| Pilot session storage | 9/15; all 14 reviewed tests GREEN in host contour, but Gate 4/5 incomplete | Must replace remaining rapid-pilot native sessions, finish ratchets and provide an approved fictional Compose login fixture for restart proof |
| Remove «Моя работа» navigation item | 3/11; exact Gate 1 approved and predecessor RED recorded | Must complete reviewed RED, minimal removal, regression and Gate 5; route `/pilot/` remains |
| Object-list RBAC fixtures | 2/9 | Depends on navigation predecessor GREEN, then needs Gates 2–5 |
| Prepare GET/HEAD RBAC fixtures | 6/12; amended Gate 1 approved; repeated test reviews and RED evidence exist | Finish accepted Gate 3, minimal fixture GREEN, verification and code review |
| E2E RBAC fixtures | 5/10; amended Gate 1 approved; RED reviewed; Gate 4 attempt recorded | Must reach stable GREEN and Gate 5 before combined PDF RED |
| Combined PDF E2E | 2/9 | Blocked by E2E RBAC GREEN; then Gates 2–5 |
| Original assignment-order PDF upload | 5/16; product direction settled, contract still in Gate 1 constructibility refinement | Release-critical replacement for obsolete manual registration; no RED/GREEN before fresh exact-hash approval |
| Fictional test-user seed | 0/17 | Release-critical; waits for the required canonical schema/generation frontier |
| Object-detail snapshot + generation metadata | 0/13 and 0/14 | Critical predecessors to the fictional seed; versions follow the landed v11 frontier, never guessed early |
| Full verification | no current literal `VERIFY_OK` | Hard NO-GO until a clean final run produces it |

Completed OpenSpec task counts are navigation aids, not gate approval. A slice
is Done only when its executable spec, RED evidence, independent test review,
minimal GREEN, regressions and independent code review all agree.

## Release-critical scope

The following must be complete for GO:

1. A reproducible, ownership-safe Compose contour: `make up` preserves state;
   explicit `make reset` recreates only proven-owned test resources.
2. Canonical migrations and prerequisites needed by the test-user dataset,
   including object detail and generation metadata after v11.
3. Deterministic fictional users, roles, objects and workforce fixtures with no
   real identities, secrets or production/network dependency.
4. Durable pilot sessions that survive an ordinary Compose restart and fail
   closed on storage corruption/faults.
5. The approved shared navigation behavior: no «Моя работа» item anywhere,
   while `/pilot/` and all other navigation/error contracts remain intact.
6. Local RBAC fixtures for object list, prepare and the full E2E path, including
   exact 401/403/404/405/503 and HEAD behavior without hidden state mutation.
7. One combined PDF artifact on the golden journey with authorization-first
   reads, correct content/metadata and exact approved prepare delta only.
8. The pilot assignment-order workflow uses an immutable original PDF, not a
   manually entered order number: one PDF up to 20 MiB, content validation,
   append-only correction, composition confirmation and a separate opening
   action. Any smaller 2026-09-09 increment must still cover the golden path and
   be explicitly approved; silent fallback to legacy registration is forbidden.
9. One clean launch rehearsal, complete verification evidence, and fresh
   independent Gate 5 approvals for every release-owned production diff.

## Deferable work

These items may continue in parallel but do not block 2026-09-09 unless a
release-critical slice adopts them as a stated prerequisite:

- migrated-evidence and migration-quarantine canonicalization;
- broad PILOT_ONLY characterization beyond the selected golden path;
- construction-control queue characterization beyond evidence needed by the
  launch journey;
- full target implementation of completion, ОТиЗ, premium, payments,
  deductions, K1–K11 and inspection reassignment rules decided on 2026-09-02;
- 1С ДО integration, OCR, multiple upload files and production/legacy cutover;
- runtime-DDL debt removal not touched by the release-critical changes.

Deferral does not promote known pilot defects into product requirements. Owner
decisions remain product truth and feed later executable slices.

## Critical dependency graph

```text
classification v11 (landed)
  ├─> object-detail snapshot ─> generation metadata ─> fictional test-user seed
  │                                                     └─> launch rehearsal
  ├─> session storage ──────────────────────────────────> restart proof
  └─> original-PDF canonical prerequisites ─────────────> upload/open golden path

remove «Моя работа» ─> object-list RBAC ─┐
prepare RBAC ─────────────────────────────┼─> E2E RBAC ─> combined PDF ─> rehearsal
session + fictional identities ──────────┘

all release-critical Gate 5 approvals + clean Compose + literal VERIFY_OK ─> GO
```

The shared E2E/object-list/prepare tests and `PilotView`/
`ProductionPilotHttpEntrypointFactory` are serialized hotspots. Their writers
must not overlap.

## Delivery gates and accountable roles

| Gate | Accountable role | Entry | Exit evidence |
|---|---|---|---|
| 1 — executable contract | Discovery/spec author, then owner | Product decision and bounded slice | Independent planning review plus owner approval of exact hashes |
| 2 — RED | Fresh RED author | Gate 1 exact hashes unchanged | Intended public-seam failure, distinguished from setup failure, regression and predecessor blocker |
| 3 — test review | Fresh independent test reviewer | RED and evidence complete | Explicit `APPROVED`; reviewer did not author/fix reviewed tests |
| 4 — minimal GREEN | Implementer | Gate 3 approved | Smallest production change makes approved tests green; no assertion weakening |
| 5 — integration/code review | Integrator and fresh independent code reviewer | Focused GREEN and complete diff | Focused suites, architecture, lint, diff hygiene and full verification recorded; explicit code-review `APPROVED` |

The integrator updates OpenSpec tasks and this plan only from durable evidence.
Any executable-spec change returns to Gate 1; any test change after approval
returns to Gate 2/3. A reviewer never approves their own work.

## Daily checkpoints

### Wednesday, 2026-09-02 — contract closure and first GREENs

- Preserve v11 GREEN and complete its integration record.
- Finish session-storage and navigation test-review/GREEN paths.
- Resolve current prepare/E2E RBAC Gate 3 findings without weakening assertions.
- Obtain constructibility review and exact-hash approval for original-PDF upload.
- Advance object-detail/generation planning against the landed v11 frontier.
- Exit: no release-critical slice is blocked by an unanswered owner decision;
  each has a named next gate and evidence file.

### Thursday, 2026-09-03 — prerequisites and RBAC

- Land navigation removal and object-list RBAC through minimal GREEN.
- Land prepare RBAC and E2E RBAC through focused GREEN.
- Complete object-detail and generation-metadata prerequisite gates in order.
- Exit: the golden journey reaches its own business/artifact failure, not
  setup, identity, navigation or authorization predecessors.

### Friday, 2026-09-04 — persistence, fixtures and original PDF

- Complete session-storage Gate 5 including ordinary restart evidence.
- Drive fictional fixture seed through RED/review and minimal GREEN once its
  exact frontier is landed.
- Drive original-PDF upload through approved RED/review and minimal GREEN.
- Exit: clean fictional dataset can log in and preserve a state-changing action
  across restart; original PDF can be accepted through the approved public seam.

### Saturday, 2026-09-05 — combined artifact

- Complete E2E RBAC Gate 5.
- Run combined-PDF RED/review and minimal GREEN.
- Exercise authorization, integrity/fault, repeat/reload and concurrency cases.
- Exit: one authorized combined PDF is generated and read without RBAC drift,
  forbidden artifact mutation or a second legacy artifact.

### Sunday, 2026-09-06 — integrated golden journey

- Run the entire fictional journey on a clean owned Compose generation.
- Exercise direct original upload, composition, separate opening, core work and
  combined artifact path within the approved release scope.
- Classify every failure as setup, intended RED, regression or predecessor.
- Exit: all release-critical focused suites green; no unknown failure class.

### Monday, 2026-09-07 — integration freeze

- Freeze release-critical behavior; accept only fixes for demonstrated launch
  blockers or verification regressions.
- Run strict OpenSpec validation, architecture, lint, diff hygiene, focused
  DB/HTTP/E2E/built-image/restart suites and full `make verify`.
- Obtain all missing independent Gate 5 reviews.
- Exit: first literal `VERIFY_OK`, or an explicit NO-GO recovery list with one
  owner and deadline per item.

### Tuesday, 2026-09-08 — clean rehearsal

- From an ownership-proved clean state, execute reset/recreate, `make up`,
  fictional login, golden journey, ordinary restart and repeated read.
- Verify no real data, deliverable addresses, credentials or external services.
- Capture exact commands, revision, hashes, timestamps and resulting evidence.
- Exit: second literal `VERIFY_OK`; rehearsal signed off; rollback/recovery
  exercised or proven by the approved automated checks.

### Wednesday, 2026-09-09 — GO/NO-GO and test-user opening

- Reconcile working tree, OpenSpec, reviews and evidence; do not rely on status
  prose alone.
- Run the final verification ladder below on the exact candidate.
- Open access only if every GO checkbox is satisfied. Otherwise declare NO-GO,
  preserve evidence and return to the failed gate.

## Verification ladder

Run from the exact candidate, stopping only to classify and fix failures:

1. `git diff --check`.
2. Strict validation of every active release-critical OpenSpec change.
3. Focused unit and public-seam tests for the changed slice.
4. Canonical clean reset and ordered migrations, then repeat migration.
5. Focused identity/RBAC, session, upload/storage, PDF and golden E2E suites.
6. `make architecture-check` — all seven policies must pass.
7. Repository lint and built-image checks.
8. `make fresh-test-verify` to prove cleanup of the disposable contour.
9. `make verify`; the final machine-readable result must be literal
   `VERIFY_OK`.
10. Repeat the launch rehearsal after ordinary restart and prove state
    preservation.

`VERIFY_OK` cannot be inferred from individually green stages. A setup failure,
skip, timeout, expected RED, predecessor blocker or unclassified failure is not
release-green.

## GO / NO-GO checklist

GO requires every item:

- [ ] Exact release revision and evidence timestamps recorded.
- [ ] All release-critical OpenSpec tasks and strict validations agree with
      specs, tests and reviews.
- [ ] Every release-critical production diff has fresh independent Gate 5
      `APPROVED`.
- [ ] Owned Compose reset/recreate succeeds; ordinary `make up` is
      state-preserving.
- [ ] Fictional test identities work and no real personal data, secret or
      external production dependency is present.
- [ ] Navigation, object-list RBAC, prepare RBAC and E2E RBAC are green.
- [ ] Original PDF and separate work opening complete the approved golden path.
- [ ] Session and user-created state survive ordinary restart.
- [ ] Combined PDF bytes, metadata, authorization and repeat read are green.
- [ ] Architecture reports 7/7; lint and `git diff --check` pass.
- [ ] Final `make verify` output contains literal `VERIFY_OK`.
- [ ] Rehearsal has no unknown/unclassified failures.
- [ ] Recovery procedure and evidence location are known to the operator.

Any unchecked item is NO-GO. There is no waiver by narrative status, deadline,
partial suite success or an old review.

## Rollback and recovery

- Before launch record the exact candidate revision, Compose project/resource
  identity, canonical schema fingerprint and fictional fixture fingerprint.
- Preserve append-only DB and artifact evidence. Do not roll back domain facts
  with `UPDATE`, `DELETE`, destructive Git commands or an automatic reseed.
- On application regression, withdraw test-user access and redeploy the last
  independently verified candidate; do not downgrade or rewrite canonical
  history.
- On session/storage failure, stop writes, preserve the owned volume and fault
  evidence, then use only the approved inspection/recovery seam. Never expose
  session IDs or payloads in diagnostics.
- Use `make reset` only for the disposable test contour after exact ownership
  proof. Ambiguous or foreign resources make reset fail closed.
- If the candidate cannot recover without deleting unowned state or weakening
  approved tests, declare NO-GO and return to the relevant gate.

## Blockers and required inputs

GRILL-009, removal of «Моя работа», the refined session-storage contract and
the stated RBAC amendments have owner decisions/exact-hash approvals recorded.
They are not current decision blockers. Original-PDF upload still requires the
fresh constructibility rereview and exact-hash approval identified in its task
file before RED. Any newly discovered product choice is written as a compact
owner question and does not stop unrelated READY work.

No production credential is a prerequisite for this release. Required runtime
inputs are limited to local Compose configuration and deterministic fictional
fixtures. The session restart verifier currently exits `SETUP_FAILURE 78`
because no approved fictional Compose email/password fixture is available;
credentials from an unrelated disposable test MUST NOT be reused. Missing,
ambiguous, real-person or production-bound credentials/data are a hard NO-GO,
not a reason to copy secrets into the repository.

## Update protocol

The integrator updates this file after every completed gate, new blocker, owner
decision and full verification run:

1. Re-run `openspec list --json` and inspect the relevant `tasks.md`, executable
   spec, latest review and actual test output.
2. Update the current-position row, dependency/blocker and next checkpoint.
3. Link durable evidence; never replace append-only decisions or reviews.
4. Record verification as GREEN only from the command's actual output. Record
   setup failure, intended RED, regression and predecessor blocker separately.
5. Keep release-critical and deferable work separate; moving the boundary needs
   an explicit owner decision.

## Owner-requested stopping point

The owner instructed the orchestration to stop after completion of the next
Gate 5 and produce a handoff. Therefore the next slice that genuinely completes
Gate 5 is an explicit operational pause point: record its review and
verification evidence, update this plan/status, stop assigning new work, and
produce the handoff. This pause does not mean TEST-USER-READY or GO unless the
full checklist above is independently satisfied.
