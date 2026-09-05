# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 production safe-log v57

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/safe_log_gate3_v57`
- Reviewed RED commit: `90f9383facf4aaee91b602b02008e9dd58333085`
- Approved executable-spec commit: `bcdaedb7cd7cb7b80684d29f432a3d20a40e0177`
- Independent Gate 1 approval commit: `b510efc57b7dc4f7c3965f0b98e33bcfcedb721c`
- Prior Gate 3 correction requests: `e21565cc567d9783e433b1cd7a1c37ab4c242eb8`, `18ddd12f80ffe7a1873a0940a26d3532b94dffbe`
- Verdict: **CHANGES_REQUESTED**

## Independence and scope

I did not author the owner decision, planning amendment, executable
specification, production implementation, reviewed test, RED/correction
evidence, prior Gate 3 reviews, or Gate 1 reviews. This append-only review
record is my only repository change. The verdict covers only the corrected v57
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
b64ea32ddfdad6bc38fc7dc4c83da5ddf566941f7179a4c9f1027bcaf4676641  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
356b4bf821296e2b8e6ff79eeaab28cff963218c9a00807efdbb0d9be68c2f32  docs/operations/assignment-order-original-production-safe-log-red-correction-v57-2026-09-05.md
```

## Prior blocker disposition

The v55 UID-sensitivity blocker remains resolved: the task-owned sentinel is
copied by container root, the PHP probe runs as exact effective UID/GID 65534,
and the child proves existing regular non-symlink exact-`0600` identity with a
different owner UID before invoking the public factory. Exit `70` is setup
failure and the missing-file oracle remains separate.

The v56 blocking-read and ordinary cleanup defects are substantially corrected.
Both pipes are nonblocking, the running loop uses a ten-second monotonic
deadline, and `finally` attempts TERM, a monotonic grace period, KILL, pipe
closure, and `proc_close` for the exact child. Timeout, launch/read failure,
over-limit output detected inside the loop, unavailable reap status, exit 70,
signal/infrastructure exits, and unrecognized protocol tuples cannot become a
behavioral pass: each becomes `SETUP_FAILURE`. The three semantic protocols are
kept distinct. A normal reproduced run left no Docker container or task-owned
temporary directory behind.

## Review findings

- **Traceability, seam, and sensitivity:** the test cites the stable spec ID,
  constructs through the public production factory, and observes cleanup via
  the returned real application. Reflection, exact exception shape, exact
  result tuple, and exact whole-file JSON/LF equality detect the mandatory
  field, permissive validation, inert/wrong logging, truncation, duplicate or
  extra output, and sensitive filename/exception leakage.
- **Invalid matrix and ordering:** missing, relative, non-canonical, symlink,
  directory, wrong mode, controlled wrong owner, and device cases are distinct.
  Sentinels cover no create/repair. The query sentinel and absent private root
  prove invalid safe-log rejection must precede database and private-storage
  access and use the fixed redacted construction error.
- **Append and result preservation:** the direct logger fixture preserves a
  prior line and appends one exact line. The production cleanup probe requires
  the selected `REJECTED/INVALID_PDF` result to survive and requires exactly one
  redacted stream-close diagnostic through the configured production file.
- **Remaining bounded-output defect:** after the child is first observed not
  running, `$runBounded` performs `stream_get_contents()` on both nonblocking
  pipes and appends those bytes, but never reapplies the `4096`-byte limits.
  A child can write more than 4096 bytes, have the loop read exactly 4096, exit,
  and leave further bytes for this final drain. That tuple is eventually an
  unrecognized `SETUP_FAILURE`, but the output accumulation itself has already
  exceeded the asserted bound. Therefore the correction evidence statement
  that each output accumulation is bounded is false, and the verifier's
  bounded-read/output requirement is not yet proven. Apply the same length
  guard immediately after each final-drain append (or use one helper that
  enforces the cap on every append), then capture fresh RED evidence and obtain
  a fresh independent Gate 3 review.

## Independent RED reproduction

```text
php -l tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php

php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
```

The focused command exited `255` with test-owned `INTENDED_RED` and exactly the
expected eleven production gaps: missing exact third config field, missing
production cleanup append, acceptance of missing/relative/non-canonical/
symlink/non-regular/wrong-mode/device paths, controlled wrong-owner acceptance,
and the private-root-first non-canonical exception. There was no
`SETUP_FAILURE`. Afterward, `docker ps --filter
ancestor=fmonitor2-php-test:latest` returned no rows, no
`/tmp/aoou-production-boundary-*` directory remained, `git diff --check` was
clean, and the worktree was unchanged before adding this record.

## Decision

**CHANGES_REQUESTED** for Gate 3 at exact RED commit
`90f9383facf4aaee91b602b02008e9dd58333085`.

Gate 4 is not authorized. Preserve the corrected UID fixture, monotonic
deadline, TERM/KILL/finally/reap behavior, exact semantic protocols, setup
classification, and all public-seam assertions. Close only the final-drain
bound gap, record a fresh intended RED, and request a fresh independent Gate 3.
