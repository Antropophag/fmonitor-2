# Independent code-review addendum — INVITATION-REISSUE-001

Date: 2026-09-08
Reviewer: separately tasked agent `/root/review_invitation`
Base PR head: `f71a2663dc7271a3bae1247f1a239358023c96cc`
Reviewed amendment: one pending byte insertion in
`app/PilotHttp/PilotUserAdminHttpHandler.php:16`
Verdict: **APPROVED**

CI and the local reproduction found one documented standards miss: the new direct
global call used `preg_match(...)` inside namespace `FMonitor2\PilotHttp`, while
`pilot_http_auth_001_global_calls_test.php` requires production runtime/global
calls to be explicitly global. The amendment changes only that expression to
`\preg_match(...)`. It does not change the regular expression, route match,
control flow, assertions, persistence, authorization, or UI behavior.

The reviewer inspected the complete pending diff (`1` insertion, `1` deletion),
confirmed `git diff --check` and PHP syntax, and independently ran:

```text
php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

tools/architecture/check --json
{"errors": [], "ok": true, "rules": 7}
```

The focused INVITATION-REISSUE-001 lifecycle was reported GREEN after the fix by
the root verification flow. The reviewer's attempted repeat could not start
because the previously isolated MariaDB on port 23307 had already been stopped;
this was a setup refusal before fixture creation, not a behavior failure.

This addendum approves the single qualification amendment. The original test and
code approvals remain applicable to the invitation behavior. Exact amended commit
continuity must be pinned after the correction is committed.
