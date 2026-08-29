# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.2

- Gate: 3 — fresh independent re-review of post-bind transitive CSS identity mismatch
- Reviewer: separately tasked agent `/root/bootstrap_smoke_test_rereview`
- Independence: reviewer authored neither specification nor reviewed test
- Specification: `919383966f962fb9811a5bf6350536310de03683`
- Reviewed test commit: `834d6831e45e575f8c24619f0928fa0a45da545d`
- Review date: 2026-08-29
- Verdict: `APPROVED`

## Findings

No blocking findings.

The sole change from the previously reviewed test replaces the unsupported exact exit-code oracle `70` with the specified `exitCode !== 0`. It preserves the independently specified exact `SHLZ_ASSETS_UNAVAILABLE` stdout, empty stderr, no-timeout, stopped-listener, no-active-generation, and no-server-marker assertions.

## Review checks

- Traceability: the sensitivity exercises the v0.2 sections 4–5 post-bind full transitive graph smoke requirement.
- Public seam: the test launches the CLI as a separate process and observes its public HTTP listener and process result; it does not invoke production internals or inspect business facts through SQL.
- Sensitivity: preflight succeeds, the adversary waits for the listener, atomically replaces the last manifest member without changing size, and current production fails on the exact missing classification.
- Expected-value independence: `nonzero` and `SHLZ_ASSETS_UNAVAILABLE` come directly from specification `9193839`; no implementation-selected exit value remains.
- Determinism and isolation: the fixture stays at the exact 256-member graph bound and below 8 MiB, uses task-owned paths/database/port, and cleanup leaves no demo/router process residue.
- Regression strength: an implementation that reports the generic `STARTUP_FAILED`, leaves the listener running, activates the generation, writes server residue, times out, or leaks stderr remains rejected.

## RED verification evidence

```text
$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
RED at post-spawn transitive mismatch exact failure
Expected stdout: {"ok":false,"reason":"SHLZ_ASSETS_UNAVAILABLE"}
Actual stdout:   {"ok":false,"reason":"STARTUP_FAILED"}
stderr:          empty
timedOut:        false
process exit:    255 (test assertion failure)

$ ps -eo pid=,args= | rg 'php -S 127\\.0\\.0\\.1|fmonitor2-pilot-demo\\.php'
No demo/router process residue.
```

Gate 3 is approved for test commit `834d6831e45e575f8c24619f0928fa0a45da545d`. Gate 4 may implement only the reviewed post-bind error classification behavior without changing the approved expectation.
