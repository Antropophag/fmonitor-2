# Object-detail schema GRILL-004 planning rereview

- Review date: `2026-09-02`
- Reviewer task: fresh independent review after GRILL-004 and catalogue-prefix
  reconciliation
- Change: `canonicalize-object-detail-snapshot-schema`
- Independence: the reviewer did not author or edit the reviewed OpenSpec
  artifacts, executable specifications, tests, or production code.

## Exact reviewed artifacts

- `proposal.md` —
  `5365527203f120016792612c923279ccd2f6582275eda5dc526bf00957344f98`
- `design.md` —
  `bd05b802c02c4c8eb83e2f20f219041e6765c86cb6eadf0efc7aea4a5b6752b3`
- `tasks.md` —
  `2c5b1b6f102f57e6377f80581f4221c7af069e7c7649085718659f0d4dfc146a`
- `specs/deployment/canonical-object-detail-snapshot-schema/spec.md` —
  `e8dbec00f873e28ac686ce5c1e71134aecbc9fb777ab03c10e8d621c87360213`

## Sources and prior findings checked

The review compared all four artifacts with `PRODUCT.md`, `CONTEXT.md`,
`docs/development-process.md`, the object-detail evidence and prior reviews,
`docs/operations/test-user-data-reset-decision.md`, and the accepted
catalogue-prefix reconciliation. Historical records were treated as evidence,
not rewritten as current truth.

## Findings

No blocking defect was found.

1. The GRILL-004 decision is represented at the correct boundary. The canonical
   migration is data-free and source-independent. The first TEST-USER contour
   remains deterministic synthetic/native and excludes real personal data and
   sanitised legacy cutover. Literal object-detail rows, provenance, hashes and
   reset population semantics remain in separately gated seed/import behavior.
2. Schema ownership does not drift into product semantics. The package preserves
   the existing snapshot/quarantine payload and first-write/conflict behavior,
   existing rows, and consumer fail-closed behavior while excluding integrity,
   reconciliation, retention and storage redesign.
3. The later catalogue reconciliation is coherent in all four artifacts:
   composed production configuration accepts at most 25 ASCII bytes and rejects
   26 bytes, invalid characters and non-ASCII before DB connection/access.
   The object-detail-local 30-byte arithmetic is explanatory only.
4. Ordering remains symbolic. The next literal version and catalogue position
   are assigned only after actual predecessors land. The tasks preserve Gate 1,
   demonstrated RED, independent test review, minimal GREEN, regression and
   independent code review in order.
5. The importer becomes data-only only inside the later reviewed implementation
   cycle; this planning record authorizes neither runtime edits nor RED.

## Verification

- `openspec validate canonicalize-object-detail-snapshot-schema --strict` —
  PASS (`Change 'canonicalize-object-detail-snapshot-schema' is valid`).
- `git diff --check` — PASS (exit 0, no output).

## Verdict

**READY_WHEN_PREDECESSORS_LAND**

The planning package is coherent after GRILL-004 and prefix reconciliation.
Literal migration order, executable Gate 1 approval, RED and implementation
remain pending and are not authorized by this review.
