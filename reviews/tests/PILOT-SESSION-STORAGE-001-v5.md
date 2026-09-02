# Independent Gate 3 test rereview v5 — PILOT-SESSION-STORAGE-001 v8

- Date: 2026-09-02
- Reviewer: separately tasked agent `/root/session_v8_test_rereview`
- Test author: separately tasked agent `/root/session_red_v5`
- Reviewed commit: dirty shared worktree; exact SHA-256 manifest below
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v8
- Verdict: **APPROVED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
tests, support harness or production implementation. This review record is the
reviewer's only change to the slice.

## Review result

The v4 blocker is closed. The new public CLI application seam makes the
otherwise-uncaught internal-failure branch deterministic without adding a
runtime selector to the production executable. The verifier proves exact exits
`64`, `65`, `70` and `0`, empty/opposite streams and exact diagnostics. Exit
`70` uses a throw-only `PilotSessionStorageInspection`; exits `0|65` use the
real inspector. The static executable guard requires the production bin to bind
the real inspector, CLI application and exact argv tail, and rejects the named
test/alternate dependency selectors. The executable remains a native-only
composition root; dependency injection exists at the application class seam.

All six finding families from v3 remain materially closed:

1. configuration, root ownership/mode/identity, short I/O, monotonic timeout,
   wall-clock reversal and GC overflow/order are covered;
2. normal, stale, absent, regeneration/destroy, all three eight-collision
   families and old/neither/new crash regions are covered;
3. exact primitive BEFORE/AFTER event sequences, tuple ordinals, hashes,
   outcomes and the happy-path operation set are asserted independently;
4. raw HTTP priority, exact 503, cookie/CSRF/scheme/return-to, both consumers,
   restart reuse and logout cleanup are covered across local and explicit
   disruptive verifiers;
5. inspector success, syntax, material-invalid/unavailable, redaction,
   identity-change/incomplete-read and internal-failure behavior are covered at
   their approved class or CLI boundary;
6. Docker children have bounded nonblocking collection, TERM grace, KILL
   fallback and reap behavior.

The test/support code cannot create owner operation results, owner filesystem
events or inspector results. Adapters produce only approved primitive/entropy
DTOs; the CLI throwable adapter supplies no success/failure result. Material,
metadata/digests, raw sockets and process state are independent evidence. No
scenario dispatcher or test-selected production route exists.

## RED reproduction and hygiene

- All 14 auto-discovered `pilot_session_storage_*_test.php` files were run
  individually under a 30-second outer timeout. Every file exited `255` at an
  intended missing owner/HTTP/CLI assertion, rather than setup failure or
  timeout.
- The protocol test first proves the known favicon and currently observes `500`
  instead of required `404` for the unknown-asset priority assertion.
- The explicit Compose restart verifier exited intended RED `65` because the
  approved inspector executable is absent, before any Docker mutation.
- `/tmp/fmonitor2-session-storage-tests` and
  `/tmp/fmonitor2-session-inspector-tests` were absent after replay.
- Every reviewed PHP file passes `php -l`.
- `openspec validate define-pilot-session-storage-contract --strict` passes.
- Scoped `git diff --check` passes.

These REDs are sensitive to the missing approved production behavior and admit
Gate 4. They are not evidence that implementation is GREEN.

## Exact reviewed hashes

