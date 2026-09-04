# INSPECTION-PLANNING-SCHEMA-001 — construction-control local identity GREEN

- Date: `2026-09-04`
- Gate 3: `reviews/tests/INSPECTION-PLANNING-SCHEMA-001-construction-control-local-identity-integration-v1.md`, `APPROVED`

Only the configured `/pilot/construction-control` branch now uses the existing
local authenticated-user resolver with exact `construction_control.read`.
Root/card branches remain unchanged; legacy fallback remains available only
when no trusted local actor ID exists.

```text
PASS: INSPECTION-PLANNING-SCHEMA-001 real HTTP/Compose DML-only contract
PASS: INSPECTION-ITEM-COMPLETE-001 raw HTTP endpoint admission
ARCHITECTURE CHECK PASSED (7 rules)
lint and diff-check: PASS
```

The full planning verifier proves healthy schedule/calendar/queue/control,
missing/incompatible schema fail-closed behavior and exact zero DML/schema
repair snapshots. Independent Gate 5 remains required.
