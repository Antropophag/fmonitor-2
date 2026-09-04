# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 5 code review v1

- Reviewer: separately tasked agent `/root/original_upload_gate5_preflight`
- Review timestamp: `2026-09-04T06:21:44+03:00`
- Production implementation endpoint: `a083637a3cc67cd5b60f8b10a9a489c48cc540d5`
- Production implementation commits: schema `17f03a5c0e997c0f15188c736b50a97ab95e4014`, core `da45b5eee01a6a120054d3d08238512cc3466e6b`, parser `c46c4f382fc4c6ef84fb57c2b35cd343a9717af7`, private storage `3987c39`, maintenance `613bd26`, MariaDB worker `a083637a3cc67cd5b60f8b10a9a489c48cc540d5`
- Reviewed production range: `e5cc1603f4c49a755476bd769bebbed4eaafca76..a083637a3cc67cd5b60f8b10a9a489c48cc540d5`
- Current evidence envelope: `4f47c1f3be6e142d63b49fca5b7832c00505fb7d`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Normative OpenSpec delta SHA-256: `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Verdict: **CHANGES_REQUESTED**

## Independence and delivery scope

The reviewer did not author the specification, tests, RED evidence, production
implementation, or GREEN evidence. This append-only review record is the only
change made by the reviewer. The review covers Standards and Spec separately,
including security, private storage, MariaDB concurrency, maintenance,
append-only behavior and scope. Later v12 consumer/session corrections in the
evidence envelope were read as integration evidence but are not production
implementation of this slice.

Production files and reviewed hashes:

```text
8a28d5d18885dffd03fd630b28dce190488de7d3ff0d7ccf9a2224ba3d688e32  app/AssignmentOrderOriginal/AssignmentOrderOriginalApplication.php
18f9690734a5f4a1489b5afa82b02d682b4517eae134adbf2fdd6aaf42a463ab  app/AssignmentOrderOriginal/AssignmentOrderOriginalMaintenanceApplication.php
2ff6365d6b1bd48c4bcfdaab3f28351ddbbbfe5ad065371731cabbf7cadfa500  app/AssignmentOrderOriginal/AssignmentOrderOriginalPrivateStorageFactory.php
77898a06762192900257f03efe41e3cbc382b2471c902b893864db08e27c52f1  app/AssignmentOrderOriginal/AssignmentOrderOriginalVerificationWorkerBootstrap.php
6a33746714b5d6554cf4820387a316c6829861f0057fe6229499cfaf981d3ebd  app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
c03ccfbf857bf2d645c66462e602e19b00186f70b0bd5fa6845b9e1c5fa440fd  app/AssignmentOrderOriginal/ProductionAssignmentOrderOriginalFactory.php
c1cf978b175c98088e7c870aa06bfdae3b6699f4a06050fa95a7de4c6cb3f1e0  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
61e9698b02093bccfe4eca8c71b8c53ccbd53c28f6380c6c091e461008a5d10d  app/InstallationProcess/MariaDbAssignmentOrderOriginalProductionRuntime.php
0d1675da7af05262d80a43d35158625d30473b652a73bd92a4dc9deebbc814c6  app/InstallationProcess/ProcessCapabilityChecksClassifier.php
90b9c3ac51a7dc5cd827a21479ed2adcbcf7f021b8517d12103f6d1d508b2346  app/InstallationProcess/ProcessCommandCapabilitiesSchemaMigration.php
1d10cf28fb7395b8908f5b1c76116e344c377955f061c3c9112fb7a062fedd89  bin/fmonitor2-migrate.php
```

## Standards findings

1. **BLOCKING — the public production seam is not constructible.**
   `ProductionAssignmentOrderOriginalFactory::create()` unconditionally throws
   in `app/InstallationProcess/MariaDbAssignmentOrderOriginalProductionRuntime.php:5-6`.
   There is no trusted production wiring for authorization, composition lookup,
   clock, repository, private storage, lifecycle observers and safe logging.
   This violates the constitution's one explicit public application seam and
   the v4 production-composition contract.

2. **BLOCKING SECURITY — the owned PDF inspector is not the approved parser.**
   `app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php:18-47` performs
   regular-expression searches over raw bytes. It does not traverse `Prev`,
   validate classic/xref-stream offsets and generations, decompress object
   streams, resolve the latest object graph, enforce structural bounds/cycles,
   or inspect active entries hidden inside compressed objects. Therefore it can
   accept unsafe or malformed PDFs and cannot implement pinned
   `fmonitor-passive-pdf-v1` (`specs/...:96-104`).

3. **BLOCKING — private orphan maintenance is non-functional.**
   `FilesystemAssignmentOrderOriginalPrivateStorage::listOrphans()` always
   returns an empty page and `inventoryCanonicalJson()` always returns `{}`
   (`AssignmentOrderOriginalPrivateStorageFactory.php:25,28,43`). Abandoned
   stages and unreferenced finalized blobs can never be discovered or
   reconciled. In addition, finalize reuses an existing digest path without
   proving its bytes and size (`:36`), contrary to verified content reuse.

4. **BLOCKING — the MariaDB verifier bypasses the reviewed application seam.**
   `MariaDbAssignmentOrderOriginalProductionRuntime.php:8-15` implements a
   separate correction-only flow with direct SQL, hard-coded composition
   identity/hash, injected-passive-only configuration and no real PDF,
   storage/lease, repository or audit protocol. This is duplicated domain logic
   and makes the two-worker GREEN non-representative of production behavior.

5. **BLOCKING — public input validation and totality are absent.**
   `AssignmentOrderOriginalApplication.php:70-83` authorizes before validating
   command shape and accepts malformed UUID/date/mode-lineage/reason values.
   The date is compared lexically. Several dependency calls can throw outside
   the narrow catches. The approved contract requires shape first, exact
   no-stream precedence and typed results for every port failure.

The compressed one-line implementation style across the application/runtime
files also creates documented-reviewability and maintenance risk (possible
Duplicated Code and Divergent Change), but it is not independently decisive
given the blocking behavioral defects above.

## Spec findings

1. **BLOCKING — semantic replay precedence is wrong.** Correction lineage,
   stale and target checks run before stream fingerprint lookup
   (`AssignmentOrderOriginalApplication.php:82-83`). A retry with an already
   accepted fingerprint after the leaf moved can return `STALE_REVISION`
   instead of the required `REPLAYED`. The required ordering is fingerprint
   before current-lineage validation (`specs/...:67-75,174-176`).

2. **BLOCKING — `NO_CHANGES` is not durable.** The service uses process-local
   `$accepted` state (`AssignmentOrderOriginalApplication.php:68,83`) rather
   than committed repository evidence. It fails after restart and may retain a
   candidate whose commit lost CAS. This does not meet durable replay and
   append-only lineage semantics.

3. **BLOCKING — worker coverage does not establish the specified concurrency
   contract.** The worker does not invoke the same production application and
   hard-codes composition evidence. Passing identical/different correction
   outcomes therefore does not prove production CAS, storage exclusion,
   response-loss, audit or lease behavior (`specs/...:1037-1039`).

4. The production factory, structural parser and orphan inventory findings in
   Standards are also direct missing v4/OpenSpec requirements, not optional
   hardening or future lifecycle scope.

No scope finding was raised for leaving HTTP upload/read/download, applying the
composition, opening works, or checklist availability unchanged; those remain
correctly assigned to future changes.

## Fresh verification

At the timestamp above, the nine non-DB focused suites passed with their exact
`ASSIGNMENT_ORDER_ORIGINAL_*_OK` transcripts. With the local test-contour
credential, the schema suite printed all five expected schema OK lines, the
MariaDB verifier printed `ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_CONCURRENCY_OK`, and
the canonical runner printed `PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI
contract`.

```text
$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check e5cc1603..a083637
PASS (no output)

