# CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.1

Status: `DRAFT / OWNER_APPROVAL_REQUIRED` for Gate 1. This is an explicitly
`PILOT_ONLY` characterization of the current rapid-pilot operator importer. It
does not approve production-linked test data, target import semantics, runtime
DDL, premium rules, quarantine lifecycle, authorization or concurrency.

Technical consistency: `READY_FOR_OWNER_REVIEW` on 2026-09-01 after read-only
review by fresh separately tasked agent `object_detail_gate1_consistency_v3`.
This is not product-owner approval.

## Actor and intent

A discovery/test agent needs a deterministic executable oracle for the current
serial object-detail projection before its schema ownership moves to canonical
migrations. The observed actor is an infrastructure migration operator with
separate source-read and target-write credentials. No FMonitor user command,
HTTP authorization, CSRF boundary or audit event exists in this slice.

## Public oracle seam

- Stable future verifier entry point:
  `php tests/Verification/characterize_object_detail_import_001_test.php`.
- Every behavioral action SHALL run the real child process
  `php rapid-pilot/import-production-object-details.php --captured-at=<value> --page-size=1 --apply`.
- The child SHALL receive a private active-generation manifest, a private
  synthetic legacy-source database, the real target generation guard and the
  literal local-pilot acknowledgement through environment inputs.
- The verifier SHALL NOT include the importer, call private functions, reproduce
  its DML as the behavioral action, or treat existing static token verifier
  output as execution evidence.
- Test-owned SQL is permitted only for isolated setup, independent observation
  and exact cleanup. It is not the import seam.

## Fixed worked fixture

All identifiers and technical values below are fictional test facts. Expected
values are fixed here before RED and SHALL NOT be copied from importer output.

- First capture: `2026-09-01T10:15:00+03:00`.
- Repeat capture: `2026-09-02T11:45:00+03:00`.
- Present object: `451301`; missing-source object: `451302`; transactional
  rollback probe inserted later in ordering: `451300`.
- Active target cases initially contain exactly `451301` and `451302` and are
  paged with literal page size `1`.
- Schema version: `technical-object-detail-v1`.
- Metadata in source is exactly:

| sysname | field id | type | source raw | expected display |
|---|---:|---:|---|---|
| `floors` | 101 | 1 | ` 12 ` | `12` |
| `weight` | 102 | 1 | ` 1000 ` | `1000` |
| `speed` | 103 | 1 | ` 1.6 ` | `1.6` |
| `pittype` | 104 | 4 | ` 7 ` | `Глухая` |
| `pitmaterial` | 105 | 4 | ` 9 ` | `Железобетон` |
| `paired` | 106 | 1 | ` 0 ` | `0` |

Type-4 dictionary contains `(104, 7, Глухая)` and
`(105, 9, Железобетон)`. Scalar fields have no dictionary. Every expected
provenance contains source table `fm_maintable`, its literal sysname, the field
id/type above, and either `fm_fields_values` plus the trimmed dictionary id or
two JSON nulls.

The exact canonical material JSON for object `451301` is:

```json
{"schemaVersion":"technical-object-detail-v1","objectId":451301,"fields":{"floors":{"raw":"12","display":"12","provenance":{"sourceTable":"fm_maintable","sourceColumn":"floors","fieldId":101,"fieldType":1,"dictionaryTable":null,"dictionaryId":null}},"weight":{"raw":"1000","display":"1000","provenance":{"sourceTable":"fm_maintable","sourceColumn":"weight","fieldId":102,"fieldType":1,"dictionaryTable":null,"dictionaryId":null}},"speed":{"raw":"1.6","display":"1.6","provenance":{"sourceTable":"fm_maintable","sourceColumn":"speed","fieldId":103,"fieldType":1,"dictionaryTable":null,"dictionaryId":null}},"pittype":{"raw":"7","display":"Глухая","provenance":{"sourceTable":"fm_maintable","sourceColumn":"pittype","fieldId":104,"fieldType":4,"dictionaryTable":"fm_fields_values","dictionaryId":"7"}},"pitmaterial":{"raw":"9","display":"Железобетон","provenance":{"sourceTable":"fm_maintable","sourceColumn":"pitmaterial","fieldId":105,"fieldType":4,"dictionaryTable":"fm_fields_values","dictionaryId":"9"}},"paired":{"raw":"0","display":"0","provenance":{"sourceTable":"fm_maintable","sourceColumn":"paired","fieldId":106,"fieldType":1,"dictionaryTable":null,"dictionaryId":null}}}}
```

Its independently fixed SHA-256 is
`5fbb37587f0bd1dff238fd1e97972b4e74d9ac4583c875961d9639e6022e0d15`.
The stored payload adds exactly top-level `contentSha256` with that value and
`capturedAt=2026-09-01T10:15:00+03:00` after the three material members.

