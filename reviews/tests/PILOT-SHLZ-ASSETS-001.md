# Test review: PILOT-SHLZ-ASSETS-001

- Reviewer: separately tasked agent `/root/shlz_parser_test_finalreview`
- Test author: separately tasked Gate 2 agents; final corrective commit `c443f3c`
- Reviewed commit: `c443f3c94348637eaf575fe2d8357af6cb611a6c`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.1 at `dd22aa883407e19f94d5990bb39ff7cce5bf1712`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — RED, exit `255`; malformed import case 0 expected exact redacted `503 Service unavailable.\n`, but the public route returned exact `200` with `@import "member.css" layer(;\n`.
- Verdict: `APPROVED`

## Findings

None.

### Corrective change and grammar sensitivity

Commit `c443f3c` removes exactly the unsupported trailing-stylesheet case `@import "member.css"; )` identified in the prior independent review; no other expectation, fixture, helper, or production byte changes in that commit. The remaining six rejected cases are confined to malformed import grammar required by specification sections 3 and 5: unclosed `layer(`, unclosed `supports(`, an extra `)` in the import prelude, invalid `???` media-suffix tokens, an unterminated quoted target, and an unterminated quoted `url(...)` target.

The adjacent accepted cases independently prevent over-rejection: a complete `layer(components) supports(display: grid) screen and (min-width: 40rem)` suffix and valid bare `layer` followed by an ordinary `@media` rule both serve root and member bytes exactly. The test no longer asserts strict validation of arbitrary trailing stylesheet text or rejects an unknown-but-valid media type.

### Traceability and seam

The test cites exact `PILOT-SHLZ-ASSETS-001 v0.1` and exercises only the real raw-HTTP router/composition seam. Task-owned bytes provide the oracle independently of the production parser, renderer, directory enumeration, or private Showcase. The fixed recursive example proves all five exact routes, bytes, MIME, length, GET/HEAD parity, concurrent/repeated stability, and root HTML stylesheet order.

The rejected-case matrix covers the specified invalid targets, malformed graph grammar, UTF-8 failure, `pilot.css` collision, malformed-route and method priority, unknown/unreferenced routes, symlinked members/components, removal and identity replacement, exact 256-member/depth-32/8-MiB boundaries, graph cycle/deduplication, and same-identity aliases. Exact redacted errors, security headers, no session/cookie behavior, and broken-graph fail-closed behavior are observable at the public seam.

### Determinism, isolation, and RED quality

Fixtures use unique task-owned paths and database names, loopback PHP servers, exact expected bytes, and `finally` cleanup. They do not depend on production systems, mutate `../shlz-ui`, or derive expectations from implementation details. The captured failure occurs only after all predecessor split-graph and routing assertions pass, at malformed import case 0. The returned `200` proves the setup reached the intended public behavior; the mismatch is specifically the missing strict import parser required by the approved specification.

## Required changes

None. Gate 4 may proceed from exact reviewed test commit `c443f3c94348637eaf575fe2d8357af6cb611a6c`. Any later specification or test change requires fresh independent Gate 3 review.

## Verification evidence

- `git diff 75643a2 c443f3c -- tests/InstallationProcess/pilot_shlz_assets_001_test.php` — only the previously rejected final malformed member is removed from the array.
- `git diff dd22aa8..c443f3c -- specs/PILOT-SHLZ-ASSETS-001.md tests/InstallationProcess/pilot_shlz_assets_001_test.php --check` — PASS.
- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS, no syntax errors.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — intended RED, exit `255`, at malformed import case 0 with exact `503` expected and exact `200` current behavior.
