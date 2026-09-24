# Independent Gate 5 final review — ADMIN-INTEGRATION-STATUS-001

- Reviewer: separately tasked agent `issue30_gate3`, acting as final reviewer; authored none of the specification, tests, production implementation, OpenSpec artifacts, or verification evidence.
- Review date: 2026-09-24.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T021336Z-40890aba85/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `3b62b0fbaed160364da0bc99e467d0e1a556cf801c1bdfbdcd915c7a3d65626e`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T021336Z-40890aba85/snapshot/source.patch`, SHA-256 `a263c4adf5f1fb582ec17320462b78562defaa52ac3a8e0c9ca8131ae991b8e6` (matches `snapshot/manifest.json`).
- Reviewed contract and scope: `specs/ADMIN-INTEGRATION-STATUS-001.md`, issue #30 updated 2026-09-24, the `add-integration-status-admin` OpenSpec artifacts, the complete candidate diff, and the append-only Gate 3 record.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T021336Z-40890aba85/verification-plan.json`, package-declared SHA-256 `88638f122b351ba00c413d16f5887c0b2f70e6a2a994973c675eca26c4193b05`; lane `CRITICAL`, required reviews `gate3` and `final`.

## Findings

1. **HIGH — the nominally read-only GET/HEAD view explicitly creates server-side session state to make response bytes stable.** Location: `app/YiiRuntime/Views/integration-status.php:6-8,24`; related test: `tests/Yii2/yii2_integration_status_001_test.php:23`. On the first page read, the view stores `integration-status-csrf` in `ReliableSession`, whose configured owner is filesystem-backed durable session storage. It then rewrites every occurrence of the current masked CSRF token in the rendered shell to this stored token. This is an observable mutation caused by GET/HEAD and is unrelated to the integration read model, contrary to A4's read-only contract. It also couples presentation to a stale auxiliary CSRF representation: if Yii rotates the underlying token while the session key survives, the rewritten logout token can be invalid. The implementation exists only to satisfy byte-for-byte response equality even though Yii intentionally randomizes masked CSRF representations. Correction: remove the integration-specific session read/write and document-wide token replacement. Amend the test to compare the durable facts and stable semantic content while normalizing or excluding the intentionally volatile CSRF field; because that changes an approved test expectation, recompute the plan and obtain the required Gate 2/3 delta approval before reimplementation.

2. **HIGH — a failed jobs projection is indistinguishable from a successful empty dead-job list.** Locations: `app/YiiRuntime/Controllers/IntegrationStatusController.php:46-47,77-83,127-135`; `app/YiiRuntime/Views/integration-status.php:22`. `safeRead()` correctly returns `available=false` if `fm2_jobs` is absent or its query fails, but the jobs section never inspects `jobs['available']`; it always renders an empty table and no error state. This violates the issue and delta requirement that a read/source failure be distinguishable from a successful empty result and contradicts the design's fail-closed `Данные недоступны` outcome for absent/partially updated schema. The focused unavailable fixture renames only `fm2_equipment_fact_runs`, so it cannot catch this regression. Correction: render an explicit safe jobs error state when `available=false`, retain a distinct empty state for an available zero-row result, and extend the approved public-seam fixture to exercise jobs unavailability and both outcomes. The test change requires Gate 2/3 delta review.

3. **MEDIUM — available empty diagnostic lists do not use or expose the required empty-state contract.** Locations: OpenSpec safe-presentation requirement; `app/YiiRuntime/Views/integration-status.php:20-22`. All three sections always render a table with an empty `<tbody>` when the query succeeds with zero rows. There is no `shlz-empty-state`, `shlz-table__empty`, or explanatory `Нет данных` outcome tied to the list. This makes a genuinely empty successful list visually ambiguous and fails the explicit requirement to use existing table/empty/error contracts. Correction: render the established empty-state component for each available zero-row list and add focused DOM assertions distinguishing empty success from unavailable error.

4. **MEDIUM — partial database initialization can leak the native connection.** Location: `app/YiiRuntime/Controllers/IntegrationStatusController.php:61-65`. If `new mysqli(...)` succeeds but `set_charset('utf8mb4')` throws, `actionIndex()` never receives `$db`, so its `finally` cannot close the allocated connection. This pattern has already required explicit lifecycle handling elsewhere in the repository. Correction: guard `set_charset` inside `connection()` and close before rethrow on partial initialization; retain the existing outer `finally` for fully initialized connections.

## Conformance assessment

The authorization and primary data boundary are otherwise sound. The route is explicitly `GET,HEAD` with a fallback for other methods. `AccessControl` delegates the `access.administer` role to Yii's configured `canonicalAccess`; that checker reads canonical local authorization facts, and the policy requires both `status=1` and `activation_state='active'`, so a currently blocked session is denied. Navigation uses the same checker and capability.

