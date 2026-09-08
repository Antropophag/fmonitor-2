# Code review: PILOT-SESSION-EMPTY-ENV-FIXTURE-001 v1

- Review date: `2026-09-05`
- Reviewer: independently tasked Gate 5 reviewer
  `/root/session_env_fixture_gate5`; reviewer authored none of the specification,
  test, proposed patch, implementation, or GREEN evidence
- Implementation author: `/root`
- Reviewed commit: `46415f0589c7871f643fc644d265d164541a7dcc`
- Base production source commit: `af75b2002766391ad0dce78bf609e4bbdeeadfa7`
- Specification: `specs/PILOT-SESSION-EMPTY-ENV-FIXTURE-001.md`
- Approved test review:
  `reviews/tests/PILOT-SESSION-EMPTY-ENV-FIXTURE-001-v1.md`
- Verdict: **APPROVED**

## Findings

None.

The implementation is confined to the reviewed protocol test plus its GREEN
record. The test bytes after application have SHA-256
`fef3f2c0b5308d9b445e2e3cc023321606f72cca9b19a1d1b36a4dbc6d40c174`,
the exact result approved by Gate 3. `git apply --reverse --check` succeeds for
the reviewed patch SHA-256
`02f590c9d8127486d6ac801f83d32d1d9c424b3e2cb03f2f8caaeb080336eb53`
against the final tree, confirming that the reviewed hunk is the hunk applied.
The pre-implementation test recovered from the parent commit has the recorded
SHA-256 `315825c95c7ba4059b63e298bf3f710621ff9ffd3e33c57c4982fb43146204d3`.

The new command prefix is selected only for an explicitly present, exactly empty
`FMONITOR_SESSION_STATE_ROOT`. It uses the fixed argv entries `/usr/bin/env` and
`FMONITOR_SESSION_STATE_ROOT=` without a shell. The probe and server receive the
same prefix, PHP binary, working directory, and environment map. No database
password, environment-derived value, path, session data, or other credential is
copied into argv or probe output.

The setup probe reads only the approved key through both native `getenv` forms
and requires exact exit/stdout/stderr `[0, '["",""]', '']` before server start.
Both output pipes are nonblocking and drained during supervision, with a
deterministic 4096-byte limit per stream. Supervision uses a monotonic
three-second deadline; cleanup always runs, sends TERM with a monotonic 500 ms
grace and KILL with a bounded two-second observation when needed, verifies the
child stopped, closes both pipes, and reaps the process. The exit code captured
on the first completed status observation is preserved across later status
calls.

The only pre-existing test line changed is `pssStart`. The raw request helper,
route and asset priority checks, Host and URI rejection checks, exact
GET/HEAD/POST 503 status/header/body/cookie assertions, injected-fault cases,
positive HTTP/HTTPS cases, readiness/stop behavior, and test cleanup remain
byte-identical. No production, router, protected E2E, runtime selector, or
safe-log file changed in implementation commit `46415f0`; the recorded
production hashes for `PilotHttp.php` and `LazyPilotSessionStorage.php` remain
`66806572...26826` and `ee1e8eec...f0931` respectively.

## Independent verification

The reviewer ran:

```text
php -l tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
No syntax errors detected

php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer
```

The same focused protocol test also passed in a fresh uniquely named container
from `fm2-session-check-3c9ce25b4a:af75b2002766`, resolving to exact image ID
`sha256:b98963779a006f167986f082f7c7ff78f28e9fc4e30d66a6e11f9d8ecb7613d8`,
with `--network none`, the repository tests mounted read-only, a task-specific
unprivileged tmpfs at `/workspace/fmonitor-2/.test-artifacts`, and `php` as the
entrypoint:

```text
PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer
```

The archived host and 25-test image GREEN logs exist with their recorded hashes
`29cf45ef...3d6b` and `d0fd77d8...4bdc`; the image log reports
`SESSION_IMAGE_FAILURE_COUNT=0`. The qualifying RED log also matches its
recorded hash `6af61c82...aa96`. `git diff --check` passes.

## Required changes

None.

This approval covers only the faithful explicit-empty environment fixture. It
does not prove clean Compose startup/restart, session continuity across service
stop/start, protected E2E behavior, or launch readiness.
