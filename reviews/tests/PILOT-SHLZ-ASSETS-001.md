# Test review: PILOT-SHLZ-ASSETS-001

- Reviewer: `/root/shlz_assets_test_review`
- Test author: separately tasked Gate 2 agent (commit `277e08f`)
- Reviewed commit: `277e08f075821e6d0919100152ac18d9fd4458c0`
- Specification: `specs/PILOT-SHLZ-ASSETS-001.md` v0.1 at `dd22aa883407e19f94d5990bb39ff7cce5bf1712`
- Public seam: raw HTTP `GET|HEAD /pilot/assets/shlz.css` and browser-relative manifest routes
- Red command and intended failure: `php tests/InstallationProcess/pilot_shlz_assets_001_test.php` fails at the first split dependency, `GET /pilot/assets/foundation.css`, with expected `200 text/css` and task-owned bytes versus actual `404 Not found.`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — required manifest graph cases are absent.** Section 5 explicitly requires evidence that a duplicate normalized target remains one route, that a different-identity alias fails closed, and that member-count, depth, and aggregate-size limit overflow returns `503`. The test has no fixture or assertion for any of those cases. A parser that serves the happy recursive graph but omits deduplication, alias detection, or all three resource bounds would pass this test. Add public-HTTP fixtures for these outcomes; include the allowed duplicate/cycle behavior because section 3 makes it part of the manifest contract.

2. **Blocking — committed-representation determinism is not tested.** Section 4 requires repeated/concurrent `GET|HEAD` to return a byte-stable committed representation. Current coverage makes only one GET and one HEAD for each stable member. The removal and replacement cases sensitively cover later identity failure, but do not distinguish a per-request rebuilt manifest from the lifetime-committed manifest nor exercise concurrent responses. Add an observable public-seam test that would fail if the graph is rebuilt or response bytes diverge after composition capture, plus repeated/concurrent GET/HEAD parity appropriate to the stated contract.

3. **Blocking — required HTML stylesheet order is not asserted.** Section 5 says Gate 2 proves that root HTML retains `/pilot/assets/shlz.css` followed by `/pilot/assets/pilot.css`. This test never requests an HTML route. Add the smallest raw-HTTP assertion at the existing public route (or explicitly amend the approved specification if this is delegated to an already approved predecessor test).

The present assertions otherwise use the correct raw HTTP seam and independently owned bytes. They sensitively cover recursive imports, exact GET/HEAD MIME and length, unreferenced-file exclusion, malformed route and method priority, malformed/import target rejection, invalid UTF-8, `pilot.css` collision, file/directory symlinks, removal, regular-file identity swap, redaction, security headers, and cleanup isolation. Syntax is valid, and the captured RED is caused by the missing split-export behavior rather than setup failure.

## Required changes

- Add the missing duplicate/cycle, different-identity alias, and all three limit-overflow fixtures and assertions.
- Add lifetime-manifest/repeated/concurrent determinism coverage at the public seam.
- Add the required root HTML stylesheet-order assertion, or amend the approved Gate 1 contract before resubmitting Gate 2.
- Re-run the focused test and retain the intended RED output for the revised artifact.
