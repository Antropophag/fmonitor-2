# Gate 3 review — YII2-CLEAN-STAND-CUTOVER-001

- Date: 2026-09-14
- Reviewer: independent `gate3_clean_cutover`; authored none of the reviewed specification, OpenSpec artifacts, tests, support fixture, or RED evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T081702Z-c84433f4f5/package.json`.
- Reviewed reconstructible source: candidate source `2d1b93d861c44728bf53e4af4ff7db8d18eb1abaf55dde62a0d3c37bcad84473`, executable source `9c24436a856a3ed56c78068262b4528f791e4bf6c7e45c41850ab0629339a6ee`, over base `f4dab7d6b576edc3bb6e9578a939e90b9a1f36ae`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T081702Z-c84433f4f5/snapshot/source.patch`, SHA-256 `aaea9d960502c79ff426e46c5b80178dccb269a519a6f9a16883c856cdcb850d`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T081702Z-c84433f4f5/verification-plan.json`, SHA-256 `785ae19137f09384556e035d43906f3a385cff160605c975b7c2cdf3424e9c16`.

## Assessment

The change has the right bounded intent: fresh Yii2 provisioning and acceptance, with restore, reconciliation, rollback, legacy retirement, and production traffic cutover excluded. The normative contract also correctly requires real MariaDB, Compose, HTTP, jobs, image, process, and loaded-file evidence before `CLEAN_STAND_ACCEPTED`.

All seven retained commands are honest missing-seam RED: each exits non-zero because `tests/Support/yii2_clean_stand_acceptance.php` does not exist, and each record is bound to the package source and executable source. This proves that the proposed public acceptance seam is absent. It does not prove that the submitted tests are sensitive to the real-boundary and authorization guarantees they claim. In their current form, one small PHP program can read `driver.json`, copy its booleans and strings to the result, append the requested event names, and pass the complete suite without starting Docker, MariaDB, HTTP, worker, scheduler, or any existing application seam.

## Findings

1. **HIGH — the pre-effect target identity contract is impossible for the fresh-create lifecycle.** Locations: `specs/YII2-CLEAN-STAND-CUTOVER-001.md:14-26`; `openspec/changes/yii2-clean-stand-cutover/design.md:74-81`; `tests/Support/clean_stand_contract.py:16-25,44-69`; `tests/Deployment/yii2_clean_stand_admission_001_test.py:8-26`. A package prepared before state-changing deployment cannot bind observed container, network, and named-volume IDs for a Compose project that the authorized `run` is supposed to create. The fixture evades this by declaring five fictional pre-existing container IDs while also claiming `CREATE_DISPOSABLE_PROJECT`. Split the lifecycle explicitly: before the first effect, bind source/image/Compose, exact namespace/names, credential references, expected absence/non-overlap, and authorized effects; after creation, capture the actual IDs and re-attest every created resource before database/bootstrap effects. Add conflicts for unexpected pre-existing project/container/network/volume and post-create ownership/label/image/network/volume drift. Do not solve this by weakening identity checks or by requiring unknowable future IDs.

2. **HIGH — A2–A6 are synthetic JSON-oracle tests, contrary to the reviewed contract.** Locations: `tests/Support/clean_stand_contract.py:70-96`; all six `tests/Deployment/yii2_clean_stand_*_001_test.py`; `tests/Architecture/yii2_clean_stand_runtime_closure_001_test.py`. The test driver supplies `freshDatabase.ok`, schema version/count, users, container health, HTTP status, heartbeats, every golden-flow result, the complete jobs chain, process commands, and loaded-file conclusion. Tests merely assert those same supplied values. They never require observable command execution, canonical argv/environment, existing migration/provisioning/application entrypoints, database queries, HTTP requests, long-running process observations, or attributable trace artifacts. Replace result booleans with a recording/faulting process boundary whose inputs are low-level command/HTTP/inspection outputs, and assert the exact ordered public calls and independently derived facts. Retain a separately classified, authorization-gated real-disposable acceptance command in the verification plan; fixture-mode GREEN must not itself qualify as `CLEAN_STAND_ACCEPTED` evidence for task 4.2/5.1.

3. **HIGH — admission and no-effect sensitivity is incomplete.** Locations: `specs/YII2-CLEAN-STAND-CUTOVER-001.md:14-26,72-79`; `tests/Deployment/yii2_clean_stand_admission_001_test.py:8-29`; `tests/Support/clean_stand_contract.py:42-69,100-114`. Missing independent cases include malformed/non-canonical manifest and authorization, wrong versions/intent, missing or extra effect authority, malformed/expired authorization, supplied authorization-digest mismatch, authorization ID replay/conflict, operation UUID malformed/duplicate, Compose path mismatch/symlink/content change, mutable or malformed image reference, absent/insecure/symlinked credential reference, unexpected credential keys, per-container/network/volume observed mismatch, and manifest/authorization target aliasing. The current helper always recomputes the authorization digest, so it cannot detect a stale supplied digest. Negative assertions allow exactly one new file but do not compare the pre-existing target tree or a process/effect trace; an implementation can mutate the target and still satisfy them. Add distinct stable reasons and byte/effect snapshots proving every rejection occurs before the first external effect and does not leak credential contents.

4. **HIGH — partial, interruption, replay, and final-result ordering are not covered.** Locations: `specs/YII2-CLEAN-STAND-CUTOVER-001.md:28-40,72-79`; `tests/Deployment/yii2_clean_stand_provisioning_001_test.py:7-17`; `tests/Deployment/yii2_clean_stand_acceptance_result_001_test.py:7-18`. There is only a successful linear fixture and one readiness failure. No test interrupts or fails database creation, prepare, migrations, provisioning, startup, each health boundary, each golden family, jobs, closure, evidence append/fsync, or final publication. Replay is checked only after fully fabricated success and does not prove no second external effects. Add a bounded step/fault matrix proving known facts remain append-only, ambiguous/partial outcomes never publish `accepted.json`, exact replay resumes or returns deterministically without repeating completed mutations, conflicting replay fails closed, and the final report becomes durable only after every required fact. Whole external-effect trace and evidence-tree stability must be asserted, not just return JSON.

5. **HIGH — golden-flow and jobs tests do not bind existing production owners or canonical facts.** Locations: `tests/Deployment/yii2_clean_stand_golden_flows_001_test.py:7-16`; `tests/Deployment/yii2_clean_stand_jobs_001_test.py:7-16`; `tests/Support/clean_stand_contract.py:79-83`. A dictionary key plus `factAppended: true` can pass without login, authorization, Yii HTTP, FKR, construction-control, checklist, OTIZ, scheduler, worker claim/lease, outbox attempt, or history. The jobs chain is a caller-provided list and does not correlate IDs/timestamps/table prefix; heartbeats are caller-provided strings and not proven distinct or fresh. Bind each flow to its landed public seam, exact request/response and independently queried append-only fact identity; prove unauthorized projection byte stability. For jobs, correlate one workload/job/lease/history/outbox identity through canonical configured table names, show worker and scheduler process identities with separate fresh heartbeats, and derive recovery counters through `jobs/health` plus read-only queries.

6. **MEDIUM — runtime closure is self-attested and unaffiliated with executions.** Locations: `specs/YII2-CLEAN-STAND-CUTOVER-001.md:61-70`; `tests/Architecture/yii2_clean_stand_runtime_closure_001_test.py:7-15`; `tests/Support/clean_stand_contract.py:83`. `loadedLegacy: []`, process commands, and `imageHasRapidPilot: false` are accepted directly from the fixture. Nothing proves the image inventory came from the authorized digest, the commands came from the observed containers, or loaded-file traces correspond to the health/login/golden/migration/jobs invocations being accepted. Require raw inventory/inspect/trace observations with candidate, container, invocation, and artifact digests, then derive closure. Include independent rapid-pilot and RuntimeRecovery contamination cases for image, process argv, and each attributable execution class.

7. **MEDIUM — the executable plan does not yet distinguish safe local contract tests from the later authorized real acceptance.** Locations: `openspec/changes/yii2-clean-stand-cutover/verification-input.json`; generated `verification-plan.json`; `openspec/changes/yii2-clean-stand-cutover/tasks.md:26-46`. Every planned command is the synthetic test mode, while the done definition requires a real disposable `CLEAN_STAND_ACCEPTED`. Add an explicit non-default, authorization-required real-disposable command/evidence slot and result classification to the verification input/plan. Gate 3/4 may test orchestration safely, but focused GREEN and full CI must not be reported as the real deployment acceptance, and no CI job may acquire permission to create the owner target.

## Evidence

The retained records are valid `INTENDED_RED` and consistently bind source `2d1b93d861c44728bf53e4af4ff7db8d18eb1abaf55dde62a0d3c37bcad84473` and executable source `9c24436a856a3ed56c78068262b4528f791e4bf6c7e45c41850ab0629339a6ee`. A direct rerun confirmed each failure is `AssertionError: missing-clean-stand-acceptance-public-seam`, not environment setup or an accidental assertion failure. The package snapshot is present and hashes as recorded above.

Harness state reports PR, CI, deployment, and action authorization as `UNKNOWN`/false; none is treated as success or authority. This review ran no Docker, database, deployment, or stand mutation.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gate 2 and correct the root-owned lifecycle/spec and executable matrix without expanding beyond fresh provisioning and acceptance. Retain fresh exact-source RED, rebuild the role package, and resubmit for independent Gate 3. Production implementation must not begin against this package.

---

## Correction review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T082625Z-850a7fe3b9/package.json`.
- Exact reviewed source: candidate `534d6a932fccb077a6eaf7cf3232a19def8685b97aad7322dc2b9aa586e4e35a`, executable source `3ed71f9585e4ce91a0312854619c9b0158417e0590862f7342df5c0ce74ff6b3`, over base `f4dab7d6b576edc3bb6e9578a939e90b9a1f36ae`.
- Snapshot patch SHA-256: `516fc23918c75a53d4b93038a66d0f3b1dff32880c790c53115c3cecd62fb7a3`.
- Correction delta SHA-256: `6d9dec8842620d752e7887a2e894eb3bb1f350cf61cab9bbe78b0c5fc7fae0d6`.
- Verification plan SHA-256: `732e1ebdba1d3be480cce7ac4d0d85fe3056f89c6f6ff5116f621d1adac38260`.
- Reviewer independence is unchanged; this reviewer authored none of the correction artifacts or evidence.

