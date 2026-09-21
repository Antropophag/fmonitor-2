# Test review: OBJECT-DETAILS-EDITING-001 — Gate 3 rereview v2

- Reviewer: independent Gate 3 reviewer `/root/gate3_review`; authored none of the specification or reviewed tests
- Test author: root delivery agent
- Reviewed source: base `d189102c8b080200436fcd566afa185bf13393e0` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T155535Z-438e2be88a/snapshot/source.patch`, SHA-256 `9ed65b609802ac697cc89555b9491033ff115ae43f1a8c99beb61ba086e9c071` (candidate source `137630064390f7a26bb3562643fd9607b7b1767b8f75f2f793dbc5ec6fc6e48d`)
- Agreed review scope / prior findings disposition: rereview of all findings in `reviews/tests/OBJECT-DETAILS-EDITING-001.md`, the corrected six-test candidate, Quality Graph ownership, and twelve package-recorded RED/reachability results
- Specification: `specs/OBJECT-DETAILS-EDITING-001.md`
- Public seams: `ObjectDetailsEditApplication`, canonical Yii details POST/card, effective public consumers/import, canonical migration/recovery, and Playwright-driven canonical card UI
- Evidence: package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T155535Z-438e2be88a/package.json`; six deterministic `INTENDED_RED` results plus six `FIXTURE_REACHABLE` probes at the same candidate/executable source
- Verdict: `CHANGES_REQUESTED`

## Prior findings disposition

1. **Incomplete A1-A15 matrix — OPEN.** The unit test now independently accepts all thirteen field names and adds a few unsafe shapes (`tests/InstallationProcess/object_details_editing_001_test.php:14-23`), but it still does not prove correction from existing values, plan-fixation states, reference coherence, explicit nullable clearing, exact numeric/date boundaries, duplicate keys, or zero factory candidate. The MariaDB, consumer, HTTP and recovery gaps described below remain.
2. **Wrapper-only RED evidence — OPEN.** Fixture probes were added, but the four owner/unit/consumer/recovery RED executions still stop at `class_exists` (`object_details_editing_001_test.php:10`, `object_details_editing_mariadb_001_test.php:11`, `object_details_effective_consumers_001_test.php:9`, `object_details_editing_recovery_001_test.php:6`). A setup probe does not execute or validate the masked behavioral body.
3. **Static browser test — PARTIALLY RESOLVED.** The replacement genuinely launches Chromium and drives canonical Yii (`tests/Yii2/yii2_object_details_editing_browser_001_test.php:4-6`, `tests/Support/object_details_editing_browser.cjs:4-18`). The captured failure reaches Playwright and times out on the missing accessible trigger, so browser/fixture/module reachability is established. The flow remains incomplete: it checks only two groups, Kshah absence, close target, Cancel and Escape; no backdrop, focus behavior, submitted-value retention, Save/reload, zero-write checks, history, or >8 pagination.
4. **Concurrency/replay/rejection discrimination — OPEN.** `object_details_editing_mariadb_001_test.php:20-24` is unchanged: no two-connection race, stable reason checks, replay event identity/outcome, request-row counts, inactive user, missing capability, or object-scope denial.
5. **Migration/recovery lifecycle — OPEN.** `object_details_editing_recovery_001_test.php:5-8` remains a file-existence probe, class sentinel and three source substrings. It performs no migration or restore lifecycle.
6. **History expected values — OPEN.** `object_details_editing_mariadb_001_test.php:18-19` still checks only three implementation-shaped field keys, not complete independently derived immutable history facts.
7. **Consumer public seams — OPEN.** `object_details_effective_consumers_001_test.php:12-16` still inserts directly into the proposed table and calls only `MariaDbEffectiveObjectDetails`; no importer or named card/queue/document/Bitrix/ERP/OTIZ consumer is invoked.
8. **HTTP security/no-write matrix — PARTIALLY RESOLVED.** Guest-before-lookup, wrong methods and missing CSRF were added (`yii2_object_details_editing_001_test.php:4,9-10`). Read-only/missing-capability, inactive, scope-before-existence, malformed/duplicate payload, conflict/stale, unavailable, sensitive error-body and validation-retention cases remain absent. The recorded RED stops at guest route 404 versus expected 303, so later HTTP assertions are still masked.
9. **Quality Graph owner verifier — RESOLVED.** `.quality-graph/verification-policy.json:550-570` now registers the MariaDB application-owner test and canonical Yii test in addition to the card verifier, and retains the four relevant consumer edges. This is an appropriate minimal ownership correction; approval still depends on making those verifier bodies complete and reviewable.
10. **Static substring weakness — OPEN.** Recovery remains entirely source-token based, while Yii history/modal checks remain broad body substrings (`yii2_object_details_editing_001_test.php:5-7`). The browser correction removes this weakness only for its small covered subset.
11. **Compressed traceability — OPEN.** `verification-input.json:33-98` still maps acceptance ranges to short files without named assertion groups; notably A9-A12 maps to a test that invokes none of those consumers, and A15 maps to no migration/restore execution.

