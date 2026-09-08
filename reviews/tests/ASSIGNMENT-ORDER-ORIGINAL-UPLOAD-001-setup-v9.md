# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v9

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v9`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `9f276e06b6fa0b77624c12e79fbe577cf874b3fb`
- GREEN-attempt FK-index-gap base: `dad4c425637c0100322ca913a2f88be68fea4e07`
- Prior Gate 3 v8: approved before the newly observed unavoidable InnoDB FK support-index gap
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`, `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli, prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, tests, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Findings

### Every FK has one exact leading-index route in the manifest

The seven-table manifest and FK oracle now agree with InnoDB's required local
index semantics. `requests.root_original_id`,
`requests.current_revision_id`, and `events.revision_id` receive exact
single-column non-unique support indexes because no previously approved key
starts with those columns. No redundant entry was added for the remaining
foreign keys:

- `revisions.previous_revision_id` is led by its unique key;
- `revisions.root_original_id` is led by the approved
  `(root_original_id,revision_number)` index and unique key;
- `events.root_original_id` is led by the approved
  `(root_original_id,revision_id,event_type)` unique key;
- `audits.request_id` is led by its approved composite unique key;
- `maintenance_audits.request_id` is led by its approved unique key.

Before the intentional missing-seam guard, the verifier walks every FK tuple
and requires at least one approved key whose first column is the FK local
column. It then creates live MariaDB probe tables and observes keys through the
same `information_schema.STATISTICS` normalizer used by the full migration
matrix. A real FK produces the expected exact `INDEX(parent_id)` plus primary
key. Missing, additional and wrong-column variants all remain unequal to that
oracle. The full post-guard matrix separately compares the exact complete key
list and exact FK list for every owned table, so an omitted FK, omitted support
index, redundant index, wrong local column, wrong target or wrong action cannot
be hidden by the preflight coverage assertion.

### Existing setup, fixture and cleanup coverage remains intact

No previously approved expectation was weakened or removed. The RED retains
live CHECK round trips and wrong-regex/operator sensitivity, nullable-default
normalization probes, exact seven-table names/properties/columns/keys/FKs/CHECKs,
clean and repeat outcomes, compatible leading partial and populated behavior,
combined gross/near conflicts, opaque-identity default/collation drift,
same-count wrong SHA-256 CHECK sensitivity, and pre/post snapshots proving
conflict paths perform no partial DDL or row mutation.

The fixture still derives literal projection hashes independently from exact
fictional credential-free user, role, grant, workforce, case, order,
composition, opening, task, checklist and decoy rows. It retains zero-original
facts, repeat idempotency, drift rollback, reverse byte-validated cleanup and
unrelated-state preservation. Seed and cleanup contention require a
server-observed `SERIALIZABLE` InnoDB wait from the exact child connection to
the exact blocking connection, with bounded socket/process/observer/transaction
cleanup.

All probe tables are inside the bounded `t_aoou_clean_<12 hex>` database and
all five disposable databases are dropped from the outer `finally`. An
independent post-run catalog query returned zero matching schemas. No
production system, secret, runtime consumer, private method or domain mutation
is used by the qualifying RED.

## Reproduced real-MariaDB RED and cleanup

Against MariaDB at `127.0.0.1:23306`:

```text
$ php -l tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema query for SCHEMA_NAME LIKE 't\\_aoou\\_%'
0

$ git diff --check
PASS (no output)
```

The test reaches line 149 only after the FK coverage walk and all four live key
probes pass. The failure is therefore the intended absent public migration seam,
not an FK-index oracle, MariaDB setup, fixture setup or cleanup failure. Gate 3
authorizes task 3.1 minimal setup implementation without changing the approved
expectations.

## Required changes

None.

## Exact reviewed SHA-256 inputs

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f4851a33f5bf56c6797c2586791798d8f16d5cfa347162fe84a5b0915cd93a9d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9e2c354069c3b62f5d65eea37dd0417b6bbd8ef78d804f870882beda537dbbc1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
7b87a611fe771fb5a602be4cd22587bcc517aaeacbe4f613fd3cd91f76eadb06  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
fc574aa8c5b76c0dd3d7a1794380b21b25d779c12dd92fef0e7e2f181ee7abad  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v8.md
3d3a8ba8b2b8dc0724f42c05495b5deb5690e0dc2e3c3d4c47c564dcefab5a6c  docs/operations/assignment-order-original-database-setup-green-attempt-fk-index-gap-2026-09-05.md
58e470931e45dfd456661b671fc37d7da0bd7c0095288c3e7a57ecfc31f518cf  docs/operations/assignment-order-original-database-setup-red-correction-v9-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