```text
253597faa233e686f4a4e5d3e2af8029261dfe267e0f18930cd95008711d224d  specs/PILOT-SESSION-STORAGE-001.md
f5c29dd33e72b8e800d1fe73a48b6809ecb176d722b85a60766e3a4aadb979c9  openspec/changes/define-pilot-session-storage-contract/proposal.md
a2c4a24b31b17139438983eac1a33ca413c68998a021e8759b60d94f64bf9914  openspec/changes/define-pilot-session-storage-contract/design.md
4aa35bada03c1dfd0a1d31dce6e4d99309ce8b440adcc03f353c62c63c48ff56  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
988d2473c4799c9e8e7f5390de143c4aea1117f4be5e6ed152c1778f731dd610  openspec/changes/define-pilot-session-storage-contract/tasks.md
98f2911f9bc3cee184fb4bd01c5da8a7fa6ce9afd4fc7283c20bc50e8acff76d  docs/operations/pilot-session-storage-red-evidence.md
58274d3dc080dbd0894fa5c7eb4e2e56ed9550700b3b672ba94d221b3b6fc397  tests/InstallationProcess/pilot_session_storage_collisions_001_test.php
2999604188b329c50c52baf96b8d546bac276928d5e64a5019604364b2c6269a  tests/InstallationProcess/pilot_session_storage_concurrency_gc_001_test.php
d4301758d88148aa3966600d38a085170918f589a1cbd71178d60737402c6fde  tests/InstallationProcess/pilot_session_storage_crash_001_test.php
6a469400f54be6165aff1c56d39dc7282761b6238aa55537b9f88e34a9edc9e9  tests/InstallationProcess/pilot_session_storage_crash_regions_001_test.php
207f9e0d75641ccfbe80aa7e30141b1887bff50ce8627876057f56b7323b06a8  tests/InstallationProcess/pilot_session_storage_faults_001_test.php
3784d48a7c31f11abfece779b08ed8c401c9842310138fbfbc66a067c13078b5  tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
37664c2f69c2e421fed1c7e9215dce082c55cf17ddedd54d52113eaf99ad737f  tests/InstallationProcess/pilot_session_storage_gc_order_001_test.php
f77b36b86954703456fd4f1786a06adc8a42da6b7ed04b53eefb35d09a81b6d0  tests/InstallationProcess/pilot_session_storage_inspector_001_test.php
c1d16665d18270ed54c69a51170a4173c3c4e2c2422682090e76bb4dbdf37ccb  tests/InstallationProcess/pilot_session_storage_inspector_cli_application_001_test.php
bc526e8e1d66a37194c5e5026270b944337ccb0ddb55b0daa1ecde651b6fd4a1  tests/InstallationProcess/pilot_session_storage_lifecycle_faults_001_test.php
cc072cd1e03e1a8792294632b02ce6b53ff61da632e4905122e1d32783fe97ac  tests/InstallationProcess/pilot_session_storage_lock_clock_001_test.php
ce906b976d496a8bc4fa9bb785c2a4b1300ad40ebfe60ea217f88b0b00a8ae6f  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
65d1f8b6dd687bb8843009b9cdb83e5d3fa34469d7b97d0ccc1d88b7d52d2baf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
fd4a2ffe4405994013aa3cabac34d2acb53e323e9addfcb6bcf444d788c23b6c  tests/InstallationProcess/pilot_session_storage_validation_001_test.php
e37c1bb3a4f942714a663c48cf4c3037f8b292d1298994e37f44575f6e76526b  tests/Support/PilotSessionInspectorCliFixture.php
31f0f334061cd2ef267e91a98bb5d1371ab321a655e5aa8f96e8dc8abb4615ad  tests/Support/PilotSessionStoragePublicApiFixture.php
401e2885a38095a0d1e8428b5b8cc44c355d3fb32a1ebe18eae5becd6e6a82bb  tests/Support/pilot_session_storage_compose_restart_verifier.php
1b9ce25924f86dd0415800f2084d79510c65b0d0a9983fa58d67322d34dbb6ec  tests/Support/pilot_session_storage_crash_runner.php
29bb1727ec97bfd6433e4deaafae611dc1fd7577cabf9f20bafb6fbc43501fdf  tests/Support/pilot_session_storage_http_fault_router.php
1339c22c25e251f192f8f0ff9e7eaac7fb21d213b65ffda1f9a9a998d3c0c3fc  tests/Support/pilot_session_storage_http_router.php
```
