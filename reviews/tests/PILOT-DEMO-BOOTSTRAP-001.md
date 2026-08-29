# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.2

- Reviewer: `/root/bootstrap_css_test_review`
- Test author: `/root/bootstrap_css_test`
- Reviewed commit: `c6fc146`
- Specification: `specs/PILOT-DEMO-BOOTSTRAP-001.md` v0.2 at `9193839`
- Public seam: separate CLI process `php bin/fmonitor2-pilot-demo.php [start|reset|status|cleanup]`, followed only through its printed raw HTTP routes and public `status`
- Red command and intended failure: `php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php` — RED, exit `255`; the first new standalone-dist case expected exact `{"ok":false,"reason":"SHLZ_ASSETS_UNAVAILABLE"}` but production returned exact `{"ok":false,"reason":"STARTUP_FAILED"}`. The test reached the public CLI seam after valid fixture/bootstrap setup and failed on the missing v0.2 error contract.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Traceability is stale.** The reviewed test still declares `PILOT-DEMO-BOOTSTRAP-001 v0.1` on line 7, while its new assertions derive from approved v0.2. Update the citation so the reviewed artifact unambiguously names its normative source.
2. **A mandatory rejected case is absent.** Section 8 requires a broken/escaping transitive import to fail closed before the ready banner. The test adds only a missing dependency (`@import "./missing.css"`). It never supplies an existing out-of-root target such as `@import "../outside.css"` (with distinctive secret bytes) and therefore cannot distinguish containment enforcement from ordinary missing-file handling.
3. **The wrong-basename adversary is not behavior-distinguishing.** Creating `dist/not-shlz.css` after the first missing-root run leaves the exact required `dist/shlz.css` absent, so this case exercises the same condition as the preceding case. Make the fixture demonstrate that a plausible wrong-basename file cannot substitute for the official root without merely repeating an unchanged missing-root setup, or state explicitly that it is a decoy/no-fallback assertion and separately cover the named wrong-basename input seam if one exists.
4. **The independent graph oracle is adequate for the current one-file official export but unsafe as a transitive oracle.** `pdbCssGraph()` silently normalizes traversal above the dist root (`array_pop()` on an empty stack), does not reject absolute/schemed/query/fragment targets, and has no explicit graph/member/depth limits. Thus a future invalid official graph can be transformed into a different in-root route rather than causing test setup to reject it. The oracle should independently parse the accepted import grammar and fail on escaping/invalid targets; it must not reproduce permissive production behavior. Exact route, GET/HEAD parity, MIME, `Content-Length`, bytes, stylesheet order, unknown-route `404`, redacted error code, public seam and stable literal expectations are otherwise asserted.
5. **RED is honest.** Syntax passes, MariaDB/demo setup completes far enough to invoke the first new CSS startup contract, no ready banner is printed, and the observed `STARTUP_FAILED` versus required `SHLZ_ASSETS_UNAVAILABLE` is the intended missing production behavior rather than a harness/configuration failure.

## Required changes

- Cite specification v0.2 in the test.
- Add a behavior-distinguishing escaping-import adversary with an existing out-of-root file and verify exact redacted `SHLZ_ASSETS_UNAVAILABLE`, no server/ready generation, and no leaked target/path/bytes.
- Harden the independent graph walker to reject targets outside the approved relative CSS-import grammar and dist root (and enforce the inherited graph bounds), rather than normalizing them into different in-root paths.
- Make the wrong-basename sensitivity independently meaningful or clarify/restructure it as a no-fallback decoy case.
