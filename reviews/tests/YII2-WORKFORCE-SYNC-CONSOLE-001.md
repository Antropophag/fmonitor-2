# Independent Gate 3 test review — YII2-WORKFORCE-SYNC-CONSOLE-001

- Reviewer: separately tasked agent `gate3_workforce_sync`; authored none of the reviewed specification, OpenSpec, verification plan, or tests.
- Review date: 2026-09-12.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T133110Z-d6f45eb37c/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `a8ba73a926d1031c8b039555d0b6c3f142abd991`, source digest `becffd2d5ba55da20e2f917f7d67b71f3a7359c715831fcc6deaa7815800ff44`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T133110Z-d6f45eb37c/snapshot/source.patch`, SHA-256 `a51569ddd8508e524c9c600d73f48cf543d05d1255d0be247fbba874ed6acf04` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T133110Z-d6f45eb37c/verification-plan.json`, package-declared SHA-256 `26273d6e4341f922bf46c8c3769e8977cc01a69c70289bfbe6ff1657e550d344`.
- Normative contract: `specs/YII2-WORKFORCE-SYNC-CONSOLE-001.md`; lifecycle input: `openspec/changes/yii2-workforce-sync-console/verification-input.json`.
- Scope boundary: issue #76 workforce synchronization console only. OTIZ remains explicitly excluded; no OTIZ path occurs in the prepared actual-path inventory or reviewed tests.

## Findings

1. **HIGH — A1 configuration grammar and pre-side-effect rejection are materially under-tested.** Locations: `specs/YII2-WORKFORCE-SYNC-CONSOLE-001.md` A1; `tests/Yii2/yii2_workforce_sync_console_001_test.php:6-13`. The test covers only direct Bitrix environment configuration. It never exercises the allowed absolute private `FMONITOR_BITRIX_CONFIG` form, rejection of mixed file/direct modes, relative/private-file failures, or the complete DB scalar boundaries (empty host/name/user, zero/nondecimal port). Moreover, all invalid-input assertions observe only exit/stdout/stderr against an unreachable DB and nonexistent token path; they do not prove rejection occurred before token/config filesystem access, network delivery, DB connection, or durable facts. Correction: add table-driven valid file-mode and invalid mixed/boundary cases, with access-observable file/network/DB sentinels and before/after durable inventories.

2. **HIGH — A2's durable and failure matrix is incomplete through the new public seam.** Locations: contract A2 and A4; `tests/Yii2/yii2_workforce_sync_db_001_test.php:6-8`; verification input A2. The sole Yii DB exercise proves a 51-row success and one unchanged repeat, but does not independently assert normalized checksum/run identity, same or concurrent identity reconciliation, partial delivery/normalization/schema/commit uncertainty, non-`completed` owner exit `1`, unknown runtime exit `70`, atomic no-partial publication, or resource/staged-token cleanup on every terminal path. The plan also omits the inherited delivery/history/canonical-runner controls named by the contract, so those behaviors are neither reached through Yii nor retained as executable controls in this package. Correction: extend the public-subprocess fixture matrix across these outcomes, assert complete relevant persisted facts before/after, and include the inherited oracle commands in the verification plan.

3. **HIGH — A3 promises direct/alias parity for five outcome families, but the tests compare only different success phases.** Locations: contract A3; OpenSpec `Direct и alias parity`; `tests/Yii2/yii2_workforce_sync_db_001_test.php:6-8`; `tests/Yii2/yii2_workforce_sync_console_001_test.php:8-13`. Direct performs the first successful publication and alias performs the unchanged repeat, so their stdout and durable results are intentionally different and never compared under equivalent starting state. Alias parity is absent for configuration failure, transport failure, and DB failure; no unknown/runtime case is covered either. Correction: run direct and alias against equivalent isolated initial states for success and unchanged repeat, then invoke both for every specified failure family and compare exit/stdout/stderr plus durable facts.

