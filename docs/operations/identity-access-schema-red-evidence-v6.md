# IDENTITY-ACCESS-SCHEMA-001 RED evidence v6 — complete runtime request observer

- Date: `2026-09-01`
- Role: fresh Gate 2 test author `identity_access_red_runtime_complete_20260901g`
- Supersedes: `identity-access-schema-red-evidence-v5.md` for runtime-path coverage
- Outcome: `RED_EXPECTED`; independent Gate 3 review is required

The exclusive MariaDB contour now executes a real password login in the current
pilot image, invitation, role attach/detach, and both block and unblock public
paths. Terminating HTTP seams run in isolated child processes. The fixture is
deterministic fictional native data only. All assertions are aggregated so one
remaining lazy-DDL failure does not hide later missing/incompatible scenarios.

For the migrated family, login, invitation and role operations reach their
current success boundaries. Block and unblock both expose the remaining
`CREATE TABLE IF NOT EXISTS fm2_pilot_user_status_events` statements. With that
member absent, block currently creates it and commits the user-state mutation;
the test requires the existing safe failure, unchanged user state and zero
`CREATE`/`ALTER`/`DROP`. With an incompatible member, the current request emits
the existing safe error and rolls back user state without schema DDL; this case
is retained as a fail-closed regression branch.

Command and qualifying intended failures:

```text
$ tools/verification/run-identity-access-isolated-red.sh
Runtime observer failures:
- migrated paths emitted DDL: [CREATE TABLE IF NOT EXISTS ...status_events, ...]
- missing family did not fail closed
- missing family mutated state
- missing family emitted DDL: [CREATE TABLE IF NOT EXISTS ...status_events]
exit: 255
```

The script owns a random container, random host port, tmpfs datadir and trap
cleanup. After the run, `docker ps -a --filter name=fm2-ia-red-` showed no
remaining container. It publishes the random port on all host interfaces only
for the lifetime of that exclusive container so the disposable pilot-image
login child can reach the same observer database.

The unexpected-v6/later-migration short-circuit cannot be executed before v6
exists: the approved public runner exposes neither a replacement migration map
nor a fault-injection seam. Adding one in Gate 2 would alter production. Its
literal runner assertions therefore remain a post-GREEN executable branch; the
already qualifying canonical missing-v6 RED remains unchanged.

Tasks 2.1–2.3 remain unchecked pending correction of the incompatible-path
process-status assertion and independent review. Task 2.4 remains unchecked.
