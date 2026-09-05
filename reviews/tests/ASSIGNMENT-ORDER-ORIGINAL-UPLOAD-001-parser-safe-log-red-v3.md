# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 parser/safe-log RED v3

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/command_gate5_final_gate3`
- RED commit: `22c103074ce6b161d73b01280f643461f45a0c2d`
- Gate 5 finding: `eea7a12ed42cd1a4baa91ea8762987ac13768643`
- Scope: the two corrective executable-test edits and their RED evidence; no production, test, specification or OpenSpec artifact was changed by this reviewer
- Verdict: **CHANGES_REQUESTED — parser RED approved; production-config safe-log RED requires an owner-approved Gate 1 amendment**

## Exact reviewed identities

```text
0f7fbfcdf121bb1dcd75e2ee1b4823699621bd5ae256973923d427cf5263cb97  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
fce7a7bd6e75d6d714241d054fdb46751651f60abf21e5a845c670dbb899943e  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0e7f5fd84c6974b411a5f8a869fb270e663cae99fdb1017c15e585218dec8bd4  openspec/changes/replace-pilot-registration-with-original-upload/design.md
6ace1c1e3029bf549cd2e0fd7127610e670285e48d5aed0a02ffdb679123ab5d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

## Parser RED: approved in isolation

The classic positive fixture is independently generated and its object-1 entry
is changed to the known in-range object-2 offset. The xref-stream positive
control is asserted before mutation; its fixed `/W [1 4 4]` entry is then
changed independently to a different valid object offset, an out-of-file offset
and unsupported type 3. The expected `INVALID_PDF` outcomes trace to the active
fail-closed broken-offset/conflicting-identity parser contract and do not reuse
production parser logic.

Exact reproduction:

```text
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
Expected: classic_identity=INVALID_PDF, stream_identity=INVALID_PDF, stream_bounds=INVALID_PDF, stream_type=INVALID_PDF
Actual:   classic_identity=PASSIVE_PDF, stream_identity=PASSIVE_PDF, stream_bounds=PASSIVE_PDF, stream_type=PASSIVE_PDF
EXIT=255
```

This is a valid, bounded demonstrated RED for the parser Gate 5 finding. A
minimal parser correction may proceed against this approved portion without
changing the asserted behavior.

## Production safe-log RED: public-contract traceability blocks approval

The cleanup probe is a useful sensitive oracle: it preserves exact
`REJECTED/INVALID_PDF/non-retryable`, expects one canonical append with only
the request-derived correlation, fixed event, phase and sequence, and keeps the
filename and exception sentinel out of the expected bytes. The missing, `0644`
and `/dev/null` targets also fail RED as recorded:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
production factory cleanup probe: exact safe log append absent
production factory safe log missing: accepted
production factory safe log invalid mode: accepted
production factory safe log discard device: accepted
EXIT=255
```

However, the active executable specification's normative PHP block declares
`AssignmentOrderOriginalProductionConfig` with exactly two public constructor
fields: `privateStorageRoot` and `tablePrefix`. The RED changes that public
constructor use to a third `safeLogFile` argument. No reviewed artifact provides
an already-approved production-factory config amendment for that third field.

The owner-approved worker safe-log v5 amendment is not such an approval. Its
approval record is
`docs/operations/assignment-order-original-worker-safe-log-owner-approval-2026-09-04.md`
at amendment commit `2880b55a8f52d0c64d581f6952ae78441d8fa398`; it explicitly binds
`safeLogFile` in the serializable five-FD **worker** config and the independent
evidence-reader config. The active delta scenario likewise says “Worker и
evidence reader имеют одну safe-log identity”. It does not amend the exact
production-config class block or specify a production deployment lifecycle for
that public path.

Approving this test would therefore approve an unreviewed public application
configuration contract by implication. Gate 3 cannot do that. The owner must
first approve a Gate 1 amendment that explicitly defines the production
safe-log configuration/binding (including exact constructor shape, path
ownership/lifecycle and fail-before-use semantics), and the executable spec,
design and delta must be made coherent. After that, a fresh Gate 3 must review
the safe-log RED against those exact hashes. The existing RED also asserts only
construction failure for invalid targets; any amended “before DB/storage use”
claim needs an observable mutation/access sentinel rather than prose alone.

## Isolation and cleanup audit

Commit `22c1030` changes only the two executable tests and its append-only RED
evidence. It changes no `app/` production file, normative spec or OpenSpec
artifact. Both reproductions reached their intended assertions. The database
test creates a random exact database and task root, closes its connection,
drops only that database in `finally`, and removes only that root. A post-run
repository status check found no test-created residue. `git diff --check
22c1030^..22c1030` produced no output.

The combined corrective test commit is not Gate-3-approved because one half
crosses an unapproved public contract. Parser production work is admitted only
for the parser assertions identified above; safe-log production work remains
blocked at Gate 1.
