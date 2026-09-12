# YII2-IMPORTS-WORKFORCE-001 — Gate 3 test review

- Reviewer: independent Codex reviewer `/root/gate3_imports_workforce`; authored neither the specification nor the tests
- Reviewed root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T204725Z-7b0beca9e5/package.json`
- Exact source: base commit `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T204725Z-7b0beca9e5/snapshot`, patch SHA-256 `73cd15783da0f0fedad1e0571d92e5160334cb3cd6599e43a5f941eb904a74f8`, harness source `5f1bf758ec89da708a411bf0854a4e356655aa64f2de63f0756543ff12746a28`
- Verification plan SHA-256: `b1522c063a8c1fd8a174dfb096fda322448012e3eb6e0272ee486b80b8c2011f`
- Review date: 2026-09-11

## Complete findings

1. **HIGH — the primary transport RED is a setup failure, not an intended behavior failure.** `tests/Yii2/yii2_imports_workforce_001_test.php:12-25` starts the real `bin/yii`, but the supplied checkout/snapshot has no usable `vendor/autoload.php`. Independent execution exits 255 with `Failed opening required .../vendor/autoload.php` and PHP warnings/traces in both output streams, before any missing command or adapter is reached. This directly violates Gate 2's requirement that RED fail for missing behavior rather than broken setup. Supply reproducible ignored dependencies (or a documented package-compatible setup), rerun the exact test, and retain evidence showing the first failure is the absent import/workforce route/service rather than bootstrap.

2. **HIGH — A2–A4 do not exercise the declared new public Yii seams or prove parity.** `tests/Yii2/yii2_case_import_db_001_test.php:5-9`, `tests/Yii2/yii2_snapshot_import_db_001_test.php:5-9`, and `tests/Yii2/yii2_workforce_sync_db_001_test.php:5-10` only inspect source strings and then run the existing InstallationProcess oracle directly. They never invoke `case-import/run`, `snapshot-import/run`, or `workforce-sync/run` with synthetic DB/delivery fixtures and independently observe their durable facts. Therefore a controller/adapter that drops inputs, maps the result incorrectly, opens another connection, invokes an owner twice, breaks retry/reconciliation/atomicity/lock/history behavior, or fails token cleanup can pass. Add deterministic behavioral tests through each new public seam for every A2–A4 acceptance family, with independent persisted/file/delivery observations and the existing owners as the expected-value oracle rather than as a disconnected subprocess.

3. **HIGH — the required closed transport and security matrix is materially incomplete.** `tests/Yii2/yii2_imports_workforce_001_test.php:17-29` covers three dependency failures and only four invalid argument examples. It does not sensitively cover exact route placement, mandatory final `--interactive=0`, unknown options, abbreviations, duplicate/reordered options, case-import 1/100/101 cardinality and canonical positive-int64 boundaries, snapshot non-empty path/lowercase digest grammar, the complete required DB/prefix/port environment matrix, mixed/partial Bitrix configuration, generic throwable mappings, or Bitrix/snapshot/exception/SQL redaction. Several A1/A6 implementations that accept forbidden syntax or leak protected values would pass. Add a table-driven subprocess matrix that checks exact exit/stdout/stderr and no pre-validation DB/file/external facts for each boundary and secret class.

4. **HIGH — compatibility alias equivalence is not behaviorally tested.** `tests/Yii2/yii2_imports_workforce_ownership_001_test.php:6-12` proves only that certain strings are present/absent. It never runs direct and retained alias forms against the same fixtures and compares stdout, stderr, exit status, owner invocation count, and durable outcome, despite A5 and A6 explicitly requiring direct/alias parity. Add paired behavioral invocations for success and representative rejection/failure/replay cases for all three aliases.

5. **MEDIUM — package/load closure is represented by source-presence heuristics rather than the promised production artifact/load evidence.** `tests/Deployment/yii2_imports_workforce_package_001_test.py:5-19` checks six source files, three configuration strings, three unrelated files, and two Dockerfile substrings. It does not build/inspect the production package or trace loaded files, so it cannot detect omission of required owners/runtime files, accidental loading of `rapid-pilot`, web/session/jobs code or a second autoloader, nor execution during startup. Add a production artifact inventory/load-trace assertion for each command and a startup non-execution witness.

6. **MEDIUM — the verification plan overstates acceptance coverage.** The plan maps the disconnected legacy oracles as GREEN evidence for A2–A4 and architecture/CI inventory tests as A6 coverage, although neither observes the new seam's authorization/admission or complete redaction contract. Its `paths.effective` also includes intended future production files that are absent from the reviewed Gate 2 source; that is valid planning, but not evidence that the behavioral matrix is covered. Regenerate the mapping after adding the missing seam tests and classify each test only for outcomes it actually observes.

7. **HIGH — two tests mapped as required GREEN are not GREEN on the supplied source/environment.** `php tests/InstallationProcess/pilot_case_import_001_test.php` exits 255 at initial database setup with `Access denied for user 'root'`, and `python3 tests/Verification/verification_ci_001_test.py` fails two cases because the changed `tools/verification/suites.tsv` makes the catalog report `unknown catalog suite: integration`. The latter is a candidate regression, not an intended missing-production RED; the former is additional unreproducible setup. Correct the suite registration and provide the exact database fixture/environment needed for all mapped legacy oracles, then retain fresh GREEN records.

The other independently executed new tests do produce ordinary missing-file/composition REDs: ownership and the three adapter tests exit 255 at their explicit absent-controller/adapter assertions, while the package test exits 1 at the absent controller source. Snapshot/workforce legacy oracles, legacy workforce delegation and architecture guard are GREEN. Those results do not compensate for findings 1–7. No local full `make test` or `make verify` was run, in accordance with the owner decision.

## Verdict

**REJECTED.** Gate 3 does not advance. Return the complete candidate to Gate 2, correct all findings, provide reproducible intended RED evidence from the exact new seams, capture a fresh source package, and request independent rereview. Task 2.4 remains unchecked; production implementation must not begin from this package.

## Independent Gate 3 rereview — 2026-09-12

- Reviewer: independent Codex reviewer `/root/gate3_imports_workforce`; authored neither specification nor tests
- Corrected root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T211200Z-8c5d14f13f/package.json`
- Exact source: base commit `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260911T211200Z-8c5d14f13f/snapshot`, patch SHA-256 `90ae7d2d9dd8ab81b68d8eac3e77230d0d2929d62adc48a6d37488c8c3488833`, harness source `feabee8505d968e79edab128371494bab4f9d263a164d5770e74896093805dca`
- Verification plan SHA-256: `3cae730e905e4db41908d253083af3b8e3bca7ca10b38e6b2a78b25afb198ffe`
- Dependency setup: ignored-use intent only; the external Yii/Composer `vendor` tree was attached for execution and is not source evidence

