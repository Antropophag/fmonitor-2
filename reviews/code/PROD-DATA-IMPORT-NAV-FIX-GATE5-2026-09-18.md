# Gate 5 code review — production-data import and shared navigation fixes

- Reviewer: separately tasked agent `/root/prod_data_fix_gate5`; authored none of the reviewed specifications, tests, Gate 3 record, or production changes.
- Review date: 2026-09-18.
- Specification/test author: root agent.
- Production implementation author: separate executor agent.
- Reviewed source: dirty snapshot over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`; six-artifact binary diff SHA-256 `4d4166bfab0740189812e83af6eafdd59e5788d0723a2d916220af34591764a8`.
- Specifications: `specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md` SHA-256 `8e75af71fc6ea505a5f61ab1dee4dee26bcfa28bc8dd4f52f9262019e6842748`; `specs/YII2-MAIN-NAVIGATION-001.md` SHA-256 `2dc278b18b0e6d984d3f6853a6037c781cad3308a7e9e6a234afc9b87b13a8bd`.
- Tests: `tests/Yii2/yii2_legacy_import_db_001_test.php` SHA-256 `9ec0bc6d0e56f99359d4617c8d442aad18172de11a1b30c0963cba30fa52a8d4`; `tests/Yii2/yii2_main_navigation_001_test.php` SHA-256 `e270d636fba0eaa464ac7e735873359db6be8125300b5c1d31db640480664ce0`.
- Production: `app/InstallationProcess/MariaDbLegacySourceSnapshot.php` SHA-256 `6cec9983d0725ba72c29f39415bf1436e46ce096b8db45c1b79d2f5b5ee35c92`; `app/YiiRuntime/MainNavigation.php` SHA-256 `9620163a7495becc73fae76d3df0b1d35c126bcc339787c05e2fbbe9c5a278f7`.
- Gate 3: `reviews/tests/PROD-DATA-IMPORT-NAV-FIX-GATE3-2026-09-18.md` SHA-256 `97e0ddfca6ff29c2c05089aeb94f912299b12a93ecb3c2ea930806409ce6ab53`; final and post-Gate-4 test-delta verdicts `APPROVED`.
- Verdict: `APPROVED`.

## Complete findings

None.

## Assessment

The import correction binds three string parameters in the same order as their SQL placeholders: `workdatefinish`, `ptoactdate`, then `factworkstartdate`. The `workdatefinish <= cutoff` projection therefore normalizes only a completion fact unavailable at the fixed snapshot cutoff to `NULL`; equality remains visible. Normalization occurs before `LegacyImportRouting::classify()` and the eligibility checks, so a future completion cannot incorrectly classify or exclude an otherwise eligible unopened object. The same normalized value reaches the canonical immutable mirror, while the source connection remains a consistent read-only snapshot.

The import test reaches the real console seam with a fixed cutoff and proves the formerly excluded object is classified and imported, its mirrored future completion is `NULL`, details and template association are published, the same-cutoff replay is idempotent, a mid-import fault publishes no partial facts, a later immutable-mirror conflict adds no case facts, the source is unchanged, and the source password is absent from output. No secret handling, append-only history, provenance, or all-or-nothing regression was found.

The navigation correction adds exactly one established renderer entry. `/pilot/installers` is emitted only when canonical `installers.read` access succeeds; the existing renderer continues to filter every other section through its corresponding effective permission, keeps both administration links under `access.administer`, preserves feedback and its return path, and changes no route or controller authorization. The test exercises all five scoped HTTP surfaces, exact ordered membership, labels and current-section semantics, removes only `installers.read` to prove link absence everywhere, confirms direct installer access remains `403`, and verifies reads and denial create no facts. The other negative permission phases and OTIZ internal navigation remain covered.

The implementation is minimal and does not change RBAC definitions, server-side guards, audit writers, persistence ownership, rapid-pilot boundaries, or configuration/secret files.

## Verification evidence

- `php -l app/InstallationProcess/MariaDbLegacySourceSnapshot.php` — PASS.
- `php -l app/YiiRuntime/MainNavigation.php` — PASS.
- `git diff --check` — PASS.
- `php tests/Yii2/yii2_legacy_import_db_001_test.php` — PASS: `YII2-LOCAL-DATA-BOOTSTRAP-001 native legacy import`.
- `php tests/Yii2/yii2_main_navigation_001_test.php` — PASS: `YII2-MAIN-NAVIGATION-001 shared permission-aware Yii navigation`.

No full local `make test` or `make verify` run was performed, consistent with the owner restriction. At review time `python3 tools/delivery/harness.py state` reported no active binding and CI/GitHub/deployment as `UNKNOWN`; this Gate 5 approval does not convert those states to GREEN or authorize publication. The checked-in current-delivery-goal names the separate #157 compact-manifest queue, while this explicitly assigned review concerns the isolated prod-data-fix worktree; that operational mismatch remains for root to reconcile before publication.

## Verdict

`APPROVED`

No Gate 5 correction is required for the reviewed scope and exact artifact bytes. Any later change to the six reviewed specification/test/production artifacts requires delta review; exact-source CI remains a separate required delivery gate.

---

## Rereview — aggregate legacy fact counts after deployment finding

- Rereviewer: the same independent `/root/prod_data_fix_gate5` agent; authored none of the correction.
- Updated reviewed source: dirty snapshot over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`; updated six-artifact binary diff SHA-256 `718141a1d2e2473bf66a9db84e31e3aec089faf8f8ae12d076f60969118df7a2`.
- Changed production artifact: `app/InstallationProcess/MariaDbLegacySourceSnapshot.php` SHA-256 `046a85fdc465266a0dfa67214fe4c1ee959f3942397e49d142761c368f3c85cf`.
- Harness state after focused verification: source `91f3f1478d31711ce9f0816ed974bdcd188575c6d38ac40a4e8df6692bed5303`, executable source `010a27376c48b8e4a9f1efa904a241b55fd03d0964d5b1c33388227ec468c4ac`; CI and deployment remain `UNKNOWN`.
- Rereview verdict: `APPROVED`.