$ php -l <each of the 11 production PHP files above>
No syntax errors detected (all files)
```

An initial migration-runner invocation without the explicit test-contour
credential failed setup with MariaDB access denied; the explicit credential run
then passed. No secret is recorded here.

## External predecessor blockers

`docs/operations/assignment-order-original-upload-full-verify-red-v2-2026-09-04.md`
records exact commit `a55565dbd72e3112fd9f133dc3e4c77bfaf3ed94`, command
`make verify`, exit `2`, and
`FULL_VERIFICATION_FAILURE count=3 stages=unit-test,db-test,e2e-test`.
The remaining navigation, object-card/prepare/UI-shell/E2E assertions and
Docker credential/vsock setup issue are external release predecessors. They are
not defects assigned to this implementation, but repository-wide GREEN is a
Gate 5 exit condition and no waiver exists.

## Decision and required next cycle

Gate 5 is **CHANGES_REQUESTED**. Integration is not approved. Correct the
production factory and adapters, replace the regex inspector with the approved
bounded structural parser, implement real verified storage inventory/reconcile,
restore shape/exception/replay precedence and durable no-change behavior, and
make the MariaDB concurrency test exercise that same production seam. Those
production corrections require new focused RED evidence, fresh independent
Gate 3 approval, minimal GREEN, repository-wide `make verify`, and a fresh
independent Gate 5 review on exact hashes.
