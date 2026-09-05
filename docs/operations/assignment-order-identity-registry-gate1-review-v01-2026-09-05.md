# ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001 — independent technical Gate 1 review v01

- Date: `2026-09-05`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed repository HEAD: `7c9303dee000920ec48ec51de932013825fadd94`
- Reviewed executable specification SHA-256: `31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f`
- Scope: engine-only two-table metadata family, immutable historical receipt,
  frontier preservation, source capture, verification observer/snapshot,
  interruption/recovery and concurrency; no tests or implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the candidate specification, OpenSpec artifacts,
tests or production implementation. This append-only review record is the only
authored artifact.

## Determination

The specification is ready for Gate 2 at the exact reviewed bytes. It defines a
single production migration facade and a typed verification composition over the
same engine, with independently observable catalog, snapshot, phase and result
contracts. Input validation, caller-owned connection lifetime, caller transaction
rejection, fixed redacted infrastructure exception, schema-conflict result and
read-only completion behavior have one deterministic outcome each.

No new product behavior or owner decision is introduced. The migration preserves
existing internal order identity, case/version and prepared instant as metadata;
it creates no selection, registration, artifact, process or audit fact. Engine
completion is expressly insufficient for selection readiness, runtime registration
or mixed-writer operation. The source inventory demonstrates why all-writer
exclusion, allocator ownership, selection-family schema, original-reader handoff,
same-identity rendering and canonical registration remain separate gated release
dependencies.

## Technical assessment

### Schema and prefix constructibility

The two table shapes are complete at the catalog seam: ordered columns and exact
types/nullability/extras, character sets/collations, named keys, FK target/actions,
named CHECK semantics and absence of extra schema objects are normative. The
registry's declared indexes cover the FK leading column, preventing an unwanted
server-created support index. The corrected schema does not put the
`AUTO_INCREMENT` column in a CHECK expression. Positive/PHP-range enforcement is
instead required at migration preflight, future allocator write boundaries and
read integrity, consistent with the recorded MariaDB 11.4.7 isolated probe.

The literal length inventory recomputes correctly: table basenames are 31 and 32
bytes; the longest stated constraint basename is 28 bytes; with prefix 25 all
remain below MariaDB's 64-byte identifier limit. The named lock is 57 bytes.
Prefix 26 and invalid characters are rejected before database access. Prefix is
included in every non-primary explicit name and in the hash-derived database/family
lock identity.

### Receipt, transaction and DDL atomicity

The contract correctly separates MariaDB implicit-commit DDL from the atomic
backfill. Whole-family/source/capacity preflight precedes mutation. Compatible
missing tables and a monotonic frontier may remain after interruption as an exact
empty recoverable state. All registry rows and the singleton receipt are then
staged in one owned transaction and committed once; no row without a receipt is
adopted. Unknown commit outcome remains infrastructure failure, while a repeat
after an acknowledged or unacknowledged successful commit proves the immutable
receipt and returns `applied=false`.

The completed-family rule freezes only the historical subset at or below
`legacy_max_id`, including count, tuple hash, normalized prepared-instant hash,
source discriminator and allocated instant. Later identities above that boundary
do not rewrite the receipt. The current frontier must remain at least the preserved
frontier and above every current registry ID. The explicit exhaustion sentinel
allows integrity observation at `9223372036854775808` while forbidding a further
PHP-range allocation.

### Empty, populated, repeat and changed-source behavior

The mandatory matrix distinguishes absent family, either compatible empty partial
family, incompatible sibling, empty source, populated source with gaps, nonempty
registry without receipt, exact repeat, late legitimate rows and forbidden changes
inside the frozen historical boundary. It also fixes orphan, duplicate,
out-of-range and malformed-time rejection. Initial frontier is independently
defined as the maximum of legacy next ID, historical maximum plus one and an
existing empty registry frontier; overflow is a pre-DDL/DML conflict.

The source is captured before DDL, then read/locked and compared again inside the
owned transaction. A change across that boundary rolls back staged DML and reports
infrastructure failure without modifying the physical source. This is an
additional integrity check under the explicit stopped-writers precondition; the
named migration lock is not misrepresented as exclusion of an old application
writer.

