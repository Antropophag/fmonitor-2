# INSPECTION-ITEM-COMPLETE-001 endpoint admission RED evidence v2

Date: 2026-09-01

The isolated canonical v1-v8 raw-HTTP harness now executes the complete
pre-admission contour before asserting results:

1. GET the public checklist page as active, unassigned actor `7301` with exact
   `inspection.item.complete` and no `checklist.edit`;
2. independently parse its `data-csrf` token and secure session cookie;
3. POST a valid `item_completed` JSON envelope to the public operations route;
4. GET the public offline sync-context route;
5. only then assert the collected endpoint statuses.

The checklist page is HTTP 200 and supplies both token and cookie. Current
production returns HTTP 403 for the actual POST instead of approved HTTP 200,
which is the qualifying behavior RED. Sync-context was already collected
before that assertion.

Before GET/POST, the test snapshots exact `SHOW CREATE TABLE` plus every row of
all four v8 tables and hashes every owned artifact-tree member. The after
snapshot is identical, proving the rejected attempts perform no DDL repair,
business mutation, or artifact write.

HTTP connect/read/write operations have explicit deadlines and response caps.
Server pipes are nonblocking; startup detects early exit; teardown uses bounded
TERM, KILL escalation, drains only owned pipes, closes them, and reaps the owned
process. The private database/artifact root and Compose volumes are removed.

```text
$ tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
Expected: 200
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

The independent prefix RED now puts literal invalid ASCII `bad-prefix` first,
so its reproduced failure directly proves PX-01 before any DB access.

```text
$ tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
TestFailure: Invalid production prefix must fail configuration before DB access: bad-prefix
RED_ASSERTION: expected failing behavior observed
```

```text
7d1ef839d2296cea0946e9b365026f5430f132c56ad72fa324b039affb104808  tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
1e8b7f4a58a1a34d86923cf74cf8160cbb7908eec7b98c179043635feb70b04e  tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
```

Production and approved artifacts were not edited.
