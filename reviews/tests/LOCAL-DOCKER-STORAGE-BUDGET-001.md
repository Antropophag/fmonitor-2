# LOCAL-DOCKER-STORAGE-BUDGET-001 — Gate 3 test review

- Reviewer: `/root/docker_growth_gate3` (independent reviewer; authored none of the reviewed specification, OpenSpec artifacts, tests, production code, or RED evidence).
- Review date: 2026-09-26.
- Test author: `/root`, as recorded in `docs/operations/local-docker-storage-budget-delivery.md`.
- Reviewed source: base `708e0a6db6cf7075e392ddc3814e6234672123da` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T083206Z-4d0057ef6a/snapshot/source.patch`, SHA-256 `ede5f354d6abc6947c377e809ed7a309fd95e6b51f204b85a06677aad1c6815d`; candidate source `b30c867ebb030b7705a8612b7fa9939404a2b91b8e6a68d903348c6c7d3acecb`.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T083206Z-4d0057ef6a/package.json`.
- Verification plan: SHA-256 `43d01c375bc84d24dd11f7625f041117a007763d29e2303ae40000160441953f`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Specification: `specs/LOCAL-DOCKER-STORAGE-BUDGET-001.md`, SHA-256 `c324057b6f6e761bca68e94f92771aefba9d598f8efc9a0f5ac3bf9b20f62b04`.
- Executable test: `tests/Verification/local_docker_storage_budget_001_test.py`, SHA-256 `fdc5c5319f3f1cac49014262e9161a6b58ecab0fcd980b7c0fb375fe56f60c46`.
- Public seam: `tools/delivery/run-in-profile` and its owning Docker storage guard; applicable repository-owned disposable Compose lifecycle.
- RED command: `python3 tests/Verification/local_docker_storage_budget_001_test.py`; retained evidence exits `1` at `INTENDED_RED: owning Docker storage guard is absent`.
- Agreed scope: first complete review of the root-authored normative A–N contract, OpenSpec delta, executable RED matrix, and package-bound evidence. No prior findings.
- Verdict: **CHANGES_REQUESTED**.

## Findings

1. **HIGH — the normative contract gives mutually exclusive requirements for ephemeral-volume teardown.** `specs/LOCAL-DOCKER-STORAGE-BUDGET-001.md:43-45` says that no automatic path in the slice may remove a volume, while `:55-61` requires an automatic `down --volumes --remove-orphans` after success, failure, INT, and TERM for applicable disposable callers. The OpenSpec requirement at `openspec/changes/bound-local-docker-growth/specs/delivery/local-docker-storage-budget/spec.md:48-61` clearly distinguishes exact-project ephemeral teardown from forbidden broad cleanup, so an executor cannot conform to both current normative statements. Correct H to forbid global/unowned volume removal while explicitly allowing only the exact-project lifecycle teardown defined by K/L; retain the persistent/foreign exclusion.

2. **HIGH — A/B's core dependency identity and execution provenance are not behaviorally tested.** `tests/Verification/local_docker_storage_budget_001_test.py:116-128` only searches implementation text for lockfile names and checks one `image=` assignment string. It never runs `run-in-profile` across two source revisions and a changed dependency input, never proves all image-content inputs (including the Dockerfile) participate in the digest, and never checks the completed-run fields required by B: current Git SHA, immutable image ID, profile, command status, and duration. An implementation can hard-code the searched strings, omit a dependency from hashing, reuse a stale image, or emit no honest provenance and still pass. Replace the source-text oracle with isolated fixture copies or injected source/dependency seams: run two source-only revisions and one dependency-byte revision, assert same/different exact image identities as specified, assert exactly one guarded build/run, and validate all required provenance fields plus the three exact labels from observable fake-Docker argv/output.

