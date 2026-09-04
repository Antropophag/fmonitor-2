# Independent Gate 5 review — PILOT-SESSION-STORAGE-001 v10 sequential write identity

- Date: 2026-09-04
- Reviewer: separately tasked agent `/root/session_sequential_gate5`
- Independence: reviewer did not author or edit the specification, test, RED
  evidence, Gate 3 review, production implementation, or GREEN evidence
- Reviewed production commit: `d66fc17575e934ff8f13d883b398655bbecfaadb`
- Reviewed parent: `4ecf15948fa9416986f8ec58020d28a60d35b9df`
- Approved Gate 3 v2: `273e7feeea17b375561d5dc6850851460e00aeab`
- Reviewed RED commit: `9facee6ff2d47bdbde3253b8916b33a8222faf11`
- Verdict: **APPROVED**

## Scope and conformance

The production diff is one state transition in
`FilesystemPilotSessionStorage::writeCommit`: after and only after
`publish(...)` returns success, it removes the private `anonymousIds` marker
for the accepted ID before returning `ownerWriteCommitted(id)`.

This is the minimal conforming correction for the reviewed sequential-write
RED. `start(null)` still marks the generated candidate as anonymous so an
already-existing committed target cannot become positive authority. A first
successful no-clobber publication converts that candidate into the accepted
current session. A later `writeCommit` for the same ID now takes the existing
session update path, retaining the cookie identity, instead of treating the
owner's own committed file as a collision and allocating an orphan identity.

The marker is not cleared on lstat failure, pre-existing collision, entropy
failure, stage/write/fsync/link/unlink/directory-fsync failure, or collision
exhaustion. Those paths retain their typed fail-closed behavior and cannot
turn a failed candidate into an update-capable accepted session. Collision
retry still marks each fresh internally generated candidate; no caller value
or cookie can acquire anonymous-candidate authority. The change does not alter
cookie construction, committed filenames or material, stage/tombstone
lifecycle, event correlation identity, entropy limits, response buffering,
regeneration, destroy, GC, or HTTP failure projection.

## Test sensitivity and regression review

The Gate 3 v2 test exercises the public `PilotCommandSession` and real
filesystem owner. It independently checks that two successful writes leave
one committed file at the original Set-Cookie identity, that its exact
whole-array bytes contain the second token, that a real reopen under the same
cookie returns those bytes, that entropy requests are exactly `[32,16,16]`,
and that every committed-file primitive event retains the original cookie
hash. The recorded predecessor RED (`[32,16,32,16]`, stale cookie material and
an alternate committed identity) demonstrates sensitivity to the exact defect.

The fresh broad run covers all 24 `pilot_session_storage*_test.php` files,
including collision families, fault categories, crash regions, lifecycle,
configuration/revalidation/swap, protocol, LocalAuth, malformed/accepted
payloads, and UserAccess flash/action-token persistence. The two local-RBAC
public admission tests also pass, so the identity correction does not move
authorization behind session/downstream behavior.

No production or test artifact was edited by this reviewer. Concurrent
uncommitted PilotHttp work visible in the shared worktree was excluded from the
reviewed commit diff and is not approved by this record.

## Fresh verification evidence

```text
php -d display_errors=1 -d error_reporting=-1 \
  tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 sequential write identity

for f in tests/InstallationProcess/pilot_session_storage*_test.php; do
  php -d display_errors=1 -d error_reporting=-1 "$f" || exit $?
done
24/24 files PASS

php -d display_errors=1 -d error_reporting=-1 \
  tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

php -d display_errors=1 -d error_reporting=-1 \
  tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
PASS (exit 0)

git diff --check d66fc17^ d66fc17
PASS (exit 0)
```

Post-run inspection found no `task-sequential-*` or
`foreign-sequential-*` residue under the task-owned test parent.

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
ad871362746b391c18b50b8338e862bad3c0fd8d844eac3d8b309b9fa91558a6  tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
905f27b9b520146b82e6ae60c65be4553d178dceac1b9ee974c0ab94d9e2b3f8  reviews/tests/PILOT-SESSION-STORAGE-001-sequential-write-identity-v2.md
24f4ec88ba89f4faf130f307597ce07b47c4c1e2c53d28428ee2937cf213232e  app/IdentityAccess/FilesystemPilotSessionStorage.php
9d6a115a65370fefd620d440cbb9bbeec59f479be09faf3a763b9f9535e343a8  docs/operations/pilot-session-storage-sequential-write-green-2026-09-04.md
```

## Gate decision

Gate 5 is **APPROVED** for production commit `d66fc17` and the exact reviewed
artifacts above. The sequential session identity correction is complete. This
record does not approve or claim completion of separate checklist LocalAuth,
original-upload, full verification, Compose restart, or other integration
slices.
