# Pending Gate 1 prefix reconciliation rereview

- Review date: `2026-09-02`
- Reviewer scope: fresh independent rereview after correction of `PFX-G1-01`
  in `WORKFORCE-CANONICAL-RUNNER-001` section 6, plus consistency of the four
  pending Gate 1 artifacts
- Authority checked:
  `docs/operations/catalogue-prefix-ceiling-reconciliation.md`
- Prior review checked:
  `docs/operations/pending-prefix-gate1-reconciliation-review.md`
- Verdict: **READY_FOR_FRESH_OWNER_REVIEW_WHEN_PREDECESSORS_LAND**

## Closure of PFX-G1-01

`WORKFORCE-CANONICAL-RUNNER-001` section 6 now contains executable cases through
the public CLI which independently require:

1. exact 25-byte ASCII `processTablePrefix` success through the complete v1-v5
   runner, with clean success and a separate assertion that every derived
   catalogue identifier is at most 64 bytes;
2. otherwise-valid exact 26-byte input to return exit `64`, exact
   `CONFIGURATION_INVALID` stdout and empty stderr, while a connection observer
   proves zero DB connection/access and ledger/schema/rows/ambient objects stay
   unchanged;
3. the direct `BitrixWorkforceHistorySchemaMigration` verifier to retain its
   independently approved family-local 37-byte success / 38-byte pre-DB
   rejection, explicitly without treating that domain as composed production
   configuration support.

This closes the only blocking finding from the prior review.

## Cross-artifact findings

- `IDENTITY-ACCESS-SCHEMA-001` requires the composed ASCII domain `0..25`,
  exact 25-byte success with derived-identifier checks, and exact 26-byte plus
  invalid-character rejection before DB access. Its separate 36-byte
  family-local arithmetic is explanatory only.
- `CHECKLIST-TEMPLATE-SCHEMA-001` requires the composed runner to reject length
  26 as `CONFIGURATION_INVALID`, exit `64`, before DB connection/access, and
  exercises 25 success / 26 rejection in its Gate 2 matrix. Its 29-byte
  family-local arithmetic is explicitly not composed configuration support.
- `INSPECTION-EVIDENCE-SCHEMA-001` inherits the composed `0..25` input, requires
  exact 25-byte success and 26-byte rejection by catalogue preflight before DB
  connection/access, and keeps its 30-byte family-local arithmetic distinct.
- `WORKFORCE-CANONICAL-RUNNER-001` now states and exercises the exact composed
  25/26 boundary while preserving direct workforce-family 37/38 behavior.
- Mentions of the historical 32-byte runner boundary and the previously
  accepted 27-32 range are expressly historical or rejected ranges. They do
  not reintroduce broader composed support.

## Gate guard

This rereview approves no executable specification and authorizes neither RED,
test edits nor implementation. The runner and identity artifacts still record
owner decision `PENDING`; checklist-template and inspection-evidence remain
`DRAFT — BLOCKED_PREDECESSORS`. Each must satisfy its recorded predecessor,
literal-version, fresh-review and explicit owner-approval conditions before
Gate 2.