### Complete findings

None.

### Assessment

The correction removes the per-object query pattern without changing classification inputs. It buffers the already whole-snapshot `fm_maintable` result once and issues exactly two aggregate fact queries: checklist events group directly by `value_id`; installer attributions join `checklist_value_id` to `fm_install_checklists_values.id` and group by the resulting `v.value_id`. Both apply the same inclusive `ctime <= cutoff` predicate as the replaced queries. Integer-keyed maps preserve the object association, and `?? 0` preserves the former `COUNT(*) = 0` result for objects with no matching fact rows.

The main read and both aggregates execute after `START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY` and before commit, so they observe the same repeatable-read source snapshot. Cutoff parameter order and the earlier `workdatefinish` normalization remain unchanged; classification still runs only after both counts have been attached. Prepare, bind, execute, result conversion, later template reads, and mapping all remain inside the existing `try`; any thrown failure attempts rollback and is rethrown without returning a partial snapshot.

Memory is proportional to the source snapshot plus one integer count per distinct object represented in each log aggregate, rather than to the number of individual fact rows. The aggregate queries return counts rather than materializing log events and eliminate deployment-scale query amplification. This seam already returns the complete source snapshot and templates as arrays, so the correction does not introduce a new unbounded result category; pagination would be a separate design change rather than a requirement of this fix.

The complete production/spec/test diff was reread. Navigation, specifications, and root-authored tests are unchanged from the prior approval. No SQL grouping, join, cutoff, absent-count, snapshot, rollback, secret, history, or maintainability finding remains.

### Verification

- `php -l app/InstallationProcess/MariaDbLegacySourceSnapshot.php` — PASS.
- `git diff --check` — PASS.
- `php tests/Yii2/yii2_legacy_import_db_001_test.php` — PASS.
- `php tests/Yii2/yii2_main_navigation_001_test.php` — PASS.

No full local suite was run. The updated exact artifact bytes are Gate 5 `APPROVED`; exact-source CI remains separately required and is not represented as GREEN by this rereview.

---

## Final rereview — owner-approved planned-start eligibility scope

