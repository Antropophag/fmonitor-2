# Test review: YII2-DOCUMENTARY-CLOSURE-001

- Reviewer: Codex independent reviewer `/root/documentary_gate3`; authored neither the specification nor the tests.
- Test author: root agent under the owner-authorized continuation of #76 recorded in the specification.
- Reviewed source: base commit `ee8fade4e240f7ad5886be616e89921954e36a20` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T184436Z-4f1a92bb8f/snapshot`, patch SHA-256 `9979c190ac62dcdeaf637c015a103c4d08e3e45fcf2990f69eb49ea8f7f31c8c`, source digest `cce4647daeed64322abfa6471a7f58f5d655808863d8c477a0cccb80bcbb1e1b`.
- Snapshot verification: restored with `tools/delivery/review-source.py` to a private detached worktree; restored HEAD is the stated base, patch digest matches `manifest.json`, and `harness.py state` reports the stated source digest. `harness.py doctor` reports `integration: CONFIGURED`.
- Verification plan: package `20260910T184436Z-4f1a92bb8f`, SHA-256 `5b32144216e455020b43c5da9c9656810ac316422a56a816f4a3b0962ea9c3d3`; A1-A7 map to the HTTP, concurrency and browser tests and the plan has no unresolved coverage marker.
- RED evidence: HTTP record `1789065847125700000-0c0a8072200b4487811d5e1e53467f4f` reaches a real opened object and fails on the absent completion route (`404` instead of the specified below-85 `409`); concurrency record `1789065847125212000-e97f5ecc03b040b8955043e6ea6c1b3c` reaches two real servers and sessions after 85% setup and fails because both absent routes return `404` before reaching the held case lock; browser record `1789065847118165000-9bb1e60ffa4a46b894d774b9dd5c0506` completes real login/card setup and fails on the missing PTO form. All three records have source digest `cce4647daeed64322abfa6471a7f58f5d655808863d8c477a0cccb80bcbb1e1b`, no source drift, and are valid intended REDs rather than setup failures.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **High — the candidate breaks the repository verification-inventory baseline and therefore does not satisfy A7's claim that all new tests are included in the active inventory.** The snapshot adds the two DB members at `tools/verification/suites.tsv:361-362` and the E2E member at `tools/verification/suites.tsv:363`, but does not add those exact permitted members to `tests/Verification/verification_inventory_001_test.py:107-223`. Retained record `1789065916226728000-1153a46f67f843e095d7dac1eeedc3fd` fails `test_repository_baseline_membership` with DB baseline drift (`ecb69c...` expected, `9d62e1...` actual). Add the two documentary DB lines and one documentary E2E line to the test's explicit `added_by_suite` allowance, preserving the fixed legacy baselines, then retain corrected evidence and regenerate the exact-source package/plan.

2. **High — A5's cross-type serialization rule is not tested.** `specs/YII2-DOCUMENTARY-CLOSURE-001.md:43` requires the owner case lock to serialize concurrent PTO/declaration attempts so that a declaration which enters before PTO is rejected and can be accepted only after PTO commits. `tests/Yii2/yii2_documentary_concurrency_001_test.php:27-30` races PTO/PTO, correction/correction and declaration/declaration, while the only PTO prerequisite check is sequential in the HTTP test. An implementation can lock per fact type, pass every current race, and violate the required PTO/declaration ordering. Add a distinct-process PTO/declaration race using the existing held-lock barrier; assert the result/fact set for the actual serial order and prove a rejected declaration succeeds when retried after the accepted PTO.

3. **High — A1 transport and read admission remain materially insensitive.** `specs/YII2-DOCUMENTARY-CLOSURE-001.md:15-17` requires `objects.read` for GET/HEAD and rejection of non-string fields, including arrays. `tests/Yii2/yii2_documentary_http_001_test.php:53-62` checks a successful reader, guest POST, and only an array-valued `action`; it never removes/denies `objects.read`, and it does not submit array values for dates, details, `factId`, or reason. A controller that ignores card read capability or lets PHP array coercion/TypeError produce `500` for these fields can pass. Add revoked/absent `objects.read` GET and HEAD cases with no protected document/history disclosure, plus representative array-valued fields for every action family with exact allowed `400/422` outcomes and unchanged-facts assertions.

4. **Medium — the rendered history and browser form contract is only partially asserted.** A4 requires every root/revision row to show ordered history, author name with stable user ID, time, date, reason and effective details (`specs/YII2-DOCUMENTARY-CLOSURE-001.md:39`); A6 requires accessible submit controls and the specified native `required`/`max`/`maxlength` constraints at desktop and 390px (`:49`). The HTTP checks at `tests/Yii2/yii2_documentary_http_001_test.php:42-50` only search for unordered dates/text and escaping, and `tests/Yii2/documentary_browser.mjs:21-26` checks labels and overflow only. A page can omit author/time/IDs, reorder revisions, or omit field constraints and still pass. Assert one literal root-plus-two-revision rendering in order, including author name/ID and bounded displayed time, and inspect the exact constraints plus visible/usable submit controls at both viewports.

## Disposition

The specification is traceable to a real Yii HTTP/browser seam, uses the existing InstallationProcess mutation owner, preserves actor derivation and append-only facts, supplies independently determined literal progress/persistence expectations, and uses a private canonical database with fixture-owned teardown. The three focused REDs are credible. Findings 1-4 leave foreseeable A1, A4-A7 regressions undetected, so implementation must not begin from this candidate. Submit the corrected whole Gate 2 candidate with a new reconstructible snapshot, regenerated plan and fresh intended RED evidence.

## Rereview 1 — corrected whole candidate

- Reviewed source: base commit `ee8fade4e240f7ad5886be616e89921954e36a20` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T185032Z-6a366fc56b/snapshot`, patch SHA-256 `495ab3facd2539c494c28a9055e091ee4ddd40ec3f60b4bcd6796f7fc272cd26`, source digest `f1214e53d1688fc967f5e33eebfc9a8280eb884b3fdc6d09069c5be70e331e8c`.
- Snapshot verification: restored to a private detached worktree; restored HEAD and patch digest match the manifest, `git diff --check` is clean, and `harness.py state` reports the exact evidence source digest.
- Verification plan: package `20260910T185032Z-6a366fc56b`, SHA-256 `22723c8203cd0a32e6ae29c323b8e6c9225c55f6d562dcb5b4bfaa56d261b0ed`, no missing mapped tests.
- Corrected RED evidence: HTTP `1789066191011070000-cd87ce9da3484276b414951005e5f464`, concurrency `1789066191011101000-e191bc461e0a4c16a01364b8dce0853f`, and browser `1789066191011089000-1057556090a241d185a056a97880e951`; all are intended missing-route/form failures on source `f1214e53...` with no drift. Inventory record `1789066192201132000-e39114f8955e4ac3b2570f6afcc0bc4d` is GREEN (16 tests) on the same source.
- Prior findings disposition: findings 1-3 are resolved. Finding 4 is substantially addressed but remains incorrect/incomplete as detailed below.
- Verdict: `CHANGES_REQUESTED`.

