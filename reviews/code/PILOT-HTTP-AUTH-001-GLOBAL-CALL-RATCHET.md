# Code review: PILOT-HTTP-AUTH-001 global-call ratchet

- Reviewer: `Codex agent /root/global_call_qualification_review` (independent Gate 5 reviewer; did not author the implementation or approved regression test)
- Reviewed commit: working tree at HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`
- Reviewed at: `2026-08-31T20:28:05Z`
- Normative specification: [`specs/PILOT-HTTP-AUTH-001.md`](../../specs/PILOT-HTTP-AUTH-001.md), v0.12, section 11.2
- Approved regression test: [`tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php`](../../tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php)
- Verdict: `APPROVED`

## Standards

`APPROVED`. The scoped production diff contains 64 additions and 64 removals across the eight reviewed files. Every change adds only the leading global-namespace qualifier `\` to an existing direct runtime/global function call. Names, arguments, literals, SQL, control flow, interfaces, persistence, authorization, audit/history behavior, and integration ownership are unchanged. No documented-standard breach or blocking code smell was found.

The approved open-world token oracle reports no remaining bare or namespace-relative direct function call anywhere under `app/PilotHttp/*.php`. This satisfies the section 11.2 rule that applicable production runtime primitives use fully-qualified global names and cannot resolve to same-namespace shadow functions.

The architecture ratchet initially exposed a line-fingerprint instability on minified SQL-bearing lines. That was handled as a separately specified, RED-reviewed harness repair. Its final stateful scanner preserves SQL/DDL literals, comments, heredocs, nowdocs, and namespace separators while treating only executable PHP global-call qualification as fingerprint-neutral. The reviewed architecture baseline migration retained the same debt inventory and the final architecture check is green; no SQL ownership debt was added by this repair.

## Spec

`APPROVED`. The implementation exactly performs the section 11.2 regression repair and nothing else. The regression test is appropriately sensitive: its unlisted bare-call probe and namespace-relative probe fail the oracle, while declarations, methods, static calls, constructors, and explicitly qualified calls are excluded structurally. A plausible reintroduction of any unqualified runtime call in the complete `app/PilotHttp/*.php` scope would make the test fail.

No semantic, persistence, security, authorization, audit/history, or integration-boundary change was found.

## Verification evidence

```text
php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

php -l <each of the eight reviewed production files>
8/8 PASS: no syntax errors

make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

git diff --check -- <eight reviewed production files>
PASS
```

## Reviewed-input SHA-256 manifest

```text
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
60110b7db537c74262310a7e982e20e6945c12b49f5d1524f0b02c0a13c58271  tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
6fc16a4196e8f0f2f61594b9fa97fac7b7eb7681d62fe5cae8f4d72adbc423a9  app/PilotHttp/AccessPolicy.php
b92369d7a36efe107ba3c312d79c7eb71af81c16f2d594c5384cce5cc931728c  app/PilotHttp/ChecklistSync.php
3c042c5f4595735e6328a560ae9d8e8bd57b4bd60bbf44dda353f57b27fcabae  app/PilotHttp/InstallerDirectoryView.php
c764ed28e7ccc01fbdbb4ba286ff0e37afd38bea1d8f972eface2d8fea7cd340  app/PilotHttp/ObjectCardView.php
050d4dc24d7fc95f354275df4ba9b946d5ea6dfdebe2e0d198709df16b806143  app/PilotHttp/PilotE2ECoordinator.php
ff20d0468e4b01b077ef71e00af95f9f8ea96c2c74550e3e6636e574e180ce3a  app/PilotHttp/PilotHttp.php
64f79c5c3b7ea18dee7ff0a4b9b990870d9b008181438a2bba9ac659684f35f3  app/PilotHttp/PilotView.php
dbfcdc9bdec1c1c04de99a1e3ccd943fdca4c76b3b5126d3e96aa4dc674bf1b7  app/PilotHttp/UserDirectoryView.php
3e599bae1aee34cfaaf5e161623cf07a0248a7a0b5143ebbe21951b0a0731082  tools/architecture/check.py
1a320a70643023eff9722f28386d40cae81d83e138d2f122de9e4f678c3679ab  tools/architecture/baseline.json
9fc40f36b41e86cf2a0e4db52c9b47148f34c27dcd81182bdeb67b856d2f3909  tools/architecture/tests/test_debt_fingerprint.py
f226e65e2648b0ae5a0505ebfd3c3ad1a61fed3d2385966d43529f5a99fd1a24  reviews/tests/ARCHITECTURE-DEBT-FINGERPRINT-001.md
```

Any change to the reviewed production, specification, regression-test, or architecture-ratchet bytes above invalidates this approval and requires a fresh independent review.

## Findings

None.

## Required changes

None. Gate 5 for PILOT-HTTP-AUTH-001 section 11.2 global-call qualification is `APPROVED`.