- Reviewer: independent `/root/prod_data_fix_gate5`; authored none of the specifications, tests, Gate 3 record, or production changes.
- Updated reviewed source: dirty snapshot over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`; eight-artifact binary diff SHA-256 `c46c3ffcce837de7829dd924800439393a10f346aa927b4f86900ece7a7b2afb`.
- Changed production artifact: `app/InstallationProcess/MariaDbLegacySourceSnapshot.php` SHA-256 `5612edd2841edf5ec608235f73f147028ebcd6bbc4578b46f74d8b34e71aad68`.
- Expanded contracts: `specs/PILOT-CASE-IMPORT-001.md` SHA-256 `90656047aab2576cc29e745b707c3faad6dd52169c7c86bb60c42b7a82cf553b`; `specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md` SHA-256 `c9d30dd435ede7dfd5444f96f4d93de7d0a6b6af23a0435a9b067c62d9e62506`.
- Expanded tests: `tests/InstallationProcess/pilot_case_import_001_test.php` SHA-256 `c6a247b09068406066e6e814466c834309dbb606c8d47705d86ad33362d8e342`; `tests/Yii2/yii2_legacy_import_db_001_test.php` SHA-256 `d4ead1591375312d3519493fd27b8b6b8f2373f8598176b403b922df3c6c0e7c`.
- Gate 3 record: `reviews/tests/PROD-DATA-IMPORT-NAV-FIX-GATE3-2026-09-18.md` SHA-256 `adc56b8276673c9edb37fefee28e1dbde74d60c271beabf134be3c378c9ec764`; planned-start correction verdict `APPROVED`.
- Harness state after verification: source `6396f56ef60c538c8b2fa3603a0c6c1bbc75ab4325245072e98a2be9cead27fb`, executable source `373e58762c6a7a533be6eed32732f11e8a8e07cedcb5136b71ffd5b7941e50bc`; CI and deployment `UNKNOWN`.
- Final verdict: `CHANGES_REQUESTED`.

### Complete findings

1. **HIGH — the explicit-ID public seam does not enforce the planned dates that the expanded contract retains as required.** `specs/PILOT-CASE-IMPORT-001.md` section 5 requires nonempty `plannedStartDate` and `plannedFinishDate`. The mass importer correctly retains `$start === null || $plannedFinish === null`, but `app/InstallationProcess/PilotCaseImporter.php` method `rejections()` only checks address, entrance, and registration number before PTO/completion. A new legacy row with blank/null `workdatestart`, or with both `workdateendadjusted` and `plan_finish_date` blank, is therefore imported through `bin/fmonitor2-import-cases.php`, contrary to the reviewed contract. The two-seam test delta proves a valid pre-boundary date but has no missing-start/missing-finish case at the explicit-ID seam, so all three focused commands can remain GREEN under this violation. **Correction:** preserve date-independent eligibility while rejecting blank/null required planned start and absent planned finish through the explicit-ID seam, add deterministic public-seam cases for both missing facts and their exact `LEGACY_OBJECT_REQUIRED_DATA_MISSING` result/no-write behavior, then repeat the applicable Gate 2/Gate 3 review before Gate 5.

### Assessment

The two contracts consistently state that planned start remains a required object fact but its value relative to `2026-10-01` does not determine pilot eligibility. The obsolete rejection code and exact rejection example were removed, the verification inventory explicitly covers valid dates before/on/after the former boundary, and the cited product rule matches `PRODUCT.md`: unopened objects without checklist movement are eligible regardless of planned date. The explicit-ID implementation does not yet satisfy the retained required-date part of that contract, as finding 1 details.

The only new production change since the aggregate-count rereview removes `$start < '2026-10-01'` from the mass snapshot eligibility expression. The adjacent `$start === null` condition remains, so absent, zero, or malformed planned starts are still rejected by `self::date()`/the required-data guard. Planned finish remains required. No classification input or routing rule changed: event/attribution history, actual start, started/progress flags, PTO, visible completion, and quarantine status continue to exclude non-native candidates exactly as before. The fixed-cutoff projection still hides only future completion before classification, and both aggregate fact queries retain their approved same-snapshot semantics.

The explicit-ID seam already had no calendar lower-bound. Its corrected test now proves an otherwise-valid `2026-09-30` object creates exactly one unopened `needs_assignment_order` case and proves the mixed invalid row is rejected only for its other invalid facts. Existing-case replay continues to bypass mutable legacy re-evaluation and preserves the progressed case byte-for-byte; the mixed already-present-plus-new path creates only the new case. The mass seam imports the pre-boundary native candidate with its exact mirror/details/template/provenance facts, safely reports it as `alreadyPresent` on replay, and preserves all facts. Those replay/new-import paths are sound, but they do not compensate for the untested explicit-ID missing-date acceptance.

All prior approved fixes remain present: cutoff parameter binding/order and future-completion normalization, aggregate `GROUP BY` counting with missing groups mapped to zero, repeatable-read read-only source semantics and rollback, all-or-nothing target writes, mirror conflict rejection, source immutability and secret redaction, and permission-filtered installers navigation with unchanged direct authorization. The complete eight-artifact diff contains no broader production eligibility, RBAC, navigation, history, audit, or persistence change.

### Verification

- `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_case_import_001_test.php` — PASS: `PILOT-CASE-IMPORT-001 CLI contract`.
- `php tests/Yii2/yii2_legacy_import_db_001_test.php` — PASS: `YII2-LOCAL-DATA-BOOTSTRAP-001 native legacy import`.
- `php tests/Yii2/yii2_main_navigation_001_test.php` — PASS: `YII2-MAIN-NAVIGATION-001 shared permission-aware Yii navigation`.
- PHP lint for both changed production classes — PASS.
- `git diff --check` — PASS.

No full local suite was run. Gate 5 is `CHANGES_REQUESTED` for these exact artifact bytes. Exact-source CI and deployment remain separate required gates and are not claimed GREEN.

---

## Final correction rereview — required planned-date presence

- Reviewer: independent `/root/prod_data_fix_gate5`; authored none of the corrected specification, tests, Gate 3 record, or production implementation.
- Updated reviewed source: dirty snapshot over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`; complete nine-artifact binary diff SHA-256 `f410a9989f44fbee1a6536d887d796790addd67f67cc7cbb538ff808f25e73b7`.
- Corrected production artifact: `app/InstallationProcess/PilotCaseImporter.php` SHA-256 `58cae67dfe66c729bef0a12721628da9c0e01a635ae3fa01d172f8ab4bc77a67`.
- Unchanged mass-import production artifact: `app/InstallationProcess/MariaDbLegacySourceSnapshot.php` SHA-256 `5612edd2841edf5ec608235f73f147028ebcd6bbc4578b46f74d8b34e71aad68`.
- Corrected contract/test: `specs/PILOT-CASE-IMPORT-001.md` SHA-256 `a267ccc66afd614d9bb68806f8113e3313f8575f2e9e3bb7beec6679d3940ba7`; `tests/InstallationProcess/pilot_case_import_001_test.php` SHA-256 `83d52a844532c9f7d6463e28526f1307ef72ee30ee4bd93376d506805aa37e6e`.
- Gate 3 record: `reviews/tests/PROD-DATA-IMPORT-NAV-FIX-GATE3-2026-09-18.md` SHA-256 `e9357e85afeb265342f9670b704a59cec689fafeed7aaeac43faf78be259a9e6`; required planned-date correction verdict `APPROVED`.
- Harness state after verification: source `4d86f3905c9d9731b8de93c824663ffc1c89aaef7cf106330cbb5de48b04d961`, executable source `a2ee82d14a92e178eb62c6fb418b61810a7920beac1016d845db487d53516937`; CI and deployment `UNKNOWN`.
- Final verdict: `APPROVED`.

