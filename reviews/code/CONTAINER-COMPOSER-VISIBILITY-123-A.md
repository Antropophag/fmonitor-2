# Gate 5 code review — CONTAINER-COMPOSER-VISIBILITY-123-A

- Reviewer: independent `gpt-5.6-sol / low` agent `/root/issue123_gate5`; authored none of the reviewed specification, tests, implementation, or evidence.
- Review date: 2026-09-16.
- Base: `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf`.
- Reviewed commit: `baf31724b67f30a35e9e1289551eab7b28a839bc`.
- Exact candidate source: `dd04b8d312433412c5a9f4dfca167a954de9836510ae0c74f48b52f5d82bcb23`; executable source: `d4132fa982dd6c4c46cfc725c0d28defd7a0f80afe3fa85091d52011ce5b0b25`.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T075226Z-d8a438a22d/package.json`; reconstructible snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Normative specification: `specs/CONTAINER-COMPOSER-VISIBILITY-123-A.md`, SHA-256 `70c7228d4e2cfb992c7c1611526e8cafdc7ed0be2c8952ad3b85a07968fa563c`.
- Gate 3 record: `reviews/tests/CONTAINER-COMPOSER-VISIBILITY-123-A.md`, SHA-256 `d328a5fc5fc4e1f221d0884186f921701a00c1da0882406be461ce6d4df4c1ed`, final delta verdict `APPROVED`.
- Verdict: `CHANGES_REQUESTED`.

## Verification evidence reviewed

The immutable package contains four source-matched GREEN records, all bound to the candidate and executable identities above:

- A–O public-route regression: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789544630889351000-fa2646dac6044280b3219775d023a0a6.json`, 8/8 tests GREEN in 354.050s. The recorded clean-worktree runs report `setup_failures_before_behavior: 0`; representative command durations are 0.522–1.142s.
- Governance/profile compatibility: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789544991676895000-809735cdf52440c6911741c48316104a.json`, GREEN in 100.172s.
- Exact-source/change-verification regression: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789545098384568000-9c38fa2eb4f743939a2f498c68c5a02b.json`, GREEN in 21.203s.
- Runtime-storage boundary: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789545123963095000-6b9822c9189c4a74a4a7db1df426a481.json`, GREEN in 2.373s.

No local full `make test` or `make verify` was run. Exact-source CI remains pending and is not treated as GREEN.

## Standards

Apart from the blocking exact-source boundary below, the implementation is narrowly located in the existing profile launcher and image recipe. It reuses `review-source.py capture/restore` and `source_details().executable_digest`, preserves additions/deletions/modes, hashes lock/recipe/runtime inputs into image identity, verifies image labels before execution, and runs the composed root read-only. No new manager, profile, snapshot format, product runtime, deployment Compose, dependency update, or application behavior is introduced.

The `.local` mount is not a dependency or project-source fallback: it is the existing explicit per-worktree artifact area, is mounted only when already present, otherwise becomes a disposable tmpfs, and cannot create host `vendor`, `node_modules`, or `.venv`. `/tmp` is separately disposable. This is consistent with the owner-approved writable artifact/runtime exception and worktree isolation.

No additional documented-standard violation or actionable Fowler-baseline smell was found. `git diff --check` is clean.

## Specification

The source-matched tests substantiate the main composition: candidate-only project bytes load from `/workspace`, Composer/Yii load from immutable `/workspace/vendor`, host stale vendor is ignored, corrupt/missing dependencies and changed lock fail before behavior, source and vendor are read-only, two worktrees remain isolated, and governance/integration/browser share the seam. Dockerfile-specific ignore rules prevent host dependency and secret trees from entering the build context. The image tag/build args/labels cover executable source, manifests, lock, recipe, runtime pins, profile, and ignore policy.

One exact-candidate entry-point leak remains.

## Findings

### F1 — Blocking — integration/browser network composition reads live host source after freeze

- Location: `tools/delivery/run-in-profile:98-110`.
- CCV123A-01 requires host changes after freeze not to influence execution, and CCV123A-05 requires integration/browser network/service ownership to be preserved for the exact candidate. The launcher correctly restores the frozen candidate into `$materialized`, but then runs `docker compose -f compose.test.yaml config` after `cd "$root"`. Therefore a change to the live host `compose.test.yaml` after `FMONITOR_EXECUTION_SNAPSHOT` was created can change the selected network and DB environment (or make the profile omit them) while the container source, source digest, and image label still claim the frozen candidate.
- Matrix D currently mutates only a project marker and exercises the governance profile, so all GREEN records can pass without observing this leak. This is a plausible stale/dirty-host regression at an existing integration/browser execution entry point, not an optional broader audit.
- Correction: resolve the profile network configuration from the restored frozen candidate (for example, run the existing Compose config query with `$materialized` as its project directory/source) while preserving the existing fixed Compose project/network semantics. Add a bounded public-route regression that freezes a candidate, mutates the live host Compose input afterward, and proves integration/browser selection remains governed by the frozen snapshot. Because that changes the approved executable test, refresh the plan/RED and obtain the required Gate 3 delta approval before returning to Gate 5.

## Verdict

`CHANGES_REQUESTED`

Gate 5 does not pass for exact candidate source `dd04b8d312433412c5a9f4dfca167a954de9836510ae0c74f48b52f5d82bcb23`. Correct F1, renew the changed-test review, run the affected focused evidence on a fresh exact-source package, and request independent Gate 5 rereview. Standards: one hard exact-source boundary finding; Spec: one blocking missing invariant at integration/browser entry points.
