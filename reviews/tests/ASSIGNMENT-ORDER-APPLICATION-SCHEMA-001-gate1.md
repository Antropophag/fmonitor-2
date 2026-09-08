# Independent Gate 1 review — ASSIGNMENT-ORDER-APPLICATION-SCHEMA-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, tests or implementation.
- Date: 2026-09-07.
- Reviewed source commit: `5d75309ba83c539fc799415238b09774484b3ad3`.
- Specification: version 0.1, SHA-256 `3c1c828d6c66b228aaa467880376919d12e35086d793251b4bcd06d275ca6e5d`.
- Parent behavior: `ASSIGNMENT-ORDER-COMPOSITION-APPLY-001` v0.2, independently Gate 1 `APPROVED`.
- Public seam: `AssignmentOrderApplicationSchemaMigration::apply(...)` and `isReady(...)`.

## Material finding

1. **The required prefix-25 family cannot be created within MariaDB’s identifier limit.** The second table suffix `fm2_assignment_order_application_attempts` is 41 characters. With the contract’s accepted 25-character literal prefix, its physical table name is 66 characters, while MariaDB table identifiers are limited to 64 characters. Section “Independent executable examples” explicitly requires positive native coverage for both empty and 25-character prefixes, so this is not merely an untested edge. Shorten that table suffix to at most 39 characters, or reduce the accepted prefix consistently across the schema and parent application/reader interfaces. Update all exact names and OpenSpec artifacts together. The non-primary index, foreign-key and check symbol suffixes fit at prefix 25; their longest resulting names are 49 characters.

## Contract assessment otherwise

The schema module has a small deployment interface and keeps DDL, readiness proof, recovery and lock ownership behind it. Factory-free static `apply`/`isReady` outcomes are exact, scalar validation precedes SQL, and fixed exceptions avoid native error disclosure. The idle/autocommit/utf8mb4/default-collation prerequisites protect caller connection state; ambient transactions cannot be implicitly committed or rolled back.

The two-table structure supports the approved application contract. Application headers enforce per-case sequence uniqueness, global request identity, event uniqueness, order/case ownership, same-case predecessor links, UUID/hash/revision/kind constraints and bounded valid JSON snapshots. Attempts enforce safe terminal result shape and retain denials without actor/object/order foreign keys, which is necessary when those entities are absent or unauthorized. The application foreign key on successful/replayed attempts preserves the audit-to-fact link. Process events use the actual predecessor primary key `fm2_process_events.id`, and the identity registry exposes the required unique `(assignment_order_id,installation_case_id)` target.

Append-only preservation and recovery are concrete. All dependencies and both target shapes are inspected under the owned named lock before DDL. Only both-absent creation and an exact empty applications-table prefix may progress; populated partial, reversed, malformed, wrong-engine/collation, trigger, dependency and constraint states conflict without repair. A first-table DDL failure may be resumed only from the exact empty shape. Existing rows, definitions and AUTO_INCREMENT state remain unchanged, and no version row, grant or predecessor data is written.

The named-lock identity is bounded and prefix/database scoped. Pre-owned lock rejection, acquisition timeout, release ownership, release failure and observer exceptions have exact unavailable outcomes. The optional observer is a narrow coordination seam after native phase events; it cannot replace SQL, metadata or results, and null remains inert. `isReady` is read-only and lock-free, returning true only for the complete compatible family and predecessors.

Deferring canonical runner registration and migration numbering is stated as a downstream frontier/consumer gate rather than a launch exemption. That avoids claiming runtime readiness before the parent writer and opening integration exist, while preserving mandatory eventual registration. No baseline edit, legacy writer, Bitrix access, import, preview or remote mutation belongs to this slice.

## OpenSpec evidence

The proposal, design, tasks and delta consistently describe the same storage prerequisite and defer canonical registration explicitly. Independent strict validation passes:

```text
openspec validate store-assignment-order-applications --strict
Change 'store-assignment-order-applications' is valid
```

```text
b1259165cdc46ecae1c57935b81b2bfec0de657bbfc398d2d592489f110fce90  openspec/changes/store-assignment-order-applications/proposal.md
dda2cca35f05a19480246cf546f342067e4be5a4ddbadaed0c48cb8537add101  openspec/changes/store-assignment-order-applications/design.md
5c38dda462b1ae50c7b0cd7c3f9e22e726c47ee95ae6df059ef186a2346c6e29  openspec/changes/store-assignment-order-applications/tasks.md
9e878f38772540cab81f96eb40bb4ba04b1cda53d20e147b562662d7751aab17  openspec/changes/store-assignment-order-applications/specs/pilot/assignment-order-application-storage/spec.md
```

Return to Gate 1 to make the table name and accepted prefix physically compatible, then obtain a new independent specification review. No spec, OpenSpec artifact, test or implementation was changed; only this review record was added.
