# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 3 — fresh independent final re-review
- Reviewer: separately tasked agent `/root/bootstrap_test_review_final`
- Test author: separately tasked agent (commit author `antropophag`)
- Reviewed commit: `76149d0d3f0eeebff2f96d8a67584204f83b168b`
- Specification commit: `71e5e5066a26b3a7d9e5d2ee08b27f14e205ed94`
- Specification: `specs/PILOT-DEMO-BOOTSTRAP-001.md`, version `0.1`, `APPROVED`
- Public seam: separate `php bin/fmonitor2-pilot-demo.php [start|reset|status|cleanup]` process, its printed loopback URL, and browser-shaped HTTP requests
- Date: `2026-08-29`
- Verdict: `CHANGES_REQUESTED`

The test was reviewed independently against the approved specification and all findings in the preceding rejection. Commit `76149d0` resolves the earlier coverage and seam-boundary findings, but one deterministic-execution blocker remains.

## Finding

1. **Negative CLI probes have no enforceable runtime bound and can bypass cleanup.** `pdbRun()` applies stream timeouts to stdout/stderr, but then calls blocking `proc_close()` without checking or terminating the child. Stream timeouts do not impose a process deadline. A defective Gate 4 implementation that hangs, waits indefinitely, or incorrectly starts the foreground server for `status`, invalid arguments, occupied-port `start`, CSS failure, running-server `reset`/`cleanup`, marker collision, prefix collision, or final `cleanup` can therefore hang this acceptance test indefinitely. Because control never returns to the outer `finally`, its server/database/home cleanup is also not guaranteed. `pdbContract()` has the same unbounded subprocess shape for the inherited support-oracle set. Replace both with a deadline-driven nonblocking collector which polls process state, terminates on timeout (with bounded escalation), closes pipes/process, and reports a `TestFailure`; preserve partial stdout/stderr for diagnosis with secret-safe assertions. Add or extract a focused self-check proving a deliberately sleeping child is bounded and reaped, rather than relying on the outer shell `timeout` used only during review.

## Prior blockers verified resolved

- Missing and incompatible sibling `shlz-ui` CSS are exercised through an isolated sibling-checkout layout. The adversary changes only task-owned copies/links under the test home and leaves the real sibling checkout untouched.
- Mismatched generation ownership and exact next-generation prefix collision are both driven through the public CLI and protected by preservation sentinels.
- Production composition is protected by executing the approved migration-runner, importer, artifact-store, and pilot E2E public contracts; the bootstrap journey still uses only CLI/HTTP for business assertions.
- Direct SQL is now limited to bootstrap-owned catalog discovery and ownership/collision containment sentinels. It no longer observes process events or snapshots business rows as an oracle.
- CLI grammar, canonical ports, occupied bind, launch smoke, spoofed identity, non-imported object, artifact immutability, restart persistence, running-server refusal, interrupted generation behavior, backup preservation, reset, and cleanup containment are covered with independently fixed values.
- The outer `finally` correctly stops a tracked server, drops both task databases, and removes the task-owned home when normal control or an exception reaches it; filesystem traversal treats symlinks as links and does not descend into the shared source checkout.

## RED evidence

Commands run from repository root:

```text
$ php -l tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_demo_bootstrap_001_test.php

$ timeout 30s php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PHP Fatal error: Uncaught TestFailure: extra CLI argument exact redacted rejection
Expected: [64, "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", ""]
Actual:   [1, "", "Could not open input file: bin/fmonitor2-pilot-demo.php\n"]
```

Exit status: `255` (elapsed approximately 14.4 seconds, before the external 30-second review guard).

The RED is deterministic and reaches the first executable expectation. Its cause is the absent approved public CLI seam, not syntax, MariaDB setup, fixture setup, or HTTP behavior.

## Required change

Bound and reap every subprocess started by `pdbRun()` and `pdbContract()`, add a focused timeout/reaping proof, and request another fresh Gate 3 review. Gate 4 must not begin on test commit `76149d0d3f0eeebff2f96d8a67584204f83b168b`.

## Reviewed artifact manifest

```text
e6b082c9b2ed2bd0c8aca370fa785dd2aa25a38901c12d620f8b6e1e1d048263  specs/PILOT-DEMO-BOOTSTRAP-001.md
c489731b6719c633c20b5bed61685667185f1107e329214d974c8af5e3793398  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
45fe94035a3295b14f47a8bc7dc3941d0b51f1796b448521438923f36b694137  tests/InstallationProcess/production_migration_runner_001_test.php
08b991f19719eacc1368ce6302d51854006323dc928f184ae5892578519b03e1  tests/InstallationProcess/pilot_case_import_001_test.php
952a3e5cdf515695712c149c63a7ff9783dad0d7f8db3c8583779c768c3688fc  tests/InstallationProcess/artifact_store_001_test.php
2929830a6808fac914557b75b9689f13f2b9ae95beb69dfc3ed8d71887ad3a14  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Any change to the specification, executable test, test bootstrap, or relevant support-oracle set invalidates this verdict and requires a fresh independent review.
