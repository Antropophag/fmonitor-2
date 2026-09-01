# IDENTITY-ACCESS-SCHEMA-001 RED evidence v3 — public-seam blockers

- Date: `2026-09-01`
- Role: fresh Gate 2 test author `identity_access_red_runtime_20260901d`
- Supersedes: `identity-access-schema-red-evidence-v2.md` only for the requested
  runtime-observer and runner short-circuit additions
- Outcome: `BLOCKED_ON_PUBLIC_TEST_SEAM`; no production code changed

## Requested additions

The assigned correction asked for statement-observed characterization of login,
invitation, role attach/detach and block/unblock through existing public
runtime/application seams, covering migrated success and missing/incompatible
fail-closed paths without `CREATE`, `ALTER` or `DROP`. It also asked for robust
unexpected driver-failure redaction and proof that a later migration is not
invoked after a v6 conflict/failure.

## Runtime statement-observer blocker

The existing runtime seams do not expose an injectable statement observer:

- `RapidPilotLocalAuth` constructs its own concrete `mysqli` connection and its
  request handlers terminate the process;
- `RapidPilotUserAccessView::handleStatus()` also constructs its own concrete
  `mysqli` connection and terminates the process;
- `MariaDbPilotUserDirectory` accepts a concrete `mysqli`, not an observable
  public connection/statement interface.

The available MariaDB test instance reports `performance_schema=0`,
`general_log=0`, and `log_output=FILE`. Consequently statements from those
public paths cannot be observed read-only. Enabling the server-global general
log or changing its output would mutate shared server state and make the suite
non-isolated under parallel execution. A static source regex is not a substitute:
it cannot observe indirect statements and was already rejected by the
independent test review.

Therefore no honest deterministic test can currently prove both runtime outcome
and absence of DDL through these public seams. This requires an approved public
test seam/design change (for example request-scoped observable DB dependencies),
or an isolated per-test MariaDB instance whose logging is owned by the test.

## Later-migration short-circuit blocker

`bin/fmonitor2-migrate.php` currently exposes migrations v1-v5 only. The approved
v6 production migration is intentionally absent at RED, and there is no public
runner configuration or post-v6 sentinel migration. Thus the public seam can
prove the current missing-v6 RED and existing earlier-conflict short-circuit,
but it cannot observe whether a migration *after v6* was invoked following a v6
conflict/failure. Adding a fake migration through an internal test hook would
violate the public-seam contract; adding v6/v7 production wiring is Gate 4 work.

## Unexpected driver failure

The public CLI already has a redacted `DATABASE_UNAVAILABLE` boundary for
connection/setup failures and a redacted `MIGRATION_FAILED` boundary around the
migration loop. A deterministic failure specifically originating inside future
v6 metadata/DDL cannot be selected through the public runner without either the
absent v6 implementation or a fault-injection seam. Permission denial on the
current runner would fail an earlier migration and would not establish v6
classification or v6 short-circuit behavior.

## Gate status

- OpenSpec tasks 2.1-2.3 remain unchecked because the complete assigned scope is
  not demonstrable.
- Task 2.4 remains unchecked.
- No test or production source was changed.
- Independent Gate 3 rereview must not begin from this record alone.

## Read-only verification

```text
MariaDB probe: performance_schema=0, general_log=0, log_output=FILE
Public runner inspection: literal migration map ends at v5
Runtime seam inspection: concrete self-created/injected mysqli; no observer seam
```
