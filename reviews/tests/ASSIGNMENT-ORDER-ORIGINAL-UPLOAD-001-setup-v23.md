# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v23

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v23`
- Reviewed RED commit: `73ef81e08ae77dd370625128552ae04e9fd7bb25`
- Gap authority: Gate 5 v3 `12fca973ab0a9eb997607a63259df783aaf62c4f`
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Gate 1 authority: `3a1be3cef02e2826b0afc1e80cf0da5e531ba7d9`
- Scope: replacement setup fixture binary-identity tests for tasks 2.2/2.3
- Verdict: **APPROVED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
RED evidence, support oracle or production implementation. This append-only
review record is the only authored artifact.

## Independent assessment

The correction closes the byte-identity sensitivity gap identified by Gate 5
v3 without changing the public seam or an approved product expectation.

- The complete generated-field matrix now captures every fixture-owned row's
  non-identity field from the database with `HEX(CAST(column AS BINARY))`,
  requires its generated mutation to have different octets before exercising
  either public fixture call, and requires restoration of the exact original
  octets. Null, numeric, date, enum and utf8mb4 text fields remain covered by
  the existing type-directed mutation generator; identity columns remain the
  deliberate exclusions.
- Seven explicit utf8mb4 representatives independently cover trailing U+0020
  in user, assignment-order and installer text, case-only role and task drift,
  and both composed U+00E9 and decomposed U+0065 U+0301 accent drift in email
  fields. Each case first proves byte distinction rather than assuming it from
  its source literal.
- For every representative, seed and cleanup must both throw the fixed public
  `AssignmentOrderOriginalVerificationFixtureConflict`; full state snapshots
  before and after each call require zero DML. Exact octets are restored before
  the next case. The existing generated all-field matrix applies the same
  zero-DML assertions to both calls.
- Current production's ordinary MariaDB collation comparison accepts the first
  trailing-space user drift. The canonical RED therefore demonstrates that a
  plausible non-binary equality regression is caught at the intended fixture
  contract, after the database and predecessor setup have succeeded.
- The correction retains the previously approved clean/repeat/leading-partial/
  populated/conflict migration families, exact manifest and DDL properties,
  prerequisite and publication recovery matrices, deterministic fixture seed,
  partial and repeated cleanup, prefix/projection isolation, contention
  observers, bounded worker cleanup and failure redaction. Independent
  post-run inspection found neither owned schemas nor owned connections.

The OpenSpec task checkboxes accurately reopen Gate 3 and setup GREEN after the
Gate 5 finding; they do not alter the approved behavior. No specification,
support oracle or production file changed in the reviewed commit.

## Reproduced intended RED and isolation evidence

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: trailing-space user was collation-accepted by seed.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema SCHEMATA/PROCESSLIST query for t_aoou_%
0
0

$ git diff --check
PASS (no output)
```

The failure is the intended missing binary fixture comparison, not setup,
connectivity, worker lifecycle or cleanup failure.

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c439aabf1c1f9ec57b945290661d50900c567b738e34f10cdfd80852ce8c36d2  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
3480ea78ee679fd7ba9cd667bebaf5ddb7a71925eac318b42dbb22984753cd37  tests/Support/assignment_order_original_fixture_worker.php
0b9b54666530573c5e7bee48e833bd1e5501c7e17c6864fbafbc3391d0b32ea7  docs/operations/assignment-order-original-fixture-binary-collation-red-2026-09-05.md
79798a1a79aae370daa4154582e1ca5c034840558376766b5b71ac01cde26ce5  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v3.md
```

This record omits its own circular hash. Fresh Gate 3 is **APPROVED**. Task 3.1
may resume with only the minimal binary-comparison production correction; a
fresh independent Gate 5 remains mandatory afterward.