3. **HIGH — the executable guard matrix omits several mandatory fail-closed and maintenance cases and does not prove the promised full argv/order.** Although N at `specs/LOCAL-DOCKER-STORAGE-BUDGET-001.md:67-69` explicitly requires capability and lock errors, idempotency, full argv/order, and A–M coverage, the test has no unsupported Docker/Buildx capability case, no lock-acquisition failure, no negative measurement, no invalid/boundary override matrix, and no two sequential no-op maintenance witness. Cleanup assertions at `tests/Verification/local_docker_storage_budget_001_test.py:137-151` check token membership/counts, not the complete ordered calls, and the diagnostic case does not assert host free bytes, Docker image/cache/volume totals, configured limits, or automatic-versus-manual classification. Add deterministic cases for every listed rejection and boundary, exact ordered argv assertions (including no Docker effects before validation), repeated no-op behavior with identical selectors/budgets, and the complete diagnostic schema. Also define exact accepted override bounds in Gate 1; “numeric, bounded”/“reasonable bounds” does not independently determine expected values.

4. **HIGH — K/L and the OpenSpec disposable-lifecycle scenarios have no executable witness.** The test ends after guard/doctor checks and contains no fake Compose caller, trap, signal, or `down --volumes --remove-orphans` assertion. This misses exact isolated project identity, exactly-once teardown after success, failure, INT and TERM, preservation of the primary status, separately observable cleanup failure, hostile ambient project values, and exclusion of the persistent stand. The repository already has a Compose path in `tools/delivery/run-in-profile`; the delivery inventory's conclusion that current `compose.test.yaml` uses tmpfs does not satisfy N's explicit success/failure/signal teardown matrix or prove the existing teardown remains safe. Either narrow K/L and N coherently through a reviewed Gate 1 decision if no caller is applicable, or add an isolated fake-Compose lifecycle harness covering every stated outcome and exact argv without creating real Docker resources.

## RED evidence assessment

The retained evidence is source-bound and credibly demonstrates the first missing owning guard rather than a setup failure. The referenced focused log records exit `1` and the exact `INTENDED_RED` assertion; the governance planner test is separately recorded GREEN after a permission-only fixture correction. Early failure is acceptable for a complete pre-implementation matrix, but it cannot compensate for assertions that are absent from the test body. After correcting the contract and matrix, prepare a fresh exact-source package and retain a fresh intended RED before Gate 4.

## Required changes

- Reconcile H with K/L and specify exact override bounds.
- Replace static A/B source checks with observable identity, invalidation, build/run, label, and provenance behavior.
- Complete the guard rejection, idempotency, diagnostic, and full argv/order matrix.
- Add applicable disposable success/failure/signal/persistent-boundary witnesses, or obtain and encode a coherent Gate 1 narrowing before changing those expectations.
- Regenerate the verification plan/package and request independent Gate 3 rereview against fresh source-bound RED evidence.

Gate 4 remains blocked. Production implementation, final review, CI, publication, merge, deployment, Docker settings changes, and destructive Docker actions are outside this verdict.

---