### Resolution assessment

The correction resolves the central lifecycle defect: authorization now binds future names and expected absence, while exact IDs, labels, image, network attachments, and mounts are observed after project creation and before database/bootstrap effects. It also separates recording-driver success (`CLEAN_STAND_CONTRACT_VERIFIED`) from real acceptance (`CLEAN_STAND_ACCEPTED`), adds an authorization-gated real slot, records ordered low-level calls/effects, expands admission, adds failures/interruption/replay, correlates job/outbox identities and canonical double-prefixed tables, and tests raw inventory/process/include contamination.

The fresh records remain honest missing-seam `INTENDED_RED`, consistently bound to candidate source `534d6a932fccb077a6eaf7cf3232a19def8685b97aad7322dc2b9aa586e4e35a` and executable source `3ed71f9585e4ce91a0312854619c9b0158417e0590862f7342df5c0ce74ff6b3`. However, the corrected suite contains stale fixture paths that make the post-implementation test target impossible to reach, and two material sensitivity gaps remain.

### Remaining findings

1. **HIGH — four corrected tests mutate a deleted `steps` fixture tree and will fail after the missing seam is implemented.** Locations: `tests/Deployment/yii2_clean_stand_runtime_001_test.py:17`; `tests/Deployment/yii2_clean_stand_jobs_001_test.py:17`; `tests/Deployment/yii2_clean_stand_golden_flows_001_test.py:16`; `tests/Architecture/yii2_clean_stand_runtime_closure_001_test.py:16`. The v2 fixture stores all raw values under `driver["observations"]`; it has no `driver["steps"]`. Current RED stops on the earlier missing-seam assertion, hiding these `KeyError`s. Change each mutation to its v2 raw observation (`containers`, `jobs`, `golden`, and an existing raw closure observation respectively), then obtain fresh RED proving it still fails at the absent seam rather than at test setup. The first closure negative is redundant with the three valid raw contamination cases and may be removed instead of inventing a derived `loadedLegacy` input.

