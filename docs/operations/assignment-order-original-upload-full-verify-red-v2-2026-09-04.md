# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — full verification RED v2

- Recorded: `2026-09-04T05:01:00+03:00`
- Exact commit: `a55565dbd72e3112fd9f133dc3e4c77bfaf3ed94`
- Command: `make verify`
- Exit: `2`
- Terminal result: `FULL_VERIFICATION_FAILURE count=3 stages=unit-test,db-test,e2e-test`

GREEN stages: test DB reset, canonical v12 migration, architecture 7/7, lint,
characterization and diff check. Original-upload suites, anonymous repeated
session regression, checklist endpoint/UI client, auth hot path, TCPDF renderer,
artifact store and production composition passed.

Remaining classified failures:

- unit: intended navigation-removal RED; separate Docker credential/vsock
  setup failure while resolving pinned build-image metadata;
- DB: object-card/navigation/prepare/UI-shell and dependent E2E presentation
  contracts;
- E2E: superseded `Сформировать распоряжение` launch assertion.

This is not `VERIFY_OK`, Gate 5 approval or a waiver.