## Gate 3 correction rereview — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the corrected specification, OpenSpec artifacts, tests, production code, or evidence.
- Refreshed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T083935Z-00f7766832/package.json`.
- Exact corrected candidate source: `fb927a90d836e9453efa37ac0a4a2f450aaa9156aef4fb3fa1ff3e8b6189989c`; executable source: `d2ffb793d419c13899d71d3ab6b00815e4cdfd0f68b49c1ccbe0bbe91462b642`.
- Snapshot: base `708e0a6db6cf7075e392ddc3814e6234672123da` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T083935Z-00f7766832/snapshot/source.patch`, SHA-256 `5f5f57f5a1278386651be6517209fb0e5f919bf1369953f992ae6b2f53a4fbfa`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T083935Z-00f7766832/delta.patch`, SHA-256 `e68d35d96c95776e4a1b281bceb79ecc12d109b7964ba1f5e9358ae07a192fda`.
- Verification-plan SHA-256: `2b5cc08955072087163d134e9997056e01ef61693a141d9da0979bb295061349`; planner decision remains `CRITICAL`, required reviews `gate3` and `final`.
- Corrected specification SHA-256: `45bc388d124695d83f7c1e507b6c2e73834698a250d111837ee3cbac98074814`; corrected test SHA-256: `5d9ee67d2c51e63c85f13770d8ce02a0874905ccf28b71a3a338d9a68c6664e6`.
- Verdict: **CHANGES_REQUESTED**.

### Prior findings disposition

1. **Fixed.** H now confines the no-volume-removal rule to storage-guard/global cleanup and expressly identifies K/L's validated exact-project disposable teardown as the sole volume exception. The normative contract and OpenSpec now agree while retaining persistent/foreign exclusion.

2. **Open (partially fixed).** The correction replaces the static string oracle with real isolated `run-in-profile` executions, proves a source-only commit does not change the tag, proves one `composer.lock` change does change it, checks one build/run, labels, immutable image ID, and successful-result provenance. It still does not prove the contract's **every dependency input** condition: `Dockerfile.focused-checks` copies `composer.json`, `composer.lock`, `pyproject.toml`, and `uv.lock`, and its result also depends on the Dockerfile, dockerignore, and pinned `dependencies.env`, but the test mutates only `composer.lock`. An implementation that hashes only `composer.lock` passes the entire corrected A matrix. The provenance loop also exercises only exit `0`, so a runner that always reports `exit_code: 0` while returning a failed child status can pass B.

3. **Fixed for the returned scope.** The corrected matrix adds exact accepted/rejected override bounds, empty/negative/non-numeric measurements, unsupported Buildx, lock failure, cleanup failure, sequential no-op, concurrent lock/recheck, exact principal argv/order, and the required diagnostic fields with redaction/manual ownership. Stable low-space reason and early no-effect rejection are observable. No new blocker was found in this correction group.

4. **Fixed.** The new fake-Compose public-seam matrix checks validated exact project/file argv, exactly-once teardown, successful and failed operations, successful and failed cleanup, INT/TERM, preservation of the primary failure status, separately reported cleanup status, hostile ambient project isolation, and persistent-project rejection without real Docker effects.

### Complete correction findings

1. **HIGH — A/B remains insensitive to incomplete dependency hashing and dishonest failed-command provenance.** At `tests/Verification/local_docker_storage_budget_001_test.py:225-252`, the fixture copies multiple dependency inputs but changes only `composer.lock`, then validates result fields only for the two successful source-only runs. This does not close prior finding 2. Mutate each independently identified image-content input with distinct bytes—at minimum Dockerfile, dockerignore, `dependencies.env`, `composer.json`, `composer.lock`, `pyproject.toml`, and `uv.lock`—and require a different deterministic tag for each, while retaining a source-only same-tag control. Add a non-zero fake child run and require both process status and `RUN_IN_PROFILE_RESULT.exit_code` to preserve that exact value, with the other provenance fields still present. The expected dependency set must come from the reviewed contract/fixture, not be discovered from implementation output.

### Fresh RED assessment

The refreshed record is bound to candidate `fb927a90d836e9453efa37ac0a4a2f450aaa9156aef4fb3fa1ff3e8b6189989c`, exits `1`, and retains the exact first failure `INTENDED_RED: owning Docker storage guard is absent`. This remains a credible missing-behavior RED rather than setup failure. The expanded matrix is deterministic and uses fake Docker/Compose, but fresh RED cannot approve the still-absent A/B assertions above.

### Correction verdict

**CHANGES_REQUESTED**

Gate 4 remains blocked only on the complete dependency-input invalidation matrix and failed-command provenance witness. Preserve all resolved cases, capture fresh exact-source RED, regenerate the package, and request bounded Gate 3 rereview.

---

## Gate 3 third review — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the reviewed artifacts or evidence.
- Refreshed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T084214Z-c7f2572d71/package.json`.
- Exact candidate source: `d28b9db154821195849fe8104b1f76bb6132cb92368f6b79955af97b482b7e72`; executable source: `7a0b2b77967254225241f44c79a5dba3c91489b2b964846725ae5fe4d9875e36`.
- Snapshot: base `708e0a6db6cf7075e392ddc3814e6234672123da` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T084214Z-c7f2572d71/snapshot/source.patch`, SHA-256 `4b9293be78b8cb347bb44f9fb01421bbf5a4a4e94c9ccd5f48e8e2e9ae74422b`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T084214Z-c7f2572d71/delta.patch`, SHA-256 `d1d4634ac33051e2f8b0ee7fd2655e6e0971a490b5755935eb81b302598c9441`.
- Verification-plan SHA-256: `0265e52e7ad9d192c8739d4479f2f64069dc55c668d9140f76513ea80bb21072`; planner decision remains `CRITICAL`, required reviews `gate3` and `final`.
- Corrected test SHA-256: `f5f6d5ec80ee12d480b4694ab4ebd76334520d03be766dc863d59909a721fe18`.
- Verdict: **CHANGES_REQUESTED**.

