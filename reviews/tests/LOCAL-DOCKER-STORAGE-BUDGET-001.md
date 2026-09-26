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

---

## Post-rebase CI-correction Gate 3 review — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the rebased implementation, corrected tests, CI inventory, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T105232Z-93404c8ab5/package.json`.
- Rebased baseline: `fd75b5848b4344013411f4191ee330e147e377c4`; committed feature head at preparation: `728d15f2912ce063b2f8ad15b108c0b3490bd1d5`.
- Exact test-correction candidate source: `2327b21d9bd37d4adde2310aa639ad549bc96dfa5ec972b0585bcd7305d6eea9`; executable source: `a38dee50ad6e0bb51033bc8af856e3f59911dd509ff059e389eeabebb78439d6`.
- Reconstructible snapshot: base `728d15f2912ce063b2f8ad15b108c0b3490bd1d5` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T105232Z-93404c8ab5/snapshot/source.patch`, SHA-256 `4e290b1dec13272c511d8631a5f2f4a26cb18918a63e9ce956c4a9ce435ae589`.
- Verification-plan SHA-256: `2f2d0915ae0c7e47476eab1cdcc508c055774c965324905dbea8e1df358f623c`; lane remains `CRITICAL`, required reviews `gate3` and `final`.
- Reviewed test bindings: storage contract `c3a9a2455afef0fa70cbc7f641880c2ea858d9c8aace88646180f6ca06e119ef`; container composition `7a2a06555b3e45c9703240bb30eccd9262f768bc042664f786ad27484b3ed350`; delivery harness `474b099bd7993747ae6294d0248959f5a4caf647f38b3bfd9828784ef17bb2aa`; focused cache `0f684a5d61ea7b7c83d0cea076a845e5c5e7016143291fbadf16340d5405d39a`; registered bootstrap `0d029529e58119e3c9a599528d1278298778ea53d4166894ea6b4679237c7edf`.
- Verdict: **APPROVED**.

### Complete findings disposition

- All earlier A–N, dependency identity, frozen-source mount, provenance, cleanup, diagnostic, concurrency and disposable-lifecycle findings remain fixed; none of those expectations is weakened by this correction.
- The CI-exposed Buildx finding is correctly covered. `local_docker_storage_budget_001_test.py` now emits the aligned field format used by real `docker buildx inspect`; the unchanged production guard's strict single-space parser rejects it with structured reason `unsupported_buildx`. This is a behavior RED at the public guard seam, not fixture failure, and is sensitive to a parser that tolerates ordinary field whitespace while still requiring exact builder name and `docker-container` driver.
- `registered_yii2_focused_bootstrap_001_test.py` and `delivery_harness_001_test.py` now provide the Buildx version/inspect capability that the real wrapper legitimately requires. Their failure modes remain explicit; richer retained stderr improves diagnosis without changing acceptance semantics.
- `focused_check_cache_180_test.py` and `container_composer_visibility_123_a_test.py` remove obsolete source-in-image label expectations and instead require the approved dependency-only image owner plus frozen source mount/execution provenance. These are traceable corrections to the approved A/B model, not expectation weakening.
- The four minimal dependency manifests added only when absent make harness fixtures reach the dependency-hash seam. Copying `.gitignore` into the isolated storage fixture stabilizes tracked source capture and does not hide contract inputs. The planner now maps every changed regression test as a focused obligation.
- The harness classification correction from `REGRESSION_FAILURE` to `SETUP_FAILURE` for an intentionally failed dependency build preserves the established rule that failure before child behavior is not intended RED; the retained command verdict stays `REGRESSION_FAILURE`.

### RED assessment

The retained record is bound to exact candidate `2327b21d9bd37d4adde2310aa639ad549bc96dfa5ec972b0585bcd7305d6eea9`, exits `1`, and fails at the sufficient-space path with `DOCKER_STORAGE_GUARD ... outcome=rejected, reason=unsupported_buildx`. The fake reached Buildx version and real-spacing inspect output, so the failure isolates the production parser defect. No production correction is included in this review source, as required.

### Post-rebase Gate 3 verdict

**APPROVED**

Gate 3 passes for exact source `2327b21d9bd37d4adde2310aa639ad549bc96dfa5ec972b0585bcd7305d6eea9`. The executor may make the bounded Buildx whitespace correction against this reviewed package without changing approved expectations. Focused GREEN, refreshed final review and any authorized exact-source CI continuation remain separate mandatory evidence and are not implied here.

---

## Buildx identity security-correction Gate 3 review — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the security finding, refreshed test, implementation, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T110523Z-29522e7773/package.json`.
- Rebased baseline: `fd75b5848b4344013411f4191ee330e147e377c4`; committed feature head: `728d15f2912ce063b2f8ad15b108c0b3490bd1d5`.
- Exact test candidate source: `606c249a25cce33750a9d9b506df6b5421b922ced72fa1be948d9f1defbb5148`; executable source: `96be9fd11fbc883fcb0c4631d415b2dc707d130c1a5b796d6da10c5110b954df`.
- Snapshot: base `728d15f2912ce063b2f8ad15b108c0b3490bd1d5` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T110523Z-29522e7773/snapshot/source.patch`, SHA-256 `2baaeab39194258eb1ba2687c6c5c5765394c02451a0756c77543829539516c6`.
- Verification-plan SHA-256: `7b54d68f6c76a3d6ab174a6355acc43de0091f9c5f501de0aff8862045256424`; lane remains `CRITICAL`, required reviews `gate3` and `final`.
- Refreshed storage test SHA-256: `d7f95efeebdbd9589c2781fb306df65b735b1dbd70076edd4002093f10e877aa`.
- Verdict: **CHANGES_REQUESTED**.

### Prior findings disposition

All previously approved A–N matrices and the post-rebase CI-fixture corrections remain fixed. The new valid aligned top-level case preserves sensitivity to the real Buildx output. Missing-all, indented-only, duplicate-same `Name`, and conflicting duplicate `Name` correctly require rejection after exactly version/inspect and before cleanup/build.

### Complete findings

1. **HIGH — the security matrix proves uniqueness/exactness only for `Name`, so an implementation that does not validate `Driver` can pass.** `tests/Verification/local_docker_storage_budget_001_test.py:51-56,182-185` supplies both valid fields together, removes both together in `missing`, indents both together, and expresses both duplicate cases only by duplicating `Name`. The Gate 5 correction explicitly requires exactly one non-empty top-level occurrence of **each** key with exact values. A parser that strictly validates `Name` but ignores `Driver` or defaults it to `docker-container` rejects every current negative fixture and accepts the positive fixture. It can therefore authorize maintenance for an unproven driver while the test remains GREEN.

   Add independently distinguishable cases for valid Name with missing Driver, valid Driver with missing Name, a single wrong Driver, duplicate-same Driver, and conflicting duplicate Driver. Each must assert the exact `[buildx version, buildx inspect]` trace, non-zero result, no cleanup/build, and the stable safe rejection reason. Retain the current top-level/indentation/Name-duplicate cases.

### RED assessment and verdict

The retained RED is exact-source bound and correctly reaches the new public guard matrix. It fails on `indented` because the current executor parser admits the nested fields and starts build, which is a valid intended security RED rather than setup failure. It cannot establish the uncovered Driver axis.

**CHANGES_REQUESTED**

The executor security correction remains blocked until the test independently proves presence, exactness and uniqueness for both identity fields. Preserve all previous coverage, retain fresh source-bound RED, regenerate the package, and request bounded Gate 3 rereview. No production change is approved by this verdict.

---

## Buildx Driver-axis Gate 3 rereview — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the corrected matrix, implementation, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T110724Z-9758b27d23/package.json`.
- Exact candidate source: `42ba53b54c6a27067687ce7682118f7b95d8ed19b965caa865e976d37939efaa`; executable source: `a108f96bb552dcf5a173e0a9c10edbb4f83c8106f1e5d4c29a954193db499b00`.
- Snapshot: base `728d15f2912ce063b2f8ad15b108c0b3490bd1d5` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T110724Z-9758b27d23/snapshot/source.patch`, SHA-256 `42f389ded9207aa98927eb6e82e4755b045881d391bc3b370a61e7fe739523e8`.
- Verification-plan SHA-256: `5866406a88351274ac29635ed947f6f1b6359b09c391a1964e2a52a68772aea2`; lane remains `CRITICAL`, required reviews `gate3` and `final`.
- Corrected storage test SHA-256: `0ce3bc59dec06521fc889fcf40e03764b83d4260fc3c4d91b1c35f5e3439f107`.
- Verdict: **CHANGES_REQUESTED**.

### Prior finding disposition

The prior Driver-axis finding is fixed: missing Name, missing Driver, wrong Driver, duplicate-same Driver and conflicting Driver are now independently rejected, alongside the retained missing-all, indented and Name-duplicate cases. Every negative requires only Buildx version/inspect calls and structured `outcome=rejected`, `reason=unsupported_buildx`.

### Complete rereview findings

1. **HIGH — exact and non-empty `Name` remains independently untested.** There is no single top-level wrong-Name fixture. The `conflicting` case has two Name occurrences, so a parser that rejects duplicates but accepts any single Name value passes it. Such a parser can accept `Name: foreign` with exact `Driver: docker-container` and authorize maintenance against the wrong builder. Likewise, the Gate 5 requirement says each occurrence must be non-empty, but empty `Name:` and empty `Driver:` are not distinguished from absent fields; a parser that defaults an empty present value can pass all current cases.

   Add single wrong-Name, empty-Name and empty-Driver fixtures. Require for each the same exact version/inspect-only trace and stable structured `unsupported_buildx` rejection. Retain the now-complete Driver and duplicate axes.

### RED assessment and verdict

The fresh exact-source RED remains credible and fails on the intentionally admitted indented identity before build. It does not make a lone foreign/empty identity observable.

**CHANGES_REQUESTED**

The executor correction remains blocked on the three narrow Name/non-empty cases. All other prior findings remain fixed. No production implementation is approved by this verdict.

---

## Final Buildx identity-matrix Gate 3 rereview — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the corrected matrix, implementation, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T110900Z-a8aacdbb9c/package.json`.
- Exact candidate source: `e451965c220b3e35069767550d725acad237b99b8b68f486d5610e5490629999`; executable source: `d9198a37e70266cebf610a6e71d679316fa15f2a5c5a953a1f01278d926c6368`.
- Snapshot: base `728d15f2912ce063b2f8ad15b108c0b3490bd1d5` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T110900Z-a8aacdbb9c/snapshot/source.patch`, SHA-256 `90be664289c59d083c78c114a2906076f25e43e87623bd915b449060a4b81279`.
- Verification-plan SHA-256: `c722b35b93b37cf677545f9cba12bb4ff6d67fc20b48ac46b408437bd2358e41`; lane remains `CRITICAL`, required reviews `gate3` and `final`.
- Final storage test SHA-256: `a03970ae66ba220426c53f4666c836a8a5be6ed28db40d47433fedb47e4d6c8d`.
- Verdict: **APPROVED**.

### Complete findings disposition

- All previously approved A–N, dependency/provenance, frozen-source, cleanup, concurrency, diagnostics, disposable lifecycle and post-rebase fixture expectations remain fixed.
- The original Buildx identity finding is fully covered. The matrix has a real aligned valid top-level pair and independently rejects missing-all, missing Name, missing Driver, single wrong Name, single wrong Driver, empty Name, empty Driver, indented-only identity, duplicate-same Name, conflicting Name, duplicate-same Driver, and conflicting Driver.
- Every negative case requires a non-zero result after exactly `buildx version` and `buildx inspect`, with no cleanup/build effect, plus stable structured `outcome=rejected` and `reason=unsupported_buildx`. An implementation must therefore prove exactly one non-empty unindented occurrence of each field with exact values `fmonitor2-focused` and `docker-container`.
- The prior Driver-axis and wrong/empty-Name findings are fixed. No new sensitivity or setup-isolation gap was found.

### RED assessment

The fresh record is bound to exact candidate `e451965c220b3e35069767550d725acad237b99b8b68f486d5610e5490629999`, exits `1`, and fails because the current executor admits the indented identity and proceeds to build. This is the intended missing fail-closed behavior, not a fixture failure. Earlier negative cases are reached first and rejected correctly; the failing case demonstrates the remaining implementation defect.

### Final identity-matrix verdict

**APPROVED**

Gate 3 passes for exact source `e451965c220b3e35069767550d725acad237b99b8b68f486d5610e5490629999`. The executor may implement the bounded top-level exact-identity parser without changing approved expectations. Focused GREEN, refreshed final review and exact-source CI remain separate mandatory evidence.

---

## CI Buildx provisioning Gate 3 review — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the CI finding, refreshed test, workflow action, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T113622Z-22c74f1fa7/package.json`.
- Exact candidate source: `6626e24bb468c8029f3d517b7b365ab077b1cb031dd84839aa74b2d193fe9888`; executable source: `9cbce5a1a5cfc058bf63afdfbdf3bff23586d13ee0fa5869588029c91a4cf4cb`.
- Snapshot: base `587504542f72dbd880a799007488cea426f67a25` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T113622Z-22c74f1fa7/snapshot/source.patch`, SHA-256 `c8ca2f3fd4c569773a748892bfe04c3063c181cf6ab10484f093c610912ee09c`.
- Verification-plan SHA-256: `8b008f7f1fa87a823af9d82f4ec5036065b7b4ce61bc3cd24fbc54bd004a64c3`; lane remains `CRITICAL`, required reviews `gate3` and `final`.
- Refreshed storage test SHA-256: `e007a08d0ed8e6bc4c348582fc8f60fe2bb5bf9dfac5f7dda91c2053e76884c8`.
- Verdict: **CHANGES_REQUESTED**.

### Scope and prior findings disposition

All previously approved A–N and Buildx identity-security cases remain unchanged. `.github/actions/setup-runtime/action.yml` is the correct shared owner: all five Quality Graph category jobs consume it. Requiring the official `docker/setup-buildx-action` at immutable commit `8d2750c68a42422c14e847fe6c8ac0403b4cbd6f` is bounded to provisioning the missing CLI plugin and does not change CI admission, cleanup policy, Docker daemon ownership, or product behavior.

### Complete findings

1. **HIGH — the test does not prove that the pinned action is an executable workflow step.** `tests/Verification/local_docker_storage_budget_001_test.py:160` uses an unrestricted substring search over `action.yml`. The exact text can occur only in a YAML comment, block scalar, display name, or other non-`uses` value and the test will pass while no Buildx plugin is installed; the four observed category jobs would still fail before behavior. This is a direct plausible false GREEN for the sole new acceptance.

   Parse the composite action or use an anchored logical-line oracle that requires exactly one active step whose `uses` value equals `docker/setup-buildx-action@8d2750c68a42422c14e847fe6c8ac0403b4cbd6f`. Reject comment/string-only occurrence and mutable refs; keep the assertion scoped to the owning shared action. If conditional steps are allowed, require that this provisioning step is unconditional for the category consumers.

### RED assessment and verdict

The retained record is exact-source bound, exits `1`, and fails at `INTENDED_RED: CI runtime does not install pinned Buildx` before the guard matrix. It credibly demonstrates the missing action in the current owner file, but it does not close the comment/non-step false-positive above.

**CHANGES_REQUESTED**

CI provisioning implementation remains blocked until the test proves an active immutable `uses` step rather than text presence. Preserve all prior matrices and refresh the source-bound RED/package for rereview. No workflow implementation is approved here.

---

## CI Buildx active-step Gate 3 rereview — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the corrected oracle, workflow implementation, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T113825Z-63cd9a82fe/package.json`.
- Exact candidate source: `359e3afe021944c254a1ead29b7cce541dabee9bf0574ac5a633ed5239f116b7`; executable source: `9093190f1fd8350351e11e1bdd772a6c842b53be5841e8b66add2faedc8ab83b`.
- Snapshot: base `587504542f72dbd880a799007488cea426f67a25` plus `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T113825Z-63cd9a82fe/snapshot/source.patch`, SHA-256 `07b1ba18aa0dc971cacdec88f36ee40befdebfbb08629a375e67d05c79444f11`.
- Verification-plan SHA-256: `ee8c574fd1144e9e7467f5ed65c7a9556e9f136aefe89407bf20be7632941d14`; lane remains `CRITICAL`, required reviews `gate3` and `final`.
- Corrected storage test SHA-256: `c8de3ef53ca74a6a32e5128452b2ffd9a8bad5e63faed1f9dba96964a1fc8d56`.
- Verdict: **APPROVED**.

