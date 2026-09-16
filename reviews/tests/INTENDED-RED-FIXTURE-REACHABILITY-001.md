# Gate 2 evidence — INTENDED-RED-FIXTURE-REACHABILITY-001

- Test author: root Codex session.
- Base: `764f2c0f2118a8c8f8cdb7b8235fb360e982bb13`.
- Scope: opt-in pre-Gate-3 fixture remainder reachability; no #20 changes.
- Planner lane: `CRITICAL`; required reviews: `gate3`, `final`.
- Plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T212306Z-a1e1da2b41/verification-plan.json` (`f3950d8ad97c2bc74e1028784e7d8a81d4034ac862cc968051c25d416c751175`).

## RED

Command:

`python3 tools/delivery/harness.py run --intended-red 'INTENDED_RED: pre-Gate-3 fixture reachability safeguard is absent' -- python3 tests/Verification/fixture_reachability_001_test.py`

Initial record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789593795859432000-ea419bafa9594c958d2b6a4f1e0bfe96.json`.

Observed outcome: `INTENDED_RED`, child/CLI exit `7`. The failure is caused by absent declaration grammar, runner option/outcome and dual-evidence Gate-3 admission. Full stdout/stderr remain in the external record paths.

Sensitivity before implementation:

- valid opt-in declaration is rejected as malformed;
- runner rejects `--fixture-reachability` as unknown;
- ordinary test still reaches the genuine missing-product marker;
- synthetic missing table/column, wrong helper argument, malformed provider/index and invalid CSRF/setup source lie behind that RED boundary;
- realistic post-fork SQLite fixture has both controlled broken-column and healthy variants, neither yet reachable through the missing runner mode.

`SETUP_FAILURE`, arbitrary nonzero/crash after a marker, wrong/missing/duplicate marker and signal are asserted not to become `FIXTURE_REACHABLE`. No production system or product mutation is invoked.

## Gate 3

Reviewer: independent gpt-5.6-sol/low reviewer (`/root/gate3_fixture_reachability`), 2026-09-17.

Reviewed source: retained snapshot for candidate source
`3135898041faeaa781ebfb95a98077443e462291b7d9a0c8a0885c5994780d08` in
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T212446Z-ffb95d12f2/snapshot`.
The reviewed test/spec hashes match the package bindings. RED record:
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789593872457599000-a0869ba38be844588cbbd7ed66e5e7a1.json`.

The retained RED is legitimate: the ordinary mapped command exits `7`, the
expected marker is present, and the failures show the missing declaration grammar,
unknown `--fixture-reachability` runner option and absent dual-evidence admission.
It is not a `SETUP_FAILURE`. The candidate also demonstrates a real early product
RED before each synthetic remainder, covers all five requested forensic defect
classes, and includes healthy and broken post-fork SQLite variants. The proposed
seam remains the existing runner/prepare contract and does not require product
implementation, #20 changes, a new Gate/framework, or product mutation.

### Findings

1. **High — the separate evidence identity contract is materially under-tested.**
   `tests/Verification/delivery_harness_001_test.py:524-551` proves missing versus
   present evidence, duplicate RED and one wrong-boundary execution, but does not
   exercise rejection for mismatched acceptance id, command id, command
   environment, test blob, source, or an otherwise healthy reachability record
   borrowed from another mapped command. Those are independent fail-closed fields
   required by the normative `Separate exact-source evidence` section and case E;
   the current assertions could pass an implementation which validates only
   outcome and boundary. Add bounded negative cases for every required identity
   dimension, plus a positive case in which both records explicitly carry and
   match the expected acceptance/command identities. Preserve the existing
   exact-source/environment checks rather than replacing them.

2. **High — repeated/concurrent isolation (case P) has no executable coverage.**
   `tests/Verification/delivery_harness_001_test.py:498-600` only runs controls
   sequentially. It does not prove that two same-boundary controls create
   independent retained records or that one command cannot borrow the other's
   `FIXTURE_REACHABLE` result. Add a bounded repeated/concurrent control test which
   asserts distinct record ids, correct per-command/test identities, and Gate-3
   rejection of a crossed evidence pair.

3. **Medium — the realistic post-fork fixture is not cleanup-isolated.**
   `tests/Verification/delivery_harness_001_test.py:575-600` creates a
   `NamedTemporaryFile(delete=False)` and never removes it. This weakens the
   deterministic/disposable-fixture claim and becomes more visible once case P is
   added. Use the test's existing disposable directory (or a cleanup/finally path),
   give each invocation its own database, and assert both healthy and defective
   variants clean up without sharing state.

4. **Medium — opt-in compatibility is only implicit in unrelated legacy coverage.**
   `test_fixture_reachability_declaration_is_opt_in_and_fail_closed` validates a
   declared acceptance and malformed declarations, but the five-method mapped
   acceptance wrapper does not prove case C: an undeclared intended-RED test still
   reaches ordinary Gate-3 preparation unchanged. Add that focused assertion to
   this safeguard's mapped acceptance suite so an implementation cannot impose
   reachability globally while still satisfying the new declared cases.

Verdict: `CHANGES_REQUESTED`.

The findings are bounded to missing acceptance coverage and fixture isolation;
they do not require generic instrumentation, a test-architecture rewrite, or a
new framework. Preserve the RED evidence above, update the tests as one coherent
candidate, capture a new exact-source RED/package, and return for Gate 3 before
implementation.

## Root correction after Gate-3 return 1

Root added the four requested bounded corrections without changing the contract:

