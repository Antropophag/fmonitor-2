# Gate 3 test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 storage/parser/boundary v1

- Дата: `2026-09-05`
- Reviewer: `Codex agent /root/command_gate5_storage_gate3` (fresh independent Gate 3; не писал reviewed tests, production implementation или planning artifacts)
- RED author: `Codex agent /root/command_gate5_red_storage`
- Reviewed RED commit: `c9714ec25ef51933e5760c97b1a2f35980d26b8d`
- Gate 5 finding record: `fa97cfd5f5ca900424bcf9863eff66ed6b701a2e`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Verdict: `CHANGES_REQUESTED`

## Exact reviewed identities

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
16f5e9378388e96199e4d6cbdcfef23361169052aa46102d58e779764ef8fefc  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
b5b0417669e30f3edc9d3f0d07590537a64e3da5890f847344e7a2586fa279e9  tests/Support/AssignmentOrderOriginalPdfCorpus.php
8b162fe38a8df31637eebf317392419ebb94e9ec54205898242bf418d4a26b25  tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php
dc7f867627ae0f3f5e328ec7db6078f58f0e14649788b8f5cb048e88f223a0a2  tests/Support/assignment_order_original_private_bytes_restart_probe.php
619b08ef2a8dd9afd5e630b511b394f64ee818d106ecc2d6c7743879ba0e1108  tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
0974d25b27ed6976adcf6a66fea628170334cb6f7bb458a8572a4da085a63da0  docs/operations/assignment-order-original-gate5-storage-parser-boundary-red-2026-09-05.md
```

## Traceability, seam и RED

Persisted-bytes test проходит real `AssignmentOrderOriginalFileStorage`
stage/write/finalize/lease seam, освобождает process-local references и читает
изолированный private root отдельным PHP process. Random marker, independently
computed SHA-256/size и exact-one matching regular non-symlink file делают
oracle независимым от production metadata layout. Это чувствительно к finding
1 и не использует DB, production payload или private helper.

Parser additions правильно адресуют обязательный full `startxref`/xref/`Prev`
parse: positive incremental revision, impossible terminal offset и out-of-file
classic entry independently distinguish valid chain from structural
corruption. Escaped PDF name отдельно требует lexical `#53` decoding.

Production-boundary test вызывает публичные constructors/factory names и
агрегирует fail-closed path, mode, symlink, protected-chain, marker,
disjointness, injected-clock/fault, collision и no-mutation observations.
Random temp roots изолированы; every run removes only its generated control
tree in `finally`. Commit diff changes only executable tests/support and
append-only RED evidence; production, OpenSpec tasks and domain contracts are
unchanged.

Literal reproduction on the reviewed artifacts:

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

All five changed PHP artifacts pass `php -l`; `git diff --check
c9714ec^ c9714ec` exits `0`.

## Blocking findings

### 1. Production factory oracle is existence-only

`assignment_order_original_production_boundary_001_test.php` records a failure
only when `ProductionAssignmentOrderOriginalFactory` does not exist. An empty
class, a class without the approved `create(mysqli, config): application`
signature, or a factory returning verifier/fake/incomplete bindings satisfies
`class_exists()` and removes this finding without making production
construction possible. Thus the test does not catch the plausible regression
identified by Gate 5 finding 7 and does not exercise the approved public
construction seam.

The corrected RED MUST invoke `ProductionAssignmentOrderOriginalFactory::create`
with isolated real prerequisites and prove a usable
`AssignmentOrderOriginalApplication` is returned with production-only
bindings, while preserving no runtime DDL and cleaning only task-owned
resources. If full DB construction is deliberately covered by another exact
reviewed test, this test must cite and jointly execute that oracle rather than
accepting class existence.

### 2. Indirect-action fixture is not sensitive to reference traversal

`AssignmentOrderOriginalPdfCorpus::indirectActiveAction()` puts forbidden
`/OpenAction` directly in the Catalog. Any implementation that merely decodes
and scans dictionary names can reject it without following `4 0 R`; conversely
the current direct forbidden-name check already makes the asserted result
independent of whether the referenced action dictionary is traversed. The
label says “reference traversal”, but the fixture cannot prove it.

The corrected fixture MUST keep the directly reachable dictionary key itself
passive/allowed and place the forbidden action/key only behind an indirect
reference in a structurally valid reachable object location covered by the v54
algorithm, or split the assertion so each required evasion mechanism has an
independent mutation/sensitivity proof. Expected `UNSAFE_PDF` must then depend
on graph traversal rather than on a direct `/OpenAction` token.

## Non-blocking observations

- The restart probe has no explicit timeout, but it executes a fixed local PHP
  reader with no external resource and closes/reaps its child; this is
  deterministic enough for this bounded RED.
- Invalid private-root and safe-log constructor cases accept any thrown type.
  The v54 contract fixes fail-closed validation but does not name a public
  exception for those two production adapters, so this is acceptable. A valid
  construction case should nevertheless accompany the corrected production
  factory invocation to prevent an always-throw implementation from satisfying
  the negative path assertions.
- Exact immutable-byte count `1` is consistent with one content-addressed
  finalized content identity and prevents a metadata-only or duplicate byte
  publication from passing.

## Required correction

1. Replace the production-factory `class_exists` oracle with a real public-seam
   construction/use assertion (and a positive valid path/safe-log case).
2. Make the indirect-active fixture sensitive specifically to bounded reference
   traversal, without a directly forbidden key deciding the same result.
3. Capture fresh intended RED transcripts and exact hashes append-only, then
   obtain a fresh independent Gate 3. Gate 4 parser/storage/boundary correction
   remains forbidden on this reviewed batch.
