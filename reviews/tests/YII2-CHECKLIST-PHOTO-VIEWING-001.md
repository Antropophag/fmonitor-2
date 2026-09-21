# Test review: YII2-CHECKLIST-PHOTO-VIEWING-001

- Reviewer: independent Codex reviewer `/root/photo_gate3` (gpt-5.6-sol / low); authored neither the specification nor the tests
- Test author: root delivery author (as declared by the separate-executor route; individual human/agent identity is not present in the package)
- Reviewed source: base `70590d392cc481541956f26d5804e9b35c93607a` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T184613Z-a77414bdb0/snapshot/source.patch`, SHA-256 `0b6f1edf483e4651dcd533a2371ef8902a15ad1831342f3f8b37ee931c594136`; candidate source digest `9e0d18afed05c3e990326d54cf35c93cd7b9ba53ff9afe3f90df1e2c4ee2206b`, executable source digest `631efdc5d6ee0bce62b7ec51dc3246f2a95a2f1f0f4a23d234e94708227c5a63`
- Agreed review scope / prior findings disposition (for rereview): first Gate 3 review; full normative contract, OpenSpec lifecycle artifacts, verification input, HTTP/browser RED tests, retained RED/GREEN evidence, authorization and exact-source binding; no prior findings
- Specification: `specs/YII2-CHECKLIST-PHOTO-VIEWING-001.md` v1.0 and `openspec/changes/shared-checklist-photo-viewing/`
- Public seam: `GET|HEAD /pilot/objects/{objectId}/checklist/photos/{photoId}`, checklist `data-projection`, section-strip/gallery viewing actions, and preservation of the existing local IndexedDB/blob queue
- Red command and intended failure: `php tests/Yii2/yii2_checklist_photo_viewing_001_test.php` — exit 255 because accepted projection lacks `viewUrl`; `php tests/Yii2/yii2_checklist_photo_viewing_browser_001_test.php` — exit 255 because the clean browser cannot find the server-backed image. Retained records: `1790016150962313000-6e7d47d5feff49e1b6ea6f8ae4c550c6.json` and `1790016186643549000-331f50ea28414393a3c4509fecff887a.json`. Existing local queue regression `php tests/Yii2/yii2_inspection_journey_001_test.php` is GREEN in record `1790016338408758000-682d3b2f90e340368813dcf3e358e894.json`.
- Verification plan: SHA-256 `f170c3543a04d994dbc6d103de4d3925a0aab8363fad555394a6898df2aa5e5d`; lane `CRITICAL`; required reviews `gate3`, `final`
- Authorization: review was explicitly assigned against the prepared reviewer package; package declares `separate_executor`. This review does not authorize implementation, merge, deployment, or CI dispatch.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — The storage/integrity failure contract has no RED coverage.** The normative contract requires validation of the internal `storage_name`, containment under the checklist private root, regular-file status and persisted byte size, with a safe `503`, `Retry-After`, no sensitive disclosure and no mutation (`specs/YII2-CHECKLIST-PHOTO-VIEWING-001.md:22,38`). `tests/Yii2/yii2_checklist_photo_viewing_001_test.php:36-58` covers only a healthy blob and authorization/not-found responses. An implementation that follows an absolute/traversal/symlink storage name, serves a truncated blob, returns 500, leaks an exception/path, or omits `Retry-After` can pass. Add deterministic corrupt/missing/unsafe-name/non-regular-or-equivalent fixture cases through the public HTTP seam and independently assert the safe response and unchanged facts/files.

2. **High — Authentication/authorization and route rejection coverage is incomplete.** The declared acceptance seam explicitly includes guest **and inactive**, and the contract distinguishes `roleAccess`, `checklist.read`, `inspection.item.complete`, denied access, GET and HEAD (`verification-input.json`; normative lines 20 and 38). The HTTP test checks one allowed reader, one denied active reader and guest GET only (`tests/Yii2/yii2_checklist_photo_viewing_001_test.php:43-56`). It has no inactive case, no evidence for each accepted authorization source, no denied/guest/inactive HEAD parity, and no noncanonical/overflow id route cases. A GET-only authorization bug, accidental upload/revoke requirement, or permissive route coercion can pass. Add the missing independently identified actors/methods and malformed-boundary examples.

3. **High — The sensitive offline/local-queue preservation claim is not observably covered by the new candidate.** Contract line 25 requires unchanged `meta/operations/photoBlobs`, upload request, ordering, and all `queued/sending/retryable_error/rejected/conflict` states. The new browser test only observes that selecting one offline file produces one `blob:` image (`tests/Support/checklist_photo_viewing_browser.cjs:14`). The mapped pre-existing journey is GREEN, but the verification input does not identify which assertions cover each listed invariant and the new browser exercise does not inspect queue records, request payload/order, transitions, or accepted durable fallback after reload. Because the planner classifies this as `sensitive-offline-ui`, provide traceable existing assertions for every claimed invariant or add focused regression assertions; at minimum prove the pending blob remains queued and the accepted projection takes over after reload without creating a duplicate operation.

4. **Medium — Error/retry expectations are too weak for the stated UI contract.** `tests/Support/checklist_photo_viewing_browser.cjs:12` checks text/button presence and merely that one request contains `retry=`. It does not prove the photo card remains, empty-state stays absent, retry uses the same base URL, successive retry values are monotonic, or retry creates no checklist/IndexedDB operation. It can pass an implementation that discards the card, adds an arbitrary retry URL/constant token, or enqueues a domain operation. Assert those observable outcomes, including at least two retries with ordered distinct values and before/after operation facts.

5. **Medium — Revocation is not covered across both public UI representations, and historical row preservation is only partially asserted.** The HTTP test checks projection ids, a 404 and retained bytes (`tests/Yii2/yii2_checklist_photo_viewing_001_test.php:60-65`), but contract lines 26 and 33 require disappearance from both section strip and gallery plus row/blob fingerprints unchanged except the already specified revoke facts. No browser assertion covers the two representations, and no before/after row fingerprint excludes unintended metadata mutation. Add the UI projection/reload assertions and an explicit allowed-delta comparison for row/operation history.

6. **Medium — Successful response header expectations are not independent or complete.** The disposition assertion accepts any value beginning `inline;` and would accept the uploaded original filename; cache policy checks only `no-store`, not `private`; HEAD checks only status/body/length and not the required equality of MIME, disposition, nosniff and cache headers (`tests/Yii2/yii2_checklist_photo_viewing_001_test.php:36-44`). Assert a contract-chosen generic filename independent of implementation/input, both cache directives, and exact relevant GET/HEAD header parity.

7. **Medium — OpenSpec does not carry the complete safety surface of the normative contract.** The delta requirements cover authorized bytes, revocation, full-size, retry and local queue, but omit the storage-name/regular-file/size validation and safe 503/`Retry-After`, malformed/overflow ids, method 405/Allow, and the complete read-only adjacent-facts list. The proposal/design mention fail-closed storage only generally, so lifecycle artifacts and their scenarios cannot demonstrate the full Gate 1 matrix. Add explicit requirements/scenarios or clearly link those normative acceptance IDs into the OpenSpec delta and verification input.

8. **Low — The browser result artifact contains hard-coded success booleans.** `tests/Support/checklist_photo_viewing_browser.cjs:15` writes `cleanBrowser`, `fullSize`, `retry`, and `otherUser` as literal `true`; control flow currently throws on preceding checks, so it is not a false GREEN today, but the artifact is not an independent record of observations and is fragile under later edits. Populate results from actual measured values, as already done for `localPreview`, or remove the redundant aggregate assertion.

The chosen public seams are appropriate and the retained failures are deterministic intended REDs for missing durable projection/rendering rather than setup failures. Expected fixture bytes and identities are independently constructed. Those strengths do not close the missing rejection, integrity and sensitive queue branches above.

## Required changes

- Close findings 1–7 in the specification/OpenSpec/verification mapping and RED tests, preserving one complete deterministic candidate.
- Re-run the affected focused RED commands through the harness and retain exact-source evidence for the corrected snapshot.
- Return the complete corrected candidate with each finding marked `fixed`, `open`, or justified `not-applicable`; finding 8 should be fixed or explicitly justified.
- Do not begin production implementation until a new independent Gate 3 verdict is `APPROVED`.

---

## Rereview revision 2 — 2026-09-21

- Reviewer: independent Codex reviewer `/root/photo_gate3` (gpt-5.6-sol / low); authored neither specification nor tests
- Reviewed source: base `70590d392cc481541956f26d5804e9b35c93607a` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T185746Z-72ff52414c/snapshot/source.patch`, SHA-256 `681ea7fe19f6eb48507ecad72fb65a9e278d1a24bf588f92640a594edac388ab`; candidate source digest `b1c1da2f00f786bf4bd5abd89256df913cf1fad7b579f66faecb2dd03e9acdcc`, executable source digest `91f993e7507af0e43334e4982708a0458d08abad7c2f8a8832bcf5770d8456bd`
- Delta from prior reviewed source: corrected HTTP/browser tests and OpenSpec delta plus inclusion of this prior review record; no production implementation
- Verification plan: SHA-256 `21b904b63dbfcf8163b640931e4ff588989cf41d20920407a25a26cafc77ab9e`; lane `CRITICAL`; required reviews `gate3`, `final`
- Exact evidence: HTTP RED record `1790016828293175000-02427a01708a42129842a97d8a3799c5.json` fails at missing projection `viewUrl`; browser RED record `1790016867691711000-cdf47ba187c44fcea6e87707b7c03052.json` fails at missing clean-browser server image; existing journey record `1790017019382297000-52fa4e5ee304431797f2b9fa7e5a5b8d.json` is GREEN. All three bind candidate source `b1c1da2f...` and executable source `91f993e7...`.
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Open / partially fixed.** Missing blob, size mismatch and unsafe `../` storage identity now exercise GET/HEAD, safe 503, `Retry-After`, redaction and no mutation. The normative and OpenSpec contracts still explicitly reject symlink and non-regular artifacts, but the test supplies neither. An implementation that validates spelling/size while following an in-root symlink or serving a non-regular entry can still pass.
2. **Open / partially fixed.** Inactive, guest/denied HEAD, a second role, and malformed photo ids were added. The test still does not isolate the three independently permitted authorization sources (`roleAccess`, `checklist.read`, `inspection.item.complete`): fixture user 73 has both `inspection.item.complete` and `checklist.read`, so an implementation incorrectly requiring `checklist.read` passes. It also tests malformed `photoId` only, while the contract/OpenSpec scenario names malformed `objectId` too.
3. **Open / partially fixed.** The browser test now proves one queued operation, durable server fallback after reload and no duplicate operation. It still provides no traceable oracle for unchanged `meta/operations/photoBlobs`, upload request and ordering, nor for `sending`, `retryable_error`, `rejected`, and `conflict`. The cited GREEN PHP journey exercises server outcomes, not these IndexedDB/client queue states. The CRITICAL `sensitive-offline-ui` contract remains broader than its Gate 2 evidence.
4. **Fixed.** The corrected browser test preserves the card/no-empty-state, observes two monotonic retry values, verifies the same base path and checks no local operation is created.
5. **Fixed.** Corrected tests cover revoked absence in section and gallery, direct 404, retained bytes, exact allowed photo-row delta and one appended revoke history fact.
6. **Fixed.** The HTTP test now independently asserts the generic filename, `private` plus `no-store`, and relevant GET/HEAD header parity.
7. **Fixed.** OpenSpec now carries malformed route/method behavior, fail-closed private-storage integrity, retry details and the complete read-only requirement.
8. **Fixed.** Browser result fields are derived from observed values/control-flow outcomes rather than the former blanket literal-success artifact.