### Complete findings

None.

### Prior finding disposition

The prior HIGH finding is resolved. `PilotCaseImporter::rejections()` now adds `LEGACY_OBJECT_REQUIRED_DATA_MISSING` when `workdatestart` is absent after approved legacy presence normalization, or when both `workdateendadjusted` and fallback `plan_finish_date` are absent. The independently approved public-seam test supplies distinct missing-start and missing-finish rows, expects the exact ordered reason for both, and proves the isolated target receives no case facts.

### Assessment

`missingLegacyDate()` exactly preserves the explicit-ID seam's established presence-only boundary: `null`, trimmed empty strings, all-zero strings, and `0000-00-00...` sentinels are absent. Other nonempty values remain present for this seam, so the correction does not silently introduce calendar parsing or restore the removed `2026-10-01` threshold. Finish selection has the contracted shape: adjusted finish satisfies the requirement when present, otherwise `plan_finish_date` is the fallback; rejection occurs only when both are absent.

Required-date checks run after existing IDs are separated from new IDs and before any INSERT. Thus replay of an already-present or progressed case continues not to reread mutable legacy eligibility, while any missing-date rejection in a new mixed batch returns the stable reason and publishes none of that batch's new cases. The correction adds no update/delete path, does not alter timestamps, locking, concurrency, lost-commit reconciliation, or append-only history, and does not expose raw values or secrets.