Missing-source canonical material is exactly
`{"schemaVersion":"technical-object-detail-v1","objectId":451302,"code":"SOURCE_OBJECT_NOT_FOUND"}`;
its fixed SHA-256 is
`5f3d14bbdc7708430233092240bc82fe3ab3e28867ef1e4b7bc14fbf542e2b89`.

## Isolation, execution proof and cleanup

1. Caller SHALL supply exactly one
   `FMONITOR_OBJECT_DETAIL_VERIFY_RUN_TOKEN` of 12 lowercase hexadecimal
   characters and an exact repository-owned
   `FMONITOR_OBJECT_DETAIL_VERIFY_ARTIFACT_ROOT`. Missing, malformed, symlinked,
   non-directory or out-of-bound values are `SETUP_FAILURE`; `/tmp`, home root
   and fallback paths are forbidden.
2. The run derives target SQL prefix `odci_<token>_`, private source database
   `fm2_odci_<token>`, and exact artifact child `object-detail-<token>` below the
   validated root. It owns only the exact source tables, target fixture tables
   and artifact files enumerated by the Gate 2 test. Wildcard discovery or
   cleanup is forbidden.
3. Before mutation the test SHALL refuse any occupied exact owned SQL name,
   source database or artifact child. It SHALL NOT reuse, repair, truncate,
   rename, drop or inspect rows in an occupied namespace.
4. The test SHALL precreate the current exact details/quarantine tables and
   fingerprint every target fixture structure before each CLI call. Runtime
   `CREATE TABLE IF NOT EXISTS` remains observed debt, not approved ownership;
   this slice neither removes it nor accepts structural drift.
5. Each child execution SHALL leave independently observable process evidence:
   exact argv/exit status, stdout/stderr, target full-row snapshots and schema
   fingerprints. Exit status or a verifier-authored summary alone is
   insufficient; an echo-only verifier fails.
6. A fixed ambient artifact decoy and unrelated target-table fingerprint SHALL
   survive every successful and failing scenario byte-identically.
7. On success or failure the test SHALL reap each exact child, remove only its
   explicit owned names and prove none survives. Cleanup is bounded and
   idempotent; process-name scans, recursive parent deletion, SQL patterns and
   wildcard drops are forbidden.
8. The complete verifier SHALL run twice with different unoccupied tokens.
   Normalized stdout SHALL be byte-identical, stderr empty, all database facts
   independently re-proven before cleanup, and both runs leak no owned artifact.

Generated tokens, prefixes, database/table names, paths, process ids, credentials,
SQL and stack traces SHALL NOT enter normalized stdout.

## Clean accepted detail and missing-source quarantine

- **GIVEN** the fixed fixture and no target detail/quarantine rows
- **WHEN** the operator applies the import with the first capture time
- **THEN** child stdout is exactly the JSON object, in this key order,
  `{"mode":"apply","activeCases":2,"sourceRows":1,"missingSource":1,"schemaVersion":"technical-object-detail-v1","created":1,"alreadyPresent":0,"quarantineCreated":1,"quarantinePresent":0}`
- **AND** details contains exactly object `451301`, the fixed schema version/hash,
  exact payload described above and first capture time
- **AND** quarantine contains exactly object `451302`, code
  `SOURCE_OBJECT_NOT_FOUND`, the fixed schema version/hash and first capture time
- **AND** all six trimmed raw/display/provenance structures match the fixed table
  and no `lift_type` or seventh field exists
- **AND** structures, decoy, unrelated target facts and unowned artifacts do not
  change.

Stable milestone:

`OBJECT_DETAIL_IMPORT clean details=1 quarantine=1 fields=6 hashes=exact`

## Serial exact replay preserves original evidence

- **GIVEN** the exact two rows created above
- **WHEN** the same source material is applied serially with the repeat capture
- **THEN** child stdout is exactly
  `{"mode":"apply","activeCases":2,"sourceRows":1,"missingSource":1,"schemaVersion":"technical-object-detail-v1","created":0,"alreadyPresent":1,"quarantineCreated":0,"quarantinePresent":1}`
- **AND** every stored column and every raw payload byte of both original rows is
  unchanged, including the first capture time
- **AND** no new row, structure or artifact is created.

Stable milestone:

`OBJECT_DETAIL_IMPORT replay details_present=1 quarantine_present=1 mutations=0`

This is current serial hash-only behavior, not approval that hash-only integrity
or capture preservation is sufficient for the target system.

## Changed-detail conflict rolls back the whole target batch

- **GIVEN** the accepted rows above, a new active/source object `451300` with
  otherwise valid fixed fields, and changed `floors=13` source material for
  existing object `451301`
- **WHEN** the operator applies the import
- **THEN** the child exits non-zero and stderr contains stable category
  `DETAIL_PROJECTION_CONFLICT`
- **AND** the complete two-table target row snapshot remains byte-identical to
  before the call: the earlier-ordered pending object `451300` is absent and the
  original `451301`/`451302` rows are unchanged
