# Test review: PILOT-SHLZ-ASSETS-001 v0.2

- Reviewer: separately tasked agent `/root/shlz_owner_test_review`
- Test author: separately tasked Gate 2 agent; reviewed corrective commit `c95bc76`
- Reviewed commit: `c95bc766bf7fb5bad36c932a05204898e8dfc457`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.2 at `331b8ac9616b99162fe75b7bc501e1dc223a9d73`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — deterministic RED, exit `255`; the trusted root-owned fixture expected exact `200` GET/HEAD bytes but the current implementation returned redacted `503`.
- Verdict: `APPROVED`

## Findings

- Traceability: the corrective delta directly covers the two owner-boundary omissions found at Gate 5. Owner read/search requirements are asserted independently with `0100` and `0400` directory fixtures, while a root-owned dist root and entrypoint must be accepted by the public HTTP seam.
- Root-owner oracle: under effective UID `0`, the test creates its own root-owned fixture and therefore always executes deterministically. Under a non-root UID it uses only an explicitly configured suitable fixture, a verified adjacent export, or a task-owned fixture provisioned through an available Docker socket; if none is available, only this privilege-dependent case is capability-gated. Candidate UID/type are checked before use, expected bytes are read independently, and the helper does not modify a pre-existing candidate.
- Safety and cleanup: Docker ownership changes are restricted to the task-owned `$base/root-owner-allowed` tree and are restored to the invoking eUID before normal recursive cleanup. The root execution path restores file/directory permissions in `finally`; servers are stopped and the main test cleanup removes the task-owned tree. Three RED runs left no `psa-*` fixture.
- Overlap sensitivity: the existing continuously mutating identity/member-mode fixture now also mutates the root entrypoint mode during the request. It proves at least 20 mutations after request transmission and before the first response byte, and continued mutation through response completion, strengthening the oracle against a response assembled after graph validation.
- Preserved coverage: the delta retains the previously approved literal graph oracle, recursive import grammar, manifest-only routes, GET/HEAD parity, method/route priority, symlink and traversal rejection, graph limits, between-request replacement, concurrency, no-identity behavior, and stylesheet order.
- Expected-value independence: expected CSS/status/header values remain literals from the approved spec and task-owned fixtures, not values derived from production parsing or implementation source.
- Gate 5 obligation: executable HTTP behavior cannot prove descriptor open count without adding a production test hook or platform watcher. The fresh Gate 5 reviewer must inspect that each graph member is opened once, retained through whole-graph revalidation, rewound/re-read on the same descriptor, closed exactly once with attempt-all cleanup, and that the selected response uses captured bytes rather than reopening its path.

## Required changes

None.

## Verification evidence

- `git diff --check 331b8ac..c95bc76` — PASS.
- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — RED in three consecutive runs, each exit `255`: trusted root-owned fixture expected `200`, current implementation returned `503`.
- Post-run inspection — no `.test-artifacts/psa-*` fixture remained.

Gate 4 may proceed for exact specification v0.2 and reviewed test commit `c95bc76`.
