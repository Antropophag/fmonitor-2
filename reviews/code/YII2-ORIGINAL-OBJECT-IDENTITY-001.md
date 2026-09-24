# Final review: YII2-ORIGINAL-OBJECT-IDENTITY-001

- Reviewer: `issue243_final_review` (independent Gate 5 reviewer; authored neither product, specification nor tests)
- Authorship: `root` authored specification/tests; `issue243_executor` authored product implementation
- Base: `origin/main` / `b1542f92009b8dc4216a36962ff38a51e0b6c388`
- Reviewed Git HEAD: `aec7b4804bb3762031667a7853f4f571865d42ca`
- Exact candidate source: `21b5d9b175f679c2a7237a5b1d1f85d620734d9b120e6b118bda54c7f4cd2a80`
- Exact executable source: `9991d0bef4643038a34009361329c605fb514e1c50469140f23a830d08d14672`
- Verification plan SHA-256: `7595a2e26d2094af26544442d90750e4ee2e33ed2b32b4389be3fb4bb8d73862`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T023331Z-dd548038b0/package.json`
- Planner classification: `CRITICAL`; required reviews `gate3`, `final`; required categories `e2e`, `governance`, `integration`, `unit`
- Gate 3: approved after two correction rounds and one test-delta rereview, recorded in `reviews/tests/YII2-ORIGINAL-OBJECT-IDENTITY-001.md`
- Exact GREEN evidence: `1790217164989456000-022fe37dd065453f847a19d05ac4c8d7` (`php tests/Yii2/yii2_original_object_identity_001_test.php`) and `1790217180957738000-1472efec8a52495284d050a257eb1e4d` (`php tests/Yii2/yii2_original_transport_001_test.php`), both bound to the candidate/executable sources above
- Reviewed scope: complete `origin/main...HEAD` diff, A1-A8, OpenSpec artifacts, Gate 3 history, focused evidence, effective-details ownership, authorization ordering, escaping, IDs/document labels, append-only originals/replay/downloads, integration boundaries, maintainability and test sensitivity
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — OI-01.2 and A1-A3 are only partially implemented, and the focused test masks the missing breadcrumb fields.** The contract first enumerates initial form, correction form, their breadcrumbs and linked history in OI-01.1, then requires the same surfaces to show effective address/entrance and factory number in OI-01.2. However, `app/YiiRuntime/Views/original.php:17` and `app/YiiRuntime/Views/original-history.php:22` render only registration number and address in their breadcrumbs. Entrance and factory number, including the required missing-factory label, are absent there. The test mirrors the underimplementation: `tests/Yii2/yii2_original_object_identity_001_test.php:25` and `:46` scope only registration/address inside form breadcrumbs, while no scoped history-breadcrumb assertion exists. Render the required entrance and factory value or missing label on each specified breadcrumb surface, then add independent scoped assertions for initial, correction and history breadcrumbs across imported, manual/hostile and missing examples. Re-capture exact-source focused evidence and return for final rereview.

2. **Low — possible Duplicated Code (maintainability judgment call).** `app/YiiRuntime/Views/original.php:10-14,17,28` and `app/YiiRuntime/Views/original-history.php:11-15,22,24` independently repeat identity normalization, fallback labels and identity presentation. The present breadcrumb drift demonstrates the maintenance risk between surfaces required to remain consistent. Consider extracting a narrow shared presentation partial/value formatter without changing common `ViewSupport`, CSS/JS, or introducing another effective resolver. This finding alone would not block Gate 5, but should be considered while correcting finding 1.

## Confirmed properties

- The implementation reuses the existing public `MariaDbEffectiveObjectDetails` owner through `PreopeningResources`; it does not create a second resolver or persistence projection.
- Effective reads occur after existing original-surface admission and object/order resolution. The dependency-failure probe is sensitive to denied/unavailable paths reading too early.
- Effective values used by the changed views are HTML-escaped; hostile values remain text.
- Technical object/order/revision IDs remain in URLs, hidden fields, data attributes and download relationships. Existing order version, revision and document labels remain intact.
- No writer, schema, PDF, replay/idempotency, correction append-only history or download logic is changed. Exact focused evidence confirms current implementation behavior, but GREEN evidence does not override the uncovered normative gap above.
- No unrelated product scope creep or hard repository-standard violation was found.

## Required correction

Resolve finding 1 and strengthen the acceptance test so the corrected behavior is independently observable on every required breadcrumb surface. Then prepare a fresh exact-source package and repeat Gate 5. Finding 2 is advisory unless the correction adds further drift.

## Gate 5 rereview — corrected candidate 178a512d

- Reviewer: `issue243_final_review` (same independent final reviewer; authored neither product, specification nor tests)
- Authorship remains: `root` specification/tests; `issue243_executor` product implementation
- Reviewed Git HEAD: `1a84980c039dad06b85ae4fcd5718568fc8abcd6`
- Exact candidate source: `178a512d2627fca11e8aacd2e7656facc83b6eb4858ec6317cf4cbc5f0cc3d67`
- Exact executable source: `ea80d72ab147bcda6159aa32cfadf7014bbd9214aab984a600e7c05ccfca0100`
- Verification plan SHA-256: `8072fa40d5d85b67bbb4fe347f51ac1afb8bfac9deccf03ce9b6b806c86fe7f1`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T024234Z-a49dd83585/package.json`
- Planner classification remains `CRITICAL`; required reviews `gate3`, `final`; required categories `e2e`, `governance`, `integration`, `unit`
- Exact GREEN evidence: `1790217707076999000-baa1f534eb624536999ef3602bc9618a` (`php tests/Yii2/yii2_original_object_identity_001_test.php`) and `1790217722449315000-835af71a83c24dc2973b4a75e215b006` (`php tests/Yii2/yii2_original_transport_001_test.php`), both clean and bound to the candidate/executable sources above
- Verdict: `APPROVED`

