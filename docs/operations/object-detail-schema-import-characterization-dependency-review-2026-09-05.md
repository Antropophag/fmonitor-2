# Object-detail schema / importer-characterization dependency review

- Review date: `2026-09-05`
- Reviewer role: independent, read-only dependency reviewer
- Scope: planning dependency only; no importer execution, real data, Gate approval,
  RED, implementation, or production/test/spec/planning edit
- Verdict: **READY_FOR_SCHEMA_SPEC_DRAFT**

## Question reviewed

Must the data-free canonical object-detail schema Gate 1 package wait for owner
approval and reviewed GREEN of `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001`, or may
the exact schema executable specification be prepared while importer behavior
approval remains a separate gate?

## Determination

The schema executable specification may be drafted now. The current dependency
in task 1.1 over-serializes two distinct kinds of evidence:

1. The accepted schema evidence already fixes the complete two-table family,
   exact source manifests, current DDL ownership, partial/interrupted states,
   collation provenance, prefix bounds, and row-preservation constraints.
   Neither table has an FK or row prerequisite. Canonical ownership is therefore
   data-free and must precede initializer, importer and consumer access.
2. The importer characterization exists to create a reviewed executable oracle
   for current serial DML behavior. Its pending owner approval and GREEN block
   claims that those observed import outcomes have been regression-proved; they
   do not make the DDL manifests or the no-runtime-DDL ownership obligation
   unknowable.

This separation is also consistent with the approved TEST-USER decision: the
first contour is synthetic/native and contains no production-source import or
personal data. The schema change remains empty; fixture population stays behind
its own Gate 1 contract.

Accordingly, a schema author may prepare an exact executable Gate 1 draft from
the accepted schema evidence and four coherent OpenSpec artifacts. Drafting is
not approval. The draft must keep importer behavior preservation referential and
conditional: it may require no schema mutation and fail-closed schema
preconditions now, but it must not present the pending PILOT_ONLY serial DML
characterization as owner-approved or reviewed GREEN.

## Exact permitted boundary

The schema draft may specify:

- exact empty manifests for `fm2_pilot_object_details` and
  `fm2_pilot_object_detail_quarantine`;
- clean creation, exact populated repeat, both exact-compatible partial-family
  recoveries, whole-family read-only conflict preflight, and byte-preservation;
- explicit validated collation, the composed 25-byte prefix acceptance and
  26-byte pre-DB rejection boundary, and namespace/decoy isolation;
- canonical migration ownership before initializer/importer/consumers;
- removal of both importer `CREATE TABLE IF NOT EXISTS` statements, no target
  DDL in dry-run or apply, and absent/incompatible schema failing closed before
  target DML;
- no seed rows, source reads, FK/CHECK/JSON/exclusivity redesign, reconciliation,
  or production cutover.

The verdict does **not** permit:

- marking schema task 1.1 or 1.2 complete, assigning a literal migration version
  without a fresh landed-catalogue read, or recording owner Gate 1 approval;
- beginning schema or importer RED, tests, production changes, or migration
  registration;
- treating clean import, six-field projection, serial replay, conflict
  atomicity, quarantine, dictionary/metadata rejection, or cleanup behavior as
  an approved/implemented oracle before the characterization slice completes
  its own Gates 1–5;
- using the production importer, external/real data, or embedding object-detail
  fixture rows in this ownership slice.

Before schema Gate 2 can rely on preservation of current importer DML outcomes,
`CHARACTERIZE-OBJECT-DETAIL-IMPORT-001` must have the necessary owner-approved
contract and reviewed executable evidence. If Gate 1 approval of the schema
draft is sought earlier, that approval must explicitly exclude unproved DML
outcome claims and authorize only the independently fixed ownership/DDL
contract. Any later normative incorporation of characterization results changes
the schema executable-spec hash and requires fresh Gate 1 review/approval.

## Process reasoning

`docs/development-process.md` requires an approved executable specification
before RED and hash-specific re-review after normative change. It does not
require all regression harnesses to be GREEN before an independently knowable
specification is drafted. The OpenSpec planning review likewise concluded that
GRILL-004 blocks population/provenance choices rather than the data-free
ownership plan. Its `READY_WHEN_PREDECESSORS_LAND` verdict did not elevate the
importer characterization to a schema-data dependency.

Task 1.1's statement that the literal version/order remains unassigned until
the importer oracle lands remains a safe scheduling rule, but it is not a reason
to withhold an unapproved schema executable-spec draft. Version assignment and
catalogue insertion must still use the actual landed frontier at the time they
are proposed.

## Exact inspected SHA-256 hashes

### Governing product/process records

- `AGENTS.md`: `cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8`
- `PRODUCT.md`: `9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf`
- `CONTEXT.md`: `3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09`
- `docs/development-process.md`: `a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504`
- `docs/fmonitor-2-pilot-spec.md`: `25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb`
- `docs/fmonitor-2-pilot-data-model.md`: `10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d`

### Canonical schema OpenSpec and evidence

- `proposal.md`: `5365527203f120016792612c923279ccd2f6582275eda5dc526bf00957344f98`
- `design.md`: `bd05b802c02c4c8eb83e2f20f219041e6765c86cb6eadf0efc7aea4a5b6752b3`
- delta `spec.md`: `e8dbec00f873e28ac686ce5c1e71134aecbc9fb777ab03c10e8d621c87360213`
- `tasks.md`: `103444e1037638b7677e07a78a6f3124b7f5105ccab77cb6abfae28289a5f441`
- schema evidence: `cc8d66a09d156c7d5cead80ee5d40b36bcf52f2a5f0ed8cda78b1895989a97e2`
- first review: `db6b829359572f18b73f1e680e03d431dce2b430e88e7f3c18e1c5287ffe0cea`
- first rereview: `195c3677732e54c44983698b1e8fcf7c65a6360a6d7d14345eb41602de381665`
- accepted rereview v2: `eacdfd1fb0f3826cde601c84b93c5e748fd880d61cf1407e2323ee5634d62b5d`
- OpenSpec update review: `c107e706c63e78f11c12f2d5a8fc4216728660c23e65c6081bf331c2629404ad`

### Import characterization and TEST-USER decision

- characterization delta spec: `d09ec281200c2c4522ead5714a1c879a592f3af42c8482cd4ba606900acc9d62`
- characterization tasks: `08ddc907ede8b09b164412feb32e7d7f96c904d2d6c173e8ee3f93c400f99790`
- data/reset owner decision: `a3ea053acc9c7ae50a9a96ac0e3e8125f91e2d1944c336027ce1fa40a9f84992`
- decision review: `6d1d9aee43d3a13eb07c477ac47e593816e54fca7310ecb0e32fd173340ba9d1`
- accepted decision rereview: `e3876b84abc6dd47bc85ea190bfb96af5c9543c2f531e520ecb84fe42d405d5f`

## Final verdict

**READY_FOR_SCHEMA_SPEC_DRAFT**, within the limits above. Importer behavior
approval remains a separate prerequisite for claiming or testing preserved DML
semantics, not a prerequisite for writing the exact data-free schema ownership
contract.
