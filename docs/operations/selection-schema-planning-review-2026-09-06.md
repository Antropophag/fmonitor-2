# canonicalize-assignment-order-selection-schema — independent planning review

Дата: 2026-09-06.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed commit containing the new package: `f3a174c`.  
Repository HEAD during review: `e2726f9743fa5b6250151083e307f395250f33d8`.  
Verdict: **APPROVED_FOR_PLANNING**.

Это review только coherence OpenSpec planning package. Executable specification
`ASSIGNMENT-ORDER-SELECTION-SCHEMA-001` ещё не создан, task 1.1 открыт. Поэтому
вердикт не является technical Gate 1 approval, не разрешает RED/implementation и
не подтверждает selection или release readiness.

## Exact reviewed hashes

```text
c1d76a28f26a284d1d3a986fb1a97496433c46f517a087e359e99aaed670e492  openspec/changes/canonicalize-assignment-order-selection-schema/design.md
48f3ed088d3a76440cc6b4f80d0955971fd4a5962d1e95d939b9393f6ea8dc2e  openspec/changes/canonicalize-assignment-order-selection-schema/proposal.md
554118e0a0830c68ecb2c89f4b45d9e80d5d2b13c2b9f257e993f0837b7d604d  openspec/changes/canonicalize-assignment-order-selection-schema/specs/pilot/assignment-order-selection-schema/spec.md
5aecd1f39d6d4bb0adf62d0567d2ced17185d41f2b92f92ef2b5a0311c5f6a73  openspec/changes/canonicalize-assignment-order-selection-schema/tasks.md
5cb8a371a43356849b374356212689562a8ccba7db402bb05c46715b1df704b2  specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md
31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f  specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md
f8459a5472c98b93e094631c62d5ab191fe8fb33d869908147d989ca4ef5c207  docs/operations/selection-compatibility-next-package-review-2026-09-06.md
1946dceb03ce4a231504670e37173a1dba52670910bd321e86029be261aa20f9  docs/operations/selection-v08-counter-outcome-review-2026-09-05.md
4c0b90e2a273a408ce637bdc7de8ba8c6d895604fb5258715d09703827363064  app/InstallationProcess/AssignmentOrderIdentityRegistryMigration.php
```

## Planning coherence

### Five-table scope — PASS

The package consistently limits the standalone migration to the selection
header, members, requests, events and audits tables. The declared order
`selections → members → requests → events → audits` allows the future executable
contract to create referenced parents before children. No artifact/template,
physical order, effective-composition or ready-marker table is silently added.

This matches selection v0.8 sections 7–8 and leaves optional-render artifact
ownership to the separately ordered next package. The migration creates no
selection/domain facts.

### Complete registry prerequisite — PASS

Before the first selection DDL, the package requires both the registry's public
`isBackfillComplete` receipt proof and exact referenced registry metadata. It
forbids calling registry apply, repairing registry rows, advancing its frontier
or changing legacy facts. Missing, incomplete, inaccessible or incompatible
registry state is a fail-before-selection-DDL outcome to be made exact at Gate 1.

This correctly consumes the approved disabled registry as a prerequisite rather
than treating registry existence alone as ownership readiness.

### Populated read-only proof versus ownership readiness — PASS

A complete populated selection family may be accepted only after exact metadata
and data-coherence proof. Repeat then performs no DDL/DML and preserves rows,
hashes, counts and counters. A nonempty partial family, gap, incompatible shape
or malformed facts fails closed without repair, deletion, counter reset or
synthetic data.

The package explicitly states that this read-only schema/data proof does not
establish all-writer ownership, N-1 exclusion, original-reader dispatch,
optional rendering or startup readiness. Those remain later combined
compatibility gates. This distinction is consistent across proposal, design,
delta scenarios and tasks.

The future executable contract must define a bounded deterministic coherence
algorithm for a potentially populated family, including canonical ordering,
lossless ID/counter parsing, hashes/counts and what remains observable when the
proof cannot complete. That is correctly assigned to task 1.1 and is not an
unresolved planning contradiction.

### Prefix and MariaDB constructibility — PASS

The longest planned base name,
`fm2_assignment_order_selection_requests`, is 39 ASCII bytes; with the approved
25-byte prefix it is exactly MariaDB's 64-byte identifier ceiling. The package
requires prefix 0..25, short deterministic constraint/index names and exact
prefix-64 verification. Prefix 26 must fail before database access in the future
contract.

Event and audit IDs retain unsigned physical types without putting the forbidden
AUTO_INCREMENT column into a CHECK. Logical capacity/precommit/read-integrity
rules remain in selection v0.8. Task 1.1 still has to enumerate and byte-count
every table, constraint and index name and pin schema fingerprints at prefix 0
and 25.

### Recovery and lock boundaries — PASS

The plan acknowledges MariaDB's partial DDL commits. Recovery accepts only an
exact empty leading table prefix, creates the missing suffix, and never claims a
transactional rollback or drops previously created tables. Gaps, incompatible
metadata and nonempty partial states fail without mutation. A complete populated
family is handled only by read-only proof.

The lock is scoped to database/prefix, bounded, and permits independent prefixes.
Timeout, metadata/DDL denial, connection failure, observer failure and cleanup
failure remain unavailable rather than false readiness. Verification-only phase
observation is excluded from production configuration; no runtime caller is
introduced.

Task 1.1 must still pin the lock namespace, timeout, acquire/release outcomes,
observer phases, interruption points, exception shapes and attempt-all cleanup.
The current package correctly records these as executable-contract work.

### Disabled deployment boundary — PASS

The package does not register a canonical migration number, edit the canonical
runner, startup/bootstrap, application factories, HTTP routes, ready manifests or
legacy/selection allocators. Engine-only GREEN/Gate 5 would prove a disabled
component, not deploy it. Canonical registration is deferred until all reader,
writer, render, readiness and N-1 exclusion dependencies have their own gates and
the actual migration frontier is known.

Rollback after facts is forward-compatible only: history and registry frontier
cannot be deleted or lowered. A marker is explicitly rejected as proof that an
already running N-1 writer was stopped.

### Process and scope — PASS

Tasks preserve the required order: exact executable contract and independent
Gate 1; public-seam RED and independent Gate 3; minimal disabled GREEN and
independent Gate 5; only then later combined integration and full verification.
No checkbox incorrectly claims schema/spec/test implementation. `rapid-pilot`
receives no new adapter, and architecture baseline growth is prohibited.

The package introduces no product behavior or new product decision. It neither
authorizes mixed writers nor synthesizes physical orders for selection.

## Findings

No blocking planning-coherence finding.

Before technical Gate 1, task 1.1 must supply the promised exact five-table
metadata, named keys/constraints, fingerprints, public DTO/result/exception and
snapshot declarations, complete populated-data invariants, counter behavior,
lock/observer/interruption matrix and independently determined examples. This is
the planned next gate, not approval inferred from this record.

## Disposition

The four exact OpenSpec artifacts are **APPROVED_FOR_PLANNING**. They may guide
creation of `ASSIGNMENT-ORDER-SELECTION-SCHEMA-001`; the resulting executable
specification and any revised OpenSpec bytes require a fresh independent Gate 1
review at exact hashes before RED. Full selection Gate 1 and release readiness
remain blocked by the separately recorded compatibility packages.
