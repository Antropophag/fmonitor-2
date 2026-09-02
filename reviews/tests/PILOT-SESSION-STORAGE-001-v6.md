# Independent Gate 3 test rereview v6 — PILOT-SESSION-STORAGE-001 v8

- Date: 2026-09-02
- Reviewer: separately tasked agent `/root/session_v8_test_rereview`
- Test author: separately tasked agent `/root/session_red_v5`
- Reviewed commit: dirty shared worktree; exact SHA-256 manifest below
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v8
- Prior approval: `reviews/tests/PILOT-SESSION-STORAGE-001-v5.md`
- Verdict: **APPROVED**

The reviewer did not author or edit the reviewed tests, support fixtures or
production implementation. This review record is the reviewer's only change in
this pass.

## Entropy-order correction

The corrected fixtures follow the approved protocol instead of adapting an
expectation to the bounded production WIP:

- anonymous start requests exactly 32 bytes for the generated session ID;
- normal write then requests 16 bytes for its stage token;
- regeneration requests 32 bytes for the candidate ID, 16 bytes for the stage
  token and 16 bytes for the revoked token before any optional 12-byte failure
  correlation;
- destroy collision fixtures supply only the required 16-byte revoked-token
  candidates, and failure-only paths retain independent 12-byte correlation
  material;
- length-keyed queues are used where multiple owners/actions make linear fixture
  order irrelevant, while the filesystem tracer independently asserts observed
  request order `[32, 16]`.

The correction does not weaken an assertion, select a production branch or
synthesize an owner result. No stale stage, revoked, committed, lock, DB fixture
or owned temporary root remained after the full replay.

## Full replay against bounded production WIP

All 14 auto-discovered tests ran individually under a 35-second outer timeout.
Ten are GREEN:

```text
pilot_session_storage_collisions_001_test.php
pilot_session_storage_concurrency_gc_001_test.php
pilot_session_storage_crash_001_test.php
pilot_session_storage_crash_regions_001_test.php
pilot_session_storage_filesystem_001_test.php
pilot_session_storage_gc_order_001_test.php
pilot_session_storage_inspector_001_test.php
pilot_session_storage_inspector_cli_application_001_test.php
pilot_session_storage_lock_clock_001_test.php
pilot_session_storage_validation_001_test.php
```

Four remain legitimate intended REDs for missing production behavior:

1. `pilot_session_storage_faults_001_test.php`: injected `flock` failure is
   currently returned as `OK`, rather than typed `UNAVAILABLE`.
2. `pilot_session_storage_lifecycle_faults_001_test.php`: injected revoked-link
   failure during regeneration is currently returned as `OK`.
3. `pilot_session_storage_protocol_001_test.php`: unknown asset currently
   returns `500`, rather than the required pre-session `404`.
4. `pilot_session_storage_user_access_fault_001_test.php`: the injected
   UserAccess production composition is not wired yet, so its verifier-owned
   HTTP child closes without the required exact `503` response.

The last case's empty raw response and secondary parser warning are downstream
evidence of the missing production composition, not a database, socket-listener
or fixture setup failure: the same run first seeded the authenticated opaque
session through the real owner, created its bounded fictional DB fixtures and
reached the owned HTTP child. Cleanup then removed both filesystem and DB
fixtures.

The v8 CLI application coverage remains GREEN for exact `64|65|70|0`, stream
mapping, real-inspector use for `0|65`, a throw-only inspection implementation
for `70`, exact argv forwarding, and native-only production-bin binding guards.
The prior storage/fault/lifecycle/collision/concurrency/GC/crash/inspector/raw
HTTP matrix and anti-self-attestation constraints remain present. The explicit
Compose verifier remains correctly outside auto-discovery; with the inspector
now present it exits `78` before Docker access because verifier credentials are
not supplied. Its restart, snapshot equality, authentication reuse, logout and
30-second Docker child deadlines remain statically intact.

Every reviewed PHP file passes `php -l`; strict OpenSpec validation and scoped
`git diff --check` pass. `/tmp/fmonitor2-session-storage-tests` and
`/tmp/fmonitor2-session-inspector-tests` were absent after replay.
The corrected exact test set remains approved for Gate 4. The ten GREEN tests
do not make the slice complete; the four intended REDs still require minimal
production work and the entire reviewed set must be GREEN before Gate 5.

## Exact reviewed hashes

