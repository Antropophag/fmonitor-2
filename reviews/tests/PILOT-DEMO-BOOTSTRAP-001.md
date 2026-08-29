# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.2

- Reviewer: `/root/bootstrap_css_test_rereview`
- Test author: `/root/bootstrap_css_test`
- Reviewed commit: `88e99ebd55746b3a3cd6de828e6171f8d799ce66`
- Specification: `specs/PILOT-DEMO-BOOTSTRAP-001.md` v0.2 at `919383966f962fb9811a5bf6350536310de03683`
- Public seam: separate CLI process `php bin/fmonitor2-pilot-demo.php [start|reset|status|cleanup]`, followed only through its printed raw HTTP routes and public `status`
- Red command and intended failure: `php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php` — RED, exit `255`; after syntax and inherited-contract setup, the first standalone-dist rejection expected exact `{"ok":false,"reason":"SHLZ_ASSETS_UNAVAILABLE"}` but production returned exact `{"ok":false,"reason":"STARTUP_FAILED"}`. No ready banner was accepted. This is the missing v0.2 public error contract, not a harness/configuration failure.
- Verdict: `APPROVED`

## Findings

None.

The prior blockers are resolved: the test cites the exact approved v0.2 specification; the wrong-basename fixture now contains plausible official export bytes and proves there is no basename fallback; an existing out-of-root dependency with distinctive secret bytes proves escaping-import containment and redaction; and the independent graph oracle rejects over-root, remote/invalid targets and enforces the inherited 256-member, depth-32 and 8 MiB bounds. The inherited `PILOT-SHLZ-ASSETS-001` public-seam contract remains an explicit regression prerequisite, covering graph grammar, bounds, identity, GET/HEAD and fail-closed behavior. Expected routes, MIME, bytes, labels, actors, dates and state transitions remain independently fixed by the specifications or the configured official export bytes, without production renderer/parser reuse.

## Required changes

None.
