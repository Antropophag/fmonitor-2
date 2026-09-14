# Independent Gate 3 test review — YII2-LOCAL-DATA-BOOTSTRAP-001

- Reviewer: separately tasked agent `/root/gate3_review`; authored none of the reviewed specification, verification input, or test.
- Review date: 2026-09-14.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T153122Z-f58f783792/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `44880bbe4df579789a094fda526e6a4415598b29`, harness source `9f7078cfb1d0647984d90d7edf8937c9428db038034663a341c990ce184fcb48`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T153122Z-f58f783792/snapshot/source.patch`, SHA-256 `d63b46e3c122064568770e7b1563f9f2202c7cceac9248339e73ba62852b8b75`.
- Verification plan SHA-256: `7f066d80b838249b37830609581227134a837f23e8f4f51c20cd272aea66bb15`.

## Complete findings

1. **CRITICAL — the single lexical test does not exercise the declared public seams or nearly all A1–A4 behavior.** Locations: `specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md:17-52`; `tests/Deployment/yii2_local_data_bootstrap_001_test.py:5-29`; verification input acceptance `A1-A4`. The test parses Makefile text and checks file/token presence. It never invokes `make import-legacy`, `make sync-workforce`, `make up-with-data`, or `php bin/yii legacy-import/run --interactive=0`. Consequently an implementation can pass while commands execute twice, ignore exit codes, print success early, contact production during `make up`, return the wrong stable status/output, mutate partial state, or fail to resume safely. Correction: add isolated subprocess fixtures for every public seam, with command/network/DB event witnesses, exact exit/output assertions, ordered invocation counts, fail-fast cases at each stage, no premature success, and before/after durable inventories. Keep lexical checks only as supplemental architecture evidence.

2. **CRITICAL — the native legacy-import data contract has no behavioral acceptance coverage.** Locations: contract A3 (`specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md:34-45`); test lines 25–29. File existence, route registration, and absence of one literal cannot detect write access to legacy MariaDB, multiple/inconsistent cutoffs, wrong template or eligibility selection, omitted object details/associations, non-idempotent replay, incompatible non-empty generation, partial publication, or UNKNOWN being reported as success. Correction: run the native Yii command against deterministic isolated source/target fixtures and independently assert the complete source read-only witness, one explicit cutoff across all reads, imported and excluded rows/relations, unchanged repeat, incompatible generation, stage faults, commit-uncertainty reconciliation, terminal results, and complete target facts. Bind the established rapid oracle explicitly as retained characterization evidence if it owns these expected values.

3. **HIGH — private configuration safety and secret containment are entirely untested.** Locations: contract A2 (`specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md:26-32`); test lines 19–22. String presence in recipes does not prove exclusive config sourcing, regular-file/non-symlink enforcement, exact mode `0600`, validation before any network/DB effect, format grammar, argv redaction, terminal redaction, or Compose environment-dump exclusion. Correction: cover missing, directory, symlink, permissive-mode, malformed, and valid files for both sources; use distinct secret canaries and access/network/DB sentinels; inspect argv, stdout/stderr, and rendered Compose configuration. Assert rejection precedes effects and that no alternate environment source overrides the private files.

4. **HIGH — workforce failure/resume and cross-stage append-only preservation are not covered by the mapped existing workforce tests.** Locations: contract A4 (`specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md:47-52`); test lines 21–24; verification input `A1-A4`. The plan contains existing case-import/workforce checks, but the sole acceptance mapping for this specification names only this lexical test. It does not run workforce through the Make seam with the prescribed private file, nor prove unavailable legacy/Bitrix/target handling, preservation of facts committed by preceding stages, or safe continuation after each failure boundary. Correction: add Make-level cases for all three dependency failures and reruns, observing exact stage calls and complete durable facts; retain and explicitly map the approved workforce oracle rather than treating unrelated planned checks as implicit coverage.

5. **HIGH — the production closure assertion is neither transitive nor artifact/runtime sensitive.** Locations: contract A3 lines 44–45; test lines 19, 21, and 28. Searching only two prospective PHP files and two Make recipes for `rapid-pilot` passes if a helper/autoloader, built image, Compose service, or runtime-loaded dependency contains or loads it. It also does not prove the native command exists inside the canonical built image. Correction: inspect the built production image inventory, execute the closed command in that image, capture the loaded-file closure, and reject `rapid-pilot` transitively across command/runtime/Make composition while retaining the oracle outside production.

6. **HIGH — the normative contract itself leaves acceptance-changing details unresolved.** Locations: A2–A3. It does not define the accepted grammar/keys of `.local/legacy-source.env` or `.local/bitrix-workforce.json`, who selects and persists the cutoff (or how a repeat obtains “the same cutoff”), the stable non-zero/result vocabulary, the definition/source of `eligible`, `applicable checklist template`, `incompatible/non-empty generation`, `partial`, and `UNKNOWN`, or the precise success observable and durable bootstrap/run facts. Without those choices, independent expected results cannot be derived and materially different implementations can all claim conformance. Correction: return to Gate 1 and either specify these outcomes and worked examples directly or cite the stable inherited contracts that define them; enumerate exact rejection outcomes and persisted/no-new-fact expectations.

## Traceability, determinism, sensitivity, and RED evidence

The test cites the specification identifier and is deterministic as a source inspection, but it does not use the confirmed public seam and is insensitive to plausible behavioral regressions. The verification input collapses four materially different acceptance groups into one test mapping, which overstates coverage; the generated plan reporting no `missing_tests` is not acceptance approval.

I independently ran `python3 tests/Deployment/yii2_local_data_bootstrap_001_test.py` at the reviewed source. It exited non-zero with `AssertionError: MISSING_TARGET:import-legacy`, an intended missing-implementation RED rather than a setup failure. No retained source-bound RED record was supplied in the package (`evidence` is empty), and no full local suite was run. CI and deployment remain `UNKNOWN`.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gate 1 for the unresolved contract vocabulary and Gate 2 for a behavioral, public-seam acceptance matrix. Capture fresh exact-source RED evidence and submit one complete corrected package for independent rereview.

---

## Correction Gate 3 rereview — 2026-09-14

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T153533Z-7b41b391ab/package.json`.
- Exact corrected source: reconstructible snapshot over base `44880bbe4df579789a094fda526e6a4415598b29`, harness source `f20b22d940522c4e6598d590de5e5f19682d1aea1badbc073c36209916b1a94b`.
- Snapshot patch SHA-256: `361c2e9e79f2bfa018916d223c682e2ca315ed98497a02f58e0aeaa1d35df1c3`.
- Corrected verification plan SHA-256: `63ef90d55a4302a641b3f76d0cfb40caa2b36ddffefd1ae8071052c42b6dc396`.
- Independence is unchanged; this reviewer authored none of the corrected contract, test, verification input, or implementation.

