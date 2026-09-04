# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v14 capability migration recovery — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_capmigration_rereview`
- Reviewed commit: `08f1afd7c6dc4d9d8d9db30aa6d5af2d56b04cd2`
- Triggering Gate 5 review: `c40c0101f46cbbee0187ea48f569999c3a0c49f1`
- Constructibility gap: `811ee93eca9f57d92ec3ab2c0d203fac3fcc2f5d`
- Prior Gate 1 verdict: `cf27a95c88be60b870d76cad59828238dabdf70f`
- Scope: technical capability-migration recovery amendment and complete current
  executable specification/OpenSpec change; no tests or production approval
- Verdict: **APPROVED**

The reviewer did not author the reviewed specification, OpenSpec artifacts,
tests, fixture or production implementation. This append-only review is the
only authored artifact.

## Review result

V14 closes all four blocking v13 findings and makes the migration-recovery
expectations independently executable at the named public setup seam.

1. `AFTER_SCHEMA_TABLE_CREATED` is invoked with the exact logical table after
   every durable missing-table `CREATE`, before the next manifest member. The
   full-schema revalidation boundary remains separately observable as
   `AFTER_SCHEMA_REVALIDATED_BEFORE_CAPABILITIES(null)`. A Gate 2 observer can
   therefore stop after each manifest position and prove exact leading-partial
   recovery without depending on private SQL or constraint names.
2. Final publication has distinct pre-ALTER and post-ALTER phases. An ALTER or
   post-ALTER observer failure causes one fresh capability classification:
   durable exact V5 returns `APPLIED` and includes the capability table; exact
   V4 throws the fixed unavailable signal and retries publication; conflict or
   reread unavailability fails closed without more DDL. Thus ambiguous loss of
   the ALTER response leaves only full-exact-schema plus exact V4 or V5, and a
   retry classifies either state before mutation.
3. The technical exception is now literal and testable:
   `AssignmentOrderOriginalSchemaMigrationUnavailable`, message exactly
   `AssignmentOrderOriginalSchemaMigrationUnavailable`, code `0`, previous
   `null`.
4. The capability candidate is observably limited to a normalized top-level
   `capability IN (...)` expression referencing only `capability`; the
   engineer-position predicate is excluded, and multiple candidates conflict
   even if one is exact. Conflict output is the binary-sorted union of all and
   only incompatible original logical tables and
   `fm2_process_user_capabilities` iff capability classification conflicts.
   Exact V5 is unchanged and never appears in `affectedTables()`.

The normative order is coherent: complete zero-DDL preflight, missing leading
suffix creation, full seven-table fresh revalidation, pre-publication phase,
capability ALTER as the last DDL, post-publication phase and fresh V5 proof.
The verification observer is injectable only through the named verification
factory. Static production apply binds a no-op observer, and environment,
request, CLI and global runtime selection remain forbidden. The amendment does
not change roles, authorization meaning, upload/correction behavior,
composition, opening, HTTP behavior or any other product workflow.

## Gate disposition

Gate 1 for this capability-migration recovery amendment is approved. This
review does not approve tests or production and does not close the separate
fixture findings from `G5-SETUP-1`. Complete fixture-row preflight, non-case
drift, partial multi-row occupancy, byte-validated cleanup and all-or-nothing
sensitivity remain open Gate 2 work. Tasks 2.2, 2.3, 3.1 and 3.2 correctly
remain unchecked and still require demonstrated RED, fresh independent Gate 3,
minimal GREEN and fresh independent Gate 5.

## Verification

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (before this review record; no output)
```

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
a005be8716f867168842b944f4756fa52107ee723e72518f8fd66f1503da1fcf  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
4780c6f754712f054930e975fe392707ef0d70795147750234c0e335ff6f7725  docs/operations/assignment-order-original-setup-capability-publication-gate1-gap-2026-09-05.md
60405d86e7b2ffdd5c31048f247d6f1a30c77bfafe99f76daeaca034f1dfe293  docs/operations/pilot-assignment-order-original-capability-migration-gate1-review-v13-2026-09-05.md
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
```

The review record omits its own circular hash.
