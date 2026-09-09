# Code review: REVIEW-SOURCE-001

- Reviewer: Codex agent `/root/review82` (`gpt-5.6-sol`, low)
- Implementation author: Codex agent `/root/implement82` (`gpt-5.6-sol`, low); root authored specifications, tests, registrations, instructions and delivery evidence
- Reviewed source: base `934b58f5eb00045fa90472c0750307eb782e9864` + retained snapshot `/private/tmp/fmonitor-82-gate5-source`, `source.patch` SHA-256 `c0c23c14b5a297f1c9c66fa6b7a40f0ab45476e894c6dc0958ce1dabefa8464f`, restored at `/private/tmp/fmonitor-82-gate5-checkout`
- Agreed review scope / prior findings disposition: first complete Gate 5 review of the REVIEW-SOURCE-001 implementation and registrations against its approved contract, plus the instruction/process/template/config changes against GitHub issue #82. The unchanged approved acceptance test was not reopened; the narrow inventory-test amendment received Gate 3 approval in `reviews/tests/REVIEW-SOURCE-001.md`.
- Specification: `specs/REVIEW-SOURCE-001.md`, SHA-256 `8b69ad529eaa3390a64562b9f34e925964e6d74900db8d8ca402035453ab9ca5`
- Approved test review: `reviews/tests/REVIEW-SOURCE-001.md`, Gate 3 rereview and inventory amendment `APPROVED`
- Verification plan: `.local/verification/82-plan.json`, SHA-256 `5d223d1da74edadeff04d02b456d16f3d602ca797d7b1f004875ddb89c7fa77e`
- Verification commands: implementer evidence records acceptance 6/6, planner 12/12, architecture guard 59/59 and CI selection 15/15 GREEN; root records `make architecture-check` with HTTP qualification PASS and 7 architecture rules PASS. Reviewer ran `python3 tests/Verification/review_source_001_test.py` (6/6 PASS, 2.951s), `python3 tests/Verification/change_verification_001_test.py` (12/12 PASS, 9.931s), and `python3 tests/Verification/verification_inventory_001_test.py` (15/15 PASS, 6.614s). Authoritative full CI is correctly still pending.
- Implementation digest: `tools/delivery/review-source.py` SHA-256 `a2b3474edc0693fe82f0aff3b74c9d9df3ef5a8bd5e54894e3b8e0fadb68b7ea`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Major — `restore` can modify the source checkout that the contract says remains unchanged.** `restore()` checks only whether the destination already exists, loads the snapshot repository, and passes any new destination directly to `git worktree add` (`tools/delivery/review-source.py:131-143`). A destination below that repository is accepted. Reproduction from the frozen candidate with a clean fixture repo and empty snapshot: `restore --snapshot "$snap" --output "$repo/nested restore"` exits 0, registers a detached worktree, and changes the source status from empty to `?? "nested restore/"`. This violates the observable `fidelity` guarantee that source files/status do not change and the stated purpose of restoring to a separate checkout. The approved tests use an external destination and therefore do not catch this plausible regression. Reject any restore destination inside the snapshot's source repository before creating directories or registering a worktree, using the same resolved containment rule as capture; add a public-seam rejection test proving nonzero/stderr, no destination/worktree registration, and exact protected source state. Because this requires a test change discovered at Gate 5, restart at Gate 2 and obtain independent Gate 3 approval for that delta before Gate 5 rereview.

The remaining bounded implementation matches the contract: temporary-index capture preserves the real index, binary/full-index patching covers the reviewed bytes and modes, ignored data stays excluded, manifest/digest/base validation precedes destination creation, existing and dangling outputs fail closed, apply cleanup is gated on successful worktree creation, subprocesses avoid shell evaluation, and the registrations select the intended unit and governance obligations. The process/instruction changes implement issue #82's authorship, complete-candidate, grouped-checkpoint, exact-source, focused-check, failure-inventory and truthful-status rules without weakening domain or CI gates. Delivery evidence accurately reports the one Gate 3 return, the later inventory correction, measured checks, and pending full CI/merge state.

## Required changes

- Reject a restore destination contained within the source repository before any filesystem or Git mutation.
- Add the focused regression described above, retain RED/GREEN and regenerated-plan evidence, obtain independent Gate 3 approval for the test delta, then return the corrected implementation delta for Gate 5 rereview.

## Authorship clarification

The original header's attribution was too broad. Executor `/root/implement82` authored the CLI implementation **and the suite/category registrations**. Root authored the normative specification, acceptance tests, the later one-line inventory-baseline test amendment, instructions and delivery evidence. The original Gate 5 verdict and finding remain unchanged.

## Gate 5 rereview — containment correction

- Reviewer: Codex agent `/root/review82` (`gpt-5.6-sol`, low)
- Implementation author: Codex agent `/root/implement82` (`gpt-5.6-sol`, low)
- Reviewed source: base `934b58f5eb00045fa90472c0750307eb782e9864` + retained snapshot `/private/tmp/fmonitor-82-gate5-final-source`, `source.patch` SHA-256 `fafddd5beba8aab18db1d9e495bd3bdff1f1fa22c005b674ae9153a5e3440026`, restored at `/private/tmp/fmonitor-82-gate5-final-checkout`
- Agreed scope: corrected code delta for the sole prior Gate 5 finding against the independently approved containment spec/test delta; no broader rereview
- Corrected implementation: `tools/delivery/review-source.py` SHA-256 `a45bc1170ba7712088e49961d2b84cb917b6b9bb72b04ec07290bb07118b99ef`
- Verification plan: `.local/verification/82-plan.json`, SHA-256 `6ef07afa19a9aa129124181919bac566e8bb6fdee03e676f996814189428114b`; reviewer ran `check` -> `CHANGE_VERIFICATION_OK`. Its bindings match the corrected implementation, approved test SHA-256 `7277038049f83b5dbc9d7c4d9b896087b2ac01b06912f01e6fff4174be9630f1`, and amended spec SHA-256 `4566267511bf862ff80ddf9c36195b8b655519118297234baf00839fcd446def`.
- Verification: implementer reports acceptance 7/7 GREEN. Reviewer repeated `python3 tests/Verification/review_source_001_test.py` in the frozen restored source: 7/7 PASS in 4.230s.
- Prior finding disposition: **resolved**. The only code delta adds a resolved-path containment check for both source repository and snapshot immediately after snapshot validation and before parent-directory creation or worktree registration. Direct descendants, symlink aliases resolving into source, and snapshot descendants now fail through the public error path without mutation.
- Findings: None.
- Verdict: `APPROVED`

## Rereview required changes

None.
