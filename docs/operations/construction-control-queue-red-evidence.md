# CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001 — Gate 2 RED evidence

Date: 2026-09-02

## Approved input

The RED test pins the owner-approved executable specification:

```text
f0dadc560979970968e265e8e26f0bfed42101a00dcaaed2f8cc7cf311c0243d  specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md
b7b2b8ae6558edcb9e73f4c39d26e010e393668e2982cc3db69235c9f7f2a433  tests/Verification/characterize_construction_control_queue_001_test.php
3434db2b537321c1bcccc6a38281680d228d5a69d08b453bff0db2e6ed3d428a  tests/Support/characterize_construction_control_queue_verifier.php
1feabaa9fd529e8bdb2cf3609fdcc7da18455dbb86d29edcca45cc85f19052b8  tests/Support/construction_control_queue_response_only_fake.php
```

The outer meta-test, not the future worker, owns the evidence verdict. While
the worker is paused at explicit dispatch/response barriers, the outer process
must independently observe the exact non-empty table inventory and rows, four
loopback accounts and their effective SELECT-only grants, four distinct
performance-schema threads, and the denied `_x` INSERT statement. It parses
the raw HTTP exchange log itself and checks the exact authorization GET/HEAD,
headers/bodies, projection, escaping, hrefs, 50/1 pagination, all six failures,
repeat and concurrent cases. It fingerprints DB/files before dispatch and at
the response barrier, then proves positive existence followed by exact cleanup.
Every phase has a bounded deadline.

Before the normative requests the outer observer replaces the address of
object `451201` with an unpredictable value, releases one real HTTP sensitivity
request, requires the raw response to contain that value, restores the approved
literal and proves the complete fixture fingerprint equal again. Each declared
worker PID must be distinct and alive at the dispatch barrier, must match the
PID/slot/nonce connection attributes independently observed for its exact
MariaDB thread (including sensitivity slot `_x`), and must be absent after
helper exit. At every mapping boundary the complete active connection set for
all four owned principals must equal the exact expected slot set.

The outer observer now drives a separate barrier cycle for every serial request
and one common concurrent A/B cycle. It records each exact thread's initial
event bound, audits only the subsequent history window before teardown and
proves `_s` has zero live connections before the next request. Definitions,
rows, `AUTO_INCREMENT`, engine/collation, and filesystem/session `lstat`
identity, uid/gid, mode, device, inode, link count, size and timestamps are
compared around every group. `SHOW GRANTS` must contain exactly global `USAGE`
plus one table-level `SELECT` for every exact configured-database/owned-table
pair, identically for all four accounts, and no other row.

Every failure enters attempt-all cleanup: bounded process-group TERM/KILL,
pipe close and `proc_close`, and exact worker termination. Before any SQL or
artifact removal, the outer observer re-enumerates the complete token namespace
and permits destructive cleanup only when it is wholly absent or equals the
exact expected table/account/host/grant set; partial or unexpected ownership is
a surfaced `SETUP_FAILURE`. Cleanup is followed by independent absence checks. A
resource-owning response-only fake is also executed and rejected by the outer
thread/challenge evidence.

A dynamically created fake helper which prints success/audit literals without
creating resources is run first and is rejected by the positive-existence
witness. Helper stdout and helper-authored JSON are not accepted as authority.
No production or `rapid-pilot` file was edited by the RED author.

## Environment distinction

The canonical MariaDB currently reports `@@performance_schema = 0`. Running
the focused command against port `23306` therefore stops before fixture or
account creation with exit `2`:

```text
SETUP_FAILURE: performance_schema must already be enabled
```

This is intentionally not claimed as RED. The verifier does not mutate global
MariaDB instrumentation.

For the behavioral RED demonstration, a disposable MariaDB 11.4.7 process was
started with statement instrumentation and
`events_statements_history_long` enabled at server startup. No setting was
changed by the verifier. Command (with the Docker-assigned loopback port):

```text
FMONITOR_VERIFY_DB_PORT=<disposable-port> \
  php tests/Verification/characterize_construction_control_queue_001_test.php
```

Both runs from distinct clean randomly generated namespaces returned exit `1`
with the same intended first missing production-HTTP behavior:

```text
RED_ASSERTION: production HTTP verifier must expose exact positive fixture/accounts before requests; evidence={"tables":[],"accounts":[],"stderr":"ASSERTION_FAILURE: production HTTP construction-control queue matrix is not implemented\n"}
```

The disposable server was then stopped. The test's `finally` path removed its
exact ambient SQL decoy and repository-private artifact root. The deliberate
Gate 2 tracer exists but performs no composition: therefore the independent
observer sees empty positive evidence and fails before any transcript can be
trusted. The preceding literal fake is rejected by the same observation.

Classification: reproducible `RED_ASSERTION`, not `SETUP_FAILURE` and not a
production regression. Production GREEN is forbidden until a fresh independent
Gate 3 test review approves the exact spec/test/transcript hashes.

## Static checks

```text
php -l tests/Verification/characterize_construction_control_queue_001_test.php
No syntax errors detected

git diff --check -- tests/Verification/characterize_construction_control_queue_001_test.php
PASS

openspec validate characterize-construction-control-queue --strict
Change 'characterize-construction-control-queue' is valid
```
