# Test rereview: CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2 — oracle v6

- Date: `2026-09-05`
- Reviewer: fresh separately tasked agent `/root/object_detail_import_gate3_v2`
- Test author: a different previously tasked agent
- Reviewed commit: `6517a1ad2bf3aa12a45f117667f2b6659ba4bbd0`
- Public seam: `php tests/Verification/characterize_object_detail_import_001_test.php`, invoking the real importer CLI as a child
- Verdict: `APPROVED`

The reviewer authored none of the reviewed test, specification, production,
migration, approval, or RED-evidence bytes. Execution used only fresh synthetic
Docker fixtures. It did not access VPN, legacy/production data, shared
databases, production credentials, or protected E2E state.

## Pinned identities

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
fae2d9a5e3ba184663e7850ca83fdfd14125d71d22b8970b5d95e45572e19bcc  tests/Verification/characterize_object_detail_import_001_test.php
668b51891c956202cc4d6fe0d37287fdf615489e56bdfa66eea087d0d899b449  exact six-line expected normalized transcript, LF after every line
b3182ecde4b0e3270417732f9f386f24b5b80d0e10f1ac195692c6a463e9debf  docs/operations/object-detail-import-red-evidence-v6-2026-09-05.md
442fd28a8fe5ea4628068084b0ddfaf3b8d166b58cd26339763b7dbec38791d1  docs/operations/object-detail-import-v02-owner-approval-2026-09-05.md
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
2bc47395cdaf61974c493907a84d8f8f25dc034e10c140a8fd851ca4845ace8a  app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
```

The dated owner record approves the exact v0.2 specification hash above and
supersedes the stale pending-status prose inside those unchanged bytes.

## Findings

None.

The v6 test resolves the acquisition blocker from
`reviews/tests/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001-v5.md`. Exact root, token,
child, container name, local image ID, and namespace vacancy are established
before mutation. Artifact ownership is latched immediately after successful
creation, with `mkdir`, `chmod`, Docker create, inspect, and start inside the
protected acquisition block.

Docker-create intent is recorded before invoking the CLI. If its response is
lost, recovery inspects only the exact previously vacant container name and
accepts ownership only when the immutable image ID and token label match. It
then retains the exact container ID and any observed volume IDs and removes by
ID after a fresh exact name/ID/token proof. Non-not-found Docker errors cannot
masquerade as absence. The executable `after-child` and
`after-create-response-loss` probes traverse these real acquisition paths and
prove exact child/container cleanup plus ambient-decoy preservation.

The prior v4 lifecycle corrections remain intact: parent-owned resource
cleanup is independent of worker `finally`; workers enter a validated private
process group before descendants; TERM/KILL target that group; live-grandchild
and real parent-owned timeout probes prove descendant and resource cleanup; a
running child cannot enter `proc_close`; behavioral and cleanup classifications
retain the required precedence; and listener acquisition/control is fully
inside its socket cleanup guard.

The test continues to use the approved external CLI argv and first/repeat
capture rules. It dynamically resolves the local image tag to an immutable ID,
requires empty mounts plus exact MariaDB tmpfs, uses distinct root/source/target
credentials, verifies exact table grants and DDL-denied compatibility, and
captures complete five-table schema and row state around every behavioral call.
Fixed clean rows and payload bytes, replay, dry-run, transactional conflict,
both source rejections, and all eight pre-source schema refusals are independently
asserted. Test-owned DML only creates, arranges, restores, and observes the
private fixture; it does not reproduce the importer behavior under test.

## Independent RED and cleanup evidence

The focused command produced the intended genuine RED:

```text
$ tools/verification/run.sh red tests/Verification/characterize_object_detail_import_001_test.php
REGRESSION_FAILURE: real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal
RED_ASSERTION: expected failing behavior observed in tests/Verification/characterize_object_detail_import_001_test.php
[wrapper exit 0]
```

The RED occurred after both acquisition probes, the parent-owned
timeout/descendant probe, exact public v12 creation, read-only compatible
inspection through the DDL-denied principal, and unchanged prerequisite
snapshot. The meta-test executed two distinct normal workers and compared their
complete normalized results. The failure is the real importer's runtime
`CREATE`, not setup failure or test-owned import behavior.

Reviewer pre/post checks established:

```text
docker volume ls -q | sort  # identical before and after; diff exit 0, no output
docker ps -a --filter 'label=fmonitor2.object-detail-token'  # no output after
find .test-artifacts/object-detail-import -maxdepth 2 \
  \( -name 'object-detail-*' -o -name 'ambient-decoy.txt' \) -print  # no output
test -d .test-artifacts/object-detail-import  # exit 0
process inventory for verifier/importer/containment probe  # no survivor
php -l tests/Verification/characterize_object_detail_import_001_test.php  # no syntax errors
git diff --check  # exit 0, no output
```

No old Docker volume was inspected or removed. The common artifact root
survived.

## Verdict

`APPROVED`

Gate 3 passes for the exact specification, test, expected transcript, and RED
evidence hashes pinned above. Gate 4 may proceed without changing the approved
test expectations.