4. **HIGH — A4 redaction and cleanup assertions cover only a small stdout subset and no logs.** Locations: contract A4; `tests/Yii2/yii2_workforce_sync_console_001_test.php:13`; `tests/Yii2/yii2_workforce_sync_db_001_test.php:8`. Current checks search terminal output for direct-environment values and a few success-fixture strings. They do not force or inspect failures containing config/token paths and contents, DB coordinates/prefix, employee PII, exception class/message/trace, or SQL; they inspect no command-contract logs and do not assert staged-token deletion after success, rejection, transport failure, DB failure, or Throwable. Correction: inject distinct canaries for every protected class into deterministic failure paths, capture every specified terminal/log sink, and assert cleanup for each outcome through both public launchers.

5. **MEDIUM — the single-owner witness is lexical and can pass behaviorally duplicated composition.** Location: `tests/Yii2/yii2_workforce_sync_ownership_001_test.php:3-4`. Counting literal `MariaDbWorkforceSynchronization` and `new mysqli` occurrences across two expected files and checking a few forbidden strings cannot detect indirect second construction, a second invocation of the same owner, or SQL/composition moved into another loaded helper. This does not establish the contract's “exactly one mysqli lifecycle and one invocation” requirement. Correction: retain the lexical/package checks as architecture guards, but add injectable or observable behavioral witnesses for connection creation, owner invocation count, close-on-all-paths, and job/manual reuse of the same composition boundary.

## Traceability, determinism, and retained evidence

The verification input maps A1–A4 to named tests and has no reported `missing_tests`, but the mappings overstate the behavioral coverage described above. The tests cite the specification identifier, and their main behavioral exercises use subprocess public seams; the ownership test is appropriately only a supplemental structural check. The local HTTPS fixture and isolated random database are deterministic enough for Gate 3, subject to the missing fault/concurrency witnesses.

The four retained RED records are source-bound with no drift and fail for the intended missing production files: controller, shared composition, or package closure. Records:

- `1789219749051386000-c9cd928068f44896bfbc8b5b7b20d7fe`
- `1789219750054533000-928dd681099d4d9b951fb08c9e4da353`
- `1789219750863085000-bf9af45928514c1aadfabdf0f8ebd5a3`
- `1789219751627793000-4ddc5afdcac442c3af1e54835798a60c`

The retained architecture, verification-CI, and jobs workforce/retry controls are GREEN at the same source. `openspec validate yii2-workforce-sync-console --strict` is GREEN, and `git diff --check` for the reviewed planning/test inputs is clean. These results do not resolve the acceptance gaps above. Package approval and CI/deployment remain `NOT_REVIEWED`/`UNKNOWN`, correctly not treated as GREEN or authorization.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gate 2, correct the complete test/verification matrix above without expanding into OTIZ, capture fresh intended RED at a new exact source, and submit the complete package for independent rereview.

---

## Gate 3 correction rereview — 2026-09-12

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T133952Z-b6b3eceb15/package.json`.
- Corrected exact source: reconstructible snapshot over base `a8ba73a926d1031c8b039555d0b6c3f142abd991`, source digest `b5f5d7d487aec569bc5971a0745a748d5ac1126699d2643b4e7ebc47efb47bbe`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T133952Z-b6b3eceb15/snapshot/source.patch`, SHA-256 `6afe6c282822341a77c57221eb05dffeaecd302b3589feb883841708269205ff`.
- Delta reviewed from the previous package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T133952Z-b6b3eceb15/delta.patch`.
- Corrected verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T133952Z-b6b3eceb15/verification-plan.json`, SHA-256 `2649a3e17ecc962770dc44943beff3a886c4ecfab099164d9d0f6792454afa48`.
- Independence remains unchanged: this reviewer authored none of the corrected spec, OpenSpec, verification input, tests, or retained evidence.

### Resolution of prior findings

