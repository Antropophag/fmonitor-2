# DEV-SETUP-001 independent code re-review

Verdict: **APPROVED**

Reviewed candidate `f4ab8bd77b96c67769574d27f7103497b89cc40f` against
`origin/main` (`321fde662d26d467c16030e1c82687f8ce56b63d`). The exact committed
three-dot diff had SHA-256
`2ae5cd9be2e6421d0a14d6781b72a38372205ccea118185abb72abf361550b46`.
The worktree was clean before this v2 review record was added.

## Standards

All findings from `DEV-SETUP-001-review.md` are resolved:

- Local setup and the CI composite action now consume pins through the same
  validating Python parser. Its export grammar excludes shell metacharacters,
  so evaluating the validated assignments cannot execute the malformed manifest
  input covered by the new acceptance test.
- The frozen legacy-suite inventory subtracts the newly registered unit member
  explicitly, preserving the established baseline. Quality Graph `governance`
  and legacy-suite `unit` remain intentionally distinct per
  `tools/verification/README.md` and issue #43.
- `git diff --check origin/main...HEAD` passes.

The read-only Python ZIP adapter is narrow: it implements only the two operations
used by pinned `shlz-ui` generation, changes `PATH` only for that generation
command, rejects other modes, and has a Unicode filename/content preservation
test. Removing Playwright's global Chrome channel makes the browser verifier use
the lock-pinned Chromium installed by setup. Pinning npm closes the remaining
local/CI runtime-version gap. No material documented-standard breach or Fowler
smell remains.

## Spec

The candidate implements DEV-SETUP-001's public commands, exact prerequisite
checks, single pin authority, non-mutating doctor behavior, preservation and
validation of existing dependency trees, temporary construction plus guarded
atomic publication, and repeat-safe reuse. The malformed-manifest regression
test now proves the previously missing “validate before use” boundary. The
portable ZIP and pinned Chromium adjustments support the requested clean macOS
setup without broadening domain or data behavior.

## Independent evidence

- `python3 tests/Verification/development_setup_001_test.py`: 7/7 PASS.
- `python3 tests/Verification/verification_inventory_001_test.py`: 15/15 PASS.
- `python3 tests/Verification/verification_ci_001_test.py`: 9/9 PASS.
- `python3 tools/delivery/render-dependencies.py --check`: PASS.
- `git diff --check origin/main...HEAD`: PASS.

Clean-checkout installation and the full `make test` are integration-readiness
evidence, not prerequisites to this code-review verdict. They were still running
or pending and are not claimed here. Any final integration/production readiness
claim must cite their actual results on the exact candidate.