### Complete findings for revision 2

1. **High — Symlink/non-regular storage rejection remains insensitive.** Add a deterministic in-root symlink and a non-regular artifact case (or a platform-portable equivalent that separately proves both predicates) through GET and HEAD, with the same 503/redaction/no-mutation assertions. Location: `tests/Yii2/yii2_checklist_photo_viewing_001_test.php:68-78`; contracts: normative acceptance 4 and OpenSpec lines 30-35.
2. **High — Authorization alternatives and malformed object ids are not independently proven.** Configure distinct actors so one succeeds solely via `checklist.read`, one solely via `inspection.item.complete`, and the specified `roleAccess` route is independently identified; ensure none has upload/revoke as a hidden prerequisite. Add the same zero/leading-zero/sign/suffix/overflow matrix for `objectId`, not just `photoId`. Location: `tests/Yii2/yii2_checklist_photo_viewing_001_test.php:46-66`; normative acceptance 2 and rejection section.
3. **High — The declared offline queue state matrix is still not covered.** Either narrow the normative/OpenSpec preservation claim to the observably inherited behavior actually covered, or map and retain deterministic browser evidence for `meta`, `operations`, `photoBlobs`, upload payload/order, and each `queued/sending/retryable_error/rejected/conflict` state. The server-side `yii2_inspection_journey_001_test.php` cannot substitute for client IndexedDB/state-machine evidence. Location: `tests/Support/checklist_photo_viewing_browser.cjs:16` and `verification-input.json:32-38`; normative acceptance 7.

