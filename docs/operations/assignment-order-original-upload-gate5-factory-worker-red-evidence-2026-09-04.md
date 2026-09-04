# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrective factory/worker RED evidence

- Recorded: `2026-09-04T06:27:30+03:00`
- Baseline Git SHA: `8fa0ed7d1b13ef8ef131c59509df8d479a6d91c4`
- Trigger: Gate 5 `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md`
- Scope: production factory constructibility and real-mode MariaDB worker parity
- Result: **INTENDED RED**

The verifier now requires a valid MariaDB/private-storage production config to
construct the public `AssignmentOrderOriginalApplication`, then requires valid
initial and correction commands to traverse that same application. The
two-worker contour retains separate command, barrier-read, barrier-write and
result descriptors (plus ordinary stdout/stderr), selects `inspectorMode=real`,
uses the approved structural PDF fixture, and observes revision/event/audit/blob
outcomes only through the approved evidence-reader contract.

Command (credential was supplied from the already-running isolated test
container and is intentionally omitted):

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted> php tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
```

Actual terminal failure, exit `255`:

```text
INTENDED_RED: valid MariaDB/storage production config must construct submitAssignmentOrderOriginal; factory threw LogicException
```

This failure is specific to the Gate 5 finding: the current production factory
still throws instead of composing authorization, composition lookup, repository,
owned private storage/lease, parser and audit adapters. No production file was
changed. The later real-worker and evidence assertions remain unreachable until
that predecessor is minimally GREEN; they prevent the former correction-only
direct-SQL worker from satisfying this verifier.

Supporting checks:

```text
php -l tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
No syntax errors detected

git diff --check
PASS (no output)
```
