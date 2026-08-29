# Test review: PILOT-SHLZ-ASSETS-001 v0.2

- Reviewer: separately tasked agent `/root/shlz_assets_v2_test_cleanreview`
- Test author: separately tasked Gate 2 agent; reviewed corrective commit `e49f512`
- Reviewed commit: `e49f51273d1ebcdaeb64f173ffced4a21d32597f`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.2 at `331b8ac9616b99162fe75b7bc501e1dc223a9d73`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — deterministic RED, exit `255`; a request overlapped by continuous file-identity and mode mutation returned `200` with 6,291,456 body bytes instead of the required redacted `503`.
- Verdict: `APPROVED`

## Findings

- Traceability: the test cites v0.2 and exercises only the confirmed raw HTTP seam. Task-owned graph bytes supply independent expected values. It covers complete transitive serving, import grammar and graph rejection, route/method priority, GET/HEAD parity, limits, symlink boundaries, owner/mode rejection, in-request mutation, between-request official replacement, and stylesheet ordering.
- Sensitivity: the overlap fixture starts a continuously mutating worker before request transmission, proves at least 20 mutations between transmission and the first response byte, and keeps mutation active through response completion. Current production therefore exposes the precise missing atomic-capture behavior as `200` rather than the specified `503`.
- Determinism and isolation: three consecutive focused review runs failed at the same behavioral assertion with the same status and body length. Fixtures, expected bytes, database, HTTP servers, and mutation worker are task-owned and cleaned; no `psa-*` artifact remained after the runs.
- Replacement behavior: the same long-lived server is expected to serve exact old bytes, then exact new bytes after an atomic two-file official replacement. This distinguishes permitted between-request replacement from forbidden mixed in-request capture without relying on process-global identity.
- Trusted-owner/mode behavior: explicit fixtures reject group/other-writable root and member modes, executable members, and—when runnable as root—a member whose owner differs from the trusted root owner. The effective-UID review environment is non-root, so the privileged owner fixture is correctly capability-gated rather than nondeterministically attempted.
- Prior coverage: the v0.2 corrective delta preserves the existing graph, grammar, routing, security-header, concurrency, bounds, symlink, HTML-order, replacement, and owner/mode assertions. It removes only implementation/residue inspection oracles. The specification's external-cache/guardian prohibition remains a mandatory Gate 5 implementation inspection; it is not necessary to turn that prohibition into a nondeterministic executable HTTP oracle at Gate 2.
- Expected-value independence: exact CSS, statuses, headers, lengths, and replacement representations are literal consequences of the approved examples and task-owned fixtures, not production source or a planned implementation.

## Required changes

None.

## Verification evidence

- `git diff --check 331b8ac^..e49f512 -- specs/PILOT-SHLZ-ASSETS-001.md tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — RED in three consecutive runs, each exit `255`: overlap expected `503`, actual `200`, body bytes `6291456`.
- `git diff 0d59fbd..e49f512 -- tests/InstallationProcess/pilot_shlz_assets_001_test.php` — reviewed; behavioral coverage retained, nondeterministic/runtime implementation-inspection probes removed.
- Post-run inspection — no `.test-artifacts/psa-*` fixture remained.

Gate 4 may proceed for exact specification v0.2 and reviewed test commit `e49f512`.