### Resolution assessment

The correction partially resolves prior finding 6 by defining private-file keys, cutoff format/journaling, terminal result codes, counts, and inherited fact contracts. It partially resolves finding 3 by testing valid files plus missing/directory/symlink/mode rejection with terminal secret canaries. The verification input now names workforce, production-image, and schema controls rather than leaving them wholly implicit.

### Remaining complete findings

1. **CRITICAL — findings 1 and 4 remain: no Make public seam is executed.** `tests/Deployment/yii2_local_data_bootstrap_001_test.py:7-30` still performs only Makefile source parsing. Therefore `make import-legacy`, `make sync-workforce`, and `make up-with-data` may invoke commands twice, ignore failures, run out of order, print success early, read production from `make up`, fail to pass the private files, lose preceding facts, or fail resumably while every assertion passes. The validator subprocess at lines 32–55 is not any declared public seam. Add isolated Make subprocess executions with command/effect witnesses, exact counts/order/terminal outcomes, a failure at each stage, no later stage after failure, before/after durable facts, and successful retry.

2. **CRITICAL — finding 2 remains: mapped schema tests do not exercise native legacy import facts.** The A3 mapping names `classification_provenance_schema_001_test.php`, `checklist_template_schema_001_test.php`, and `object_detail_snapshot_schema_001_test.php`. These establish schema/frontier contracts, not that `php bin/yii legacy-import/run --interactive=0` reads the source read-only, fixes/reuses one cutoff, selects only eligible unopened objects, imports correct template/details/associations atomically, reports independent counts, or handles replay/incompatible/partial/UNKNOWN correctly. The local test still checks only prospective file/route presence. Add a deterministic source/target DB oracle through the native command, or explicitly redirect an existing complete import oracle through that seam, covering every stated outcome and durable run/fact inventory.