### All findings disposition

1. **Original F1 remains fixed.** H and K/L have coherent, disjoint cleanup ownership.
2. **Original F2 remains open.** Failed-child provenance is now fixed: the candidate requires process/result exit `37`, current Git SHA, immutable image ID, profile, duration, and one build/run. The seven-input invalidation correction is not executable as written and names the wrong dockerignore, as detailed below.
3. **Original F3 remains fixed.** No delta changed the accepted guard/configuration/diagnostic matrix.
4. **Original F4 remains fixed.** No delta changed the accepted disposable lifecycle matrix.

### Complete third-review findings

1. **HIGH — the seven-input correction has a deterministic fixture setup failure and tests the wrong dockerignore.** `tests/Verification/local_docker_storage_budget_001_test.py:226-235` does not copy `tools/delivery/Dockerfile.focused-checks` into the isolated repository. The first loop iteration at `:248-255` therefore attempts `read_text()` on a nonexistent fixture file and will fail before observing public-runner behavior once the earlier missing-guard RED is implemented. The fixture copies and mutates root `.dockerignore`, but the focused recipe's actual dedicated input is `tools/delivery/Dockerfile.focused-checks.dockerignore`; the root file is not the reviewed focused-image input. Copy both exact focused files into `repo/tools/delivery`, enumerate the dedicated dockerignore path, and run each mutation from the same clean baseline (or otherwise prove only one input differs for every case). Require every isolated mutation to change the baseline tag while the source-only control reuses it. Retain the now-correct exit-37 provenance witness.

### Fresh RED assessment

The fresh evidence is correctly bound to candidate `d28b9db154821195849fe8104b1f76bb6132cb92368f6b79955af97b482b7e72`, exits `1`, and records the expected missing-guard RED. It cannot expose the later fixture error because line 129 deliberately stops first. Thus it is credible RED for the missing production seam but not evidence that the corrected dependency matrix is runnable.

### Third-review verdict

**CHANGES_REQUESTED**

Gate 4 remains blocked on the fixture-owned correction above. This is the same unresolved F2 cause returned a third time; rebuild and execute the complete matrix through the intended first behavior failure before another review package. Production implementation, full local suites, and destructive Docker actions remain outside this verdict.

---