The mass snapshot still requires a parseable start and planned finish but no longer compares the start to the former cutoff. Its classification, future-completion normalization, aggregate checklist/attribution counts, read-only repeatable-read snapshot, immutable mirror, provenance, replay, rollback, and secret-redaction behavior remain unchanged. The permission-filtered navigation correction and negative authorization behavior also remain unchanged.

The complete accumulated diff was reviewed. No scope broadening beyond owner-approved planned-date eligibility and required presence was found, and no prior Gate 5 cutoff, performance, classification, mirror, navigation, authorization, history, or security concern regressed.

### Verification

- `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_case_import_001_test.php` — PASS: `PILOT-CASE-IMPORT-001 CLI contract`.
- `php tests/Yii2/yii2_legacy_import_db_001_test.php` — PASS: `YII2-LOCAL-DATA-BOOTSTRAP-001 native legacy import`.
- `php tests/Yii2/yii2_main_navigation_001_test.php` — PASS: `YII2-MAIN-NAVIGATION-001 shared permission-aware Yii navigation`.
- PHP lint for `PilotCaseImporter.php`, `MariaDbLegacySourceSnapshot.php`, and `MainNavigation.php` — PASS.
- `git diff --check` — PASS.

No full local suite was run. Gate 5 is `APPROVED` for the complete candidate at the exact artifact bytes above. Exact-source CI and deployment remain separate required gates and are not claimed GREEN.

---

## Final mirror-canonicalization rereview

- Reviewer: independent `/root/prod_data_fix_gate5`; authored none of the corrected specification, tests, Gate 3 record, or production implementation.
- Updated reviewed source: dirty snapshot over base `b56d3ec0518d1870cee16d1ce4755bb5633b441a`; complete ten-artifact binary diff SHA-256 `4f1a622308ed54927b72864ca566c7055cfde9bb83946be5f44480b26c677cd5`.
- Corrected production artifact: `app/InstallationProcess/MariaDbLegacyImportApplication.php` SHA-256 `e9a85dc3202bf0a52089c2d0d8180c5b22d16a75bcc7895d810897a4c0a22043`.
- Corrected contract/test: `specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md` SHA-256 `5e27d264b619c2c569ce0db1444095417a3985eaa0a15de1a9fb8f8155443f4e`; `tests/Yii2/yii2_legacy_import_db_001_test.php` SHA-256 `5418c8d55743e55b4ca919501bba509975f49089bab0da9e2c5996c79f868272`.
- Gate 3 record: `reviews/tests/PROD-DATA-IMPORT-NAV-FIX-GATE3-2026-09-18.md` SHA-256 `1bf0459449480a1a02da4040a1769ff1d7c19d3ffd0c25ef1d53663e7b8e7003`; replay canonicalization correction verdict `APPROVED`.
- Harness state after verification: source `33281541c24c54b01ae4900d508bec8047507d22c719c5f3cdd3f9d829db87d3`, executable source `6171c5924ffe0e7f9c7b1499fd7c6687692101e4f2974370437e3504a458a1b3`; CI and deployment `UNKNOWN`.
- Final verdict: `APPROVED`.

### Complete findings

None.

### Assessment

The correction applies one strict `canonicalDate()` function symmetrically to the source expectation and an existing target mirror for all six date fields. Exact `Y-m-d` and `Y-m-d H:i:s`/`Y-m-dTH:i:s` values become `Y-m-d`; `null`, trimmed blank, all-zero, and `0000-00-00...` sentinel representations become `null`. Calendar-invalid dates, malformed suffixes, and out-of-range time components throw `MIRROR_DATE_INVALID`, remain inside the import transaction, and cannot be mistaken for equivalent facts.

On first insert, the canonicalized expected array is bound, so the target mirror receives canonical dates and null absence rather than source representation artifacts. On replay, the existing row is canonicalized only in the local comparison array: no UPDATE path was added, so an already stored raw-but-equivalent representation is accepted without rewriting its bytes. Any non-date difference still compares strictly and raises `MIRROR_CONFLICT`.

