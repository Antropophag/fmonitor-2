# Supplementary code rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent
  `identity_access_runner_scope_review_20260901ad`
- Independence: reviewer authored neither the implementation nor the reviewed
  observer/test
- Supplements: `reviews/code/IDENTITY-ACCESS-SCHEMA-001-v3.md` and
  `reviews/code/IDENTITY-ACCESS-SCHEMA-001-v4.md`
- Reviewed scope: exactly
  `tools/verification/run-identity-access-isolated-red.sh`, including its
  linkage to
  `tests/InstallationProcess/identity_access_runtime_ddl_001_test.php`
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved `v0.1`
- Evidence: `docs/operations/identity-access-schema-red-evidence-v9.md` and
  `docs/operations/identity-access-schema-green-verification.md`
- Verdict: `CHANGES_REQUESTED`

## Standards and safety review

The script is narrowly scoped and otherwise appropriately defensive:

- `set -euo pipefail` makes setup/test failures observable;
- a cryptographically random 16-hex token gives the container and root password
  per-run names/values; the linked PHP test independently randomizes its
  database name;
- the MariaDB `11.4.7-noble` data directory is a container-owned `tmpfs`, so the
  observer cannot reuse or persist project database state;
- `general_log=TABLE` is enabled at server startup in that exclusively owned
  container, avoiding mutation or truncation of a shared server log;
- the `EXIT INT TERM` trap targets only the exact random container name and
  performs idempotent forced removal;
- the linked test uses only explicit fictional identity data, clears only its
  exclusive log, observes DDL, drops its exact random database in `finally`, and
  the container teardown discards all remaining log/data state;
- the script invokes exactly the intended runtime observer test and exports only
  its isolated connection details.

No repository, Compose volume, shared database, production/legacy personal data
or persistent log is selected by the script.

## Finding

### 1. Host port is published on all interfaces instead of loopback

Severity: **medium**; commit-blocking for this isolated observer.

The command currently contains:

```bash
-p 0.0.0.0::3306
```

This asks Docker to publish the temporary root-access MariaDB port on every host
interface. The root password is random and the exposure is short-lived, but the
listener is still reachable beyond the local test host while the test runs.
That is broader than required by the linked test, which connects to
`127.0.0.1`, and contradicts the append-only evidence description of a “random
loopback host port” in
`docs/operations/identity-access-schema-red-evidence-v5.md` (retained by the
superseding evidence chain).

Required correction: bind the random published port to loopback only, for
example:

```bash
-p 127.0.0.1::3306
```

Then rerun `bash -n`, the observer, and the before/after container check. No
other behavior or file needs to change for this finding.

## Independent execution evidence

The reviewer ran against the current dirty worktree:

```text
$ bash -n tools/verification/run-identity-access-isolated-red.sh
PASS

$ tools/verification/run-identity-access-isolated-red.sh
PASS: IDENTITY-ACCESS-SCHEMA-001 isolated runtime observer
```

`docker ps -a --filter 'name=^/fm2-ia-red-'` was empty both before and after the
run, proving successful trap cleanup for the normal completion path. The run
did not use the shared Compose MariaDB or its general log.

## Gate decision and commit authority

This supplementary scope is `CHANGES_REQUESTED`. It grants **no commit
authority** for `tools/verification/run-identity-access-isolated-red.sh` in its
current form. After the single loopback-bind correction and repeated checks, a
fresh append-only supplementary rereview may authorize exactly that script.

This record authorizes no production/test edits, no unrelated staging and no
push.
