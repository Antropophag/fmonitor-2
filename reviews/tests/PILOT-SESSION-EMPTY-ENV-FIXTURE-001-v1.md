# PILOT-SESSION-EMPTY-ENV-FIXTURE-001 v1 — independent Gate 3 test review

- Review date: `2026-09-05`
- Reviewer: independently tasked Gate 3 reviewer
  `/root/session_env_fixture_review`; reviewer authored none of the reviewed
  specification, RED evidence, proposed patch, test, or runtime artifacts
- Reviewed repository HEAD: `af75b2002766391ad0dce78bf609e4bbdeeadfa7`
- Verdict: **APPROVED**

## Traceability and qualifying RED

The approved test-only contract requires the explicitly present empty
`FMONITOR_SESSION_STATE_ROOT` value to reach the native PHP child before the
existing raw HTTP assertions run. The recorded RED is sensitive to exactly that
fixture defect: the unchanged protocol test in the exact unprivileged,
network-disabled image exited 255 because GET expected 503 and received 200,
while independent native probes observed `[false,false]` for the map-only child
and `["",""]` with the approved `/usr/bin/env KEY=` argv prefix. The usable
compatibility default in that image independently explains the 200 when the key
is lost. This is qualifying fixture-transport RED rather than broken database
setup or a request to change runtime absent-key behavior.

The original test remains byte-identical at SHA-256
`315825c95c7ba4059b63e298bf3f710621ff9ffd3e33c57c4982fb43146204d3`,
and `git apply --check` passes for the exact proposed patch. The five separately
reported database-connection failures are outside this review and supply no
evidence for or against the patch.

## Patch review

The patch conforms to the approved boundary:

- The prefix is selected only by
  `array_key_exists('FMONITOR_SESSION_STATE_ROOT', $extra)` together with exact
  value `''`. Absent and nonempty values retain the prior command. Probe and
  server both use the same exact prefix, PHP binary, cwd, and environment map.
- The command is passed as argv without a shell. Only the fixed known empty
  session-root assignment appears in argv. Database passwords and all other
  environment values remain in the `proc_open` environment map and are not
  printed by the probe.
- The probe child reads only `FMONITOR_SESSION_STATE_ROOT` through the two
  required native `getenv` forms. Its accepted result is independently fixed as
  exit 0, stdout exactly `["",""]`, and empty stderr. Any other observation is
  labeled `SETUP_FAILURE` and occurs before HTTP server start.
- Both output pipes are nonblocking and drained during supervision. Each read
  permits at most the remaining bytes plus one, so byte 4097 deterministically
  rejects that stream. The normal loop uses `hrtime(true)` with a three-second
  deadline and retains the exit code from the first status observation that
  reports process completion.
- Cleanup runs through `finally` on success and failure. A live child receives
  TERM, is observed for a monotonic 500 ms grace, then receives KILL and is
  observed for at most two seconds. The implementation checks that it stopped,
  closes both pipes, reaps with `proc_close`, and rejects failed pipe cleanup.
  On normal completion the retained exit code prevents later
  `proc_get_status()` calls returning `-1` from erasing the observed result.
- The only existing line changed is `pssStart`; all HTTP request construction,
  asset/unknown-route/Host/URI priority assertions, exact GET/HEAD/POST 503
  matrix, positive HTTP/HTTPS behavior, injected-fault behavior, server
  readiness/stop behavior, and test cleanup remain byte-equivalent after the
  narrow command prefix and prerequisite probe are inserted.

No production file, router, protected E2E test, runtime selector, or safe-log
mechanism is changed or made reachable. The proposed probe is a deterministic
test setup assertion at the existing public raw HTTP seam.

## Independent verification

The patch was applied only to a task-owned temporary copy of the test. The
temporary patched file passed:

```text
php -l <temporary>/tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
No syntax errors detected
```

Its resulting SHA-256 was
`fef3f2c0b5308d9b445e2e3cc023321606f72cca9b19a1d1b36a4dbc6d40c174`.
The temporary tree was removed, the repository test retained its original hash,
and `git diff --check` passed. No database-backed test was run during the
isolated database preparation.

## Exact reviewed SHA-256

```text
d7c9a4acd71aaadd25c21bed1bc836b34ee8e61c64c6318877e44301711760cf  specs/PILOT-SESSION-EMPTY-ENV-FIXTURE-001.md
35a7019594da85c3dcce4fa77839219ab620198238a24c75842cb3eee826e621  docs/operations/session-empty-env-fixture-gate1-review-2026-09-05.md
a1afd7605027f5e622e61103b074e6264d00f300021e9c0209b6955be20906a4  docs/operations/session-empty-env-fixture-red-2026-09-05.md
02f590c9d8127486d6ac801f83d32d1d9c424b3e2cb03f2f8caaeb080336eb53  docs/operations/patches/pilot-session-empty-env-fixture-v1.patch
315825c95c7ba4059b63e298bf3f710621ff9ffd3e33c57c4982fb43146204d3  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
ee1e8eec0ae2b0044f76ea255033aec36b0edeeb699dd667fef093152058e1c5  app/PilotHttp/LazyPilotSessionStorage.php
```

## Final verdict

**APPROVED** for the exact patch hash above. The patch may be applied without
changing its bytes, followed by focused host and exact-image GREEN evidence and
a fresh independent Gate 5 review. This approval does not satisfy the separate
Compose startup/restart prerequisite or approve any production or safe-log
change.