3. **HIGH — finding 3 remains materially open despite the metadata matrix.** The test accepts one valid example and rejects only missing, directory, symlink, and mode `0644`. It does not test exact legacy key set, missing/extra/duplicate keys, empty scalar boundaries, port bounds, cutoff grammar, malformed JSON, URL/id/token/department boundaries, or rejection before DB/network effects. It inspects only validator stdout/stderr, not command argv or rendered Compose environment as the contract requires. Add table-driven grammar boundaries, access/network/DB sentinels, and argv/Compose-dump secret-canary assertions through the Make commands.

4. **HIGH — finding 5 remains for the new legacy command.** Mapping the existing workforce package and production-image cleanup controls does not establish the built-image presence, transitive loaded-file closure, or closed execution of the new `legacy-import/run` path. The local test still searches only `LegacyImportController.php` and `LegacyImportConsole.php` for a literal. Extend artifact and runtime-load checks specifically to the new command and its Make/Compose execution.

### Evidence and verdict

I independently reproduced the corrected test's intended RED: `python3 tests/Deployment/yii2_local_data_bootstrap_001_test.py` exits non-zero at `MISSING_TARGET:import-legacy`, not at fixture setup. The correction package still contains no retained evidence records (`evidence: []`). CI and deployment remain `UNKNOWN`; no local full suite was run.

`CHANGES_REQUESTED`

Gate 4 remains blocked on the four bounded gaps above. The corrected Gate 1 definitions should be retained; return to Gate 2 for actual Make/native-command behavior and command-specific production-closure coverage, then capture fresh source-bound RED and resubmit the complete package.

---

## Final correction Gate 3 rereview — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T154734Z-9e1dff6945/package.json`.
- Exact source: base `44880bbe4df579789a094fda526e6a4415598b29` plus snapshot patch SHA-256 `60ff6450756eeaaeb8a77aea8adbd80557bf7635b3d6db797315200665ad3951`; harness source `1595d1b4d271d12016defecaf14f427dfc530adcb632b4c38f6d30a328e29682`.
- Verification plan SHA-256: `217deeed86b2a8f31990e7011efd09d7a1a1ad02c183c2e8024305501e78946b`.

The correction materially closes the basic public-seam gap: a hermetic fake-command fixture now invokes all three Make targets and observes happy-path ordering and stop-on-failure, while an isolated MariaDB fixture invokes the native legacy entrypoint using a SELECT-only source user and observes eligible/opened selection, detail/template/association facts, repeat stability, source-secret redaction, and source write prevention.

### Remaining complete findings

1. **HIGH — A3's explicit terminal/failure outcomes and cutoff journal are still untested.** `tests/Yii2/yii2_legacy_import_db_001_test.php:31-38` covers only success and a repeat. It does not assert the promised result counts, completed durable run and fixed/reused cutoff, nor any `69`, `78`, `2`, `70`, or `75` outcome. In particular incompatible/non-empty generation, partial publication, and UNKNOWN-as-non-success can regress while the complete suite passes. The mapped schema tests do not exercise these runtime outcomes, and the established case-import oracle is not mapped as the owner of this new import-run/cutoff behavior. Add durable run/cutoff/count assertions plus deterministic source/target unavailable, incompatible generation, stage-failure atomicity, and unknown-commit cases with exact closed terminal results.

