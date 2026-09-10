# Code review: YII2-INSTALLER-DIRECTORY-001

- Reviewer: `/root/installer_gate5`
- Authors under review: root authored scope/spec/tests and test-helper corrections; `/root/installer_executor` authored the five production paths. The reviewer authored none of them.
- Reviewed source: base `aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T214600Z-85eaf2cc8b/snapshot/source.patch`
- Exact source digest: `c25b25e6cf3685a15a5d8237382b3c76f4e5e8a30572c768b8de28a590f6159d`
- Snapshot patch SHA-256: `1e28acaac6e1262f787d5e5464575cd76e525946571bd02087e8c4cdd6fe7e4a` (verified against `snapshot/manifest.json`)
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T214600Z-85eaf2cc8b/package.json`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T214600Z-85eaf2cc8b/verification-plan.json`, SHA-256 `86ceb73deabc19daaabce1ef7e2a97f275699615676374945155f045bafb0279`
- Contract: `specs/YII2-INSTALLER-DIRECTORY-001.md`
- Gate 3: APPROVED at source `6bcabe783fe105cad70cf5fe7d880772d14887369564468f4e064dbcde10f87e`; three subsequent root-authored helper deltas independently APPROVED in `reviews/tests/YII2-INSTALLER-DIRECTORY-001.md`
- Verdict: `CHANGES_REQUESTED`

## Verification evidence reviewed

Root exact-source focused GREEN records:

- HTTP: `1789076525960011000-e8d2e73d6c5146ccb53ca63f4b166843`
- Browser: `1789076535179200000-bc8789cf820441b7a8e9f9c68324912d`
- Inventory: `1789076544202477000-8938706f3b0d4a289def43fa6cdb3d8f`
- Architecture guard: `1789076562007528000-0140be052aee4a579dcedff868453c1a`
- CI roster: `1789076588075022000-f190de07750b4d43902a625d2a42eb99`
- Change verification: `1789076620643232000-47cef9d924414fddb0065af3fb97e820`
- Architecture check: `1789076643080325000-f47bba729ada4d04a5e9ddafdc2ea4ef`

The additional local `make test` was stopped after unit GREEN and a long DB stage. Child PASS results subsequently classified by the harness as `UNKNOWN` because of source drift, and an unreadable temporary `.test-artifacts/.../shlz.css` caused a source-details `PermissionError` cascade. None of those results is treated as GREEN. CI and deployment are `UNKNOWN`.

The authorized verification plan reserves one authoritative full exact-source `make test` for CI after Gate 5. Therefore absence of a local full GREEN is not, by itself, a Gate 5 blocker. The incomplete retained inventory described below remains a review finding.

## Findings

1. **HIGH — Pagination does not implement or sensitively test the required current-page semantic.** Locations: `specs/YII2-INSTALLER-DIRECTORY-001.md:41-44`, `app/YiiRuntime/Views/installers.php:31,37`, `tests/Yii2/yii2_installer_directory_http_001_test.php:9`, `tests/Yii2/installer_directory_browser.mjs:11`. The contract requires pagination to have exactly one `aria-current="page"`. The active pagination link instead has only `data-current-page="true"`. The HTTP assertion succeeds by counting the unrelated sidebar current-section marker, and the browser test likewise checks only that sidebar marker. **Correction:** add `aria-current="page"` to the active pagination link and scope the HTTP/browser assertions to `nav.fm2-pagination`, accounting separately for the sidebar marker. Because approved expectation/test bytes must change, return the correction through Gate 2 and independent Gate 3/helper-delta approval, rerun affected exact-source focused checks, and prepare a new Gate 5 package.

2. **MEDIUM — The stopped full-run UNKNOWN/artifact-cascade inventory is not preserved in the delivery record as claimed.** Location: `docs/operations/yii2-installer-directory-delivery-2026-09-10.md:35-40`. The record says the complete inventory and record IDs were preserved by the executor, but it neither enumerates nor links them. Consequently the interrupted parent run, child PASS-to-UNKNOWN source-drift outcomes, artifact `PermissionError`, and unresolved disposition are not independently auditable from the delivery record. **Correction:** record the exact parent and child record IDs, complete PASS/UNKNOWN/failure inventory, interruption reason, affected artifact path/cause/disposition, and explicitly retain all UNKNOWN results as non-GREEN.

