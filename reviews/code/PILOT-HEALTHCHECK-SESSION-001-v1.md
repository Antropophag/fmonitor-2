# Gate 5 code review: PILOT-HEALTHCHECK-SESSION-001

- Reviewer: independent separately tasked agent `/root/health_review`; did not author implementation or tests.
- Date: 2026-09-07.
- Reviewed commit: `ee313546b97a39da1af30e7feffc94b0fe7e1b15`, against `e1d3a64824395c96ceb47827b21e114dc4c60076`.
- Specification: v0.2 and Gate 3 v2 approved artifacts.
- Verdict: **CHANGES_REQUESTED**.

## Blocking finding

`rapid-pilot/healthcheck.php` invokes curl with `--cookie-jar` pointing to the retained cookie file, discards stderr, and treats curl exit 0 plus HTTP 200 as success. The post-call `owned()` check verifies type, UID and mode only. Curl does not report failure to create/write its cookie jar through a failed operation. The reviewer verified this explicitly in the installed curl manual:

```text
If the cookie jar cannot be created or written to, the whole curl
operation does not fail or even report an error clearly.
```

Consequently, if an existing correctly owned mode-0600 jar cannot persist updated cookies (for example write failure due to exhausted storage), the probe may report success with a stale or empty jar. The approved contract says cookie-storage errors produce exit 1. In the empty/stale-cookie case this also risks reproducing repeated anonymous session creation, the incident mechanism this change is intended to prevent. Existing-file metadata validation cannot prove successful persistence.

Required: detect cookie persistence failure explicitly at the CLI seam and fail closed. Add a deterministic regression for failed jar persistence, demonstrate RED against this implementation, and obtain renewed independent Gate 3 approval before implementing the correction. Keep this bounded to the existing cookie-storage-error contract; no authentication/domain/GC policy change is needed.

## Other findings and evidence

- Independently reran `python3 tests/Verification/pilot_healthcheck_session_001_test.py`: all reviewed cases passed, ending `PILOT_HEALTHCHECK_SESSION_OK` (approximately 7.6 seconds).
- Read retained `health-green.log` and `health-architecture.log`: focused suite passes and architecture check reports seven rules passed.
- Both prior protected HTTP paths remain anonymous liveness checks; status 200 is required after at most three explicitly controlled same-origin redirects. Curl automatic redirects are not enabled, proxy configuration is disabled, and the origin/credentials/control-character checks precede subsequent requests.
- Session/domain persistence remains owned by the existing HTTP application. The helper writes only private healthcheck operational artifacts and performs no SQL, authentication action, or session deletion.
- UID rejection is present for root, private directory, cookie, and lock: `lstat` UID must exactly equal `posix_geteuid()`, with appropriate inode type and specified modes. This was inspected manually; the unprivileged macOS fixture does not exercise foreign ownership.
- Compose uses the public shell seam with a six-second timeout, allowing the specified one-second contention wait plus two two-second path deadlines and small process overhead. Docker explicitly installs curl; the characterization runner invokes the approved native-session regression.
- No production source/test edits or remote actions were made by this reviewer. Passing focused tests and architecture checks do not close the blocking cookie-write failure gap or substitute for final integration verification.

## Reviewed SHA-256

| Artifact | SHA-256 |
| --- | --- |
| `rapid-pilot/healthcheck.php` | `1ed5be10654c56731a2e61669afbb37702b537d94d4271cd31270766d388ec18` |
| `rapid-pilot/healthcheck.sh` | `23e99deb7f62b4009cb4181e07ba3984d8800628bb4fd81b4fc98f839d451a09` |
| `tests/Verification/pilot_healthcheck_session_001_test.py` | `f728c1d630d5cde39cfee9b1416cfaa52cb4afeb3d1bc97e5cd6dcaffb7bd37c` |
| `tests/Support/pilot_healthcheck_storage_cases.py` | `8329fb60fbc0413872f8c514c8924f5f78aeb024f7532eef16d806ac0c245003` |
