# PILOT-SESSION-STORAGE-001 v8 — Gate 2 RED evidence

Date: 2026-09-02  
RED author: `/root/session_red_v5`  
Status: **INTENDED RED / READY FOR INDEPENDENT TEST REVIEW OF CURRENT TRACERS**

## Approved admission

```text
253597faa233e686f4a4e5d3e2af8029261dfe267e0f18930cd95008711d224d  specs/PILOT-SESSION-STORAGE-001.md
f5c29dd33e72b8e800d1fe73a48b6809ecb176d722b85a60766e3a4aadb979c9  openspec/changes/define-pilot-session-storage-contract/proposal.md
a2c4a24b31b17139438983eac1a33ca413c68998a021e8759b60d94f64bf9914  openspec/changes/define-pilot-session-storage-contract/design.md
4aa35bada03c1dfd0a1d31dce6e4d99309ce8b440adcc03f353c62c63c48ff56  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
```

Approval is append-only in
`docs/operations/pilot-session-storage-v8-exact-hash-approval-2026-09-02.md`.
No production file was authored by this RED role.

## Test hashes

```text
e37c1bb3a4f942714a663c48cf4c3037f8b292d1298994e37f44575f6e76526b  tests/Support/PilotSessionInspectorCliFixture.php
45da11a84240332ba563e14fe36e4fe06381d482c0010d35fe16f5d7805e5b43  tests/Support/PilotSessionStoragePublicApiFixture.php
1b9ce25924f86dd0415800f2084d79510c65b0d0a9983fa58d67322d34dbb6ec  tests/Support/pilot_session_storage_crash_runner.php
29bb1727ec97bfd6433e4deaafae611dc1fd7577cabf9f20bafb6fbc43501fdf  tests/Support/pilot_session_storage_http_fault_router.php
1339c22c25e251f192f8f0ff9e7eaac7fb21d213b65ffda1f9a9a998d3c0c3fc  tests/Support/pilot_session_storage_http_router.php
401e2885a38095a0d1e8428b5b8cc44c355d3fb32a1ebe18eae5becd6e6a82bb  tests/Support/pilot_session_storage_compose_restart_verifier.php
4f08c913a445b166ac6056a5655cdbe3e42a868d3bd270495d51708d77e4f86a  tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
207f9e0d75641ccfbe80aa7e30141b1887bff50ce8627876057f56b7323b06a8  tests/InstallationProcess/pilot_session_storage_faults_001_test.php
fd4a2ffe4405994013aa3cabac34d2acb53e323e9addfcb6bcf444d788c23b6c  tests/InstallationProcess/pilot_session_storage_validation_001_test.php
cc072cd1e03e1a8792294632b02ce6b53ff61da632e4905122e1d32783fe97ac  tests/InstallationProcess/pilot_session_storage_lock_clock_001_test.php
68793f24d16ec62b7e2745db24990ec6b09035862d000c6398aabfbda1157d74  tests/InstallationProcess/pilot_session_storage_lifecycle_faults_001_test.php
d4301758d88148aa3966600d38a085170918f589a1cbd71178d60737402c6fde  tests/InstallationProcess/pilot_session_storage_crash_001_test.php
5d798eb2cd0e48a6a69f69562875d008f1595b6a95779f0f07a652e1fd0ce652  tests/InstallationProcess/pilot_session_storage_crash_regions_001_test.php
2999604188b329c50c52baf96b8d546bac276928d5e64a5019604364b2c6269a  tests/InstallationProcess/pilot_session_storage_concurrency_gc_001_test.php
58274d3dc080dbd0894fa5c7eb4e2e56ed9550700b3b672ba94d221b3b6fc397  tests/InstallationProcess/pilot_session_storage_collisions_001_test.php
37664c2f69c2e421fed1c7e9215dce082c55cf17ddedd54d52113eaf99ad737f  tests/InstallationProcess/pilot_session_storage_gc_order_001_test.php
65d1f8b6dd687bb8843009b9cdb83e5d3fa34469d7b97d0ccc1d88b7d52d2baf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
f77b36b86954703456fd4f1786a06adc8a42da6b7ed04b53eefb35d09a81b6d0  tests/InstallationProcess/pilot_session_storage_inspector_001_test.php
ce906b976d496a8bc4fa9bb785c2a4b1300ad40ebfe60ea217f88b0b00a8ae6f  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
c1d16665d18270ed54c69a51170a4173c3c4e2c2422682090e76bb4dbdf37ccb  tests/InstallationProcess/pilot_session_storage_inspector_cli_application_001_test.php
```

