# INSTALLATION-COMPLETION-SCHEMA-001 — readiness-before-checklist RED v4

- Date: `2026-09-04`
- Test head: `918a567`
- Production changes: none

V3 session setup is now complete. The first current RED occurs in missing
completion schema after the expected unavailable responses: checklist rendering
mutates `fm2_checklist_revisions` and backfills
`fm2_checklist_operation_installers` before completion-schema readiness rejects
the request. The before/after full prefixed-family snapshot detects both tables.

Required production ordering is therefore explicit: completion readiness must
fail closed before checklist projection/backfill or any other DML. After this is
GREEN, the same reviewed test proceeds to plaintext page mapping, card local
identity, exact healthy checklist and PTO append. No expectation was changed.

Fresh independent Gate 3 review must approve the actual v4 RED before production
implementation.
