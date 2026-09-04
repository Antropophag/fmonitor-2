# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 5 setup review v1

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_dbsetup_gate5`
- Reviewed implementation commit: `32dd3151941f1198e0fdd6ce5ee8ee9e1b851abd`
- Approved test commit: `5ebf9be5700f7addd83cf7a2705f851c477f2ce5`
- Fresh Gate 3 authority: `47194340cad8a171d2594bdf09294b314494e457`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author the specification, OpenSpec artifacts, approved
tests, support oracle, RED evidence, or reviewed implementation. This
append-only review record is the only authored artifact.

## Blocking findings

### G5-SETUP-1 — fixture silently accepts and later deletes non-case drift

The approved contract requires every occupied fixture identity to be compared
byte-for-byte before any DML, and requires cleanup to validate every owned row
byte-for-byte before deleting any of them. The implementation does neither.
`MariaDbAssignmentOrderOriginalVerificationFixture::run()` locks only one user
or task row and reads only `fm2_installation_cases.id=4512`; it compares only
that row's `process_state`. `seed()` then uses `INSERT IGNORE` for every row,
and `clean()` issues unconditional identity-bounded deletes.

Consequently, for example, an already occupied `fm2_pilot_users.user_id=31`
with a different name/email, a drifted role, role assignment, capability,
decoy case, order, installer snapshot, or task is silently retained by seed.
The call commits successfully instead of throwing
`AssignmentOrderOriginalVerificationFixtureConflict` before DML. Cleanup then
deletes that foreign/drifted row without validating its bytes. A partially
occupied multi-row statement has the same defect: `INSERT IGNORE` can insert
the absent sibling while ignoring a conflicting sibling, violating the
preflight-all and all-or-nothing contract.

This is both a production defect and an approved-test sensitivity gap. The
test mutates only `fm2_installation_cases.id=4512.process_state`, exactly the
single field the implementation checks. It does not perturb any other owned
identity, nor a partial row-set in a multi-row insert, so the green result
cannot establish the normative every-row behavior. Correct the fixture with a
deterministic binary-order lock/read preflight of the complete exact identity
set, compare complete rows (including nullable fields), perform writes only
after all checks pass, and apply the same full preflight before cleanup. Extend
the Gate 2 test to prove at least a non-case drift and partial multi-row
occupancy; because the approved expectation changes, this restarts Gate 2 and
requires a fresh independent Gate 3.

### G5-SETUP-2 — capability constraint upgrade is accepted when incomplete

`AssignmentOrderOriginalSchemaMigration::caps()` returns immediately when the
first CHECK containing `capability IN (` merely contains
`assignment_order.original.upload`. It does not require
`assignment_order.original.correct`, does not compare the complete approved
capability set, and does not reject ambiguous/multiple capability CHECKs. A
schema whose constraint permits upload but omits correction is therefore
reported as successfully migrated/unchanged, although the correction grant
cannot be inserted. Conversely, an arbitrary superset containing upload is
accepted without proving it is the selected canonical frontier constraint.

Select and validate the exact predecessor constraint deterministically, then
replace it with the exact approved successor set; treat missing, ambiguous, or
near-equivalent unexpected constraints as a fail-closed conflict/unavailable
outcome. Add regression cases for upload-without-correct, an unexpected
superset, and multiple candidate CHECKs.

### G5-SETUP-3 — migration failure can publish a split capability/schema state

`apply()` alters the prerequisite capability CHECK before creating any missing
original tables. MariaDB DDL commits implicitly, and the method has no
compensation or durable migration-state protocol. If any later `CREATE TABLE`
fails (permissions, storage exhaustion, injected server failure, or a race),
the capability constraint remains upgraded and any earlier original tables
remain created, while the method throws an untyped database exception and
returns no authoritative affected set. This exposes capabilities for a schema
that was not fully installed and makes failure observability depend on a later
retry.

At minimum, order publication so capabilities cannot become usable before the
entire compatible original-table family exists and is revalidated, and define
a deterministic recovery/fail-closed result for unavoidable MariaDB
non-transactional DDL partials. Add a fault test between table creations and
between table completion/capability publication to prove that no usable
capability is exposed against an incomplete schema and that a retry converges
without data loss.

## Positive observations

- The seven-table manifest, exact columns/collations/defaults, key/FK set and
  CHECK equivalence pass the approved real-MariaDB verifier, including the
  quote-aware boolean canonicalization and FK support indexes.
- Clean, repeat, compatible leading partial, populated, and tested conflict
  paths pass; the fixture data is fictional and contains no credentials or
  original facts.
- Migration/fixture code is kept under the setup boundary; searches and the
  architecture check found no application, HTTP, worker, cron, or
  `rapid-pilot/` runtime invocation and no new runtime-DDL owner.
- The capability authorization regression remains green for the currently
  covered canonical predecessor.

These strengths do not offset the destructive fixture drift behavior or the
incomplete capability/migration failure handling.

## Reproduced verification

```text
$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/process_command_authorization_001_test.php
PASS: PROCESS-COMMAND-AUTHORIZATION-001

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output before this review record)
```

The green focused test is real but insufficiently sensitive to G5-SETUP-1 and
G5-SETUP-2, and it does not exercise G5-SETUP-3.

## Exact reviewed hashes

```text
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f4851a33f5bf56c6797c2586791798d8f16d5cfa347162fe84a5b0915cd93a9d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
1ff5aa983dd647d7ff74df5c275b92ad1d7b528dc2276ba5244399f562fd65b2  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9e2c354069c3b62f5d65eea37dd0417b6bbd8ef78d804f870882beda537dbbc1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
b3f14655d3c31ca372966d4a8231fbf5511646554a77c7f06e688922ec1becd3  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
e663d15fcee0e10b82f5df204986d9702eb5ad9bcc192b4fa403316ddbdd9ec3  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v16.md
27de384ba6e158810ef658174882cca48e7ed763ceba5cac6890a3e68870812b  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
75d1cc01f2e6d94504e9a266098de44ebcd5b2aa6d4ac743537cae75c519b016  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
7dcf210a033ed0c1a4723ab6b399eb22a2bfa33d8d921c53654c55824901fc5f  app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php
60b1b62516122f2e983483cc240382e6c0371cf93f267819b632f4dca9692a56  app/AssignmentOrderOriginal/MariaDbCheckCanonicalizer.php
9f4f195c9f3b57cc21b534a497eb64227256e6bde1d7d7d1247f119e022d8220  docs/operations/assignment-order-original-database-setup-green-2026-09-05.md
```

The review path omits its own circular hash. Gate 5 is not approved; task 3.2
must remain open. Correction of the test sensitivity belongs to Gate 2, then a
fresh independent Gate 3 and a new minimal GREEN/fresh Gate 5 are required.