### Recovery, concurrency and observable verification

Phase emission points are exact relative to successful lock acquisition, each
DDL, frontier establishment, transaction commit and lock release. An observer
failure maps to the same fixed exception after attempt-all owned cleanup. The
production facade binds an inert observer, so verification does not introduce an
environment, HTTP or production fault selector. Snapshot types and ordering are
fixed and the standard MariaDB catalog remains an independent schema/preservation
oracle.

The interruption and concurrency contract supplies bounded monotonic deadlines,
pipe barriers, termination/reaping, exact owned-resource cleanup and foreign-decoy
preservation. Same-prefix migrations serialize with an observable five-second
lock timeout; separate prefixes proceed independently. Release failure after commit
cannot become false success and cannot erase committed history. Caller connection
ownership remains with the caller in all paths.

## Independent expected-value checks

The example hashes recompute from the specified ASCII byte streams:

```text
tuple bytes:
2,4512,1\n
7,4513,3\n
SHA-256 8159e7f3e55b317c01056ec6c7172e2c9bca8a798c0bc38408b6b6df1d71be86

prepared bytes:
2,2026-08-27T09:30:00.000000Z\n
7,2026-08-28T10:15:00.000000Z\n
SHA-256 a5506e2a71f414d4667cc95d1446155f69d9156ecf87e51bf9d7ec485997be53

empty SHA-256:
e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
```

The `+03:00` source instant converts to exact UTC
`2026-08-27T09:30:00.000000Z`; the Z source remains
`2026-08-28T10:15:00.000000Z`. IDs are sorted numerically, independent of source
insertion order.

## Verification performed

```text
$ git rev-parse HEAD
7c9303dee000920ec48ec51de932013825fadd94

$ shasum -a 256 specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md
31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f

$ openspec validate canonicalize-assignment-order-identity-registry --strict
Change 'canonicalize-assignment-order-identity-registry' is valid
```

No product RED, existing-data write, implementation, canonical registration,
remote action, protected-test edit or safe-log mechanism was run or changed.

## Exact reviewed hashes

```text
cee5f61943c18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
678fcf994af15608f564b099a4f8bd917e09228c89894560cc522603ee89a5d4  docs/architecture/guardrails.md
b4887bbe1defd8ecc9ac9eb8463be06a0e1a27051e1cad49d9d5f0127b8356d2  specs/MIGRATION-PROCESS-001.md
5fb30cd498695623090be4c121cde3a5a0c00c4930d9444f3b39cf86d26cf09f  specs/PRODUCTION-MIGRATION-RUNNER-001.md
31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f  specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md
86896f3f16faf5a9f33276b8dc975cbb0d04c013044c80d7b444262927d1f299  docs/operations/selection-writer-reader-cutover-inventory-2026-09-05.md
55b0230f4dc518ac501ca332de75f577c0736bd6ee38972270c4f29510371b16  docs/operations/selection-auto-increment-check-correction-2026-09-05.md
7555aa06e53ea191a4fd7854f1fcf46ceb785a0f87b18154b84981bfe40bce5e  openspec/changes/canonicalize-assignment-order-identity-registry/.openspec.yaml
9ccbd26d387166e0b077994340f6e596e9e07303d75fe690d8782e3cc1f25bbe  openspec/changes/canonicalize-assignment-order-identity-registry/proposal.md
dcbc315d41416d5206ab0621834643e573a73fac975c6b6c14420621d329a46e  openspec/changes/canonicalize-assignment-order-identity-registry/design.md
7f37bdb874a5f2a84ccbc8d29aed01992de6c93a9072d0aac89d220767d73fa6  openspec/changes/canonicalize-assignment-order-identity-registry/tasks.md
385eb7c56a8f27c41e4bac6b530fe518c240618f88719d699e0ecae9cf793591  openspec/changes/canonicalize-assignment-order-identity-registry/specs/deployment/assignment-order-identity-registry/spec.md
```

This review record omits its own circular hash. Approval is limited to the exact
engine-only candidate. Gate 2 may now establish missing-behavior RED; this verdict
does not approve tests, GREEN, Gate 5, canonical registration or writer cutover.
