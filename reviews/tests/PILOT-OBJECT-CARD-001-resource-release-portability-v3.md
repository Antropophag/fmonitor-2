# PILOT-OBJECT-CARD-001 resource-release portability — independent Gate 3 rereview v3

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/http_auth_uppercase_rereview`
- Reviewed correction commit: `1b54cca61c742a75971ef49c339557e53dd7d5fa`
- Prior review: `42e653d` (`NEEDS_CHANGES` solely for unbounded observer lifecycle)
- Reviewed test SHA-256: `17f861d7527690d75d21b619ba5dea2baee3b6c62a84e414b807f39a02f13db2`
- Verdict: **APPROVED**

## Rereview result

The sole v2 blocker is closed. `pocRunBoundedObserver()` now owns the complete
subprocess lifecycle:

- both stdout and stderr are set nonblocking and drained together on every
  polling iteration, preventing sequential full-pipe deadlock;
- the normal execution window uses `hrtime(true)` with a caller-supplied fixed
  deadline;
- expiry marks the result timed out, sends `SIGTERM`, and polls/drains for a
  separate fixed 250 ms monotonic window;
- a still-running child receives `SIGKILL` and a separate fixed one-second
  terminal-state window;
- nonterminal state after both windows closes the remaining pipes and fails
  setup explicitly rather than pretending to return an empty resource list;
- once terminal, both pipes receive their final nonblocking drain and are
  closed, then the process is reaped by exactly one `proc_close()`;
- the already captured terminal `exitcode` is preferred because PHP may return
  `-1` from `proc_close()` after `proc_get_status()` consumed it.

The real Darwin observer remains fixed to the absolute command:

```text
/usr/sbin/lsof -a -p <exact-positive-pid> -Fn
```

It has a two-second deadline, requires `timedOut=false`, exact exit `0`, empty
stderr and nonempty machine-readable `n<path>` records. Unsupported platform,
missing executable, procfs/lsof failure, timeout or abnormal result remains a
setup failure; there is no skip or empty fallback.

## Timeout/reap sensitivity

On Darwin, a fresh task-owned PHP child deliberately sleeps for five seconds
but is run with a 50 ms observer deadline. The test independently requires:

- `timedOut=true`;
- positive exact owned PID;
- complete cleanup in less than 1.5 seconds, below the child's five-second
  natural completion;
- `posix_kill(pid, 0) === false` after return when POSIX observation is
  available, proving the child is no longer live and has been reaped.

This probe executed during the full native test and passed. It is sensitive to
removing the deadline, omitting termination, returning before terminal state,
or leaving a zombie/live child. It uses `PHP_BINARY`, not a replacement for the
fixed real `lsof` path.

The previously approved controls remain intact: current-process deliberately
open/closed byte-exact CSS descriptor detection; live worker PID/running
precondition; Linux exact `/proc/<pid>/fd` targets; Darwin exact `n` records;
and exact escaped random `readerUser + database` PROCESSLIST with open-one and
closed-zero probe sensitivity. Request checks still require zero matching CSS
descriptors and zero matching DB connections after every completed response.

## Independent full verification

### Native macOS arm64

```text
$ FMONITOR_TEST_DB_HOST=127.0.0.1 \
  FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_card_001_test.php
PASS: PILOT-OBJECT-CARD-001 public HTTP card
```

This execution includes the 5-second-child/50-ms timeout sensitivity and all
real `/usr/sbin/lsof` observations.

### Linux arm64

The current repository and sibling `shlz-ui` were copied from read-only mounts
into a disposable non-root user-owned workspace in
`fmonitor2-php-test:latest`:

```text
PASS: PILOT-OBJECT-CARD-001 public HTTP card
```

Linux exercised the retained exact procfs observer plus the same CSS and DB
positive/negative controls and complete object-card behavior matrix.

Additional evidence:

```text
git diff --name-only 1b54cca^..1b54cca
tests/InstallationProcess/pilot_object_card_001_test.php

git diff --check 1b54cca^..1b54cca                              PASS
php -l tests/InstallationProcess/pilot_object_card_001_test.php  PASS
post-run owned .test-artifacts children                         0
post-run pilot object-card / php -S processes                   0
```

The random per-run database/users, exact PID, task-owned filesystem root and
outer cleanup preserve parallel isolation. The bounded observer owns only its
own child and never enumerates/kills unrelated processes.

## Reviewed hashes

```text
17f861d7527690d75d21b619ba5dea2baee3b6c62a84e414b807f39a02f13db2  tests/InstallationProcess/pilot_object_card_001_test.php
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
7464c3610b5466b0c8cfc41d077c189d6384a4ea9499f2997717c7628205de86  app/PilotHttp/PilotHttpEntrypoint.php
```

## Verdict

**APPROVED** for the exact corrected test at commit
`1b54cca61c742a75971ef49c339557e53dd7d5fa`. The observer is portable,
sensitive, deadline-bounded and reaped; full native macOS and Linux non-root
matrices are GREEN. No production, executable specification or Gate 4 behavior
change is needed or authorized.