### Prior findings disposition

1. **High breadcrumb completeness — fixed.** `app/YiiRuntime/Views/original.php:17` and `app/YiiRuntime/Views/original-history.php:22` now render registration number, address, entrance and factory number (or the explicit missing factory label) in the breadcrumb. Every effective value remains HTML-escaped. The correction changes no URL, command, hidden ID, document label, writer or persistence boundary.
2. **Test sensitivity — fixed and independently approved at Gate 3.** `tests/Yii2/yii2_original_object_identity_001_test.php:25,46,52` extracts separate breadcrumb fragments and requires the complete imported identity on initial, the complete escaped manual/hostile identity on correction and history, and all available/missing labels on both missing-state surfaces. The test-delta RED failed specifically at missing scoped `Подъезд 2`; the corrected exact candidate completes GREEN, including desktop/320px browser coverage.
3. **Low duplicated presentation code — accepted as nonblocking advisory.** The two views still repeat the small normalization/fallback/presentation shape. A shared partial could reduce future drift, but introducing a common `ViewSupport`/presentation abstraction is outside this bounded change and is unnecessary for current correctness. No extraction is required for this Gate 5 verdict.

### Full current findings list

No blocking findings. One nonblocking Low maintainability advisory remains: the identity presentation is duplicated between the two original views. It does not violate a documented repository rule, create a second resolver, or weaken current A1-A8 behavior.

### Final assessment

The complete `origin/main...HEAD` candidate now conforms to A1-A8. It reuses `MariaDbEffectiveObjectDetails`, preserves authorization-before-effective-read, escapes hostile values, supplies explicit missing labels, retains technical IDs and document identity, and leaves append-only originals, replay, downloads and integration boundaries unchanged. The corrected scoped assertions close the prior test blind spot, and the exact focused records are GREEN. No additional changes are required for Gate 5.
