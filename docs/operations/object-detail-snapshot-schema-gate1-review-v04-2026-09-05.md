# OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 — independent Gate 1 rereview

- Review date: `2026-09-05`
- Reviewer role: fresh independent Gate 1 technical reviewer; did not author the
  candidate, OpenSpec artifacts, correction, or prior review
- Reviewed commit: `65fd86fd00b5b7d4df7f0537bd2699b991fc50e6`
- Scope: data-free executable schema contract and four OpenSpec artifacts only;
  no RED, tests, implementation, importer execution/DML, source data, registry
  mutation, or owner decision
- Verdict: **APPROVED**

## Review result

The v0.4 correction resolves the sole blocker from review commit
`3c7c628279fb019bd0f3f580a9c23e132615adfd`. Both normative PHP examples are
now syntactically constructible: the concrete migration methods have bodies,
and the verification composition declares the exact backed enum, observer
interface and public typed entrypoint. Both fenced PHP blocks parse with
`token_get_all(..., TOKEN_PARSE)`.

The public contract is sufficiently exact and observable for Gate 1. It fixes
the two result shapes and key order, sorted exact physical table names,
`InvalidArgumentException` message, read-only compatibility result, technical
`DatabaseUnavailable` outcome, and CLI exit/JSON/LF mappings. Clean creation,
exact repeat, both compatible partial directions, family-wide preflight
conflict, byte-preserving sentinels, namespace isolation, database-default
utf8mb4 collation, and the 25/26-byte composed prefix boundary all have
independently determined observable results.

The failure and concurrency acceptance is also constructible at the public
seams without a runtime selector:

- the table-scoped DDL principal makes the details CREATE durable and produces
  a real denial on quarantine; administrative inspection proves the exact
  partial state and an ordinary privileged retry creates only the missing table;
- the public verification observer closes the supplied test connection only
  after `QUARANTINE_CREATED`, so both CREATEs are durable but final inspection
  cannot yield success; a fresh connection proves the exact complete family and
  an ordinary retry returns `applied=false` with an empty list;
- the closed backed enum has exact `LOCK_ACQUIRED`, `DETAILS_CREATED`, and
  `QUARANTINE_CREATED` values, while the public observer interface gives the
  test a type-complete implementation seam;
- the bounded READY/RELEASE two-worker protocol proves same-family lock
  serialization: A creates two tables and B observes a no-op after acquire.
  Holding A beyond B's five-second acquisition timeout yields
  `DatabaseUnavailable` and no mutation by B; retry after release completes
  normally. Different database/prefix namespaces derive different lock names.

The lock remains held through preflight, independently committed CREATEs,
post-create verification and result formation, with exactly one release attempt
after successful acquisition. Acquisition timeout/NULL/query failure,
connection loss, observer failure, final inspection failure, and release
failure cannot publish success. Durable exact partial or complete state is
retained for fresh retry.

The proposal, design, tasks and delta specification remain coherent with this
contract. Strict validation reports
`Change 'canonicalize-object-detail-snapshot-schema' is valid`. They preserve
canonical schema ownership, source-free empty deployment, runtime/importer
no-DDL, restartability, exact conflicts, opaque row preservation and separately
approved population. No acceptance statement in this review promotes current
importer DML evidence into an approved behavior contract.

## Authority boundary

This is **technical Gate 1 approval only**. Task 1.2 separately requires an
explicit owner approval before Gate 2/RED; this review does not supply or infer
that approval. `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001` remains the gate for
importer serial extraction/replay/conflict acceptance and for changes to its
DML. No source import, fixture population, migration registration, version
reservation, RED, or implementation is authorized by this record.

Candidate version 12 remains conditional on the main reviewer's fresh registry
and operations-prerequisite checks. This rereview did not mutate or independently
take ownership of those operational checks.

## Exact reviewed SHA-256 hashes

### Governing documents

- `AGENTS.md`: `cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8`
- `PRODUCT.md`: `9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf`
- `CONTEXT.md`: `3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09`
- `docs/development-process.md`: `a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504`
- `docs/fmonitor-2-pilot-spec.md`: `25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb`
- `docs/fmonitor-2-pilot-data-model.md`: `10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d`

### Candidate and four OpenSpec artifacts

- `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4: `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- OpenSpec `proposal.md`: `765f5c1980d2be241ee1732b93f260196b35f5be24ad6a21543197ed6ad303f3`
- OpenSpec `design.md`: `71e4dd6bd50270c71eff2459bb45019e67b9cd206944bc5c4d2648c6f838e799`
- OpenSpec `tasks.md`: `0c9321443e825bc888eb94bf316160e679f6e4f14070b4acd8544c96a526ab5f`
- OpenSpec delta `spec.md`: `6790151d50dc9425d12e9d7e7dbf693827d6688c7a2ad052e08d5a93e784665a`

### Correction, prior reviews/evidence, and inspected public runner

- v0.4 correction evidence: `8e28209647e48779f313433e56a02707b240662071cf38ebe63a85154e9bc9f7`
- v0.3 independent review: `1d7bb10c644a85d03117366d3677e8b32302154f0d0e63621a18a4cc4b81454e`
- prior readiness review: `a611939d4cfc81ad146b0aa4d5179bc48bfcc4083623a682dd6c2c5d796aed81`
- dependency review: `49faf5ae3da3586e09c974caca5b71c30d41534a8226e31be6f856e865f4e87d`
- schema evidence: `cc8d66a09d156c7d5cead80ee5d40b36bcf52f2a5f0ed8cda78b1895989a97e2`
- importer behavior evidence: `ac29a675157c1f4bfbc2aa16e8ed3c579b6c4c66cf8ae1128d27548612a08a51`
- `app/InstallationProcess/CanonicalMigrationApplication.php`: `5ca58842cf4b0f1e7107cb277afa349251b6f971b1ace2d1527c841f34348007`
- `bin/fmonitor2-migrate.php`: `e9caa610a952ba9bcbef28dd6e17996e3c83cc5a51c82f527e7b44a99625acf9`

## Final verdict

**APPROVED** for exact commit
`65fd86fd00b5b7d4df7f0537bd2699b991fc50e6`. The v0.4 data-free schema
contract satisfies technical Gate 1 acceptance observability and fixes the
prior constructibility blocker. Explicit owner approval remains a separate
prerequisite before RED, and importer DML remains separately gated.