## Required disposition

Correct both findings and submit a newly prepared exact-source package for independent Gate 5 rereview. Test changes require the appropriate independent Gate 3 delta approval before Gate 5 can approve the corrected candidate. One full exact-source CI remains required after Gate 5 and cannot be inferred from focused GREEN evidence.

---

## Gate 5 rereview v2 — 2026-09-11

- Reviewer: `/root/installer_gate5`
- Reviewed source: base `aa4d20f30edb4b5e9c7de7abad8ac2d016ec4952` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T215322Z-dc84c39b40/snapshot/source.patch`
- Exact source digest: `4875c8e9fa33e8192ecfad792f6137d21edd625f62c57cb9a01efa423c02510b`
- Snapshot patch SHA-256: `5a8cea2becf1f04abe61bc1a9824200b7ddaf8e1addc608281267e64b438d65e` (verified against `snapshot/manifest.json`)
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T215322Z-dc84c39b40/package.json`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T215322Z-dc84c39b40/verification-plan.json`, SHA-256 `2576df79243b7ac15805882f819b5ec8697cd2ae16732b57c12425c16f5beaef`
- Previous reviewed snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T214600Z-85eaf2cc8b/snapshot`
- Exact delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260910T215322Z-dc84c39b40/delta.patch`
- Verdict: `APPROVED`

### Prior findings disposition

1. **Pagination semantics — resolved.** `app/YiiRuntime/Views/installers.php:37` now gives the active pagination link `aria-current="page"` while retaining its presentation hook. The HTTP test scopes the one-current-page assertion to `nav.fm2-pagination` and separately asserts the sidebar marker; the browser test observes page 1 and page 2 current markers across real keyboard navigation. The root-authored test deltas have independent `TEST_HELPER_DELTA APPROVED` and `TEST_HELPER_DELTA APPROVED` obsolete-global-current follow-up records in `reviews/tests/YII2-INSTALLER-DIRECTORY-001.md`. Their recorded SHA-256 values match the reviewed v2 files: HTTP `c83110775f48cb58bcb920de38862950730070e7bdc00501c0ed4dc83ab2f604`, browser `b5cd8e440d53be875ea333ab7be7b06cf40f080086c7299a93dedbbce56ca3ee`.

2. **Stopped full-run inventory — resolved.** `docs/operations/yii2-installer-directory-delivery-2026-09-10.md` now records that the directly launched parent had no harness record and ended by root SIGINT/exit 130, enumerates all child record IDs classified UNKNOWN after source drift, enumerates the no-record tests affected by the temporary unreadable test-owned artifact, records the artifact path and teardown disposition, and explicitly retains the run and all UNKNOWN outcomes as non-GREEN pending the planned authoritative CI.

### Verification evidence

- `php tests/Yii2/yii2_installer_directory_http_001_test.php` — exact-source GREEN, record `1789077114100784000-a48ecb2eafcc4542ba859757e3696f15`
- `php tests/Yii2/yii2_installer_directory_browser_001_test.php` — exact-source GREEN, record `1789077122122621000-159a2d5054604cf88a0d168933aecd1e`

Both records start and finish on source `4875c8e9fa33e8192ecfad792f6137d21edd625f62c57cb9a01efa423c02510b` without source drift. The complete v1-to-v2 delta contains only the pagination production correction, the independently reviewed test corrections, the expanded delivery inventory, and append-only Gate 3/Gate 5 review records. The unchanged complete candidate was rechecked through the exact package for specification conformance, authorization, closed query behavior, privacy and sanitized failures, read-only facts, assignment/version/date semantics, stable ordering, bounded real-HTTP queries, runtime closure, responsive/browser behavior, verification inventory and architecture boundaries.

### Findings

None.

### Remaining delivery state

Gate 5 is approved for exact source `4875c8e9fa33e8192ecfad792f6137d21edd625f62c57cb9a01efa423c02510b`. CI, PR, deployment and stand cutover remain `UNKNOWN` and are not approved or implied by this verdict. One authoritative full exact-source CI run remains required before production integration can be declared complete.