- Prior finding 2 is partially resolved: unchanged independent Bitrix delivery and workforce synchronization oracles are now mapped, planned, and retained GREEN at the corrected exact source. The new contract reasonably leaves their already-approved identity/concurrency/atomicity policy with those owners.
- Prior finding 3 is resolved by an explicit scope decision: the contract now requires direct-success followed by alias-unchanged-repeat on one isolated database, plus exact parity only for equivalent invalid-configuration and DB-unavailable inputs. The tests cover those declared cases.
- Prior finding 4 is partially resolved by narrowing observable redaction to command stdout/stderr and adding file-mode DB-failure cleanup plus direct-environment canaries.
- Prior finding 5 is narrowed from exact internal invocation counting to one reachable source composition boundary, but resource closure remains an explicit requirement and is still not observed.
- OTIZ remains excluded from the contract, package actual paths, load guards, and tests.

### Remaining findings

1. **HIGH — still-normative A1 DB validation boundaries are not covered.** Locations: corrected contract A1; `tests/Yii2/yii2_workforce_sync_console_001_test.php:9-11`. A1 still requires nonempty DB host/name/user and a canonical decimal port in `1..65535`. The corrected test adds file mode and relative-file rejection, but still has no empty host/name/user cases and no zero or nonnumeric port case. Those defects could reach filesystem/network/DB while all current assertions pass. Correction: add these table-driven invalid inputs and assert the same closed pre-side-effect outcome.

2. **HIGH — A1/A4 unexpected-failure behavior and file-mode success cleanup remain untested.** Locations: corrected contract A1 (`unexpected composition failure` → exit `70`/`SYNC_FAILED`) and A4 (staged token removed after file-mode success or failure; protected exception/message/trace absent); `tests/Yii2/yii2_workforce_sync_console_001_test.php:12-14`; `tests/Yii2/yii2_workforce_sync_db_001_test.php:6-8`. The added file-mode case reaches only DB-unavailable exit `69`; the successful DB test continues to use direct environment. No deterministic unexpected composition failure is injected, so exit `70`, Throwable redaction, and staged-token cleanup on that path can regress. No file-mode success demonstrates cleanup either. Correction: run at least one successful publication using the private file mode, and inject a deterministic unexpected composition failure carrying exception canaries; assert terminal mapping/redaction and unchanged staged-token inventory for both.

3. **MEDIUM — the promised single mysqli lifecycle/resource closure still lacks a sensitive test.** Locations: corrected contract A2; OpenSpec design decision 1; `tests/Yii2/yii2_workforce_sync_ownership_001_test.php:3-4`. The correction narrows invocation-count claims but continues to require one mysqli lifecycle and guaranteed close. Literal source counts do not fail if the connection is leaked or if a helper opens another connection without the counted spelling. Correction: add an observable connection factory/resource witness or another deterministic lifecycle probe that fails for duplicate construction and failure-path leakage; keep the lexical assertion only as a supplementary ownership guard.

### Corrected evidence assessment

The four corrected RED records are bound to source `b5f5d7d487aec569bc5971a0745a748d5ac1126699d2643b4e7ebc47efb47bbe`, report no source drift, and still fail first on the intentionally missing Yii controller/composition/package files. The two jobs controls, Bitrix delivery oracle, workforce synchronization oracle, architecture guard, and verification-CI test are retained GREEN at that same source. This is valid RED/control evidence, but it does not make the remaining acceptance gaps GREEN by implication. CI and deployment remain `UNKNOWN` and are not approvals.

### Rereview verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked. Correct the three bounded Gate 2 gaps above, retain the narrowed scope and OTIZ exclusion, capture fresh exact-source RED, and resubmit one complete correction package.

---