### Findings

1. **High — the history test contradicts the new normative selector contract.** The amended `specs/YII2-DOCUMENTARY-CLOSURE-001.md:39` says each public history row is identified by both `data-completion-history` and `data-completion-version`. The XPath in `tests/Yii2/yii2_documentary_http_001_test.php:43` instead selects a version-bearing descendant of an element with `data-completion-history`; XPath `//*[...]//*[...]` excludes the ancestor itself. A conforming implementation that places both required attributes on each row will yield zero matches and fail, while a structure that puts the history attribute only on a wrapper can pass without each row carrying the required type identity. Select rows with both attributes on the same element, for example `//*[@data-completion-history="declaration" and @data-completion-version]`, and retain the existing exact ordered row assertions.

2. **Medium — the browser constraint correction still omits the PTO correction form and does not prove vertical viewport availability.** `tests/Yii2/documentary_browser.mjs:22-29` inspects record PTO, record declaration and correct declaration, but never `correct_pto`, so its date/reason `required`, `max` and `maxlength` contract can regress unnoticed. The submit check at `:19` verifies only left/right bounds after scrolling; a control outside the vertical viewport still passes because `isVisible()` means rendered visibility, not intersection with the viewport. Inspect `correct_pto` after the two roots exist and assert `top >= 0` and `bottom <= innerHeight` alongside the horizontal bounds at both widths.

