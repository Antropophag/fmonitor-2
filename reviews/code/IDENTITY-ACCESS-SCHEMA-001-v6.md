# Supplementary code rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent
  `identity_access_runner_code_rereview_20260901af`
- Independence: reviewer authored neither the implementation nor the reviewed
  observer/test
- Supersedes: the loopback-binding finding in
  `reviews/code/IDENTITY-ACCESS-SCHEMA-001-v5.md`
- Reviewed scope: exactly
  `tools/verification/run-identity-access-isolated-red.sh`, including its
  invocation of
  `tests/InstallationProcess/identity_access_runtime_ddl_001_test.php`
- Prior independent test review:
  `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v8.md`, verdict `APPROVED`
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved `v0.1`
- Verdict: `APPROVED`

## Standards and safety review

The sole finding from code review v5 is closed. Docker now publishes the
random MariaDB host port with the exact binding `-p 127.0.0.1::3306`, matching
the observer's `FMONITOR_TEST_DB_HOST=127.0.0.1` connection and preventing the
temporary root-access listener from being exposed on non-loopback interfaces.

The remainder of the narrow harness retains the reviewed isolation and cleanup
properties:

- `set -euo pipefail` makes setup and observer failures visible;
- a cryptographically random token scopes the container name and root password
  to one invocation;
- MariaDB `11.4.7-noble` stores its data directory in a container-owned
  `tmpfs`, and `general_log=TABLE` is confined to that disposable server;
- the `EXIT INT TERM` trap forcibly removes only the exact randomized container
  and tolerates repeated cleanup;
- the linked observer owns and drops its separately randomized database and
  uses only fictional identity data;
- the script exports only the isolated connection details and invokes exactly
  the intended runtime DDL observer.

No repository data, Compose volume, shared MariaDB, production/legacy personal
data or persistent general log is selected. No expectation changed: the same
observer still requires migrated login, invitation, role attach/detach and
block/unblock paths without DDL, plus safe HTTP 400, unchanged state and no DDL
for missing and incompatible identity-family paths.

No documented-standard violation or applicable code-smell finding remains in
this exact scope.

## Independent execution evidence

The reviewer ran the current dirty worktree without editing the harness,
observer, specification or production code:

```text
$ bash -n tools/verification/run-identity-access-isolated-red.sh
PASS

$ tools/verification/run-identity-access-isolated-red.sh
PASS: IDENTITY-ACCESS-SCHEMA-001 isolated runtime observer
```

During that invocation an independent Docker observation reported:

```text
fm2-ia-red-1324ea69d6440ccd|127.0.0.1:50904->3306/tcp
```

The matching `docker ps -a` query was empty before and after the run, proving
normal-path trap cleanup left no `fm2-ia-red-*` container behind.

## Gate decision and commit authority

This supplementary Gate 5 rereview is `APPROVED`. The loopback-only correction
closes the sole blocking finding from v5 while preserving script safety,
isolation, cleanup and all approved observer expectations.

This record explicitly authorizes inclusion of exactly
`tools/verification/run-identity-access-isolated-red.sh` in the independently
accepted identity/access iteration commit. It authorizes no other previously
unapproved file, unrelated staging, push or external mutation.