## Gate 3 rebuilt-matrix review — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the reviewed specification, lifecycle artifacts, tests, production code, or evidence.
- Refreshed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T084440Z-8d05bc6a7a/package.json`.
- Exact candidate source: `739c5d27ebd1f3a15540ffce08f0569dbfc3d6da5d11df276a65e70f4843a12b`; executable source: `7a6e10b07b19ec7671b8804d8b8b4ac6d2ac2bfe2262bbc1e7326e7115131cbb`.
- Snapshot: base `708e0a6db6cf7075e392ddc3814e6234672123da` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T084440Z-8d05bc6a7a/snapshot/source.patch`, SHA-256 `c64aa7e9892cc74fcd83b47c4d89db2b8262e6267d1145e3868a96cc02bb0073`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T084440Z-8d05bc6a7a/delta.patch`, SHA-256 `8a43b997a217cf125369fcb9516288aa6d6698669de317f5e6d0bff923bb994e`.
- Verification-plan SHA-256: `7dd3868aa7bafc29770949325103ccc22274dd50046e5ca6cdb4d53b21522d68`; planner decision remains `CRITICAL`, required reviews `gate3` and `final`.
- Normative specification SHA-256: `45bc388d124695d83f7c1e507b6c2e73834698a250d111837ee3cbac98074814`; rebuilt test SHA-256: `4366c0d64cd320432c9a9448664893c097d2bb90069805038781fad749183cc7`.
- Verdict: **APPROVED**.

### Complete findings disposition

1. **Original F1 fixed.** H limits storage-guard/global cleanup and K/L exclusively owns validated exact-project ephemeral teardown.
2. **Original F2 fixed.** The fixture now copies the complete canonical `tools/delivery` module, including `Dockerfile.focused-checks` and `Dockerfile.focused-checks.dockerignore`. Seven separate temporary Git fixtures each establish a clean baseline public run, mutate exactly one contract-enumerated input—recipe, dedicated dockerignore, pins, Composer manifest/lock, or Python manifest/lock—and require a different exact tag. The separate source-only commit requires tag reuse. Successful provenance retains Git SHA, profile, immutable image ID, status, duration and one build/run; the independent failed-child case requires exact process and result status `37` with the same provenance fields and one build/run.
3. **Original F3 fixed.** Exact bounds/rejections, capability and lock errors, cleanup failure, idempotency, concurrent recheck, complete diagnostic schema/redaction, and principal argv/order remain unchanged and covered.
4. **Original F4 fixed.** Exact-project disposable success, failure, cleanup failure, INT/TERM, hostile ambient project and persistent-project exclusion remain unchanged and covered.
5. **Third-review fixture finding fixed.** The missing recipe and wrong root dockerignore are gone; every dependency case now starts from its own complete clean fixture, so setup failure or cumulative cross-input sensitivity cannot masquerade as the required invalidation.

### Fresh RED and assessment

The retained focused record is bound to exact candidate `739c5d27ebd1f3a15540ffce08f0569dbfc3d6da5d11df276a65e70f4843a12b`, exits `1`, and fails at `INTENDED_RED: owning Docker storage guard is absent`. The required fixture inputs are now structurally present before that guard, and the remainder of the matrix uses only isolated fake Docker/Compose and temporary Git repositories. Expected values come from the normative A–N contract rather than planned production structure. No blocking findings or new delta risks remain.

### Final Gate 3 verdict

**APPROVED**

Gate 3 passes for exact source `739c5d27ebd1f3a15540ffce08f0569dbfc3d6da5d11df276a65e70f4843a12b`. Gate 4 may proceed against this reviewed package without changing the approved specification or test expectations. Production implementation, focused GREEN, final review, exact-source CI, publication, merge, deployment, host settings, and destructive Docker actions remain outside this approval.

---

## Gate 3 refresh after final-review A/B gap — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the specification, refreshed tests, implementation, final-review findings, or evidence.
- Refreshed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T090604Z-e9550236c9/package.json`.
- Exact candidate source: `a19555fb0cfe7910a70614fa0554e2fc4e5198c38cb7abf0b97a06490c09b3ae`; executable source: `c2f44e967df765a6fb1970cb165e33e8f41d42e65cfdf4cf6f5d3f7f06b4d8da`.
- Snapshot: base `708e0a6db6cf7075e392ddc3814e6234672123da` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T090604Z-e9550236c9/snapshot/source.patch`, SHA-256 `15dc3fca682e7ac6397779cea7335a4200afae6a9ec4dc000ee196a33cc7411e`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T090604Z-e9550236c9/delta.patch`, SHA-256 `4b2fea1b86fb057eb50fe842ac055319c0507adb67305eeb95c020fbb7169ddf`.
- Verification-plan SHA-256: `4d7c080058ebb1a2a8c2a1d560f90b7c22a1f33344e9fc24acc793cc798b366d`; planner decision remains `CRITICAL`, required reviews `gate3` and `final`.
- Refreshed test SHA-256: `3b8df8a15ac012fb37c8b98da3b017b13e382ed54577194f7d768201ed952d0a`.
- Verdict: **CHANGES_REQUESTED**.

### Prior findings disposition

1. Original F1 remains fixed; cleanup ownership is unchanged.
2. Original dependency-input/provenance F2 remains fixed for the previously approved seven-input invalidation and exit-37 result fields.
3. Original F3 remains fixed; guard/configuration/diagnostic coverage is unchanged.
4. Original F4 remains fixed; disposable lifecycle coverage is unchanged.
5. The rebuilt fixture finding remains fixed; complete isolated fixtures and clean-baseline mutations are unchanged.

### Refresh findings

