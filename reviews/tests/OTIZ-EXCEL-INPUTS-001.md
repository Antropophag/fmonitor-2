# OTIZ-EXCEL-INPUTS-001 — Gate 3 test review

- Date: 2026-09-14
- Reviewer: `review_inputs` (independent agent; authored none of the reviewed specification, test, production seam, or evidence)
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T185854Z-8b15a5b9ef/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `cb8f610594bb95003065bbdea1233081b1952bb5a6db3c960e898e09debb4190`, executable source digest `f24997c83ce5e1e0fe43d197b17a034fa2b38b76379cc5435972ed066ba923ce`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T185854Z-8b15a5b9ef/snapshot`, patch SHA-256 `efdd9b7af189d583d45bb99a352ddf06abdfaec7b2b6724487ff594b2605a323`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T185854Z-8b15a5b9ef/verification-plan.json`, SHA-256 `eb0f83993172490166f9ac8ea679784a33a5e27ab2b165e8c356d3ffad247e46`
- Agreed scope: `specs/OTIZ-EXCEL-INPUTS-001.md`, `tests/Otiz/excel_inputs_001_test.php`, and the existing `MariaDbNativePremiumInputs::forDate` public seam. Certificate commands/schema/HTTP, publication, acceptance, settlement/payment and the already approved calculator are outside this verdict.

## Findings

1. **CRITICAL — most blocker witnesses do not prove the required fail-closed result, and several named blockers have no witness.** The contract requires any blocker to keep the row visible with `operands=null` (`specs/OTIZ-EXCEL-INPUTS-001.md:14-20`). The test checks `operands=null` only for an absent plan (`tests/Otiz/excel_inputs_001_test.php:32`); the damaged composition, template, attribution, certificate, completion and card cases merely search `issues` (`:26-28,33,44,46`). An implementation can append the expected issue while still returning calculable operands and pass. There is no direct case for invalid non-null plan date, absent applied crew, unknown checklist item, missing/mismatched operation template identity, unresolved premium norm, unresolved shaft coefficient, or a selected installer with zero contribution before a later attribution change. Correction: table-drive every contract blocker, assert the exact code, row retention and `operands === null`, and restore each corruption. Include the missing named cases without depending on certificate-publication tests.

2. **HIGH — native case selection and applied-composition choice are under-specified by the fixture.** The contract selects only `native_candidate`, `working`, started-by-cutoff cases with a legacy object, excludes quarantine, sorts by object ID, and uses the latest immutable applied composition rather than pending/uploaded originals (`specs/OTIZ-EXCEL-INPUTS-001.md:13,17`). The test has one qualifying case and only proves that zero registered legacy orders do not exclude it (`tests/Otiz/excel_inputs_001_test.php:11-20`). It would pass if the reader included planned/future-start/quarantined cases, omitted a valid second case, returned database order, selected an older application, or consumed a newer pending/uploaded composition. Correction: add a compact multi-case selection matrix plus two applied revisions and a later unapplied selection/original; assert the exact sorted IDs, latest applied application/crew and absence of all ineligible decoys.

3. **HIGH — checklist event resolution, contribution arithmetic and report cutoffs remain materially insensitive.** The contract requires accepted-revision/ID ordering, both device and server cutoffs for every event kind, immutable operation/template identity, support for current template shapes, unknown-item rejection, and integer remainder assignment in binary tab order (`specs/OTIZ-EXCEL-INPUTS-001.md:18`). The test exercises one evenly divisible two-installer item, sequential HTTP events, and makes only a retraction future one timestamp at a time (`tests/Otiz/excel_inputs_001_test.php:14-20,34-39`). A reader that includes future completions or future attribution changes, orders by device time, ignores accepted revision/template identity, drops remainder basis points, or recognizes only this fixture's template representation can pass. Correction: add adversarial out-of-time completion and attribution events for each cutoff, deliberately non-time-ordered accepted revisions/IDs, operation-template mismatch, unknown item, supported template representations, and a non-divisible contribution split whose independently calculated expected remainder follows binary tab order and conserves the item total.

