# PILOT-SESSION-STORAGE-001 v10 — UserAccess local-profile GREEN

- Date: `2026-09-04`
- Gate 3 authority: `reviews/tests/PILOT-SESSION-STORAGE-001-user-access-css-v8.md`, `APPROVED`
- Production commit: `051aa38`
- Scope: owner-backed `GET /pilot/admin/users`

Minimal production correction uses trusted `FMONITOR_AUTH_USER_ID` already
constructed by the canonical owner-backed UserAccess branch to read the active
`fm2_pilot_users` profile. Exact `access.administer` remains independently
checked by the existing local capability policy. Requests without that trusted
local actor retain the predecessor legacy identity path. No alternate session
owner, filesystem read, native PHP session or test selector was added.

Fresh canonical DB verification:

```text
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess flash owner handoff
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess action tokens
PASS: PILOT-SESSION-STORAGE-001 v10 owner payload handoff
PASS: PILOT-SESSION-STORAGE-001 v10 accepted payload raw HTTP
PASS: PILOT-SESSION-STORAGE-001 v10 object payload raw HTTP
PASS: PILOT-SESSION-STORAGE-001 v10 LocalAuth canonical payloads
PASS: PILOT-SESSION-STORAGE-001 v10 LocalAuth fault boundaries
PASS: PILOT-SESSION-STORAGE-001 v10 LocalAuth lifecycle
ARCHITECTURE CHECK PASSED (7 rules)
lint: exit 0
PHP lint: no syntax errors
git diff --check: exit 0
```

The flash verifier proves one-shot consumption, buffered commit before 200 and
exact publish-failure 503 with prior bytes preserved. The token verifier proves
the rendered token is owner-committed and that publication failure cannot leak
the buffered page. Gate 5 remains independent.
