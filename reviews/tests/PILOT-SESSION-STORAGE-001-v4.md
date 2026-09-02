# Independent Gate 3 test rereview v4 — PILOT-SESSION-STORAGE-001 v7

- Date: 2026-09-02
- Reviewer: separately tasked agent `/root/session_v7_test_rereview_v4`
- Test author: separately tasked agent `/root/session_red_v5`
- Reviewed commit: dirty shared worktree; exact SHA-256 manifest below
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v7
- Verdict: **CHANGES_REQUESTED (Gate 1/test gap)**

The reviewer did not author or edit the specification, OpenSpec artifacts,
tests, support harness or production implementation. This review record is the
reviewer's only change to the slice.

## Blocking finding

### Inspector exit 70 is normative CLI behaviour but has no constructible oracle

Section 10 specifies an exact externally observable branch: an otherwise
uncaught internal failure must make
`bin/pilot-session-storage-inspect.php` exit `70`, write nothing to stdout and
write exactly `Inspection unavailable.\n` to stderr. The reviewed tests do not
exercise that branch. The three injected inspector-class cases at
`pilot_session_storage_inspector_001_test.php:8` establish only an
`InspectionResult::UNAVAILABLE`; they cannot establish the CLI catch boundary,
exit code, or stdout/stderr mapping.

This omission cannot be corrected honestly at Gate 2 with the currently
approved seam. The four-argument CLI always constructs its native filesystem,
and the contract expressly forbids an environment/argv/request selector for a
test adapter. Native identity/metadata/read failures are intentionally mapped to
exit `65`, not `70`; inducing an unrelated PHP/runtime failure is neither
deterministic nor an implementation-independent oracle. Class-seam coverage is
not declared equivalent to CLI coverage anywhere in the approved contract, and
cannot prove a process exit protocol in any case.

Return to Gate 1 and do one of the following explicitly:

1. add a narrow public CLI-runner/composition seam through which a verifier can
   supply an inspector (or primitive adapter) whose unexpected `Throwable` is
   caught by the same production CLI boundary, while the real executable binds
   only native dependencies; or
2. remove exit `70` from normative acceptance and document it as defensive,
   non-Gate-2 behaviour.

After owner approval of amended exact hashes, add the corresponding process
test and obtain a fresh Gate 3 review. Gate 4 remains closed.

## Disposition of the six v3 findings

1. **Gate 2 matrix:** materially closed for the previously named configuration,
   root mode, identity swap, short read, deterministic monotonic timeout,
   wall-clock reversal, GC overflow/order and representative close failure
   cases. The remaining exit-70 issue above is a Gate 1 constructibility gap.
2. **Lifecycle/collisions:** closed. The suite now covers all three eight-attempt
   collision families, stale regeneration, idempotent absent destroy,
   successful regeneration/destroy, old/neither/new crash regions and the
   dual-valid-ID prohibition.
3. **Event protocol:** closed. The real-owner trace independently asserts exact
   sequence, BEFORE/AFTER pairing, operation/artifact/hash equality, hash
   nullability, tuple ordinals, AFTER outcomes and the normal-write event set.
4. **Raw HTTP:** sufficiently closed across the auto-discovered raw-socket tests
   plus the explicit actual-Compose verifier: priority and exact 503 are tested
   in raw HTTP; cookie/CSRF/scheme/return-to and both consumers are covered; the
   disruptive verifier owns authenticated regeneration, restart reuse and
   logout cleanup. It remains correctly outside auto-discovery.
5. **Inspector matrix:** all previously named material cases are covered either
   at the exact CLI boundary or the approved inspector class seam, except the
   distinct exit-70 CLI branch described above.
6. **Compose deadlines:** closed. Every Docker child has a 30-second deadline,
   TERM grace, KILL fallback and reap; the verifier remains explicit.

## Sensitivity, independence and RED reproduction

- No test/support caller creates an owner operation result, owner filesystem
  event or inspector result. Test adapters create only approved primitive and
  entropy DTOs.
- Parent-side filesystem metadata/digests, raw socket responses and process
  state remain the evidence; the removed scenario dispatcher is absent.
- All 13 auto-discovered session-storage scripts were run under a 30-second
  outer deadline. Each exited `255` at an intended missing-production assertion,
  not a setup failure. The protocol tracer reached the new unknown-asset oracle
  and observed current `500` versus required `404`.
