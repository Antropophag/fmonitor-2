# Independent Gate 3 review — PRODUCTION-RUNTIME-FOUNDATION-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: separately tasked agent `/root/runtime_tests`
- Verdict: **APPROVED (BOUNDED)**

This record approves only the production runtime foundation exercised by the
reviewed assertions. The full `PRODUCTION-HTTP-RUNTIME-001` Gate 3 remains
`CHANGES_REQUESTED` in its separate review.

## Exact reviewed artifacts

```text
9635975c55612c482981fa1c81092ae2ff6742b629503829f04b22718106e94c  openspec/changes/production-http-runtime/specs/operations/production-http-runtime/spec.md
5f711667a415721015f9faaf39074e78e8890c5406ac84f381f06273922fd7c8  openspec/changes/production-http-runtime/design.md
0fed2e8dfd236a46838156610bc4c7208fb4b3c9f453236d6b7e013f2ca45a71  specs/PRODUCTION-HTTP-RUNTIME-001.md
da9f97fc895a52dab0f061e1885abf62be82f94728d361cb311a83522bb5f4a5  tests/Runtime/production_runtime_contract_001_test.php
15120d98acd511aca5c90c05708cf4199f2c46568df19775de5a24e83d91c307  tests/Runtime/production_runtime_compose_001_test.php
```

## Approved acceptance boundary

Gate 4 may implement only enough foundation to satisfy these reviewed outcomes:

- a separate production deploy tree with one image and separate foreground nginx
  and PHP-FPM services, without `php -S`, `socat`, demo manifest/start/bootstrap;
- nginx sends application requests to `public/runtime.php`, which delegates the
  existing composite rapid router, while migrations remain a one-shot service;
- all fifteen explicit configuration values are present in configuration and
  Compose; missing values and the reviewed port/host/prefix/path/scheme invalid
  classes fail with stable secret-free `CONFIGURATION_INVALID`;
- the real isolated Compose contour can run prepare then migrate, provision a
  DML-only runtime principal, start nginx/FPM, and serve successful live/ready,
  login GET with a CSRF field, nonempty pilot CSS, unknown-route 404, and untrusted
  Host 400;
- PHP runs as UID 10001, its state volume remains mounted across a process restart,
  and live/ready distinguish a DB outage and recover when DB returns.

These are public artifact/configuration and real process/HTTP seams. The Compose
project and volumes are unique per run and removed in `finally`.

## Demonstrated RED

```text
$ php tests/Runtime/production_runtime_contract_001_test.php
Fatal error: Uncaught TestFailure: INTENTIONAL_RED: required production runtime file deploy/runtime/Dockerfile
Expected: true
Actual: false
exit 255

$ php tests/Runtime/production_runtime_compose_001_test.php
Fatal error: Uncaught TestFailure: INTENTIONAL_RED: production Compose definition exists
Expected: true
Actual: false
exit 255
```

Both failures are the intended absent production runtime artifacts. Syntax checks
pass, and neither failure depends on Docker or database setup.

## Explicitly pending

This bounded approval does not cover authenticated login/session continuity,
valid or invalid CSRF POST behavior, checklist/mutating routes under DML-only
privileges, real fact/history/session/artifact persistence, unsafe storage
preparation, missing-schema no-repair evidence, secret-free logs/bodies, or
graceful `SIGQUIT` drain and orphan handling. Those blockers remain in
`reviews/tests/PRODUCTION-HTTP-RUNTIME-001.md` and need a later Gate 2/3 review.

## Verdict

**APPROVED (BOUNDED).** The reviewed tests are traceable, deterministic at their
stated foundation seams, and sensitive to the missing packaging/configuration and
basic real nginx/FPM outcomes. Test expectation changes require rereview.
