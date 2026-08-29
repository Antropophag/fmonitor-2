# Test review: PILOT-SHLZ-ASSETS-001

- Reviewer: `/root/shlz_assets_test_rereview`
- Test author: separately tasked Gate 2 agents (commits `277e08f`, `0ed3874`)
- Reviewed commit: `0ed3874e2defb9ef14c01b198373e172c58cee9a`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.1 at `dd22aa883407e19f94d5990bb39ff7cce5bf1712`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css`, browser-relative manifest routes, and configured root HTML
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` fails at the first split dependency, `GET /pilot/assets/foundation.css`, with expected `200 text/css` and task-owned bytes versus actual `404 Not found.`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — different-identity alias remains untested.** The previous review explicitly required the section 5 `different-identity alias` failure case. Commit `0ed3874` adds the allowed duplicate-normalization/cycle fixture, but no fixture creates two observations of one logical route backed by different filesystem identities and no assertion requires the graph to fail closed with `503`. An implementation that deduplicates solely by route text while accepting an identity change during manifest capture would still pass.

2. **Blocking — concurrent `GET|HEAD` contract is only partially observed.** `psaConcurrent()` sends GET only and retains only status/body. It neither sends concurrent HEAD requests nor checks their committed `Content-Length`, CSS MIME, security headers, and empty bodies. Sequential `psaGetHead()` coverage and concurrent GET bodies are useful, but an implementation whose concurrent HEAD path diverges from the committed representation required by section 4 would pass.

The other previously reported gaps are closed at the public seam. The revised test now covers exact lower/pass and upper/fail boundaries for 256 members, depth 32, and aggregate 8 MiB; allowed duplicate normalization and a cycle; lifetime identity replacement after composition capture; repeated GET/HEAD; concurrent GET bytes; and root HTML stylesheet order. Existing coverage remains sensitive to recursive imports, exact member bytes/MIME/length, route and method priority, unreferenced files, malformed targets and graph, invalid UTF-8, `pilot.css` collision, symlink components, removal/replacement, redaction, and security headers. Expected CSS bytes and boundary values come from the approved specification and task-owned fixtures. Syntax is valid, fixture cleanup is isolated, and the captured RED is caused by missing split-export behavior rather than setup failure.

## Required changes

- Add a public-seam fixture and assertion for a duplicate logical route observed with a different filesystem identity, expecting redacted `503`.
- Extend concurrent coverage to include HEAD and assert the same committed status/application headers/length with an empty body.
- Re-run the focused test and retain the intended first-split-route RED output.
