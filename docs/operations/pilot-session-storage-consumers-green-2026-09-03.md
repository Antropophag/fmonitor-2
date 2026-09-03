# PILOT-SESSION-STORAGE-001 v10 consumers — Gate 4 GREEN

- Date: 2026-09-03
- Exact implementation commit:
  `a0ae2706112b6a2ac08fd006044c0d5a6dbfb53a`
- Approved LocalAuth Gate 3:
  `reviews/tests/PILOT-SESSION-STORAGE-001-local-auth-lifecycle-v3.md`
- Approved UserAccess Gate 3 records: flash v7 and tokens v1.

Implementation result:

- LocalAuth uses the single request-scoped real owner for canonical start,
  write, regeneration and destroy;
- production and injected factories share the same owner instance with the
  LocalAuth pre-admission hook;
- command sessions use `PilotCommandSession` rather than native PHP sessions;
- UserAccess GET tokens/flash and invitation POST state commit before response;
- authentication queries moved to the allowed MariaDB adapter seam;
- all native session/hardcoded-root fingerprints were removed without changing
  the architecture baseline.

Observed verification:

```text
all 23 tests/InstallationProcess/pilot_session_storage*_test.php — PASS
PILOT-HTTP-AUTH-001 complete global-call qualification — PASS
make architecture-check — ARCHITECTURE CHECK PASSED (7 rules)
quality graph validation — PASS
git diff --check — PASS
```

The exact LocalAuth success and fault tests prove canonical anonymous and
authenticated bytes, committed return-to, regeneration/old-ID invalidation,
logout destruction, exact write/regenerate/destroy fault traces and full 503
envelopes. The UserAccess tests prove one-shot flash, committed action tokens
and buffered publication failure.

Full repository GREEN and Compose restart remain separate required evidence;
the CSP login fixture still needs a test-only owned-root setup correction before
it can join the final regression ladder.
