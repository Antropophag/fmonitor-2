# Code review: ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001 v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed implementation: `d7ed54d03041605200887c607ce6b3ce81f579be`
- Implementation base: `4c23ee1402e48c1d4225210758a0e0ac4a2e705b`
- Approved specification SHA-256: `4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332`
- Approved Gate 3 v2 SHA-256: `74da9a7f3ca3fdb787eef29639e4673b9ba6dfab6df6709b533d72a2c2d2c67c`
- Approved 95-case test SHA-256: `beec61b817c3234debf85a80f806054e85e9ab4e31b6b03f9a40b03454d313c9`
- Separate empty-probe test-patch approval SHA-256: `4c1d9edb77044373826ac4d1547552d50380a8fce1e151ce4272cc0f8cf5b7f9`
- Verdict: **APPROVED**

The reviewer authored neither implementation nor tests/specification. No
production source, test or specification was edited by this review.

## Findings

No blocking correctness, ownership, ordering, security or maintainability
finding remains in the scoped lifecycle implementation.

### One resource owner and operation-specific failures

`AssignmentOrderOriginalResourceScope` owns the supplied stream, the one stage
actually returned, cleanup-attempt flags and the lease helper for one invocation.
Its flags are set before each abort/stage-close/stream-close/finalize primitive,
so callback or primitive Throwable cannot cause a second attempt.

Stream reads catch Throwable and validate exact status/payload pairs: BYTES must
make 1–65536 bytes progress, while EOF requires empty bytes; every other tuple,
including typed FAILED, becomes retryable STREAM_FAILURE. Stage begin/write/
completed/finalize calls use the storage wrapper and become retryable
STORAGE_FAILURE. Inspector Throwable and `INSPECTOR_FAILED` also map to storage
failure. Classification no longer depends on concrete `WorkerFaults` or a test
target.

`AssignmentOrderOriginalSubmissionFailure` has a private constructor and only
internal technical/conflict/rejected factories. Public dependency Throwable
cannot forge a selected status/reason object. The service catches this internal
type separately, performs scoped cleanup, and maps unexpected pre-commit
Throwable to persistence failure. `AssignmentOrderOriginalResponseDeliveryLost`
is likewise constructed only by the internal post-commit signal boundary and is
re-thrown without cleanup.

### Finalize capture and lease ownership

Finalize is attempted once between exact BEGIN and validated DONE signals.
`AssignmentOrderOriginalLeaseResources::capture` reads outcome status once and
lease once, assigning any returned lease before validating status. It reads lease
status and content once, then captures opaque identity, digest and byte size once
each. Success requires approved outcome status, status-OK lease, non-null content,
valid opaque ID, exact lowercase digest equality and exact positive received
size.

Every getter/validation failure becomes storage failure while retaining any
lease already returned for one later release. A non-success outcome carrying a
lease is therefore released; an exception before the lease getter returns does
not fabricate ownership. `releaseAttempted` is set before the sole release call,
and typed failure/Throwable produces only the isolated phase diagnostic. No path
can retry release.

Normal accepted candidates close stage then stream while the lease is held. A
stage-close failure marks that close attempted, then cleanup performs abort,
remaining stream close and release without repeating stage close. Stream-close
failure similarly proceeds to abort and release without repeating either close.
Cleanup after a valid candidate is otherwise inert for stage/stream and releases
only the lease.

### Observer and lifecycle order

Storage signals now cover every approved command event. STAGE_BEGIN and
FINALIZE_BEGIN occur before their primitives; WRITE/DONE and FINALIZE_DONE occur
only after the corresponding success/validation. ABORT_BEGIN/DONE and STAGE_CLOSE
track actual cleanup attempts. Cleanup callback Throwable is contained and cannot
prevent the primitive or later cleanup; normal acquisition callback Throwable is
an operation-specific storage failure before the primitive starts.

The service emits request-miss immediately before acquisition and fingerprint-
miss before ID/CAS work. It emits post-finalize before commit only after both
candidate closes succeed with the lease held. Commit protocol releases the lease
before post-commit lifecycle and delivery. The latter two callbacks share the
response boundary: lifecycle failure prevents delivery, delivery failure is not
retried, and either produces the fixed redacted response-loss exception without
re-entering cleanup or commit.

Terminal replay snapshots the stored result, then cleanup closes its supplied
unread stream exactly once with no storage/lifecycle events. Post-stream
fingerprint replay uses the same attempt-always abort, stage-close and stream-
close owner as rejection; first cleanup failure cannot skip later cleanup or
replace replay.

### Commit, CAS, recovery and audit preservation

`AssignmentOrderOriginalCommitProtocol` owns all post-lease repository work.
Generic commit Throwable becomes OUTCOME_UNKNOWN and performs one terminal
recovery read. FOUND copies a snapshot, NOT_FOUND selects persistence failure,
and unavailable/Throwable/malformed recovery selects outcome unknown. Every
selection then passes through one phase-aware lease release.

