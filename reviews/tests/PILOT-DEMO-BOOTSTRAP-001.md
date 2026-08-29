# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 3 — fresh independent re-review after bounded-process correction
- Reviewer: separately tasked agent `/root/bootstrap_test_review_approve`
- Independence: reviewer authored neither specification nor test
- Test author: separately tasked agent (commit author `antropophag`)
- Reviewed commit: `c717251`
- Specification commit: `71e5e50`
- Specification: `specs/PILOT-DEMO-BOOTSTRAP-001.md`, version `0.1`, `APPROVED`
- Public seam: separate `php bin/fmonitor2-pilot-demo.php [start|reset|status|cleanup]` process, its printed loopback URL, and browser-shaped HTTP requests
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

None.

- **Prior blocker closed — pass.** `pdbRun()` and `pdbContract()` now share a nonblocking, deadline-driven collector. It polls process state, preserves partial stdout/stderr, sends bounded TERM then KILL escalation, drains and closes both output pipes, and calls `proc_close()` to reap the child. Every negative CLI probe and every inherited contract therefore has an enforceable local runtime bound.
- **Focused collector sensitivity — pass.** `pdbDeadlineSelfCheck()` runs before database/test-home setup, starts a child which writes `READY` and then sleeps for ten seconds, gives it a 150 ms deadline, and requires timeout detection, completion within 1.5 seconds, exact captured stdout/stderr, and (where POSIX inspection is available) absence of the child PID after reaping. A collector which merely configures stream timeouts, fails to terminate, loses buffered output, or leaves the child alive cannot pass.
- **Traceability and public seam — pass.** The test cites the approved v0.1 specification and exercises the CLI in separate processes, the printed loopback server, browser-shaped HTTP requests, cookies, PRG redirects and real downloads. SQL remains limited to isolated bootstrap fixture/catalog and ownership-containment evidence, not business-result assertions.
- **Expected-value independence and sensitivity — pass.** Exact actors, object IDs, dates, routes, labels, artifacts, status keys, generations and next step are literal specification values. Missing/incompatible shlz CSS, occupied port, foreign marker and prefix, interrupted generation, spoofed identity, excluded object, restart persistence, reset preservation and cleanup containment remain covered.
- **Inherited coverage — pass.** Before reaching the focused missing-CLI RED, the test executes and requires PASS from `PRODUCTION-MIGRATION-RUNNER-001`, `PILOT-CASE-IMPORT-001`, `ARTIFACT-STORE-001`, and `PILOT-E2E-FLOW-001`; no bytes in those four tests changed from the preceding review.
- **RED validity — pass.** The full test reaches the first CLI grammar assertion and fails because the approved executable seam does not yet exist: expected exit `64` and exact redacted JSON, actual exit `1` and `Could not open input file`. This is missing Gate 4 behavior, not setup, MariaDB, syntax, or subprocess-control failure.
- **Determinism, cleanup and orphan safety — pass.** The full RED completed in about 14.7 seconds. Its outer `finally` removed the unique test home and databases. Post-run inspection found no `.pilot-demo-test-homes` child and no `fmonitor2-pilot-demo.php` or bootstrap-test process.

Gate 4 may implement only enough production behavior to satisfy this reviewed test while preserving all approved expectations. Any specification or test change invalidates this approval and requires a fresh Gate 3 review.

## Verification evidence

```text
$ php -l tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
exit code: 0

$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PHP Fatal error: Uncaught TestFailure: extra CLI argument exact redacted rejection
Expected: [64, "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", ""]
Actual:   [1, "", "Could not open input file: bin/fmonitor2-pilot-demo.php\n"]
exit code: 255
elapsed: approximately 14.7 seconds

post-run .pilot-demo-test-homes children: 0
post-run target demo/test processes: 0
```

## Reviewed artifact manifest

```text
e6b082c9b2ed2bd0c8aca370fa785dd2aa25a38901c12d620f8b6e1e1d048263  specs/PILOT-DEMO-BOOTSTRAP-001.md
472551bfec326020c93e9a21faaa9dc81caf11b336066746e694820755d1faa5  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
45fe94035a3295b14f47a8bc7dc3941d0b51f1796b448521438923f36b694137  tests/InstallationProcess/production_migration_runner_001_test.php
08b991f19719eacc1368ce6302d51854006323dc928f184ae5892578519b03e1  tests/InstallationProcess/pilot_case_import_001_test.php
952a3e5cdf515695712c149c63a7ff9783dad0d7f8db3c8583779c768c3688fc  tests/InstallationProcess/artifact_store_001_test.php
2929830a6808fac914557b75b9689f13f2b9ae95beb69dfc3ed8d71887ad3a14  tests/InstallationProcess/pilot_e2e_flow_001_test.php
721a3a6e06ef1c9fb0d5ce6ea104d88591bf057a0afa64ab056ddd6562162886  docs/development-process.md
201885dc684287c1526c4657e5a9dd71f23d7dca74423fb5f329169e03fea358  PRODUCT.md
68f38cae8a69b33bb194e5b6f5d3809f4ddb90004d59af6b7a8a3c5b11870037  CONTEXT.md
```

The review record itself is metadata because a self-hash is circular.
