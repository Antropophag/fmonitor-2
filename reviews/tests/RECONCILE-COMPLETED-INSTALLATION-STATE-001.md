# Test review: RECONCILE-COMPLETED-INSTALLATION-STATE-001

- Reviewer: independent Gate 3 agent `/root/issue276_gate3` (`gpt-5.6-sol`, low)
- Test author: root agent (declared in the change and test headers)
- Reviewed source: base `fd75b5848b4344013411f4191ee330e147e377c4` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T104348Z-25a13c3b74/snapshot`; candidate source `0953ae5782c9a09f3f89eadcd3d825987dbcd8a469442699aff7977243c6859f`
- Agreed review scope / prior findings disposition: initial Gate 3 review; no prior findings. Reviewed the complete A1-A6 reconciliation matrix and OTIZ A8 change, including public seam, malformed/contradictory history, authorization, atomicity, concurrency, rollback, commit unknown, backup/restore, read parity, diagnostic determinism, and intended RED.
- Specifications: `specs/RECONCILE-COMPLETED-INSTALLATION-STATE-001.md`; `specs/OTIZ-EXCEL-INPUTS-001.md`; OpenSpec change `openspec/changes/reconcile-completed-installation-state/`
- Public seams: bounded Yii preview/apply/readback console through one InstallationProcess owner; active persisted-state read projections; `MariaDbNativePremiumInputs::forDate()`; disposable backup/restore rehearsal
- Red commands and intended failures: five exact-source records listed under Evidence below
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **CRITICAL — A1-A4 have no executable behavioral test at the declared public seam.** `tests/InstallationProcess/completed_installation_reconciliation_001_test.php` only requires that a proposed PHP file exist and scans its source for method names and literals. It never creates cases or completion history, calls preview/apply/readback, observes persisted facts, or proves zero writes. Consequently it covers none of the valid-candidate rules, malformed/contradictory matrix, stable keyset/limit, candidate digest freshness, authorization/prerequisites, byte-equivalent history, atomic state/event pair, idempotency, concurrent runners, lock timeout, rollback, or commit-unknown classifications. An implementation containing the searched comments/strings can pass while doing nothing or performing unsafe writes.

2. **CRITICAL — the console test does not execute the console boundary.** `tests/Yii2/yii2_completed_installation_reconciliation_console_001_test.php` scans a proposed controller and config for substrings. It does not invoke preview, apply, or unknown-commit reconciliation with valid and invalid argv; verify exact grant, UUID, candidate identity, backup/restore identifiers, bounded limit, non-interactive behavior, JSON schema/continuation, exit codes, secret/path/SQL redaction, stale preview rejection, or delegation to the application owner. The negative DML substring scan is neither a public-seam assertion nor robust against alternative DB APIs.

3. **HIGH — corruption and deterministic per-candidate outcomes are wholly uncovered.** There are no fixtures or independently expected reason codes for missing/duplicate PTO or declaration roots, broken/gapped/foreign correction links, invalid dates/actors/case references, retracted or sub-85% checklist evidence, non-working state, or conflicting completion events. There is also no mixed batch proving one malformed candidate cannot contaminate a neighboring success and that ordering/continuation are stable. This is especially unsafe because the normative A1 text asks for a stable reason code but does not enumerate the exact code for each rejection; the executable matrix must resolve that ambiguity before implementation.

4. **CRITICAL — concurrency, rollback, and commit-unknown safety are asserted only as source tokens.** No test coordinates two real connections at the case lock, forces a bounded lock timeout, injects a confirmed rollback, or simulates loss at the commit boundary and then calls bounded readback. There is no oracle for exactly one transition/event, loser outcomes, `PERSISTED_COMPLETE`, `PERSISTED_ABSENT`, the two inconsistent half-pairs, no blind retry, or operation-id isolation. These are durability-sensitive behaviors and cannot advance on lexical checks.

5. **HIGH — A5 read parity is only one value assertion plus file substring checks.** `tests/Yii2/yii2_completed_installation_read_parity_001_test.php` behaviorally checks only `InstallationCaseCurrentStatus::project()`. It does not exercise object queue/card, completion register, checklist/construction-control, operational dashboard, weekly FKR, or native OTIZ with the same persisted completed fixture. It does not prove completed/100% presentation, absence from active writers, read-only behavior, exact-`working` mutation admission, live-versus-reconciliation parity, or preservation of the `fm_maintable` passport join. `str_contains(..., 'completed')` is satisfiable by comments or unrelated code.

6. **CRITICAL — the money-sensitive OTIZ change has no behavioral oracle.** `tests/Otiz/installer_attribution_diagnostic_001_test.php` scans implementation text for `array_sum($c)`, `fullName`, and a regex. It never calls `MariaDbNativePremiumInputs::forDate()`. It therefore cannot prove zero progress with one and multiple selected installers yields an empty distribution without a blocker or invented weights; positive progress with one/multiple missing installers blocks publication; every affected safe case/tab/display name is retained in stable binary tab order; issue deduplication does not erase members; cutoff behavior and contribution conservation remain intact. It also over-specifies an internal expression (`array_sum($c)`) rather than the public result.

7. **CRITICAL — the recovery test is not a backup/restore roundtrip.** `tests/Deployment/yii2_completed_installation_reconciliation_restore_001_test.py` only scans the proposed owner and RuntimeRecovery PHP sources for words/table names. It does not create representative cases/events/root+correction rows, preserve AUTO_INCREMENT, run the existing backup owner, restore into a disposable target, compare restored bytes/identities, or demonstrate that the next preview/apply is safe. Source inventory is not recovery evidence and can pass while the backup omits data or restore corrupts lineage.

8. **HIGH — retained RED evidence proves only shallow feature absence and has unknown fixture reachability.** The owner, console, and recovery records stop at absent proposed files; read parity stops at the first status-label assertion; OTIZ stops at a missing implementation substring. All five records have `end_fixture=UNKNOWN`, no acceptance id, and never reach the missing behavioral matrices. These are attributable REDs for the first shallow assertions, but not sufficient intended RED for Gate 3 on this sensitive slice.

9. **HIGH — the test structure is vulnerable to false GREEN.** Four PHP files and the Python recovery file can pass after adding the expected tokens without any correct implementation. Several tests unconditionally print `INTENDED_RED ... is not implemented` after successful assertions while exiting zero, which would produce misleading output on GREEN. Tests must report success accurately and fail only from observable contract mismatches.

## Required changes

1. Replace the owner and console lexical tests with deterministic executable tests using the actual public owner/console seams and isolated MariaDB fixtures. Cover every A1-A4 outcome, exact persisted rows, no-write rejections, safe output, and stable ordering/continuation.
2. Add a table-driven corruption matrix with explicit reason-code expectations for every malformed/contradictory condition in A1. If exact codes or candidate identity grammar are not yet normative, return to Gate 1 and define them first.
3. Add coordinated multi-connection tests for winner/loser, occupied lock timeout, rollback, commit-boundary unknown, all readback classifications, and operation-id isolation. Assert one and only one state/event pair and byte-equivalence of unrelated history.
4. Execute the real Yii commands for valid/invalid argv and assert authorization, UUID, preview identity freshness, backup/restore prerequisites, non-interactive bounded operation, delegation, safe JSON schema, redaction, and exit outcomes.
5. Turn read parity into behavioral fixtures across every named A5 consumer. Assert the same completed/100% meaning, zero reader writes, exact-working writer denial, live/reconciliation equivalence, and unchanged passport values sourced from `fm_maintable`.
6. Replace the OTIZ source scan with `forDate()` fixtures covering zero progress, positive partial attribution, multiple missing members, stable tab ordering, names, issue persistence/publication blocking, no invented allocation, conservation, and cutoff cases. Expected outcomes must be derived from the specification, not implementation tokens.
7. Replace the recovery source scan with an actual disposable backup/restore roundtrip that checks cases, events, completion roots/corrections, AUTO_INCREMENT, and a subsequent safe preview/apply.
8. Capture fresh exact-source RED for the complete corrected matrix with fixture reachability established. Each command must fail at the intended missing behavior rather than file absence or a lexical assertion. Refresh the verification plan/package and return the full corrected Gate 3 candidate.
9. Remove misleading unconditional `INTENDED_RED` success messages; use normal GREEN output after all assertions pass.

## Evidence inspected

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T104348Z-25a13c3b74/package.json`
- Snapshot manifest and patch under the package's `snapshot/` directory
- `php tests/InstallationProcess/completed_installation_reconciliation_001_test.php` — `INTENDED_RED`, exit 255, record `1790419384693459000-6130167775fa42fdbb6062aae068fee9`; fails because the owner file is absent.
- `php tests/Yii2/yii2_completed_installation_reconciliation_console_001_test.php` — `INTENDED_RED`, exit 255, record `1790419391539075000-739895be8471404cb2dcfe9734c1f3a4`; fails because the controller file is absent.
- `php tests/Yii2/yii2_completed_installation_read_parity_001_test.php` — `INTENDED_RED`, exit 255, record `1790419398331063000-b1ef37dd25134ff5a4f2d3ad7ae44099`; fails because one status label is still `В работе`.
- `php tests/Otiz/installer_attribution_diagnostic_001_test.php` — `INTENDED_RED`, exit 255, record `1790419405330868000-a4b4740961d842a8bd5b54dc56c7939a`; fails on absent `array_sum($c)` text.
- `python3 tests/Deployment/yii2_completed_installation_reconciliation_restore_001_test.py` — `INTENDED_RED`, exit 1, record `1790419412279281000-28705630f76a4e7f978492359985c3d1`; fails because the owner file is absent.
- Full local `make test` / `make verify` was not run, per owner prohibition. CI/deployment remain `UNKNOWN` and were not treated as approval or GREEN.