### Finding disposition and assessment

The prior CI-provisioning finding is fixed. The oracle uses a full-line anchored expression for `- uses: docker/setup-buildx-action@8d2750c68a42422c14e847fe6c8ac0403b4cbd6f`, requires exactly one occurrence, determines that step's indentation-bounded block, and rejects any `if:` property in it. Comments, display names, block strings, mutable refs, duplicates and conditional provisioning cannot satisfy the acceptance. The assertion remains scoped to `.github/actions/setup-runtime/action.yml`, the shared owner consumed by all category jobs; no per-job duplication or admission change is requested.

All previously approved A–N and Buildx identity-security matrices remain unchanged. The fresh exact-source RED exits `1` at `INTENDED_RED: CI runtime does not install pinned Buildx as one active step`, directly demonstrating the absent workflow prerequisite rather than a Docker/fixture setup accident. No new blockers were found.

### CI provisioning Gate 3 verdict

**APPROVED**

Gate 3 passes for exact source `359e3afe021944c254a1ead29b7cce541dabee9bf0574ac5a633ed5239f116b7`. The executor may add the one unconditional exact-pinned setup action to the shared runtime owner without changing approved expectations. Focused GREEN, final review and exact-source CI remain separate mandatory evidence.

---

## Current-main runtime-correction Gate 3 review — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the CI diagnosis, refreshed tests, implementation, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T122838Z-9f0e493c4f/package.json`.
- Current-main base: `2be52c959ec8c7a9722d4efbefaaea9a38113869`; committed candidate base for snapshot: `f209eb427395b38cb64601b6ab9008ff0ef50abb`.
- Exact test candidate source: `7b375d2f08b8a93db79782e2900f4ab9e0560da0f6eaf7106037a68a813f4dbe`; executable source: `3c7819e997dbc9f69154faf124aa194a2bba15133abbbe20aae3818be38edd86`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T122838Z-9f0e493c4f/snapshot/source.patch`, SHA-256 `879c5dd5c449c6be6d5101ed23266022f3aa833f8adf0ac80b32eb30d3df5fd4`.
- Verification-plan SHA-256: `42666e56b288dc184e7ed80ddd13201f59d8385193fbe40be0411efd5dd8ebf2`; lane remains `CRITICAL`, required reviews `gate3` and `final`.
- Refreshed storage test SHA-256: `982824a56dc4a0e8e4360b4d50ab756ff0ebcad394d87bb0120f61724707fc49`.
- Verdict: **CHANGES_REQUESTED**.

### Findings disposition and scope

- All prior A–N, identity-security and pinned active CI-step findings remain fixed.
- The Buildx header correction is adequately sensitive. The valid fixture contains exact aligned header `Name`/`Driver`, then `Nodes:` and an unindented node-level `Name`. A compliant parser must validate the unique exact pair only before `Nodes:`; treating the later node name as a duplicate will fail the positive path. Existing missing/wrong/empty/indented/duplicate/conflicting header negatives remain intact.
- Creating `.local`, `vendor`, and `.test-artifacts` inside the materialized source is bounded and compatible with provenance: they are empty mountpoint directories, not new source facts, and the existing mounted-harness digest witness continues to bind executed files.

### Complete findings

1. **HIGH — the mountpoint test is source-text presence, not a pre-run behavior witness.** `tests/Verification/local_docker_storage_budget_001_test.py:159` accepts one exact `mkdir -p` substring anywhere in `run-in-profile`. The text can be in a comment, unreachable branch, cleanup function, or after `docker run`; the test then turns GREEN while Docker still sees absent nested destinations under the read-only `/workspace` bind and exits 125 before child behavior. It also does not prove the directories are empty or confined to the materialized source.

   Extend the existing fake `docker run` witness: resolve the single `/workspace` source and require `.local`, `vendor`, and `.test-artifacts` to exist as empty directories at the moment `docker run` is invoked. Require no additional repository paths to be created by this preparation (an explicit before/after inventory or equivalent bounded witness), and retain digest equality. The runtime observation establishes ordering and effect; an exact command-string assertion may remain supplemental but cannot be the sole oracle.

### RED assessment and verdict

The retained exact-source RED exits `1` at `runner does not materialize nested writable mountpoints before read-only source mount`. It identifies the absent intended command, but because the current assertion is static it does not prove the correction prevents the observed Docker exit 125.

**CHANGES_REQUESTED**

The executor runtime correction remains blocked on a pre-`docker run` mountpoint behavior witness. The Buildx header correction may remain in the same refreshed matrix. No production implementation is approved by this verdict.

---

## Runtime mountpoint-witness Gate 3 rereview — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the corrected runtime oracle, implementation, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T123101Z-7a070a3dd7/package.json`.
- Current-main base: `2be52c959ec8c7a9722d4efbefaaea9a38113869`; snapshot base: `f209eb427395b38cb64601b6ab9008ff0ef50abb`.
- Exact candidate source: `c7348853456f2b243a701e6bada2a0aa2eeddcdcec937e117d03b45864ae0739`; executable source: `45723c23d64018ba19996d5fa482e3cf93e30f555e93e7157989c3416a0597dd`.
- Snapshot patch SHA-256: `325e84c06bcc4135baa19e9cbd3dc7d08fca9ecde550f52961e79759ce9e92bb`.
- Verification-plan SHA-256: `c301da453c150a0312cb911225ab077bc0d511726d36fa3c4d80ad84577cfe76`; lane remains `CRITICAL`, required reviews `gate3` and `final`.
- Corrected storage test SHA-256: `b949cfef96130ca8b4f2477fbf7a72ece69a1092a8ee03ad9887ea9688875e46`.
- Verdict: **APPROVED**.

