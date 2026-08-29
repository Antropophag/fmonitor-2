# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 3 — fresh review after canonical demo Host-binding regression
- Reviewer: separately tasked agent `/root/bootstrap_host_test_review`
- Independence: reviewer authored neither specification nor reviewed test
- Test author: separately tasked agent (commit author `antropophag`)
- Reviewed commit: `b7920be`
- Specification commit: `71e5e50`
- Specification: `specs/PILOT-DEMO-BOOTSTRAP-001.md`, version `0.1`, `APPROVED`
- Public seam: separate demo CLI and browser-shaped requests served by production `public/router.php`
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

- **Traceability — pass.** The added regression directly closes the Gate 5 finding against specification sections 4–5: the loopback plain-HTTP exception belongs only to the exact configured canonical Host, not to any syntactically valid loopback port supplied by a client.
- **Raw Host override — pass.** `pdbRequest()` selects the exact `Host` entry before serialization, removes it from the caller-supplied header map, and emits one `Host:` line. The attack request therefore reaches the real listener with exactly one non-canonical raw Host and cannot accidentally retain the helper's canonical Host.
- **Cookie and Origin confinement — pass.** The non-canonical GET must retain the exact production cookie attributes, including `Secure`, while preserving exact cookie name and independently constrained opaque value. A following POST reuses that cookie, sends a matching attacker-controlled plain-HTTP Origin and `Sec-Fetch-Site: same-origin`, and must receive the exact redacted `403` response. This distinguishes both security decisions: fixing only cookie relaxation reaches and fails the Origin assertion; fixing only Origin handling still fails the cookie assertion.
- **No mutation and canonical behavior — pass.** Exact public queue equality after rejection proves no process fact was added. The pre-existing canonical demo assertions remain in the same test immediately afterward: the trusted cookie must omit only `Secure`, and the full prepare/download/register/open browser journey must succeed. The ordinary production composition remains separately required to retain `Secure` and reject plain-HTTP Origin.
- **Expected-value independence — pass.** Host, routes, cookie contract, `403` response, and unchanged public projection derive from the approved specification and inherited production security contract, not from private implementation helpers or implementation-generated expected values.
- **Determinism, isolation, and cleanup — pass.** The focused test uses a distinct unused loopback port only as header data, does not start a listener there, and retains the existing bounded MariaDB/home fixtures and cleanup path. No demo server or matching test-state directory remained after the RED run.

No blocking or non-blocking findings.

## RED evidence

```text
$ php -l tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_demo_bootstrap_001_test.php

$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PHP Fatal error: Uncaught TestFailure: demo nonce never relaxes cookie for noncanonical Host
Expected: array (
  'httponly' => NULL,
  'path' => '/pilot',
  'samesite' => 'Strict',
  'secure' => NULL,
)
Actual: array (
  'httponly' => NULL,
  'path' => '/pilot',
  'samesite' => 'Strict',
)
exit 255
```

The RED is honest: startup, persistence restart, canonical initial projection, form rendering, and identity-spoof checks all succeeded first. The reviewed test then failed at the new public response-header assertion because current production incorrectly removes `Secure` for a non-canonical loopback Host. No production code was changed by this Gate 3 review.

## Reviewed manifest

```text
71e5e50  specification
b7920be  canonical Host-binding regression test
e6b082c9b2ed2bd0c8aca370fa785dd2aa25a38901c12d620f8b6e1e1d048263  specs/PILOT-DEMO-BOOTSTRAP-001.md
a967fa5da4fd038933236fc758d72e7f4fa1161449c29c54dceff3b7bc478ace  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
fc108be099779dbacd3b47965b55bdf9cbbdd54c76fe13054b275ad13a6b6331  app/PilotHttp/PilotE2ECoordinator.php
```

## Gate decision

Gate 3 is `APPROVED`. Gate 4 may minimally bind both the trusted-demo cookie and plain-HTTP Origin decisions to the exact configured canonical Host without changing reviewed test expectations.

## Superseded review history

The prior Gate 3 approval for cookie-parser commit `cc5dfe3` remains historical evidence for that earlier test boundary. This fresh approval covers corrective commit `b7920be` and supersedes it as the current Gate 3 input for the Host-binding implementation.
