# INSTALLATION-COMPLETION-SCHEMA-001 — session-config Gate 2 correction v3

- Date: `2026-09-04`
- Prior Gate 3 v2: historical after this fixture change
- Production changes: none

V2 reached configured E2E and exposed/authorized page error mapping and card
local identity, but exact healthy checklist then failed before rendering because
the production session owner used its unavailable default root. V3 points the
session owner at the already private task-owned artifact root, gives it an exact
instance and trusted HTTPS scheme, and relies on the existing attempt-all root
cleanup.

No response expectation changes. Missing/drift checklist GET/HEAD must remain
plaintext 503; exact queue/card/checklist and PTO append must pass. Fresh Gate 3
is required before reapplying the two minimal production changes.