---

## Runtime-only Gate 3 review — 2026-09-26

- Reviewer: independent Gate 3 agent `/root/issue276_gate3` (`gpt-5.6-sol`, low)
- Test author: root agent
- Reviewed source: base `fd75b5848b4344013411f4191ee330e147e377c4` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T110525Z-86841a6967/snapshot`; candidate source `d4a9c9ea453279891e4ed8d19271a77df4963d7479731ffb56d9c11f9067c02d`
- Scope: the superseding narrow contract `RECONCILE-COMPLETED-INSTALLATION-STATE-001 v0.2` R1-R3 only: live persisted-completed projections and `MariaDbNativePremiumInputs::forDate()` zero/positive/multiple attribution diagnostics. Historical reconciliation, CLI, migration, backup/restore, and data mutation are explicitly excluded. The earlier full-reconciliation `CHANGES_REQUESTED` above remains historical and is not a finding against this new scope.
- Tests: `tests/Yii2/yii2_completed_installation_read_parity_001_test.php`; `tests/Otiz/excel_inputs_001_test.php`; `tests/Support/ExcelInputsAdversarial.php`
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **HIGH — R1's unfinished-overdue exclusion is not asserted.** The real projection test correctly proves the shared label, queue/card rendering, 100%, completed dashboard stage, exclusion from dashboard `active`, weekly 85/15 completion, live `fm_maintable` passport read, and absence of process-fact writes. However, R1 separately requires completed cases not to appear in unfinished-overdue. The test never asserts `overdueCount`, the overdue materialized rows, or the corresponding HTTP list. A regression could remove the case from active while leaving it overdue and still pass. Add a past deadline to the completed fixture and assert `overdueCount===0` and no object 4512 in `dashboard['overdue']`.

2. **HIGH — R2 has no executable assertion or mapped existing regression.** The narrow contract explicitly retains exact-`working` checklist admission and append-only completed document corrections. `yii2_completed_installation_read_parity_001_test.php` performs reads only, and the package acceptance `R1-R2-runtime-projections` maps only this file. Either add a bounded assertion using the same completed fixture that a real checklist operation is rejected with no changed process facts, plus a completed-document correction that appends without reverting state/history, or split R2 from this acceptance and map named existing executable regressions that prove both invariants on the prepared source.

3. **HIGH — R3's required safe case id is absent from the diagnostic oracle.** The positive and multiple missing-attribution fixtures call the real `forDate()` seam and correctly assert fail-closed operands, affected tabs/names, retention of both affected installers, and stable tab order. They do not assert that the deterministic diagnostic contains the required safe case id (`6101` or the contract's agreed safe representation). Add an exact structured field assertion if issues expose context; otherwise assert the unambiguous case id in the message for both single and multiple violations. Prefer structured context over prose parsing if that is the public issue shape.

4. **MEDIUM — retained read-parity RED does not establish fixture reachability.** Record `1790420693356801000-61dcfba2a29c4e0bbe1aef9b45950385` fails on the initial pure `InstallationCaseCurrentStatus::project()` assertion before `DocumentaryFixture` is constructed, so none of the real queue/card/dashboard/weekly/passport/read-only assertions execute. Its record has `end_fixture=UNKNOWN`. Add a bounded fixture-reachability mode or move/capture an independent reachability probe that creates the completed case and exercises the real readers while preserving the intended missing-label RED. The OTIZ RED is materially stronger: record `1790420700565928000-7591213be0bb465a8cd8a8f6463729d6` reaches the real MariaDB/HTTP fixture and fails at the intended missing affected-installer diagnostic, though later multiple/zero assertions will need fresh sequential RED evidence after the first correction if they expose further missing behavior.

### Required changes

1. Add explicit completed-not-overdue assertions against the real dashboard DTO (past-deadline fixture, zero overdue count, absent object id).
2. Cover both R2 invariants executablely in this test or map and retain exact-source evidence from existing real regressions for checklist rejection and append-only completed document correction.
3. Assert the safe case id in single and multiple `INSTALLER_ATTRIBUTION_ABSENT` diagnostic results.
4. Retain exact-source fixture-reachability evidence for the read projection matrix, then refresh the plan/package and capture intended RED for the corrected tests. Later assertions must not remain merely unreachable text behind the first failure.

### Evidence inspected

- Package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T110525Z-86841a6967/package.json` and its retained snapshot/verification plan.
- `php tests/Yii2/yii2_completed_installation_read_parity_001_test.php` — `INTENDED_RED`, exit 255, record `1790420693356801000-61dcfba2a29c4e0bbe1aef9b45950385`; intended missing completed shared-status label, but before DB fixture reachability.
- `php tests/Otiz/excel_inputs_001_test.php` — `INTENDED_RED`, exit 255, record `1790420700565928000-7591213be0bb465a8cd8a8f6463729d6`; reaches real `forDate()` fixture and fails because the positive-progress issue does not name the affected installer.
- Full local suite was not run. CI/deployment remain `UNKNOWN` and are not approval or GREEN.

