# Independent Gate 1 amendment review — ASSIGNMENT-ORDER-APPLICATION-SCHEMA-001

- Verdict: **APPROVED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, tests or implementation.
- Date: 2026-09-07.
- Reviewed source commit: `e84a26ad0ac4991fbb5cb90c5a9d1637719d58a0`.
- Specification: version 0.2, SHA-256 `d08ece40babbedbd8b433f39a787a568da4ec699b1558a90862e0f3bfd728c6f`.
- Prior Gate 1 record: `ASSIGNMENT-ORDER-APPLICATION-SCHEMA-001-gate1.md`, `CHANGES_REQUESTED`, retained unchanged.
- Parent behavior: `ASSIGNMENT-ORDER-COMPOSITION-APPLY-001` v0.2, independently Gate 1 `APPROVED`.
- Public seam: `AssignmentOrderApplicationSchemaMigration::apply(...)` and `isReady(...)`.

## Amendment disposition

The sole blocking finding is resolved. The attempts table suffix is now `fm2_assignment_application_attempts`, 35 characters. With the unchanged accepted 25-character prefix, its physical name is 60 characters and the application header table is 58 characters; both fit MariaDB’s 64-character table identifier limit. All prefixed index, foreign-key and check symbol names remain within the limit, with the longest at 49 characters.

The rename changes no column, constraint, lifecycle or parent application/read behavior. References within the executable schema contract consistently identify the renamed attempts table or use unambiguous family-relative wording. The OpenSpec artifacts did not embed the old physical suffix and therefore remain aligned without semantic edits.

## Complete contract assessment

The two-table family supports the approved application interface with exact physical metadata. Application rows enforce per-case sequence, global accepted-request identity, process-event uniqueness, order/case ownership, same-case predecessor links and bounded valid snapshots. Attempt rows enforce safe status/reason/application combinations and deliberately omit actor/object/order foreign keys so healthy denials can be recorded without invented domain rows.

The migration lifecycle is deterministic and preservation-safe. It validates scalar and connection state, predecessor readiness and both target shapes before DDL under a database/prefix-scoped named lock. It permits only clean creation, exact empty-header interrupted resume, or a complete compatible no-op. Populated partial, malformed, reversed and incompatible families conflict without repair. Existing data, DDL and AUTO_INCREMENT state are preserved; runtime migrations, grants, version rows and legacy/product facts are outside the seam.

The observer remains a narrow optional instrumentation interface for bounded native phase coordination after real lock/DDL events. Lock acquisition, pre-owned lock refusal, release ownership/failure and observer failure have explicit outcomes. `isReady` is lock-free and read-only and requires both exact tables plus ready predecessors. Prefix 0 and 25, caller transaction preservation, native contention, partial DDL recovery and no-op preservation are all observable at the public seam.

Canonical runner registration and migration numbering remain explicitly mandatory downstream work after writer/opening integration, not an exception from launch integration. No baseline, production source, test, schema version, Bitrix endpoint, import, preview or remote state changes in this Gate 1 slice.

## OpenSpec evidence

Independent strict validation passes:

```text
openspec validate store-assignment-order-applications --strict
Change 'store-assignment-order-applications' is valid
```

The unchanged aligned OpenSpec hashes are:

```text
b1259165cdc46ecae1c57935b81b2bfec0de657bbfc398d2d592489f110fce90  openspec/changes/store-assignment-order-applications/proposal.md
dda2cca35f05a19480246cf546f342067e4be5a4ddbadaed0c48cb8537add101  openspec/changes/store-assignment-order-applications/design.md
5c38dda462b1ae50c7b0cd7c3f9e22e726c47ee95ae6df059ef186a2346c6e29  openspec/changes/store-assignment-order-applications/tasks.md
9e878f38772540cab81f96eb40bb4ba04b1cda53d20e147b562662d7751aab17  openspec/changes/store-assignment-order-applications/specs/pilot/assignment-order-application-storage/spec.md
```

No blocking ambiguity remains. Gate 1 v0.2 is approved. Native RED and independent Gate 3 must precede minimal schema implementation; canonical/frontier integration remains required downstream. No specification, OpenSpec artifact, test or implementation was changed by this reviewer; only this review record was added.