1. **HIGH — the new A/B assertions do not bind the reported source to the mounted bytes and can be bypassed by equivalent source-specific build inputs.** At `tests/Verification/local_docker_storage_budget_001_test.py:245-255`, equal `image_digest` is produced by the fake's tag-only `image inspect` behavior, while `source_digest` is checked only for inequality. The mount assertion accepts any `type=bind,src=<anything>,dst=/workspace,readonly`; it never resolves `src`, proves that it is the runner's frozen/materialized snapshot, checks its `marker.txt` against source A/B, or derives the reported `source_digest` from those mounted bytes. A runner can mount an unrelated directory and independently report the current checkout digest while passing. The build-argv rejection bans only literal `EXECUTABLE_SOURCE` and `org.opencontainers.image.revision`; alternate source-derived args/labels such as `GIT_SHA`, `SOURCE_DIGEST`, or `org.fmonitor.source=<digest>` pass. The Dockerfile check bans only the exact substring `COPY . /workspace`; equivalent `COPY ./ /workspace/`, JSON-form COPY, or `ADD . /workspace` embeds source and passes.

   Correct the fake/public oracle to parse the single `/workspace` mount, require an absolute existing read-only source path distinct from the ambient checkout, verify the expected A/B marker and compute/compare the reported executable-source digest from that exact mounted tree. Require neither the current Git SHA nor the computed source digest to occur anywhere in build argv, regardless of option name, while retaining equal tag and immutable image ID. Parse normalized Dockerfile COPY/ADD instructions (including shell/JSON forms) or use an equivalent robust context witness so a broad source-tree copy into `/workspace` is rejected without relying on one spelling. This keeps dependency-manifest COPY instructions allowed while rejecting the current-source embedding that Gate 5 found.

### Fresh RED assessment

The retained record is source-bound to candidate `a19555fb0cfe7910a70614fa0554e2fc4e5198c38cb7abf0b97a06490c09b3ae`, exits `1`, and is an intended behavior RED. It establishes that the corrected implementation is missing, but the first failing assertion cannot compensate for the mount/build-input/COPY alternatives that are absent from the refreshed test body.

### Refresh verdict

**CHANGES_REQUESTED**

Gate 4 correction remains blocked until the A/B public oracle proves the exact frozen mounted bytes and robustly excludes source-derived image inputs/source-tree embedding. Preserve all previously approved coverage, retain fresh source-bound RED, regenerate the package, and request bounded Gate 3 rereview. Final review and exact-source CI remain pending/UNKNOWN.

---

