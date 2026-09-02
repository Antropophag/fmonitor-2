# INSPECTION-ITEM-COMPLETE-001 — independent Gate 5 Spec review

Date: 2026-09-01  
Reviewer: `/root/item_code_spec` (independently tasked; did not author the
specification, tests, production implementation, RED evidence, or Gate 1/3
records)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Reviewed contract and scope

- Executable specification SHA-256:
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- OpenSpec delta SHA-256:
  `d3d2b90ef251e9fed550d23c666a4dd42b69eefcffc95e9036857bf2f949262c`.
- Owner approval SHA-256:
  `b78cfe90c90826d6185cf41a883213cf0643f9685cb9120bdd6dd82abfe6eb04`.
- Gate 3 records reviewed: in-memory v5, MariaDB v5, and HTTP v3.
- Production reviewed: `app/InspectionEvidence/`, the `item_completed` branch
  in `app/PilotHttp/ChecklistSync.php`, and checklist admission/mapping in
  `app/PilotHttp/PilotHttp.php`.
- Unrelated dirty-worktree changes were ignored.

The review specifically checked capability-only all-object scope,
receipt-time reauthorization (including replay), object-to-case resolution,
typed HTTP mappings, replay/conflict/concurrency ownership, only-item wiring,
HTTP admission, and absence of a legacy item-completion fallback.

## Findings

### SPEC-01 — `item_completed` can be rejected before the application seam

`ChecklistSync::accept` applies the legacy envelope/static-section guard before
dispatch (`app/PilotHttp/ChecklistSync.php:54-56`). Consequently an
`item_completed` command with malformed syntax, or a positive section not in
the legacy `SECTION_ITEMS` constant, returns the legacy
`{status, message}` response without resolving the case or calling
`InspectionRecording::completeItem`.

This conflicts with the approved contract in three observable ways:

1. receipt-time actor status and exact `inspection.item.complete` capability
   are required to precede command syntax for every receipt, but this path
   never performs the application reauthorization;
2. application syntax accepts any positive `sectionId`, with membership owned
   by the associated immutable template, while the adapter imposes a separate
   legacy static allow-list;
3. every deterministic `item_completed` result must have exactly
   `{status, revision}`, but the early result has `{status, message}`.

The reviewed HTTP test covers only an envelope that passes this pre-guard, so
its passing result does not exercise this contour. Move `item_completed`
dispatch ahead of legacy validation (leaving that validation for retained
legacy operation branches), and add the missing public-adapter regression
coverage without weakening the approved result/precedence expectations.

### SPEC-02 — production application does not establish canonical v8 compatibility

The public application promises `INSPECTION_SCHEMA_UNAVAILABLE` at precedence
step 3 when the landed canonical v8 family is absent or incompatible.
`InspectionEvidence::completeItem` delegates that decision to
`inspectionSchemaAvailable()` (`app/InspectionEvidence/InspectionEvidence.php:23-24`),
but the MariaDB implementation probes only selected columns from four tables
(`app/InspectionEvidence/MariaDbInspectionAuthorization.php:4`). It does not
run or reproduce the canonical v8 compatibility check and does not validate
columns/constraints subsequently required by the case, template, writer, and
reader adapters.

Thus a partially compatible schema can pass step 3 and fail later as a raw SQL
infrastructure exception instead of the specified typed deployment result.
The outer HTTP call to `ChecklistSync::ensureSchema()` does not close this gap:
the approved production application seam is public and independently callable,
and the MariaDB Gate 3 test uses that seam directly. Make the application-side
deployment check authoritative for the complete canonical v8 family (without
DDL or repair), and add a sensitivity test for an incompatible required schema
element outside the current four-table subset.

## Confirmed conforming behavior

- Authorization checks active user plus exact capability and does not conjunct
  current engineer assignment; actor and assigned engineer remain separate
  evidence facts.
- Valid first receipts and exact replays recheck current authorization;
  `deviceTime` is not used for authority.
- Object id is resolved through an explicit seam, unequal object/case ids are
  supported, zero maps to deterministic rejection, and ambiguity/failure takes
  the retryable infrastructure path.
- Application result mapping is correct for accepted, duplicate, conflict,
  deterministic rejection, and schema-unavailable results after the seam is
  reached.
- The new branch handles only `item_completed`; a valid non-item operation
  remains on the legacy path, and there is no legacy item-completion mutation
  fallback after dispatch.
- Existing-id replay/conflict precedes mutable case/template/crew checks after
  authorization, syntax, and deployment checks. The MariaDB transaction owns
  per-case revision locking and the reviewed overlap test fixes one accepted /
  one stale outcome.
- Factory composition uses caller-owned connections, validates the prefix
  before DB access, sets `utf8mb4`, injects the clock, and performs no DDL.

## Independent verification

The following current focused checks passed:

```text
PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring
PASS: INSPECTION-ITEM-COMPLETE-001 example A
PASS: INSPECTION-ITEM-COMPLETE-001 receipt-time authorization
PASS: INSPECTION-ITEM-COMPLETE-001 authorization before replay all
PASS: INSPECTION-ITEM-COMPLETE-001 exact replay after mutable changes
PASS: INSPECTION-ITEM-COMPLETE-001 conflict and stale revision
PASS: INSPECTION-ITEM-COMPLETE-001 installer normalization all
PASS: INSPECTION-ITEM-COMPLETE-001 precedence all
PASS: INSPECTION-ITEM-COMPLETE-001 stable typed rejections
PASS: InspectionEvidence SQL owner policy
ARCHITECTURE CHECK PASSED (7 rules)
```

These checks support the conforming behavior above but do not cover the two
blocking contours. Gate 5 Spec approval requires both findings to be closed and
independently rereviewed.