### Prior findings disposition

- Finding 1 is resolved: the main transport test now checks for the absent production command source before invoking Yii, so its RED is no longer caused by missing Composer bootstrap.
- Finding 7 is resolved for the mapped Gate 3 checks: `architecture_guard_001_test.py` and `verification_ci_001_test.py` are independently GREEN, and the plan no longer misclassifies the legacy DB tests as acceptance evidence.
- Findings 2–6 are only partially addressed and remain blocking as consolidated below.

### Complete rereview findings

1. **HIGH — A4 still has no behavioral test through `workforce-sync/run`.** `tests/Yii2/yii2_workforce_sync_db_001_test.php:5-10` is unchanged in substance: it checks source strings, then launches `workforce_synchronization_manual_pilot_test.php` directly through the legacy owner seam. It never invokes Yii, never supplies either allowed Bitrix configuration form, and never independently observes invocation count, run UUID, full/missing reconciliation, identity conflict, lock/replay/history, partial/transport failure, catalogue atomicity, or staged-token cleanup. A broken or bypassing Yii adapter can pass. Replace the disconnected oracle with a deterministic synthetic-delivery exercise through the new Yii seam covering the complete A4 matrix.

2. **HIGH — A3's new Yii test covers only a small subset of the required snapshot behavior.** `tests/Yii2/yii2_snapshot_import_db_001_test.php:9-16` covers success, one repeat count, and bad SHA. It omits regular/private-file and effective `0600` enforcement, snapshot schema/object/template invalidity, mirror/template/detail conflicts, case rejection, unknown generic failure/redaction, exact no-partial-facts assertions for every rejection, and complete idempotent result/history preservation. The hand-built success fixture also does not establish parity with the full existing snapshot oracle. Add the missing cases through `snapshot-import/run`, observing all relevant case/detail/template/history facts independently.