## Gate 3 second A/B refresh — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the refreshed test, implementation, or evidence.
- Refreshed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T090842Z-f0d4c4af6e/package.json`.
- Exact candidate source: `645c07f662c16549673e408f5efed5c37dd73a1bc37ba8b383bffa841cb1fe81`; executable source: `53d3907ac7416840517a4fc04d9620dec46c64f97c6ed1f24f83a31616dfb825`.
- Snapshot: base `708e0a6db6cf7075e392ddc3814e6234672123da` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T090842Z-f0d4c4af6e/snapshot/source.patch`, SHA-256 `81a3ab5079d6d395c1db4a4089295147e3a743d8b10a429ba1efa105944a6595`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T090842Z-f0d4c4af6e/delta.patch`, SHA-256 `e22ccd9736556c548ea34068fcb80d6d740af381811b7411261a4d9f34e4ad7b`.
- Verification-plan SHA-256: `65780897e140e822e075002d1c5c3260355ea4a65a3f850a3a6780aece85e4f2`; planner decision remains `CRITICAL`, required reviews `gate3` and `final`.
- Refreshed test SHA-256: `19764a7f2e6242851f3faedd7989a1bad4735d0da5e6fb806803993d7ff62a4d`.
- Verdict: **CHANGES_REQUESTED**.

### All findings disposition

- Original F1, F3, F4, seven-input invalidation, exit-37 provenance, and complete-fixture findings remain fixed and unchanged.
- The preceding A/B refresh finding is **partially fixed**: the fake now reads the mounted marker, independently computes the executable digest with the mounted harness, checks it against the env/result, rejects actual Git/source digest values anywhere in build argv, and normalizes logical Dockerfile COPY/ADD instructions so only the four explicit dependency manifests may come from build context.
- One frozen-source ambiguity remains open below.

### Complete second-refresh findings

1. **HIGH — the mount witness still does not prove that Docker executes one frozen snapshot rather than the mutable ambient checkout or a later overriding mount.** `tests/Verification/local_docker_storage_budget_001_test.py:254-268` requires that *some* `/workspace` mount be read-only, while the fake at `:69-80` selects the first matching mount. It neither requires exactly one `/workspace` destination nor asserts that its resolved `src` differs from the fixture's ambient repository root. A runner that bind-mounts the mutable `$root` read-only passes marker and digest checks during these sequential calls. A runner can also place a valid frozen mount first and a different `/workspace` mount later; the fake witnesses the first while Docker resolves the later mount for execution. Both violate the final-review requirement that the command execute the exact frozen/materialized source.

   Require exactly one bind mount whose normalized destination is `/workspace`, with `readonly` present, an absolute resolved source different from `repo.resolve()`, and the existing marker/digest equality. Make the fake reject zero or multiple workspace mounts rather than selecting `next(...)`. Retain the current build-value and normalized Dockerfile assertions, which otherwise close the earlier bypasses.

### RED assessment and verdict

The fresh intended RED is correctly bound to candidate `645c07f662c16549673e408f5efed5c37dd73a1bc37ba8b383bffa841cb1fe81` and remains credible for the missing corrected behavior. It does not make the ambiguous/overridden mount observable.

**CHANGES_REQUESTED**

Gate 4 correction remains blocked on the single exact-mount witness above. Preserve all resolved matrices, capture fresh source-bound RED, regenerate the package, and request bounded Gate 3 rereview. Final review and exact-source CI remain pending/UNKNOWN.

---

## Gate 3 third A/B refresh — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the refreshed test, implementation, or evidence.
- Refreshed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T091055Z-b5d9d64370/package.json`.
- Exact candidate source: `30e04c90d321decf95b14e6ef6faab87eab07800532dbb29b44d7da8842b3ba2`; executable source: `20163c9ead6380fbb280d025d63bfbdb690b523460d594c1a7bd5e4fc5fcaf1b`.
- Snapshot: base `708e0a6db6cf7075e392ddc3814e6234672123da` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T091055Z-b5d9d64370/snapshot/source.patch`, SHA-256 `cf2aa0b35f3f23ab3c807dff709f256e280013a69b4fb7d31024ca856d2d980c`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T091055Z-b5d9d64370/delta.patch`, SHA-256 `baa8e1aad17507105b5d3e9b8b176bdade87b6ca7585fb8e9481136e9c61cef4`.
- Verification-plan SHA-256: `71107ac842fe839e8115985db95f50a81dc4adc5e9926c5c2b9bd9d4f082ad74`; planner decision remains `CRITICAL`, required reviews `gate3` and `final`.
- Refreshed test SHA-256: `82b6ec0be7f589a8598a5b57154238050199fec0eee8e503f5299e89125582c8`.
- Verdict: **APPROVED**.

### Complete findings disposition

- Original F1, F3 and F4 remain fixed.
- Original F2 remains fixed for seven independent dependency-input invalidations, source-only tag/image reuse, successful and exit-37 provenance.
- The first A/B refresh finding is fixed: actual Git/source digest values are absent from build argv, the logical Dockerfile parser rejects ADD and all non-manifest build-context sources, and mounted bytes independently produce the reported execution digest.
- The second-refresh exact-mount finding is fixed: both argv and runtime witness require exactly one `/workspace` bind; it is read-only, absolute and existing, its resolved source differs from the ambient fixture checkout, its marker identifies the expected source revision, and its mounted harness digest equals both the passed environment value and result provenance. A later overriding workspace mount or mutable ambient checkout can no longer pass.

### Fresh RED assessment

The retained intended RED is bound to exact candidate `30e04c90d321decf95b14e6ef6faab87eab07800532dbb29b44d7da8842b3ba2`, exits `1`, and fails for the missing corrected behavior rather than fixture setup. The complete A–N matrix remains isolated to fake Docker/Compose and temporary repositories, and the new A/B witnesses are independently derived from the mounted source rather than implementation-reported metadata alone. No blocking findings or new delta risks remain.

### Refreshed Gate 3 verdict

**APPROVED**

Gate 3 passes for exact source `30e04c90d321decf95b14e6ef6faab87eab07800532dbb29b44d7da8842b3ba2`. The executor may correct implementation against this package without changing the approved specification/test expectations. Focused GREEN, renewed independent final review, exact-source CI and publication remain pending and are not implied by this approval.