2. **HIGH — admission still does not cover several independent trust boundaries returned in the first review.** Locations: `tests/Deployment/yii2_clean_stand_admission_001_test.py:9-38`; `tests/Support/clean_stand_contract.py:19-36`. There is no malformed operation/authorization UUID, manifest `expectedAbsent` mismatch, Compose symlink, missing/insecure/symlinked credential file, independent neighbor overlap, individual unexpected container/network/volume name, authorization-id conflict before effects, or manifest/authorization aliasing case. `PRODUCTION_OVERLAP` is one opaque driver string and cannot prove production versus neighbor/resource-class handling. Add representative separate public-seam cases with stable reasons and unchanged target/effect trace. This remains bounded admission work already required by A1.

3. **MEDIUM — attributable closure evidence is still insufficiently bound.** Locations: `tests/Support/clean_stand_contract.py:28-30`; `tests/Architecture/yii2_clean_stand_runtime_closure_001_test.py:8-29`. The raw include rows have only invocation, container ID, and files; the test verifies the invocation set and clean paths but not source/image/operation, trace artifact digest, or expected container affiliation. Thus traces from a different candidate/operation or an unrelated container can be replayed and accepted. Add those bindings to each observation and independent mismatch cases. Also require recording mode to be rejected by the real `--authorization-package` path, ensuring the separate real slot cannot produce `CLEAN_STAND_ACCEPTED` from `FMONITOR_CLEAN_STAND_RECORDING_DRIVER`.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked. Correct the root-owned tests/fixture only, retain fresh exact-source intended RED, rebuild the package, and resubmit. No production implementation or live clean-stand operation is authorized.