---

## Runtime-only Gate 3 rereview — 2026-09-26 11:13Z

- Reviewer: independent Gate 3 agent `/root/issue276_gate3` (`gpt-5.6-sol`, low)
- Reviewed source: base `fd75b5848b4344013411f4191ee330e147e377c4` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T111301Z-8fbc95181d/snapshot`; candidate source `54b4c4f7e26390838e2528edd67773bc486cdad4b50968c700b584b96bc3249e`
- Scope: corrected delta for the four findings in the immediately preceding runtime-only review. No historical reconciliation behavior was reopened.
- Verdict: `APPROVED`

### Prior findings disposition

1. **Completed overdue assertion — fixed.** The completed fixture now has an independently past deadline and the real dashboard DTO asserts both `active===0` and `overdueCount===0`; it also asserts object 4512 is absent from the materialized overdue rows. This closes both branches of R1 rather than inferring overdue behavior from active membership.
2. **R2 executable mapping — fixed.** Verification acceptance `R1-R2-runtime-projections` now maps `yii2_completed_checklist_admission_001_test.php` and `yii2_completed_document_corrections_001_test.php`. Both are GREEN on exact source `54b4c4f7e26390838e2528edd67773bc486cdad4b50968c700b584b96bc3249e`, proving exact-working checklist rejection and the existing append-only completed-document correction contract.
3. **Safe case id in attribution diagnostic — fixed.** Both the single and multiple missing-installer assertions now require case `6101` together with each affected tab and display name; the multiple case retains stable `7001` then `7002` order in one fail-closed issue.
4. **Fixture reachability — fixed.** The prepared plan declares `completed-runtime-read-fixture` with `fixture_read_only`; record `1790421111014479000-6e6de1a589584ebd9bf583e6e2b437f1` is `FIXTURE_REACHABLE` on the exact candidate and constructs the live-completed MariaDB fixture through the real completion writer before reaching the dashboard. The paired intended RED remains attributable to the missing shared completed label.

### Findings

None for the corrected narrow runtime-only Gate 3 scope.

### Evidence inspected

- Package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T111301Z-8fbc95181d/package.json`, verification plan, retained snapshot, and corrected test delta.
- Read fixture probe: `FIXTURE_REACHABLE`, exit 0, record `1790421111014479000-6e6de1a589584ebd9bf583e6e2b437f1`.
- Read projections: `INTENDED_RED`, exit 255 at the absent completed shared-status label, record `1790421125398829000-ca1476230e55473f919eab122bd5218a`.
- OTIZ `forDate()`: `INTENDED_RED`, exit 255 at the absent case/affected-installer diagnostic, record `1790421132566770000-662682b08f5046e7b8b8ced7edbcdcd2`.
- Exact-working checklist admission: `GREEN`, record `1790421145659202000-caa7436b62c946d69f0c67ed1d1a3346`.
- Completed document correction: `GREEN`, record `1790421157874209000-003b4c2d5aa64ddca18109cca8b006b7`.
- Full local suite was not run. CI and deployment remain `UNKNOWN`; this Gate 3 approval does not promote either to GREEN.

