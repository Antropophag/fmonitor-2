# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.2

- Gate: 3 — fresh independent review of post-bind transitive CSS identity mismatch
- Reviewer: separately tasked agent `/root/bootstrap_smoke_test_review`
- Independence: reviewer authored neither specification nor reviewed test
- Specification: `919383966f962fb9811a5bf6350536310de03683`
- Reviewed test commit: `bd1d648`
- Review date: 2026-08-29
- Verdict: `CHANGES_REQUESTED`

## Finding

### Blocking — exit `70` is not an independently specified expected value

`tests/InstallationProcess/pilot_demo_bootstrap_001_test.php:149` compares the complete result to an array whose first member is exact exit `70`.

The approved v0.2 specification requires a `SHLZ_ASSETS_UNAVAILABLE` reason and a **nonzero** exit for missing, invalid, identity-failed, or post-bind mismatched shlz graph assets. It specifies exact exit `64` only for CLI grammar errors; it does not assign exit `70` to this failure. Moreover, the already-established preflight path for the same public `SHLZ_ASSETS_UNAVAILABLE` class exits `78`. Exact `70` therefore cannot be derived independently from the specification and would reject a conforming implementation that preserves the existing shlz failure exit while correcting the post-bind reason.

Required change: assert that the exit is nonzero (or amend and reapprove Gate 1 with a single exact exit-code contract for this failure class). Keep the exact stdout, empty stderr, no-timeout, stopped-server, no-active-generation, and no-server-marker assertions.

## Other review checks

- Traceability: the sensitivity directly exercises sections 4–5 through the separate CLI and same-origin HTTP listener.
- Public seam: no production private method, parser, test hook, or SQL business-state oracle is used.
- Sensitivity: preflight succeeds, the adversary waits for the child listener, atomically swaps the final transitive member, and the observed RED is specifically the missing post-bind public classification.
- Graph oracle: the `./` correction is valid; dot segments now match the inherited accepted relative-import grammar.
- Bounds: the fixture has exactly 256 unique members including `shlz.css`, stays below 8 MiB, and places the swapped member last in sorted traversal.
- Cleanup/isolation: `finally` stops the asynchronous process, drops the dedicated database, and removes the task-owned home; the failed run left no demo/router process residue.

## Verification evidence

```text
$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
RED at post-spawn transitive mismatch exact failure
Expected reason: SHLZ_ASSETS_UNAVAILABLE
Actual reason:   STARTUP_FAILED
Actual exit:     70
stderr:          empty
timedOut:        false

$ ps -eo pid=,args= | rg 'php -S 127\\.0\\.0\\.1|fmonitor2-pilot-demo\\.php'
No demo/router process residue.
```

Gate 3 remains closed for test commit `bd1d648` until the unsupported exact exit-code oracle is corrected and independently re-reviewed.