4. **HIGH — the evidence-envelope contract is neither fully deterministic in the specification nor tested at its trust boundary.** `sourceEvidence` requires six keys and every source envelope to contain label/locator/lower-SHA256, while composition and progress hashes must bind complete source rows (`specs/OTIZ-EXCEL-INPUTS-001.md:11,17-19`). The test checks only the deadline and completion digests, one composition ID, and selected progress fields (`tests/Otiz/excel_inputs_001_test.php:21,25,34,42`). It never asserts exact certificate/composition/progress labels, locators or hashes, all six keys, active operation IDs, template ID/hash, or that attribution/event/template mutations change the proof. Moreover, the spec says to hash a “whole row” and several row sets without defining the exact canonical object keys, scalar normalization and row ordering, so two conforming implementations can produce different hashes. Correction: define the canonical composition and progress hash payloads explicitly, then assert all envelope fields and independently computed hashes, including stable ordering and sensitivity to each bound source class.

5. **HIGH — completion lineage and documentary cutoff coverage can pass partial implementations.** The contract validates dates, contiguous versions, previous-correction/version links, resolves comparison from the current leaf even after report date, but derives documentary progress from the lineage known within the cutoff (`specs/OTIZ-EXCEL-INPUTS-001.md:16,19`). The test covers one post-cutoff PTO correction and corrupts only `previous_version_no` (`tests/Otiz/excel_inputs_001_test.php:41-44`). It does not distinguish a broken version number, previous-correction link, invalid root/correction date or recorded timestamp, multiple pre/post-cutoff corrections, or declaration correction cutoff. Correction: add bounded lineage corruptions for each validated link/date and a mixed correction chain proving current PTO comparison uses the final leaf while PTO/declaration progress independently uses only facts recorded and effective by the report cutoff.

6. **MEDIUM — transaction preservation does not demonstrate a consistent read view.** The contract requires the reader to operate inside the publication's consistent read view and neither begin nor end the caller transaction (`specs/OTIZ-EXCEL-INPUTS-001.md:9`). The test proves only that `@@in_transaction` remains true after the call (`tests/Otiz/excel_inputs_001_test.php:45`); it would pass if the reader committed and immediately began another transaction, or if its multiple queries observed a concurrent half-new source set. Correction: retain an independent witness visible only in the original transaction and use a controlled second connection/inter-query hook or equivalent deterministic barrier to prove one coherent snapshot across deadline, composition, completion and progress while also proving the caller transaction identity remains intact.

## Traceability, determinism, and RED evidence

The test cites the specification and reaches the public reader after a real Yii selection, signed-original upload, opening and checklist command flow. The happy-path values for deadline, 200 basis points, installer split, premium and shaft coefficient are independently asserted; the future certificate, corrected PTO comparison, documentary cutoff, changed-installer, retraction, all-items total, no-write and caller-transaction checks are useful foundations. Direct fixture corruption is isolated and restored for most exercised branches.

The retained RED record `1789412252666886000-a708d7e3b100477da950f496d1728edc` completes the real Yii setup and fails at the intended missing behavior: expected one eligible native row without a registered legacy order, actual zero. Its candidate source `543913ef2b6b336dfd844db7c45a455dd06e685a5688994c64dc06915aff1fc0` differs from the reviewed package only through later lifecycle review-record content; its executable source `f24997c83ce5e1e0fe43d197b17a034fa2b38b76379cc5435972ed066ba923ce` exactly matches the restored package and `source_drift=false`. The RED is therefore valid for the reached gap, but it stops before the sensitivity gaps above.

The package snapshot restores successfully and reports candidate `cb8f610594bb95003065bbdea1233081b1952bb5a6db3c960e898e09debb4190` and executable `f24997c83ce5e1e0fe43d197b17a034fa2b38b76379cc5435972ed066ba923ce`. PHP syntax checks for the reviewed spec/test and `git diff --check` are GREEN. No full local suite was run. CI and deployment remain `UNKNOWN` and are not treated as approval or GREEN.

## Required changes

Address findings 1–6 in the normative contract/test, capture fresh intended RED for the corrected exact executable source, regenerate the bounded package, and resubmit the complete input-reader scope for independent Gate 3 review.