The corrected exact-source RED failures remain intended and setup-independent, but they fail before the newly added downstream assertions execute. That is valid incremental RED evidence; approval is blocked by the three remaining sensitivity gaps, not by the early failure itself.

### Required changes for revision 2

- Close the three findings above in one complete corrected candidate.
- Retain new exact-source HTTP/browser RED evidence and the relevant existing queue/state regressions.
- Return all revision-2 findings with explicit dispositions for another independent rereview; production implementation remains blocked pending `APPROVED`.

---

## Rereview revision 3 — 2026-09-21

- Reviewer: independent Codex reviewer `/root/photo_gate3` (gpt-5.6-sol / low); authored neither specification nor tests
- Reviewed source: base `70590d392cc481541956f26d5804e9b35c93607a` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T190552Z-57bc228e48/snapshot/source.patch`, SHA-256 `4284ce0292546214f211baadd5793b88b24b5103614c40cadc04f15be3938c80`; candidate source digest `7a4ac592843d860d284f15b179a3e41bdb3cfecc1afa28cda810b5aba46b05ed`, executable source digest `565f77179a6ee7e481bbb4321b13855399a669a0ad8480dcbea77ed81fcc83d4`
- Verification plan: SHA-256 `6bd3c8d0e4249face5781ed6c2321687a05a130e35a6c3173eaa34f558332603`; lane `CRITICAL`; required reviews `gate3`, `final`
- Exact evidence: HTTP RED `1790017300896801000-def33c30244f408dbe3571beaf71eccc.json`; photo browser RED `1790017333783870000-b79e6ed004d84dca95a2e444bf4cfbf5.json`; server journey GREEN `1790017487980108000-24d9c9eb88fb46499b041cbc27884ca0.json`; full browser journey GREEN `1790017511015458000-af9ad5deb436499281874714b29aa1f5.json`. All bind candidate `7a4ac592...` and executable source `565f7717...`.
- Verdict: `CHANGES_REQUESTED`

### Revision-2 findings disposition

1. **Fixed.** The HTTP candidate now creates an in-root symlink and proves GET/HEAD reject it with 503, `Retry-After`, redaction and no handler mutation. Together with missing, size-mismatch and unsafe-name cases this makes the ordinary-file/containment contract sensitive.
2. **Open because the behavioral cases were added with a deterministic setup-isolation defect.** The candidate now isolates `inspection.item.complete`, `roleAccess`, and `checklist.read`, and adds the malformed `objectId` matrix. However, it captures `$before = $http->facts()` at line 34 and then permanently deletes `checklist.read` permission rows for roles 2 and 7 at lines 50-51 before asserting `$before === $http->facts()` at line 67. `facts()` inventories every table, including `fm2_pilot_role_permissions`; therefore a correct production implementation will fail the advertised read-only assertion because of test-owned mutations. Restore both permission rows in `finally` blocks before the facts comparison, or take a scoped/baseline snapshot after reversible fixture setup. This is a broken-setup failure, not a product oracle.
3. **Open / partially fixed.** Mapping `yii2_inspection_browser_001_test.php` and retaining its exact-source GREEN evidence closes the store inventory, device scope, photo blob, conflict/rejected/retryable-error retention, retry acceptance, upload occurrence and item→photo→section ordering portions. It still never asserts an operation row has status `queued` or `sending`: the offline predicate checks only `itemId`, and request observation checks only that a request was emitted. An implementation that skips or mislabels either explicitly contracted state can pass. Add deterministic observation of both state values (holding the request long enough to observe `sending`), or narrow the normative/OpenSpec state preservation claim if those labels are not public behavior.

### Complete findings for revision 3

1. **High — Authorization fixture mutations invalidate the later read-only oracle.** Restore the deleted role-permission rows or move to a clean/scoped baseline so the HTTP test can become GREEN for the intended implementation. Location: `tests/Yii2/yii2_checklist_photo_viewing_001_test.php:34,50-51,67`.
2. **High — Explicit `queued` and `sending` state preservation remains insensitive.** Assert both exact IndexedDB statuses in the mapped browser state-machine test, with deterministic request control for `sending`. Location: `tests/Yii2/inspection_browser.mjs:26-37`; normative acceptance 7 and OpenSpec local-queue requirement.

All other findings from revisions 1 and 2 are closed. The retained RED failures are still intended missing-feature failures, and both mapped GREEN regressions are genuine exact-source successes; neither fact waives the two remaining Gate 2 quality defects.

### Required changes for revision 3

- Correct the permission-fixture cleanup/baseline and add explicit deterministic `queued`/`sending` assertions.
- Retain corrected exact-source RED/GREEN evidence and return the two findings with dispositions for final Gate 3 rereview.
- Production implementation remains blocked pending `APPROVED`.

---

## Final rereview revision 4 — 2026-09-21

- Reviewer: independent Codex reviewer `/root/photo_gate3` (gpt-5.6-sol / low); authored neither specification nor tests
- Reviewed source: base `70590d392cc481541956f26d5804e9b35c93607a` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T191357Z-6b55956445/snapshot/source.patch`, SHA-256 `78b72b3d47938f33509609561a3a2b9c7a3e2d99cbb317b6496dff538ce9ddfe`; candidate source digest `391bdd05144bd1d7fd03634d9141760256622f1bff8edf68fb687cc908b0b4f4`, executable source digest `4142421803aac4e8755b602c4b1cf274bff5b5a11b1fd24b8510d4bcfb2c6a8e`
- Verification plan: SHA-256 `90e156fd4966509ef86faba30ad2ac48727d8bcba91110e94d7f0077eaf23fa3`; lane `CRITICAL`; required reviews `gate3`, `final`
- Exact evidence: HTTP RED `1790017769422243000-017f3dd7de3944eca95f843f57bdb565.json`; photo browser RED `1790017810292802000-4943be2dd5f8446d854fd1fb4524b2b4.json`; server journey GREEN `1790017967910906000-e7192b33b2844d8c8168e1da14eb4ecb.json`; full browser journey GREEN `1790017996303210000-e2970b09b16147a3ab1f2b1265a8ab16.json`. All bind candidate `391bdd05...` and executable source `41424218...`.
- Verdict: `APPROVED`

### Revision-3 findings disposition

1. **Fixed.** The two temporarily deleted `checklist.read` permission rows are restored before the full `facts()` baseline is captured. The later read-only equality therefore compares only product requests against a fixture-stable database, while the isolated `inspection.item.complete` and `roleAccess` probes remain meaningful.
2. **Fixed.** The browser candidate now asserts the selected offline photo operation has exact status `queued`, holds its upload request, observes exact persisted status `sending`, and releases the request only afterward. This is deterministic and complements the mapped GREEN state-machine regression for stores, scope, conflict, rejected, retryable error, photo blob retention and send ordering.

### Findings

None. All findings from revisions 1–3 are closed for the reviewed exact source.

The two feature tests remain RED for the intended missing durable `viewUrl`/server-rendering behavior rather than setup failure. Both adjacent server and browser queue regressions are GREEN on the same exact source. The revision-4 delta is confined to test setup cleanup and stronger state observation; it introduces no production behavior and no new uncovered regression surface.

### Required changes

None. Gate 3 is approved for implementation against this exact reviewed test candidate. Any later specification or test change requires delta review and, when the recomputed plan still requires Gate 3, renewed independent approval.