The reader uses fixed table names behind a validated prefix, fixed page size 25, SQL `COUNT` plus `LIMIT/OFFSET`, deterministic ordering, and page clamping. Current columns match the workforce, equipment, diagnostics, and jobs schemas. Workforce and ERP latest attempt and latest completed success are queried separately. ERP `receipt_json` is decoded only to a five-key nonnegative integer allowlist and removed before rendering; raw payloads, actor JSON, job payload/result, provider details, URLs, DSNs, tokens, and exception text are not selected. Persisted visible strings are escaped, ERP order identity is truncated HMAC, and `safeRead` suppresses exception detail. No integration transport, retry, queue command, cron, SMTP, or writer owner is composed by the authorized GET.

These strengths do not resolve the read mutation and unavailable/empty-state defects above.

## Verification evidence

The package retains three GREEN records bound at start and end to exact source `3b62b0fbaed160364da0bc99e467d0e1a556cf801c1bdfbdcd915c7a3d65626e` with no reported source drift:

- `1790215930670713000-08d5d93600974ee08c44fdc365a9f6bb`: `php tests/Yii2/yii2_integration_status_001_test.php`, GREEN.
- `1790215940882880000-753ae1cdfb364513ab7971f972d4e963`: `python3 tests/Verification/change_verification_001_test.py`, 18/18 GREEN.
- `1790215972438003000-7b2dd832dcfa4248af04cc6b4cc97f33`: `python3 tests/Verification/architecture_guard_001_test.py`, GREEN.

I independently reran the same three bounded commands at the reviewed source. The focused HTTP/browser test passed, verification planner tests passed 18/18, and the architecture guard exited 0. The focused test snapshots integration/jobs/outbox tables, but it does not inspect filesystem session content and therefore misses finding 1. Its unavailable scenario covers ERP only, and its populated-list browser scenario does not cover successful empty states, so the GREEN result is compatible with findings 2 and 3. Full exact-source CI, PR, merge, and deployment remain `UNKNOWN` and are not treated as GREEN.

## Verdict

`CHANGES_REQUESTED`

Gate 5 is blocked. Return the session-stabilization expectation and jobs unavailable/empty behavior to Gate 2, obtain the required independent test-delta approval, correct the production lifecycle and presentation findings, rerun the selected focused commands at a fresh exact source, and submit the complete correction delta for independent Gate 5 rereview.

---

## Gate 5 correction rereview — 2026-09-24

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022059Z-a2626d9ce5/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `1dd21fb99f3f8e3ff6d166f5db5322c36fff6393605d5b34c1510d8064f58588`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022059Z-a2626d9ce5/snapshot/source.patch`, SHA-256 `7c64528ecb7a22eef71af040d34a720ac6a970d970354465d5ac73d129bd0ddb` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022059Z-a2626d9ce5/verification-plan.json`, package-declared SHA-256 `f4cbf3d328168b1b3b59fc6eb4fd52f5c94a5b4fc1a6cb89a89a658df45b1e8f`.
- Reviewed the complete source plus the correction delta. Independence is unchanged.

### Prior-finding disposition

- Finding 1 production behavior is fixed. `integration-status.php` no longer reads or writes `integration-status-csrf`, buffers the document, or replaces Yii's masked CSRF representation. The separately approved Gate 3 delta now checks repeat semantic timestamps rather than byte-identical HTML.
- Finding 2 production behavior is fixed. The jobs section renders `Данные заданий недоступны` when `available=false`, distinct from its successful empty outcome.
- Finding 3 production behavior is fixed. Workforce, ERP diagnostics, and jobs each render an explicit `shlz-empty-state` with source-specific copy when the read is available and has zero rows; unavailable reads use a separate danger alert.
- Finding 4 production behavior is fixed. `connection()` now closes the allocated `mysqli` if `set_charset` throws and rethrows the original error, while `actionIndex()` retains its outer `finally` for a fully initialized connection.

### Remaining finding