---

## Final correction review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T083138Z-7bdf48b5c5/package.json`.
- Exact reviewed source: candidate `d46fbcf5394e9e1d87d4a2cd8b627be62c8236d6192e45a017808f8b0073a9be`, executable source `890ed2b87371019802e3b777081fa1c54e8eec20f5c76d05d31d9c8631f34043`, over base `f4dab7d6b576edc3bb6e9578a939e90b9a1f36ae`.
- Snapshot patch SHA-256: `bd24f2d0467b7f824598a4aa8d15c49316c3f163020ef493a4d4b7699a9c21aa`.
- Correction delta SHA-256: `c960929d97608c3c81b1ec3497c2b950ae7b1d09fde0108c7113b006d601652f`.
- Verification plan SHA-256: `df2296eeecf4f294343e0b9f43fb17512770f05daefec7aca229f7af6ab43c76`.
- Reviewer independence is unchanged.

### Finding disposition

All remaining Gate 3 findings are resolved.

- The four stale `driver["steps"]` mutations now target actual v2 raw observations, and the redundant derived closure mutation was removed. The post-implementation negative paths are therefore reachable rather than hidden `KeyError`s.
- Admission now independently exercises malformed authorization/operation UUIDs, expected-absence failure, Compose symlink, credential missing/mode/symlink, production and neighbor overlap, unexpected container/network/volume, target aliasing, and authorization replay conflict. Every case continues to require an empty external-effect trace, unchanged target sentinel tree, and no secret disclosure.
- Include traces now bind source, image, operation, artifact digest, invocation class, and expected container identity. Independent mutations cover each binding as well as image inventory, process argv, and loaded-file contamination.
- The authorization-package seam explicitly rejects recording/test mode as `RECORDING_DRIVER_FORBIDDEN`; recording-driver success remains `CLEAN_STAND_CONTRACT_VERIFIED`, never `CLEAN_STAND_ACCEPTED`.

The earlier two-phase identity, ordered effect trace, post-create gating, partial/interruption/replay matrix, canonical job/outbox correlation, and non-default authorization-gated real acceptance slot remain intact. The recording matrix is an orchestration contract, while actual `CLEAN_STAND_ACCEPTED` remains contingent on the separately authorized real-disposable execution; focused/CI GREEN cannot be substituted for it.