3. **HIGH — A1/A6 transport, configuration and redaction coverage remains incomplete.** `tests/Yii2/yii2_imports_workforce_001_test.php:19-43` improves argument and DB validation, but still omits a sensitive 100-ID accepted boundary, option abbreviations and several duplicate/reordered forms, missing DB password/key and complete missing-variable combinations, valid/partial/mixed `FMONITOR_BITRIX_CONFIG` versus explicit Bitrix inputs, CA-file behavior, unknown Throwable mappings, and redaction of snapshot path/hash/content, Bitrix origin/user/departments/token/config paths/content, exception class/message/trace and SQL. It asserts no durable side effects only indirectly via port 1, not for validation paths involving files/external delivery. Add table-driven cases for every normative input boundary and protected-value class, including independent no-file/no-delivery/no-DB-fact witnesses.

4. **HIGH — alias parity remains rejection-only.** `tests/Yii2/yii2_imports_workforce_ownership_001_test.php:14-19` compares only a single `--bad` rejection per alias. It does not compare success, domain rejection, dependency failure, replay, stdout/stderr/exit and durable facts for valid legacy syntax. This leaves A5's compatibility promise untested. Add direct/alias paired executions for success and representative operation-specific rejection/failure/replay cases for all three commands.

5. **MEDIUM — A5 production package/load closure is still incomplete.** `tests/Deployment/yii2_imports_workforce_package_001_test.py:21-30` adds a useful runtime included-file trace, but it neither builds nor inventories the production artifact, asserts all required application-owner files are packaged/loaded, rejects web/session or a second autoloader comprehensively, nor proves commands are not executed during HTTP/startup. The subprocess return is not asserted, so a pre-controller bootstrap failure can still produce a passing load trace after the initial file-presence guards become green. Assert the expected closed command outcome, exact required/forbidden load families, packaged file closure, and an explicit ordinary-startup non-execution witness.

6. **MEDIUM — A2's redirected legacy suite is useful but does not establish all claimed adapter properties.** `tests/Yii2/yii2_case_import_db_001_test.php:9` redirects the comprehensive legacy case-import suite through a Yii wrapper, which substantially improves parity coverage. However, the wrapper `tests/Support/yii_case_import_test_entrypoint.php:3-5` rewrites every invocation to Yii and does not independently count owner calls or DB lifecycles; lexical `substr_count` cannot prove one mysqli lifecycle or one owner invocation. Add an injectable/counting composition witness (or equivalent observable connection/invocation evidence) while retaining the comprehensive durable-fact suite.

The six intended-RED mapped tests were independently reproduced from this source and fail at explicit absent production controller/adapter assertions, not dependency setup. Both mapped GREEN governance tests pass. These correct outcome classifications do not prove the missing acceptance coverage above; a harness outcome marker is not Gate 3 approval. No full local suite was run.

### Rereview verdict

**REJECTED.** Gate 3 remains closed. Correct findings 1–6 as one complete Gate 2 candidate, capture a fresh exact-source package, and request another independent rereview. Task 2.4 remains unchecked and no implementation is authorized.

## Third independent Gate 3 review — case-import-only split, 2026-09-12

