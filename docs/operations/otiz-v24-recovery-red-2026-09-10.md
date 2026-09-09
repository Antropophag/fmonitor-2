# OTIZ canonical v24 recovery — root-authored RED

The existing public backup/restore test advances its current-image contract from
23/69/39 to24/71/39. Historical recovery profiles22/23 stay unchanged. The fixture
adds a completed settlement with receipt, object lock, accepted snapshot/object,
closure and audit event. Backup and restore now compare all domain tables as well
as Jobs rows, allocator state and private files; mismatch/no-mutation checks remain.
A wrong manifest version23 is rejected before target mutation. The existing exact
v22-image forward rehearsal now targets current24 and preserves the v22 facts.

Accepted supplement: `specs/OTIZ-SETTLEMENT-001.md`, canonical recovery section.
Verification mapping includes both existing recovery tests and intended new
`RuntimeRecoverySchemaV24`/current RuntimeRecovery adapter boundaries. No runtime
implementation changed for this test increment.

RED: `php tests/Runtime/runtime_jobs_recovery_001_test.php` built the current
legacy runtime image and returned255. Its default PHP configuration hid the child
assertion. The test launcher now enables display_errors only for the synthetic
test child; production configuration is unchanged.

Observable rerun used the existing runtime dependency image
`fmonitor2-runtime:restore36-v23-e5d1b34e` with the **current checkout read-only
mounted** at `/workspace/fmonitor-2`, PHP entrypoint, test DB connection through
`RecoveryContainerNetwork`, and `FMONITOR_JOBS_RECOVERY_CONTAINER=1`.
It is not evidence that this old image contains the current source. Full command
and output were captured in this session; output is retained at
`/tmp/fm2-root-v24-recovery-observed.log`.

The exact canonical24 migration and public RuntimeReadiness both succeeded before
calling the backup CLI. The intended failure was then:

```text
INTENTIONAL_RED: v24 public backup accepts exact jobs frontier
Expected: exit0, BACKUP_CREATED
Actual: exit70, BACKUP_FAILED
```

Parent exits255. Current RuntimeRecovery still binds its inventory to version23;
the new tables cause rejection before dump/publication. The image includes real
MariaDB clients/tar, so this is not absent-tool setup failure. No stand data or
services were used. Disposable databases, user, state and newly built image were
cleaned by the existing harness. The historical v22 forward drill has not yet been
run at this checkpoint; it remains a required focused GREEN obligation.

## Exact v23 source/image supplement after review020deb3f

The forward rehearsal now runs both exact historical profiles in separate child
processes: v22 source `f22d80a609d52a194c1fd68b1db7ab7273740f28`, and v23 source
`a6bafb1d` (the full SHA is fixed in the test). Each image is built from its Git
archive and its OCI source label checked. It exports a bundle using that source's
own reviewed recovery fixture, restores it with the same image and attestation,
then migrates current code to24. All old table rows/AUTO values/private bytes must
remain equal; a deleted-high-id AUTO gap is checked for each profile. Each old
image must reject the newly created24 bundle before any target mutation.

The v23 fixture's original session instance is `recovery`, not the v22 fixture's
`restore`; the first attempt exposed this setup mismatch and it was corrected in
the test environment without changing production code. New v23 export cleanup
removes its source/target fixture databases after copying the private bundle.

Observed command:
`FMONITOR_RECOVERY_FORWARD_VERSION=23 php tests/Runtime/runtime_recovery_forward_update_001_test.php`

Actual historical v23 export/restore and forward24 preservation assertions passed.
It then reached the same intended current24 backup failure: exit70 BACKUP_FAILED
instead of BACKUP_CREATED; parent255. Captured output:
`/tmp/fm2-root-v23-forward-red.log`. No claim of current24 GREEN or old-image
rejection execution is made yet: those assertions remain downstream of the
missing current inventory adaptation. Default test execution covers both profiles.

The acceptance seam now explicitly names both historical exact v22/v23 images,
matching the reviewed test and normative supplement. The historical-image build
message also names the selected historical version instead of always saying22.
No behavior or existing RED evidence changed in this mapping correction.