All eight retained commands are fresh honest missing-seam `INTENDED_RED`, with matching source `d46fbcf5394e9e1d87d4a2cd8b627be62c8236d6192e45a017808f8b0073a9be` and executable source `890ed2b87371019802e3b777081fa1c54e8eec20f5c76d05d31d9c8631f34043`. No findings remain for the agreed Gate 3 scope.

### Final verdict

`APPROVED`

Gate 4 may proceed against this exact reviewed contract and test matrix. Refresh the executor package so implementation is bound to this source. This approval does not authorize creation of a disposable stand, real acceptance, production cutover, restore, rollback, or reconciliation; CI and deployment remain `UNKNOWN`.

---

## Post-approval fixture-ownership correction — 2026-09-14

- Scope: only the `copy.deepcopy` ownership correction in `tests/Support/clean_stand_contract.py`, relative to the approved Gate 3 package `20260914T083138Z-7bdf48b5c5`.
- Corrected helper SHA-256: `c760f8bf2636c5bbe105423730614576f4fbe439e24c5fab03d4c5c524764686`.
- Reviewer independence is unchanged. The paused executor's acceptance-seam implementation and any resulting GREEN were explicitly excluded from this test-delta verdict.

### Assessment

No findings. The approved fixture reused mutable global `NAMES` for manifest, authorization, and recording-driver pre-create observation, and reused mutable `IDS` subtrees for post-create IDs and mounts. Consequently, mutating one side of a conflict could silently mutate its expected counterpart and erase the test's mismatch.

The correction imports `copy` and independently deep-copies all five ownership boundaries: manifest names, authorization names, pre-create observed names, post-create IDs, and post-create observed volume mounts. Direct identity checks confirm these structures no longer alias. Mutating the authorization database now leaves the manifest database unchanged, and mutating a mounted volume ID leaves the independently observed resource-ID map unchanged. This is exactly the sensitivity repair required; it changes no contract, expected result, production code, public seam, or stand state.

### Narrow test-delta verdict

`APPROVED`

The executor may continue against the corrected root-owned fixture. Fresh exact-source focused evidence and Gate 5 review remain required. This verdict authorizes no deployment or other stand action.

---

## Acceptance-only topology contract correction review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T085545Z-4a9a04037d/package.json`.
- Exact reviewed source: candidate `036d89df98088286158389eb3221334f3ddc9da48b6c7342cca0f0d0f21829d6`, executable source `0b2683617a41f15220bbd89fe78a3a1deff696e1b4b137b5db59f4617b62f9e3`, over base `f4dab7d6b576edc3bb6e9578a939e90b9a1f36ae`.
- Snapshot patch SHA-256: `956f5ba30b5c89056db329bacc2bb997aa65a808f99d85917e697e96c75ce3f6`.
- Correction delta SHA-256: `17a96bdc1beb536d6a05473c80b7f6f69373cfae5a3976e5802dd06de4cfeb5a`.
- Verification plan SHA-256: `58e4485a682e1daab241895c58eda486355a6f757fede42b31d6fb4b39037055`.
- Reviewer independence is unchanged. No production implementation or live stand action was reviewed or performed.

### Assessment

The owner-authorized contract correction is in scope. It permits private acceptance-only setup, enqueue, and include probes so the real disposable run can seed representative state and observe runtime closure without adding production routes, commands, or image content. The stable spec, delta spec, design, planned paths, and verification input agree on that boundary. Previously reviewed acceptance tests remain GREEN; the new isolation test is an honest `INTENDED_RED` because all four acceptance-only artifacts are absent, rather than because Docker or a live target is unavailable.

### Finding

