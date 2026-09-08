# INSPECTION-ITEM-COMPLETE-001 — LocalAuth handoff GREEN

- Date: `2026-09-04`
- Gate 3: `reviews/tests/INSPECTION-ITEM-COMPLETE-001-local-auth-integration-v2.md`, `APPROVED`
- Session sequential prerequisite: production `d66fc17`

The production LocalAuth callback now updates the same server array that the
request factory consumes, copying only `REMOTE_USER`, `FMONITOR_AUTH_USER_ID`
and `FMONITOR_AUTH_CSRF` produced by successful LocalAuth. A dedicated read-only
resolver uses the trusted local ID, active `fm2_pilot_users` profile and exact
`inspection.item.complete`; requests without a local ID preserve legacy
predecessor identity behavior. Client headers/environment are not accepted as
substitutes.

```text
PASS: INSPECTION-ITEM-COMPLETE-001 raw HTTP endpoint admission
PASS: item-only UI/client wrapper and raw HTTP endpoint admission
PASS: PILOT-SESSION-STORAGE-001 v10 sequential write identity
ARCHITECTURE CHECK PASSED (7 rules)
lint and diff-check: PASS
```

The approved test observes four actual post-callback actor/CSRF tuples, exact
capability-only admission, malformed item 422, non-item 403, sync-context 200,
42 enabled item controls, disabled future controls, zero mutation and complete
DB/artifact/session/router cleanup. Independent Gate 5 remains required.
