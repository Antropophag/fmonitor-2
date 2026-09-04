# PILOT-OBJECT-CARD-001 resource-release portability — independent Gate 3 rereview v2

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/http_auth_uppercase_rereview`
- Reviewed correction commit: `88fe01f49905d590c90b1b341ed0c02a4685a18e`
- Prior review: `6f0aad9` (`NEEDS_CHANGES`)
- Reviewed test SHA-256: `c9b151d7fe12d12e986f2d3c93fae0cc5d5648529272f8975de681ec155b77f3`
- Verdict: **NEEDS_CHANGES**

## Rereview result

The portability and sensitivity findings from v1 are substantively closed:

- every post-response check requires a positive PID and `running=true`;
- Linux enumerates exact `/proc/<pid>/fd` symlink targets;
- Darwin invokes absolute `/usr/sbin/lsof` with exact
  `-a -p <decimal-pid> -Fn` arguments and parses only nonempty `n<path>` name
  records;
- unsupported OS, unavailable procfs, missing/non-executable `lsof`, failed
  observer execution and empty Darwin name output fail setup rather than skip;
- configured CSS comparison is byte-exact;
- an intentionally open current-process CSS descriptor is observed and the
  same descriptor is absent after close;
- MariaDB release uses an escaped exact random `readerUser + database`
  `PROCESSLIST` predicate, with one-row open-probe and zero-row closed-probe
  controls;
- the DB oracle no longer depends on Linux socket inode/address reconstruction
  and is stronger because it detects every connection under the unique
  application principal;
- only the test changed; production and specification bytes are unchanged.

Both full behavior matrices pass independently:

```text
native macOS arm64:
PASS: PILOT-OBJECT-CARD-001 public HTTP card

Linux arm64 fmonitor2-php-test, disposable non-root owned workspace:
PASS: PILOT-OBJECT-CARD-001 public HTTP card
```

The random database/user/artifact namespace and exact PID keep parallel runs
isolated. Existing outer cleanup removes only owned resources. The deliberate
descriptor is closed in `finally`; the reader probe is closed before HTTP
cases. No production debug seam or behavior change was introduced.

## Blocking finding: Darwin observer has no bounded runtime/reap

The requested bounded observer lifecycle is not implemented. In
`pocOpenDescriptorPaths()` the parent performs blocking
`stream_get_contents($pipes[1])`, then blocking `stream_get_contents($pipes[2])`,
then `proc_close($process)`. There is no nonblocking mode, readiness multiplexing,
monotonic deadline, status polling or forced termination deadline.

The `finally` block helps only after control returns or an exception is thrown.
If `/usr/sbin/lsof` hangs, keeps either pipe open, or blocks while inspecting a
process, control never reaches `finally`; the db-test can hang indefinitely.
Even on an exceptional path, `proc_close()` after one `proc_terminate()` has no
bounded terminal-state check and may itself wait indefinitely.

The two successful real runs prove the ordinary implementation and output
parser, but cannot prove the required timeout/reap behavior. This is a
determinism and cleanup gap in the test harness, not a product failure, and does
not authorize production changes.

## Exact required Gate 2 correction

Keep all current platform selection, exact command, parsing and sensitivity
controls. Replace only Darwin subprocess collection/cleanup with one bounded
runner that:

1. sets stdout/stderr nonblocking and collects both without sequential-pipe
   deadlock;
2. uses a fixed monotonic deadline and polls/selects in bounded intervals;
3. on timeout sends termination, waits only to a second fixed monotonic
   deadline, then sends a forced kill if still running;
4. confirms terminal state, drains/closes both output pipes and reaps the child
   exactly once;
5. reports timeout/nonterminal/abnormal exit as setup failure and never returns
   an empty descriptor list;
6. in `finally`, closes every still-open pipe and applies the same bounded
   terminate/kill/reap path without calling an unbounded `proc_close()` on a
   confirmed-live child.

Add a sensitivity probe using a task-owned fake observer executable or an
equivalent injected command that deliberately outlives the deadline. It must
prove bounded failure and child reaping without replacing the production
`/usr/sbin/lsof` path used by the real Darwin observer. A simple successful
`lsof` run is not timeout sensitivity.

This correction remains test-only. After capturing the timeout probe's intended
RED/then GREEN, rerun the full native macOS and Linux matrices and obtain a
fresh independent Gate 3 for the new test hash.

## Independent verification

```text
$ git diff --name-only 88fe01f^..88fe01f
tests/InstallationProcess/pilot_object_card_001_test.php

$ git diff --check 88fe01f^..88fe01f
PASS

$ FMONITOR_TEST_DB_HOST=127.0.0.1 \
  FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card
```

Linux was run in the available arm64 image as user `10001` after copying the
read-only repository and sibling `shlz-ui` inputs into an owned
`/home/fmonitor/workspace`; it printed the same exact PASS line.

## Reviewed hashes

```text
c9b151d7fe12d12e986f2d3c93fae0cc5d5648529272f8975de681ec155b77f3  tests/InstallationProcess/pilot_object_card_001_test.php
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
7464c3610b5466b0c8cfc41d077c189d6384a4ea9499f2997717c7628205de86  app/PilotHttp/PilotHttpEntrypoint.php
```

## Verdict

**NEEDS_CHANGES.** The resource observations are now portable, exact and
sensitive in normal execution, and both OS matrices are GREEN. Gate 3 remains
blocked solely because the Darwin observer can hang and cannot prove bounded
termination/reaping. Fix only that test-harness lifecycle, add timeout
sensitivity, and resubmit the changed test bytes for fresh independent review.
