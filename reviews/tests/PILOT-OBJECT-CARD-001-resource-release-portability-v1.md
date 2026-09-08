# PILOT-OBJECT-CARD-001 resource-release portability — independent Gate 3 review v1

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/http_auth_uppercase_rereview`
- Reviewed commit: `13c03256b15419b526984dc1f79e9a02edfb6c52`
- Public seam: real `GET|HEAD /pilot/objects/{id}` through the production HTTP entrypoint
- Reviewed oracle: `pocAssertRequestResourcesReleased(...)`
- Verdict: **NEEDS_CHANGES — test-only portability correction required; production changes prohibited**

## Finding

The resource-release requirement and expected empty results are valid, but the
current observation mechanism is Linux-only. At line 336 it requires
`/proc/<worker-pid>/fd`, then derives both CSS descriptors and MariaDB client
sockets from Linux procfs. Native macOS has neither `/proc/<pid>/fd` nor
`/proc/net/tcp*`, so the approved object-card behavior fails before the first
resource assertion can observe anything.

Independent native reproduction:

```text
$ FMONITOR_TEST_DB_HOST=127.0.0.1 \
  FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_card_001_test.php

Example A GET request scope live public HTTP worker is observable
Expected: true
Actual: false
... pilot_object_card_001_test.php:336
```

This is fixture setup/platform failure, not evidence that production retained a
CSS descriptor or DB connection. The exact unchanged test passes end-to-end in
the available Linux arm64 `fmonitor2-php-test:latest` image when run as a
non-root task user from an owned workspace:

```text
PASS: PILOT-OBJECT-CARD-001 public HTTP card
```

The reviewed commit changed only an operations document; object-card test and
production bytes are unchanged.

## Approved shape of the Gate 2 correction

The correction must remain test-only and preserve one common semantic oracle:
after each completed response, the still-live HTTP worker owns neither the
configured CSS file nor a MariaDB connection made with this test's unique
application principal.

### 1. Live worker precondition

Retain `proc_get_status()` and require a positive PID **and** `running=true`
before inspecting resources. A missing/dead worker is setup failure, never an
empty-success observation.

### 2. CSS descriptor observation

- On Linux, retain the current exact `/proc/<pid>/fd/*` symlink scan.
- On Darwin, invoke absolute `/usr/sbin/lsof` for the exact decimal PID using
  machine-readable field output and accept descriptor paths only from records
  whose line is exactly `n<path>`. Require successful process execution and
  parse only `n` name records; do not search human-formatted columns, command
  text or substring output.
- Any other platform, missing/non-executable `lsof`, failed `lsof`, malformed
  PID or unavailable Linux procfs is explicit setup failure. There is no skip
  and no empty fallback.
- Compare the parsed path byte-exactly to the configured canonical CSS path.

For mechanical sensitivity, factor the platform descriptor enumerator so the
test can first prove it sees a deliberately open task-owned CSS descriptor for
the current test process, then close it and prove absence. This prevents a
broken `lsof` invocation/parser or inaccessible procfs from vacuously returning
the same empty list expected for the worker. The control handle must be opened
and closed in a bounded `try/finally` and must not be inherited by the HTTP
worker.

### 3. DB connection observation

Replace all PID socket-inode and `/proc/net/tcp*` mapping with one portable
`information_schema.PROCESSLIST` query for exact:

```text
DB = <random task-created database>
USER = <random task-created readerUser>
```

Both values must continue to be escaped with the live admin connection. Do not
filter by host/port/PID: the principal/database pair is unique to this test, and
no other request may legitimately use it. Requiring zero rows is strictly
stronger than reconstructing only sockets visible in one worker: it detects a
retained connection even if its socket representation or client address cannot
be mapped portably.

The existing `readerProbe` supplies a natural positive control. While it is
open, require exactly its task-unique `USER+DB` row to be observable; after
closing it, require zero before starting the HTTP cases. This makes query
permissions/filtering sensitive instead of allowing a vacuous zero-row oracle.

## Why no production change is authorized

`PILOT-OBJECT-CARD-001` requires the inherited entrypoint to close acquired CSS
and DB resources once. Production already satisfies that behavior in Linux and
its dedicated injected/real-resource tests. Native macOS fails before examining
production state solely because the test hard-codes Linux procfs. Altering
production resource lifetime, adding debug endpoints, exposing descriptors, or
retaining test handles would be a contract regression.

The portable observers remain outside production and do not replace public HTTP
behavior assertions. They inspect only OS/process metadata and the disposable
database's process list after the real response has completed.

## Cleanup and concurrency constraints

The existing random database/user, task-owned artifact root, exact process
handle and outer `finally` remain authoritative. The correction must not inspect
or kill unrelated PIDs, scan arbitrary user databases, or treat foreign
connections as target connections. The unique `readerUser+database` predicate
allows parallel tests without the previously observed global PROCESSLIST race.

Every spawned `lsof` process must have all pipes closed and be terminated/reaped
on exceptional paths. The positive descriptor handle must close in `finally`.
Unsupported tools/platforms fail before behavior assertions and still flow
through existing server/database/user/artifact cleanup.

## Required verification before fresh Gate 3

1. Capture the current native macOS RED above.
2. Implement only the portable test helpers and call-site wiring described
   here; do not edit `app/`, `public/`, `rapid-pilot/` or specifications.
3. Demonstrate positive/negative CSS descriptor controls and positive/negative
   exact `readerUser+database` PROCESSLIST controls.
4. Run the full unchanged behavior matrix on native macOS and Linux as a
   non-root task user; both must print the exact PASS line.
5. Verify no owned DB/user, server, child process or artifact remains, then
   obtain a fresh independent Gate 3 for the corrected test hash.

## Reviewed hashes

```text
82fbac131ae7200037b9a8287dca488f3fcbb0a9d83d8313643ff09f14ffdf13  tests/InstallationProcess/pilot_object_card_001_test.php
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
7464c3610b5466b0c8cfc41d077c189d6384a4ea9499f2997717c7628205de86  app/PilotHttp/PilotHttpEntrypoint.php
```

## Verdict

**NEEDS_CHANGES.** The current oracle is valid on Linux but non-executable on
native macOS. The exact portable replacement above is equivalent for CSS,
stronger for DB connection release, preserves sensitivity and isolation, and
requires no production or Gate 1 change. Corrected test bytes require fresh
independent Gate 3 review before being treated as integration evidence.