1. **HIGH — the isolation test does not prevent the override from modifying production services or exposing acceptance capabilities inside the normal topology.** Location: `tests/Architecture/yii2_clean_stand_acceptance_isolation_001_test.py:4-21`; contract: `specs/YII2-CLEAN-STAND-CUTOVER-001.md:89-93`. The test accepts any file containing four strings and no literal `ports:`. An override can redefine `web`, `php`, `jobs-worker`, or `jobs-scheduler` command/image/mounts/environment; attach setup credentials or scripts to those services; publish via `network_mode`, `expose`, host mounts, or another YAML form; or add the acceptance profile to a production service, while this test remains GREEN. Lexically checking the original production files cannot detect such merged-Compose changes. The three adapter checks also prove only rejection when every `FMONITOR_CLEAN_STAND*` variable is absent, not binding to the exact operation/target/private principal.

   Make the oracle structural/behavioral: parse or render the merged production+override Compose model and require the canonical production services' image, command/entrypoint, mounts, environment/secret references, networks, healthchecks, and publication configuration to equal the production-only model. Require the override to add only an explicit allowlist of dedicated acceptance services/profile, with no host publication and only the minimum read-only support mounts/private credential references. Execute each adapter with missing and conflicting operation/target/context bindings, not merely a completely clean environment, and require fail-closed behavior. These corrections stay within the already approved acceptance-support scope and do not require a new production interface.

### Verdict

`CHANGES_REQUESTED`

The acceptance-only contract is acceptable, but Gate 4 for this correction is blocked until its isolation test is sensitive to merged-topology drift and exact-context conflicts. Retain fresh exact-source RED and resubmit the narrow test package. Existing Gate 3 approval for the earlier acceptance application remains historical; it does not approve these new topology/adapters.

---

## Strengthened acceptance-isolation rereview — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T085933Z-249165895b/package.json`.
- Exact reviewed source: candidate `cbf74fd324322f8576d93aba03bff41ad8d92f6ec3a2c725933ce2723e4b6115`, executable source `08abcbe52f657043d69063e872e59cfa61cfbe122b3a107b9aa66219ce4d3ad7`.
- Snapshot patch SHA-256: `da5f10a3ab69404814837714a90abe91df0bddfd66caf890eab7a9e361344b26`.
- Correction delta SHA-256: `775aa124e900a24434f76062513321e926b09898b7795b7d273b871e927d6629`.
- Verification plan SHA-256: `dca9f520b61c191d800253c98a760f8577b278b7ae323856c23b488498d5ac23`.
- Reviewer independence is unchanged; no implementation or stand operation was performed.

### Resolution assessment

The correction now structurally limits instrumentation of `php`, `jobs-worker`, and `jobs-scheduler` to one exact read-only probe mount and an exact three-variable environment delta. It forbids override ownership of their image, build, command, entrypoint, publication, dependency, health, security, network, secret, and user fields. Adapter checks now cover missing context and independent operation, target, and context-digest conflicts. The retained isolation result is an honest missing-artifact `INTENDED_RED`; all previously reviewed mapped tests are GREEN on the same exact source.

### Remaining finding

1. **HIGH — dedicated acceptance services still have an unchecked mount/credential/image escape hatch.** Location: `tests/Architecture/yii2_clean_stand_acceptance_isolation_001_test.py:17-23`; contract: `specs/YII2-CLEAN-STAND-CUTOVER-001.md:89-93`. The test checks only a subset allowlist and applies read-only validation only to mount entries that are already dictionaries. A candidate override can use a string mount such as `/:/host`, add arbitrary writable named/tmpfs mounts, inject unrelated environment or secret material, choose a different image, or omit the private context/setup-principal bindings, and still pass lines 18–23. That does not prove “only the minimum read-only support mounts/private credential references” or exact-candidate isolation.

   Require an exact schema for each dedicated service: exact immutable runtime image reference, exact command/profile/restart/network mode/dependency, exact environment key allowlist with private file references, and an exact mount allowlist. Reject string-form mounts; require every bind to be read-only and each source/target to be one of the explicitly authorized support/context/credential paths. Forbid extra named volumes/tmpfs/devices and any direct reusable credential value. This is the remaining half of the previously requested structural isolation oracle, not new scope.

### Verdict

`CHANGES_REQUESTED`

Gate 4 for the acceptance-only topology remains blocked on this narrow dedicated-service isolation correction. Existing acceptance implementation approval is unaffected, but these new adapters must not be implemented until the root-owned test closes the escape hatch and fresh exact-source RED is retained.

---

