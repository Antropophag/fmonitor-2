# INSTALLATION-COMPLETION-SCHEMA-001 — card local identity integration RED

- Date: `2026-09-04`
- Gate: `2`, unchanged approved runtime verifier
- Production changes: none

The exact healthy runtime matrix stops on `GET /pilot/objects/4512`: expected
200, actual redacted 503. The same fixture's object queue is 200, and its local
actor `901` is active with exact `objects.read`; `REMOTE_USER` is descriptive
only and no legacy positive identity exists. The generic configured card branch
still resolves the actor through the legacy email directory before reading the
card, causing the infrastructure mapping.

Minimal GREEN is route-local: exact object-card GET/HEAD uses active local
profile plus exact `objects.read` when trusted local ID exists; legacy fallback
remains only when local identity is absent. Checklist/completion behavior,
missing/drift fail-closed matrices, 85% append and bootstrap snapshots remain
unchanged.

Fresh independent Gate 3 review is required before production edit.
