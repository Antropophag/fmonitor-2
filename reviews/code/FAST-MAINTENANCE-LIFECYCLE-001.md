# Code review: FAST-MAINTENANCE-LIFECYCLE-001

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/final_review`
- Implementation author: separate executor; reviewer authored none of the reviewed specification, tests, implementation, Gate 3 record, or evidence
- Reviewed source: base `fa1dfa8dba9b20eeaa1c072c5c351acada29b615` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T005739Z-2a975b4f58/snapshot`, patch SHA-256 `44d9d0255a313e1b289bdb94f8bb0416a8500ed9fbc1a081352874506d1c7bf4`, candidate source `564ae33ce35d2e8998950e55081cf41082b53e8f2f6cb6bb4e87d3999896bf8d`
- Agreed review scope: issue #162 / T06 only; public prepare/state/executor routing, fail-closed reasons, requirement path and digest freshness, unchanged classifier and STANDARD/CRITICAL behavior, test sensitivity, historical measurement, compatibility, and positive executor RED
- Specification: `specs/FAST-MAINTENANCE-LIFECYCLE-001.md`; OpenSpec `minimal-fast-maintenance-lifecycle`
- Approved test review: `reviews/tests/FAST-MAINTENANCE-LIFECYCLE-001.md`, controlling Gate 3 verdict `APPROVED`
- Verification plan: SHA-256 `457da5612060d06db2bbc9717d0497bc081aad4e793f1ff36ec81328dbe69fc1`, lane `CRITICAL`, required reviews `gate3,final`
- Verification evidence: exact-source GREEN records `1789520185196923000-6143b228b2a241e09c62f1739c425042` and `1789520225964402000-dff3a7aecb464855a84ed05706196411`; reviewer also reran `python3 tests/Delivery/fast_maintenance_lifecycle_162_test.py` GREEN
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — executor admission accepts an intended RED from a different mapped test than the declared executable regression.** In `tools/delivery/harness_context.py:1005-1018`, lifecycle RED status is computed with `any(item.get("outcome") == "INTENDED_RED" for item in evidence)`. `tools/delivery/harness_context.py:1053-1068` validates evidence against the plan but never binds that RED evidence to `lifecycle.executable_regression`; complete mapped coverage is required only for reviewers at lines 1069-1073. A FAST plan with multiple mapped tests can therefore supply intended RED for another acceptance test and admit an executor while the declared existing regression was never RED. This violates acceptance 5 and example B, which require executable intended RED for the maintenance regression. Bind the admitted RED record to the normalized argv/path of `executable_regression` and add a negative multi-test witness plus the existing positive executor witness.

2. **HIGH — canonical requirement path containment can be escaped through a symlinked parent.** `_safe_repo_path` in `tools/delivery/harness_context.py:42-48` rejects absolute paths and `..` only lexically. Eligibility at lines 982-1000 and freshness at lines 327-345 reject a symlink only when the final path itself is a symlink. `specs/link/requirement.md`, where `specs/link` points outside the checkout, is therefore accepted as a regular canonical file and its external bytes are digested. This violates the regular repository-file boundary in acceptance 1 and the repository-relative path guarantee. Resolve root and candidate, prove containment, reject symlinks in every path component, and cover both prepare eligibility and later state freshness.

3. **MEDIUM — case M reports an incomplete after-set and therefore understates mandatory lifecycle artifacts/bytes.** The normative example at `specs/FAST-MAINTENANCE-LIFECYCLE-001.md:77-85` defines the after evidence set as verification input/compact record, regression, final record, and delivery closeout. `tests/Delivery/fast_maintenance_lifecycle_162_test.py:324-340` measures only serialized lifecycle plus a synthetic final-review record, then reports `9 -> 2`; `docs/operations/issue-162-fast-maintenance-delivery.md` repeats that count. It omits the verification input, regression, and delivery closeout bytes from the after measurement (and the regression from the before set), so the requested before/after count and size comparison is not the one specified. Measure the complete exact fixture sets, while separately reporting which artifacts are newly created versus reused.