## Final exact-service-schema isolation review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T090238Z-62d19026ab/package.json`.
- Exact reviewed source: candidate `3001cd5c7d0e8b65f825e2d40663c4f678310b05fde2d4add5859040869fa3b2`, executable source `3c3eb27a05595855d7bbf3f56b3cda66232432bf5b36ba366c8bfe1f9cdf07cf`.
- Snapshot patch SHA-256: `35da5e108cff4f225855b27a3998d1fd9ca6b76a43ec30b2fddbd85edbab0b09`.
- Correction delta SHA-256: `0f6f1afdc6a562df5de7c55f272426119d167abda37a51380f0659aa23769793`.
- Verification plan SHA-256: `419c14c1e32401ea01671053b594ab17bf6cf367a0d0ba650d25388387f02c10`.
- Reviewer independence is unchanged; no implementation or stand action was performed.

### Finding disposition

The sole remaining isolation finding is resolved. Each dedicated acceptance service must now equal a complete expected mapping: the explicit immutable runtime-image reference, exact PHP script command, acceptance-only profile, private file-reference environment, exact healthy/completed dependencies, `service:db` network namespace, no restart, read-only root filesystem, no-new-privileges, and exactly three dictionary-form read-only binds for its one script, exact context, and private database credential file. Extra keys, string mounts, writable/named/tmpfs mounts, arbitrary environment, alternate image, or missing context/principal bindings all make equality fail.

The production-service deltas remain restricted to exact probe/context mounts and exact instrumentation environment, with all non-instrumentation topology owned by the unchanged production Compose. Exact-context negative execution still covers absent, operation-conflicting, target-conflicting, and digest-conflicting adapter invocation.

The fresh isolation record is honest `INTENDED_RED`, bound to source `3001cd5c7d0e8b65f825e2d40663c4f678310b05fde2d4add5859040869fa3b2` and executable source `3c3eb27a05595855d7bbf3f56b3cda66232432bf5b36ba366c8bfe1f9cdf07cf`; it fails because the four isolated support artifacts are absent. The eight previously approved mapped checks remain GREEN on the same exact source. No findings remain in this correction scope.

### Final correction verdict

`APPROVED`

Gate 4 may implement the acceptance-only Compose override and private adapters against this exact schema. This approval creates no production interface and authorizes no Docker, database, deployment, or stand mutation. Gate 5 and real owner authorization remain mandatory.

---

## PHP probe activation correction review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T090805Z-40acd24823/package.json`.
- Exact reviewed source: candidate `201f7a67cd5bda96f48dd19d94e2801cb0688ee768d91a7cff8424745d8f4129`, executable source `7cd9350965e5f301269c5de55819f671d87923e65a7fa517505da232cfdb9b38`.
- Snapshot patch SHA-256: `3115385d0eeb16a4aee51592bae774b1526b278e0f864a73ac019b5b1061ee37`.
- Correction delta SHA-256: `c32f8c67697d81a59f81e3ff7b152034ac9f9ca39b334bd6692013f4977be13a`.
- Verification plan SHA-256: `695bbbb09bf0284cfe0b3dd43e018f32d49edfc495921b7c5342b6b8dba9e18c`.
- Reviewer independence is unchanged; no implementation or stand action was performed.

### Assessment

No findings. The correction removes the ineffective Compose `PHP_VALUE` environment assumption and specifies a mechanism PHP CLI/FPM will actually consume: an exact read-only bind of `99-acceptance-probe.ini`, whose sole directive is `auto_prepend_file=/run/fmonitor-acceptance/include-probe.php`, plus `PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/run/fmonitor-acceptance-ini`. Keeping the production `conf.d` directory first preserves the image's production PHP configuration while appending only the isolated acceptance probe.

The structural oracle still requires exact environment and volume equality for `php`, `jobs-worker`, and `jobs-scheduler`; the added context and ini mounts are read-only and acceptance-scoped. The ini content is asserted byte-for-byte, the support path is added to the verification binding, and no production Compose/image/config/public interface is changed by the contract. The fresh isolation run is honest `INTENDED_RED` because the acceptance ini/topology artifacts are absent; the other eight mapped checks remain GREEN on the same exact source.

### Verdict

`APPROVED`

Gate 4 may implement this exact acceptance-only ini activation mechanism together with the previously approved isolated adapters. This verdict authorizes no live container, database, deployment, or stand action.