### Disposition

The inventory allowance preserves the legacy hashes and now passes; revoked `objects.read` and every action-family array field are covered with no-write checks; the fresh cross-type race proves both requests reach the common held lock, validates either real serial order, and retries the declaration after the rejected order. Correct the two remaining test-oracle issues, capture a fresh exact-source package and RED evidence, and resubmit the focused delta. Implementation remains blocked at Gate 3.

## Rereview 2 — rebuilt whole A1–A7 candidate

- Reviewed source: base commit `ee8fade4e240f7ad5886be616e89921954e36a20` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T185429Z-08b52b6b9c/snapshot`, patch SHA-256 `3fa2b07f5e036415e2690838e65bf400ea09a02f4bda7559c4c378dc557e5a98`, source digest `42e0cf30ea1f855ea9d6cf37d9b8d47a0c0e12c6a089f236fe908940db7c5c92`.
- Snapshot verification: restored to a private detached worktree; restored HEAD and patch digest match the manifest, `git diff --check` is clean, and `harness.py state` reports the exact evidence source digest.
- Verification plan: package `20260910T185429Z-08b52b6b9c`, SHA-256 `83b3c22dd9d2e1313042d28b6b04c7ae2c634d0312423ca716cbb9c4fb8d6880`; A1-A7 map to all three documentary tests, inventory is a focused obligation, all required categories are present, and no mapped test is missing.
- Fresh RED evidence: HTTP `1789066420599954000-732a4d045b9b4258b0531b58d449cf5c`, concurrency `1789066420599944000-1d45326c606144dd810c6f05bb82d17f`, and browser `1789066420599941000-322dd9c86422409aa2b5ab724b4d7d3a`; each is an intended missing-route/form failure on source `42e0cf30...` with no drift. The prior exact-member inventory GREEN `1789066192201132000-e39114f8955e4ac3b2570f6afcc0bc4d` remains applicable because the inventory bytes are unchanged.
- Prior findings disposition: all original findings 1-4 and rereview-1 findings 1-2 are resolved.
- Verdict: `APPROVED`.

### Findings

None.

### Disposition

The history oracle now selects rows carrying both normative attributes and checks root plus revisions 1/2 in order with effective values, author name/stable ID and exact persisted time. The browser inspects all four forms and their exact native constraints, and verifies enabled visible submit controls within both horizontal and vertical bounds at 1440px and 390px. The rebuilt matrix also strengthens independent boundary values and admission precedence: 500-character declaration data crosses the held-lock race, PTO and declaration record grants are split, disabled status is denied, correction remains accepted after a real retraction to 84%, and every root/correction timestamp is bounded with the Moscow offset. Existing no-write, append-only, isolation, public-owner, fault, restart, projection and neighboring-flow assertions remain intact.

Gate 3 is approved for minimal implementation from this exact snapshot. Any later specification or test change requires review of the changed delta; Gate 5 and exact-source full CI remain separate obligations.

## Gate 3 test-helper delta review

- Reviewer: Codex independent reviewer `/root/documentary_gate3`; authored neither the specification nor the tests.
- Scope: only `tests/Yii2/DocumentaryFixture.php` and `tests/Yii2/documentary_browser.mjs` changed relative to the approved Gate 3 snapshot; normative behavior and production code are unchanged.
- Reviewed source: base commit `ee8fade4e240f7ad5886be616e89921954e36a20` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T191152Z-8dda613c69/snapshot`, patch SHA-256 `be75e7386acf0acdbb80b6b33a679c46f662ff2858a0e59e9b781b547c874c35`, source digest `c9718c348ca7a33ac6b32bb12419c33fa378780684f127963da4083a9644c0bb`.
- Snapshot verification: restored to a private detached worktree; restored HEAD and patch digest match the manifest, `git diff --check` is clean, and `harness.py state` reports the exact evidence source digest.
- Verification plan: package `20260910T191152Z-8dda613c69`, SHA-256 `7ffbc9e4742f65543f679199cc5370eb6045bff5c210a6f797396463801f0edd`, with no missing mapped tests.
- Fresh RED evidence: HTTP `1789067431152677000-f3c550cd93214e91bff9b896d14a651d`, concurrency `1789067431160000000-9f6bcaf80d1949ed85d9eaa6233b2c8b`, and browser `1789067431154983000-b479b63a24d541549304d8cde25c6730`; all remain intended absent-route/form failures on exact source `c9718c34...` with no source drift.
- Corrected file identity: the private RED worktree and implementation worktree have identical SHA-256 values: `DocumentaryFixture.php` = `1f923b359662d2866084d817a3aa66efd608baf9d9b5a82f017a2bb52679081f`; `documentary_browser.mjs` = `2c4e832d93ba67bc5d342cd979d136fc3ea6e30bbabee2e8206f1ded2e2949e1`.
- Verdict: `APPROVED`.

