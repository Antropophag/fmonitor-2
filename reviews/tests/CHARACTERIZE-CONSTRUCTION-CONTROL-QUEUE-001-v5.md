# Independent Gate 3 test review — CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001

- Date: 2026-09-02
- Reviewer: separately tasked agent `/root/session_green_v8`
- Test author: separately tasked agent; not this reviewer
- Reviewed state: dirty shared worktree, exact hashes below
- Verdict: **CHANGES_REQUIRED**

Reviewer did not edit the executable specification, reviewed test, test-support
code or production code.

## Findings

### 1. The meta-test can be satisfied by a fabricated missing helper

`characterize_construction_control_queue_001_test.php` delegates every public
HTTP exchange, fixture, four-principal connection audit, statement-history
audit, sensitivity attempt, deadline and owned cleanup operation to the absent
`tests/Support/characterize_construction_control_queue_verifier.php`. It then
trusts two values produced by that same helper: normalized stdout and an audit
JSON file.

A trivial helper can print the six expected transcript lines, write the literal
values accepted by `ccqAssertAudit()`, create no fixture/account/worker, and exit
zero twice. The outer test will accept it: its only independent database
observations are that the per-token namespace is empty before and after, and
that an unrelated ambient decoy remains unchanged. Empty-before plus
empty-after does not prove that the required fixture, four principals, HTTP
workers, sensitivity double or cleanup ever existed.

This is the prohibited missing-helper tautology. The current intended RED proves
only that a named file is absent; it does not prove that production lacks the
characterized queue behavior.

### 2. The literal HTTP matrix and projection sensitivity are not independently observed

The outer test parses no HTTP response. It does not independently check any
status, header, body/hash, HEAD parity, row identity/order, escaping, engineer
precedence, activity maximum, PTO flag, canonical href, pagination identity or
failure case. All of those may be asserted only inside the same future helper
whose success transcript the outer test trusts.

Consequently a response-only fake, direct renderer call, reconstructed graph,
or hard-coded transcript can pass. There is also no outer sensitivity mutation
that changes one approved fixture input and requires the corresponding observed
HTTP output to change while unrelated output remains stable.

### 3. Four-principal SQL audit and anti-write sensitivity are self-attested

The outer test does query `mysql.user`, but only to require zero matching
accounts before and after each helper run. It never observes creation of exact
`_s`, `_a`, `_b`, `_x` accounts, verifies their effective grants, maps their
process/thread identities, captures statement history, or checks overlap and
teardown. `ccqAssertAudit()` merely compares helper-authored JSON literals such
as `runtime_slots`, `concurrent_threads`, `production_write_attempts` and
`sensitivity_write_attempt_observed`.

The required sensitivity double is therefore not anti-fake evidence: the same
helper being tested declares both that it attempted DML and that its audit
detected/rejected the attempt. No independently observed DML statement is
required for the main characterization to proceed.

### 4. Per-boundary deadlines and cleanup are not proved

The outer `ccqRun()` provides one 25-second process deadline and process-group
termination. It does not independently enforce or observe the specification's
bounded worker readiness, socket exchange, composition barrier, response
barrier, audit query, teardown and child-exit deadlines. Nor does it enumerate
and reap the helper's exact worker PIDs.

Post-run absence checks cannot prove attempt-all cleanup because a helper that
created nothing passes them. Cleanup needs independent evidence that every
declared owned table, all four exact accounts, artifact/session child and
worker existed at the required phase, followed by independently observed
absence and ambient-decoy preservation.

## Required correction

The reviewed test/support package must make evidence ownership independent of
the verifier under test. At minimum:

1. Move HTTP byte parsing and the full literal authorization/header/projection/
   pagination/failure/repeat/concurrency assertions into a meta-test-owned
   observer, or expose raw request/response artifacts that the outer test reads
   and validates independently.
2. Have the outer privileged observer create or independently witness the exact
   fixture and four runtime accounts, verify effective grants, bind each exact
   connection to its performance-schema thread, and read/classify statement
   history itself. The helper must not author the claim that its own statements
   were read-only.
3. Make the sensitivity run observably fail the meta-test based on an
   independently captured `_x` DML statement, then run the production
   characterization only after that rejection is proved.
4. Record exact phase markers/PIDs and enforce every required deadline and
   attempt-all cleanup from the independent owner. Prove positive existence
   before proving cleanup absence.
5. Demonstrate that a literal transcript/audit-JSON fake and a response-only
   fake fail. Capture a new intended RED caused by missing production behavior,
   not merely by an absent helper filename.

These changes restart Gate 2 and require a fresh independent Gate 3 review.

## Reproduction

Canonical MariaDB:

```text
php tests/Verification/characterize_construction_control_queue_001_test.php
SETUP_FAILURE: performance_schema must already be enabled
exit 2
```

No verifier helper or owned artifact child remained after the run.

Disposable `mariadb:11.4.7-noble` was started with performance schema,
`events_statements_history_long` consumer, statement timing and history size
10000 enabled at server startup. The focused test reached:

```text
RED_ASSERTION: missing test-only production-HTTP queue harness must become a successful first run
Could not open input file: .../tests/Support/characterize_construction_control_queue_verifier.php
exit 1
```

The exact disposable container was force-removed after reproduction. This is a
deterministic missing-helper RED, but for the reasons above it is not a
sufficient behavior-sensitive Gate 2 RED.

Static checks:

```text
php -l tests/Verification/characterize_construction_control_queue_001_test.php
No syntax errors detected

openspec validate characterize-construction-control-queue --strict
Change 'characterize-construction-control-queue' is valid

git diff --check -- tests/Verification/characterize_construction_control_queue_001_test.php docs/operations/construction-control-queue-red-evidence.md
PASS
```

## Exact reviewed hashes

```text
f0dadc560979970968e265e8e26f0bfed42101a00dcaaed2f8cc7cf311c0243d  specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md
cd086d4baa20d6bc27d9850e27a0aac63ca7983c8df250db6183282980c4b0af  tests/Verification/characterize_construction_control_queue_001_test.php
cdb24932c216e028ec68a9d2d9cbb308d5673aee710952ecf8bd3261ad38f401  docs/operations/construction-control-queue-red-evidence.md
de7167bc108bdbf3fbd348434c4818324455eb900935c6c974dd3f4ed93e3a02  openspec/changes/characterize-construction-control-queue/proposal.md
bf0ac6a294aa34ef379237208eb6b765117fdef1564a9cc693fa9c0cc615178b  openspec/changes/characterize-construction-control-queue/design.md
a361549f4ab8920218fc4438877811de971da8695cbc608a2ad153cd98aef412  openspec/changes/characterize-construction-control-queue/specs/verification/construction-control-queue-characterization/spec.md
259d04a7f9d414720fb788fa7d21bbb0da5278f169051138ac2baececfe9d12e  openspec/changes/characterize-construction-control-queue/tasks.md
```
