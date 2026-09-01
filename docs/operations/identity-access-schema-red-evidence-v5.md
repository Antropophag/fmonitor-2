# IDENTITY-ACCESS-SCHEMA-001 RED evidence v5 — exclusive runtime SQL observer

- Date: `2026-09-01`
- Role: fresh Gate 2 test author `identity_access_red_isolateddb_20260901f`
- Supersedes: `identity-access-schema-red-evidence-v4.md` for runtime observer feasibility
- Outcome: `RED_EXPECTED`; independent Gate 3 review is still required

## Exclusive lifecycle

`tools/verification/run-identity-access-isolated-red.sh` starts a random-named
MariaDB 11.4.7 container with a random loopback host port, test-owned tmpfs data
directory, and `general_log=TABLE` enabled from startup. A trap removes the
container on success, assertion failure, signal, or setup failure. No shared
Compose database, production credential, legacy person, named volume, or
persistent database artifact participates.

## Public runtime observation

`tests/InstallationProcess/identity_access_runtime_ddl_001_test.php` creates only
fictional native identity data, clears the exclusively owned log after fixture
DDL, then executes invitation and role attach/detach through
`MariaDbPilotUserDirectory`. These migrated-success operations must emit no
`CREATE`, `ALTER`, or `DROP`. Block/unblock is entered through
`RapidPilotUserAccessView::handleStatus`; the current source is expected to make
the observer RED because that request path still executes `CREATE TABLE IF NOT
EXISTS ...fm2_pilot_user_status_events`.

The prior canonical CLI RED remains qualifying: the public runner currently
returns v5 instead of approved literal v6. This observer is additive evidence
and does not weaken that expectation.

Executed command and intended failure:

```text
$ tools/verification/run-identity-access-isolated-red.sh
TestFailure: Migrated block/unblock runtime seam executes no DDL.
Expected: []
Actual: [CREATE TABLE IF NOT EXISTS `runtime_fm2_pilot_user_status_events` ...]
exit: 255
```

After the trap, `docker ps -a --filter name=fm2-ia-red- --format
'{{.Names}}'` returned no rows. `php -l`, `bash -n`, and `git diff --check` for
the three new artifacts passed.

## Deliberately not claimed

Unexpected v6 driver-failure short-circuit and proof that no later migration is
attempted remain unobservable until production v6 exists: the public runner has
no injectable migration map or fault seam. Adding a production hook at Gate 2
would violate the approved seam. Missing/incompatible runtime fail-closed and
login observations will be completed before tasks 2.1–2.3 can be checked; they
are not claimed by this iteration.
