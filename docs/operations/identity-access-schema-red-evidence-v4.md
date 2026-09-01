# IDENTITY-ACCESS-SCHEMA-001 RED evidence v4 — global-log observer safety check

- Date: `2026-09-01`
- Role: fresh Gate 2 test author `identity_access_red_observer_20260901e`
- Supersedes: `identity-access-schema-red-evidence-v3.md` only for the proposed
  MariaDB general-log workaround assessment
- Outcome: `BLOCKED_ON_SAFE_PUBLIC_TEST_SEAM`; no test or production code changed

## Proposed observer assessed

The assessment considered a test-only observer around the public login,
invitation, role attach/detach and block/unblock processes using:

1. a MariaDB advisory `GET_LOCK`;
2. saved `@@global.general_log` and `@@global.log_output` values;
3. temporary `log_output=TABLE` and `general_log=ON`;
4. unique disposable database/prefix plus thread/time filtering;
5. `finally` restoration and removal of this test's evidence rows.

The live repository test database was probed read-only. It reports:

```text
@@global.general_log = 0
@@global.log_output = FILE
@@performance_schema = 0
CURRENT_USER() = root@%
root@% has GRANT ALL PRIVILEGES ON *.* WITH GRANT OPTION
```

Only the probing connection was present in `information_schema.PROCESSLIST` at
the instant of the probe. That observation does not reserve the server for the
duration of a future test.

## Why the workaround is not a safe deterministic test

`GET_LOCK` is cooperative and connection-scoped. It serializes only tests that
voluntarily request the same lock; it neither prevents another repository test
nor a Compose service from opening a connection while global logging is on.
Changing `general_log`/`log_output` therefore affects every client on the shared
Compose test MariaDB, not only the random database or selected connection.

Database, timestamp and thread filtering can make the *assertion result*
namespace-specific, but cannot prevent unrelated statements (including bound
values materialized by the server logger) from being persisted in
`mysql.general_log` during the observation window. Deleting only selected
evidence rows afterwards cannot prove that no unrelated row was captured.
The race remains even after a zero-other-connections `PROCESSLIST` preflight,
because that query supplies no reservation against a later connection.

Exact restoration also has a failure boundary outside PHP's control: process
termination, container interruption or a lost admin connection after either
global `SET` can leave logging/output changed. A PHP `finally` is useful cleanup,
but is not a guarantee for server-global state. When the initial state is
`general_log=ON`, cleaning table rows additionally requires temporarily
disabling a logger owned by another actor, which is itself not isolated.

The admin capability to perform the global changes is therefore not evidence
that the changes are task-owned. The proposed observer would make a shared
server-global mutation and could capture unrelated data, contrary to the
deterministic synthetic/native-data constraint. It was not implemented.

## Remaining safe routes

Either of the following would remove the blocker without imitating evidence:

- a per-test MariaDB server/container whose complete lifecycle and log are
  exclusively owned by this test; or
- an approved request-scoped observable database seam that keeps production
  behavior unchanged while exposing executed statements to the test.

The current `make test-env-up` database is repository-isolated from production,
but it is a shared long-lived test service and does not establish exclusive
ownership for one test process. Spawning a separate container is not part of
the current public test seam or repository test lifecycle and would add a new
Docker/runtime dependency to this focused PHP test; that requires an approved
test-infrastructure decision rather than an ad-hoc Gate 2 edit.

## Runner fault/short-circuit check

The public canonical runner still exposes a literal map ending at v5 and no
test-only migration injection seam. Consequently a v6 unexpected driver failure
and observation of a later migration after v6 still cannot be selected through
the approved public process before production v6 exists. Adding production
hooks or fake migrations is outside Gate 2. Literal assertions may be reviewed
for future sensitivity after the first qualifying missing-v6 RED, but without a
public fault setup they would be unreachable and would not constitute executed
RED evidence.

## Gate accounting

- Existing missing-v6 RED from v2 remains qualifying.
- OpenSpec tasks 2.1–2.3 remain unchecked because the full verification wording,
  especially the runtime statement observer, is not covered.
- Task 2.4 remains unchecked; no independent Gate 3 approval exists.
- No production source, test artifact or historical evidence/review was changed.
