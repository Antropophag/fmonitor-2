# Gate 5 review — LOCAL-DOCKER-STORAGE-BUDGET-001

- Reviewer: `/root/docker_growth_final_review` (independent; authored none of the specification, tests, OpenSpec artifacts, implementation, or supplied evidence).
- Review date: 2026-09-26.
- Base: `708e0a6db6cf7075e392ddc3814e6234672123da`.
- Exact reviewed candidate source: `a5f8d18c90e4dd0cb574f0e705fd9a33fbae7c018cf09283eaf27e50ada82129`.
- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T090225Z-14c0191c45/package.json`, SHA-256 `fafd7f4a368693e5fd81ccd22f1db946ad5f9c23ede74823fbfd7ad6a5f8bfcf`.
- Reconstructible snapshot: base above plus `snapshot/source.patch`, SHA-256 `69c35986cbdad7338d95d20bdc5883931a515b43df4b1afde4a126252da7caf2`; snapshot manifest SHA-256 `bfae37bd39ec8977aa5cdb580aba525b300a2effc0a04c3a8e182a3757396257`.
- Verification plan SHA-256: `dc42f03767e2c91753e0eabe6eb8d385d8ca79c8e1cf1d54c6a902fa119e8708`; planner lane `CRITICAL`, required reviews `gate3`, `final`.
- Reviewed contract: `specs/LOCAL-DOCKER-STORAGE-BUDGET-001.md`, SHA-256 `45bc388d124695d83f7c1e507b6c2e73834698a250d111837ee3cbac98074814`.

## Evidence inspected

The complete prepared package, required context, context manifest, verification plan, snapshot manifest/patch, normative A–N contract, OpenSpec proposal/design/delta/tasks, Gate 3 history, production diff, architecture/documentation changes, executable test, and both supplied GREEN records were reviewed. The GREEN records are exact-source bound to the reviewed candidate:

- `python3 tests/Verification/local_docker_storage_budget_001_test.py` — GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790413261408816000-0c42a76a26814c0391593d7d4f437348.json`.
- `python3 tests/Verification/change_verification_001_test.py` — GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790413302777556000-8d6036a8db234d888cb16a41ea094f74.json`.
- Bounded static checks during review: Python/Bash syntax and `git diff --check` passed. No full local suite and no real destructive Docker operation was run.

The focused matrix is otherwise strong: it observes exact cleanup argv/order, selector bounds, fail-closed outcomes, lock/recheck concurrency, safe diagnostics, dependency-tag invalidation, immutable image reporting, and disposable Compose success/failure/signal teardown. OpenSpec scope and A–N traceability are coherent apart from the blocker below.

## Findings

### 1. HIGH — execution provenance violates LDB001-B and the test cannot detect it

Locations: `specs/LOCAL-DOCKER-STORAGE-BUDGET-001.md:19-21`; `tools/delivery/run-in-profile:105-128,184-202`; `tools/delivery/Dockerfile.focused-checks:50-62`; `tests/Verification/local_docker_storage_budget_001_test.py:225-275`.

LDB001-B permits source provenance as a per-build label only if that does not create a source-derived tag **and** the executed result remains bound to current source through the existing bind-mounted workspace. The implementation passes `EXECUTABLE_SOURCE` into the build, labels the built image, copies the entire source tree into `/workspace`, and runs that copied tree. `docker run` has no source bind mount. Consequently the implemented provenance mechanism does not satisfy the contract's stated condition. The stable dependency tag is overwritten by a source-specific immutable image build on every invocation; this is not the contract's described dependency image plus current bind-mounted source model.

The executable test only checks stable/different tag strings, one build/run, labels, Git SHA and an image-ID-shaped value from fake Docker. It does not inspect the run mounts or prove that the command executes the current source independently of image contents, so the plausible regression/alternative implementation above remains GREEN.

Correction: choose and encode one coherent normative model. Under the current approved contract, build the dependency-addressed image without embedding current repository source, bind-mount the exact frozen/materialized source read-only at `/workspace` (with any explicitly owned writable artifact mounts), and add a fake-Docker assertion for the exact mount plus a witness that the executed source changes while dependency tag/image identity is reused. If source-copying/rebuilding is intentionally required instead, return to Gate 1 and revise A/B and the test matrix, then recompute the plan and obtain the required independent Gate 3 approval before implementation rereview.

### 2. HIGH — mandatory review/CI admission is not current for this candidate

Locations: prepared `package.json` (`review_results`, `ci_obligations`); harness state at review time (`admission_context.reviews`, `ci`).

The planner requires `gate3` and `final`, but the only recorded Gate 3 approval is bound to candidate `8a95bf1911c2ff9750f56d12ddc6cf6f334781653d5296731b6c0f3ee6337b2d` and plan `ae6444...`, while the reviewed candidate is `a5f8d18...` with plan `dc42f...`. Harness therefore reports Gate 3 `STALE`, final `MISSING`, and exact-source CI `UNKNOWN`. The package also carries lifecycle fields saying both CI and final review are not required despite the authoritative CRITICAL plan selecting them. Those contradictions cannot support overall GREEN or PR-ready status.

Correction: after resolving finding 1, regenerate the exact-source plan/package, obtain/record every planner-required current review (including fresh Gate 3 whenever the spec/test changes), run the single selected exact-source CI consumer, and require harness admission to show current approvals and GREEN CI. Correct the generated lifecycle metadata or its producer so it does not contradict the authoritative plan.

## Verdict

**CHANGES_REQUESTED**

The production implementation must not be corrected by this reviewer. Gate 5 does not pass for source `a5f8d18c90e4dd0cb574f0e705fd9a33fbae7c018cf09283eaf27e50ada82129`; exact-source CI remains pending/UNKNOWN and is not treated as GREEN.

---

## Gate 5 correction rereview — 2026-09-26

- Reviewer independence: unchanged; `/root/docker_growth_final_review` authored none of the corrected specification, test, implementation, Gate 3 record, or GREEN evidence.
- Fresh package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260926T092250Z-a724207718/package.json`, SHA-256 `26343e879cc7b9e3f6a24df14fad549115141513d82ca389edb966fb78b2ab8e`.
- Exact reviewed candidate source: `3f225a111bc4a9ec578bd3c036c8d8ecffb7fcfee0d4b3bc77e5061a7bea4382`; executable source `c9c37e2088818c45d6e09eb4ccce3a87eca8b4eb78ed813ea72205646f946b08`.
- Reconstructible snapshot: base `708e0a6db6cf7075e392ddc3814e6234672123da` plus `snapshot/source.patch`, SHA-256 `4de65f945e7b208028867f5b458c82ad7c8c1c1dc92e2e57ad9444db5099f87d`; manifest SHA-256 `85edce613bbf3c3f3703ed489abb8fd6a968aff70c2b311f7c750c02b002c1d8`.
- Correction delta: `delta.patch`, SHA-256 `85abfa3f91b3e1499de40f2fdf6536b177747815279cfe0c9614b93ce5eb11d8`.
- Verification plan SHA-256: `18f69416f22b165994603b74b03f83fd9ea3cf2399419c6da91f01daa152fc44`; lane `CRITICAL`, required reviews `gate3`, `final`.
- Current contract SHA-256 remains `45bc388d124695d83f7c1e507b6c2e73834698a250d111837ee3cbac98074814`.
- Current approved test SHA-256: `82b6ec0be7f589a8598a5b57154238050199fec0eee8e503f5299e89125582c8`.

