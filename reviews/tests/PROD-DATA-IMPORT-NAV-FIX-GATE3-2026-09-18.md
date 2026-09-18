# Gate 3 test review — production-data import and shared navigation fixes

- Reviewer: separately tasked agent `/root/prod_data_fix_gate3`; authored none of the reviewed specifications or tests.
- Review date: 2026-09-18.
- Test author: root agent.
- Reviewed source: dirty snapshot over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`; binary diff SHA-256 `7e9e61e2aee5b6dc195ef07a03f95c1b48d67e946a1470ab5f8217641999c5c6`.
- Specifications: `YII2-LOCAL-DATA-BOOTSTRAP-001`, `YII2-MAIN-NAVIGATION-001`.
- Public seams: `php bin/yii legacy-import/run --interactive=0`; semantic MAIN navigation returned by the five specified Yii HTTP GET routes.
- Initial verdict: `CHANGES_REQUESTED`; superseded by the approved correction rereview below.

## Findings

1. **HIGH — the navigation test does not cover the required absence branch of “iff `installers.read`”.** `specs/YII2-MAIN-NAVIGATION-001.md` requires `/pilot/installers` to be absent on every accessible scoped route without `installers.read`, but `tests/Yii2/yii2_main_navigation_001_test.php` only grants the permission and checks the positive membership. It never removes `installers.read` or reruns the accessible-route matrix. An implementation that always renders the link would satisfy the new assertions. Add a phase that removes only `installers.read`, asserts exact MAIN membership without `/pilot/installers` on all five still-accessible routes, and confirms reads remain fact-free. Page authorization need not be retested because the contract explicitly leaves it unchanged.

2. **MEDIUM — the normative navigation count is internally stale.** `specs/YII2-MAIN-NAVIGATION-001.md` item 4 still says “all four permissions” after adding `installers.read`; the enumerated navigation now depends on five permission values. Replace the count with “all listed permissions” (as already done in the acceptance example) or the correct count so the normative condition is unambiguous.

The import specification and test are otherwise approved for this correction scope. The fixture independently places `workdatefinish` after the fixed cutoff, exercises the real console seam, expects the row to remain eligible/imported with its mirrored completion normalized to `null`, checks associated details/template publication, idempotent replay, all-or-nothing failure behavior, source immutability, and secret redaction. Its expected values are explicit and deterministic.

## RED evidence

- `php tests/Yii2/yii2_legacy_import_db_001_test.php` failed for the intended missing behavior: expected eligible/imported/details/associations `3`, actual `2`.
- `php tests/Yii2/yii2_main_navigation_001_test.php` failed for the intended missing positive behavior: expected `/pilot/installers` in MAIN navigation on `/pilot/objects`, actual membership omitted it.
- Both commands used isolated test fixtures and reached their behavioral assertions; no setup failure and no full local suite run occurred.

## Required changes

Correct findings 1 and 2, capture fresh intended RED evidence, and resubmit the corrected spec/test delta for independent Gate 3 review before production implementation.

---

## Correction rereview — 2026-09-18

- Corrected reviewed source: dirty snapshot over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`; four-artifact binary diff SHA-256 `ee631662b9534634203e802f2a30fbd57cb62b940ee2c21b235cb6ee0040793d`.
- Independence is unchanged; this reviewer authored none of the corrected specification or test.
- Final verdict: `APPROVED`.

### Prior findings disposition

1. **Resolved.** The test removes only `installers.read`, runs the exact MAIN-membership matrix over all five scoped routes, proves `/pilot/installers` is absent while all other permitted links remain, checks the installer directory retains its `403`, verifies the reads and denial create no facts, and then restores the permission for the remaining matrix phases. This makes both a missing conditional link and an always-visible link observable regressions.
2. **Resolved.** Normative item 4 now says “all listed permissions,” consistently with the enumerated mapping and acceptance example.

### Corrected RED evidence

I independently ran `php tests/Yii2/yii2_main_navigation_001_test.php`. It reached the public HTTP/DOM assertion and failed for the intended positive missing behavior on `/pilot/objects`: expected `/pilot/installers` with label `Монтажники`, while actual MAIN membership omitted it. Exit was non-zero; there was no setup failure. The negative phase necessarily follows that positive assertion and is deterministically sensitive once the missing positive behavior is implemented.

### Complete findings

None for the agreed two-fix scope. Traceability, public-seam choice, independent expected values, positive and rejected permission cases, determinism, isolation, read-only behavior, replay coverage for the import, and intended RED sensitivity are adequate. Gate 4 may proceed against this reviewed source; later spec/test changes require delta review.

---

## Post-Gate-4 test-delta rereview — 2026-09-18

