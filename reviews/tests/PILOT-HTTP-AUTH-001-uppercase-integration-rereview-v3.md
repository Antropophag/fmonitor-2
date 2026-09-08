# PILOT-HTTP-AUTH-001 legacy-query fault — independent Gate 3 rereview v3

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/http_auth_uppercase_rereview`
- Reviewed commit: `9a42573fad0f70c5f222f1b558be1d479a06486f`
- Predecessor reviews: v1 `CHANGES_REQUESTED`; v2 uppercase correction `APPROVED`
- Scope: downstream legacy identity query-fault correction at `tests/InstallationProcess/pilot_http_auth_001_test.php:67`
- Verdict: **APPROVED**

## Findings

No blocking findings in the reviewed correction.

The fault now hides and restores `legacy_users`, the exact configured legacy
table required by the production `/pilot/` identity lookup. This is traceable
to `PILOT-HTTP-AUTH-001 v0.12`: after valid `REMOTE_USER` and CSS validation,
the shell must resolve exactly one active legacy user joined to an active legacy
role; an unavailable required query maps to generic `503`.

The assertion observes the real public HTTP seam and requires exact:

```text
503
Content-Type: text/plain; charset=UTF-8
Retry-After: 60
Service unavailable.\n
```

It also calls the shared `phaExact`/`phaSecurity` oracle, which checks exact
content length, every inherited cache/security header, absence of CORS,
cookies, server/debug/auth headers and absence of unexpected application
headers. Thus a false `200`, leaked database detail, missing retry contract or
weakened security envelope is detected.

The reviewed commit changes only this one test line. No production, executable
specification, fixture schema or expected response contract changed. The
preceding bad-port case independently retains general connection-failure
coverage; this corrected case specifically proves failure of the mandatory
legacy identity query.

Cleanup is safe for the isolated task-owned database. On success the table is
renamed back before subsequent scenarios and the final full-database snapshot
assertion detects any catalog drift. On any assertion/exception, the outer
`finally` terminates owned servers, removes owned artifacts/users and drops only
the random `t_pha001_<12hex>` database. The independent run left no matching
database, PHP server or task artifact behind.

## Independent verification

```text
$ git diff --name-only 9a42573^..9a42573
tests/InstallationProcess/pilot_http_auth_001_test.php

$ git diff --check 9a42573^..9a42573
PASS

$ FMONITOR_TEST_DB_HOST=127.0.0.1 \
  FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_http_auth_001_test.php

Fatal error: Uncaught TestFailure: CSS swap reached post-lstat synchronization point
Expected: true
Actual: false
... pilot_http_auth_001_test.php(77)
```

The run passes the corrected uppercase scenario and the corrected line-67
legacy-query `503` assertion, as well as all intervening auth/config cases. It
fails later at the known macOS `LD_PRELOAD` synchronization harness because the
preload hook does not create its ready marker. That later line-77 setup failure
does not contradict or weaken this Gate 3 result and is not production behavior
evidence.

Post-run checks found zero `t_pha001_%` schemas, zero owned `.test-artifacts`
entries and no surviving `php -S` process.

## Reviewed hashes

```text
6d9bc6afd52a820af0cb216ec63b8cb5426bf4234609cdfc2ea83e584025d07d  tests/InstallationProcess/pilot_http_auth_001_test.php
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
66806572db401c1fc5c7087ff7cc0b20e72e4f06203040b6cbc76ee3a1326826  app/PilotHttp/PilotHttp.php
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

## Verdict

**APPROVED** for the exact line-67 Gate 2 correction at reviewed commit
`9a42573fad0f70c5f222f1b558be1d479a06486f`. No Gate 4 production change is
needed or authorized: current production already returns the required result
when its actual legacy identity source is unavailable. The cumulative test's
later macOS preload harness failure remains a separate integration task.