### Evidence and delta reviewed

The complete fresh package, previous snapshot and finding delta, refreshed Gate 3 history, current A–N contract/OpenSpec artifacts, full production candidate, architecture/docs, and exact-source GREEN records were reviewed. Supplied exact-source evidence is:

- `python3 tests/Verification/local_docker_storage_budget_001_test.py` — GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790414488465464000-fbbaefe8d0ca4e829475b1f74eb9a947.json`.
- `python3 tests/Verification/change_verification_001_test.py` — GREEN, record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790414529885178000-f5c4f90a9f10447d8b674f5ac4e8466b.json`.
- Review-only bounded checks: Bash/Python syntax and `git diff --check` passed. No full local suite or real destructive Docker action was run.

### Prior findings disposition

1. **F1 fixed.** `Dockerfile.focused-checks` now contains only the enumerated dependency manifests and installs dependencies outside `/workspace`; it no longer copies the source tree or accepts/emits executable-source build metadata. `run-in-profile` no longer supplies Git/source-derived build values. It binds the restored materialized source exactly once to `/workspace` read-only, keeps writable artifacts in separately declared mounts, and reports the digest of that restored source. Source-only revisions therefore retain both dependency tag and immutable image ID while their executed-source digest changes.

   The independently approved A/B oracle is materially sensitive to the prior defect: it derives the mounted-tree digest with the mounted harness, checks the A/B marker from that exact absolute non-ambient source, requires exactly one read-only `/workspace` bind, rejects an overriding duplicate mount, rejects current Git/source digest values anywhere in build argv, and parses logical Dockerfile `COPY`/`ADD` instructions so an equivalent broad source embedding cannot pass. Seven dependency inputs still independently invalidate image identity and failed-child status/provenance coverage remains intact.

2. **F2 not an open Gate 5 blocker; prior characterization corrected.** Gate 3 is a pre-implementation review of specification/test expectations. The refreshed independent Gate 3 approval covers the unchanged current test blob `82b6ec0b...`; the later production-only correction does not require Gate 3 to approve implementation bytes. Harness reports the whole-candidate Gate 3 binding as `STALE`, but that mechanical status does not invalidate the independently recorded pre-implementation approval under `docs/development-process.md`. Gate 5 is the exact-code review and this rereview supplies that decision.

   Exact-source CI remains `UNKNOWN`, but the documented sequence runs the single selected CI consumer only after an APPROVED final review. It is therefore a pending post-review delivery obligation, not a reason to withhold the Gate 5 verdict. It remains a strict blocker to overall GREEN/PR-ready/publication until the selected exact-source CI is GREEN. The generated lifecycle fields that say CI/final are not required remain non-authoritative and inconsistent with the authoritative CRITICAL plan, but the plan/package exposes the correct obligations and no candidate behavior or review independence depends on those fields.

### Complete correction findings

No blocking findings remain in the correction delta or the previously reviewed unchanged A–N implementation.

## Correction verdict

**APPROVED**

Gate 5 passes for exact source `3f225a111bc4a9ec578bd3c036c8d8ecffb7fcfee0d4b3bc77e5061a7bea4382`. This approval does not assert CI GREEN or PR-ready status: the single planner-selected exact-source CI run remains mandatory and pending.