The old scenario dispatcher and fabricated-result runners are absent. Test
adapters return only approved primitive DTOs. Owner results are never created by
test code; material, raw HTTP and process state are observed independently.

## Reproduced RED outcomes

All commands exit `255` for a missing approved production behavior:

- filesystem: `PilotSessionStorageConfig` absent;
- DTO/fault: `PilotSessionStorageFactory` absent;
- crash: real owner absent before child launch;
- lifecycle and concurrency/GC: real owner absent before setup mutation/fork;
- inspector: exact CLI absent (`Could not open input file`, observed exit `1`
  rather than contract exit `0` and canonical output);
- raw HTTP: real router returns `500` rather than exact `503` after the favicon
  bypass assertion succeeds.

Final matrix additions reproduce independently:

```text
pilot_session_storage_collisions_001_test.php       exit 255: v7 collision owner absent
pilot_session_storage_gc_order_001_test.php         exit 255: v7 GC owner absent
pilot_session_storage_user_access_fault_001_test.php exit 255: v7 authenticated UserAccess owner absent
```

The fault tracer encodes exact backed enum accessors, readonly DTO reflection,
closed `IO_ERROR` warning/exception construction, entropy failure and direct
real-owner injections across root/session mkdir/lstat/list, lock open/flock,
stage open/write/fflush/fsync/fstat/close and publish link/unlink/directory-fsync.
Each tuple is selected only by exact operation/artifact/ordinal. The crash tracer directly commits an old session, launches a
child invoking `regenerate`, pauses only on exact
`AFTER/UNLINK/COMMITTED/OK`, kills/reaps it with deadlines, then independently
requires old/new committed names absent and a non-addressable tombstone.

The inspector test computes the literal canonical envelope independently from
`lstat` and SHA-256, tests exact argv/exit/stdout/stderr protocol, redaction and
unknown-entry fail-closed behavior with before/after material equality. The HTTP
test uses real raw sockets and includes a verifier-only router that explicitly
calls `createWithSessionStorageDependencies`; no environment/request value
selects that method.

The lifecycle extension seeds real committed material, then independently
drives read/mtime/rename and regenerate/destroy revoked-link/committed-unlink
faults. The concurrency tracer releases two real owners simultaneously against
absent descendants and requires both distinct payloads as committed files; its
GC branch creates 10001 owned entries and requires `GC_FAILED` with an unchanged
count. The explicit (non-auto-discovered) Compose verifier performs a real
authenticated login and UserAccessView read, takes the exact inspector snapshot,
ordinary `docker compose stop/start pilot`, requires byte-identical canonical
snapshot before cookie reuse, then proves the original authenticated cookie on
UserAccessView. It currently exits intended RED `65` before Docker mutation
because the approved inspector CLI is absent; credentials are required only
after that production predecessor exists.

## Hygiene and cleanup

All PHP artifacts pass `php -l`; scoped `git diff --check` passes. After
each reproduced failure both `/tmp/fmonitor2-session-storage-tests` and
`/tmp/fmonitor2-session-inspector-tests` are absent. HTTP children and crash
children have bounded terminate/kill/reap paths.

## Honest remaining scope

