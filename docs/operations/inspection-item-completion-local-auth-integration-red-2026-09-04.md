# INSPECTION-ITEM-COMPLETE-001 — local-auth/session integration RED

- Date: `2026-09-04`
- Gate: `2`, unchanged approved public HTTP verifier
- Production changes: none

After object-list/UserAccess local-identity migration, the unchanged inspection
endpoint verifier reaches a successor integration failure. Its canonical local
actor `7301` exists only in `fm2_pilot_users` with exact
`inspection.item.complete`; legacy users are deliberate non-authority decoys.

The corrected verifier supplies `FMONITOR_AUTH_USER_ID=7301` and no
`REMOTE_USER`. The production entrypoint invokes `RapidPilotLocalAuth`, which sets trusted
`REMOTE_USER`, `FMONITOR_AUTH_USER_ID` and `FMONITOR_AUTH_CSRF` in the PHP server
globals. The injected callback receives the request server array by value, so
the subsequent `PilotHttpRequestFactory` sees the pre-auth copy. Checklist POST
and sync-context therefore see no authenticated request identity and return generic 401.

Fresh request diagnostics before any assertion change:

```text
POST /pilot/objects/4512/checklist/operations
status 401
body Authentication required.\n

GET /pilot/construction-control/objects/4512/sync-context
status 401
body Authentication required.\n
```

The public test then fails at JSON decoding because both approved JSON responses
were replaced by plaintext denial. The page/UI branch independently reports all
item-only controls unavailable. Minimal GREEN must propagate only the trusted
identity/CSRF values produced by the existing LocalAuth callback into the same
request array before request construction; it must not add a second session
owner, accept client headers or grant legacy authority. Tests remain unchanged
and require fresh independent Gate 3 integration review before production work.
