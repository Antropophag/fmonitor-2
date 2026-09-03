# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — MariaDB worker RED evidence

Date: 2026-09-04

RED author: `/root/original_upload_red_maintenance`

Verdict: **INTENDED RED; Gate 2 task 2.2 complete; no Gate 3 verdict**

## Superseded setup blocker

Canonical additive migration v12 is now integrated at base
`cecfafef3f1c4997031e6a71e1dcdbdaf54c608c`. It supplies the approved original
tables and exact upload/correct/reconcile capability literals. Therefore the
earlier constructibility record remains historical evidence but no longer
blocks preparation of a real MariaDB worker verifier.

## Executable matrix

The new test guards the public production factory, private-storage factory and
verification worker bootstrap before any setup. Behind that guard it:

- creates random verifier-owned MariaDB databases and uses only the canonical
  public migration CLI to advance them through v12;
- seeds the approved active identity, active role, exact correction capability,
  case, order, composition and immutable revision-1 fixture facts;
- creates mode-0600 config/password files and a private storage root under the
  repository's verifier-artifact directory, never under `/tmp`;
- launches each worker with distinct command, barrier-read, barrier-write and
  result descriptors plus isolated stderr; objects/connections are not
  serialized;
- requires both exact `READY <requestId>` lines before either exact `RELEASE`
  line is written, with every parent read bounded;
- covers identical corrections as exactly `ACCEPTED+REPLAYED`, different
  corrections as exactly `ACCEPTED+CONFLICT`, and independently proves exactly
  one appended `n+1` revision;
- reaches the same barrier in separate malformed-release, EOF and timeout
  fixtures, expects exit 70, no result/stdout, redacted stderr and zero commit;
- closes pipes, terminates any still-running child, reaps processes, drops only
  exact random databases and recursively removes only validated task-owned
  artifact roots in `finally`.

Command and config JSON use the exact approved key sets, explicit nulls where
applicable, lower-case enum values and base64 upload bytes. Result assertions
consume only the worker result descriptor. Expected status multisets and row
counts come directly from the specification rather than a repository helper.

Together with the prior initial, parity/authorization, owned-PDF,
stream/storage, repository/replay, lineage/CAS, maintenance, commit/lease and
audit-precedence RED records, this closes every Gate 2 task 2.2 subset. The task
checkbox is advanced, but no Gate 3 review is inferred or started. Production
code is unchanged by this RED.

## Verification transcript

```text
$ php -l tests/Support/assignment_order_original_worker_runner.php
No syntax errors detected in tests/Support/assignment_order_original_worker_runner.php
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
$ php tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical production MariaDB/worker seam is missing: FMonitor2\AssignmentOrderOriginal\ProductionAssignmentOrderOriginalFactory
exit 255
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
$ git diff --check
PASS (no output)
```

The runtime failure is the intended missing production behavior, not setup:
both PHP files lint and execution stops at the explicit public-seam guard before
opening MariaDB or creating any artifact.

An additional local rerun of
`php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php`
was attempted after the RED capture, but this shell has no accepted MariaDB
admin credential (`Access denied for user 'root'@'172.29.0.1'`). That is a setup
failure, not schema evidence and not part of the intended RED classification.
The already integrated v12 GREEN/review history remains the schema evidence.
