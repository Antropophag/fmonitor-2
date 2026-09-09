# Independent Gate 5 review — PRODUCTION-RUNTIME-SESSION-CONTENTION-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Implementation author: `/root`
- Verdict: **APPROVED (BOUNDED)**

## Reviewed identities

```text
d858b529f8ce6edd0c361c0a9b697f4bd436e0eddfd8b35bbf89d52aa10d6b24  specs/PILOT-SESSION-STORAGE-001.md
799e824a6fee1d3cc1a448892a971a817ac4a17f18f478e8ba11bb18cc882edd  tests/Runtime/session_contention_001_test.php
f079416f7b7e234b908a08efc5d5c3f25274741f543df2421f857609ee938aa7  tests/Runtime/session_contention_worker.php
dcbe75d48355b44f66938e849c10e1914170ac6c0c9d9a2af2d73005c53a1880  app/IdentityAccess/PilotSessionStorageTypes.php
9746ffc3592e01ac780148671385cdd52f6a9f48efdccc70ffa85a7f66106008  app/IdentityAccess/NativePilotSessionFilesystem.php
d3b7100ad34dd90e4a26141f5ac309ed44b9dc103a2562f6d16674c6e6a7bfa8  app/IdentityAccess/FilesystemPilotSessionStorage.php
```

## Review

The native adapter now uses flock's third argument to classify only an actual
would-block condition as the new closed `WOULD_BLOCK` result. Ordinary native false
and existing warning/exception outcomes remain permanent failures with no diagnostic
leakage.

The owner retries only typed WOULD_BLOCK within its existing monotonic two-second
deadline. A read/start may proceed after acquisition and therefore reads the latest
committed bytes. Mutation categories still unlock and return their original failure
after observed contention, so caller-computed stale payloads are never published.
Timeout and permanent-failure mappings remain fail-closed.

## Verification

```text
$ php tests/Runtime/session_contention_001_test.php
PASS: PRODUCTION-HTTP-RUNTIME-001 transient native session contention

$ php tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v5 real-owner filesystem tracer

$ php tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 sequential write identity

$ git diff --check
# exit 0, no output
```

## Verdict

**APPROVED (BOUNDED).** Transient native contention no longer turns a safe session
read into HTTP 503, while contended mutation safety remains unchanged. Full browser
and runtime Gate 5 remain pending.
