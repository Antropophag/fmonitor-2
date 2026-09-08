# Independent test fixture review — PILOT-ROUTE-CSP-001 session root v1

Date: 2026-09-03  
Reviewer: separately tasked agent `csp_login_fixture_review`  
Reviewed commit: `ceac9d1777ccf750cde7a127a39bf236023c3e51`  
Verdict: **APPROVED**

The reviewer did not author the reviewed test or evidence and made no change to
production code. This append-only review record is the reviewer's only edit.

## Reviewed artifacts

- Owner-approved executable specification SHA-256:
  `47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef`
- Corrected login HTTP verifier SHA-256:
  `c5c3886805fe5050ea48e6dd2a1be0cf79312308eb75d3350d2dee9078118301`
- Session-root correction evidence SHA-256:
  `b3a21206b8b2de8df6f2332ea66e51a0bdbd27cd6d04202227c9bfd2b5163c34`

`git show ceac9d1` contains only the verifier fixture correction and its
operations evidence. It contains no production edit.

## Findings

The diff changes only `prclStart` and `prclStop`. All request scenarios,
expected status codes, literal CSP values, exact asset bytes/headers, forbidden
tokens, cache assertions, redirect assertions and final failure aggregation are
byte-unchanged from `ceac9d1^`.

Each server invocation creates a CSPRNG-named task root
`.../fmonitor2-session-storage-tests/prcl-<16 hex bytes>` with mode `0700` and
passes the complete now-required configuration explicitly:

- `FMONITOR_SESSION_STATE_ROOT=<that owned root>`;
- `FMONITOR_SESSION_INSTANCE=csp_login`;
- `FMONITOR_TRUSTED_REQUEST_SCHEME=http`.

The root is returned with the process handle. Normal `finally` teardown stops
the process, closes its pipes, recursively removes only that returned random
root (treating symlinks as leaves), and removes the shared parent only when it
is empty. The independent post-run filesystem probe found no remaining owned
root. This review does not broaden the fixture's authority to any sibling root.

The parent commit was reproduced in a detached home-directory worktree against
the live test database. It reached the HTTP fixture but stopped with
`TestFailure: setup: login cookie missing`, confirming the recorded pre-fix
setup failure. The exact reviewed commit passes against the same database. The
correction therefore restores the existing public-HTTP assertions rather than
changing their expected behavior.

## Independent verification

```text
git rev-parse HEAD
ceac9d1777ccf750cde7a127a39bf236023c3e51

sha256sum tests/InstallationProcess/pilot_route_csp_login_001_test.php
c5c3886805fe5050ea48e6dd2a1be0cf79312308eb75d3350d2dee9078118301  tests/InstallationProcess/pilot_route_csp_login_001_test.php

php -l tests/InstallationProcess/pilot_route_csp_login_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_route_csp_login_001_test.php

# detached worktree at ceac9d1^, DB 127.0.0.1:23306
php tests/InstallationProcess/pilot_route_csp_login_001_test.php
PHP Fatal error: Uncaught TestFailure: setup: login cookie missing

# ceac9d1, same DB environment
php tests/InstallationProcess/pilot_route_csp_login_001_test.php
pilot_route_csp_login_001_test: PASS

find /tmp/fmonitor2-session-storage-tests -maxdepth 2 -mindepth 1 -print
# no output
```

This approval is limited to the test-only session-root fixture correction. It
does not reapprove production CSP/session code, close the broader session
storage Gate 5, or substitute for exact-SHA regression and CI evidence.
