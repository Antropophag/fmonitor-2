# Independent Gate 3 rereview — PILOT-SESSION-STORAGE-001 v10 LocalAuth lifecycle v3

- Date: 2026-09-03T23:14:36+03:00
- Reviewer: separately tasked agent `/root/session_local_auth_gate3_v3`
- Test/implementation author: not this reviewer
- Reviewed commit: `c9e25aec84ce0e83afa42c8db493b1bf61277396`
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v10, sections 3,
  6–9 and 11
- Public seam: raw HTTP through the production rapid router or the public
  injected-dependency entrypoint factory, with externally observed owner files
  and primitive events
- Prior records: LocalAuth lifecycle v1 and v2 `CHANGES_REQUESTED`
- Verdict: **APPROVED**

## Findings

The complete four-test set closes the two prior review records without changing
their approved direction. The lifecycle tracer covers anonymous GET, protected
GET with safe return-to, successful login regeneration and logout destruction
through the real router. The dedicated return-to tracer reopens the committed
owner artifact before login and requires the literal `/pilot/objects/4512`
value plus byte-identical whole-array serialization.

The new canonical tracer closes the remaining v2 blocker. It retains raw bytes
for both the initial anonymous state and the regenerated authenticated state,
decodes with classes disabled, and independently requires
`raw === serialize(decoded)` for each. It also binds the rendered CSRF to the
anonymous state and the literal actor ID/email to the regenerated state. Thus a
non-canonical encoding in either write path cannot pass merely because selected
decoded fields happen to agree.

The fault tracer reaches each changed LocalAuth mutation branch through raw
HTTP and the exact public dependency seam. It selects the first
`rename(committed)` failure for return-to `writeCommit`, and the first
`unlink(committed)` pre-invalidation failure for login `regenerate` and logout
`destroyCommit`. For each branch it requires status 503, the literal 21-byte
body, the complete section-6 application header set, absence of buffered
redirect/cookie and forbidden disclosure headers, one matching redacted
operation/artifact/ordinal/native-false event, and byte-identical preservation
of the old committed state. The pre-invalidation selection therefore has an
unambiguous expected validity outcome and is sensitive to premature response
publication or destructive mutation.

Expected status, paths, headers, body, actor facts, return-to and fault tuples
are literal specification/fixture values rather than computations copied from
production. Test routers only compose the same production graph with the four
permitted session ports; they neither dispatch a scenario nor synthesize an
owner result. Database namespaces and state roots are collision-resistant and
test-owned. Every independently reproduced RED reached its intended assertion,
which demonstrates successful DB/server/owner setup before the missing
LocalAuth integration. Finally blocks stop the owned process, drop only exact
prefix tables and remove only owned roots. The hard-coded legacy root remained
absent before and after all runs, and no task directory remained.

No blocking test-design, determinism, seam, sensitivity, traceability,
expected-value or cleanup finding remains for this LocalAuth lifecycle slice.

## Independent reproduction

```text
$ git rev-parse HEAD
c9e25aec84ce0e83afa42c8db493b1bf61277396

$ php -l <each of four LocalAuth tests and five support routers>
No syntax errors detected ...

$ php tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
INTENTIONAL_RED: LocalAuth GET uses configured owner
Expected: 200
Actual: 503
exit=255

$ php tests/InstallationProcess/pilot_session_storage_local_auth_return_to_001_test.php
INTENTIONAL_RED: owner LocalAuth redirects anonymous protected GET
Expected: 303
Actual: 401
exit=255

$ php tests/InstallationProcess/pilot_session_storage_local_auth_faults_001_test.php
INTENTIONAL_RED: LocalAuth write exact fault status
Expected: 503
Actual: 401
exit=255

$ php tests/InstallationProcess/pilot_session_storage_local_auth_canonical_001_test.php
INTENTIONAL_RED: canonical anonymous LocalAuth GET
Expected: 200
Actual: 503
exit=255

$ test ! -e /home/fmonitor/.local/state/fmonitor2/sessions # before and after
exit=0
$ find /tmp/fmonitor2-session-storage-tests -mindepth 1 -maxdepth 1 -type d
(no output)
$ git diff --check a2dd558..c9e25ae
exit=0
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
f8cfa30eace1eb62c8e32cc3a5d308019003088992746769e671e4635c8dcb9a  tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
965c7d7a0cbe88d60fa311b8542ef8bb1173e9009cf4952f002e185e23edaf09  tests/InstallationProcess/pilot_session_storage_local_auth_return_to_001_test.php
08676b53f6052cce06f33f5e2e3c48ae1f6bb559a9338f8f1174a5cd0a5bccad  tests/InstallationProcess/pilot_session_storage_local_auth_faults_001_test.php
60304fd0d10e49b0ff9d56c3c8d6d46907c3bb8ef148bc310b28a7849ccd0a00  tests/InstallationProcess/pilot_session_storage_local_auth_canonical_001_test.php
84b5392735ea96dd58cdab962a066374fd571819d4d78cae6b7f97276dcaf2c1  tests/Support/pilot_session_storage_local_auth_router.php
048d764f25d9609ed2b0c899d45b5adc187dd15c62215488918d43dd6555a6cd  tests/Support/pilot_session_storage_local_auth_fault_common.php
0c7ab38aafe880c85d6518546ffad75b470c49f5ec9ca55ee6ee829dfcca7d08  tests/Support/pilot_session_storage_local_auth_write_fault_router.php
4736aec39bf3c4eb5d3ccdda2f0232520eab3cf6859ddae78825db8ae710aeae  tests/Support/pilot_session_storage_local_auth_regenerate_fault_router.php
2e23f903635c26adbf52a82f2b4b8823974e790bb8a431fb419efe8a90c859f9  tests/Support/pilot_session_storage_local_auth_destroy_fault_router.php
8ad78b6769490868834b2fa7cc8b5a8fd2f00988307ab7dc730a14e09e9111f4  docs/operations/pilot-session-storage-local-auth-lifecycle-red-evidence-v2.md
0d6cf98f127b55dd208d4884003e31cbf1fafda35cfb2f6ad0f2836948aed369  docs/operations/pilot-session-storage-local-auth-lifecycle-red-evidence-v3.md
```

Gate 3 is approved for this exact package. Gate 4 may implement only the
reviewed LocalAuth lifecycle behavior without weakening these expectations.