- Reviewer: independent Codex reviewer `/root/gate3_imports_workforce`; authored neither specification nor tests
- Owner-confirmed scope: case-import only; snapshot import and workforce sync are explicitly deferred to later #76 changes
- Root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T075621Z-eb5d3c74da/package.json`
- Exact source: base commit `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T075621Z-eb5d3c74da/snapshot`, patch SHA-256 `7781abc64d62374256fe60da69964a65430ce803ca3c9037768edee375fedcea`, harness source `f9af9366df67520a9e8926ea6938f7d75eb58414ad5823d9bc4b2a260f0bd682`
- Verification plan SHA-256: `36feea388542778ead640a526e9221f5d7af0dc528b7dc66ab9bc6338ec6c73b`

The normative spec, OpenSpec artifacts and verification mapping are coherently narrowed to one `case-import/run` seam. Prior snapshot/workforce findings are therefore inapplicable to this package rather than implicitly approved. The prior setup and verification-inventory failures remain resolved.

### Complete findings for the narrowed slice

1. **HIGH — required direct/alias behavioral parity is absent.** The normative A3 contract and delta scenario require success, rejection, dependency failure and replay to preserve stdout/stderr/exit and durable facts through the retained legacy syntax. `tests/Yii2/yii2_imports_workforce_ownership_001_test.php:3-5` performs only lexical source assertions; it never invokes either direct or alias transport. The redirected full DB oracle in `tests/Yii2/yii2_case_import_db_001_test.php:9` runs only the Yii wrapper, not paired transports. An alias that changes argument order, exit mapping, result JSON, replay behavior or facts can pass. Add paired direct/alias behavioral executions for success, eligibility rejection, unavailable DB and repeat, comparing exact terminal and independently observed durable outcomes.

2. **HIGH — the promised one-owner/one-connection witness is only a lexical count.** `tests/Yii2/yii2_imports_workforce_ownership_001_test.php:4` counts the strings `PilotCaseImporter` and `new mysqli` in controller plus adapter source. That does not detect a loop/retry that constructs or invokes twice, indirect connection construction, or a second invocation through a factory. Both the normative A3 contract and OpenSpec design explicitly require an invocation-count witness. Add an injectable/counting boundary or equivalent runtime observation proving exactly one mysqli lifecycle and one owner invocation for an accepted command; retain the lexical boundary checks as supplemental architecture evidence.

3. **HIGH — closed transport/redaction mapping does not cover all normative runtime outcomes.** `tests/Yii2/yii2_imports_workforce_001_test.php:7-13` covers argument/environment rejection and database unavailable, while the redirected legacy suite covers the established domain oracle. The candidate still has no focused fault showing an unknown `Throwable` maps to the specified redacted import failure, with exception class/message/trace and SQL absent; nor does it prove empty password and empty prefixes are accepted as the normative A1 text expressly permits. Add deterministic transport-level fault/redaction canaries and accepted-boundary cases for the allowed empty values. Also assert complete closed JSON/stderr on the 100/101 and environment cases, not exit code alone, so Yii warnings or leaked values cannot pass those branches.

4. **MEDIUM — package/load closure remains weaker than its normative and design claims.** `tests/Deployment/yii2_imports_workforce_package_001_test.py:5-15` checks source presence and one included-file trace, but it does not inspect a built production artifact, detect a second autoloader, exclude session composition, or provide an ordinary HTTP/startup execution witness. Checking that three source files lack the literal route is not proof that import is unreachable/not executed from startup. Add the declared artifact inventory, explicit second-autoloader/session exclusions and an ordinary-startup non-execution observation. The improved subprocess outcome and required/forbidden included-file assertions should remain.

### Evidence and verdict

All four mapped RED tests were independently reproduced at their explicit missing controller/adapter/package assertions: package exits 1, and transport/DB-oracle/ownership exit 255. `architecture_guard_001_test.py` and `verification_ci_001_test.py` are independently GREEN. These are intended missing-implementation REDs with no observed setup failure, but they do not make the missing acceptance assertions above observable. No local full suite was run.

**Gate 3 verdict: REJECTED.** Gate 4 remains closed. Correct findings 1–4 and resubmit one fresh complete case-import-only package. Task 1.3 remains unchecked; no reviewer package is prepared because approval was not reached.

## Fifth independent Gate 3 review — APPROVED, 2026-09-12

- Reviewer: independent Codex reviewer `/root/gate3_imports_workforce`; authored neither specification nor tests
- Root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T080541Z-f7ea75a02a/package.json`
- Exact reviewed source: base commit `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T080541Z-f7ea75a02a/snapshot`, patch SHA-256 `c3b54c6dfe0c6eac60ff7d11e5214e79d07ad79ebb7d23555ada4c5649d465b2`, harness source `57bc3db8cae925df1bdffab96204568ab8eb7676a0c043e7121f301bab727235`
- Verification plan SHA-256: `167c3d365c2869f4a62bed245a4cefc92a3c6f8aa26a7fcf2fa6bb95b9af2f69`

### Complete findings

None.

The fifth candidate closes both findings from the fourth review. The transport matrix now explicitly rejects abbreviated object and interactive options with the complete closed tuple. The command load trace excludes web and session families and admits only the application and Composer autoloaders; the normative wording correctly targets an independent legacy autoloader rather than forbidding the two intentional layers. The built runtime-image inventory/closed command and ordinary web-startup reverse trace remain present.

All earlier applicable findings are closed for the owner-confirmed case-import-only slice: the complete established case-import oracle runs separately through the Yii test transport and retained alias, with independent expected terminal and durable facts for success, rejection, dependency failures, repeat, concurrency and unknown-commit reconciliation; source witnesses enforce the single composition boundary; invalid/environment/100-ID/empty-value/unknown-Throwable redaction cases exercise the closed transport; and package/load/startup boundaries are observable. Snapshot import and workforce sync remain explicitly outside this approval and require later changes.

The four mapped candidate tests independently produce intended RED at the absent controller/adapter/package source guard, not at dependency setup. Both mapped governance tests are GREEN. No local full suite was run; final exact-source full CI remains a later delivery gate.

**Gate 3 verdict: APPROVED.** Gate 4 may proceed only from this reviewed case-import-only contract/test source plus the appended review/task metadata. Any behavioral spec/test change requires a new Gate 3 delta review.