1. **HIGH — the corrected error/empty and partial-initialization branches have no regression-sensitive executable coverage.** Locations: prior findings 2–4; `tests/Yii2/yii2_integration_status_001_test.php:20-28`; corrected `app/YiiRuntime/Views/integration-status.php:16-18`; corrected `app/YiiRuntime/Controllers/IntegrationStatusController.php:61-70`. The only approved test delta changes repeat-body comparison. The focused test still populates all three lists for its main/browser scenario, forces unavailability only by renaming `fm2_equipment_fact_runs`, and never requests the page with an unavailable `fm2_jobs` projection or any available zero-row workforce/ERP/jobs list. It therefore remains GREEN if the new jobs error branch is removed or if any/all new empty states regress back to blank tables. It also cannot fail if the new close-before-rethrow is removed. Gate 5 must establish that the tests catch plausible regressions, and these are the exact regressions that caused the prior return. Correction: add public-seam scenarios for jobs-table unavailability and available zero-row outcomes for each list, asserting distinct safe error versus source-specific `shlz-empty-state` content. Add a narrow lifecycle witness for close-on-`set_charset` failure (an injectable connection factory/fault seam or a bounded structural ownership assertion consistent with repository practice). Recompute the plan, obtain required independent Gate 3 approval for that test delta, implement as needed, and return the complete exact source.

### Evidence

The package retains three GREEN records bound to source `1dd21fb99f3f8e3ff6d166f5db5322c36fff6393605d5b34c1510d8064f58588`:

- `1790216386524671000-80fe4ebae43d441b81d378486ff3cb5f`: focused HTTP/browser acceptance GREEN.
- `1790216395928798000-5899513a501d497b91bebaa9a9f4149d`: verification planner 18/18 GREEN.
- `1790216424824509000-add7e6a4afce42cea1274829293999ad`: architecture guard GREEN.

I independently reran all three commands at the exact reviewed source; they are GREEN. Those results confirm the existing populated-list, ERP-unavailable, authorization, paging, leakage, and governance matrix, but they do not execute the corrected branches identified above. Exact-source CI, PR, merge, and deployment remain `UNKNOWN`.

### Correction verdict

`CHANGES_REQUESTED`

The production corrections are sound, but Gate 5 remains blocked until their plausible regression paths are covered by an independently approved test delta and the corrected exact source is resubmitted. No production change beyond preserving the four current fixes is requested unless the lifecycle test requires a narrow injectable seam.

---

## Gate 5 final correction rereview — 2026-09-24

- Final package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022606Z-a219e05f92/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, source digest `cfcd8ddb1a7551c1028da61e433cabc50fe7b2b13917376ac66108632fa374dd`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022606Z-a219e05f92/snapshot/source.patch`, SHA-256 `c529d0449a82b9ca1560f7a5c979d69ba47ddb4fc5855feeab0ca6753aee4e5f` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022606Z-a219e05f92/verification-plan.json`, package-declared SHA-256 `0bd3b42e451c8f1c363ec8f37d0609f255f4d941656512b22d59dab581dd82ca`.
- Reviewed the complete candidate, the previous Gate 5 findings and correction disposition, and the independently approved Gate 3 regression delta at source `7ea13c5fca2d627fea16722d27b9419d5b615ec243d65f75ba201e157f65a368`. Reviewer independence remains unchanged.

### Final assessment

No findings.

All prior production findings remain fixed: GET/HEAD no longer creates integration-specific session/CSRF state; jobs unavailability is distinct from successful empty results; workforce, ERP diagnostics, and jobs use explicit source-specific shlz-ui empty states; and partial `mysqli` initialization closes before rethrow while fully initialized connections remain under the action's `finally`.

The sole remaining correction-rereview finding is now resolved by the independently approved test delta:

- deleting all qualifying workforce, ERP diagnostic, and dead-job rows requires all three distinct successful-empty messages through the authenticated public HTTP seam;
- renaming `fm2_jobs` requires the jobs-specific unavailable message, forbids the sensitive table-name canary, and restores schema in `finally`;
- the ordered lifecycle guard requires `try`/`set_charset`/`catch Throwable`/`close`/rethrow of the same captured error before returning the connection.

The complete suite therefore catches plausible regressions in every returned branch while retaining the previously approved actor/method, latest-attempt versus latest-success, zero-count success, never-run, ERP unavailable, paging, escaping/redaction, no-durable-mutation, authorized load closure, desktop/narrow, and keyboard behavior. No expectation was weakened after the last Gate 3 approval.

### Exact-source evidence

The final package retains three GREEN records bound to exact source `cfcd8ddb1a7551c1028da61e433cabc50fe7b2b13917376ac66108632fa374dd`:

- `1790216690334047000-f197299ac8b7443fa47d9b5000c9f7ff`: `php tests/Yii2/yii2_integration_status_001_test.php`, GREEN.
- `1790216699968872000-6f5068d29296440a8c0808cd0968bd9f`: `python3 tests/Verification/change_verification_001_test.py`, 18/18 GREEN.
- `1790216732559794000-8ef4f2f44b5a4194b4599b26c49c6efe`: `python3 tests/Verification/architecture_guard_001_test.py`, GREEN.

