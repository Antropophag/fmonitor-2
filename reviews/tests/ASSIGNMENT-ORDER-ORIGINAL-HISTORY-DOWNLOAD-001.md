# Test review: ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001

- Reviewer: `/root/original_gate3`, separately tasked independent agent; did not author specification, test, fixture, worker or production implementation.
- Test author: root implementation agent.
- Reviewed source: `58203f3945d00c17ca1c96d5ec446aa40d618d86` plus test-only additions identified below.
- Specification: v0.1, final Gate 1/v2 APPROVED; SHA-256 below.
- Public seam: proposed production history reader factory/readHistory/prepareDownload; existing public native commands supply accepted evidence and existing storage owner supplies exclusion lease.
- Verdict: `APPROVED`.

## Findings

Read normative contract, test, fixture, worker and RED record. Nine cases run at prefixes 0 and 25. Every fixture creates a registered selection and two distinct accepted PDF revisions through existing public application commands before reaching the new seam. Metadata expected values are fixed independently: each PDF's known hash/size, original dates, frozen upload timestamp, actor, composition hash/snapshots and correction reason. Only generated root/revision IDs come from command receipts. Exact array comparisons cover ordered whitelisted fields and exclude private identities, configuration and paths.

Pagination checks full page, first/next limit-one pages and at/past-end cursors, retaining context and snapshot total/current pointer. Later native correction leaves an earlier page and PDF buffer unchanged while a later cursor sees the appended revision. Nested metadata and returned bytes are modified by the test without changing subsequent result values. Historical and current PDFs have different fixed hashes, so a reader substituting current bytes for an old revision cannot pass. A later pending order preserves earlier history; after a second accepted root exists, both directions of cross-order revision access reject while that order's own PDF succeeds.

Negative status checks distinguish invalid arguments, absent selected evidence/revision, corrupt existing backing and unavailable connection/configuration. Closed-connection validation order and fixed exception shape remain observable. A native caller sentinel proves both methods refuse ambient transactions without commit/rollback; charset and null-database checks prove the connection is not repaired or switched. Old-revision corruption fails history and both downloads rather than hiding a damaged chain. Missing root affects download but not healthy metadata or an already prepared buffer.

Filesystem checks use task-owned native files: missing, short, extra and same-size corrupt PDF, root symlink, PDF symlink/hardlink/directory and missing digest lock. Exact 20MiB accepted evidence is prepared intact, while an added byte fails closed. Public storage holds an actual exclusive digest lease while a separate owned worker calls the production reader. The parent requires worker completion/unavailable before releasing the lease, with bounded phase/completion deadlines and cleanup, so mistakenly blocking flock does not hang the parent indefinitely. After release, repeated downloads succeed and a new exclusive lease can be acquired. No native function/driver/result interception or diagnostic runtime reader is introduced.

## Review iteration and closure

Initial review found that PDF aliases alone did not prove the equally strict digest-lock boundary, and that root/PDF/lock mode validation lacked sensitivity. The author added a root0750 positive case; root0755, PDF0644 and lock0644 negatives; digest-lock symlink/hardlink/directory negatives; and exact restoration. These remain readable task-owned resources, not OS-denial or ownership privilege probes.

The shared observer now explicitly checks idle transaction state after return as well as unchanged native stream count, all DB rows/DDL and private filenames/hash/mode/UID/link count. Root lstat metadata is included as the `.` entry, so a forbidden chmod repair of the root cannot disappear behind fixture restoration. This closes the review's mode/alias/no-repair/resource-release gaps. A later reviewer question about root observation was resolved by re-reading the final helper; it was already present in the same final amendment.

No remaining blocking test findings. These tests do not themselves establish mid-read descriptor races, all OS fault outcomes or absence of prohibited production dependencies; specification compliance on those boundaries still requires independent code review. No test success may substitute for that review.

## RED and exact artifacts

Root command: `php tests/AssignmentOrderComposition/original_history_download_001_test.php` with documented explicit synthetic MariaDB environment. Inspected final `/Users/antropophag/.local/state/fmonitor2-verification/original-history-20260907/red-v4.log`: 18 SETUP_OK, 18 CLEANUP_OK and 18 intended missing-factory failures, exit 1. Both max-size cases additionally accept the real 20MiB original before reaching the missing seam. Later metadata/filesystem/worker assertions are pending behind the absent factory; their actual GREEN is not claimed. Earlier RED iterations remain historical. Reviewer verified log counts and hashes rather than claiming another native execution.

```text
ca1a153bafdf9a08f0d74e2219a3b90c59a14f24da9a56b0bf34f727f100fd11  specs/ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001.md
e16e41c2ecc548b748582d02c91965f1bf908cb9d9d03fd38f3da8da87566093  tests/AssignmentOrderComposition/original_history_download_001_test.php
749294f33774688faf8c734edf31397cd54f9f05071e25985663e7bd1f3015e7  tests/Support/OriginalHistoryFixture.php
b3c23b8af3efd3c7ccaf8dac71c0a80ab4ceeb5209e478e08a9da2e80ee16ae0  tests/Support/original_history_download_worker.php
```

Gate 4 may begin with these expectations. Native GREEN including worker/resource outcomes, relevant original regressions, architecture/lint/diff and independent Gate 5 remain mandatory. Parent HTTP roles/grants, application-date policy, opening and launch/full verification are outside this approval. Only this review record was written by the reviewer.