```text
253597faa233e686f4a4e5d3e2af8029261dfe267e0f18930cd95008711d224d  specs/PILOT-SESSION-STORAGE-001.md
f5c29dd33e72b8e800d1fe73a48b6809ecb176d722b85a60766e3a4aadb979c9  openspec/changes/define-pilot-session-storage-contract/proposal.md
a2c4a24b31b17139438983eac1a33ca413c68998a021e8759b60d94f64bf9914  openspec/changes/define-pilot-session-storage-contract/design.md
4aa35bada03c1dfd0a1d31dce6e4d99309ce8b440adcc03f353c62c63c48ff56  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
988d2473c4799c9e8e7f5390de143c4aea1117f4be5e6ed152c1778f731dd610  openspec/changes/define-pilot-session-storage-contract/tasks.md
7c0977f79a396f184d906873f983b2228c3e117877b6e348f1a8b1eede6589bd  docs/operations/pilot-session-storage-red-evidence.md
58274d3dc080dbd0894fa5c7eb4e2e56ed9550700b3b672ba94d221b3b6fc397  tests/InstallationProcess/pilot_session_storage_collisions_001_test.php
2999604188b329c50c52baf96b8d546bac276928d5e64a5019604364b2c6269a  tests/InstallationProcess/pilot_session_storage_concurrency_gc_001_test.php
d4301758d88148aa3966600d38a085170918f589a1cbd71178d60737402c6fde  tests/InstallationProcess/pilot_session_storage_crash_001_test.php
5d798eb2cd0e48a6a69f69562875d008f1595b6a95779f0f07a652e1fd0ce652  tests/InstallationProcess/pilot_session_storage_crash_regions_001_test.php
207f9e0d75641ccfbe80aa7e30141b1887bff50ce8627876057f56b7323b06a8  tests/InstallationProcess/pilot_session_storage_faults_001_test.php
4f08c913a445b166ac6056a5655cdbe3e42a868d3bd270495d51708d77e4f86a  tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
37664c2f69c2e421fed1c7e9215dce082c55cf17ddedd54d52113eaf99ad737f  tests/InstallationProcess/pilot_session_storage_gc_order_001_test.php
f77b36b86954703456fd4f1786a06adc8a42da6b7ed04b53eefb35d09a81b6d0  tests/InstallationProcess/pilot_session_storage_inspector_001_test.php
c1d16665d18270ed54c69a51170a4173c3c4e2c2422682090e76bb4dbdf37ccb  tests/InstallationProcess/pilot_session_storage_inspector_cli_application_001_test.php
68793f24d16ec62b7e2745db24990ec6b09035862d000c6398aabfbda1157d74  tests/InstallationProcess/pilot_session_storage_lifecycle_faults_001_test.php
cc072cd1e03e1a8792294632b02ce6b53ff61da632e4905122e1d32783fe97ac  tests/InstallationProcess/pilot_session_storage_lock_clock_001_test.php
ce906b976d496a8bc4fa9bb785c2a4b1300ad40ebfe60ea217f88b0b00a8ae6f  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
65d1f8b6dd687bb8843009b9cdb83e5d3fa34469d7b97d0ccc1d88b7d52d2baf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
fd4a2ffe4405994013aa3cabac34d2acb53e323e9addfcb6bcf444d788c23b6c  tests/InstallationProcess/pilot_session_storage_validation_001_test.php
e37c1bb3a4f942714a663c48cf4c3037f8b292d1298994e37f44575f6e76526b  tests/Support/PilotSessionInspectorCliFixture.php
45da11a84240332ba563e14fe36e4fe06381d482c0010d35fe16f5d7805e5b43  tests/Support/PilotSessionStoragePublicApiFixture.php
401e2885a38095a0d1e8428b5b8cc44c355d3fb32a1ebe18eae5becd6e6a82bb  tests/Support/pilot_session_storage_compose_restart_verifier.php
1b9ce25924f86dd0415800f2084d79510c65b0d0a9983fa58d67322d34dbb6ec  tests/Support/pilot_session_storage_crash_runner.php
29bb1727ec97bfd6433e4deaafae611dc1fd7577cabf9f20bafb6fbc43501fdf  tests/Support/pilot_session_storage_http_fault_router.php
1339c22c25e251f192f8f0ff9e7eaac7fb21d213b65ffda1f9a9a998d3c0c3fc  tests/Support/pilot_session_storage_http_router.php
```
