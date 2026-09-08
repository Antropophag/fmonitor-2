# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 storage/parser/boundary v2

- Date: `2026-09-05`
- Reviewer: `Codex agent /root/command_gate5_storage_gate3_v2` (fresh independent reviewer; did not author the reviewed tests, production implementation, or planning artifacts)
- Corrective RED commit: `bc1c917b108ab9ade17c2922e37c9739aa8ee6c4`
- Original RED commit: `c9714ec25ef51933e5760c97b1a2f35980d26b8d`
- Prior Gate 3 finding: `df89440627b74ac9f063ca341024e6014f721c01`
- Production baseline: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Verdict: `CHANGES_REQUESTED`

## Exact reviewed identities

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
10f29221e3576e159b4fed2342591eb45138f9e22352ad4b91851df5f8f5c2dd  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ca7f0f48a84c0b695cccd88fd76a7e01bb58d72aee11bba2603395628d1e1eb1  tests/Support/AssignmentOrderOriginalPdfCorpus.php
8b162fe38a8df31637eebf317392419ebb94e9ec54205898242bf418d4a26b25  tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php
dc7f867627ae0f3f5e328ec7db6078f58f0e14649788b8f5cb048e88f223a0a2  tests/Support/assignment_order_original_private_bytes_restart_probe.php
a8d6ab6b89ebbc6ea452a42be919d7f705164f0935e58c6a77b83927db8e9b9f  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
fb03988e076fc22c51f2b9354f7d18f6891fd8d4618f92e2dee014276d5be1bd  docs/operations/assignment-order-original-gate5-storage-parser-boundary-red-v2-2026-09-05.md
```

The concurrent uncommitted worker post-finalize negative test and its untracked
evidence file were explicitly excluded from this review.

## What is adequate

The private-byte restart oracle continues to exercise the real storage
stage/write/finalize/lease boundary, drops process-local references, and uses a
fresh PHP process to prove exact independently hashed bytes. It remains an
intended RED with child count `0` instead of `1`.

The corrected indirect-action corpus has no direct forbidden key in its
Catalog. The reachable variant adds only the allowed `/Names 4 0 R` edge; the
same physically present `/JavaScript` dictionary is unreachable in the
control. A focused run produces `unsafe_pdf` for both variants, so the required
reachable/unreachable distinction is genuinely RED. The full parser suite is
still independently RED first at the valid incremental `Prev` assertion.

Private-root negative cases, owned mode-`0600` safe-log positive use, safe-log
negative cases, orphan-fixture boundaries and cleanup are deterministic and
task-owned. The production-boundary run left zero `t_aoou_boundary_%`
databases and no matching temporary control root. The factory assertion also
compares table inventory before and after construction/use, correctly pinning
the no-runtime-DDL requirement.

## Blocking findings

### 1. Factory use still cannot distinguish production bindings from stubs

The corrected test calls `ProductionAssignmentOrderOriginalFactory::create`
and requires the concrete final `AssignmentOrderOriginalService`, but then
submits request ID `invalid-request`. `AssignmentOrderOriginalService` rejects
that command at its initial shape check, before any repository, order,
composition, PDF-inspector, storage, ID, or persistence adapter is reached.

Consequently a factory that constructs the real service owner with stub/fake
dependencies passes the positive oracle. This does not satisfy the prior
required correction to prove usable production-only bindings and is not
sensitive to the Gate 5 production-composition regression. Exact-class checking
prevents a fake application object, but it does not prevent fake dependencies
inside the real owner.

The corrected RED must execute a shape-valid command far enough to observe real
production adapters through the public factory seam. Prefer an isolated,
schema-prepared accepted path whose durable MariaDB rows and private bytes are
independently observed. If a bounded fail-closed path is used instead, it must
still prove a production adapter was reached and cannot be reproduced by an
arbitrary stub dependency bundle. It must retain the canonical existing `0700`
root, real MariaDB, no-runtime-DDL inventory comparison, and task-owned cleanup.

### 2. The v2 evidence transcript is not exact

The recorded production-boundary transcript omits an actual line from the
reviewed execution:

```text
private root no-create: path created
```

The executable test emits that line on the stated production baseline. Because
the project requires append-only exact RED evidence, the next correction must
add a fresh evidence record containing the literal complete output and exact
exit statuses. Existing evidence must not be rewritten.

## Reproduction

```text
$ php tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php
Expected child count: 1
Actual child count: 0
exit 255

$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
A bounded valid incremental Prev chain is accepted after parsing every revision.
Expected: PASSIVE_PDF
Actual: INVALID_PDF
exit 255

$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
INTENDED_RED: approved production boundary is incomplete:
production factory: absent
private root no-create: accepted
private root no-create: path created
private root mode: accepted
private root symlink: accepted
private root protected chain: accepted
safe log exact 0600: accepted
safe log symlink: accepted
orphan marker token: accepted
orphan production-root disjointness: accepted
orphan injected clock future: accepted
orphan injected primitive fault: accepted
invalid/fault fixture attempts changed task-owned inventory
orphan collision: accepted
exit 255
```

All three corrected PHP artifacts pass `php -l`; `git diff --check
bc1c917^ bc1c917` exits `0`. No product behavior, role, authority, public
contract, production code or OpenSpec task was changed by the reviewed commit.
Gate 4 for this corrective batch remains forbidden pending another corrected
RED and a fresh independent Gate 3 approval.
