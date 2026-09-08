# Coverage review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 real DDL denial

- Reviewer: `/root/object_detail_schema_gate3` (independent; did not author test or production)
- Exact reviewed commit: `cfabd3904959995933634bc07e51f2519b648a50`
- Exact pre-v12 comparison base: `26bed9af6c70aefc690e89b5965246ea26628e1c`
- Owner approval: `e8f17b63a3c93e8f4be5664c309b435fde3318e9`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Test SHA-256: `19db7462e488b1eb0f17b59425260a40d42204a5363e9e6cf36fce9a41b86b7d`
- Evidence SHA-256: `087eeb6a441a88c6a423b81c4aa799f30ccd79967a367328a6dc0d8f649c29c8`
- Current migration SHA-256: `2bc47395cdaf61974c493907a84d8f8f25dc034e10c140a8fd851ca4845ace8a`
- Verdict: **APPROVED_AS_ADDED_COVERAGE**

## Classification

This is sound, independently reviewed coverage of a required real database
failure/retry behavior. It was added after the generic production behavior
already existed, so it is not classified as the historical demonstrated RED
that preceded that implementation and does not retroactively repair or rewrite
the original TDD sequence. The evidence labels its byte-identical pre-v12 run
as retrospective baseline evidence, accurately and without claiming false
forward history.

## Independent execution

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_ddl_denial_001_test.php
No syntax errors detected in tests/InstallationProcess/object_detail_snapshot_schema_ddl_denial_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_ddl_denial_001_test.php
PREREQUISITE PASS: isolated principal can CREATE only the exact details table
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 real DDL-denial partial recovery
exit 0
```

A fresh administrative cleanup inventory returned exactly
`{"schemas":[],"users":[]}` for the test's `fm2_ods_denial_%` databases and
`ods_denial_%` users. `git diff cfabd39^ cfabd39 --check` passed.

The durable evidence also pins the same test SHA-256 on detached exact pre-v12
base `26bed9af...`. It records that the real privilege prerequisite passed
before the byte-identical test failed on the absent public v12 seam with exit
255. That comparison establishes sensitivity to the missing owner, but remains
retrospective evidence rather than an assertion that this test historically
preceded implementation.

## Findings

The fixture is real and independently observable:

- database and user names share a cryptographically random token, and cleanup
  drops only resources whose exact CREATE succeeded;
- the limited connection proves its `CURRENT_USER`, differs from the
  administrator connection, and has database-wide SELECT plus exactly one
  table-level CREATE privilege for the details table;
- the exact grant inventory is asserted before invoking the public seam, so the
  absent quarantine CREATE privilege is a fixture prerequisite rather than an
  inferred condition;
- the established base corpus requires privileged clean creation of both
  members in one call, while this test's first call durably creates details and
  returns typed `DatabaseUnavailable` with quarantine absent. Together these
  observations discriminate the native second-CREATE denial path from a
  generic one-table-per-call implementation;
- an independent administrator verifies the complete table inventory, empty
  durable details table, and free normative named lock while the limited caller
  remains connected;
- a fictional opaque row is inserted only after failure. Granting CREATE on the
  missing quarantine table makes ordinary retry return exactly that one created
  name, preserve every existing details-row byte, create empty quarantine,
  report complete compatibility, and make the next call an exact no-op;
- final lock freedom, connection closure, user removal, and database removal
  are asserted or structurally owned through nested cleanup.

The test uses no source import, production credentials, production database, or
runtime selector. Expected schemas, grants, results, sentinels, and lock name
are literal and independent of implementation internals.

## Authority boundary

Approval is limited to this added real DDL-denial/durable-partial/retry coverage.
It does not itself authorize or claim a new preimplementation Gate 2/3 sequence,
nor full migration Gate 5 or integration. Two-creator serialization and timeout,
different-namespace concurrency, remaining observer/composed-fixture review,
importer characterization and runtime no-DDL enforcement, full `make verify`,
OpenSpec completion, and final integration remain open.

## Required changes

None for this added coverage.