- **AND** no new quarantine code, structural drift or unowned mutation occurs.

Stable milestone:

`OBJECT_DETAIL_IMPORT detail-conflict category=DETAIL_PROJECTION_CONFLICT mutations=0`

Exact PHP exception formatting, file paths and stack trace are not asserted.

## Source rejection occurs before target DML

Each scenario starts from the accepted two-row snapshot and restores source
fixtures independently.

### Incomplete required metadata

- **GIVEN** source metadata omits exactly `paired`
- **WHEN** the operator applies otherwise unchanged input
- **THEN** the child exits non-zero with stable category
  `SOURCE_METADATA_INCOMPLETE`
- **AND** complete target rows/structures, decoy and unowned facts are unchanged.

### Unknown type-4 dictionary id

- **GIVEN** object `451301` supplies trimmed `pittype=999`, absent from the
  dictionary
- **WHEN** the operator applies otherwise complete input
- **THEN** the child exits non-zero with stable category
  `SOURCE_DICTIONARY_VALUE_UNKNOWN`
- **AND** complete target rows/structures, decoy and unowned facts are unchanged.

Stable milestone:

`OBJECT_DETAIL_IMPORT source-rejections metadata=SOURCE_METADATA_INCOMPLETE dictionary=SOURCE_DICTIONARY_VALUE_UNKNOWN mutations=0`

## Rejection catalogue and characterization boundary

Repository evidence also identifies invalid/missing arguments, manifest or
generation guard failure, missing credentials, SQL/JSON failure,
`DETAIL_QUARANTINE_CONFLICT`, and invalid page/capture inputs as run-level
rejections. They are not converted into quarantine facts. Only the three stable
categories exercised above are pinned by this slice; unexercised rejection
ordering/message/rollback remains observation evidence, not a completed
acceptance assertion.

- `PILOT_ONLY`, characterized: six-field extraction; current provenance and
  canonical material; one row per object in each table; missing whole source row
  quarantine; serial exact-hash no-op; changed-detail transactional conflict;
  metadata/dictionary rejection; supplied capture string; current output counts.
- `UNKNOWN` and excluded: concurrent runs/absent-row race; present↔missing
  transitions; detail/quarantine coexistence precedence; refresh/upsert;
  quarantine resolution/history/retention; actor/run audit; target authorization;
  semantic/range validation; consumer hash verification; production cutover and
  privacy; premium meaning; additional fields including `lift_type`.
- Separate schema-ownership slice: exact production DDL, DDL-denied importer,
  missing/incompatible schema precondition and removal of runtime DDL.

GRILL-004 blocks only population/provenance of the first test-user contour. It
does not block these private fictional fixtures and this characterization does
not authorize copying production identifiers or data.

## Stable transcript

Normalized stdout SHALL contain the four milestone lines above in specification
order followed by exactly:

`CHARACTERIZATION_OK CHARACTERIZE-OBJECT-DETAIL-IMPORT-001`

No child raw JSON, generated value, secret or environment-specific value may be
added to normalized stdout. Exact spec/test/expected-transcript hashes are pinned
at Gate 3; verifier hash is pinned only at Gate 5.

## Failure classification and Gate 2 evidence

- `SETUP_FAILURE`, exit `2`: unavailable MariaDB/admin capability; invalid root
  or token; occupied namespace; fixture/manifest/generation construction failure;
  inability to execute/audit/reap/clean; or missing required PHP extension.
- Qualifying Gate 2 `RED`: a healthy isolated meta-test proves the executable
  verifier is absent or one exact assertion above is not implemented.
- `REGRESSION_FAILURE`, exit `1`: wrong child result/category; wrong payload,
  hash, count or mutation; structural/decoy damage; nondeterminism; secret leak;
  or owned-artifact leak after an implemented assertion.

Environment/setup failure and the importer's expected domain rejection are not
Gate 2 RED by themselves. Expected first focused command:

`php tests/Verification/characterize_object_detail_import_001_test.php`

## Done definition

The slice is done only after every mandatory gate in
`docs/development-process.md` completes:

1. this exact v0.1 contract receives explicit owner `APPROVED` for Gate 1;
2. focused intended RED is demonstrated for the missing executable oracle;
3. a fresh separately tasked test reviewer records `APPROVED`, pinning spec,
   test and expected-transcript hashes but not future verifier implementation;
4. minimal verifier/test fixture reaches GREEN through the real CLI, two clean
   runs, independent database evidence, isolation and bounded cleanup;
5. canonical characterization, architecture, lint, relevant regression and
   `make verify` introduce no new regression;
6. a different fresh code reviewer records `APPROVED` and pins verifier hash.

Done does not change importer, consumer, product behavior or production schema;
does not approve any excluded/UNKNOWN behavior; and does not authorize test-data
population. This draft completes task 1.1 only after consistency review; RED is
forbidden until task 1.2 owner approval is durably recorded.