The test now supplies an imported source row with a timestamp-valued adjusted start and zero-date PTO, then asserts the exact first-insert target values `2026-10-02` and `null`. Before replay it captures complete ordered contents of the legacy mirror, installation cases, object details, template snapshot, template association, and classification provenance; after a zero-import/zero-detail/zero-association replay it asserts each table is byte-equivalent. The later address mutation still proves genuine immutable mismatch rejection and no new case facts. This distinguishes canonical equality from conflict suppression or hidden repair.

Canonicalization does not alter source classification or cutoff semantics: it occurs in the target import owner after the read-only source snapshot has already normalized/classified eligible rows. The prior future-completion, planned-date, aggregate-count, required-date, all-or-nothing, replay, provenance, source immutability, secret-redaction, navigation, and authorization behavior remains intact. The complete accumulated diff contains no new update/delete path or history rewrite.

### Verification

- `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_case_import_001_test.php` — PASS: `PILOT-CASE-IMPORT-001 CLI contract`.
- `php tests/Yii2/yii2_legacy_import_db_001_test.php` — PASS: `YII2-LOCAL-DATA-BOOTSTRAP-001 native legacy import`.
- `php tests/Yii2/yii2_main_navigation_001_test.php` — PASS: `YII2-MAIN-NAVIGATION-001 shared permission-aware Yii navigation`.
- PHP lint for `MariaDbLegacyImportApplication.php`, `MariaDbLegacySourceSnapshot.php`, `PilotCaseImporter.php`, and `MainNavigation.php` — PASS.
- `git diff --check` — PASS.

No full local suite was run. Gate 5 is `APPROVED` for the complete corrected candidate at the exact artifact bytes above. Exact-source CI and deployment remain separate required gates and are not claimed GREEN.

---

## Publication audit — clean rebased exact commit

- Reviewer: independent `/root/prod_data_fix_gate5`; authored none of the reviewed production, specifications, tests, or rebase.
- Exact reviewed commit: `3d343961230c5963bc849309ef58894f7cd284e1`.
- Exact base and merge-base: `origin/main` at `4d6315b09ad4d3eef510d94bdfebf812be38b361`.
- Exact reviewed tree: `026e7dfe2ac7772bc065e5835cc3f046f811cfbc`.
- Exact commit binary-show SHA-256: `117cd02414ec6a2d05139f30caaaf05af9ec27ac787ef2f55b078af301329248`.
- Pre-rebase candidate commit used for byte comparison: `1f7c7e69`.
- Harness state before this docs-only audit addendum: clean worktree, HEAD `3d343961230c5963bc849309ef58894f7cd284e1`, source `b2b76f081dc19a7dd4d0292f7995b68f1679740010e4678fbccb3c3a2555cfcc`, executable source `7de1f324f61f02caa14628ab4c35933414dae58d9a124f766a565a7915345eb7`; CI and deployment `UNKNOWN`.
- Publication-audit verdict: `APPROVED`.

### Complete findings

None.

### Rebase comparison

The exact commit is a direct child of the stated `origin/main`; `git merge-base origin/main HEAD` returned the same base. `origin/main...HEAD` contains exactly the ten previously approved production/spec/test artifacts plus the independent Gate 3 and Gate 5 records. No unrelated candidate file was added by the rebased commit.

All four production files, all three specifications, the Yii legacy-import test, and the navigation test match their previously approved SHA-256 values byte-for-byte. Comparing the original candidate commit `1f7c7e69` with rebased commit `3d343961` across the ten behavioral artifacts found exactly one changed line in `tests/InstallationProcess/pilot_case_import_001_test.php`: its setup expectation advances the canonical migration from schema version 28 / applied versions 1–28 to the current base's schema version 29 / applied versions 1–29. Feature fixtures, assertions, rejection outcomes, replay snapshots, and production behavior are unchanged. The post-rebase focused PASS confirms the test still reaches its public behavioral seam on the new base.

The rebase therefore introduces no unreviewed behavior or expectation drift. The previously approved cutoff projection, aggregate source reads, planned-date eligibility and required presence, canonical first mirror insert, immutable equivalent replay, genuine conflict rejection, navigation permissions, negative authorization, history preservation, and secret redaction remain intact on the current base.

### Verification evidence supplied and audited

- `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_case_import_001_test.php` — PASS after rebase.
- `php tests/Yii2/yii2_legacy_import_db_001_test.php` — PASS after rebase.
- `php tests/Yii2/yii2_main_navigation_001_test.php` — PASS after rebase.
- `git diff --check origin/main...HEAD` — PASS.
- Worktree was clean before this audit record was appended.

