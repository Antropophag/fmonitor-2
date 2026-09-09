# Test review: REVIEW-SOURCE-001

- Reviewer: Codex agent `/root/review82` (`gpt-5.6-sol`, low)
- Test author: root agent
- Reviewed source: commit `934b58f5eb00045fa90472c0750307eb782e9864` (base `b58852a9257d2c6b45163e9e61a74f6dcc5b3302`)
- Agreed review scope / prior findings disposition: first complete Gate 3 review of the root-authored normative contract, test, design impact analysis, and verification input for issue #82; no prior findings
- Specification: `specs/REVIEW-SOURCE-001.md`, SHA-256 `8b69ad529eaa3390a64562b9f34e925964e6d74900db8d8ca402035453ab9ca5`
- Public seam: `python3 tools/delivery/review-source.py capture --repo REPO --output SNAPSHOT` and `restore --snapshot SNAPSHOT --output DESTINATION`
- Verification plan: `.local/verification/82-plan.json`, regenerated from the exact clean reviewed commit in an isolated detached worktree; SHA-256 `231be8fe15c828cf514224328bbc2b6b409444cbddd8e2c50d3abcde712054c7`; `check` -> `CHANGE_VERIFICATION_OK`. It selects `python3 tests/Verification/review_source_001_test.py`, the governance planner regression, and later `make test` integration.
- Red command and intended failure: `python3 tests/Verification/review_source_001_test.py`; retained log `/tmp/82-focused-red.log`, SHA-256 `d9e59f94e1baf822db860db716ec85b035f89cb94e33fcbea042039f07c0a07b`; 6 tests ran and all failed at `INTENDED_RED: REVIEW-SOURCE-001 capture/restore public seam is absent`. Reviewer repeated the command at the reviewed commit with the same 6 intended failures (`Ran 6 tests in 0.729s`).
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Major — rejection tests do not detect mutation of foreign/source data required to remain unchanged.** The normative `integrity` row requires foreign directories/files to remain unchanged, and the `failures` row says an apply failure removes only the worktree created by that invocation (`specs/REVIEW-SOURCE-001.md:22-23`). `test_integrity_rejections_before_destination_creation` checks only that the destination was not created (`tests/Verification/review_source_001_test.py:126-147`). `test_apply_failure_cleans_only_created_worktree` checks the worktree registration and merely checks that the source repository and snapshot still exist (`tests/Verification/review_source_001_test.py:149-162`). An implementation that edits the source checkout, rewrites snapshot bytes, or changes an unrelated sentinel while returning nonzero would pass these assertions. This leaves an explicit safety outcome insensitive across corrupted patch, unsupported version, wrong digest, unavailable base/repository, and patch-apply failure. Add independently captured source state and byte/sentinel state before each rejection, then assert exact equality afterward; for apply failure, also assert the snapshot manifest/patch bytes are unchanged while the created destination/worktree registration is removed.

Traceability to the real CLI, path-with-spaces handling, round-trip bytes/modes/symlink fidelity, ignored and clean-file isolation, source preservation on the successful path, existing-output replay, second-destination replay, deterministic fixtures, independent literal expectations, and the intended RED were otherwise checked and are adequate for the bounded contract.

## Required changes

- Strengthen the integrity and apply-failure cases so they prove that source, snapshot, and unrelated existing data remain byte-for-byte/state-for-state unchanged on every rejection while only an invocation-created destination/worktree is cleaned up.
- Regenerate the exact-source verification plan and retain a new focused RED after the test correction, then return the corrected Gate 2 delta for independent rereview.

## Rereview 1 — corrected Gate 2 delta

- Reviewer: Codex agent `/root/review82` (`gpt-5.6-sol`, low)
- Test author: root agent
- Reviewed source: base commit `934b58f5eb00045fa90472c0750307eb782e9864` plus retained binary patch `/tmp/82-gate3-fix.patch`, SHA-256 `ea2ab6e1e1edeb66d276eee86bd3c084ea526ddf7a01595f6d19b06e23587764`
- Reconstructed/working test SHA-256: `9b93d67a566d9f562df3f0dc7703ecda0755fc435187b51e3e2dc9883cd7e78c` (exact match)
- Verification plan: `.local/verification/82-plan.json`, SHA-256 `0b587d0d91b37e759fd067d8304fc96bb27608afd495f0a0ad952ddffb85aebf`; binds the corrected test SHA-256 above and retains the same focused/governance/full-CI obligations. The saved plan was inspected before this review-record append; later unrelated evidence/review-file changes make an in-place `check` stale by design and do not alter the reviewed test delta.
- Red command and intended failure: `python3 tests/Verification/review_source_001_test.py`; retained log `/tmp/82-focused-red-v2.log`, SHA-256 `bf1a775b6b4282cb7f328b90dc74de4631e30aa30d814c88b12d4b4d1d523127`; 6 tests ran and all failed at the intended missing-seam assertion.
- Prior finding disposition: **resolved**. `protected_state()` captures source HEAD/status/index/diffs plus source and snapshot file bytes, modes and symlink targets, and the unrelated owner sentinel. Exact before/after equality is now asserted for invalid patch/version/digest/base/repository, patch-apply failure, non-Git capture, and existing capture/restore outputs. Apply failure still independently proves destination removal and unchanged worktree registration.
- Findings: None.
- Verdict: `APPROVED`