The remaining reviewed behavior is in scope and consistent: planner authority is preserved; deterministic non-FAST/semantic/sensitive/conflict reasons fail closed; the T05.1 classifier and #107/standard-critical policy are unchanged; state detects ordinary digest drift; legacy OpenSpec status/strict validation remains exercised; and the positive single-regression executor path succeeds with exact-source RED evidence. No additional standards or scope-creep findings were identified.

## Required changes

- Bind intended RED admission to the declared executable regression and add the multi-test sensitivity case.
- Enforce real repository containment across all canonical requirement path components in prepare and freshness checks, with symlink-parent regressions.
- Correct case M and its delivery-record measurement to include the complete normative before/after evidence sets.
- Rebuild the exact-source plan/package and obtain a fresh independent final rereview. Because the corrections change executable tests, follow the repository rule for plan recomputation and any resulting Gate 2/Gate 3 requirements.

CI, PR, deployment, and enforcement remain `UNKNOWN`; this review grants no publication, merge, deployment, or settings authorization.

---

## Correction rereview — package `20260916T010717Z-cb77c976b1`

- Corrected candidate source: `ddf8f7a1a2e8c3ad4a52c9c4f23172d6f610687d2668598170585fcf706c1e46`; executable source `14ccbecabc2685e8b12234e9854db88df25ac81ee15fe7b86aea3465b8473b0d`.
- Base: `fa1dfa8dba9b20eeaa1c072c5c351acada29b615`; retained snapshot patch SHA-256 `abf987a175298c9a3e02186d16a7aecf59b6ca51d75a169248da2baa75d0182a`.
- Verification plan SHA-256: `e0f84b4b30cdaddd7e9d1ccd5a37919e2909ed86d75efc0eec8e40f951399183`; lane `CRITICAL`, required reviews `gate3,final`.
- Approved Gate 3 correction-delta review: package `20260916T010314Z-db67146c47`, exact intended RED `1789520570098602000-18b2c71c008c4752822e6014a63302fb`.
- Exact-source GREEN evidence: `1789520772735019000-223d05f3976d43a4b6f03aa4b17b1821` and `1789520807251706000-c1b531d8b42f48ef95a2cb7f51bd1736`; both report source drift `false`. Reviewer independently reran the 11-case T06 suite GREEN.
- Scope: disposition of the three findings above and regression risk introduced by their bounded correction.

### Prior-finding disposition

1. **Resolved.** `tools/delivery/harness_context.py:1020-1022` now marks RED only when an `INTENDED_RED` evidence argv terminates in the lifecycle-declared regression path. The public executor test first admits evidence from the declared regression, then supplies independently retained RED from a second mapped test and requires rejection with the declared-regression diagnostic. This prevents an unrelated mapped RED from satisfying executor admission.

2. **Resolved.** `_safe_canonical_path` walks every repository-relative component and rejects any symlink before resolving and proving containment beneath the real checkout root. Both prepare and freshness use this function. The executable cases reject an outside target reached through `specs/link` and mark an existing binding stale after its formerly regular parent is replaced by an outside symlink.

3. **Resolved.** Case M now accounts for the complete participating evidence sets and separately reports mandatory newly created artifacts: before `10` total / `9` created / `71126` bytes / `50529` decoded characters; after `4` total / `2` created / `12571` bytes / `12254` decoded characters. Both sides include the reused regression; after also includes compact input/record, final review, and closeout projection. Review dispatches remain `2 -> 1`, phase stops `1 -> 0`, quality guarantees remain derived from the package, and token telemetry remains `UNKNOWN`. The exact report is retained in the focused GREEN evidence.

### Remaining findings

None.

The correction is limited to path containment and RED-evidence identity plus their reviewed tests/measurement. It does not expand the FAST classifier, alter #107, product code, or STANDARD/CRITICAL lifecycle, and preserves the previously reviewed deterministic routing and OpenSpec compatibility.