## Verdict

`CHANGES_REQUESTED`

Gate 4 for `OTIZ-EXCEL-INPUTS-001` is blocked. This verdict does not alter the separate certificate Gate 3 approval or authorize changes to certificate, publication, payment, calculator, deployment, import or backfill scope.

---

## Gate 3 correction review — 2026-09-14

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T191123Z-3bce6f84dd/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `82d91ee66148e58b69f18196094ac655e5702a406726c5e0623afd99cbb0fc16`, executable source digest `f9593724fcc413c70f93e08309af338845089c5bc79b90d1cdd8d7e0f1fe0411`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T191123Z-3bce6f84dd/snapshot`, patch SHA-256 `48dea83479c4ce234f6d19028f26c66e9447719f628b158c1f38dd51668c23c1`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T191123Z-3bce6f84dd/verification-plan.json`, SHA-256 `062ef6dcf5593c2abfdaf38e072a62283671c61190dc50909ed68f09d67d8ea5`
- Review scope and independence are unchanged. Certificate implementation present in the snapshot and draft publication work remain outside this verdict.

### Prior finding resolution

- Prior finding 1 is resolved. `inputBlocked` uniformly asserts row visibility, exact issue code and null operands. The corrected suite covers missing and invalid plan, absent/damaged applied composition, missing/damaged template, missing attribution, unknown item, operation-template mismatch, zero-contribution selected installer, unresolved premium and shaft norm, certificate corruption, completion corruption and card hash corruption.
- Prior finding 2 is resolved. The multi-case matrix distinguishes state, start cutoff, classification and missing legacy object while asserting object-ID ordering. A second selection/original is applied through the public workflow and a third later original remains pending, proving only the latest application controls composition.
- Prior finding 3 is resolved. Completion, attribution correction and retraction each receive device/server cutoff witnesses; accepted revision is placed in conflict with row ID/device chronology; operation template identity and unknown items fail closed; both supported template formats are exercised; and the 200/3 split independently proves binary-tab remainder allocation and conservation.
- Prior finding 4 is mostly resolved. The contract now defines exact composition and progress canonical payloads, row ordering, scalar representation and fixed labels/locators. The test independently selects the source rows, computes both digests, pins all six top-level evidence keys, compares certificate evidence byte-for-byte at the value boundary, and proves attribution/template mutations change progress evidence.
- Prior finding 5 is resolved. Three PTO corrections plus a declaration correction distinguish current comparison date from report-cut documentary progress. Root/correction dates, recorded timestamps, version continuity and both previous links are corrupted independently, and a future declaration receipt is excluded.
- Prior finding 6 is resolved. A caller-local write remains invisible to a second connection, while four source classes mutate concurrently; repeated reads retain the old result and the uncommitted witness. A reader that commits/restarts or leaves the caller's consistent snapshot cannot pass.

### Remaining findings

1. **HIGH — the historical non-applied installer can leak into `team` without failing the remainder test.** The contract says team membership comes only from the latest applied selected installers and forbids inventing workforce membership (`specs/OTIZ-EXCEL-INPUTS-001.md:20`). The adversarial fixture adds installer `800` only to historical checklist attribution and asserts that progress contributions become `['7001'=>67,'7002'=>67,'800'=>66]`, but never asserts the returned `team` (`tests/Support/ExcelInputsAdversarial.php:28-30`). An implementation that correctly allocates progress yet also adds tab `800` to the payable team would pass. Correction: in this same 200/3 witness, assert that `team` contains only applied tabs `7001` and `7002`, with their applicable contributions/weights, and excludes `800` while retaining `800` in progress provenance.

