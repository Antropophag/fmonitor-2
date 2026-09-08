# Independent Gate 3 test review — PILOT-SESSION-STORAGE-001 v10 UserAccess tokens v1

- Date: 2026-09-03T22:48:11+03:00
- Reviewer: separately tasked agent `/root/session_user_tokens_gate3`
- Test/implementation author: not this reviewer
- Reviewed commit: `04ca976f365098c5a007b0299beb8aea4795f599`
- Scope: no-flash UserAccess action-token RED against
  `PILOT-SESSION-STORAGE-001` v10 sections 3, 6–8 and 11
- Originating Gate 5 review:
  `reviews/code/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md` —
  `CHANGES_REQUESTED`
- Verdict: **APPROVED**

## Review

The reviewed commit changes only the new focused test and its append-only RED
evidence. Production and the previously approved production-composition routers
are unchanged from the reviewed Gate 4 implementation. The test reaches the
real authenticated raw `GET /pilot/admin/users` through
`ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies`,
with the canonical state root/instance and all production CSS, database,
artifact, time, prefix and trusted-scheme dependencies supplied by the existing
approved routers.

The normal branch creates a fictional active administrator, publishes literal
canonical whole-array session bytes through the real owner, and sends the real
cookie to the raw HTTP seam. It first requires `200`, then extracts a token that
is actually present in the rendered `csrfToken` input and reopens the same
session through a fresh real owner. Requiring that exact rendered token as a key
in the reopened owner payload is independent of the implementation and catches
the Gate 5 defect: production currently mutates only request-memory state on a
no-flash GET. A static page, a fabricated success claim, a different token, a
native-session side owner, or a write that is not subsequently readable by the
canonical owner cannot satisfy this assertion.

After that intended assertion becomes GREEN, the separate fault branch restores
the exact original canonical bytes through the owner and uses the existing
external primitive wrapper to fail precisely
`rename / committed / ordinal=1 / native_false`. It requires `503`, the literal
`Service unavailable.\n` body, absence of `Location` and `Set-Cookie`, exactly
one matching redacted trace tuple, and byte-identical preservation of the prior
committed material. Thus a response released before publication, success-header
leakage, a fault at another primitive/artifact/ordinal, or overwrite of prior
state remains observable. The production response builder already supplies the
section-6 fixed security envelope; the focused delta checks the newly relevant
success-leak and payload-preservation properties rather than duplicating the
previously approved full-envelope oracle.

The expected original payload, token grammar, status, fault tuple and response
bytes are literals derived from the approved contract and Gate 5 finding, not
from production output. Random values only isolate the task-owned database rows
and short-lived root. The `finally` path always stops/reaps the server, deletes
the exact fictional user and role, closes the database and removes only the
test-owned root. Independent post-run probes found no matching database fixture
or task-root residue.

## Independent RED reproduction

```text
$ date --iso-8601=seconds
2026-09-03T22:48:11+03:00
$ git rev-parse HEAD
04ca976f365098c5a007b0299beb8aea4795f599
$ git status --porcelain=v1
(no output)
$ php -l tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
$ php tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: rendered action token committed by owner
Expected: true
Actual: false
exit=255
$ test ! -e /tmp/fmonitor2-session-storage-tests
exit=0
$ independent query for token.actor.* users and token-role-* roles
RESIDUAL_USERS=0
RESIDUAL_ROLES=0
$ git diff --check HEAD^ HEAD
exit=0
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
b4bfe456852e4799916878ca048de7c960eced1b1ab798c007935e8eeaa6ff94  tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
e92a23838563b253d36eabeeb444ca5f9ec979485873abfcf923e8956f572ccb  docs/operations/pilot-session-storage-user-access-tokens-red-evidence.md
cff3d21dd30aaa7cfd91b374a254e9c4a463f0ae37675e14a94996478f9535b0  tests/Support/pilot_session_storage_user_access_router.php
18306b9015898a179a89961894011565f027e07c207f7f915afdca837313bc3f  tests/Support/pilot_session_storage_user_access_fault_router.php
be67d2b1b95f22aa0168080c00f059222eac89a5f06ae72937406476a9b4d2cb  reviews/code/PILOT-SESSION-STORAGE-001-user-access-flash-v1.md
```

Gate 3 is **APPROVED** for these exact expectations. Gate 4 may implement the
smallest owner commit needed to make the no-flash UserAccess response and its
publication-failure branch satisfy this test without changing the oracle.
