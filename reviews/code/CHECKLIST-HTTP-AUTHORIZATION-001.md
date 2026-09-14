# Code review: CHECKLIST-HTTP-AUTHORIZATION-001

- Reviewer: Codex independent reviewer `/root/review130`
- Specification/test author: root agent
- Production implementation author: executor agent `/root/execute130`
- Reviewed source: base `44880bbe4df579789a094fda526e6a4415598b29` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T161100Z-389b8f345c/snapshot`, patch SHA-256 `8a4cc16bf181a38fa916198af76fefeb74888a474728e19b6ce6f48324e1e270`
- Candidate source: `1e323057b412f31bea3e954d2ab6a9e5eb187a711548b622a74771acca92cbed`; executable source `20b91cc617f1e1d33fc2bf62b95aca23a4eccfb78ef83b77d0217624310aeadb`
- Specification: `specs/CHECKLIST-HTTP-AUTHORIZATION-001.md`, A1-A6
- Approved test: `tests/Yii2/yii2_inspection_journey_001_test.php`; Gate 3 `reviews/tests/CHECKLIST-HTTP-AUTHORIZATION-001.md`
- Production delta: `app/InspectionEvidence/MariaDbYiiChecklistMutation.php`, `app/YiiRuntime/Controllers/ChecklistController.php`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — A4's controller read guard is not regression-sensitive.** Locations: `tests/Yii2/yii2_inspection_journey_001_test.php:53-61`, `app/InspectionEvidence/MariaDbYiiChecklistMutation.php:16`, and `app/YiiRuntime/Controllers/ChecklistController.php:88-91,105-107`. The new production adapter correctly maps a missing photo-revoke assignment/capability to `forbidden` before duplicate, conflict, or business validation. Consequently every current no-read `photo_revoked` request receives safe 403 from action authorization even if both controller read checks are deleted. The independent fault-injection witness at `/Users/antropophag/.local/share/fmonitor-2/issue-130/read-guard-witness` removed only those two read-denial lines; the approved complete journey still exited 0/PASS (`read-guard-removed-before-test-delta.log`). This means Gate 2 would not catch the plausible regression A4 is specifically intended to prevent. Add a no-read request family whose action/domain path can return duplicate, conflict, and rejected independently of the read grant (for example the existing `completion_retracted` path), asserting exact safe 403, no projection, unchanged facts, and unchanged private artifacts. This changes an approved test and therefore requires renewed Gate 2 evidence and independent Gate 3 approval before Gate 5 resumes.

2. **High — read-only action refusals are not consistently mapped before business outcomes.** Locations: `app/YiiRuntime/Controllers/ChecklistController.php:92-93`, `app/InspectionEvidence/MariaDbYiiChecklistMutation.php:15,46-51`, and missing sensitivity near `tests/Yii2/yii2_inspection_journey_001_test.php:41-42`. After the initial read check, `itemId === 42` returns the business 409/message before `accept()` checks `inspection.item.complete`. Actor 95 has `checklist.read` but no item-completion permission, so this payload receives 409 rather than A2's exact authorization 403 and reveals a business rule to an actor whose action must be refused. The same reader can submit a syntactically valid `completion_retracted`: the adapter deliberately lets the type reach `mayRetract()`, whose combined ownership/assignment/business predicate returns generic `rejected`/422 rather than the required authorization 403. Enforce the known action denial before these business outcomes while preserving the authorized actor's item-42 409 exact message and authorized correction/retraction flows. Add an exact-403/no-facts reader action matrix covering item completion (ordinary and item 42), installer correction, section completion, completion retraction, photo revoke, and the already-covered photo upload; renew Gates 2/3 for that test delta.

No other blocking findings were identified in the bounded operation/photo implementation. The pre- and post-result owner read checks otherwise fail closed; item-completion `ACTOR_NOT_AUTHORIZED` and photo revoke capability/assignment failures are mapped to typed `forbidden`; authorized projections remain attached only after a current read recheck. The delta neither grants permissions nor changes append-only mutation ownership. Request length, CSRF, content type, JSON, and photo envelope parsing remain before domain authorization as the explicitly preserved A6 transport contract.

## Required changes

- Make the A4 test sensitive to deletion of the controller read gates using an action path that independently reaches duplicate/conflict/rejected outcomes; capture intended RED against the read-guard mutant and obtain renewed Gate 3 approval.
- Enforce known read-only action refusals before item-42 and completion-retraction business responses; cover the coherent reader action matrix as exact 403/no facts while retaining authorized business and mutation outcomes.
- Re-run the bound focused plan on the corrected exact source and submit a new reconstructible snapshot for Gate 5.

## Verification reviewed

All evidence below is bound to candidate source `1e323057b412f31bea3e954d2ab6a9e5eb187a711548b622a74771acca92cbed` and executable source `20b91cc617f1e1d33fc2bf62b95aca23a4eccfb78ef83b77d0217624310aeadb`:

- GREEN `php tests/Yii2/yii2_inspection_journey_001_test.php`: record `1789402074907397000-916154f8e4fe45498df61732440096cc.json`.
- GREEN `php tests/InstallationProcess/inspection_evidence_schema_001_test.php`: record `1789402092338269000-7a869f9baf9e42c1904ea2ed71ce3bb4.json`.
- GREEN `php tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php`: serial retry record `1789402179987661000-a657897148814667bcff1c8f86825998.json`.
- GREEN `php tests/Verification/characterize_inspection_photo_rejections_001_test.php`: serial retry record `1789402182106297000-133a52ff3b3147c88ac20e2484614cc3.json`.
- GREEN `php tests/Verification/characterize_inspection_photo_revoke_001_test.php`: record `1789402092364080000-67dcfecbd7fe4560b47c7eca0b3d84b1.json`.
- GREEN `php tests/Verification/characterize_inspection_photo_upload_001_test.php`: serial retry record `1789402183578260000-4501f056340743a4bf465a0b398df77b.json`.
- GREEN `python3 tests/Verification/change_verification_001_test.py`: record `1789402092361559000-40061db1eed147b08580d06ff3ff85f2.json`.
- GREEN `php tests/Runtime/runtime_storage_001_test.php`: serial retry record `1789402185327484000-7dfbf9acc4d4483bba95b4096ec3790f.json`.
- GREEN `python3 tests/Verification/architecture_guard_001_test.py`: record `1789402092393875000-484ad9afbc18410ca89ae68ab6582886.json`.
- GREEN `make architecture-check`, including `PILOT-HTTP-AUTH-001` global-call qualification and seven architecture rules: record `1789402187744067000-40ee4aefb5f34cf68ba1e8c7d5e7d26d.json`.

The first parallel focused launch (records beginning `1789402092`) exposed unsafe test coexistence rather than production failures: photo upload observed another photo characterization test's decoy tables; architecture-check scanned architecture-guard's temporary PHP fixture while that guard removed it; three other checks became `UNKNOWN` because the transient fixture changed the executable-source digest. Only the five affected checks were repeated serially (records beginning `1789402179`, `1789402182`, `1789402183`, and `1789402185`) and are GREEN above. The failed/interfered history remains retained and is not represented as GREEN.

No local full `make test`/`make verify` was run. Full exact-source CI is pending. No PR, merge, deployment, stand action, or role-policy change is approved by this review.

## Final correction Gate 5

- Reviewed source: base `44880bbe4df579789a094fda526e6a4415598b29` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T162640Z-4b7c9adcfe/snapshot`, patch SHA-256 `a15712eae228d58a77b587c0373261901cb6fac2de694bb8a7a3331886aebfdc`
- Candidate source: `da39084211ffdfe3f0dcd2a80088eb94f0a64fb1e1909e634e9dd5c98b00ac0a`; executable source `2c3f49db1341215f504032b9a7c8bec109b3e4a46fa6c9f21ba79d64ce8d2aaa`
- Reviewed correction: unchanged correction-approved specification/tests; minimal production delta in `MariaDbYiiChecklistMutation.php` and `ChecklistController.php`
- Verdict: `APPROVED`

