# PILOT-SESSION-STORAGE-001 v10 consumers — independent Gate 5 review v1

- Date: 2026-09-03T23:35:17+03:00
- Reviewer: separately tasked agent `/root/session_consumers_gate5`
- Implementation author: not this reviewer
- Reviewed HEAD: `cca68e1568e19d9245050e564b947797c9b7be7c`
- Reviewed consumer implementation: `a0ae2706112b6a2ac08fd006044c0d5a6dbfb53a`
- Reviewed command-session implementation: `f886f6e`
- Reviewed UserAccess implementations: `858a877`, `6ada05e`
- Gate 3 authorities: LocalAuth lifecycle v3, UserAccess flash v7 and
  UserAccess tokens v1 — all `APPROVED`
- Verdict: **CHANGES_REQUESTED**

## Closed behavior and architecture findings

The reviewed migration removes all prohibited native session calls and the
hard-coded session root from `app/` and `rapid-pilot/`. LocalAuth uses the same
injected storage instance as the production entrypoint, its canonical owner
payload supplies authentication, and write/regenerate/destroy complete before
their cookie or redirect is released. UserAccess invitation flash and action
tokens are read and committed through that owner; injected publication failure
discards the buffered success response. Authentication SQL was moved behind
`MariaDbLocalAuthRepository`, the allowed SQL adapter seam.

The focused raw-HTTP suite proves canonical anonymous/authenticated bytes,
safe return-to, old-ID invalidation, logout destruction, exact fault tuples,
one-shot flash and durable rendered action tokens. The protocol tracer remains
GREEN for asset/unknown-route priority and exact failure response. The
architecture ratchet is now GREEN (7/7), including its three focused
session-ownership policies, and no native-session fingerprint remains.

## Blocking findings

### SESSION-CONSUMERS-G5-01 — v10 payload validation is incomplete

`PilotSessionPayloadCodec::decode()` currently rejects references and objects,
but accepts floats, depth greater than 16 and more than 4096 entries. It also
does not require byte-identical re-encoding, does not count entries, and exposes
no checked encode operation for the LocalAuth/UserAccess/command-session write
and regenerate paths. Direct reproduction against the reviewed codec returned
the decoded `['x' => 1.5]`, `true` for a 17-level payload and `true` for a
4097-entry payload.

This contradicts v10 section 3 and the OpenSpec requirement that accepted state
contain only null/bool/int/string/array, have depth at most 16 and at most 4096
entries, be byte-identical after re-encoding, and pass the same shape check
before every write/regenerate. Since `a0ae270` makes LocalAuth depend on this
codec and writes raw `serialize($_SESSION)`, the complete consumer migration
cannot be approved from narrower object/reference and canonical-success tests.

### SESSION-CONSUMERS-G5-02 — injected factory dependencies are not authoritative

`PilotSessionRequestOwner` stores its owner in a process-global static and
`bind()` uses `self::$owner ??= $owner`. Therefore
`createWithSessionStorageDependencies()` silently ignores its explicitly
supplied filesystem/clock/entropy/observer whenever any earlier factory call in
the same process initialized the static owner. This violates v10 section 8's
public deterministic dependency seam and can make fault evidence observe the
wrong owner. A request-scoped composition must not retain or prefer a prior
factory's owner over the dependencies supplied for the current construction.

## Independent verification

Against the exact reviewed implementation (before the later, unrelated CSP
fixture-only commit):

```text
all 23 tests/InstallationProcess/pilot_session_storage*_test.php
PASS (23/23)

php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/process_user_directory_001_test.php
PASS: PROCESS-USER-DIRECTORY-001 production user directory

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
QUALITY_GRAPH_VALIDATION_OK digest=a6d37d59715b355c8e717ad6f06a71f50f09806dbd6a57dcfcdea7a0f0a8dbdf

php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer

three focused session-ownership architecture unit tests
Ran 3 tests; OK

git diff --check f886f6e^..cca68e1
exit 0
```

The uncommitted CSP-login fixture correction was outside reviewed production
scope and does not block this Gate 5 by itself. After its separate commit, its
host invocation reached a distinct environment setup failure resolving the
Compose-only hostname `mariadb`; that result is neither session GREEN nor a
consumer behavior regression and is not used as approval evidence.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
6f5d57d704b8f59ec40d71449853ced5961707aaba35b1530d9c7008855866f1  app/PilotHttp/PilotSessionPayloadCodec.php
00a40f5e4178c7b35ddbfe2d69fde4a50911b7eec4b3024952eb759a50489a76  app/PilotHttp/PilotSessionRequestOwner.php
f8cfa30eace1eb62c8e32cc3a5d308019003088992746769e671e4635c8dcb9a  tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
60304fd0d10e49b0ff9d56c3c8d6d46907c3bb8ef148bc310b28a7849ccd0a00  tests/InstallationProcess/pilot_session_storage_local_auth_canonical_001_test.php
08676b53f6052cce06f33f5e2e3c48ae1f6bb559a9338f8f1174a5cd0a5bccad  tests/InstallationProcess/pilot_session_storage_local_auth_faults_001_test.php
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
b4bfe456852e4799916878ca048de7c960eced1b1ab798c007935e8eeaa6ff94  tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
```

Gate 5 remains **CHANGES_REQUESTED** until both contract gaps are corrected
without weakening the approved tests, the relevant regression ladder stays
GREEN, and a fresh independent Gate 5 review approves the corrected exact SHA.
