# Independent Gate 3 review — PILOT-SESSION-STORAGE-001 v10 consumers v1

- Date: 2026-09-03T21:53:58+03:00
- Reviewer: separately tasked agent `/root/session_consumers_gate3_audit`
- Test/implementation author: not this reviewer
- Reviewed commit: `be1af61f17b6acd1e171d75a6034e08cb5ea568f`
- Scope: OpenSpec task 2.3 and v10 sections 3 and 6–11 for
  `RapidPilotLocalAuth`, `RapidPilotUserAccessView`, response buffering,
  native-session removal, host/image and Compose restart evidence
- Verdict: **CHANGES_REQUESTED**

## Blocking findings

There is no single current, v10-exact Gate 2 test/evidence set that is sufficient
to authorize implementation of both consumers.

1. `pilot_session_storage_user_access_fault_001_test.php` still seeds the v7/v8
   native `name|value` framing (`auth_user_id|i:...`), while v10 section 3 accepts
   only canonical `serialize()` bytes for the whole associative array. The test
   therefore cannot prove v10 payload handoff into `RapidPilotUserAccessView`.
   It also observes only a final injected write failure at the outer HTTP graph;
   that response can become 503 even while `UserAccessView` continues to use its
   private native session. It does not prove that the invitation flash is read,
   removed and explicitly committed through the sole owner, nor that a failed
   flash-removal commit discards the buffered 200 response.
2. The focused v10 accepted-payload test covers only `GET /pilot/login` and was
   correctly approved only for that narrow LocalAuth read/reuse behavior. It
   does not cover LocalAuth return-to mutation, login regeneration/old-ID
   invalidation, logout destruction, CSRF mutation, or the GET/HEAD/POST
   buffering matrix for those mutations. The older broad v5/v6 approvals name
   v8 hashes and are explicitly invalidated for the v10 payload amendment by
   sections 11 and OpenSpec tasks 1.7/2.3.
3. The protocol test proves route priority, initial-cookie behavior and exact
   injected 503 response on the current host PHP runtime. It does not bind the
   injected failure to each consumer's own mutation/commit boundary, so it
   cannot prevent success buffering around a still-native consumer. No current
   image execution record pins these exact test hashes.
4. The architecture ratchet v3 is independently approved and currently GREEN;
   it is sufficient to detect the remaining prohibited calls/root fingerprints,
   but a GREEN ratchet test is not a consumer behavioral RED. Current production
   still contains native session calls in `rapid-pilot/LocalAuth.php` and
   `rapid-pilot/UserAccessView.php`; their removal must follow a separately
   approved behavioral test, not merely the static rule.
5. The Compose verifier is an appropriate explicit shape: pre/post canonical
   inspector equality plus raw reuse of the original authenticated cookie. It
   remains v7-labelled, has no fresh execution/evidence for the current hashes,
   requires external credentials, and does not establish current-image GREEN at
   Gate 3. It may remain the disruptive final proof, but cannot fill the missing
   UserAccess vertical behavioral RED.

## Smallest next vertical RED

Add one raw-HTTP `RapidPilotUserAccessView` flash-consumption test through
`ProductionPilotHttpEntrypointFactory::createWithSessionStorageDependencies`:

- seed, through the real owner, a fixed canonical whole-array `serialize()`
  payload containing valid `auth_user_id`, `auth_email`, `auth_csrf` and a fixed
  `fm2_invitation_flash` URL;
- issue authenticated `GET /pilot/admin/users` and require that exact fixed flash
  in the buffered 200 body, then independently prove the owner committed its
  removal and a second GET does not contain it;
- repeat with a deterministic failure at that exact removal publication and
  require the exact section-6 503 for GET (no success bytes, `Location` or
  `Set-Cookie`, exact headers/body), while the pre-request committed payload and
  flash remain valid;
- record a fresh intended RED and exact hashes. The RED must fail because the
  current UserAccess consumer opens its private native root/session, not because
  of DB setup, routing, authentication, or a generic outer-owner write.

After that focused review is APPROVED, expand with separately reviewable
LocalAuth mutation cases (return-to write, successful regeneration and old-ID
invalidation, logout destroy) and exact HEAD/POST failure buffering before
claiming the whole of task 2.3. Image and real Compose runs are Gate 4/Done
evidence and must use the final exact reviewed hashes.

## Reproduction on reviewed HEAD

The following current tests all exited `0`:

```text
php tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 accepted payload raw HTTP

php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer

php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v7 authenticated UserAccess write failure

python3 -m unittest \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_native_session_and_hardcoded_root \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_rejects_internal_factory_callers \
  tools.architecture.tests.test_debt_fingerprint.DebtFingerprintTest.test_session_storage_ownership_allows_exact_internal_factory_owners
Ran 3 tests; OK
```

These GREEN outcomes confirm the existing slices; they do not supply the
missing sensitive RED described above. A source replay also finds prohibited
native lifecycle calls and compatibility roots in both named rapid-pilot
consumers, so task 3.2 is not already complete.

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
78d3ad3a82d4ac3a3ee80e72412a2aa31d101724b09e0fda5172ed0ba1ec1aef  openspec/changes/define-pilot-session-storage-contract/proposal.md
7c12ecc8c52f9ce411f57bf93270bc5fac09e35166e64482983e212122fb8ceb  openspec/changes/define-pilot-session-storage-contract/design.md
79f41f73ff2f64c52b4c07d0a10fb14cf09f2517650d97ffb5ab4a3f2ef0d1b2  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
8d9110610fe4eb9b36424633b8d8db7077c35f347e7313354a1109ab856abcd3  openspec/changes/define-pilot-session-storage-contract/tasks.md
9f18b7953d4426c42414d06186561972027e9dc238d13846b46574269507ab41  tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
315825c95c7ba4059b63e298bf3f710621ff9ffd3e33c57c4982fb43146204d3  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
65d1f8b6dd687bb8843009b9cdb83e5d3fa34469d7b97d0ccc1d88b7d52d2baf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
29bb1727ec97bfd6433e4deaafae611dc1fd7577cabf9f20bafb6fbc43501fdf  tests/Support/pilot_session_storage_http_fault_router.php
401e2885a38095a0d1e8428b5b8cc44c355d3fb32a1ebe18eae5becd6e6a82bb  tests/Support/pilot_session_storage_compose_restart_verifier.php
4355cf44e6748cc9940d2664b4a8736c976a1fa2f3d44b892a9426a15642948b  tools/architecture/tests/test_debt_fingerprint.py
1c97cc8900173d3c26b0dbe60cc8dfdd01e37722013471f9a672a532fd01ae1e  reviews/tests/PILOT-SESSION-STORAGE-001-v5.md
c7e33bb6ea2bbabbf0e3711f38254d12355833d26379403d3733d522f8d5a38c  reviews/tests/PILOT-SESSION-STORAGE-001-v6.md
ed596ff6516275d193d91a222399c402b5e5d097719524a3b47d7b586de148cc  reviews/tests/PILOT-SESSION-STORAGE-001-accepted-payload-v2.md
1422b952f3642060fb5b01a875c6d95e000c88e100e37f53983f4c624176107c  reviews/tests/PILOT-SESSION-STORAGE-001-architecture-ratchet-v3.md
```

Gate 3 for the complete v10 consumer migration remains **CHANGES_REQUESTED**.