### Finding disposition and assessment

The prior mountpoint finding is fixed. During the actual fake `docker run` call, the oracle resolves the sole exact `/workspace` bind source and records `.local`, `vendor`, and `.test-artifacts`. The public assertions require the exact three expected entries to be directories with empty contents. This proves the destinations exist before Docker processes the nested mounts and prevents the observed exit 125; a comment, dead branch, cleanup-time mkdir or post-run command cannot satisfy it. The existing mounted-harness digest equality simultaneously proves these empty directories do not change executable source identity.

The realistic positive Buildx fixture retains aligned header Name/Driver followed by `Nodes:` and an unindented node Name, while all approved header ambiguity negatives remain. Thus the header-only parsing correction and mountpoint preparation can be implemented together without weakening the dedicated-builder boundary. All earlier A–N, identity-security, CI provisioning and provenance matrices remain unchanged.

### RED assessment

The retained record is exact-source bound and exits `1` at the still-missing header parser behavior (`unsupported_buildx`). That is the first intended defect in the combined matrix. The later mountpoint witness is structurally reachable after the same approved header correction and independently observes runtime filesystem state rather than source text. No setup or sensitivity blocker remains.

### Runtime correction Gate 3 verdict

**APPROVED**

Gate 3 passes for exact source `c7348853456f2b243a701e6bada2a0aa2eeddcdcec937e117d03b45864ae0739`. The executor may implement the bounded header-before-Nodes parser and create the three empty materialized mountpoint directories before `docker run`, without changing approved expectations. Focused GREEN, refreshed final review and exact-source CI remain separate mandatory evidence.