## Gate 3 amendment — restore containment rejection

- Reviewer: Codex agent `/root/review82` (`gpt-5.6-sol`, low)
- Test/spec author: root agent
- Reviewed source: base `934b58f5eb00045fa90472c0750307eb782e9864` + retained snapshot `/private/tmp/fmonitor-82-nested-red-v2-source`, `source.patch` SHA-256 `f84dfa16d7be5ef2e734db8ec83b0f3959fb9307471b2c73cb0bf542267c9ecc`
- Agreed scope: test/spec delta only for the sole Gate 5 finding; all other approved matrices unchanged
- Specification delta: the `isolation` row now explicitly rejects restore destinations inside the source checkout or snapshot, including paths resolving through symlinks, before creating files or a worktree. Bound spec SHA-256: `4566267511bf862ff80ddf9c36195b8b655519118297234baf00839fcd446def`.
- Test delta: `test_restore_rejects_source_and_snapshot_descendants` uses three distinct destinations: direct source descendant, symlink alias resolving into source, and snapshot descendant. Each subtest requires nonzero/stderr, no destination, exact protected-state preservation, and unchanged worktree registrations. Moving the unrelated marker creation to `setUp` also ensures deletion cannot be masked by the state helper. Bound test SHA-256: `7277038049f83b5dbc9d7c4d9b896087b2ac01b06912f01e6fff4174be9630f1`.
- Verification plan: `.local/verification/82-plan.json`, SHA-256 `9e72602f71d69283bfaf407a4f0a36cd6a33a5440d571e041138dfb1536e8ec6`; inspected bindings match the spec/test digests above and include acceptance, changed inventory, governance, unit architecture, and later full integration obligations.
- RED: `/tmp/82-nested-red-v2.log`, SHA-256 `9d4299e5545125a534683bc1f5db20c91261f38cd160b0b585f4e1770808c38a`; the focused test has three intended failures because the current CLI exits 0 and creates a worktree for each forbidden destination. Fixture setup and capture succeed.
- Findings: None. Expected containment and state outcomes are independent of the implementation, paths with spaces and symlink resolution are exercised, and each relevant rejection is observable through the public CLI.
- Verdict: `APPROVED`

## Rereview required changes

None.

## Gate 3 amendment — verification inventory registration

- Reviewer: Codex agent `/root/review82` (`gpt-5.6-sol`, low)
- Test author: root agent
- Reviewed delta: `tests/Verification/verification_inventory_001_test.py` against frozen Gate 5 source `934b58f5eb00045fa90472c0750307eb782e9864` + `/private/tmp/fmonitor-82-gate5-source/source.patch`, patch SHA-256 `c0c23c14b5a297f1c9c66fa6b7a40f0ab45476e894c6dc0958ce1dabefa8464f`
- Scope: one-line addition of `python3\ttests/Verification/review_source_001_test.py\n` to the explicit unit-suite additions while retaining the historical baseline digest
- RED: `/tmp/82-inventory-red.log`, SHA-256 `81056a550b105f6579cd6f5c8babdcebe2539fe7e53db25f994f4aea37580f29`; `test_repository_baseline_membership` failed only on the newly registered unit member missing from the explicit additions
- GREEN: `/tmp/82-inventory-green.log`, SHA-256 `12f49b6b6b9ddd89c80a5e879648488ffeacfc6ca0ebe823a8c7da39b26ccdc6`; 15 tests passed. Reviewer repeated `python3 tests/Verification/verification_inventory_001_test.py`: 15 tests passed in 6.614s.
- Findings: None. The expectation is exact, independent of the inventory implementation, and preserves detection of unrelated historical drift.
- Verdict: `APPROVED`