- Reviewed scope: only the root-authored changes to the three later expected-link arrays in `tests/Yii2/yii2_main_navigation_001_test.php`; production changes were explicitly excluded and are not approved here.
- Corrected four-artifact binary diff SHA-256: `65998fced5daa58e42e0f0a21d2555b303f62c1669598656d910e9cea713bf26` over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`.
- Verdict: `APPROVED`.

### Assessment

The no-objects, no-admin, and restricted-permission phases all execute after `installers.read` has been restored and none removes that permission. Adding `/pilot/installers` to each expected set therefore follows directly from the approved permission-to-link contract. The corrections preserve the intended removal of only the unrelated links, continue to exercise the five scoped HTTP routes through semantic DOM assertions, and retain the read-only and direct-route authorization checks. No expectation was weakened and no new gap was introduced.

### Verification

- `php tests/Yii2/yii2_main_navigation_001_test.php` — PASS.
- `php tests/Yii2/yii2_legacy_import_db_001_test.php` — PASS.

These GREEN results confirm the corrected expectations on the current worktree but are not a review or approval of the executor-authored production changes. The earlier source-bound intended RED evidence remains the Gate 2 witness.

### Complete findings

None for this corrected test delta. Gate 3 remains `APPROVED`; Gate 5 must independently review the production implementation.

---

## Planned-start eligibility scope expansion — Gate 3 review, 2026-09-18

- Owner-authorized delta: remove the `plannedStartDate >= 2026-10-01` eligibility restriction, consistent with the product rule “regardless of planned date.”
- Reviewed artifacts: `specs/PILOT-CASE-IMPORT-001.md`, `specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md`, and `tests/Yii2/yii2_legacy_import_db_001_test.php`.
- Five-artifact binary diff SHA-256: `81651c6491d561f869ad6b8fab56a629bbeaeebe4cbf3c1c1dcad960218989ed` over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`.
- Verdict: `CHANGES_REQUESTED`.

### Complete findings

1. **HIGH — `PILOT-CASE-IMPORT-001` still normatively publishes the removed reason and cutoff behavior.** Section 8's exact rejection JSON still includes `PILOT_PLANNED_START_BEFORE_CUTOFF` for row 4601. Section 13 still requires tests for the exact `2026-10-01` boundary and day-before case as cutoff behavior, and section 14 still describes the product boundary as planned start from `2026-10-01`. These statements contradict sections 5 and the current `PRODUCT.md`. Remove the obsolete reason from the exact section 8 result and revise the verification/reference statements so every normative and traceability section says planned date does not restrict eligibility.

2. **HIGH — the changed `PILOT-CASE-IMPORT-001` public seam lacks a positive before-cutoff candidate.** The Yii native-import test proves the new rule through its own `legacy-import/run` seam, but the inherited contract's declared seam is `bin/fmonitor2-import-cases.php`. Its existing test uses row 4601 with `2026-09-30` only in a rejection case that also has missing required data, PTO, and completion, so it cannot prove an otherwise-valid pre-October row is eligible. The malformed planned-date success case also cannot establish the calendar comparison boundary. Add an otherwise-valid row with a planned start before `2026-10-01`, invoke the declared CLI seam, and assert exact successful import and empty-case facts. Retain the rejection case's expected reason list without the retired reason to prove reason-code removal.

The new native Yii test expectations are otherwise sensitive and independently derived: fixture 509 differs from accepted candidates only by its `2026-09-30` planned start, and its exact ID, mirror, detail/association facts, aggregate counts, and replay count all require successful import. The specs correctly retain planned start as required data while removing its value as an eligibility threshold.

### RED evidence

I independently ran `php tests/Yii2/yii2_legacy_import_db_001_test.php`. It failed at the intended public result assertion: expected eligible/imported/details/associations `4`, actual `3`; no setup failure occurred.

### Required changes

Correct both findings and resubmit the resulting spec/test delta for independent Gate 3 rereview before implementing this scope expansion.

### Planned-start correction rereview — 2026-09-18

- Corrected six-artifact binary diff SHA-256: `2a24b4ec3138f8c3160637df8a932e0449afd765ab2fff25fbc6b3ea0415cc95` over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`.
- Independence is unchanged; the reviewer authored none of the corrected specifications or tests and did not review production code here.
- Final verdict for this expanded Gate 3 scope: `APPROVED`.

#### Prior findings disposition

1. **Resolved.** Section 8 explicitly states that row 4601's `2026-09-30` planned start is admissible by itself and its exact rejected reason list no longer contains the retired cutoff reason. The verification inventory requires otherwise-valid dates before/on/after the former boundary, and the evidence citation now matches the date-independent product rule. Repository search found no remaining retired reason or old cutoff-boundary wording in the reviewed contract/test pair.
2. **Resolved.** The explicit-ID CLI fixture now includes otherwise-valid row 4515 with planned start `2026-09-30`. The test invokes `bin/fmonitor2-import-cases.php`, expects exact successful import, and asserts the exact persisted empty-case shape. Together with row 4601's rejection list, it independently proves both eligibility and removal of the obsolete reason code at that public seam.

#### Verification

- `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_case_import_001_test.php` — PASS. An initial run without the environment override hit the test file's mismatched default DB credential and was a setup failure, not behavioral evidence.
- `php tests/Yii2/yii2_legacy_import_db_001_test.php` — intended RED: expected eligible/imported/details/associations `4`, actual `3`.

#### Complete findings

None for the corrected planned-start specification/test delta. Traceability, both public seams, exact expected values, reason-code removal, boundary sensitivity, deterministic fixtures, persisted facts, rejection atomicity, and replay counts are adequately covered. Gate 4 may implement this expanded scope against the reviewed source; subsequent spec/test changes require delta review.

---

## Required planned-date presence correction — Gate 3 review, 2026-09-18

- Scope: clarify and enforce that normalized planned start and planned finish remain required data after removal of the calendar threshold.
- Corrected six-artifact binary diff SHA-256: `0cbcd14d4f092c57255675a6d1d95d8d673310a1519c12ffe5dfdf13520cd469` over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`.
- Reviewer independence is unchanged; production code was not reviewed.
- Verdict: `APPROVED`.