- The explicit Compose verifier exited `65` before Docker mutation because the
  required inspector executable is absent.
- `/tmp/fmonitor2-session-storage-tests` was absent after replay.
- All reviewed PHP files pass `php -l`; scoped `git diff --check` passes.

## Exact reviewed hashes

```text
74b4966946c73448aa1dd0e6d5e06993ed228599ce579eee54fc61739e48d920  specs/PILOT-SESSION-STORAGE-001.md
8c2a66a22cfdb672c5dcd3aee88e2cbf1b48dcc9187969855018e714afb7589a  openspec/changes/define-pilot-session-storage-contract/proposal.md
74c41cff1e55659f1fd8117b93d4bbd82683b92ae5e9f88c6e1f606419250a0f  openspec/changes/define-pilot-session-storage-contract/design.md
bd0b55401a4556b257bcd7bea5e4b29de47f9540bc2d8363e796e57bd9061e83  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
3c4d16845780f3b1246368bdd8d9ab06e205b880514e5013449989ccbd1d4310  docs/operations/pilot-session-storage-red-evidence.md
58274d3dc080dbd0894fa5c7eb4e2e56ed9550700b3b672ba94d221b3b6fc397  tests/InstallationProcess/pilot_session_storage_collisions_001_test.php
2999604188b329c50c52baf96b8d546bac276928d5e64a5019604364b2c6269a  tests/InstallationProcess/pilot_session_storage_concurrency_gc_001_test.php
d4301758d88148aa3966600d38a085170918f589a1cbd71178d60737402c6fde  tests/InstallationProcess/pilot_session_storage_crash_001_test.php
6a469400f54be6165aff1c56d39dc7282761b6238aa55537b9f88e34a9edc9e9  tests/InstallationProcess/pilot_session_storage_crash_regions_001_test.php
207f9e0d75641ccfbe80aa7e30141b1887bff50ce8627876057f56b7323b06a8  tests/InstallationProcess/pilot_session_storage_faults_001_test.php
3784d48a7c31f11abfece779b08ed8c401c9842310138fbfbc66a067c13078b5  tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
37664c2f69c2e421fed1c7e9215dce082c55cf17ddedd54d52113eaf99ad737f  tests/InstallationProcess/pilot_session_storage_gc_order_001_test.php
f77b36b86954703456fd4f1786a06adc8a42da6b7ed04b53eefb35d09a81b6d0  tests/InstallationProcess/pilot_session_storage_inspector_001_test.php
bc526e8e1d66a37194c5e5026270b944337ccb0ddb55b0daa1ecde651b6fd4a1  tests/InstallationProcess/pilot_session_storage_lifecycle_faults_001_test.php
cc072cd1e03e1a8792294632b02ce6b53ff61da632e4905122e1d32783fe97ac  tests/InstallationProcess/pilot_session_storage_lock_clock_001_test.php
ce906b976d496a8bc4fa9bb785c2a4b1300ad40ebfe60ea217f88b0b00a8ae6f  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
65d1f8b6dd687bb8843009b9cdb83e5d3fa34469d7b97d0ccc1d88b7d52d2baf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
fd4a2ffe4405994013aa3cabac34d2acb53e323e9addfcb6bcf444d788c23b6c  tests/InstallationProcess/pilot_session_storage_validation_001_test.php
31f0f334061cd2ef267e91a98bb5d1371ab321a655e5aa8f96e8dc8abb4615ad  tests/Support/PilotSessionStoragePublicApiFixture.php
401e2885a38095a0d1e8428b5b8cc44c355d3fb32a1ebe18eae5becd6e6a82bb  tests/Support/pilot_session_storage_compose_restart_verifier.php
1b9ce25924f86dd0415800f2084d79510c65b0d0a9983fa58d67322d34dbb6ec  tests/Support/pilot_session_storage_crash_runner.php
29bb1727ec97bfd6433e4deaafae611dc1fd7577cabf9f20bafb6fbc43501fdf  tests/Support/pilot_session_storage_http_fault_router.php
1339c22c25e251f192f8f0ff9e7eaac7fb21d213b65ffda1f9a9a998d3c0c3fc  tests/Support/pilot_session_storage_http_router.php
```
