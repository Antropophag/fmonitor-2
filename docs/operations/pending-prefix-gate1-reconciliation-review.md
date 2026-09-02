# Pending Gate 1 prefix reconciliation review

- Review date: `2026-09-02`
- Reviewer scope: independent consistency review of the superseding prefix
  edits in `IDENTITY-ACCESS-SCHEMA-001`, `CHECKLIST-TEMPLATE-SCHEMA-001`,
  `INSPECTION-EVIDENCE-SCHEMA-001`, and
  `WORKFORCE-CANONICAL-RUNNER-001`
- Authority checked:
  `docs/operations/catalogue-prefix-ceiling-reconciliation.md`
- Verdict: **CHANGES_REQUESTED**

## Blocking finding

### PFX-G1-01 — workforce runner has no executable 25/26 boundary case

`specs/WORKFORCE-CANONICAL-RUNNER-001.md` section 2 now states the correct
superseding composed-process contract: 25 ASCII bytes are accepted and 26
bytes are rejected before DB connection/access. However, section 6's mandatory
independent executable examples do not include that boundary at all.

This does not satisfy the reconciliation matrix requirement to supersede the
pending runner's *cases* with 25-byte success and 26-byte pre-DB rejection. Add
an independent executable example which proves, through the public CLI, that:

1. an exact 25-byte valid ASCII process prefix reaches the complete v1-v5
   runner successfully;
2. an otherwise-valid exact 26-byte process prefix returns the existing
   `CONFIGURATION_INVALID` result and exit `64` before DB connection/access;
3. the approved direct workforce-family boundary remains independently 37/38
   and is not interpreted as composed production configuration support.

No RED or implementation work is authorized by this requested planning edit.

## Checks without findings

- `IDENTITY-ACCESS-SCHEMA-001` consistently uses the composed 25-byte maximum,
  exercises 25 success / 26 rejection before DB connection/access, preserves
  its 36-byte family-local arithmetic as explanatory evidence, retains the
  historical approved 32-byte contract as history, and remains pending owner
  approval with Gate 2 forbidden.
- `CHECKLIST-TEMPLATE-SCHEMA-001` consistently uses the composed 25-byte
  maximum, exercises 25/26 at both migration and canonical-runner seams,
  preserves the correct family-local 29-byte arithmetic, and remains
  `DRAFT — BLOCKED_PREDECESSORS` without RED authorization.
- `INSPECTION-EVIDENCE-SCHEMA-001` consistently inherits the composed 25-byte
  maximum and requires 26-byte rejection by the runner before DB
  connection/access. Its 34-byte longest basename and 30-byte family-local
  arithmetic are internally correct and do not broaden composed support. It
  remains `DRAFT — BLOCKED_PREDECESSORS`, owner decision `PENDING`, and forbids
  Gate 2 before predecessors, fresh review, and explicit approval.
- `WORKFORCE-CANONICAL-RUNNER-001` preserves the approved direct-family 37-byte
  acceptance / 38-byte rejection contract and explicitly distinguishes it from
  composed support. Its status and owner record remain pending, and section 8
  still forbids Gate 2/implementation before explicit approval.
- No reviewed artifact contains a stale normative composed ceiling of 27–32
  bytes. Remaining mentions of 27–32 are historical or explicitly rejected
  ranges, not supported composed configuration.
- No artifact grants Gate 1 approval, marks an owner decision approved, or
  authorizes RED/test edits or implementation as a consequence of this
  reconciliation.

## Required next review

After PFX-G1-01 is corrected, assign a fresh independent reviewer. The eligible
verdict is then `READY_FOR_FRESH_OWNER_REVIEW_WHEN_PREDECESSORS_LAND` only if
the executable boundary coverage is present and the approval/RED guards remain
unchanged.