### Assessment

The specification now explicitly distinguishes value-independent planned-date eligibility from required normalized presence: missing `plannedStartDate` or `plannedFinishDate` produces `LEGACY_OBJECT_REQUIRED_DATA_MISSING`, while no calendar threshold is restored. The test exercises the declared explicit-ID CLI seam with two independently shaped fixtures: row 4703 has no start, and row 4704 has neither adjusted nor fallback finish. It asserts ordered exact rejection output for both IDs, exit `2`, empty stderr, and no case facts in the isolated target. These expectations are deterministic, independently derived from the required-field list, and would detect either missing-field omission or accidental partial publication.

### RED evidence

I independently ran `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_case_import_001_test.php`. It failed at the intended assertion: expected exit `2` with `LEGACY_OBJECT_REQUIRED_DATA_MISSING` for rows 4703 and 4704; actual exit was `0` and both rows were imported. The test reached the public behavioral assertion without setup failure.

### Complete findings

None for this required-date spec/test delta. Gate 3 is `APPROVED`; the production correction and all current production changes remain subject to independent Gate 5 review.

---

## Replay date-representation canonicalization — Gate 3 review, 2026-09-18

- Scope: treat equivalent source timestamp/zero-date representations and canonical stored mirror dates as the same immutable facts on replay.
- Corrected six-artifact binary diff SHA-256: `b13603f405546b1fcf29a4482000be7cc0cfce1ef9b5660de09fb25729cf794a` over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`.
- Production code was not reviewed.
- Verdict: `CHANGES_REQUESTED`.

### Complete findings

1. **HIGH — the test proves replay currently fails, but does not prove canonical persisted values or immutable-fact preservation after the fix.** The fixture gives row 501 a timestamp-valued adjusted start and a zero-date PTO value, yet the first-import assertions inspect only mirrored `zavnumber`; they never assert that the target mirror stores the intended canonical date and `null` PTO representations. Before replay, `$snapshot` contains only installation-case identity/state, so a correction that suppresses `MIRROR_CONFLICT` while rewriting the mirror or another associated fact could still pass. This is material because the amended contract explicitly requires comparison of equivalent facts and says replay must not rewrite history. After the first import, assert row 501's exact canonical mirrored date/PTO fields. Capture all relevant immutable published facts (at minimum the complete mirrored object row plus case, detail, template association/snapshot, and provenance rows) before replay and assert byte-equivalent database results afterward. Keep the exact replay counts at zero.

The fixture otherwise isolates the intended mismatch well: the first import succeeds, timestamp and zero-date source variants are used by an imported candidate, and replay reaches the real CLI seam with the same cutoff.

### RED evidence

I independently ran `php tests/Yii2/yii2_legacy_import_db_001_test.php`. The first import and preceding assertions succeeded; replay then failed at `repeat succeeds`, expected exit `0`, actual exit `70`. This is the intended missing canonical-comparison behavior, not a setup failure.

### Required changes

Add exact first-import canonical mirror assertions and complete before/after replay preservation assertions, then resubmit this spec/test delta for independent Gate 3 rereview before production implementation.

### Replay canonicalization correction rereview — 2026-09-18

- Corrected six-artifact binary diff SHA-256: `2262408106b66a5389ae69e5836628a064703dbae66b628d8521564df001215e` over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`.
- Independence is unchanged; production code was not reviewed.
- Final verdict for this replay-canonicalization Gate 3 scope: `APPROVED`.

#### Prior finding disposition

**Resolved.** The test now asserts the exact first-import canonical mirror for row 501: `workdatestartadjusted = 2026-10-02` and zero-date PTO becomes `null`. Before replay it captures complete ordered `SELECT *` snapshots of the mirror, installation cases, object details, template snapshots, template associations, and classification provenance. After the expected zero-import/zero-detail/zero-association replay, it compares every captured table exactly. A correction that merely suppresses conflict, rewrites a mirror value, duplicates an association, or alters provenance can no longer pass.

#### RED evidence

I independently ran `php tests/Yii2/yii2_legacy_import_db_001_test.php`. It failed at the new intended first-import canonical assertion: expected adjusted start `2026-10-02` and PTO `null`; actual values were raw `2026-10-02 12:34:56` and `0000-00-00 00:00:00`. All preceding import/count assertions succeeded, so this is behavioral RED rather than setup failure.

#### Complete findings

None for the corrected replay-canonicalization spec/test delta. Gate 3 is `APPROVED`; production implementation remains subject to Gate 5 review.