## Findings

1. **BLOCKER — fixture reachability does not validate the masked test bodies.** All six `FIXTURE_REACHABLE` records are deterministic and prove their declared minimal setup: MariaDB case/user and source rows exist, Yii/card/CSRF are available, and the browser fixture can serve the card. The browser RED additionally proves Chromium and the Playwright module actually run. But the four class-based probes branch and exit before constructing the missing production type, so they cannot reveal invalid constructor calls, SQL/schema assumptions, vacuous expectations, or failures later in the test. Correction: provide staged RED/reachability that executes each independent behavior cluster up to its missing behavior, rather than a separate early-exit fixture branch followed by an early class sentinel.

2. **BLOCKER — application semantics remain materially untested.** `tests/InstallationProcess/object_details_editing_mariadb_001_test.php:15-25` has no conditional-plan fixation checks, distinct authorization dimensions, stable rejection reasons, complete replay identity, real parallel race, second immutable edit, complete event snapshot, or accepted-request fact accounting. Correction: exercise these through `ObjectDetailsEditApplication` with deterministic two-connection orchestration and exact before/after override/request/event assertions.

3. **BLOCKER — the declared consumer frontier has no consumer test.** `tests/InstallationProcess/object_details_effective_consumers_001_test.php:10-16` tests only a projection after a direct SQL insert. It cannot establish A9 import repeatability or A10-A12 card/queue/search/filter/document/Bitrix/ERP/OTIZ behavior and cannot prove no network/financial side effect. Correction: seed via the public command, run identical import, and invoke every named current public consumer with independent expected values and preservation assertions for old snapshots/documents/payments.

4. **BLOCKER — A15 remains unexercised.** `tests/Runtime/object_details_editing_recovery_001_test.php:5-8` does not call `ObjectDetailsEditingSchemaMigration` or the recovery seam. Correction: cover fresh, repeat, exact-upgrade, compatible-partial, incompatible and concurrent migration states, runtime no-DDL/DML permissions, backup inventory/auto-increment, restore of overrides/events/requests, replay continuity and the next accepted revision.

5. **BLOCKER — HTTP/browser/history coverage remains below the accepted contract.** The genuine Playwright flow is a useful correction, but `object_details_editing_browser.cjs:10-18` never submits or observes facts and lacks backdrop/focus/retention/reload/history pagination. `yii2_object_details_editing_001_test.php:4-11` omits several authorization/conflict/unavailable/error-safety cases and does not create a second event or >8 chronology. Correction: complete both canonical HTTP and Playwright matrices, with zero-write assertions for every cancel/rejection and DOM-level escaped old-to-new/history checks.

6. **HIGH — independently derived expected values are still incomplete.** The event assertion checks only ordered field names (`object_details_editing_mariadb_001_test.php:18-19`); consumer and history assertions do not establish typed/raw/display/reference labels, provenance/hash, actor snapshot/time, or immutability after source/reference/user mutations. Correction: derive exact expected results from fixed fixture inputs and assert full observable records/projections without copying production serialization.

7. **HIGH — the unit matrix still accepts names rather than proving field rules.** `object_details_editing_001_test.php:14-15` only asserts each key survives normalization, so wrong normalized values can pass for most fields. It lacks correction cases, exact min/max/reference/date/null boundaries, duplicate-key decoding and the `zavnumber='0'` downstream exclusion. Correction: table-drive input, independently expected canonical output and invalid boundary neighbors for every field; test JSON duplicate rejection at the HTTP decoder where PHP arrays cannot represent duplicates.

8. **MEDIUM — acceptance mapping continues to overstate coverage.** Group labels in `verification-input.json:35-97` claim whole acceptance ranges even where no corresponding seam is invoked. Correction: restore A1-A15 item-level traceability or enumerate every subcase/assertion group explicitly, then regenerate the plan and evidence.

## Evidence assessment

- The six reachability records are genuine GREEN for their narrow probes and source-bound to the reviewed candidate.
- The Playwright RED is valid and useful: Chromium loads the canonical card and fails specifically because the accessible edit trigger is absent.
- The Yii RED is valid for route/auth ordering only: it receives 404 instead of the expected guest redirect, then stops.
- The four class-sentinel REDs are deterministic intended absences, but remain insufficient evidence for their masked bodies.
- No captured result is an environmental failure. None demonstrates the unexecuted acceptance clusters.

## Required changes

1. Complete executable A1-A15 coverage at the actual command, import/consumer, HTTP/browser and migration/recovery seams.
2. Add staged evidence that reaches every independent behavior cluster behind the four class sentinels and the early Yii failure.
3. Add deterministic two-connection concurrency, distinct authorization/rejection and complete replay/history fact assertions.
4. Expand the real Playwright flow through backdrop/focus, validation retention, cancel/no-write, Save/reload, escaped history and chronology pagination.
5. Replace projection-only and recovery-string tests with real named-consumer/import and migration/restore executions.
6. Correct item-level traceability, regenerate the verification plan, capture exact-source evidence, and return for Gate 3 rereview.