## Fourth independent Gate 3 review — 2026-09-12

- Reviewer: independent Codex reviewer `/root/gate3_imports_workforce`; authored neither specification nor tests
- Root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T080225Z-bd89b9399e/package.json`
- Exact source: base commit `adf30398c62ca4d9fc77b1d3ecdaed68a5a228bc` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T080225Z-bd89b9399e/snapshot`, patch SHA-256 `0eeffbc1eac0ed8da9dd7999294b4bbf026628f55749c66709d3a9eff2d02d52`, harness source `a08afa5733ef49bf94de7b565bfebbfdf3de9fbccf6235bcbbae74560080422a`
- Verification plan SHA-256: `71ecaa1a9d87f477832c9c14e67957741091c048bbc51aa0dad241133d0e9411`

The corrected candidate closes most prior findings: the full established oracle now runs separately through direct Yii and retained alias transports; the normative claim is accurately narrowed to a single lexical composition boundary; exact terminal outcomes cover 100/101 IDs and invalid environment; allowed empty values and an injected unknown Throwable/redaction canary are present; and the package check adds a closed subprocess outcome, ordinary startup trace and built runtime-image presence/command checks.

### Complete findings

1. **HIGH — abbreviated options remain untested despite an explicit rejection requirement.** Normative A1 says shortened inputs SHALL be rejected before DB access. `tests/Yii2/yii2_imports_workforce_001_test.php:10-13` covers unknown `--unknown`, duplicate IDs, invalid values and `--interactive` placement, but never passes an abbreviation such as `--object-i=7` or `--interact=0`. Yii option parsing can accept or normalize abbreviations independently of the adapter's intended exact grammar, so an implementation violating this explicit transport boundary can pass. Add representative abbreviated object and interactive options and assert the complete exit-64 JSON/stderr tuple.

2. **MEDIUM — load closure still does not test two explicit forbidden families.** Normative A4 excludes web/session composition and a second autoloader from the command load set. `tests/Deployment/yii2_imports_workforce_package_001_test.py:13-15` rejects rapid-pilot, demo, `YiiRuntime/Controllers` and `app/Jobs`, but has no session marker and does not count/otherwise reject multiple autoloader bootstrap files. The startup check at line 16 proves the reverse direction (ordinary web startup does not load case import), not that the case-import command avoids web/session code. Add explicit forbidden session/web load markers and a one-autoloader assertion to the command trace. The built image checks at lines 17-23 do not inspect its included-file closure, so they do not fill this gap.

All four mapped candidate tests independently produce intended RED at the absent controller/adapter/package guards. Both mapped governance tests are independently GREEN. No setup failure and no local full-suite run occurred.

**Gate 3 verdict: REJECTED.** Gate 4 remains closed for these two complete-candidate omissions. Add the narrow assertions, capture a fresh exact source package, and request rereview. Task 1.3 remains unchecked; no reviewer package is prepared.

## CI classification delta review — APPROVED, 2026-09-12

- Reviewer: independent Codex reviewer `/root/gate3_imports_workforce`; authored neither the correction nor the test
- Root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T100324Z-98cbc2e222/package.json`
- Exact source: base/head `4b0af3d230725a5ff9377d474b7eab7b5efd9579` plus snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T100324Z-98cbc2e222/snapshot`, patch SHA-256 `e21fe310615c5d468c56f632e0a7b3325909f1975dac9478260b4a5391c5d4d8`, harness source `81785d0970d220c0303a0c8f83134c1cb41922aa4dfa364c810983180a1eafff`
- Reviewed delta: only `tests/Yii2/yii2_imports_workforce_001_test.php` changes from `unit` to `integration` in `tools/verification/categories.json` and from `unit` to `db` in `tools/verification/suites.tsv`; specification, test and production bytes are unchanged

### Complete findings

None.

The classification matches the executable boundary. The transport test's unknown-Throwable/redaction path connects to the prepared MariaDB service before injecting `YiiCaseImportThrowingOwner`; it therefore requires the DB fixture and cannot run reliably in the no-DB unit shard. The `integration` policy category and `db` executable suite route it to the existing MariaDB-backed shard without weakening or omitting the test. Independent focused checks are GREEN: `verification_ci_001_test.py` (16 cases) and `change_verification_001_test.py`. The reported integration/e2e evidence remains separate; no local full suite was run.

**Gate 3 delta verdict: APPROVED.** The prior case-import Gate 3 approval remains valid with this classification-only correction. Gate 5/CI must review and validate the final committed source; UNKNOWN is not approval.
