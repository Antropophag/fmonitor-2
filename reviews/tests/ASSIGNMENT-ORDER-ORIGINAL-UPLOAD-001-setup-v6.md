# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v6

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v6`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `8612ea4d8ab6e8911cfba9156e8be0ac63a162f1`
- GREEN-attempt regex-gap base: `ebc6cd0f351fea8efce75a7f9e7134d38ecb3eeb`
- Prior Gate 3 v5: approved before the newly observed MariaDB SQL-literal round-trip gap
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`, `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli, prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, tests, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Findings

### MariaDB roots CHECK round trip is now executable and sensitive

Before the intentional missing-class guard, the test executes the support
oracle's exact `Contract::rootsDdl('probe_')` against live MariaDB, reads the
three roots constraints through the table-scoped `CHECK_CONSTRAINTS` observer,
sorts the independently declared `Contract::checks()` roots oracle, and demands
exact equality. This directly closes the gap observed at
`ebc6cd0f351fea8efce75a7f9e7134d38ecb3eeb`: canonical DDL must survive the
same server serialization and observation path later used for production.

The added normalization is narrow. After the already reviewed lower-case,
whitespace and backtick normalization, one literal `str_replace` maps only the
exact MariaDB-doubled SQL-literal text `'[[:cntrl:]/\\\\]'` to the approved
single regex representation `'[[:cntrl:]/\\]'`. It does not rewrite an
operand, operator, positive `REGEXP`, arbitrary quoted content, a different
character class, or a missing-backslash pattern. The separately retained
rewrite of canonical MariaDB `!(operand REGEXP 'pattern')` to approved
`operand NOT REGEXP 'pattern'` likewise leaves operand and pattern bytes intact.

Sensitivity is established twice before RED qualification. The direct
normalizer assertions still accept the approved negated pattern and reject the
same expression without its backslash. More importantly, a second roots table
is built from the exact canonical DDL with only that approved pattern replaced
by `[[:cntrl:]/]`; the mutation is asserted effective, its CHECK count stays
the same by construction, and its live-MariaDB observed CHECK set must remain
different from the approved oracle. Thus a missing or wrong backslash cannot
be hidden by the normalization.

### Existing setup coverage remains intact

No previously approved test or support-oracle assertion was removed. After the
new preflight, the test still covers the exact seven-table manifest; ordered
columns including charset, collation, defaults and extras; engine/table
collation; keys and ordered columns; foreign-key targets/actions; complete
normalized CHECK sets and counts; clean, repeat, leading-compatible partial and
populated migration behavior; combined gross/near conflicts; opaque identity
collation/default sensitivity; wrong same-count SHA-256 CHECK sensitivity; and
pre/post snapshots proving conflict paths perform no partial DDL or row change.

Fixture coverage remains independently row-derived for order composition,
case, opening, task, checklist and decoy digests. It retains fictional users
without credentials, exact roles/grants/workforce/case/order/installers/task
facts, zero original facts, repeat idempotency, drift conflict rollback,
reverse bounded cleanup and unrelated-state preservation. Seed and cleanup
contention still require a server-observed `SERIALIZABLE` InnoDB lock wait from
the exact child connection to the exact parent connection before lock release,
with bounded sockets, observer, transaction and child cleanup.

All five disposable databases, including both new probe tables, remain within
the independently bounded `t_aoou_<axis>_<12 hex>` ownership namespace and are
dropped in the outer `finally`. The post-run independent query found no owned
database. No production system, secret, runtime consumer, private method or
domain write is used by the qualifying RED.

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

$ independent information_schema query for SCHEMA_NAME LIKE 't_aoou_%'
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_CLEANUP_OK
```

The test passes both new live-server preflight tables before reaching line 121,
so the captured failure is the intended absent public migration seam, not
regex serialization, database setup, fixture setup or cleanup. Gate 3 therefore
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
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
e0d423537434ea0e63bcfb25d3907d67b3de8a2634f1d2ed12980dee7717b819  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ef87fabadb4dbcaf256f021b067e3bc8b44380d9297580bc141c6c920f261d9f  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v5.md
7153fdafcb1f265211df18570242b3374c5f3e0b9c9f7284fe175a79dc7efca8  docs/operations/assignment-order-original-database-setup-green-attempt-regex-gap-2026-09-05.md
2293977b222577ebac693470ac3cf76a716d6828bed082496fb971817e46081a  docs/operations/assignment-order-original-database-setup-red-correction-v6-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
