# Test review: FOCUSED-CHECK-CACHE-180

- Reviewer: independent Gate 3 agent `/root/issue180_gate3`, `gpt-5.6-sol / low`; authored neither specification nor test.
- Test author: primary/root Codex session, per `docs/operations/issue-180-focused-check-cache-delivery.md`.
- Reviewed source: base `266e01f78d86493acbd531466afe67aec23f775c` plus retained package snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T151039Z-2a33f90321/snapshot/source.patch`, patch SHA-256 `9844a0d4594e9c33f6488a5496d97ccfcc90328ed9cea58f2b4cd8ca7a7056e6`; exact candidate source `4caf7a84727bbb7ebb4cb1402106790e0b393d765d577fdcb606177e9fc0823f`.
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T151039Z-2a33f90321/package.json`; verification plan SHA-256 `38aa43117825e7c925dfbabb5420448f424b60ed207217e3be6880c193371504`; context manifest SHA-256 `8ea23f92ba7449e9b0b2bc93c55da15d211e3311a435bb00a6958e2737ddc896`.
- Agreed review scope: issue #180 only; pre-implementation Gate 3 review of the normative contract, OpenSpec artifacts, root-authored test, inventory registration and exact-source RED for traceability, public seam, sensitivity, independent expected values, cache/identity/rejection cases, determinism and setup isolation.
- Specification: `specs/FOCUSED-CHECK-CACHE-180.md`, package-bound SHA-256 `0dfcad61618b8d72fd1ce67e1993629cc4e1c07534abc7427198e508320e0235`.
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]` and its existing `tools/delivery/Dockerfile.focused-checks` build.
- Planner: `CRITICAL`; required reviews `gate3`, `final`; required category `governance`.
- Verdict: `APPROVED`.

## Evidence reviewed

- Exact-source RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789657830894208000-8783541e4e164efda2b0750a2d83d30e.json` for `python3 tests/Verification/focused_check_cache_180_test.py`, exit `1`, outcome `INTENDED_RED`, candidate source `4caf7a84727bbb7ebb4cb1402106790e0b393d765d577fdcb606177e9fc0823f`, executable source `6c591242844deb5bbf46784b75cf938d2ca6720b0771dabbc5182629650e48d9`.
- The captured failure is the intended missing behavior at `tests/Verification/focused_check_cache_180_test.py:30-32`: current stage-local `ARG COMPOSER_LOCK_SHA256` precedes `apt-get update`, so a metadata-only source/identity change invalidates dependency layers. It is not a fixture, parser or environment failure.
- Two direct bounded reruns produced the same assertion and location. No full local `make test` or `make verify` was run.
- Package-bound content hashes for the contract, test, inventory and lifecycle artifacts match the reviewed checkout.

## Acceptance and quality assessment

- Traceability is complete for the bounded pre-implementation regression: `FCC180-01` is enforced by requiring both metadata-only stage arguments to occur exactly once after every named dependency `RUN` and before the final identity `LABEL`; `FCC180-02` preserves canonical Composer and uv inputs before their owning installers; `FCC180-04` preserves the compact-result schema; `FCC180-05` is reflected in the narrow planned boundary. `FCC180-03` and the real-build portions of `FCC180-01/02/04` are intentionally deferred to the contract-required bounded BuildKit evidence after implementation, not falsely claimed by the cheap structural test.
- Sensitivity follows Dockerfile cache ordering rather than an implementation helper: an early/duplicate/missing metadata `ARG`, moving a canonical lockfile copy after its installer, removing either identity label/build argument/inspection guard, or adding `wall_time_seconds` to `RUN_IN_PROFILE_RESULT` makes an explicit assertion fail.
- Expected values are independently derived from the normative recipe boundaries and public identity contract: the test names the canonical dependency commands, lockfile copies, labels and public launcher guards; it does not derive expected placement from production output.
- Cache and dependency-input cases are complementary rather than circular: metadata arguments must be downstream of all dependency runs, while canonical dependency inputs must remain upstream of their installers. This distinguishes source-only reuse from lock-change invalidation.
- Identity and rejection coverage preserves both source/lock build arguments, final labels, both label inspections and the existing fail-closed mismatch message. The later real A→B witness must still report distinct honest source identities, image IDs, executed B, cache decisions and stale-image rejection; this approval does not treat string checks as that evidence.
- Determinism/setup isolation are adequate: the test is read-only, network-free, has no clock/randomness/external service, scopes parsing to the `common` stage, validates prerequisite command presence before position assertions, and repeats with the same intended RED.
- The OpenSpec proposal/design/delta/tasks and current delivery goal consistently restrict work to #180. Application code, dependency versions, CI composition, FAST/Gates, harness/result-schema redesign, shared cache pruning and foreign worktree/volume deletion remain excluded.

## Findings

None. Locations/corrections: none required for the reviewed source.

## Required changes

None. Gate 3 advances exact source `4caf7a84727bbb7ebb4cb1402106790e0b393d765d577fdcb606177e9fc0823f` to the separate executor. This approval does not approve implementation, Gate 5, CI, PR, merge, deployment, settings, cache pruning or any #181–#183 work.