- positive explicit acceptance/command/environment identity on both evidence records plus negative acceptance id, command id/environment, blob, source, environment, argv/borrowed-command and boundary mutations;
- two concurrent same-boundary controls with distinct retained record ids and per-command identity assertions;
- `TemporaryDirectory`-owned post-fork SQLite fixture with an explicit cleanup assertion;
- an explicit undeclared intended-RED Gate-3 compatibility scenario in the mapped safeguard suite.

Corrected exact-source RED/package and independent re-review follow below; the prior verdict remains historical evidence and is not overwritten.

## Gate 3 re-review 2

Reviewer: independent gpt-5.6-sol/low reviewer
(`/root/gate3_fixture_reachability`), 2026-09-17.

Reviewed source: retained snapshot for candidate source
`7c6e484649e798ab9f00326f10e7f7d54c8cb1413e63060f13db25ff60f1a0e1` in
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T212855Z-c5565ad1c1/snapshot`;
delta from the prior reviewed snapshot is retained as that package's
`delta.patch`. Corrected RED record:
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789594118880436000-df002d0be0cb409da3c45b1cfbb79724.json`.
The record is exact-source `INTENDED_RED`, child/CLI exit `7`, and remains caused
by the missing safeguard rather than setup failure.

Resolved from return 1:

- finding 1: the dual-evidence test now supplies positive acceptance, command and
  command-environment identity and rejects altered acceptance id, command id,
  command environment, blob, source, environment, argv/command and boundary;
- finding 4: the mapped safeguard suite now explicitly proves an undeclared
  intended-RED test retains the existing Gate-3 admission contract.

Remaining findings:

1. **High — finding 2 is only partially resolved; case P still lacks crossed
   Gate-3 admission sensitivity.**
   `tests/Verification/delivery_harness_001_test.py:587-607` proves that two
   concurrent invocations produce different record ids, but both records have the
   same command path and boundary, and neither is submitted to `prepare`. The test
   therefore cannot detect an implementation which records controls independently
   yet admits a reachability record borrowed by another mapped command. Add the
   requested bounded crossed-pair case using distinguishable mapped command/test
   identities: each correct pair is admissible and a crossed RED/reachability pair
   is rejected. Keep the concurrent distinct-record assertions.

2. **Medium — finding 3 is only partially resolved; defective cleanup is not
   observed.**
   `tests/Verification/delivery_harness_001_test.py:631-660` now uses
   `TemporaryDirectory`, which is the right isolation mechanism, but
   `assert not os.path.exists(root)` executes only on the healthy path. The broken
   post-fork child raises `SystemExit` inside the context and never reaches that
   assertion, so the promised explicit cleanup proof covers only one variant.
   Make the disposable root observable to the parent (or use an equivalent
   deterministic sentinel) and assert it is absent after both healthy and broken
   runner invocations. No production mutation or generic fixture framework is
   needed.

Verdict: `CHANGES_REQUESTED` (return 2).

The full matrix otherwise remains coherent: declaration validation, genuine
ordinary RED, strict marker/outcome precedence, separate evidence, the five
forensic defect classes, healthy/broken post-fork reachability and opt-in
compatibility are represented. Preserve both RED records and append a fresh
exact-source package after the two bounded corrections before implementation.

## Root correction after Gate-3 return 2

Root added only the two requested observations:

- two distinguishable declared tests now run controls concurrently, produce distinct command/boundary records, pass as a complete set, and are deliberately crossed/duplicated through real Gate-3 `prepare` to prove borrowed reachability is rejected;
- healthy and defective post-fork DB variants now allocate below separate observable parent directories and both assert the parent is empty after the child finishes, including the exception path.

The corrected candidate requires a fresh exact-source RED/package and independent re-review. Earlier verdicts remain append-only.

## Gate 3 re-review 3

Reviewer: independent gpt-5.6-sol/low reviewer
(`/root/gate3_fixture_reachability`), 2026-09-17.

Reviewed source: retained snapshot for candidate source
`1910a56d43211d2d8203575bc5a09cf731495dcbeb0e1b6d9122d3aa3f94d30d` in
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T213151Z-b9694a730a/snapshot`;
the package retains the bounded delta from re-review 2. Exact-source RED record:
`/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789594289135672000-8ebed6c437f34673b13d0e0d41bbd25f.json`.
It records child/CLI exit `7` and outcome `INTENDED_RED`; observed failures remain
the absent declaration/runner/admission safeguard, not `SETUP_FAILURE`.

Return-2 findings are resolved:

- case P now uses two distinguishable declared mapped tests and boundaries, runs
  their controls concurrently, checks independent record ids and identities,
  admits the complete correct evidence set through real Gate-3 `prepare`, and
  rejects a crossed/duplicated reachability set;
- the realistic post-fork healthy and defective variants create fixtures beneath
  separate parent directories, and the parent process observes both directories
  empty after each child finishes, including the defective exception path.

Full-matrix review found no remaining issue. The tests trace the normative seam,
keep expected outcomes independent of implementation internals, retain genuine
ordinary product RED, and require separate exact-source `INTENDED_RED` and
`FIXTURE_REACHABLE` evidence. Declaration grammar and opt-in compatibility fail
closed appropriately. Setup/control markers, missing/wrong/duplicate markers,
nonzero/crash and signal cannot prove reachability. Identity/source/environment/
blob/boundary mismatches and borrowed records are rejected. All five forensic
fixture classes are exercised, including healthy and defective #20-shaped
post-fork disposable DB variants. The probe remains fixture-only and disposable;
there is no product mutation, frozen-#20 dependency, generic instrumentation,
new framework or new Gate.

Verdict: `APPROVED`.

This approval applies only to the exact candidate source and snapshot above.
Implementation must be performed by the separate executor and any test/spec delta
after this verdict requires fresh independent review.
