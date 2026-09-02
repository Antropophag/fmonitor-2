# Independent test review — PILOT-SESSION-STORAGE-001

Date: 2026-09-02  
Reviewer: separately tasked fresh agent `/root/session_test_review`  
Independence: reviewer did not author or edit the specification, tests, support
launcher, RED evidence, or production code  
Gate: 3  
Verdict: **CHANGES_REQUIRED**

## Reviewed contract and artifacts

Owner approval matches the reviewed executable contract exactly:

```text
2afa029374583b18ed06d6eb37f8c9e3857b3366ac5e516f1eb3b07de8ba8ad0  specs/PILOT-SESSION-STORAGE-001.md
```

Reviewed Gate 2 artifacts:

```text
a3a1f294534b6872d1ec1e2ef2de0dcac795a2d7857a2ad0c7b00650b92a96e6  tests/Support/PilotSessionStorageScenario.php
a82eb343f56676c66de795af49839a1539a74fbbb030950578c0c39c2b447ab0  tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
6e0bc6606ad3d5ffbe781e3a6ad3d409e4d59a7eba8ef8b855785eccb57bf4b1  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
```

The review also inspected the OpenSpec proposal/design/tasks, owner approval,
Gate 1 rereview, Gate 2 RED record, both pilot contracts, the current native
session consumers, and the delivery-process requirements.

## Blocking findings

### 1. The proposed runner is a self-attesting seam, so the tests are not sensitive to the behavior they claim

Both tests pass an arbitrary scenario name to the future
`tools/verification/pilot-session-storage-scenario.php` and accept its decoded
JSON object as the complete observation. Nothing outside that runner observes
the owned filesystem, process lifetime, HTTP exchange, response timing, logs,
cookies, consumer calls, Compose volume, or cleanup. A runner containing only a
map from the scenario names to the literal expected arrays would make every
assertion GREEN without a session adapter, without wiring either consumer, and
without performing a single filesystem or HTTP operation.

This fails Gate 3 sensitivity and public-seam requirements. A black-box runner
may provide controlled fault injection and orchestration, but test-owned code
must independently create inputs and observe material outputs. At minimum:

- filesystem scenarios must inspect exact paths, lstat/fstat-visible identity,
  modes, names, bytes, link/rename/unlink outcomes, before/after snapshots, and
  process/crash boundaries outside the implementation under test;
- HTTP scenarios must issue real requests to each public consumer and parse the
  raw status line, complete ordered/multivalue headers, body, cookie jar and
  redirect behavior rather than accept `status`, `secretsAbsent`,
  `singleOwner`, or `explicitCommitBeforeResponse` booleans from the runner;
- restart must be proven by a test-owned stop/start boundary and the same real
  cookie/CSRF/return-to bytes; cleanup must be checked by the parent after the
  child has exited;
- add an echo/fabricated-result adversary (or equivalent mutation probes) that
  demonstrates the tests reject a runner that merely returns the expected
  arrays.

### 2. Aggregate booleans do not exercise the normative fault and crash matrix

The specification requires separate fault injection at configuration,
mkdir/EEXIST/swap, every open/read/lock/write/fflush/fsync/rename/
directory-fsync/unlink/close phase, regeneration and destroy crash regions, GC,
both consumers, and exact GET/HEAD/POST responses. The current arrays collapse
many distinct requirements into one self-reported object, for example:

- `identity_swap_every_open_read_write_rename_unlink` returns only unavailable,
  foreign unchanged and no dual-valid ID;
- `write_fault_open_write_fflush_fsync_fstat` returns only two category names;
- consumer scenarios return only `singleOwner` and
  `explicitCommitBeforeResponse`;
- asset coverage returns three `200` values without exact CSS/JS/SVG/font
  requests or response evidence;
- cleanup and Compose restart are five/four booleans supplied by the same
  runner.

These assertions cannot identify a skipped phase, wrong operation ordering,
wrong old/stage/tombstone/new bytes, a response emitted before durable commit,
or cleanup performed by the wrong owner. Split or enrich the scenarios with
independently observed preconditions and postconditions for every normative
phase. Include boundary coverage for ID/file grammar and data length, exact
correlation/log redaction, invalid metadata/type preservation, lock timing and
ordering, stage/tombstone association, GC eligibility/order/limits, and both
success and failure protocol paths where the contract fixes behavior.

### 3. The captured RED proves only that the dispatcher file is absent

The reviewer reproduced both failures. Each suite reaches the task-owned temp
directory and fails on its first call because the runner file is missing. That
is an intended missing-artifact RED rather than an environment failure, and
syntax plus ordinary cleanup are healthy. However, none of the later scenarios
execute, and the missing-file failure does not demonstrate that any filesystem,
crash, HTTP, consumer, restart, or cleanup assertion can detect an incorrect
implementation. Once finding 1 is corrected, recapture RED evidence that
reaches the real public seam and fails on independently observed missing or
incorrect behavior. A single early missing-runner exception is insufficient
evidence for this entire security contract.

### 4. Isolation and cleanup are not robust against hung or partially failing children

`PilotSessionStorageScenario::run()` has no timeout or terminate/kill/reap
fallback. A hung fault/crash/HTTP/Compose scenario can block the suite and leave
owned processes and storage behind. Cleanup facts are also accepted from the
child before the parent `finally`, so the test does not independently prove that
the child stopped all processes or preserved default, Compose and foreign
roots. Constructor failures after creating the shared parent but before a fully
initialized task root also have no cleanup owner.

Add bounded process execution with TERM/KILL/reap and attempt-all cleanup that
retains the first error until every exact owned resource has been handled.
Parent-owned before/after sentinels must prove preservation of foreign/default/
Compose roots. Add controlled setup, child-timeout, crash and cleanup-failure
probes; each must leave no owned process or artifact while preserving all
sentinels.

## Checks that pass

- The executable spec hash equals the owner-approved hash.
- Expected 503 header/body literals are written in the test rather than imported
  from a production response helper.
- The temp task directory uses an unpredictable name and the ordinary missing-
  runner path removes it without touching the compatibility root.
- All three PHP files pass syntax checks.
- Both focused commands fail for the named missing runner, not for PHP setup or
  repository bootstrap.

## Reproduced evidence

```text
php tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
exit 255
RuntimeException: INTENTIONAL_RED: PILOT-SESSION-STORAGE-001 public scenario runner is missing

php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
exit 255
RuntimeException: INTENTIONAL_RED: PILOT-SESSION-STORAGE-001 public scenario runner is missing

test ! -e /tmp/fmonitor2-session-storage-tests
exit 0
```

## Gate decision

Gate 3 does not pass. Return the slice to Gate 2, preserve the approved spec and
its owner-approved hash, replace self-reported summaries with independently
observed filesystem/process/HTTP evidence, prove adversarial sensitivity and
bounded cleanup, then record a new qualifying RED. A fresh separately tasked
reviewer must approve the revised tests before Gate 4. OpenSpec task 2.2 remains
unchecked.