### Correction verdict

`APPROVED`

Gate 5 approves exact candidate source `ddf8f7a1a2e8c3ad4a52c9c4f23172d6f610687d2668598170585fcf706c1e46` for issue #162/T06. This approval does not make CI, PR, deployment, or enforcement GREEN and does not authorize merge, deployment, or settings changes. Exact-source GitHub CI and compact closeout remain subsequent delivery steps.

---

## Closeout-metadata delta rereview — package `20260916T011028Z-84a3b9e743`

- Exact candidate source: `e7fba36f9539e84bcb59bd2be67d9179c607fb2bcc93e94e60a04fca4d64b8da`; executable source `7320fdd1ff58429fc2428f6a5c7e0862c16da32f97066e6b714f7b17d12456a8`.
- Retained snapshot patch SHA-256: `e5e8f349d297ecbc846d387542b24d520001de5202cbbdbba92395bce4136af2`; previous snapshot is package `20260916T010717Z-cb77c976b1`.
- Exact-source GREEN evidence: `1789520978845536000-afc065a3787a4bc2b3a69b22dafcf3bc` and `1789520997954953000-b9673e248c5b4f0c98ef820af1dd4a53`; both report source drift `false`.

### Assessment

No findings.

The delta contains only the append-only prior Gate 5 correction review, corrected historical measurement and current status in `docs/operations/issue-162-fast-maintenance-delivery.md`, and completion of OpenSpec tasks 3.1/3.2. It changes no normative specification, executable test, planner/classifier, harness behavior, product code, STANDARD/CRITICAL lifecycle, or #107 admission behavior. The delivery record continues to state exact-source CI/PR as `PENDING` and merge/deployment/settings as unauthorized; tasks 3.3/3.4 remain unchecked. The metadata is consistent with the approved source and retained evidence.

### Verdict

`APPROVED`

Gate 5 approves the metadata-only delta and exact candidate source `e7fba36f9539e84bcb59bd2be67d9179c607fb2bcc93e94e60a04fca4d64b8da`. Exact-source GitHub CI and PR preparation remain subsequent steps; this verdict does not authorize merge, deployment, or settings changes.

---

## Exact-source CI portability correction — final delta review

- Fixed point: commit `69b9e55d4a27d9816dd320ef9d565bb96785df64`.
- Reviewed delta before this append: `tests/Delivery/fast_maintenance_lifecycle_162_test.py` plus the independent Gate 3 append in `reviews/tests/FAST-MAINTENANCE-LIFECYCLE-001.md`; binary diff SHA-256 `53083d4ff7a3c8a44f53943bfb4e554820a1ba9cb654c8d04117c67b6074fed5`.
- Corrected test SHA-256: `0d429aec4f0ab797449b1661a9df19edf82a3454c1cce0feea3b25c2c6c82e62`.
- CI evidence reviewed: Quality Graph run `35043123501`, exact head `69b9e55d4a27d9816dd320ef9d565bb96785df64`.
- Focused reviewer rerun: `python3 tests/Delivery/fast_maintenance_lifecycle_162_test.py` — 11/11 GREEN; historical measurement unchanged.

### CI inventory and root cause

The complete run inventory shows `plan`, `fast`, `unit`, both integration shards, `e2e`, and `quality-results` GREEN. `governance` failed, `verify` then failed as the expected aggregate consequence, and the terminal Quality Graph check reflected that failure. The sole primary `REGRESSION_FAILURE` line is `tests/Delivery/fast_maintenance_lifecycle_162_test.py`.

The failing test's 14 assertions consistently observed a CRITICAL/`planner_not_fast` disposable plan instead of its intended FAST plan. The isolated fixture copied and imported Python delivery modules before asking Git/planner for authoritative changed paths; on the runner those imports created unignored `tools/delivery/__pycache__` bytecode paths, which correctly matched the delivery-policy boundary and escalated the fixture. This is environment-dependent fixture contamination, not a product, planner, classifier, or lifecycle implementation failure.

