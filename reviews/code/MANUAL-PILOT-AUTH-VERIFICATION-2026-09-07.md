# Manual-pilot auth correction — independent verification

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the reviewed production or test changes.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `c0137ff3cfd6b4c543f104085816b1aeb0e2548d`.
- Scope: only `app/PilotHttp/ExecutionHttpCoordinator.php` and `tests/InstallationProcess/pilot_http_auth_001_test.php` working-tree diffs against that base.
- Verdict: `APPROVED` for the bounded manual-pilot auth correction.

## Exact reviewed identities

Working-file SHA-256:

```text
29023bedc5a4d70e3010668a19cfbb6c2fa11bccb031d7fabc953f5612a47593  app/PilotHttp/ExecutionHttpCoordinator.php
43632fd27fe5ac44c6721d5777eedec7a93471d8c683e9ab4d37de1b6b114ed4  tests/InstallationProcess/pilot_http_auth_001_test.php
```

SHA-256 of each exact `git diff --binary c0137ff3cfd6b4c543f104085816b1aeb0e2548d -- <path>` byte stream:

```text
396f9c0f20009fe4b70a8699b412934be6cc468f9e98eee1164e16c9ced5cbe3  app/PilotHttp/ExecutionHttpCoordinator.php.diff
0575d06cc353f04c21a68f7b08938e88d7ea1d61022e998eab7152820e980227  tests/InstallationProcess/pilot_http_auth_001_test.php.diff
```

Combined exact two-file `git diff --binary` SHA-256:

```text
2b971309fc30906c85e6ba12bcd3f235abd17e1a69b423d4f19b4968a78174f1
```

## Findings

No blocking findings.

The production correction classifies only the routes owned by `ExecutionHttpCoordinator` before reading `FMONITOR_FRESH_ORDER_FLOW`: execution, opening, original history, and an exact original-revision download. All other paths delegate immediately. The route union matches the handler branches below it, including positive canonical object/order identifiers and the existing revision-token grammar. It therefore restores the inherited unknown-route ordering: an unrelated route can reach the exact `404` without a feature-flag read, while owned routes retain their existing flag-dependent behavior. The change adds no authorization bypass, persistence, audit mutation, or fallback route.

The test correction follows the current pilot rather than reinstating the obsolete header layout. It requires exactly one sidebar identity, the FMonitor object-list home link, the exact resolved display name, and the exact resolved email. Existing adversarial query/header/cookie/Authorization spoof inputs remain present and the assertions remain tied to the trusted resolved user. The owner-required rapid-pilot UI is preserved; no protected E2E expectation was changed.

The outage fixture now removes `process_fm2_pilot_users`, the current local authenticated-user source, and still requires the exact redacted `503 Service unavailable` response with `Retry-After: 60`. This is consistent with the later local-RBAC authority and manual-pilot behavior. It does not weaken exact legacy identity coverage: absent, inactive, dangling, duplicate, case-distinct, malformed, and spoofed principals remain exercised elsewhere in the same public HTTP test.

The source-unavailable and ordering behavior remains fail closed. Unknown routes read zero environment keys; a recognized route is the only new condition under which this coordinator may read `FMONITOR_FRESH_ORDER_FLOW`. The reviewed source uses fully qualified `\preg_match`, and the existing global-call ratchet remains green.

`specs/PILOT-HTTP-AUTH-001.md` v0.12 still describes the original header-era DOM and legacy-only resolution. Those portions have been superseded for today's manual pilot by the owner-directed preserved rapid-pilot UI and the approved local-auth work recorded in the current delivery handoff. This bounded correction properly aligns the regression test with that newer behavior; it does not claim that the old prose was silently rewritten or that full production gates are complete.

## Independent verification

```text
PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

php -l app/PilotHttp/ExecutionHttpCoordinator.php
No syntax errors detected in app/PilotHttp/ExecutionHttpCoordinator.php

php -l tests/InstallationProcess/pilot_http_auth_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_http_auth_001_test.php

PATH=/opt/homebrew/bin:$PATH php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

git diff --check -- app/PilotHttp/ExecutionHttpCoordinator.php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS
```

The HTTP test used its isolated fixture database and completed cleanup. No global database reset, stand action, remote mutation, Bitrix action, or protected E2E change was performed. Other concurrent working-tree changes were outside this review and are not covered by the verdict. This approval is bounded to the two exact diffs above and does not assert `VERIFY_OK`, deployment readiness, or completion of the continuing manual-pilot goal.
