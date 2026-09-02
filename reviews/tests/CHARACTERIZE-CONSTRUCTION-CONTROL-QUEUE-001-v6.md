# Independent Gate 3 test rereview v6 — CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001

- Date: 2026-09-02
- Reviewer: separately tasked agent `/root/session_v7_test_rereview_v4`
- Test author: separately tasked agent; not this reviewer
- Reviewed state: dirty shared worktree; exact hashes below
- Verdict: **CHANGES_REQUIRED**

The reviewer did not edit the executable specification, reviewed test,
test-support helper or production code. This review record is the reviewer's
only change to the slice.

## Findings

### 1. Blocking — request-by-request connection/history contract is not observed

The correction adds an outer privileged observer, but
`ccqRunWitnessed()` observes one initial set of four active threads and later
classifies one aggregate history snapshot for those four thread IDs. The
approved contract requires a separate barrier cycle for every request group:
exactly one `_s` connection, initial maximum `EVENT_ID`, dispatch, response
barrier, new-history audit, teardown, and independently observed zero active
`_s` connections before the next serial request. Concurrent `_a` and `_b` need
their own simultaneous mapping and initial event bounds.

The current worker manifest and global `dispatch-ready`/`responses-ready`
markers cannot establish those facts. A helper can keep four connections alive,
serve every response elsewhere, and place harmless SELECTs on the observed
threads. Aggregate history also cannot detect reuse/history gaps between the 20
exchanges. This contradicts spec isolation clauses 7–8 and leaves the SQL
read-only oracle insensitive.

### 2. Blocking — fingerprints are not taken around every request group

The outer test captures one fixture state before sensitivity and compares it
only after the entire normative HTTP matrix. Section 9 requires definitions,
all rows, allocators, owned filesystem/session state and ambient decoy before
and immediately after every request group. `ccqFixtureState()` also omits
AUTO_INCREMENT metadata. A helper can perform and reverse a mutation between
the two global snapshots while returning the expected HTTP log.

The filesystem comparison records only type/size/digest, not identity/mode, and
normalizes several helper-written observation files out of the result. There is
no separately identified session subtree fingerprint. This is not the exact
independent DB/file/session/decoy boundary required by the approved spec.

### 3. Blocking — effective grants are checked loosely, not exactly

`ccqAssertSelectOnlyGrants()` requires only that each account's joined SHOW
GRANTS contains `USAGE ON *.*` and some `SELECT ON`, then excludes several
write/admin words. It does not require identical profiles, one exact SELECT
grant for every declared owned fixture table, no SELECT on another schema/table,
or absence of unlisted privileges. A principal with only one fixture-table
grant, or extra read access outside the owned namespace, passes. This does not
meet clause 5's exact identical least-privilege profile.

### 4. Blocking — cleanup is not attempt-all or deadline-safe on failure

`ccqRunWitnessed()` has no enclosing `try/finally` owning its process group,
workers, connections and verifier-created resources. After the first barrier,
any failed grant/PID/fixture/sensitivity/log/history/fingerprint assertion throws
directly and leaves the helper running. Sensitivity and response deadline paths
do the same. In the early-evidence failure branch it sends TERM and immediately
calls blocking `proc_close()` without a bounded grace/KILL/reap loop.

The outer `finally` removes only the ambient decoy table/artifact root; it does
not validate or drop exact verifier tables/accounts and cannot safely remove a
live helper's resources. Therefore failure does not guarantee reaping, exact
account/table cleanup or post-cleanup absence, contrary to clause 12.

### 5. Blocking — the response-only fake is still not rejected

The dynamically generated sensitivity helper proves only that a helper which
creates nothing is rejected. The outer sensitivity mutation proves that some
response producer can read one changed address, but not that the production
factory/route produced the normative responses. A response-only helper can
create the expected resources/connections, read the changed literal directly,
write crafted raw response bytes/log rows, execute harmless SELECTs on the
watched threads and pass. The outer test never verifies production composition;
the expected transcript is still helper-owned and `ccqAssertAudit()` is unused.

Section `Sensitivity requirements` explicitly requires a static/response-only
fake to fail. Add a concrete response-only fake/broken-mode rejection whose
failure is decided by outer-owned evidence at the production public seam.

