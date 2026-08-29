# Test review: PILOT-SHLZ-ASSETS-001

- Reviewer: `/root/shlz_assets_test_review_final`
- Test author: separately tasked Gate 2 agents (commits `277e08f`, `0ed3874`, `9f87f1f`)
- Reviewed commit: `9f87f1f3c3edf69667111cf1c622923460e7707b`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.1 at `dd22aa883407e19f94d5990bb39ff7cce5bf1712`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Verdict: `APPROVED`

## Findings

No blocking findings.

The test is traceable to the approved specification and exercises the real raw-HTTP seam with task-owned fixtures. Expected bytes, MIME, lengths, route outcomes, boundary values, security headers, and stylesheet order are independently fixed by the specification rather than production parsing or Showcase internals.

The final revision closes the prior concurrency gap with one mixed concurrent batch containing `GET` and `HEAD` for every member in the five-route worked manifest. Every response is parsed through the same public-response oracle: GET asserts exact status, CSS MIME, byte length, bytes, and inherited security headers; HEAD asserts the same committed status/MIME/length and security headers with an empty body. The following sequential request also retains repeated-response sensitivity.

The same-filesystem-identity hardlink fixture has the correct expected result. Section 3 rejects a duplicate logical route only when its observed file identity differs; it does not reject two distinct manifest routes that intentionally resolve to the same device/inode. The fixture proves both paths really share identity and requires both exact public routes to remain available. Existing removal and regular-file-swap fixtures separately prove fail-closed behavior when a captured route loses or changes identity. No unsupported collision rule is introduced by the test.

Prior coverage remains intact: the fixed recursive graph and all exact GET/HEAD representations; unknown and malformed route behavior; method priority; unreferenced-file exclusion; root HTML stylesheet order; normalized duplicate/cycle handling; exact 256-member, depth-32, and 8-MiB boundaries; malformed/remote/escaping imports; invalid UTF-8; `pilot.css` collision; broken-graph priority; symlink file/directory rejection; removal and replacement after manifest capture; redaction; and inherited no-session/security headers. Fixtures are isolated and removed in `finally`.

## Verification evidence

- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS, no syntax errors.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — expected RED at the first missing split dependency: `GET /pilot/assets/foundation.css` expected `200 text/css; charset=UTF-8`, length `55`, and task-owned bytes; current implementation returned exact `404 Not found.`. This is the intended missing behavior, not a setup failure.

Gate 4 may proceed against the reviewed expectations at commit `9f87f1f3c3edf69667111cf1c622923460e7707b`.
