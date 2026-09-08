# PILOT-SESSION-STORAGE-001 v9 unknown-route owner decision

- Date: 2026-09-03
- Decision: `APPROVED`
- Owner answer: `Да` to the explicit recommendation that every unknown `/pilot/*` return `404` before authentication/session/config and that the session-storage contract/test be updated.
- Exact outcome: anonymous and authenticated unknown pilot routes return inherited `404 Not found`; no session environment read, filesystem primitive, cookie or login redirect. `303 /pilot/login` remains only for known login-required routes.
- Reason: preserves deterministic route priority and avoids conflating a nonexistent resource with an authentication flow.
