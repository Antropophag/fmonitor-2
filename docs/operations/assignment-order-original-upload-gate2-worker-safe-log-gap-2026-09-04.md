# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 2 worker safe-log constructibility gap

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR REMAINING TASK 2.2**

Owner approval `afe8dda` makes the fresh-connection evidence reader itself
constructible. Its exact config requires an absolute owned `safeLogFile`, and
`safeLogsCanonicalJson()` reads only that configured file.

The mandatory five-FD worker/fault matrix still cannot bind the writer to that
reader. `AssignmentOrderOriginalWorkerConfig` has no safe-log path or safe-log
binding field. `AssignmentOrderOriginalProductionConfig` likewise contains only
private storage root and table prefix. The worker protocol says the child
reconstructs adapters and injects only clock, IDs, inspector, fault and barrier;
it does not define where its `AssignmentOrderOriginalSafeLogObserver` writes.

This matters for required release/commit fault assertions. Lease-release failure
must log one exact redacted record with the selected phase, and the independent
reader must prove its cardinality/content after the child exits. Supplying a
reader-only file cannot prove the worker wrote to it. Choosing a path through an
environment variable, mutable global, conventional filename below private root,
or test callback would invent an unapproved selector and conflicts with the
explicit closed worker-config key set.

The smallest amendment is one exact safe-log binding in the worker production
composition: normally an absolute `safeLogFile` field in
`AssignmentOrderOriginalWorkerConfig`, with the same ownership/path validation
as evidence config and an explicit statement that the worker's real safe-log
adapter and the evidence reader address that file. If logs are instead stored in
MariaDB, the evidence-reader contract and worker binding must state that exact
owner consistently.

No production, test, OpenSpec or review file was edited. Part 1 commit `b0f1e60`
remains valid; the full task 2.2 matrix is not complete and cannot honestly claim
five-FD lease-release/commit-fault sensitivity until this writer/reader identity
is approved.
