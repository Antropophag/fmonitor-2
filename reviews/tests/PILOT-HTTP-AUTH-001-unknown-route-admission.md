# Test review: PILOT-HTTP-AUTH-001 — unknown-route lazy admission

- Reviewer: `/root/original_gate3`, independent agent; not author of the inherited behavior assertion or proposed repair.
- Test author: inherited approved HTTP auth test author; only its canonical migration precondition changed in a separately reviewed slice.
- Reviewed base: `88925774f7ca805758a94930b0ea62926c0fba6b` plus approved frontier precondition.
- Specification: approved PILOT-HTTP-AUTH-001 v0.12, sections 9 and 11.2; reused without a new exception.
- Public seam: real `ProductionPilotHttpEntrypointFactory::create(EnvironmentSource)` and returned entrypoint `handle(serverArray)`.
- Verdict: `APPROVED`.

## Findings

The inherited specification explicitly resolves unknown routes before configuration/dependencies and requires 404 to read no environment values. The unchanged assertion at HTTP auth test line 128 expects `[[],404]`. A feature flag read is still an environment read; the new original/composition routing does not authorize an exception for unrelated unknown routes. Existing Gate 1 therefore applies directly to a minimal lazy-admission repair.

Read the actual `production_environment_source_spy.php` support: its ordinary object implements the declared EnvironmentSource interface, records every read, and passes into the real production factory. It samples reads before create/after handle and exercises a literal unknown path. No namespaced function replacement, native driver interception or production factory shadow is used by this seam. The fixed empty expected read list is independent of implementation, and the surrounding cases separately prove lazy creation, asset-only config, missing-identity behavior and shell access order. Do not replace this assertion with an allowlist containing the fresh-flow flag.

Root execution of `php tests/InstallationProcess/pilot_http_auth_001_test.php` is captured in `/Users/antropophag/.local/state/fmonitor2-verification/canonical-frontier-20260907/pilot_http_auth_001_test-green-v1.log`. Despite that historical filename, this is valid behavioral RED: the new canonical15 precondition succeeds, execution reaches line 128, expected `[[],404]`, actual `[['FMONITOR_FRESH_ORDER_FLOW'],404]`. The failure is a forbidden eager read, not missing migration setup. The reviewer inspected evidence and support provenance; no additional execution is claimed.

```text
39b43166e3b06fcf34f8f9f30aff96b3f59ade5ea0fefd222440e50e7ad0fc81  tests/InstallationProcess/pilot_http_auth_001_test.php
9f8893e0f87d158dc76c229f681aedb970d7beeb306d59362d55cf467a671f86  tests/Support/production_environment_source_spy.php
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
```

No blocking test findings. Minimal production correction may proceed separately from the test-only frontier slice, retaining all inherited and new route expectations. Focused auth GREEN, original/composition HTTP regression and independent Gate 5 remain required. Only this review record was authored by the reviewer.
