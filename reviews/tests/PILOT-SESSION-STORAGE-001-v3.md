# Independent Gate 3 test rereview — PILOT-SESSION-STORAGE-001 v7

- Date: 2026-09-02
- Reviewer: separately tasked agent `/root/session_v7_test_review`
- Test author: separately tasked agent `/root/session_red_v5`
- Reviewed commit: dirty shared worktree; exact SHA-256 manifest below
- Specification: `PILOT-SESSION-STORAGE-001` v7, owner-approved exact hash
- Public seam: `PilotSessionStorageFactory`, real `PilotSessionStorage`, injected real HTTP graph, read-only inspector CLI and explicit Compose verifier
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
tests, support harness or production implementation. This review record is the
reviewer's only change to the slice.

## Findings

### 1. Blocking — the required Gate 2 matrix is not present

The approved contract requires Gate 2 to fault-inject configuration,
mkdir/EEXIST/swap, every primitive phase, deterministic forward/backward wall
time and monotonic lock deadlines, destroy and GC (`specs/...`, section 11,
lines 814–823). The current fault table
(`pilot_session_storage_faults_001_test.php:24-40`) covers only a subset of
first-call write paths. In particular there is no observable test for trusted
root/config rejection, EEXIST revalidation or identity swap, short read,
nonblocking lock retry/2.000-second timeout, backward/forward wall values,
wrong owner/mode/type, locked/newer/foreign GC preservation, or close failures
for all live handles.

This is sensitivity, not documentation completeness: a production owner that
omits those validations/deadlines can satisfy the current assertions. Add
independent material/event oracles for the omitted normative paths.

### 2. Blocking — lifecycle/collision coverage permits wrong destroy and regeneration implementations

`pilot_session_storage_collisions_001_test.php:8-13` checks generated-ID
collisions and regeneration candidate/tombstone collisions, but never the
separately normative eight-attempt destroy tombstone collision. The lifecycle
fault test checks only seven tuples and generally asserts only that the old
committed pathname remains. It does not prove the complete before/after-unlink
state, no-clobber behavior, directory durability boundaries, idempotent absent
destroy, stale regeneration, or dual-valid-ID prohibition over successful
regeneration/destroy.

The crash-region tests are useful independent parent-owned evidence and have
bounded kill/reap paths, but they do not replace the missing ordinary lifecycle
matrix.

### 3. Blocking — event assertions do not verify the approved event protocol

The principal event assertion is only `count >= 2` plus absence of `path()` and
`sessionId()` methods (`pilot_session_storage_faults_001_test.php:58-59`). The
filesystem tracer makes the same shape-only checks. No test independently
asserts monotonically increasing sequence, exact BEFORE/AFTER pairing, exact
operation/artifact, nullable versus 64-hex session hash, per-tuple ordinal, or
the AFTER primitive outcome. An implementation emitting arbitrary safe-looking
events would pass.

The observer itself cannot return an owner result, and repository search found
no test/support calls to the owner-only operation-result/event factories. The
anti-self-attestation direction is therefore sound, but the independent event
oracle is incomplete.

### 4. Blocking — raw HTTP coverage does not establish the session protocol

`pilot_session_storage_protocol_001_test.php:15-24` proves asset priority and
the exact generic 503 for GET/HEAD/POST, then exercises one injected stage-fsync
failure. The authenticated UserAccess test adds one invitation-write failure.
No current raw-HTTP test proves successful LocalAuth anonymous start/login
regeneration/logout destroy, old-ID invalidation, cookie name/attributes,
trusted `https` versus `http`, CSRF and safe return-to preservation, malformed
Host/URI priority, unknown asset/non-asset priority, or ordinary-write commit
ordering. Those are explicit section 7 and section 11 requirements, and both
consumers must use the single owner.

### 5. Blocking — inspector test is one-entry happy-path plus one generic failure

`pilot_session_storage_inspector_001_test.php:8-11` checks one committed entry,
four argv errors and one unknown filename. It does not prove all four logical
types, binary ordering by emitted hash, empty entries, duplicate emitted-key
failure, wrong type/uid/mode, identity change, incomplete read/digest failure,
missing paths without creation, or the exact internal-failure exit `70`.
Several incorrect inspectors could satisfy the current oracle.

### 6. Blocking — explicit Compose verifier has unbounded child commands

`pilot_session_storage_compose_restart_verifier.php` gives HTTP calls a timeout,
but `pcsCmd()` waits indefinitely while draining `docker compose ps`, `exec`,
`stop`, or `start`. Section 11 requires owned processes to have bounded
stop/reap behavior. Add a verifier-owned deadline with TERM/KILL/reap and retain
the rule that this disruptive verifier is explicit, not auto-discovered.

## Positive checks

- Owner-approved v7 specification and OpenSpec content hashes match the
  append-only approval record.