I independently reran the same three selected commands at this exact source. The focused HTTP/browser acceptance passed, verification planner passed 18/18, and architecture guard exited 0. No local full `make test`/`make verify` was run, consistent with the owner decision. Exact-source full CI, PR, merge, and deployment remain `UNKNOWN` and are not implied by this review.

### Final verdict

`APPROVED`

Gate 5 passes for exact source `cfcd8ddb1a7551c1028da61e433cabc50fe7b2b13917376ac66108632fa374dd`. The candidate may proceed to commit/PR and the one required exact-source CI run under the delivery process. Any subsequent code or test change requires refreshed exact-source review appropriate to that delta. Merge and deployment are not authorized by this verdict.

---

## Gate 5 CI architecture correction rereview — 2026-09-24

- Exact reviewed current-worktree source: candidate digest `d1647e78f119e8773ab4ba10c3b568563ba59dbad3259ea739cc18b1194503f3` over base `b1542f92009b8dc4216a36962ff38a51e0b6c388`, as reported by `python3 tools/delivery/harness.py state`.
- The active prepared package remains the preceding package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022606Z-a219e05f92/package.json` for source `cfcd8ddb1a7551c1028da61e433cabc50fe7b2b13917376ac66108632fa374dd`; a refreshed reconstructible package was not supplied with this rereview request. This verdict is bound to the stated current-worktree digest and requires refreshed packaging/evidence before publication.
- Review scope: complete current candidate, with special attention to the production refactor in `IntegrationStatusController`, the independently Gate 3-approved test/inventory delta, and preservation of every earlier Gate 5 correction.
- Reviewer independence remains unchanged.

### Assessment

No findings.

The controller now uses the configured application `Yii::$app->db` connection and `yii\db\Query` rather than constructing a second native `mysqli` lifecycle. The translation preserves the reviewed read model exactly:

- workforce latest attempt orders by `started_at, run_id` descending, latest success filters `completed` and orders by `completed_at, run_id`, and missing rows filter `reconciliation_state=missing_from_delivery`;
- ERP latest attempt/success keep their `observed_at, run_id` ordering, completed filter, receipt count allowlist, and diagnostic reason allowlist;
- jobs retain the dead-status filter, exact safe selected columns, ascending deterministic ID order, SQL-level count, fixed limit 25, and bounded offset;
- the validated table prefix remains the sole dynamic table-name input, now quoted by Yii's query builder; no raw SQL or command/writer API is introduced.

`safeRead` continues to isolate workforce, ERP, and jobs read failures independently. The request-scoped application connection is closed in the existing `finally`; Yii's connection component can reopen lazily if a later shared component needs it, and the rendered HTTP/browser matrix confirms the subsequent canonical navigation composition remains functional. The prior partial-native-initialization leak is eliminated because this controller no longer constructs or initializes a native connection.

All earlier fixes remain present: no integration-specific session/CSRF mutation, distinct jobs unavailable and successful-empty outcomes, source-specific shlz-ui empty states, canonical active/blocked access, bounded independent pagination, safe receipt/count extraction, encoded persisted values, and no integration/job/transport writer composition.

The root-authored CI corrections are also complete and were independently approved at Gate 3 for this digest. The integration acceptance is classified once as DB/integration; the shared navigation oracle includes the new route in exact membership, order, group, pinned icon, capability combinations, and direct denial; and the architecture witness rejects reintroduction of private `mysqli`, charset lifecycle, or raw SELECT/DML ownership while requiring the Yii connection and query builder.

### Verification

I independently ran at candidate digest `d1647e78f119e8773ab4ba10c3b568563ba59dbad3259ea739cc18b1194503f3`:

- `php -l app/YiiRuntime/Controllers/IntegrationStatusController.php` — GREEN.
- `php tests/Yii2/yii2_integration_status_001_test.php` — GREEN.
- `php tests/Yii2/yii2_main_navigation_001_test.php` — GREEN.
- `python3 tests/Verification/change_verification_001_test.py` — 18/18 GREEN.
- `python3 tests/Verification/architecture_guard_001_test.py` — exited 0.

The user-reported CI evidence is GREEN, but the active harness binding does not yet contain refreshed exact-source CI metadata for this digest. CI admission, merge, and deployment therefore remain `UNKNOWN` in this review.

### Final correction verdict

`APPROVED`

Gate 5 passes for current candidate digest `d1647e78f119e8773ab4ba10c3b568563ba59dbad3259ea739cc18b1194503f3`. Refresh the reconstructible package and exact-source evidence before publication/admission. Any subsequent production or test change requires review of that delta. This verdict does not authorize merge or deployment.