2. **HIGH — Make fail-fast sensitivity is incomplete.** `tests/Deployment/yii2_local_data_bootstrap_make_001_test.py:52-58` asserts only relative order on success, not exactly one invocation of every combined stage. On failures it checks absence of later stages but never requires all earlier stages and the failing stage to have run exactly once; the workforce-failure case has no forbidden stages and can pass even if orchestration skips every stage and fails for an unrelated reason. It also does not execute a retry or observe preservation of already proven facts as A4 requires. Assert the exact stage list for success and each failure, then rerun after each injected failure and compare a deterministic fact journal.

3. **HIGH — A2 grammar and containment remain under-sensitive.** `tests/Deployment/yii2_local_data_bootstrap_001_test.py:34-55` still has no malformed/key/boundary matrix for either file and no pre-network/DB sentinel. `yii2_local_data_bootstrap_make_001_test.py:59-61` checks terminal output only; its fake `docker compose config` emits nothing, so it cannot detect secrets in a real rendered Compose environment dump or argv. Add the exact-key/scalar/cutoff/JSON/URL/department rejection matrix, effect sentinels, and inspect every traced argv plus a representative rendered-config output for all canaries.

4. **MEDIUM — new legacy command artifact/load closure remains unobserved.** The package maps existing production image cleanup and workforce package tests, but neither executes `legacy-import/run` inside the built canonical image or captures its included-file closure. The new structural check remains limited to two source files. Add the same built-artifact and transitive loaded-file witness specifically for legacy import.

### RED evidence and verdict

I independently reproduced both new intended REDs. The Make test exits `1` when `import-legacy` is absent. The DB test successfully creates/migrates its isolated databases and then exits `255` because `FMonitor2\\YiiRuntime\\LegacyImportConsole` is absent; this is the intended implementation gap, not DB setup failure. The package still declares no retained evidence records. No full local suite was run; CI and deployment remain `UNKNOWN`.

`CHANGES_REQUESTED`

Gate 4 remains blocked on these four bounded sensitivity gaps.

---

## Narrowed-scope Gate 3 rereview — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T155149Z-39354cbe94/package.json`.
- Exact source: base `44880bbe4df579789a094fda526e6a4415598b29`, snapshot patch SHA-256 `9f3c8a3c3b9b694bf4fad558e6544d131174786228282127a542868b5fa67088`, harness source `1b01de26c4fe0510cea7a73a7f002eb97a69d1eaddce255535978b2f7d61b136`.
- Verification plan SHA-256: `a1615bbe13b12410d3dd5b8fab8f5fdbbfc88b288f24da96ab9aab6b2c2a6704`.

The contract's narrowing is coherent: per-invocation cutoff, generic fail-closed terminal handling, and owner-inherited idempotence remove the prior demand for an exhaustive transport-code/journal/UNKNOWN matrix. The Make correction now proves exact happy/failure stage sequences and successful retry. The package test now covers the native legacy route in source load tracing, built artifact inventory, and built-image closed execution. Those prior findings are resolved.

### Remaining complete findings

1. **HIGH — two explicit A3 success observables remain untested.** `specs/YII2-LOCAL-DATA-BOOTSTRAP-001.md:52-55` requires one cutoff to be used across every source read and the success JSON to contain `eligible/imported/alreadyPresent/details/templateAssociations` counts. `tests/Yii2/yii2_legacy_import_db_001_test.php:32-38` checks only the result name and table totals; it never asserts any returned count key/value and does not make inconsistent or missing cutoff use observable. An implementation can omit/fabricate the result counts or query different source frontiers while passing. Add exact independent JSON count assertions and a fixture/witness with rows straddling the cutoff (or an injected clock/query observer) proving one computed/provided cutoff governs all source reads.

