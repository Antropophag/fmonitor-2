# Test review: ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001

- Reviewer: `/root/original_gate3`, separately tasked independent agent; did not author spec, tests, worker or production implementation.
- Test author: root implementation agent.
- Reviewed source: `185d95c616266260aafa8e874acc9b95d507c3d5` plus test-only additions identified below.
- Specification: v0.1, independent Gate 1 APPROVED.
- Public seam: native original-reference factory, reader lookup and borrowed-transaction guard; existing public native selection/original commands supply accepted evidence.
- Verdict: `APPROVED`.

## Findings and review iterations

Read specification, Gate 1 record, test, worker, prefix fixture extension and inherited bounded process-control support. Seven cases at prefixes 0 and 25 cover exact snapshot metadata, by-value copying, idle/borrowed transaction rules, correction freshness, malformed backing, identity/configuration/binding rejection, accepted-original requirement and real correction contention. Generated root/revision IDs come only from the public original command; the remaining 14-field metadata expectation, PDF size/hash/date/upload instant and composition hash/snapshots are independent fixed specification values. Exact array comparison excludes extra private metadata. Moving the task-owned private root while reading metadata distinguishes this port from a PDF-availability reader.

The RR test establishes a caller snapshot, commits a genuine correction through a separate native connection, proves an ordinary read still sees the old root, and requires the guard to return changed. This catches reuse of ordinary stale snapshot reads after taking a case lock. Later lookup requires the new revision/date and leaves the old reference unchanged. A later pending selection without an original does not impose a latest-order policy on the earlier accepted reference.

The worker invokes the real production original command and reports its owned connection ID. The parent identifies that connection in PROCESSLIST while its exact case-row FOR UPDATE query persists, requires the caller transaction to remain active, then releases via rollback and requires a clean accepted correction. Deadlines, pipe draining, process reaping/termination and control-connection cleanup are bounded. SQL observation is lock evidence for the actual native writer, not a replacement command or injected lock outcome. During preparation the author corrected an initially copied selection-writer predicate to the real original-writer `SELECT id ... WHERE id=4512 FOR UPDATE` query before the final reviewed RED.

Independent review found that transaction-active checks alone would miss commit/rollback followed by a new transaction on successful guards. The author added caller-owned sentinel writes for matched/repeated, changed and corrupt-source unavailable outcomes. Each now proves its write remains visible before caller rollback and disappears after it. The existing active-read/foreign-reference sentinel test remains. Consequently early commit and early rollback both fail, while whole-fact comparisons continue to prove no reader writes. This finding and its closure are retained here rather than omitted from the review history.

Configuration/negative expectations are closed and meaningful: invalid IDs precede closed-connection access; malformed prefix returns the exact sanitized exception; wrong prefix cannot silently use unprefixed facts; closed connection and wrong charset are unavailable; foreign reader references, read-only transactions and switched database fail without taking ownership of caller state. Corrupt byte-size backing returns unavailable rather than empty/not-found/changed and remains unrepaired. All transaction, source and filesystem setup/observation remains within task-owned synthetic resources.

The optional fixture prefix is threaded through native schema, authorization, original config and fresh reader without changing the default. The recorded existing selected-original binding regression passed both verification and production constructors. Prefix25 setup succeeds for all seven cases, independently exercising the actual extended fixture before the new seam is reached.

## RED evidence and identities

Root command: `php tests/AssignmentOrderComposition/original_application_reference_001_test.php` with the documented synthetic MariaDB environment. Final `/Users/antropophag/.local/state/fmonitor2-verification/original-reference-20260907/red-v3.log` contains 14 SETUP_OK, 14 CLEANUP_OK and 14 intended missing-factory assertions, exit 1. Setup genuinely performs selection and accepted original first. The worker/guard assertions are pending behind that absent seam; this is not a claim of completed GREEN concurrency execution. Earlier logs remain historical. The reviewer inspected evidence and verified counts/hashes rather than claiming another native execution.

```text
cf1ae6a3da0683cc7d4f5040dd03e72963b5f4b7958cd2b71101c8d8c7c46841  specs/ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001.md
aad4743bef57e1852a3245f6771df42224bcd7843e976bebca749f8f6fde9e93  tests/AssignmentOrderComposition/original_application_reference_001_test.php
042f26acbe469048cb76b6b71869a1791f94a6df1e0d4a11efffa0ee08434656  tests/Support/original_application_reference_worker.php
b4ec647e49651ddfacfb16052fd43152e3c6b0ade19cde910992c16e842fbaa5  tests/Support/SelectedOriginalFixture.php
```

No remaining blocking findings in the reviewed tests. Gate 4 may begin. Actual native GREEN including RR/worker completion, relevant original/selection regression, architecture/lint/diff and independent Gate 5 remain mandatory. No parent application, HTTP grant or migration completion is implied. Only this review record was written by the reviewer.
