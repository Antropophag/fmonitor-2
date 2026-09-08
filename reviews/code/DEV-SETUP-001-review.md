# DEV-SETUP-001 independent code review

Verdict: **CHANGES_REQUESTED**

Reviewed against `origin/main` (`321fde662d26d467c16030e1c82687f8ce56b63d`) in
the issue-23 worktree. The committed candidate was
`1479fbfb3c5d2df1f6e0525003816aba0454cd23`; the complete candidate diff,
including the subsequent uncommitted pinned-Chromium edits, had SHA-256
`4c727991c369d3d359670a47785e8d5cc40c3bee323e377b5a22707d57e80151` at review
time. Because the candidate is still changing, rerun and record the hash after
the requested fixes.

## Standards

1. **Blocking — the manifest is executed before it is validated.**
   `tools/delivery/setup.sh` sources `tools/delivery/dependencies.env` before
   calling `render-dependencies.py --check`. This contradicts the spec's
   requirement to validate the manifest before using it and breaks the promised
   non-mutating `make doctor`: a malformed line such as
   `PROBE=$(touch /tmp/setup-doctor-mutated)` executes before the validator can
   reject it. The composite CI action has a second, less strict parser (`sed` to
   `$GITHUB_ENV`) and likewise does not validate before consuming the pins.
   Make the Python parser the single validation/export seam used by local setup
   and CI, and add an acceptance test proving a malicious/malformed manifest is
   rejected without mutation.

2. **Blocking — the repository verification inventory is red.** Running
   `python3 tests/Verification/verification_inventory_001_test.py` fails
   `test_repository_baseline_membership` with `unit baseline drift` (expected
   `ae1c98c...`, actual `00173fbe...`). Update the frozen inventory through its
   documented process. The test's `governance` Quality Graph category and legacy
   `unit` suite membership are intentionally different under the dual catalogs
   documented in `tools/verification/README.md`; that difference is not a defect.

3. **Required hygiene — `git diff --check origin/main...HEAD` is red.** It reports
   `tools/delivery/compose.test.yaml.in:21: new blank line at EOF.`

No material Fowler smell was found in the setup helpers. Splitting validation,
dependency checks, rendering and the public entrypoint is proportionate to the
scope.

## Spec

The existing-tree checks, temporary sibling builds, guarded atomic publication,
wrong-runtime early exit, and reuse of untracked user files match the principal
DEV-SETUP-001 acceptance examples. The move from a globally installed Chrome
channel to the lock-pinned Playwright Chromium is consistent with reproducible
setup.

The first standards finding is also a direct spec failure: “Setup validates the
manifest before using it” and check-only “make no filesystem ... mutation” are
not met. The current five focused tests pass but do not exercise this boundary.

## Evidence

- `python3 tests/Verification/development_setup_001_test.py`: 5/5 PASS.
- `python3 tests/Verification/verification_ci_001_test.py`: 9/9 PASS.
- `python3 tests/Verification/verification_inventory_001_test.py`: 14/15 PASS,
  one blocking unit baseline failure.
- No clean-checkout setup or full `make test` result is claimed by this review;
  those runs were still in progress or had not been supplied at review time.