### Findings and prior-finding disposition

None remaining.

1. **Resolved — A4 controller read-guard sensitivity.** The correction-approved test makes a no-read actor formally assigned and photo-revoke-capable, then exercises `photo_revoked` and `completion_retracted` duplicate/conflict/business-rejection paths through real HTTP. The final private mutant removed only the two controller read-denial lines from the otherwise corrected candidate while retaining identical final spec/test and corrected adapter bytes. It fails at A4 with expected 403, actual 200: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789403063981433000-abdd0a9b6ce543dcb1a2606934a87de0.json`. Reconstructible mutant snapshot: `/Users/antropophag/.local/share/fmonitor-2/issue-130/final-mutant-snapshot`, patch SHA-256 `eaaa76c6ba67c5f602b8f2fa4fcb582563ec68d24f02212342f90d24de309b94`. This proves the final test fails when controller read admission is removed, independently of adapter action denial.

2. **Resolved — read-only action authorization precedence.** `ChecklistController` now checks `itemComplete` before the item-42 documentary shortcut, preserving authorized 409/exact-message behavior while returning safe 403 to a reader without completion authority. `MariaDbYiiChecklistMutation` maps native item-completion `ACTOR_NOT_AUTHORIZED` to `forbidden`, requires formal assignment before completion-retraction validation/replay, and requires formal assignment plus revoke capability before photo-revoke validation/replay. The full reader action matrix is GREEN and authorized completion, correction, section, retraction, photo, duplicate, conflict, and business-projection paths remain GREEN.

The controller obtains current owner access before business mutation, rejects missing object/read admission without projection, rechecks read access after the domain result, and only then materializes projection. It reconstructs the item-completion owner with the native recording seam without changing mutation ownership. Rejected actions preserve facts/private bytes; no authorization grants, schema, migration, transport, legacy import, Makefile, integration configuration, or unrelated OpenSpec changes were introduced. No additional maintainability, security, history, or boundary finding was identified in the two-file production delta.

### Final verification evidence

All nine focused records are GREEN and bound to final candidate source `da39084211ffdfe3f0dcd2a80088eb94f0a64fb1e1909e634e9dd5c98b00ac0a` and executable source `2c3f49db1341215f504032b9a7c8bec109b3e4a46fa6c9f21ba79d64ce8d2aaa`:

- `php tests/Yii2/yii2_inspection_journey_001_test.php`: `1789402990623379000-386baef404dd418cae153e78c517da7d.json`.
- `php tests/InstallationProcess/inspection_evidence_schema_001_test.php`: `1789403078630336000-9d6530d72f904183b78b4a2a73f9fb0b.json`.
- `php tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php`: `1789403086162826000-2e8d6bceab844b76a5628def6d937e8c.json`.
- `php tests/Verification/characterize_inspection_photo_rejections_001_test.php`: `1789403088306518000-93d6035688a44099bbc5d832f7371166.json`.
- `php tests/Verification/characterize_inspection_photo_revoke_001_test.php`: `1789403089819471000-7932a8459ed84a90b46e6ed823418617.json`.
- `php tests/Verification/characterize_inspection_photo_upload_001_test.php`: `1789403091840803000-8abe47f6ff6a4ecd88db71f757cc6881.json`.
- `python3 tests/Verification/change_verification_001_test.py`: `1789403093646830000-0d854f5732704ffc9c428dad90cb84eb.json`.
- `php tests/Runtime/runtime_storage_001_test.php`: `1789403112874749000-650be4f94f2b488d97a365a038a533a8.json`.
- `python3 tests/Verification/architecture_guard_001_test.py`: `1789403115340455000-031b86154ae5441e993666e3a0d59b60.json`.

Additional exact-source `make architecture-check` is GREEN, including the required `PILOT-HTTP-AUTH-001` global-call qualification and all seven architecture rules: record `1789403134058386000-d8d2a02a60ab4d1db0bfb5cc15ad8494.json`.

The initial unsafe-parallel-run failures/UNKNOWN results and their serial retry history remain preserved in the first Gate 5 section; they were test-coexistence/source-drift effects, not production failures, and are not rewritten as GREEN. No local full suite was run. Exact-source GitHub CI, PR publication, merge, and deployment remain pending and are not approved by this Gate 5 verdict.
