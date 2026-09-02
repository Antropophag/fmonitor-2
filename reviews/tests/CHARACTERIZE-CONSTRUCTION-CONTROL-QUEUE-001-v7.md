# Independent Gate 3 test rereview v7 — CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001

- Date: 2026-09-02
- Reviewer: separately tasked agent `/root/session_v7_test_rereview_v4`
- Test author: separately tasked agent; not this reviewer
- Reviewed state: dirty shared worktree; exact hashes below
- Verdict: **CHANGES_REQUIRED**

The reviewer did not edit the executable specification, reviewed test,
test-support helpers or production code. This review record is the reviewer's
only change to the slice.

## Findings

### 1. Blocking — exact per-group connection population is not enforced

The new loop establishes separate history bounds and teardown for each group,
but it filters `ccqThreads()` down to the expected `_s` or `_a/_b` slots and
does not reject other owned accounts that are simultaneously connected. During
a serial group, `_a`, `_b` or `_x` may be live; during the concurrent group,
`_s` or `_x` may be live. The approved contract requires exactly the expected
connection(s) and no other connection belonging to any of the four owned
usernames at each mapping boundary.

The readiness JSON also supplies only PIDs. The outer test checks that those
PIDs exist, but does not bind each PID to the observed processlist/thread slot.
A helper can name its own or an unrelated live PID while its database traffic
is generated elsewhere. Thus the exact worker/connection/thread ownership and
the concurrent A/B barrier are not yet independently proved.

### 2. Blocking — filesystem/session fingerprints still omit identity and mode

`ccqTree()` returns only `type` for directories and `type,size,sha256` for
files. It does not record uid, mode, device, inode, link count or timestamps.
Consequently chmod, chown, replacement by an equal-byte file, hard-link changes
and directory identity changes can pass both the per-group filesystem and
separate session comparisons. The refreshed RED evidence claims
"filesystem identity/mode/content", but the test does not implement that
claim.

The database fingerprint now correctly includes SHOW CREATE, all rows,
AUTO_INCREMENT, engine and collation around each group. The equivalent exact
filesystem/session metadata must be added rather than described only in the
evidence narrative.

### 3. Blocking — grant matching omits the exact database

The all-and-only table-name comparison is improved, but its regular expression
accepts any quoted database name. A principal can receive SELECT on tables with
the expected token-derived basenames in another schema and still pass. Clause 5
requires SELECT on the exact owned fixture tables in the configured test
database. Parse and compare the database and table pair (including MariaDB's
escaped SHOW GRANTS representation), and prove all four normalized profiles
are identical.

### 4. Blocking — destructive cleanup does not follow the approved validation rule

The new `finally` is bounded and attempt-all for known processes, tables,
accounts and artifact child, which closes the major v6 leak. However, it blindly
issues `DROP TABLE IF EXISTS` and `DROP USER IF EXISTS` for expected names while
swallowing errors. It does not first re-enumerate the complete token-owned
namespace, reject duplicate/unexpected username/host, and validate each exact
account's grants as required by clause 12. Unexpected owned resources are
noticed only by post-cleanup assertions, after destructive cleanup has already
started.

This matters on a failed/malicious helper path: cleanup authority is explicitly
conditional on exact ownership validation. Add a read-only ownership audit
before any DROP/remove, retain bounded process reaping, and surface cleanup
failure as `SETUP_FAILURE` without hiding it behind the primary assertion.

## Positive progress

- Every serial group and the combined concurrent group now has a separate
  readiness, event-bound, response-history and teardown cycle; `_s` absence is
  checked before progression.
- DB definition/rows/allocator fingerprints occur around every group.
- The outer-only 32-hex-byte challenge is generated after worker readiness,
  required in raw HTTP, then restored exactly.
- SHOW GRANTS now requires one USAGE and one SELECT row per declared table and
  rejects additional grant rows, subject to the database-name gap above.
- A resource-owning response-only fake is concretely run and rejected, while
  the literal/no-resource fake remains rejected.
- Process-group and worker cleanup now lives in `finally` with bounded
  TERM/KILL paths, followed by absence assertions.

## Required correction

1. At each group require the full owned active-thread set to equal the exact
   expected username(s), and independently bind declared live worker PIDs to
   those request slots before dispatch.
2. Extend per-group filesystem and session fingerprints with lstat identity,
   ownership, mode, link count and timestamps.
3. Compare exact configured-database/table grant pairs and identical complete
   profiles for all four accounts.
4. Validate the complete exact table/account/host/grant ownership set before
   destructive cleanup; do not swallow cleanup errors or proceed on unexpected
   ownership.

Refresh RED evidence and request another fresh Gate 3 review. Gate 4 remains
closed.

## Reproduction

Canonical MariaDB:

```text
timeout 35 php tests/Verification/characterize_construction_control_queue_001_test.php
SETUP_FAILURE: performance_schema must already be enabled
exit 2
```

This is correctly classified as setup failure before mutation.

On a disposable `mariadb:11.4.7-noble` with performance schema, timed statement
instruments, long-history consumer and history size 10000 enabled at startup,
two independent invocations each produced the same intended RED:

```text
RED_ASSERTION: production HTTP verifier must expose exact positive fixture/accounts before requests; evidence={"tables":[],"accounts":[],"stderr":"ASSERTION_FAILURE: production HTTP construction-control queue matrix is not implemented\n"}
exit 1
```

After each run the token-owned SQL namespace/accounts and repository-private
artifact child were absent. The exact disposable container
`fmonitor2-ccq-review-v7` was force-removed after verification; its ephemeral
contents are not recoverable.

Static verification passed:

```text
php -l tests/Verification/characterize_construction_control_queue_001_test.php
php -l tests/Support/characterize_construction_control_queue_verifier.php
php -l tests/Support/construction_control_queue_response_only_fake.php
openspec validate characterize-construction-control-queue --strict
git diff --check -- <reviewed test/support/evidence files>
```

## Exact reviewed hashes

```text
f0dadc560979970968e265e8e26f0bfed42101a00dcaaed2f8cc7cf311c0243d  specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md
0123fc65a5b1f0ec258e2f2259d79727cdc8339842f9da65ce360cd481bd49f3  tests/Verification/characterize_construction_control_queue_001_test.php
3434db2b537321c1bcccc6a38281680d228d5a69d08b453bff0db2e6ed3d428a  tests/Support/characterize_construction_control_queue_verifier.php
1feabaa9fd529e8bdb2cf3609fdcc7da18455dbb86d29edcca45cc85f19052b8  tests/Support/construction_control_queue_response_only_fake.php
79421eab6768d076f99ce5768b539d012c45f10a02ec8b752e9de9bc415d4b46  docs/operations/construction-control-queue-red-evidence.md
de7167bc108bdbf3fbd348434c4818324455eb900935c6c974dd3f4ed93e3a02  openspec/changes/characterize-construction-control-queue/proposal.md
bf0ac6a294aa34ef379237208eb6b765117fdef1564a9cc693fa9c0cc615178b  openspec/changes/characterize-construction-control-queue/design.md
a361549f4ab8920218fc4438877811de971da8695cbc608a2ad153cd98aef412  openspec/changes/characterize-construction-control-queue/specs/verification/construction-control-queue-characterization/spec.md
259d04a7f9d414720fb788fa7d21bbb0da5278f169051138ac2baececfe9d12e  openspec/changes/characterize-construction-control-queue/tasks.md
```