## Positive progress

- The literal-output/audit-JSON no-resource fake is now independently rejected.
- The outer observer independently checks positive table/account/thread/PID
  existence, reads SHOW GRANTS and performance-schema history, performs the
  address sensitivity mutation, parses raw HTTP bytes and checks the full
  authorization/projection/pagination/failure/repeat matrix.
- The `_x` denied INSERT is required in independently read statement history.
- The current minimal helper cannot fabricate RED success; on instrumented
  MariaDB it fails because the independently required resources do not exist.
- Exact randomized namespaces and ambient DB/file decoys are preserved in both
  reproduced failure modes.

## Required correction

1. Drive and witness each serial/concurrent request barrier from the outer test,
   including exact connection/thread mapping, initial event bound, response
   history window and zero-connection teardown before progression.
2. Capture full DB schema/rows/allocator and filesystem/session identity
   fingerprints before and after each request group.
3. Compare exact normalized SHOW GRANTS against all and only the declared table
   SELECT grants for all four principals.
4. Put verifier/process-group, worker PIDs and exact SQL/filesystem ownership
   under attempt-all `finally` cleanup with bounded TERM/KILL/reap on every exit.
5. Demonstrate rejection of a response-only fake, not only a no-resource
   literal fake. Refresh RED evidence and request another fresh Gate 3 review.

Gate 4 remains closed.

## Reproduction

Canonical MariaDB:

```text
timeout 35 php tests/Verification/characterize_construction_control_queue_001_test.php
SETUP_FAILURE: performance_schema must already be enabled
exit 2
```

This is correctly classified as setup failure before mutation.

A disposable exact `mariadb:11.4.7-noble` was started with performance schema,
timed statement instrumentation, the long-history consumer and history size
10000 enabled at startup. The focused verifier then produced:

```text
RED_ASSERTION: verifier must expose positive fixture/accounts/four-thread/raw-HTTP evidence before cleanup; evidence={"status":1,"stdout":"","stderr":"ASSERTION_FAILURE: production HTTP construction-control queue matrix is not implemented\n","tables":[],"accounts":[],"threads":[]}
exit 1
```

No owned SQL table/account or repository-private artifact child remained. The
exact disposable container `fmonitor2-ccq-review-v6` was force-removed after
the read-only residue check; its ephemeral contents are not recoverable.

Static verification:

```text
php -l tests/Verification/characterize_construction_control_queue_001_test.php
php -l tests/Support/characterize_construction_control_queue_verifier.php
# both syntax clean

openspec validate characterize-construction-control-queue --strict
Change 'characterize-construction-control-queue' is valid

git diff --check -- tests/Verification/characterize_construction_control_queue_001_test.php \
  tests/Support/characterize_construction_control_queue_verifier.php \
  docs/operations/construction-control-queue-red-evidence.md
# exit 0
```

## Exact reviewed hashes

```text
f0dadc560979970968e265e8e26f0bfed42101a00dcaaed2f8cc7cf311c0243d  specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md
a4f10d3b1e4b2bd80b60050186fd22e54973469fac6e49f67b496e5e3382b5a1  tests/Verification/characterize_construction_control_queue_001_test.php
3434db2b537321c1bcccc6a38281680d228d5a69d08b453bff0db2e6ed3d428a  tests/Support/characterize_construction_control_queue_verifier.php
155085df67c97644a58480d33b18b9b311c1dd64f61bcf3815cb9f8ce0aa4736  docs/operations/construction-control-queue-red-evidence.md
de7167bc108bdbf3fbd348434c4818324455eb900935c6c974dd3f4ed93e3a02  openspec/changes/characterize-construction-control-queue/proposal.md
bf0ac6a294aa34ef379237208eb6b765117fdef1564a9cc693fa9c0cc615178b  openspec/changes/characterize-construction-control-queue/design.md
a361549f4ab8920218fc4438877811de971da8695cbc608a2ad153cd98aef412  openspec/changes/characterize-construction-control-queue/specs/verification/construction-control-queue-characterization/spec.md
259d04a7f9d414720fb788fa7d21bbb0da5278f169051138ac2baececfe9d12e  openspec/changes/characterize-construction-control-queue/tasks.md
```
