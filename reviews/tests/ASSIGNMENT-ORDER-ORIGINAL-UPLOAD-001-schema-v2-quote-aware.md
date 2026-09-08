# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema v2 — quote-aware correction

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/schema_v2_case_gate3`
- Test author: not this reviewer
- Reviewed RED commit: `595f9ed5374842f3708070d43c8176abda916235`
- Triggering Gate 5: `0e9c96a25d2f6533c6051c5041ae40aa77108e7e`
- Reviewed production implementation: `e4ab2fc3ff3dd7a8612a7f3fd9afb0e604ab244f`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Gate 1: `390f5629cd1a39da7d39b414b0ca6ad0419d3234`
  (approval record `54e7cb2cbf6dc1ff2967b3161220a4e840ebe4db`)
- Verdict: **APPROVED**

The reviewer authored neither the specification, production implementation nor
the corrective executable test. This review covers only the committed test
amendment and its append-only RED evidence. The nine untracked partial
command-slice production files were excluded and untouched.

## Findings

No blocking finding remains.

### Contract traceability and public seam

V54 defines V4 as the exact set of four capability identifier strings, permits
only a normalized whole top-level `capability IN (...)` candidate, and requires
every other expression to conflict before DDL. The triggering Gate 5 makes the
missing classifier property explicit: syntax folding must be quote-aware and
must preserve every quoted literal byte.

The added `uppercase_literals` fixture keeps the complete accepted expression
shape and cardinality but changes two literal byte strings to
`ASSIGNMENT_ORDER.PREPARE` and `Construction_Control_Engineer`. It therefore
isolates literal exactness from grammar, membership count and candidate
discovery. The test invokes only the established public
`AssignmentOrderOriginalSchemaMigration::apply()` seam against a real isolated
MariaDB database; it does not expose or call the private classifier.

### Sensitivity and zero-DDL oracle

The existing exact-lowercase V4 control reaches `APPLIED`, publishes capability
last and then repeats `UNCHANGED`. The new otherwise-equivalent uppercase case
must instead return exact `CONFLICT` with only
`fm2_process_user_capabilities` affected. This pair detects precisely an
implementation that lowercases quoted bytes while still allowing legitimate
case/whitespace normalization outside literals.

Before the public call, the test captures every table's full `SHOW CREATE TABLE`
text and all rows in deterministic order. Its strict tuple additionally requires
that no original table exists. Consequently a classifier that creates, alters,
rewrites or publishes before returning cannot pass the zero-state-change oracle.
The reproduced implementation fails all four observables coherently: it returns
`APPLIED`, reports all seven original tables plus capability, leaves all seven
original tables present and changes the complete snapshot.

### RED validity, isolation and provenance

The RED reaches live MariaDB after the missing-prerequisite and exact-V4 controls
have already passed. It fails only at `uppercase_literals`, with the exact actual
result recorded in the evidence, so this is missing production behavior rather
than syntax, fixture, database or harness failure. Task-owned randomized database
names are grammar-validated and dropped in `finally`; an independent post-run
catalog probe found zero matching databases.

Commit scope is exactly one executable-test line and one evidence document.
`git diff --check` passes. Production hashes recorded in the evidence match the
reviewed tree, and the nine untracked command-slice files remain outside both the
commit and this review.

## Required changes

None. This corrective RED is approved for minimal Gate 4 implementation. Its
expected conflict, affected list, absence of original tables and byte-identical
snapshot must not be weakened. A fresh independent Gate 5 is still required.

## Verification evidence

```text
$ git rev-parse 595f9ed
595f9ed5374842f3708070d43c8176abda916235

$ git diff-tree --no-commit-id --name-status -r 595f9ed
A docs/operations/assignment-order-original-schema-v2-quote-aware-red-evidence-2026-09-05.md
M tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php

$ git diff --check 595f9ed^ 595f9ed
PASS (no output)

$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
Fatal error: Uncaught TestFailure: uppercase_literals capability conflicts before original DDL with zero state change.
Expected: [CONFLICT, ['fm2_process_user_capabilities'], [], true]
Actual: [APPLIED, [seven original tables, 'fm2_process_user_capabilities'],
         [seven original tables], false]
exit 255

$ independent information_schema post-run leak probe
schemas=0
```

## Exact reviewed hashes

```text
e418c4cf9e2d37e6bf9da799f6bdfc5b2faa0f79e123b2be62810dd614b4cb85  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
37792f743b2432e19fac83e8301a42cb527bbf4351b8040d34d7132be9bf1632  docs/operations/assignment-order-original-schema-v2-quote-aware-red-evidence-2026-09-05.md
72845e1fa73fed45dbb719d28538ea471ad202984ad7bb7cf633fa451d0d00c5  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
064b2ffe51a1582d850eaed665232b84f267eec9027eb31ad523d05486d45bbc  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
34b4a20be0005e4236ee3cdb04ca6fd41537ca3608ecef41ac5e9d91f151ddf5  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
150aaf096a6e3eb928f765f9d48500a44adc2dc10eb18c07fb3967cf8eafb41b  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-schema-v2-v2.md
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

This append-only review intentionally omits its own circular hash.