2. **HIGH — the exact legacy configuration grammar is still incompletely covered.** `tests/Deployment/yii2_local_integration_config_001_test.py:16-21` tests port `0`/`65536`, bad cutoff, one extra key, and one missing key, but not the contract's nonempty host/name/user/password requirements, nonnumeric/noncanonical ports, duplicate keys, or empty-cutoff positive case. A validator accepting these invalid values can pass. Add table-driven cases for every nonempty scalar, representative nonnumeric/noncanonical ports, duplicate keys, and valid empty cutoff. Existing Bitrix-owner tests may remain the inherited detailed Bitrix grammar evidence.

3. **HIGH — the explicit argv and Compose-environment-dump redaction outcomes are not asserted.** `tests/Deployment/yii2_local_data_bootstrap_make_001_test.py:64-66` checks only captured stdout/stderr; although the fake docker records argv, the test never searches traced argv for canaries. Its fake `docker compose config` emits nothing, so it cannot detect secrets in the rendered Compose dump required by A2. Add canary checks over every recorded argv and a representative real or faithful rendered-config witness demonstrating that neither private integration secret is present.

### Evidence and verdict

The three intended REDs independently reproduce at the correct missing seams: Make target execution exits `1`, private validator presence exits `1`, and the MariaDB-backed native command exits `255` for absent `LegacyImportConsole` after fixture database setup succeeds. No full local suite was run. Package retained evidence remains empty; CI and deployment are `UNKNOWN`.

`CHANGES_REQUESTED`

Gate 4 remains blocked only on the three focused assertions above.

---

## Gate 5-finding correction Gate 3 review — APPROVED, 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T162012Z-9aa0cd6180/package.json`.
- Exact source: base `44880bbe4df579789a094fda526e6a4415598b29`, snapshot patch SHA-256 `c5de4c8cf30e7726fb11916c0e5ba7def74b7219c8774ba6af1c02da40f18fd1`, harness source `7469cef37fec4cb81b16e5eda8078e02c75dd30f601a6641b8aea71c617f59a7`.
- Verification plan SHA-256: `e2331dc24cb6aa9ba1a9c28b67a1033e8d7bf5368908671594b04e9ac0fbf3b8`.
- Review scope: root-authored correction delta in `tests/Yii2/yii2_legacy_import_db_001_test.php`; production code was inspected only to confirm RED sensitivity and was not edited.

### Complete findings

None.

The classification differential is independently determined from the inherited eligibility contract: two otherwise equivalent unopened candidates are accepted, while separate rows are excluded for an existing actual-start fact, positive progress, `workstarted`, PTO evidence, finished status, and missing identity. Asserting the exact imported legacy-ID set makes each exclusion observable without depending on the implementation's query structure.

The second eligible object plus a target trigger provides a deterministic mid-import failure after earlier writes are possible. The test requires non-success, zero rows across all five material target fact/mirror tables, removal of the trigger, and then a successful complete retry with exact facts/counts. This is sensitive to the Gate 5 atomicity defect and proves the public seam's recovery path rather than only source structure.

The immutable replay case deliberately changes the already imported target mirror while leaving source facts stable, invokes the same public command, requires non-success, and compares the complete installation-case rows before/after. It will fail if replay silently overwrites or appends case facts. The expectations are fixture-derived and deterministic; random names isolate databases/users without affecting expected values.

I independently reproduced the intended RED after successful database creation and migration: the injected detail failure expected zero `fm2_fm2_installation_cases`, but current production left `3`; command exited `255`. This is the precise missing atomicity behavior, not setup failure. No local full suite was run. Package evidence remains empty, and CI/deployment remain `UNKNOWN`.

### Verdict

`APPROVED`

Correction Gate 3 passes for exact test source `7469cef37fec4cb81b16e5eda8078e02c75dd30f601a6641b8aea71c617f59a7`. Implementation may address the demonstrated production gap. Any change to these expectations requires another Gate 2/3 review.

---

## Planning-eligibility correction Gate 3 — APPROVED, 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T162911Z-decee1432a/package.json`.
- Exact source: base `44880bbe4df579789a094fda526e6a4415598b29`, snapshot patch SHA-256 `3e384a43e214390770a8a77cbeb79a42e99a2534a135a31bef29f85eca07d3bf`, harness source `601a94e49e76b0cf01f601398f0871714b0783116c89239daca2f51b68f5c0df`.
- Verification plan SHA-256: `b1c07c9be8ffa3015282617aa7ebd92dee43dcded8e2e400f999c14cf6d462c5`.