2. **MEDIUM — two evidence source envelopes remain only partially observable.** The clarified grammar fixes `completion.source` label and locator (`specs/OTIZ-EXCEL-INPUTS-001.md:34`), but the test checks only its hash plus root/corrections (`tests/Otiz/excel_inputs_001_test.php:49`). The original-deadline assertion checks locator and hash but neither fixes nor checks its label (`specs/OTIZ-EXCEL-INPUTS-001.md:14`; `tests/Otiz/excel_inputs_001_test.php:22`), despite the general rule that every source envelope has a label (`specs/OTIZ-EXCEL-INPUTS-001.md:11`). A reader can emit an empty/wrong deadline label and wrong completion label/locator while satisfying the suite. Correction: define the original-deadline label, then assert exact `{label, locator, contentSha256}` objects for original deadline and completion, as already done for composition and progress.

### Corrected RED and verification evidence

The fresh record `1789413061694543000-6d51ddc970e94045a6dbd2b579140565` is bound at start/end to candidate source `82d91ee66148e58b69f18196094ac655e5702a406726c5e0623afd99cbb0fc16` and executable source `f9593724fcc413c70f93e08309af338845089c5bc79b90d1cdd8d7e0f1fe0411`, with `source_drift=false`. It completes real Yii setup and fails at the intended first missing native-row assertion: expected one, actual zero. This is valid RED for the corrected exact source, though execution cannot reach the two remaining assertions until the selection gap is implemented.

The frozen snapshot restores successfully and reports the package digests above. PHP syntax checks for the corrected test and adversarial helper and `git diff --check` are GREEN. No full local suite was run. CI and deployment remain `UNKNOWN` and are not treated as approval or GREEN.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked on the two bounded corrections above. Preserve the resolved blocker, selection, applied-composition, event-order/cutoff, canonical-proof, completion-history and transaction witnesses; capture fresh intended RED after correction and resubmit the exact source.

---

## Gate 3 final correction review — 2026-09-14

- Final package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T192131Z-488d6831d7/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `c244df2fd842da92905db6b2cef95e6d2d42ad25c74acba022041fdfb536a47f`, executable source digest `92d598476e669bed1d3066e40baea0f8b3f9b3720cb4f8482455b70d87fb0afe`
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T192131Z-488d6831d7/snapshot`, patch SHA-256 `9c013c033825fccc3a49310d71433a40ec723aead8eb78cef26d643b091faf37`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T192131Z-488d6831d7/verification-plan.json`, SHA-256 `b4625b5a0b7b730e774591e7902f6505a1218136d3d56b366847d33e2e5992ee`
- Review scope and independence are unchanged. Certificate implementation and publication/payment artifacts present in the snapshot remain outside this verdict.

### Remaining finding resolution

- The historical-installer finding is resolved. In the non-divisible 200/3 attribution witness, progress provenance retains contributions for tabs `7001`, `7002` and historical tab `800`, while the returned payable team is asserted exactly as applied tabs `7001` and `7002`. A reader that invents team membership from historical attribution can no longer pass.
- The evidence-envelope finding is resolved. The contract now fixes original-deadline label `Исходный плановый срок`; the test independently computes its digest and compares the complete label/locator/hash envelope. The completion test likewise compares exact label `История акта ПТО`, case/fact locator and independently computed full-chain digest.

### Complete assessment and RED evidence

No findings remain in the agreed native-input scope. Taken together, the real Yii flow and adversarial helper trace the `MariaDbNativePremiumInputs::forDate` seam; independently derive input values and canonical evidence; cover native selection, latest applied composition, deadline and certificate behavior, corrected completion history, checklist formats/order/cutoffs/attribution, all named fail-closed blockers, caller transaction preservation and a coherent read snapshot. The tests are deterministic and isolated from production systems.

Fresh intended RED record `1789413438342074000-82589005f6a44ff2b18f979b9f0dbff7` is bound at start/end to candidate source `c244df2fd842da92905db6b2cef95e6d2d42ad25c74acba022041fdfb536a47f` and executable source `92d598476e669bed1d3066e40baea0f8b3f9b3720cb4f8482455b70d87fb0afe`, with `source_drift=false`. It completes the real Yii setup and fails for the intended missing behavior: the native case without a registered legacy order is absent, expected row count one and actual zero.

The frozen snapshot restores successfully and reports the exact package digests above. PHP syntax checks for the reviewed test and adversarial helper and `git diff --check` are GREEN. No full local suite was run. CI and deployment remain `UNKNOWN` and are not treated as approval or GREEN.

