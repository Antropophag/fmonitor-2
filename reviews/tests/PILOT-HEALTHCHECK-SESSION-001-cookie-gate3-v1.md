# Gate 3 corrective regression: PILOT-HEALTHCHECK-SESSION-001

- Reviewer: independent separately tasked agent `/root/health_review`; did not author test or implementation.
- Date: 2026-09-07.
- Reviewed implementation base: `ee313546b97a39da1af30e7feffc94b0fe7e1b15` (uncorrected cookie-file transport).
- Specification: approved v0.2; cookie-storage error requires exit 1.
- New test: `tests/Verification/pilot_healthcheck_cookie_failure_001_test.py`.
- Verdict: **APPROVED** for Gate 3 corrective regression. Existing Gate 3 v2 native-session approval remains in force for unchanged artifacts.

## Review findings

The test exercises the public CLI with a real isolated loopback HTTP server. It first demonstrates ordinary health success and proves that the actual persisted cookie jar exceeds 200 bytes and contains the supplied cookie. The complete HTTP response headers are independently bounded below 200 bytes. The second invocation alone inherits native `RLIMIT_FSIZE=200`, with `SIGXFSZ` ignored so the operating system returns the write error to the transport. The server and parent process are outside that resource limit.

Expected failure exit 1 derives from the existing normative cookie-storage-error contract, independent of the proposed correction. The healthy precondition and different header/jar sizes distinguish the failed cookie write from server setup or header-file failure. This is a natural kernel-enforced storage error through a disposable process resource limit; it does not replace native functions, mutate file permissions, access preview state, or require privileged interception. Temporary files and server resources are cleaned up.

The fixture is deliberately narrower than the separately approved real LocalAuth/native-session lifecycle test and complements it. No domain/authentication/session-GC behavior or new normative output format is introduced. The test does not claim comprehensive coverage of all filesystem failures.

## Demonstrated RED

Reviewer independently ran:

```text
python3 tests/Verification/pilot_healthcheck_cookie_failure_001_test.py
```

Observed:

```text
PASS healthy public CLI; headers fit but complete cookie exceeds200 bytes
AssertionError: ('INTENDED_RED cookie persistence failure must fail health', 0, b'', b'')
```

The expected exit is 1; uncorrected implementation incorrectly returns 0 with empty output after the native write failure. This reproduces the blocking Gate 5 v1 finding. The independently rerun output matches retained external diagnostic `health-cookie-red.log`.

## Required changes

None before the bounded implementation correction. Add this regression to the verification runner and retain GREEN evidence alongside the unchanged native-session suite for renewed Gate 5 review.

## SHA-256

| Artifact | SHA-256 |
| --- | --- |
| `tests/Verification/pilot_healthcheck_cookie_failure_001_test.py` | `86edeb3ee71f526460e1d34851beb390fe1abd92ecd49a3ad69185a7e20a89f3` |
| `rapid-pilot/healthcheck.php` before correction | `1ed5be10654c56731a2e61669afbb37702b537d94d4271cd31270766d388ec18` |
| `specs/PILOT-HEALTHCHECK-SESSION-001.md` | `d70594410e43e734984ad4355b5d2b87ed30bca65bca61db38b283d90b5491ce` |
| external diagnostic `health-cookie-red.log` | `1bea87d28d806c4beaec9578e14a458f0d83c3d25182d7ecf232e5e66f1d4575` |
