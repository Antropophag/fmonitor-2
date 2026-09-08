# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 production safe-log v56

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/safe_log_gate3_v56`
- Reviewed RED commit: `f0897214f7f52d7758cac69bfe6b9caaee3830c2`
- Approved executable-spec commit: `bcdaedb7cd7cb7b80684d29f432a3d20a40e0177`
- Independent Gate 1 approval commit: `b510efc57b7dc4f7c3965f0b98e33bcfcedb721c`
- Prior Gate 3 correction request: `e21565cc567d9783e433b1cd7a1c37ab4c242eb8`
- Verdict: **CHANGES_REQUESTED**

## Independence and scope

I did not author the owner decision, planning amendment, executable
specification, production implementation, reviewed test, RED/correction
evidence, prior Gate 3 review, or Gate 1 reviews. This append-only review record
is my only repository change. The verdict covers only the corrected v56
production-command-factory `safeLogFile` RED. It does not approve parser work,
the distinct worker/evidence-reader contract, production implementation, or
Gate 5 readiness.

## Exact reviewed identities

```text
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
09fbfb8d9a5405989ad40150d011b557d8fea1ba030530fbf68ce06c3a710abb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
482fb11555e9f10968a2485c2f22e863543c67fa78c41cd8148ce3f1b0e4ce09  openspec/changes/replace-pilot-registration-with-original-upload/design.md
42a03479da33ecc99f2b6e6f76600480a723e7a8c8e25296d33285584d8d3c88  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
dd4c5f05ddfadfcff57a8f4303165a806a43a4b339227407ceac6a37b8d7d2b2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d57b1ca30f5a55077c87046f9c11ace369c08618a05b123f0e0c3de89d4bfc6f  docs/operations/assignment-order-original-production-safe-log-gate1-rereview-2026-09-05.md
46f24422011c0493595f3f690a2839753a43e4cab427e255faa2beaf4e912a81  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
2e442e608ba77e32abd4a56e4bf01d9cd50e4656e651296fffab11bc896239d4  docs/operations/assignment-order-original-production-safe-log-red-correction-v56-2026-09-05.md
```

## Prior blocker disposition

The v55 ambient `/etc/master.passwd` blocker is resolved. The parent creates a
separate task-owned `0600` sentinel, bind-mounts its directory read-only, and a
container-root step copies the bytes to `/tmp/wrong-owner.log`. The PHP child
runs with exact effective UID/GID `65534` and checks with `lstat` that the
configured entry exists, is regular, is not a symlink, has exact mode `0600`,
and has an owner UID distinct from its effective UID before invoking the public
factory. Exit `70` prevents an unestablished precondition from counting as the
wrong-owner rejection. The ordinary missing-file case remains a separate path
and assertion, so the owner predicate can no longer silently collapse into it.

## Review findings

- **Traceability, public seam, and sensitivity:** the test cites the stable
  specification ID, constructs through
  `ProductionAssignmentOrderOriginalFactory::create(mysqli, config)`, and uses
  the returned real application for the cleanup diagnostic. Reflection detects
  the exact mandatory third field. Exact exception shape and whole-file JSON/LF
  equality detect permissive config, wrong target, no-op logging, truncation,
  duplicate/extra output, unsafe fields, and a changed selected result.
- **Invalid matrix and no repair:** missing, relative, non-canonical, symlink,
  directory, wrong mode, controlled wrong owner, and device cases are distinct.
  Sentinels prove no creation, chmod/content repair, symlink replacement, or
  target mutation for the relevant host-side cases.
- **Ordering and redaction:** the invalid-safe-log probe requires the fixed
  redacted production configuration exception before any database query or
  private-root creation. The exact cleanup line excludes filename and injected
  exception detail while preserving the selected `REJECTED/INVALID_PDF`
  outcome. The prior-line direct logger oracle separately proves append rather
  than rewrite.
- **Blocking boundedness and cleanup defect:** the new Docker child is not
  bounded. Both `stream_get_contents()` calls are blocking reads to EOF, with
  no nonblocking/select loop, monotonic deadline, or timeout. If Docker, the
  shell, `install`, `setpriv`, PHP startup, filesystem access, or the reviewed
  factory hangs, the test waits forever. The process handle is scoped inside
  the main `try` and there is no child-specific `finally` that terminates and
  reaps it. Therefore an exception or interruption before `proc_close()` can
  leave the Docker client/container alive while outer cleanup removes its
  bind-mounted fixture. `docker run --rm` removes a container only after it
  exits; it is not a parent-side deadline or reap guarantee. This violates the
  executable spec's verifier requirement to bound reads/waits and terminate
  then reap every child in `finally`, and it fails the specifically requested
  deterministic/bounded/clean Gate 3 criterion.
- **Setup classification weakness:** only child exit `70` and `proc_open`
  failure are called `SETUP_FAILURE`. Docker infrastructure exits (for example
  image/runtime or entrypoint failures `125`/`126`/`127`) and signal exits are
  currently recorded as a wrong-owner “non-canonical exception shape”. They
  cannot make this RED pass silently, but they misclassify broken setup as
  product behavior and make the captured RED less trustworthy. The corrected
  harness should reserve explicit semantic exits for accepted, canonical
  rejection, and non-canonical rejection, and fail closed as `SETUP_FAILURE`
  for infrastructure/unrecognized exits.

## Independent RED reproduction

Command:

```text
php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

Result: exit `255` after about `0.48s`, with test-owned `INTENDED_RED` and the
expected eleven production gaps: absent exact third config field, absent
cleanup append, acceptance of missing/relative/non-canonical/symlink/
non-regular/wrong-mode/device paths, controlled wrong-owner acceptance, and the
private-root-first non-canonical exception. No `SETUP_FAILURE` occurred. The
database and task-owned temporary directory were removed, and no container from
the test image remained after this completed run. This successful fast run does
not supply the missing guarantee for a hung or exceptional run.

Mechanical checks:

```text
php -l tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php

git diff --check
(no output; exit 0)
```

## Decision

**CHANGES_REQUESTED** for Gate 3 at exact RED commit
`f0897214f7f52d7758cac69bfe6b9caaee3830c2`.

Before Gate 4, keep the controlled distinct-UID and separate missing-file
oracles, but add a short monotonic parent deadline with bounded nonblocking pipe
reads and child termination/reaping in a `finally` path. Treat timeout,
infrastructure/unrecognized exit, and inability to reap as explicit
`SETUP_FAILURE`; retain distinct semantic results for accepted behavior and a
wrong exception shape. Capture fresh intended RED and obtain another fresh
independent Gate 3 review. No production implementation is authorized by this
verdict.
