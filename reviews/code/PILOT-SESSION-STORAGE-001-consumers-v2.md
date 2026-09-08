# PILOT-SESSION-STORAGE-001 v10 consumers — independent Gate 5 rereview v2

- Date: 2026-09-03T23:54:38+03:00
- Reviewer: separately tasked agent `/root/session_consumers_gate5_v2`
- Implementation/test author: not this reviewer
- Reviewed HEAD: `1dd4452b80b067e2052e90168038f84055eb2960`
- Consumer implementation: `a0ae2706112b6a2ac08fd006044c0d5a6dbfb53a`
- Codec/request-owner hardening: `2fcacc92303f8ac00ad9d3abb912b9cc4421fae2`
- Gate 3 authorities: LocalAuth lifecycle v3, UserAccess flash v7,
  UserAccess tokens v1 and codec/request-owner v3 — all `APPROVED`
- Prior Gate 5: `reviews/code/PILOT-SESSION-STORAGE-001-consumers-v1.md`
  — `CHANGES_REQUESTED`
- Verdict: **APPROVED**

## Rereview result

Both v1 blocking findings are closed without weakening an approved oracle.
`PilotSessionPayloadCodec` applies one exact recursive grammar on decode and
encode: only null/bool/int/string/array leaves, no references or objects,
depth at most 16, at most 4096 recursively reachable entries, and byte-exact
canonical re-encoding on decode. Boundary fixtures cover exact 16/4096 success
and 17/4097 rejection, including nested total counting. Every production
session write or regeneration in LocalAuth, UserAccess, the coordinator login
path and `PilotCommandSession` now obtains bytes through checked `encode()` and
fails closed when state cannot be encoded.

`PilotSessionRequestOwner::bind()` now rejects a second distinct owner with
`LogicException`; it can no longer silently discard explicit injected
dependencies. Both production factories assign the resulting owner to one
local `$owner` variable and pass that exact object to the coordinator and to
the LocalAuth closure. The public composition oracle proves the conflict
behavior without inspecting static or private state.

The earlier consumer findings remain closed: storage completion precedes
cookie/redirect publication, LocalAuth and UserAccess share the canonical
owner payload, invitation flash and action tokens persist through it, and
publication failures discard buffered success. The architecture ratchet is
GREEN at 7/7 and the production scan finds no native-session fingerprint or
unchecked direct session serialization outside the codec.

## Independent verification at exact HEAD

```text
$ git rev-parse HEAD
1dd4452b80b067e2052e90168038f84055eb2960

$ for f in tests/InstallationProcess/pilot_session_storage*_test.php; do php "$f"; done
PASS (23/23)

$ php tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 codec and request owner hardening

$ php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

$ php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/process_user_directory_001_test.php
PASS: PROCESS-USER-DIRECTORY-001 production user directory

$ FMONITOR_DB_HOST=127.0.0.1 FMONITOR_DB_PORT=23306 \
  FMONITOR_DB_NAME=fmonitor2_test FMONITOR_DB_USER=root \
  FMONITOR_DB_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_route_csp_login_001_test.php
pilot_route_csp_login_001_test: PASS

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
QUALITY_GRAPH_VALIDATION_OK digest=a6d37d59715b355c8e717ad6f06a71f50f09806dbd6a57dcfcdea7a0f0a8dbdf

$ git diff --check 9baede7..1dd4452
exit 0
```

The first host invocation of the CSP test used its Compose-only default
hostname `mariadb` and failed during database setup. Re-running the unchanged
test against the repository's active host database endpoint reached the raw
HTTP seam and passed; the setup-only failure is not treated as behavior
evidence.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
2bbe1e8a58308815cf3e6e388bc4534f28a619f4afaa4682014846ac3477d373  app/PilotHttp/PilotSessionPayloadCodec.php
61f56d0057adddf5a2a0e8ce1b7485108ec93efc48d9b8715dcb9e78e945589c  app/PilotHttp/PilotSessionRequestOwner.php
ed3fdc6c7e43949828fe3b2182c8973d5cc40cd575445376a43d939d7892ee90  app/PilotHttp/ProductionPilotHttpEntrypointFactory.php
a4d2d096a5e9449488c6d6d85a79927d070036ed8447b655f4bf247d90ec4198  app/PilotHttp/PilotE2ECoordinator.php
b102352f4e2e4c655d067ba89fd7a41df487d0e2ece00f440c002f4ee51c0caa  app/PilotHttp/PilotCommandSession.php
746f5167f3d7e1ae51cc140bc75a7cdf470c316aafbcf4cf09a41b52d4302ca1  rapid-pilot/LocalAuth.php
f9088b72e6c3cc3c7f42ee20527cb3e464afced7019c85cacc0f66deae18429f  tests/InstallationProcess/pilot_session_codec_request_owner_001_test.php
c5c3886805fe5050ea48e6dd2a1be0cf79312308eb75d3350d2dee9078118301  tests/InstallationProcess/pilot_route_csp_login_001_test.php
```

## Verdict

Gate 5 is **APPROVED** for exact reviewed HEAD `1dd4452`. The complete session
consumer migration satisfies the v10 owner, payload, fail-closed and
architecture requirements at the reviewed SHA.
