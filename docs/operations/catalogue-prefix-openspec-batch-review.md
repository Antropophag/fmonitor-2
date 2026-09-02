# Independent catalogue-prefix OpenSpec batch review

Date: 2026-09-02  
Reviewer: fresh agent `prefix_openspec_batch_review_20260902j`

## Scope and independence

This was an independent planning review of the owner-authorized updates to:

- `canonicalize-classification-provenance-schema`;
- `canonicalize-inspection-planning-schema`;
- `canonicalize-migrated-evidence-schema`;
- `canonicalize-migration-quarantine-schema`.

For every change I resolved the current proposal, delta specification, design
and tasks through `openspec status --change <name> --json`, then read all four
existing artifacts. I compared them with the accepted
`docs/operations/catalogue-prefix-ceiling-reconciliation.md` and its fresh
`READY_FOR_PLANNING_UPDATES` rereview.

I did not author or edit the reviewed OpenSpec artifacts, executable specs,
production code, tests or operations status. This review approves planning
coherence only. It does not approve Gate 1, RED or implementation, and it does
not assign literal migration versions before their predecessors land.

## Common findings

All four changes now use the composed production-runner boundary consistently:

- valid ASCII process prefixes through exactly 25 bytes are supported;
- a valid-character 26-byte prefix, invalid characters and non-ASCII input are
  rejected as setup/configuration failure before DB connection/access;
- the rejected path cannot mutate schema, migration ledger, rows, counters or
  ambient objects;
- earlier wider arithmetic is retained only as direct-family explanatory
  evidence and is not presented as production configuration support;
- predecessor landing, literal version/order refresh and explicit executable
  Gate 1 approval remain mandatory before RED;
- the task sequences preserve independent RED/test review, minimal GREEN,
  regression/architecture verification and independent code review.

A search of the four complete artifact sets found no stale normative composed
runner ceiling of 27, 28, 29 or 32 bytes. References to `27/28` and `28/29` are
explicitly family-local evidence, while `26–32` describes namespaces the full
runner must reject before DB access.

## Per-change verdicts

### `canonicalize-classification-provenance-schema`

The proposal, delta spec, design and tasks consistently derive the 25-byte
ceiling from the 39-byte
`fm2_migration_classification_provenance` basename. The formerly weaker
pre-mutation clause is superseded normatively: the delta scenario now requires
26-byte/invalid rejection before DB connection/access. Symbolic ordering and
the separate taxonomy, legacy-active cutover and output-atomicity decisions are
preserved; runtime DDL removal is gated behind the full SDD/TDD sequence.

Verdict: `READY_WHEN_PREDECESSORS_LAND`.

### `canonicalize-inspection-planning-schema`

All four artifacts distinguish the composed 25/26 contract from the planning
family's 36-byte longest basename and direct-family 28/29 arithmetic. The
proposal/spec/design/tasks agree on two-table ownership, compatible partial
recovery, zero-mutation conflicts, populated preservation and DDL-free runtime
consumers. Workforce, identity/access, checklist-template and
inspection-evidence landing plus a literal version and owner-approved
`INSPECTION-PLANNING-SCHEMA-001` remain explicit prerequisites.

Verdict: `READY_WHEN_PREDECESSORS_LAND`.

### `canonicalize-migrated-evidence-schema`

All four artifacts consistently apply composed 25/26 before DB
connection/access while retaining 28/29 only as six-table family-local
identifier evidence. The proposal/spec/design/tasks agree on the 64-state
preflight, split-collation/JSON fingerprint, populated preservation, DDL-free
consumers and no import/backfill/rebuild or semantic promotion. The change
remains `BLOCKED_PREDECESSORS`; exact catalogue/version refresh and separately
approved executable Gate 1 precede RED.

Verdict: `READY_WHEN_PREDECESSORS_LAND`.

### `canonicalize-migration-quarantine-schema`

All four artifacts consistently apply composed 25/26 before DB
connection/access and preserve 27/28 solely as the three-table family's local
37-byte-basename arithmetic. They agree on read-all preflight, eight compatible
partial states, populated/counter preservation, DDL-free runtime paths and the
PILOT_ONLY/non-semantic boundary. Literal order/version and executable Gate 1
remain deferred until predecessors land; tasks do not authorize early RED or
implementation.

Verdict: `READY_WHEN_PREDECESSORS_LAND`.

## Verification evidence

Executed from `/home/antropophag/code/fmonitor-2` on 2026-09-02:

```text
$ openspec validate canonicalize-classification-provenance-schema --strict
Change 'canonicalize-classification-provenance-schema' is valid
$ openspec validate canonicalize-inspection-planning-schema --strict
Change 'canonicalize-inspection-planning-schema' is valid
$ openspec validate canonicalize-migrated-evidence-schema --strict
Change 'canonicalize-migrated-evidence-schema' is valid
$ openspec validate canonicalize-migration-quarantine-schema --strict
Change 'canonicalize-migration-quarantine-schema' is valid
$ git diff --check
(no output; exit 0)
$ make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)
```

## Overall verdict

`READY_WHEN_PREDECESSORS_LAND`

No correction is required in this batch. Each change is coherent planning for
the accepted catalogue-wide ceiling, but none permits RED or implementation
until its named predecessors land and its executable Gate 1 is explicitly
approved through the mandatory delivery process.