### Assessment

No findings.

The correction adds only conventional `__pycache__/` and `*.py[cod]` ignores to the disposable repository's committed `.gitignore`, alongside its existing fixture-only `/change.json` ignore. It cannot hide Python source changes or other delivery-policy files, and it mirrors the root repository and existing verification fixtures. The ignore file is committed before the fixture base, so it does not itself alter the candidate changed-path set. The Gate 3 append accurately records the bounded correction and keeps failed CI distinct from approval.

There is no normative spec, harness implementation, planner/classifier, STANDARD/CRITICAL lifecycle, #107 admission, product-code, or historical-measurement change. The focused 11/11 result confirms the intended A–N behavior under explicit repository-local ignore semantics; it does not replace corrected exact-source CI.

### Verdict

`APPROVED`

Gate 5 approves this test-fixture portability delta. Corrected exact-source CI remains required, and the failed run `35043123501` remains historical failure evidence. This verdict authorizes neither commit/push by the reviewer, merge, deployment, settings changes, nor publication before matching GREEN CI.

---

## Second exact-source CI portability correction — final delta review

- Fixed point: commit `d590e9c8c8485cf94ee0a50fcc488b2351e54e1d`.
- Reviewed delta before this append: `tests/Delivery/fast_maintenance_lifecycle_162_test.py` and the append-only Gate 3 review; binary diff SHA-256 `0725ca9704e2152a2c5d45be95d37d4e882889f033ed3b0f9e32504e3dc61ec3`.
- Corrected test SHA-256: `ec33210189d506dda5dbfd794412bce5c4bcf33d5530be83d55f761272aadd81`.
- CI evidence reviewed: Quality Graph run `35044404987`, exact head `d590e9c8c8485cf94ee0a50fcc488b2351e54e1d`.
- Controlling Gate 3 verdict: `APPROVED`; its intermediate incorrect-path finding is explicitly resolved and superseded in the same append.

### CI inventory and root cause

The complete run inventory shows `plan`, `fast`, `unit`, `e2e`, both integration shards, and `quality-results` GREEN. `governance` had the sole primary `REGRESSION_FAILURE`, `tests/Delivery/fast_maintenance_lifecycle_162_test.py`; `verify` and the terminal Quality Graph failure were aggregate consequences. Its only failing case was N, which raised `FileNotFoundError` while launching the external `openspec` executable absent from the runner.

### Assessment

No findings.

Case N still unconditionally proves the public legacy input routes `OPENSPEC_REQUIRED` with the exact normal-route reason and that the complete five-artifact OpenSpec change set exists: proposal, design, tasks, verification input, and the correctly located delta spec. Only invocation of an unavailable external CLI is skipped. When `openspec` is present, the test still requires real `status --json`, schema `spec-driven`, and strict validation success.

Reviewer verification is GREEN in both environments:

- ordinary `PATH`: focused suite 11/11, including real OpenSpec status/strict validation;
- `PATH=/usr/bin:/bin`: focused suite 11/11, exercising the absent-CLI branch after all routing/artifact assertions;
- standalone `openspec validate minimal-fast-maintenance-lifecycle --strict`: GREEN.

The historical measurement is unchanged. The correction neither weakens `OPENSPEC_REQUIRED`, removes lifecycle artifacts, nor changes a normative spec, harness implementation, planner/classifier, STANDARD/CRITICAL behavior, #107 admission, or product code.

### Verdict

`APPROVED`

Gate 5 approves this bounded test-portability correction. Corrected exact-source CI remains required, and run `35044404987` remains retained failure evidence. This verdict authorizes neither implementation beyond the reviewed delta, commit/push by the reviewer, merge, deployment, settings changes, nor publication before matching GREEN CI.
