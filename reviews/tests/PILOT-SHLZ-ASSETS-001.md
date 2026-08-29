# Test review: PILOT-SHLZ-ASSETS-001

- Reviewer: `/root/shlz_parser_test_review`
- Test author: separately tasked Gate 2 agents (latest commit `e3228dd`)
- Reviewed commit: `e3228dda483222ac8ae21e241daf7a5883c55cce`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.1 at `dd22aa883407e19f94d5990bb39ff7cce5bf1712`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Verdict: `CHANGES_REQUESTED`

## Findings

### Blocking — malformed-grammar oracle is not independently fixed by the specification

The new `$malformedGrammar` table classifies `@import "member.css" totally-bogus;` as malformed. Under CSS `@import` grammar the suffix is a media query list, and an identifier can be a media type; an unknown media type is syntactically valid even though it does not match. The test would therefore reject a conforming parser and is not an implementation-independent expected value.

The new expectations for an unterminated trailing comment and an unterminated ordinary `@media` block are likewise not stated by section 3 or its worked examples. Section 3 defines which leading top-level rules participate in manifest discovery and explicitly says parsing stops at an ordinary style statement; it does not define a strict whole-stylesheet validator or override CSS error recovery. Consequently those cases cannot acquire exact `503` expectations from parser implementation intent.

Return to Gate 1 or Gate 2: normatively define the accepted/rejected trailing and import-suffix grammar with independent worked examples, or remove the unsupported cases. Keep at least one valid nearby suffix and one unambiguously malformed `@import` case so the test remains sensitive without treating valid media syntax as invalid.

The public seam itself is correct: the additions use raw HTTP `GET` against both root and member routes, assert exact redacted `503` for an invalid graph, and exact bytes/MIME/HEAD parity for accepted graphs. Fixtures remain task-owned and isolated.

This verdict supersedes the earlier approval for the augmented test head; the previously approved expectations through `9f87f1f` are unaffected.

The test is traceable to the approved specification and exercises the real raw-HTTP seam with task-owned fixtures. Expected bytes, MIME, lengths, route outcomes, boundary values, security headers, and stylesheet order are independently fixed by the specification rather than production parsing or Showcase internals.

The final revision closes the prior concurrency gap with one mixed concurrent batch containing `GET` and `HEAD` for every member in the five-route worked manifest. Every response is parsed through the same public-response oracle: GET asserts exact status, CSS MIME, byte length, bytes, and inherited security headers; HEAD asserts the same committed status/MIME/length and security headers with an empty body. The following sequential request also retains repeated-response sensitivity.

The same-filesystem-identity hardlink fixture has the correct expected result. Section 3 rejects a duplicate logical route only when its observed file identity differs; it does not reject two distinct manifest routes that intentionally resolve to the same device/inode. The fixture proves both paths really share identity and requires both exact public routes to remain available. Existing removal and regular-file-swap fixtures separately prove fail-closed behavior when a captured route loses or changes identity. No unsupported collision rule is introduced by the test.

Prior coverage remains intact: the fixed recursive graph and all exact GET/HEAD representations; unknown and malformed route behavior; method priority; unreferenced-file exclusion; root HTML stylesheet order; normalized duplicate/cycle handling; exact 256-member, depth-32, and 8-MiB boundaries; malformed/remote/escaping imports; invalid UTF-8; `pilot.css` collision; broken-graph priority; symlink file/directory rejection; removal and replacement after manifest capture; redaction; and inherited no-session/security headers. Fixtures are isolated and removed in `finally`.

## Verification evidence

- `git diff e3228dd^ e3228dd --check` — PASS.
- `php -l tests/InstallationProcess/pilot_shlz_assets_001_test.php` — PASS, no syntax errors.
- `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` — RED at new malformed case 0: expected exact `503`, current public seam returned exact `200` root bytes. The failure is observable at the correct seam, but it does not cure the unsupported oracle above.

Gate 4 must not proceed against the augmented expectations at `e3228dd`.