### Final verdict

`APPROVED`

Gate 3 for `OTIZ-EXCEL-INPUTS-001` passes on the exact source above. Gate 4 may proceed for the native input-reader slice. This approval does not cover certificate implementation, publication, acceptance, settlement/payment, deployment, import or backfill.

---

## Gate 3 implementation-fixture delta review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T194524Z-91b4fb39bf/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `719b1c81888252e0d199c94e58b0e3bfe0a3f563420cf246882a2c786032a1dc`, executable source digest `612923bd364f53de61c46374016d3c7e7be7a3768c3fda673265f42565f8970e`
- Snapshot patch SHA-256: `e2185a187f7473cd14a29806c3d2d7f20fff44ed759b53dc3f3360ef1eb7293c`
- Verification plan SHA-256: `7a05421fcecda60e58a7681b7d6a0694fa99ed0ba82e86f640904a98837f421d`

### Delta assessment

No findings remain in the bounded root-authored delta. The contract now states the existing runtime distinction precisely: a native case may have no migration-classification row, while a case that has classification provenance must be `native_candidate`. The adversarial matrix creates complete synthetic provenance rows rather than assuming the real Yii fixture has migration provenance, so setup no longer fails before the intended assertion. The second and third public selection requests use expected revisions 1 and 2, matching the real selection lifecycle and removing the unrelated 409 fixture failure. These changes preserve the approved behavior and strengthen setup fidelity; they do not weaken any prior assertion.

Exact-source focused record `1789415080626953000-4f508594324e41f5a7ded7d9a111cc1c` is GREEN with `source_drift=false` and output `PASS OTIZ-EXCEL-INPUTS-001`. Syntax and whitespace checks are GREEN. No full local suite was run; CI and deployment remain `UNKNOWN`.

### Delta verdict

`APPROVED`

The bounded fixture/spec correction retains Gate 3 approval. A separate Gate 5 production finding is recorded in `reviews/code/OTIZ-EXCEL-INPUTS-001.md` and must be corrected before this slice completes.

---

## Gate 3 provenance-join regression review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T194926Z-7a96b36de4/package.json`
- Exact reviewed source: reconstructible snapshot over base `79b3a4cecae558d74d781f353e967f03c036b921`, candidate source digest `c3c67e4f8346e234da3036b3bdba6592b682c40f397b6f5a8b7962334a313768`, executable source digest `645132980e7478c40b99291ecb306d9fdbb47b6167d73e6853dd057d3ba82a18`
- Snapshot patch SHA-256: `6b5ff81e291d13c826acc5ecbf167cb538db36a8eec1ecf089d842ccf2e58945`
- Verification plan SHA-256: `f53496c2472570367821f2d9a0e594bd307da4a355d7955ca1f23acdb668aee4`

### Delta assessment

No findings remain in the bounded regression delta. The fixture inserts foreign operational-case provenance with `output_id=999999` against the real case's legacy object `4512`, first as `active_candidate` and then as `native_candidate`. In both cases it requires the reader's exact object IDs to remain `[4512]`. The first branch detects accidental exclusion by a foreign classification; the second detects duplicate/admitted joins. The row is complete, uses a distinct output identity, and is removed in `finally`, so it does not weaken or contaminate the remaining matrix.

Fresh intended RED record `1789415334673060000-73cef15d6b924f118ec528e30f70c914` reaches the new assertion and fails for the Gate 5 defect: expected `[4512]`, actual `[]` under the foreign `active_candidate` row. Its candidate digest predates review-record-only content, while executable source `645132980e7478c40b99291ecb306d9fdbb47b6167d73e6853dd057d3ba82a18` exactly matches the frozen package and `source_drift=false`. The snapshot restores successfully; helper syntax and `git diff --check` are GREEN. No full local suite was run. CI and deployment remain `UNKNOWN`.

### Verdict

`APPROVED`

Gate 3 approves the focused same-object/foreign-case provenance regression. The executor may correct the production join and return the exact-source GREEN candidate for Gate 5 rereview.
