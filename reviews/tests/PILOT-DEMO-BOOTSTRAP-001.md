# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 3 — fresh independent restart after Gate 5 safety findings
- Reviewer: separately tasked agent `/root/bootstrap_test_review_safety`
- Independence: reviewer authored neither specification, test, nor Gate 4 implementation
- Test author: separately tasked agent (commit author `antropophag`)
- Reviewed commit: `a1fca72`
- Specification commit: `71e5e50`
- Specification: `specs/PILOT-DEMO-BOOTSTRAP-001.md`, version `0.1`, `APPROVED`
- Public seam: separate `php bin/fmonitor2-pilot-demo.php [start|reset|status|cleanup]` process, its printed loopback URL, and browser-shaped HTTP requests to production `public/router.php`
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

None.

- **Gate 5 safety findings are executable — pass.** The corrective test distinguishes the missing protections with four independent observations: a restart of the initial generation must preserve the complete actionable initial projection; an ordinary built-in-server composition must still reject a same-origin plain-HTTP command; reset with its configured port occupied must preserve byte-identical `active.json`, the prior generation tree, and the publicly usable prior process state; and cleanup must preserve a forged same-fingerprint generation plus same-prefix table when its owner file lacks the independent database nonce marker.
- **Traceability and allowed seams — pass.** These assertions trace to specification sections 2, 3, 4, 6, 7, and 9. Process behavior is observed through browser-shaped requests to the production router. SQL is used only for the explicitly permitted isolated fixture/catalog and ownership-containment evidence; it is not an oracle for preparation, registration, opening, restart process state, or artifact behavior.
- **Sensitivity — pass.** The ordinary-router assertion fails against reviewed production commit `316e8b7` with `303` instead of the independently fixed `403 / Invalid request.\n`, directly exposing the demo-only HTTP relaxation leaking into normal composition. The occupied-port checks would catch premature active-manifest replacement or mutation of generation 1. The interrupted fixture now has a plausible owner marker and artifact directory, so accepting filesystem presence as completeness is observable. The forged-generation case specifically defeats cleanup based only on path, fingerprint, generation number, or table-prefix agreement.
- **Expected-value independence — pass.** Expected status, error bytes, routes, fixture people and dates, initial/final labels, prefix grammar, generation numbers, immutable tree bytes, and ownership outcomes are literal consequences of the approved specification. They are not derived from production output, private bootstrap methods, renderer output, or process-table business rows.
- **Rejected cases and preservation — pass.** The new cases verify both rejection and absence of collateral effects: a rejected ordinary HTTP POST leaves the demo queue unchanged; occupied-port reset leaves the active generation usable; foreign marker/prefix and forged marker/table inputs remain intact; interrupted generation 2 remains inactive while reset advances to generation 3.
- **Determinism, bounds, and cleanup — pass.** Ports, database names, test home, and fixture tokens remain unique. All child collection remains deadline-bounded with TERM/KILL escalation and the collector self-check. The independent RED completed in 16.0 seconds; the outer `finally` removed both unique databases and the unique home, and post-run process inspection found no bootstrap/router child. The added ordinary router is stopped in its own `finally`.

## RED evidence

```text
$ php -l tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_demo_bootstrap_001_test.php

$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PHP Fatal error: Uncaught TestFailure: ordinary built-in/router composition remains HTTPS-origin-only
Expected: array (0 => 403, 1 => 'Invalid request.\n')
Actual:   array (0 => 303, 1 => '')
at tests/InstallationProcess/pilot_demo_bootstrap_001_test.php:169
exit 255
```

The bootstrap provisions successfully, the public launch smoke and initial restart assertions pass, and execution reaches the new normal-composition request. This is the intended missing production safety behavior, not a syntax, MariaDB, fixture, port, or process-collection failure.

## Reviewed manifest

```text
71e5e50  specification
a1fca72  corrective test
e6b082c9b2ed2bd0c8aca370fa785dd2aa25a38901c12d620f8b6e1e1d048263  specs/PILOT-DEMO-BOOTSTRAP-001.md
67d5a8122a08a465ae4e35a2e5bb66051a860eaf9f3c272f76a6f55e1897537c  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
```

Any change to the specification, reviewed test, bootstrap, helper seam, or relevant production router/composition invalidates this approval and requires a fresh independent Gate 3 review.

## Required changes

None. Gate 3 is `APPROVED`; Gate 4 may make the minimal production changes required by this reviewed test without changing its expectations.

## Superseded review history

Earlier Gate 3 rounds reviewed the initial tracer, completeness/safety corrections, bounded process collection, and fixture database selection. This fresh approval supersedes those records for commit `a1fca72` after the Gate 5 safety restart.
