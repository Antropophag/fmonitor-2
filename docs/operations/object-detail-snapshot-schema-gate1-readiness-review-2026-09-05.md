# Independent Gate 1 readiness review: object-detail snapshot schema draft v0.2

- Review date: `2026-09-05`
- Reviewed base: `8a5d8f3be8723d3659a1b3f6368db0b2aaba9d2d`
- Artifact: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.2
- Scope: readiness only; no importer execution, external data, RED, implementation,
  test/spec/planning/code edit, or Gate 1 approval
- Verdict: **CHANGES_REQUIRED**

## Findings

The public migration candidate is compatible with the real
`CanonicalMigrationApplication`: a two-argument class-string `apply` can be
registered at v12, the runner maps `SCHEMA_MIGRATION_CONFLICT` to exit 2 and
schema version 12, records v12 only when `applied === true`, maps
`DatabaseUnavailable` to 69, and maps other throwables to 70. The draft also
correctly describes the runner as a contiguous in-memory registry with no
persisted ledger. The inspected registry is exactly v1 through v11, so v12 is
the current candidate, not a reservation.

The result shapes, sorted physical table-name lists, exact-repeat reporting,
prefix boundary, and CLI JSON shapes are observable at the proposed seams.
The manifest covers ordinal columns, normalized bigint display width,
nullability/default/extra/generated state, the sole visible ascending BTREE
primary key, absence of secondary indexes/FKs/CHECKs, engine, charset and the
validated database-default collation. It deliberately excludes unstable SHOW
CREATE formatting/cardinality/estimates and preserves opaque rows and decoys.

Two concrete Gate 1 blockers remain:

1. Section 3 expressly leaves the deterministic post-create verification API
   open. Gate 1 cannot approve an acceptance statement whose observable test
   construction is still undecided. No test-only production hook is required
   for the important independently committed-creation boundary: prepare exact
   v1-v11 with an administrative principal, then invoke the public runner using
   an isolated principal granted metadata access and table-scoped `CREATE` only
   for the details table. The first CREATE can succeed and the quarantine CREATE
   deterministically fails; inspection with the administrative principal proves
   the exact partial state, and an ordinary privileged retry proves preservation
   and completion. A separately constructed exact partial-family fixture covers
   restart without simulating an exception. The spec should name this public
   construction (and its expected exit 69 under the stated SQL-to-
   `DatabaseUnavailable` contract), or remove any stronger claim that specifically
   requires failure after both CREATEs followed by unavailable verification.
2. The draft and approved OpenSpec design expressly leave concurrent-run
   acceptance open. The existing runner provides neither serialization nor a
   reliable way for two racing `CREATE IF NOT EXISTS` callers to distinguish who
   actually created a table, while `applied` is defined as true exactly when at
   least one table was created. Gate 1 therefore needs one explicit decision:
   define serialization and exact two-run public outcomes, or state that
   concurrent invocation is outside this slice and remove concurrency from the
   Gate 1 completion checklist/Done implications. It cannot remain an open item
   in an executable specification submitted for approval.

No other API, result, version, metadata, collation, preservation, prefix, or
failure-observable blocker was found. Importer DML characterization remains a
separate prerequisite for its own preservation claims and is not a blocker to
approving the data-free schema contract once the two points above are resolved.

## Exact inspected SHA-256 hashes

- `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md`:
  `64742a24f6c71c845e52bc0ffb582b2ea6dd800376287cdc05bcd357e87f57d3`
- `app/InstallationProcess/CanonicalMigrationApplication.php`:
  `5ca58842cf4b0f1e7107cb277afa349251b6f971b1ace2d1527c841f34348007`
- `bin/fmonitor2-migrate.php`:
  `e9caa610a952ba9bcbef28dd6e17996e3c83cc5a51c82f527e7b44a99625acf9`
- `docs/operations/object-detail-schema-evidence.md`:
  `cc8d66a09d156c7d5cead80ee5d40b36bcf52f2a5f0ed8cda78b1895989a97e2`
- dependency review:
  `49faf5ae3da3586e09c974caca5b71c30d41534a8226e31be6f856e865f4e87d`
- OpenSpec proposal/design/tasks/delta spec respectively:
  `b4e713d21c5b6e8e68ed97cf503471f10649b4ff3f3d90a73c848d7bb405ec0e`,
  `58217fdae5b0297d345c995ebe1c72f99e3bd314b5930dadb060944f3ac02f14`,
  `8bc64560855b2bd4d08e02c5c0c72f93c5ec42e2d3353a1f7b87788d175cce77`,
  `7733f3eb9efbcce8a73ddc475cd52d42969b0410aafd3fbdbdd9735e2a40e1d1`.

## Final verdict

**CHANGES_REQUIRED**. Resolve the deterministic interruption observable and the
concurrent-run scope/outcome before requesting Gate 1 review. This record does
not approve Gate 1.