Tasks 2.2 and 2.3 remain open pending independent review and expansion findings.
The suite encodes every named primitive across normal/lifecycle/GC/crash
tracers; old-only, neither-valid and new-only crash regions; exact 100-file
oldest-then-binary GC selection; all three eight-attempt collision families;
injected-dependency LocalAuth paths; an authenticated UserAccess invitation
whose session commit fails; exact inspector CLI; and actual Compose restart.
The authenticated UserAccess verifier seeds its opaque PHP session only through
the real owner, creates fictional canonical DB fixtures with bounded cleanup,
sends a raw POST through `createWithSessionStorageDependencies`, and requires
503 with no success bytes, `Location` or `Set-Cookie`.

Tasks 2.2/2.3 remain open solely because Gate 3 explicitly requires a fresh
independent `APPROVED` review of these exact hashes. The RED author does not
self-approve or advance Gate 4.

## v3 review corrections

After `reviews/tests/PILOT-SESSION-STORAGE-001-v3.md` returned
`CHANGES_REQUESTED`, the verifier gained:

- invalid config/root-mode and deterministic lstat identity-swap cases, plus a
  sequence clock with a real externally held lock and bounded monotonic timeout;
- short-read injection support, successful/stale/idempotent lifecycle facts and
  the separately required eight-collision destroy tombstone family;
- exact event sequence, BEFORE/AFTER pairing, operation/artifact equality,
  hash nullability, tuple ordinal and AFTER-outcome assertions, with an
  independently required happy-path operation/artifact set;
- raw unknown-asset, malformed Host and malformed URI priority REDs; the
  explicit Compose verifier now proves positive cookie/CSRF/safe-return-to,
  authenticated UserAccess and regeneration/restart/logout flow;
- inspector coverage for empty state, all four logical types, emitted-hash
  binary sorting, redaction, missing root, unknown entry, wrong mode and
  symlink identity with before/after material equality;
- nonblocking stdout/stderr collection and a 30-second deadline for every
  Docker command, followed by TERM, bounded grace, KILL and reap.

The refreshed protocol RED now stops earlier at the newly asserted unknown
asset priority (`expected 404`, current `500`); the known favicon byte assertion
still passes first. This remains missing approved behavior, not setup failure.

Final bounded replay ran every `pilot_session_storage_*_test.php` under
`timeout 20`: all fourteen exit `255` at their intended missing production
owner/HTTP/CLI assertion. The explicit Compose verifier exits `65` before any
Docker mutation because the inspector CLI is absent. Both owned temp parents
are absent after replay. The v8 CLI application test now deterministically
proves exact `64|65|70|0` with owned argv/output ports, real inspector for
`0|65`, and a throw-only inspection implementation for `70`. It statically
requires the production bin to bind the real inspector, CLI application and
exact `array_slice($argv, 1)`, while rejecting test/alternate selectors.
Identity-change and
incomplete-read sensitivity use the approved inspector class with injected
primitive adapters; real-CLI symlink/type/mode cases independently cover the
material boundary.

## Entropy-order correction against bounded production WIP

After production reached the public owner seam, the previous lifecycle helper
exposed a verifier defect: it interleaved a 12-byte correlation token into a
successful regeneration before the required 32-byte candidate. The fixtures
now use length-specific independent queues for multi-operation lifecycles.
The basic filesystem tracer supplies and asserts exactly `[32,16]` for anonymous
ID then write stage. Failure tracers retain `[32,16,12]` only where the injected
write failure requires a correlation ID. Regeneration supplies candidate 32,
stage 16 and tombstone 16 in that order; correlation 12 is available only for
an actual failure. Collision queues remain keyed by the exact requested length.

Bounded replay against current production WIP now yields GREEN for the basic
filesystem, crash, concurrency, collision, GC, lock/clock, validation,
inspector and v8 CLI tracers. Intended RED remains independently at primitive
flock mapping, regeneration revoked-link fault mapping, unknown-asset priority
and authenticated UserAccess raw HTTP handling. Cleanup parents remain absent.
These GREEN partials do not authorize Gate 4 or change task 2.3; all refreshed
hashes require a new independent Gate 3 review.