### Complete findings

None.

The three added fixtures isolate the inherited planning boundary without changing the previously approved classification controls: object `509` is otherwise eligible but falls one day before the exact `2026-10-01` boundary; object `510` lacks planned start; object `511` lacks both accepted planned-finish sources. Existing accepted objects `501` and `508` remain identical controls at the boundary with complete planning facts. Exact result counts and exact imported IDs `[501, 508]` therefore independently detect omission or weakening of each planning prerequisite, while the existing opened/progress/workstarted/PTO/finished/identity rows continue to cover their separate exclusions.

I independently reproduced the intended RED after successful database setup and the earlier injected-fault/retry sequence: `LEGACY_IMPORT_COMPLETED` reported eligible/imported/details/associations counts `5` instead of `2`. The failure is specific to the missing inherited planning filter, not fixture setup. No full local suite was run; package retained evidence remains empty and CI/deployment remain `UNKNOWN`.

### Verdict

`APPROVED`

Correction Gate 3 passes for exact test source `601a94e49e76b0cf01f601398f0871714b0783116c89239daca2f51b68f5c0df`. Implementation may correct the planning eligibility boundary; later expectation changes require a new Gate 2/3 review.

---

## Final three-gap correction rereview — APPROVED, 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T155537Z-1f5e7ac1ee/package.json`.
- Exact source: base `44880bbe4df579789a094fda526e6a4415598b29`, snapshot patch SHA-256 `fdea372e7db22a80b3c42c327c280e2511ce414978a1a6ce7f8cb78b875f4378`, harness source `cb8c216611c17348af9af54ec9077b511fd8e47ab48907f08a9f29e3d575b8ef`.
- Verification plan SHA-256: `4a696f680c6d4334176e857cc8b50233b3c8c76f51b37ac975ee5524afe109a9`.

### Finding resolution

All three remaining findings are resolved.

- The MariaDB public-seam test now requires the exact initial and repeat result counts, the supplied cutoff in the terminal result, and one distinct persisted provenance cutoff across imported facts. Together with the SELECT-only source account, eligible/opened fixture, durable fact assertions, repeat snapshot, and inherited owner contracts, this is sensitive to the narrowed A3 behavior.
- The private-config test now covers every required nonempty legacy scalar, port zero/above-range/nonnumeric/noncanonical forms, malformed cutoff, missing/extra/duplicate keys, and a valid empty cutoff, while retaining missing/file-type/symlink/mode and Bitrix grammar cases.
- The validator continuation now records its complete argv and environment and the test rejects integration and ambient secret canaries from both, while also asserting redacted rendered output. The Make and package tests retain terminal redaction and production-image/load closure.

The complete accepted matrix now exercises the declared Make targets with exact ordering/counts, fail-fast at each stage and successful retries; strict private-file validation before continuation effects; native Yii legacy import against isolated source/target databases; inherited workforce owner behavior; and source/built-image/runtime-load exclusion of `rapid-pilot`. Expected data values are independently fixed by the fixtures and cited inherited contracts. The tests are isolated from production systems and deterministic apart from intentionally isolated random database names.

Three intended REDs remain reproducible at the missing production seams: absent Make targets, absent private validator, and absent `LegacyImportConsole` after successful database fixture setup. The package contains no retained harness evidence records, so this review records the independently observed RED only; Gate 4 must capture and retain normal focused GREEN evidence later. CI and deployment remain `UNKNOWN`, and approval does not authorize deployment or treat either as GREEN.

### Verdict

`APPROVED`

Gate 3 passes for exact source `cb8c216611c17348af9af54ec9077b511fd8e47ab48907f08a9f29e3d575b8ef`. Gate 4 may proceed against this reviewed specification/test source. Any later behavioral expectation change requires a new Gate 2/3 review.
