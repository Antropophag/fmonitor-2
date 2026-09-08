# PILOT-SESSION-STORAGE-001 factory ordering — independent Gate 5 amendment review v1

- Date: 2026-09-04T00:03:22+03:00
- Reviewer: separately tasked agent `/root/session_factory_order_gate5`
- Implementation/test author: not this reviewer
- Reviewed HEAD: `86f8351d06f736905749aa4920d7fad329f14546`
- Approved parent envelope: `32e236f` (consumer Gate 5 and architecture-ratchet completion)
- Diff: `32e236f..86f8351`
- Verdict: **APPROVED**

## Amendment result

The one-line production diff removes the LocalAuth hook only from the ordinary
`ProductionPilotHttpEntrypointFactory::create()` result. This restores the
established direct-entrypoint ordering: a request without `REMOTE_USER` reaches
the entrypoint identity check and returns the canonical `401` without consulting
LocalAuth/session/database state. No product authorization or session behavior
is added or removed.

`createWithSessionStorageDependencies()` is unchanged: it still passes
`self::localAuth($owner)` and the same local `$owner` object is passed to both
`PilotE2ECoordinator` and that hook. The deterministic injected seam therefore
retains LocalAuth lifecycle coverage and shared request-owner identity. The
ordinary factory likewise still constructs one `$owner` and passes it to the
coordinator; the removed hook was the only ordering defect.

## Standards axis

**APPROVED, zero findings.** The amendment is confined to the existing public
composition seam, preserves one explicit session owner, does not move domain
facts into HTTP code, does not alter append-only evidence, and introduces no
new duplication, speculative abstraction or other material smell. `git diff
32e236f..86f8351 --check` exits 0.

## Specification axis

**APPROVED, zero findings.** Normal production requests retain the Pilot HTTP
identity-first contract. Injected session-storage construction retains the
LocalAuth hook and exact shared owner required by PILOT-SESSION-STORAGE-001 v10.
All 23 session-storage tests, including raw HTTP LocalAuth lifecycle and fault
tests, pass; no approved oracle was weakened.

## Independent verification at exact HEAD

```text
$ git rev-parse HEAD
86f8351d06f736905749aa4920d7fad329f14546

$ for f in tests/InstallationProcess/pilot_session_storage*_test.php; do php "$f"; done
PASS (23/23)

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<REDACTED> \
  php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

$ git diff --check 32e236f..86f8351
exit 0
```

The first exact-HEAD HTTP invocation reached the late descriptor-warning DB
cleanup sentinel and observed one still-closing process-list id. An immediate
unchanged exact-HEAD rerun passed the whole test. This is classified as the
known timing-sensitive cleanup observation, not as a product or amendment
regression: all preceding ordering assertions had passed, the amendment cannot
affect descriptor/DB cleanup, and the clean rerun proves the same bytes satisfy
the sentinel. For comparison, the parent `32e236f` does not reach that late
sentinel: it fails earlier with `503` instead of the required missing-identity
`401`, directly demonstrating the ordering defect corrected by this amendment.

## Reviewed hashes

```text
61bd0662c87f029ea3ea8ab0ee24b70a650fa1b28afc1692e034eaf06b898e27  app/PilotHttp/ProductionPilotHttpEntrypointFactory.php
0c8074ed4548f34fc12e7c3f6a4a30458939f0726caaa78d7b21c3b1b4b1c118  tests/InstallationProcess/pilot_http_auth_001_test.php
f8cfa30eace1eb62c8e32cc3a5d308019003088992746769e671e4635c8dcb9a  tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
```

## Verdict

Gate 5 amendment is **APPROVED** for exact SHA `86f8351`. The ordinary factory
restores identity-first ordering, while the injected factory retains LocalAuth
and the exact shared request owner. No blocking Standards or Spec finding
remains.