No full local suite was run. Exact commit `3d343961230c5963bc849309ef58894f7cd284e1` is Gate 5 `APPROVED`. CI and deployment remain separate required gates and are not claimed GREEN. This publication-audit text is the only post-commit change and requires a docs-only follow-up commit; it does not alter the reviewed commit's production, specification, or test bytes.

---

## CI correction Gate 5 review

- Reviewer: independent `/root/prod_data_fix_gate5`; authored none of the CI correction policy, fixture, or Gate 3 record.
- Base/pushed HEAD: `4ce561a508d2452e0ed2ae17a87f0f27848f950f`.
- Reviewed behavioral/policy delta: `.quality-graph/verification-policy.json` plus `tests/InstallationProcess/pilot_snapshot_import_manual_test.php`; binary diff SHA-256 `648f04f05ea63eb572af2fab2d0a87fe52f07e952da2d4143cb46e3d11e77991`.
- Complete supplied delta including the Gate 3 record: binary diff SHA-256 `03c33a313d35eaae864fc498ec5d14d9dce0fb618b47f573a02579e007496bec`.
- Artifact hashes: policy `1bb5f48e05628cdf57ff32996f83f420a08e5cb2b4bfc057953abdf9077c1422`; snapshot test `92c3c34e5367bb052f584f4affc5bc8de36fb0a4211090f5ff6fbbd3c33b69f7`; Gate 3 record `cc588065a05d63c00203a67e08306a63125bd493c574045d88cb4b42b0bf132a`.
- Gate 3 CI-correction verdict: `APPROVED`.
- Gate 5 CI-correction verdict: `APPROVED`.

### Complete findings

None.

### Failure inventory and correction scope

The supplied exact CI failure inventory contains two failures and no additional `REGRESSION_FAILURE`: governance could not resolve a capability owner for the three changed legacy-import production files, and the manual snapshot integration fixture was rejected because it omitted now-required planned dates. The correction addresses exactly those causes. It does not change production code, normative specifications, runtime configuration, database schema, or public behavior.

### Assessment

The new `legacy-object-import` capability owner is bounded to exact paths for `MariaDbLegacyImportApplication.php`, `MariaDbLegacySourceSnapshot.php`, and `PilotCaseImporter.php`. Independent matching against the complete shipped ownership list resolved each path to exactly this one capability, with no zero-owner or multiple-owner ambiguity. The three verifier paths exist and cover distinct material boundaries: explicit-ID case import, manual snapshot import, and native Yii legacy database import. Empty consumers are appropriate because the correction registers direct owner tests rather than claiming a downstream capability dependency.

The policy addition is narrow: it does not use a broad directory glob, absorb unrelated installation-process owners, alter lanes/categories, waive checks, add allow-failure behavior, or weaken fail-closed planning. The complete 18-test change-verification suite remains GREEN, including shipped-policy concreteness, ambiguity rejection, registered-verifier enforcement, and bounded governance focus.

The snapshot fixture now supplies `plannedDates.start = 2026-09-01` and fallback `plannedFinish = 2026-12-01`, with adjusted values still null and `eligibility.plannedDateFilter` still null. It therefore satisfies the required-date contract while retaining a start before the former threshold, so an accidental restoration of `2026-10-01` filtering would still break the success expectation. Its first import, replay/no-op, object detail, checklist template, and no-template-association behavior is otherwise unchanged. No production code or approved feature expectation changed.

Adjacent impacts are bounded and beneficial: future changes to any of the three legacy import owners now schedule the three relevant verifiers, while the snapshot test remains a canonical integration witness for the alternative snapshot importer. No unrelated capability is made a consumer, and no competing owner mapping was introduced.

### Verification

- `php tests/InstallationProcess/pilot_snapshot_import_manual_test.php` — PASS.
- `python3 tests/Verification/change_verification_001_test.py` — PASS, 18 tests.
- JSON parse of `.quality-graph/verification-policy.json` — PASS.
- Exact owner-match inspection for all three paths — one match each: `legacy-object-import`.
- All three declared verifier files exist.
- `git diff --check` — PASS.

No full local suite was run. The CI correction is Gate 5 `APPROVED`; the candidate still requires a new exact-source CI result after this correction, and no deployment state is claimed.
