# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 production safe-log v55

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/safe_log_gate3_v55`
- Reviewed RED commit: `95625ba0db227dd03f4119303a5ea78f50d80cd5`
- Approved executable-spec commit: `bcdaedb7cd7cb7b80684d29f432a3d20a40e0177`
- Independent Gate 1 approval commit: `b510efc57b7dc4f7c3965f0b98e33bcfcedb721c`
- Verdict: **CHANGES_REQUESTED**

## Independence and scope

I did not author the owner decision, planning amendment, executable
specification, production implementation, reviewed test, RED evidence, or Gate
1 reviews. This append-only review record is my only repository change. The
verdict covers the v55 production-command-factory `safeLogFile` RED only; it
does not review parser work, the distinct worker/evidence-reader config
contract, production implementation, or Gate 5 readiness.

## Exact reviewed identities

```text
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
09fbfb8d9a5405989ad40150d011b557d8fea1ba030530fbf68ce06c3a710abb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
482fb11555e9f10968a2485c2f22e863543c67fa78c41cd8148ce3f1b0e4ce09  openspec/changes/replace-pilot-registration-with-original-upload/design.md
42a03479da33ecc99f2b6e6f76600480a723e7a8c8e25296d33285584d8d3c88  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
dd4c5f05ddfadfcff57a8f4303165a806a43a4b339227407ceac6a37b8d7d2b2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d57b1ca30f5a55077c87046f9c11ace369c08618a05b123f0e0c3de89d4bfc6f  docs/operations/assignment-order-original-production-safe-log-gate1-rereview-2026-09-05.md
ad1db8a37f5c3413746548cd4a22ce99e5a5bb7e46b4f015aadacc12d8d9f437  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
a3a65937dd6f0d7427efdf7fdb67d1fa9883ccc830ea85c51dd5ef340346ba13  docs/operations/assignment-order-original-production-safe-log-red-evidence-v55-2026-09-05.md
```

## Review findings

- **Traceability and seam:** the test cites the stable specification ID and
  exercises `ProductionAssignmentOrderOriginalFactory::create(mysqli, config)`
  plus the returned real `AssignmentOrderOriginalApplication`. The cleanup
  diagnostic is observed through that public application seam. Direct logger
  assertions are supporting boundary checks and do not substitute for the
  production-factory probe.
- **Expected-value independence and sensitivity:** constructor parameter names,
  the fixed exception class/message/code/previous shape, SHA-256-derived
  correlation, event, sole safe field, sequence, and exact JSON/LF bytes come
  from the approved executable contract. Exact whole-file equality detects a
  no-op observer, wrong target, truncation, extra lines or fields, and leakage
  of the filename or injected exception detail.
- **Valid behavior and append-only preservation:** a pre-created owned regular
  `0600` file is used by the real production application; a forced stream-close
  cleanup fault preserves the selected `REJECTED/INVALID_PDF` result and appends
  exactly one safe diagnostic. The separate prior-line oracle proves that the
  shared file logger appends without rewriting existing bytes. Existing
  cleanup/release event grammar remains inherited; this probe is sufficient to
  catch failure to bind that real observer in the production factory.
- **Invalid matrix:** missing, relative, non-canonical, configured-path symlink,
  directory, wrong mode, and character device cases are sensitive on the review
  host. The proposed wrong-owner oracle is not deterministic across supported
  environments: it hard-codes `/etc/master.passwd`. That path is a root-owned
  regular `0600` file on this macOS host (effective test UID `501`), but it is
  commonly absent on Linux. When absent, the `wrong-owner` case is only another
  missing-file case, so an implementation that enforces existence but omits the
  effective-owner check can pass. The test neither constructs a controlled
  distinct-owner fixture nor asserts the fixture precondition before invoking
  the factory.
- **No create or repair:** sentinels prove the missing file is not created, the
  `0644` file bytes/mode are unchanged, and the symlink plus target bytes remain
  unchanged.
- **Fail-before-resource ordering:** an invalid missing safe-log is paired with
  a mysqli query sentinel and an absent private root. The test requires the
  canonical production-configuration exception, zero database calls, and no
  private-root creation. A private-root-first implementation therefore fails
  for the intended observable reason.
- **Isolation and determinism:** all mutable files and the database have random
  task-owned identities and are removed in `finally`; no production secret or
  real document is used. The stable platform fixtures used for wrong-owner and
  device classification were inspected during review. The focused rerun left
  the worktree unchanged.

## Independent RED reproduction

Command:

```text
php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

Result: exit `255`, with the test-owned `INTENDED_RED` marker and exactly the
eleven intended failures recorded in Gate 2 evidence: absent exact third config
field, absent production cleanup append, acceptance of all eight invalid path
cases, and the non-canonical exception shape exposing private-root-first
construction. The MariaDB fixture and valid production upload completed before
those assertions, so this is behavior RED rather than broken setup.

Mechanical check:

```text
php -l tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

## Decision

**CHANGES_REQUESTED** for Gate 3 at exact RED commit
`95625ba0db227dd03f4119303a5ea78f50d80cd5`.

Before Gate 4, replace the ambient `/etc/master.passwd` dependency with a
controlled wrong-owner fixture whose distinct UID is proven by the harness, or
use another deterministic isolated mechanism that fails setup unless it has
actually established `file-owner UID != effective UID`. Preserve the existing
missing-file case separately. Then capture a fresh intended RED and obtain a
fresh independent Gate 3 review. No production implementation is authorized by
this verdict.
