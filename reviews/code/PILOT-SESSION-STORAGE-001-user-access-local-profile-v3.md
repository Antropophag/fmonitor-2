# PILOT-SESSION-STORAGE-001 v10 UserAccess local-profile — independent Gate 5 v3

- Date: 2026-09-04T20:20:00+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate5_v3`
- Test/implementation author: not this reviewer
- Gate 3 authority: `a82e1f1c850f1bb2b27ce6c7da672b22803dd76a`, `APPROVED`
- Production commit: `051aa38`
- GREEN evidence commit: `2e32636`
- Verdict: **APPROVED**

## Review result

The production delta is exactly one line in
`app/PilotHttp/PilotE2ECoordinator.php`. For the canonical owner-backed
UserAccess request, it consumes the positive local actor identity already
injected by `ownerUserAccess()` from the decoded canonical session payload and
loads its display profile through `MariaDbLocalUserProfile`. That adapter
requires exactly one `fm2_pilot_users` row with matching numeric identity,
`status=1`, binary `activation_state='active'`, and a nonblank full name.
Legacy identity rows are therefore no longer positive authority or display
identity for this local-session path.

Authorization is not inferred from the profile. The existing independent
`AccessPolicy::ADMINISTER_ACCESS` check remains after profile resolution and
continues to require exact active local role/capability facts. A missing,
inactive, ambiguous, or unprivileged local actor fails closed. The predecessor
legacy-principal lookup remains only for requests which do not carry the
trusted local actor seam. Database acquisition and the existing broad fault
mapping remain inside the same guarded block, so profile, capability, CSS, and
directory infrastructure failures retain the redacted `503` response with
`Retry-After: 60`.

No second session owner, direct session-file read, native PHP session
lifecycle, response publication, flash/token behavior, application mutation,
or audit/history behavior was added or changed. The focused flash oracle still
proves one-shot owner publication and exact publish-fault rollback behavior;
the token oracle still proves tokens are committed through that same owner.
Both independently reviewed tests would fail if the local actor were sent back
through the legacy user directory, if exact capability enforcement were
removed, or if owner publication stopped being fail-closed.

The trusted `FMONITOR_AUTH_USER_ID` value in this path is constructed by
`ownerUserAccess()` from the canonical session state's integer
`auth_user_id`; ordinary client headers do not map to this unprefixed server
key. No new externally controlled identity seam is introduced by the delta.

## Independent verification

I reset the disposable canonical test database, applied the complete v1-v11
migration, and ran the focused and relevant session regressions at exact HEAD
`2e32636`:

```text
$ PATH="/Applications/Docker.app/Contents/Resources/bin:$PATH" make test-db-reset
TEST_DB_RESET_OK

$ PATH="/Applications/Docker.app/Contents/Resources/bin:$PATH" make migrate
{"ok":true,"schemaVersion":11,"appliedVersions":[1,2,3,4,5,6,7,8,9,10,11]}

$ php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess flash owner handoff

$ php tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess action tokens

$ php tests/InstallationProcess/pilot_session_storage_payload_handoff_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 owner payload handoff

$ php tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 accepted payload raw HTTP

$ php tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 object payload raw HTTP

$ php tests/InstallationProcess/pilot_session_storage_local_auth_canonical_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 LocalAuth canonical payloads

$ php tests/InstallationProcess/pilot_session_storage_local_auth_faults_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 LocalAuth fault boundaries

$ php tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 LocalAuth lifecycle

$ php tests/InstallationProcess/pilot_session_storage_local_auth_return_to_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 LocalAuth return-to owner commit

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ php -l app/PilotHttp/PilotE2ECoordinator.php
No syntax errors detected in app/PilotHttp/PilotE2ECoordinator.php

$ git diff --check a82e1f1c850f1bb2b27ce6c7da672b22803dd76a..2e32636
exit 0
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
cf209250e9eadd2edd2df9519793dbfaa1dc3ab6fe2475fdc5925ee08ba85215  app/PilotHttp/PilotE2ECoordinator.php
ab87b40d25b5e8b9980cb35468a4587d579fe0c82433960f696a996dc4cc4d29  app/PilotHttp/MariaDbLocalUserProfile.php
5e46f3046a180842fcd15e7165a4f850836b4be6a9db6981522cca4e7766d748  app/PilotHttp/AccessPolicy.php
81df24ba7d3aaf6562312a470cb05e1b631654f23c2b3d78a0e80cd9e4e4bbaf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
b4bfe456852e4799916878ca048de7c960eced1b1ab798c007935e8eeaa6ff94  tests/InstallationProcess/pilot_session_storage_user_access_tokens_001_test.php
c83b1c071b5ccd32b2f2db74493b6083545c6fdfda340374edc51a2d82efbefc  tests/Support/pilot_session_storage_user_access_router.php
a59b3812c70727233e1f27b4e5ad07eaa55641785ca07309b6865ab0de29de2b  tests/Support/pilot_session_storage_user_access_fault_router.php
9290a1014cf8fd08428b660f03b4abc0085137bc7c15fc547ee3034183e490e8  reviews/tests/PILOT-SESSION-STORAGE-001-user-access-css-v8.md
00914feff9852ff2513b52091deff85345bc870725e5832560eff1dc40b407db  docs/operations/pilot-session-storage-user-access-local-profile-green-2026-09-04.md
```

## Verdict

Gate 5 is **APPROVED** for production commit `051aa38` with evidence commit
`2e32636`. The bounded UserAccess local-profile correction conforms to the v10
session-owner, local identity, exact capability, response-publication, and
fail-closed requirements.