## Gate 3 third review — 2026-09-12

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T134443Z-eef69b250b/package.json`.
- Exact reviewed source: reconstructible snapshot over base `a8ba73a926d1031c8b039555d0b6c3f142abd991`, source digest `ec3793a47bb460646e6cdaf2898c2f47c2d58ab17bf7b61daf348e8c4a69c81c`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T134443Z-eef69b250b/snapshot/source.patch`, SHA-256 `bb9ea60cdc0975be783307974b86e4db1cb5725a79bea8bb027676165ea90243`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T134443Z-eef69b250b/delta.patch`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T134443Z-eef69b250b/verification-plan.json`, SHA-256 `2977056c73706c747faa0f0c9ec320e19c5c56f96ed8dcb7eb36e1c67450100e`.
- Reviewer independence is unchanged; no reviewed artifact was authored by this reviewer.

### Prior-finding resolution

- The remaining A1 scalar-boundary finding is resolved. The table now covers empty host/name/user and port `0`, nonnumeric, noncanonical, and above-range values, while retaining the required positive empty-password/prefix case.
- The remaining file-mode/Throwable finding is resolved. The DB subprocess now proves file-mode success with staged-token cleanup; the transport test retains file-mode DB-failure cleanup and injects an owner Throwable with exception/message/SQL canaries, requiring exact exit `70`, `SYNC_FAILED`, empty stderr, and redaction.
- The delivery, synchronization, jobs, architecture, and verification controls remain mapped and retained GREEN at this exact source. Four fresh RED records have no source drift and fail on the intentionally absent production controller/composition/package files.
- The contract consistently narrows alias parity and internal domain behavior as recorded in the preceding rereview. OTIZ remains excluded from actual/planned paths and load assertions.

### Remaining finding

1. **MEDIUM — the corrected Gate 2 artifact still does not prove the contract's explicit `finally` ownership requirement.** Locations: corrected contract A2; OpenSpec design decision 1; unchanged `tests/Yii2/yii2_workforce_sync_ownership_001_test.php:3-4`. The contract now deliberately defines Gate 2 evidence as one reachable source composition boundary, `finally` ownership, and no composition in launchers. The structural test proves the first and third points but contains no assertion for `finally` or a connection `close`. The injected-Throwable subprocess proves closed terminal mapping and token cleanup, but because the subprocess exits immediately it also passes when PHP/process teardown—not the composition's required `finally`—releases mysqli. Correction: add a narrow structural assertion that the sole composition contains the required `try`/`finally` and closes its owned mysqli resource, or add an equivalent in-process observable release witness. Exact driver-handle counting remains correctly out of scope.

### Evidence and verdict

`openspec validate yii2-workforce-sync-console --strict` is GREEN and `git diff --check` is clean for the reviewed inputs. CI and deployment remain `UNKNOWN`, correctly not treated as approval.

`CHANGES_REQUESTED`

Gate 4 remains blocked only on the bounded `finally`-ownership sensitivity gap above. Preserve the corrected scope, retained oracles, and OTIZ exclusion when capturing the next exact-source package.

---

## Gate 3 final narrow rereview — 2026-09-12

- Final correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T134801Z-845cae91c9/package.json`.
- Exact reviewed source: reconstructible snapshot over base `a8ba73a926d1031c8b039555d0b6c3f142abd991`, source digest `3f9b791dce10b7d6b4fa5aa07dfffc6a46fc7d4f30148c4b9031c94fe721013c`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T134801Z-845cae91c9/snapshot/source.patch`, SHA-256 `f82d3ab0f8c7fccacecf84298f2249f31c90380d222d2bf3f26a4544f8bc6412`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T134801Z-845cae91c9/delta.patch`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T134801Z-845cae91c9/verification-plan.json`, SHA-256 `7d5a5fd6f82da373b481a504fa03dbbebdcbab7a1b5c614ff035bce839e16d46`.
- Reviewer independence is unchanged; this reviewer authored none of the reviewed contract, OpenSpec, tests, or evidence.

### Finding resolution

The only remaining finding is resolved. `tests/Yii2/yii2_workforce_sync_ownership_001_test.php` now requires the sole shared adapter source to contain both `finally` and `->close()`, in addition to the existing single owner/composition construction, forbidden transport ownership, launcher delegation, and jobs-reuse assertions. This is sensitive to omission of the contract's deliberately narrowed `finally`-ownership rule; exact internal driver-handle counting remains correctly outside the slice.