### Findings

None.

### Disposition

The helper delta corrects fixture/browser mechanics without weakening an acceptance expectation. Reading `HTMLDetailsElement.open` handles the boolean attribute correctly and prevents an already-open correction panel from being toggled closed. Selecting the visible queue link makes the existing return-path assertion deterministic at 390px while retaining the completed-queue check. The fixture grants the FKR actor the inherited `checklist.read` permission used by the canonical role catalog, and the browser now proves the checklist response is `200` before following the return link. The isolated fixture, real login/session, persisted-fact checks, four form constraints, viewport checks, navigation assertions, and all previously approved A1-A7 oracles remain intact.

This helper delta is approved. Append this section to the repository review record only after the in-progress exact-source GREEN run has finished, so the review write does not alter its source.

## Gate 3 screenshot-instrumentation delta review

- Reviewer: Codex independent reviewer `/root/documentary_gate3`; authored neither the specification nor the tests.
- Scope: observer-only change to `tests/Yii2/documentary_browser.mjs`; no assertion, workflow, normative specification, fixture or production change.
- Reviewed source: base commit `ee8fade4e240f7ad5886be616e89921954e36a20` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T191839Z-97e2b96bd4/snapshot`, patch SHA-256 `123e3c26d313c0741f5b4bb9eb1b60c270d8965c2750144ecd24da87cf3588d7`, source digest `ee2e7258d946cc65789faaecac15f4505bff5aebca2cead36958e58564d434ed`.
- Snapshot verification: restored to a private detached worktree; restored HEAD and patch digest match the manifest, `git diff --check` is clean, and `harness.py state` reports the exact evidence source digest.
- Verification plan: package `20260910T191839Z-97e2b96bd4`, SHA-256 `035c70817d9a8dfdad506bd86d929120e6cc0791649212b827ec86fec17a064a`, with no missing mapped tests.
- Fresh RED evidence: HTTP `1789067797584398000-b5d62be8e2dd46589d32e0e45288a5c5`, concurrency `1789067797583314000-22d07a46c7164524ad498a0d4aa1818b`, and browser `1789067797583313000-62a1a099b5ec4274885f332aa01103b3`; all remain intended absent-route/form failures on exact source `ee2e7258...` with no drift.
- Browser file identity: the private RED worktree and main implementation worktree both have SHA-256 `d81989173826cddecd297c83d7488b83b39ede4a09d60c40eae4d4b607c62ea2` for `tests/Yii2/documentary_browser.mjs`.
- Verdict: `APPROVED`.

### Findings

None.

### Disposition

The sole delta calls `window.scrollTo(0,0)` immediately before each existing full-page screenshot. It makes the captured fixed UI reflect the document-top state used for visual QA and does not alter the viewport sizes, form/history preparation, assertions, navigation, persistence checks or pass/fail result. All previously approved Gate 3 expectations remain intact.

This observer-only delta is approved. Append this section to the repository test-review record only after the current exact-source run has finished.

## Gate 3 A4 nonworking-form delta review

- Reviewer: Codex independent reviewer `/root/documentary_gate3`; authored neither the specification nor the tests.
- Scope: one HTTP test helper and two calls covering the inherited A4 working-case form precondition; no normative-specification or production-code change.
- Reviewed source: base commit `ee8fade4e240f7ad5886be616e89921954e36a20` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T192434Z-9ac66504cc/snapshot`, patch SHA-256 `bb79eb5d9c17a880c94ff8ad2908b91c1cf2c741796feeb7b9e0fdd7c168fa46`, source digest `cb37639907d53d46c87854e422d257071b702be52fe388448dce33e34a2c8a1e`.
- Snapshot verification: restored to a private detached worktree; restored HEAD and patch digest match the manifest, `git diff --check` is clean, and `harness.py state` reports the exact evidence source digest.
- Verification plan: package `20260910T192434Z-9ac66504cc`, SHA-256 `b962405fe941a115c694fbe2f6ac68ca53a6d9c814d6bd5ae6ed1b8e4f2e2fe4`, with no missing mapped tests and an explicit mixed Gate 3 expectation.
- Mixed exact-source evidence: HTTP `1789068160880447000-a111ab0f932b40b2a54892fd00a244c4` is `INTENDED_RED`; concurrency `1789068160879291000-8fd1917854224eae9436f6566cbce659` and browser `1789068160879304000-33d84dbf55e74f56bd290ca95b693971` are GREEN. All report source `cb376399...` with no drift.
- Verdict: `APPROVED`.

