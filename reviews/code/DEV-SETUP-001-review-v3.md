# DEV-SETUP-001 independent code re-review v3

Verdict: **APPROVED (code and focused setup contract)**

Reviewed exact candidate `1225c397240eb11f27673fa6c6cfb29fa6cae365`
against `origin/main` (`321fde662d26d467c16030e1c82687f8ce56b63d`).
The committed three-dot diff SHA-256 is
`6357f690df5e6718879cdd82816778fb84bf8d0b403700cb54263299644e34bd`.
The worktree was clean before this v3 record was added.

## Standards

The new preflight derives the effective OS user's home from
`posix_getpwuid(posix_geteuid())`, canonicalizes both paths, rejects `/` as a
home boundary, and requires the checkout to be a real descendant. It runs after
validated pin loading and PHP extension validation but before dependency checks,
clones, package commands, Docker operations or local lock creation. It therefore
enforces the inherited filesystem guard without trusting a substituted `$HOME`.

The test fixture now uses a temporary directory directly beneath the existing OS
account home and removes it through `TemporaryDirectory`. It no longer creates a
persistent `~/.local/state` parent. The prior review-record EOF issue is fixed and
`git diff --check origin/main` passes.

No new material standards violation or code smell was found.

## Spec and test adjustment

Adding `posix` to `PHP_EXTENSIONS` is required by the new public preflight and
causes the generic extension loop to reject a runtime that cannot perform the
account lookup. Updating the specification and the test's exact expected
extension string from `mysqli,pcntl,dom,mbstring,curl` to
`mysqli,pcntl,dom,mbstring,curl,posix` strengthens the assertion and matches the
already changed manifest; it does not weaken or bypass a behavioral assertion.

The separately authored foreign-checkout test uses a complete dependency fixture,
delegates only the account lookup to the host PHP/OS identity, requires a nonzero
result and actionable path diagnostic, fingerprints the sibling tree, and checks
the mutation trace. Its recorded pre-implementation unexpected-success RED is
credible, and the candidate makes it GREEN.

## Independent evidence

- `python3 tests/Verification/development_setup_001_test.py`: 8/8 PASS.
- `python3 tests/Verification/verification_inventory_001_test.py`: 15/15 PASS.
- `python3 tests/Verification/verification_ci_001_test.py`: 9/9 PASS.
- `python3 tools/delivery/render-dependencies.py --check`: PASS.
- `git diff --check origin/main`: PASS.

This approval does not claim final integration readiness. The exact-candidate full
`make test` was still running, and an earlier Linux CI E2E failure was under
investigation. Those results must be recorded and resolved independently before a
full CI/production-readiness claim.