No new findings. The complete A1–A4 mapping now covers strict argv and DB/config boundaries, private file-mode priority and cleanup on success/failure, deterministic unexpected-Throwable mapping and redaction, public-seam success/repeat and declared alias parity, retained delivery/synchronization/jobs controls, single reachable composition, package/load closure, and verification inventory. Expected values come from isolated fixtures and retained approved owners rather than the planned implementation.

Four fresh intended RED records are bound to source `3f9b791dce10b7d6b4fa5aa07dfffc6a46fc7d4f30148c4b9031c94fe721013c`, have no source drift, and fail for the absent Yii workforce controller/composition/package seam. Retained Bitrix delivery, workforce synchronization, jobs workforce/retry, architecture, and verification-CI controls are GREEN at the same source. `openspec validate yii2-workforce-sync-console --strict` is GREEN and `git diff --check` is clean.

OTIZ remains excluded from scope, paths, and load closure. CI and deployment remain `UNKNOWN`; this Gate 3 approval does not treat either as GREEN or authorize deployment.

### Final verdict

`APPROVED`

Gate 3 passes for exact source `3f9b791dce10b7d6b4fa5aa07dfffc6a46fc7d4f30148c4b9031c94fe721013c`. Gate 4 may proceed against this reviewed package; later changes to the approved expectations require a new Gate 2/3 cycle.

---

## Gate 3 delta review — partial-initialization resource release — 2026-09-12

- Delta package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T140743Z-af33a6a14c/package.json`.
- Exact reviewed source: reconstructible snapshot over base `a8ba73a926d1031c8b039555d0b6c3f142abd991`, source digest `d0283b430cb113088758554efac15af72d4088da859fb707e367d10d94badce0`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T140743Z-af33a6a14c/snapshot/source.patch`, SHA-256 `505417d87a92df8e74afdab4d8e6d260a728a13416bf51e109e4f06c707223d9`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T140743Z-af33a6a14c/verification-plan.json`, SHA-256 `600bcb21045ea67874910a49a30c9d7fd22ee9cd0fcdf70b8b0a00ce6dd44a80`.
- Verification binding: `openspec/changes/yii2-workforce-sync-console/verification-gate5-correction-input.json`, acceptance `A2-partial-initialization-close`.
- Review scope is solely the added assertion in `tests/Yii2/yii2_workforce_sync_ownership_001_test.php`; this reviewer authored neither the assertion nor the Gate 5 finding, contract, implementation, or evidence.

### Assessment

No findings. The new assertion directly traces to normative A2 and the Gate 5 finding: after `new mysqli`, database initialization must guard `set_charset` and close the allocated resource in the catch path before propagating failure. Its ordered source witness is narrow enough to reject the current leak while preserving the previously approved outer `finally` ownership and single-composition checks. It does not prescribe domain behavior, SQL, additional connection handles, or OTIZ scope.

Retained record `1789222045493018000-38790907a37940cd92d281e64860e256` is `INTENDED_RED`, bound at start and end to exact source `d0283b430cb113088758554efac15af72d4088da859fb707e367d10d94badce0`, with `source_drift=false`. It fails at the new assertion with expected `1`, actual `0`: `partial database initialization closes before rethrow`. Earlier ownership assertions pass first, so this is the intended missing resource-release behavior rather than test setup failure.

The focused correction plan includes the ownership test plus the affected console/DB/package and governance/integration controls for post-implementation GREEN. CI and deployment remain `UNKNOWN` and are not treated as approval. OTIZ remains excluded.

### Delta verdict

`APPROVED`

The Gate 5 resource-close correction may proceed to implementation against this exact reviewed test delta. The changed test must be GREEN on the corrected exact source before independent Gate 5 rereview.