### Findings

None.

### Disposition

The delta makes the existing A4 rule observable at both material stages. It changes the private fixture case to `needs_assignment_change` after 85% and again after both documentary roots exist, requires the public card to remain readable and fact-preserving, and rejects all four mutation forms. `finally` restores `working`, so subsequent cases remain isolated. The retained HTTP RED proves the pre-PTO stage already hides record forms and then fails for the intended missing behavior—`correct_pto` remains visible in the nonworking post-document stage—while the unchanged concurrency and browser paths remain green.

This bounded test delta is approved for implementation. The prior Gate 5 approval remains valid only for source `d3dbc47c...`; the production correction requires a new exact-source GREEN and independent Gate 5 delta review.

## Gate 3 A7 CI-roster alignment review

- Reviewer: Codex independent reviewer `/root/documentary_gate3`; authored neither the specification/tests nor the implementation.
- Scope: one expected E2E roster literal in `tests/Verification/verification_ci_001_test.py`; no product assertion, suite routing, category assignment or production change.
- RED predecessor: base commit `7aea9393fac2970c08d08b4b7a8f6e40840db366` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T194712Z-3ac7eaf02d/snapshot`, patch SHA-256 `22068ff09bda580aa8ab417c04948482b801140aa5db9feac5fed4e125b0a42e`, source digest `5fb695c6bae3ed84f5b2c4746333ddc58561e3405056664630490974e4fd82ff`, plan SHA-256 `31e101144ba34be9b38e1163e3530e88a9758611b678be1aeba9211e7e7f9592`.
- Corrected source: the same base plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T194801Z-2e5dcfb59e/snapshot`, patch SHA-256 `6ec70896901cb7455e3bd623bd03ee052c1829f18386aa9fc3bdd6df58ae9c8a`, source digest `dc9ac4f7174279fb066b193ce4df5d59998332e5f2f2a7ef0dadd3b8ff270db0`, plan SHA-256 `24b9213d5b9613459c41b3c52ff5981b291a8d33b9ade410a7252cfbe063b669`.
- Evidence: `verification_ci_001_test.py` record `1789069483334595000-af1564d1ed9c43e1a4cc2e493090c8d2` is intended RED only because `test_real_composition_keeps_contracts_once` expected 13 E2E rows while the real suite returned the already registered documentary browser as row 14. Corrected record `1789069633889502000-afff3f2d89e148ef8363f4cd2c54b92a` is GREEN, 16/16, on exact source `dc9ac4f7...`; neither record has source drift.
- Verdict: `APPROVED`.

### Findings

None.

### Disposition

The added literal `php\ttests/Yii2/yii2_documentary_browser_001_test.php` exactly matches the existing E2E member in `tools/verification/suites.tsv`, its `e2e` category in `tools/verification/categories.json`, and the approved inventory allowance. It appears once in the corrected expected list. The RED is a stale compatibility expectation exposed by the first full CI, not missing domain behavior or a new acceptance requirement. All routing, partition, execution, continuation and aggregation assertions remain unchanged.

This bounded A7 test-alignment delta is approved. A new full CI on the committed correction remains required.