---

## Supplemental test-delta review — 2026-09-26 11:30Z

- Reviewer: independent Gate 3 agent `/root/issue276_gate3` (`gpt-5.6-sol`, low)
- Previously approved binding: candidate/source `da65e1cf57897199c5d099efe939902f51264ca0f384b6df45aa4a4337e90b8d`
- Reviewed current candidate: `c17401976b4e94c38e55abf810a505a1da3a6d960e1f5b21d9323809a46cc2da`
- Scope: test-only correction of immutable fixture display-name expectations in `tests/Support/ExcelInputsAdversarial.php` and the explanatory comment in `tests/Otiz/excel_inputs_001_test.php`. Production behavior and other Gate 3 assertions were not reopened.
- Verdict: `APPROVED`

### Findings and verification

None.

- The canonical fixture data uses tabs `7001` and `7002` with immutable names `Монтажник 7001` and `Монтажник 7002`. This is directly established by `tests/Yii2/ObjectQueueFixture.php` and consistently consumed by the opening/inspection fixtures. The replaced expectations `Монтажник Один/Два` were not fixture facts.
- The single missing-attribution oracle still requires case `6101`, tab `7001`, and its exact immutable name.
- The multiple oracle still requires case `6101`, both tabs and both exact immutable names, and preserves the stable `7001` before `7002` order. The correction changes no case, membership, fail-closed, or ordering expectation.
- The new comment in `tests/Otiz/excel_inputs_001_test.php` accurately documents the source of those expectations and has no executable effect.
- Exact-current-source record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790422241072304000-368a06c641bd4662810ddaa678b1c9bd.json` runs `php tests/Otiz/excel_inputs_001_test.php` and is `GREEN`, exit 0, with matching start/end candidate source `c17401976b4e94c38e55abf810a505a1da3a6d960e1f5b21d9323809a46cc2da`; stdout is `PASS OTIZ-EXCEL-INPUTS-001` and stderr is empty.

This supplemental approval is limited to the reviewed test delta. CI and deployment remain `UNKNOWN`.