- The old scenario dispatcher/fabricated child JSON mechanism is absent.
- Test adapters return only primitive/entropy DTOs; no test-owned factory call
  creates an owner operation result, owner event or inspection result.
- Crash evidence is parent-owned and material; crash children have bounded
  pause/kill/reap cleanup.
- All reviewed PHP files pass `php -l`; scoped `git diff --check` passes.
- All eleven auto-discovered test scripts reproduce intended missing-production
  RED (exit 255), not setup failure. The explicit Compose verifier exits 65 on
  the missing inspector before Docker mutation. Temporary task roots were
  absent after reproduction.

## Required changes

1. Complete the exact Gate 2 matrix named in sections 1–11, especially the
   omissions in findings 1–5, with independent material/raw-HTTP/event oracles.
2. Add bounded termination/reaping to every external command in the explicit
   Compose verifier.
3. Refresh RED evidence and request a new independent review of the resulting
   exact hashes. Gate 4 remains closed.

## Reproduced commands and outcomes

```text
php -l tests/Support/PilotSessionStoragePublicApiFixture.php
php -l tests/Support/pilot_session_storage_*.php
php -l tests/InstallationProcess/pilot_session_storage_*.php
# all: syntax clean

timeout 20 php tests/InstallationProcess/pilot_session_storage_<each>_test.php
# eleven tests: exit 255, intended missing owner/CLI/HTTP-503 behavior

timeout 20 php tests/Support/pilot_session_storage_compose_restart_verifier.php
# exit 65: INTENTIONAL_RED: exact inspector CLI is missing
```

## Exact reviewed hashes

```text
74b4966946c73448aa1dd0e6d5e06993ed228599ce579eee54fc61739e48d920  specs/PILOT-SESSION-STORAGE-001.md
8c2a66a22cfdb672c5dcd3aee88e2cbf1b48dcc9187969855018e714afb7589a  openspec/changes/define-pilot-session-storage-contract/proposal.md
74c41cff1e55659f1fd8117b93d4bbd82683b92ae5e9f88c6e1f606419250a0f  openspec/changes/define-pilot-session-storage-contract/design.md
bd0b55401a4556b257bcd7bea5e4b29de47f9540bc2d8363e796e57bd9061e83  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
158f8c06c93aec45e9a853670b7ff8f00f857f980d5e2780b9a4002d30cdfcbe  tests/InstallationProcess/pilot_session_storage_collisions_001_test.php
2999604188b329c50c52baf96b8d546bac276928d5e64a5019604364b2c6269a  tests/InstallationProcess/pilot_session_storage_concurrency_gc_001_test.php
d4301758d88148aa3966600d38a085170918f589a1cbd71178d60737402c6fde  tests/InstallationProcess/pilot_session_storage_crash_001_test.php
6a469400f54be6165aff1c56d39dc7282761b6238aa55537b9f88e34a9edc9e9  tests/InstallationProcess/pilot_session_storage_crash_regions_001_test.php
207f9e0d75641ccfbe80aa7e30141b1887bff50ce8627876057f56b7323b06a8  tests/InstallationProcess/pilot_session_storage_faults_001_test.php
4902058ed4aed23e73b4402a109048ac491de96acca72a733ff30181e132b74c  tests/InstallationProcess/pilot_session_storage_filesystem_001_test.php
37664c2f69c2e421fed1c7e9215dce082c55cf17ddedd54d52113eaf99ad737f  tests/InstallationProcess/pilot_session_storage_gc_order_001_test.php
167473c8b7498b07d16690c017343a1e282ab39726039d88c908838cb3b93fec  tests/InstallationProcess/pilot_session_storage_inspector_001_test.php
17baddd6e93dcfb1294d96ccb4b77e13c425cbdb10c15b2cdb937d517c916914  tests/InstallationProcess/pilot_session_storage_lifecycle_faults_001_test.php
df014511a5bcb92d17712b368c998a33e8f11115b99f6e448dc97a48d50cc090  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
65d1f8b6dd687bb8843009b9cdb83e5d3fa34469d7b97d0ccc1d88b7d52d2baf  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
95a08fbc0e252ab1bfb9a06540d85f67e7ccf7825ff5c79ca628b922c78a2b56  tests/Support/PilotSessionStoragePublicApiFixture.php
f6c7db19aa2806e0989d7855b1cfb83affc95d8fcf2600eefdaeabbe59849ac6  tests/Support/pilot_session_storage_compose_restart_verifier.php
1b9ce25924f86dd0415800f2084d79510c65b0d0a9983fa58d67322d34dbb6ec  tests/Support/pilot_session_storage_crash_runner.php
29bb1727ec97bfd6433e4deaafae611dc1fd7577cabf9f20bafb6fbc43501fdf  tests/Support/pilot_session_storage_http_fault_router.php
```
