# Supplementary test rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent
  `identity_access_runner_test_review_20260901ae`
- Independence: reviewer authored neither the observer test, infrastructure
  correction nor production implementation
- Supersedes: the loopback-binding finding in
  `reviews/code/IDENTITY-ACCESS-SCHEMA-001-v5.md`
- Reviewed scope: exactly
  `tools/verification/run-identity-access-isolated-red.sh` and its invocation
  of `tests/InstallationProcess/identity_access_runtime_ddl_001_test.php`
- Verdict: `APPROVED`

## Scope and expectation review

The requested correction is present as the exact Docker publication
`-p 127.0.0.1::3306`. The harness still obtains Docker's randomized host port
with `docker inspect`, exports loopback plus that port to the observer, and
uses a cryptographically random token in the exact container name and root
password.

The isolation and cleanup properties reviewed in v5 remain intact:

- MariaDB `11.4.7-noble` owns a fresh `tmpfs` at `/var/lib/mysql`;
- the general log is enabled only inside that disposable server and written to
  its `mysql.general_log` table;
- the `EXIT INT TERM` trap forcibly removes only the exact randomized
  container and tolerates repeated cleanup;
- the linked observer still creates and drops its own randomized database and
  uses only deterministic fictional identity data;
- the harness invokes the same runtime observer, and no observer expectation
  was weakened or replaced.

The observer continues to require migrated login, invitation, role
attach/detach, block and unblock paths to succeed without DDL. Its missing and
incompatible identity-family scenarios still require safe HTTP 400, unchanged
user state and an empty observed DDL list.

## Independent execution evidence

I ran the current dirty worktree without editing the harness, observer,
specification or production code:

```text
$ bash -n tools/verification/run-identity-access-isolated-red.sh
PASS

$ tools/verification/run-identity-access-isolated-red.sh
PASS: IDENTITY-ACCESS-SCHEMA-001 isolated runtime observer
```

During that run, an independent `docker ps` observation reported:

```text
fm2-ia-red-2e21d6fae13747d7|127.0.0.1:50741->3306/tcp
```

Thus the container name and host port were randomized and the listener was
published only on IPv4 loopback. The exact-name `docker ps -a` query was empty
before and after the run, proving normal-path trap cleanup left no matching
container behind.

## Gate decision

This supplementary Gate 3 review is `APPROVED`. The one-line infrastructure
correction closes the only finding in code review v5 without changing the
approved observer expectations or product behavior.

A fresh independent Gate 5 reviewer is authorized to perform the narrow final
rereview of this corrected harness and its integration evidence. This approval
authorizes no unrelated edits, staging, commit or push.