---

## Read-only dependency-image mount Gate 3 review — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_gate3` authored none of the CI diagnosis, refreshed test, implementation, or evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T125840Z-998ffe5ffd/package.json`.
- Current-main base: `2be52c959ec8c7a9722d4efbefaaea9a38113869`; snapshot base: `d252510e8e4f1125e49a366b53360673ca131e88`.
- Exact candidate source: `44aed0117a1ed986dcea4a571cdeb2d22db030ad694986322129c3f1f1bdd716`; executable source: `2c10536fb59abefda2b0f2973f004f7b428d3ed560a01d0f89547103d670559b`.
- Snapshot patch SHA-256: `6613dd9a4f49c1eb6f5e94ff34b18eaeb1a805c92012cf155ff768eb08d66524`.
- Verification-plan SHA-256: `d7f4bc87a22f7628aa3a7b6dc2879a4ba13e0cf86e31d9431a4ce60455823ce2`; lane remains `CRITICAL`, required reviews `gate3` and `final`.
- Refreshed storage test SHA-256: `5dfa40eaa73a539be6717d42a082e229584f9299a7f3033e7790a342a992c412`.
- Verdict: **APPROVED**.

### Assessment and findings disposition

The new public-run assertion requires exactly one `/workspace/vendor` mount and exact argv `type=image,src=<reported immutable image_digest>,dst=/workspace/vendor,readonly,image-subpath=/opt/fmonitor/composer/vendor`. It therefore rejects the CI-observed writable tmpfs plus runtime `cp`, a tag/mutable image source, a host bind, a writable dependency view, a wrong destination, and a broader/wrong image subpath. The dependency path matches the Dockerfile's Composer installation at `/opt/fmonitor/composer/vendor`.

This is compatible with the established boundaries: the exact frozen source remains one read-only `/workspace` bind; its empty `vendor` mountpoint witness remains required before run; the dependency image ID is already bound to the stable seven-input identity and reported provenance; application source and dependency bytes are not copied into a writable runtime layer. Existing container-composition expectations already require `vendor_writable=false`, so the correction reconciles rather than weakens adjacent behavior.

All prior Buildx header/Nodes, identity-security, mountpoint, CI provisioning, cleanup, concurrency, diagnostics and disposable lifecycle findings remain fixed. No new security or sensitivity finding was found.

### RED assessment

The fresh exact-source RED reaches the real public runner and fails on the new exact vendor mount assertion. Its trace shows the current writable `/workspace/vendor` tmpfs and `cp -a` command, so the failure is the intended dependency-ownership defect rather than setup failure.

### Dependency mount Gate 3 verdict

**APPROVED**

Gate 3 passes for exact source `44aed0117a1ed986dcea4a571cdeb2d22db030ad694986322129c3f1f1bdd716`. The executor may replace the writable vendor tmpfs/copy with the exact immutable read-only image-subpath mount without changing approved expectations. Focused GREEN, refreshed final review and exact-source CI remain separate mandatory evidence.