Correction rereads preserve composition/current/target/no-change precedence.
CAS conflict performs fingerprint then current-lineage classification, retains
the lease until selection, and returns replay/conflict/persistence outcomes
without blind retry. Post-lease repository/getter Throwable is caught within the
protocol, selects persistence failure and still releases once.

The service commits the existing terminal attempt audit after release only for
selected non-replay rejection/conflict results. Audit failure retains the
existing persistence-failure mapping. Accepted results alone reach post-commit
lifecycle and delivery. No stage/stream operation occurs after commit.

Actual fresh-connection construction and stored-data validation remain the
explicit separate data-integrity dependency; this scoped application-port
implementation does not claim that evidence.

### API and construction integrity

Runtime removes the prior inline service and eagerly loads the extracted service
through `require_once`. The service then eagerly loads its internal collaborators.
Existing public DTOs/interfaces and production/verification factory construction
continue to resolve through direct Runtime imports. The PDF inspection value now
has a private constructor and closed factories, preventing public fixtures from
creating undeclared statuses while retaining the approved four outcomes.

The implementation introduces no runtime selector, alternate storage mutation
path, SQL, HTTP behavior or product permission. Internal classes are small and
single-purpose; the extracted service is 135 lines and the resource scope is 121
lines.

## Independent verification

The reviewer independently ran the unchanged approved focused test:

```text
$ php tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php
PASS COMMAND-LIFECYCLE-001 (95 cases)
```

All ten affected production files linted successfully. The focused production
diff has no whitespace errors. Parent evidence records the affected command
regressions and checks at the same SHA; its current archive identity is:

```text
7da8027b22415cb97b229f976127cedf0667844ea077441e71cefd6bc23f72a8  /Users/antropophag/.local/state/fmonitor2-verification/original-lifecycle-green-x2ov5wgx/evidence.json
```

## Exact reviewed hashes

```text
1041c338567898b577b218c6b4bd73c2be872096fd994276e4c3680bdf95dcbd  app/AssignmentOrderOriginal/AssignmentOrderOriginalCommitProtocol.php
71ed137916d136df291271d59408bd535aa33698f0b9cc206c8ba0f723b4d66a  app/AssignmentOrderOriginal/AssignmentOrderOriginalLeaseResources.php
248d021840cc9d2016faf9d25c65196839db6987b58074c11f42eded7935e047  app/AssignmentOrderOriginal/AssignmentOrderOriginalPdfAcquisition.php
3b98f31a17a5b99b6ec6ded0e63d1c18518964371ef467d2c9ed4fcc5304b426  app/AssignmentOrderOriginal/AssignmentOrderOriginalResourceScope.php
c26ad70b349d61e62834b13c2deb3de78f7ba30c596c7a3d1ada9017cfa89e96  app/AssignmentOrderOriginal/AssignmentOrderOriginalResourceSignals.php
963199da931e0acda1b075efbd06081bd1045f2a6fde4590ebd7c040a4a9f680  app/AssignmentOrderOriginal/AssignmentOrderOriginalResponseDeliveryLost.php
3b8036283e1d3de7266027ae5d59fc76ad16c393d08e44f2fcfa8810abcc68f7  app/AssignmentOrderOriginal/AssignmentOrderOriginalResultSnapshot.php
014af5a9b72ab93d7e03e9d3dbb1da208b22b28e5bf9c40bead0532ac60e74a8  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
50500d4bfe2ab4cc50f3c5d10480205538e702935b688f658e7a0a546966cf86  app/AssignmentOrderOriginal/AssignmentOrderOriginalService.php
4e8f0fe09b3a109f6bf7ec87e2e70db3e3e5be00c76a7c4ca7a58fb04d9c9cd4  app/AssignmentOrderOriginal/AssignmentOrderOriginalSubmissionFailure.php
4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332  specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md
beec61b817c3234debf85a80f806054e85e9ab4e31b6b03f9a40b03454d313c9  tests/InstallationProcess/assignment_order_original_command_lifecycle_001_test.php
1153ae35c0bf052ffffe498561c9636575111026a3e91e57fea1edc4ed73646e  tests/Support/AssignmentOrderOriginalLifecycleFixture.php
74da9a7f3ca3fdb787eef29639e4673b9ba6dfab6df6709b533d72a2c2d2c67c  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001-v2.md
4c1d9edb77044373826ac4d1547552d50380a8fce1e151ce4272cc0f8cf5b7f9  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-LIFECYCLE-EMPTY-PROBE-001-v1.md
```

## Scope boundary

This verdict approves only COMMAND-LIFECYCLE-001 at exact implementation
`d7ed54d03041605200887c607ce6b3ce81f579be`. Fresh-connection/data-integrity,
remaining public declaration and maintenance adapter gaps remain open. It is not
a combined original-command Gate 5, full `VERIFY_OK`, deployment or launch
approval.

This review omits its own circular hash.
