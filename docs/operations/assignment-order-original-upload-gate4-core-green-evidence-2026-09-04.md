# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 4 core GREEN evidence

Date: 2026-09-04

Implementation commit: `da45b5eee01a6a120054d3d08238512cc3466e6b`

Corrected test commit: `66fe7f0e329434e63d02296f465f9679567637b2`

Fresh Gate 3 v2 approval: `64a7b50da87d275e88b26c6e6f766cc917a26c8c`

Outcome: **GREEN for OpenSpec task 3.2; no Gate 5 verdict**

The production core adds the typed command/result DTOs, exact authorization,
request and fingerprint replay, immutable accepted commit, append-only lineage
and CAS reconciliation, bounded stream ownership, lease lifetime/release and
audit precedence required by the approved in-memory task 3.2 inventory. It does
not implement the owned PDF parser, production persistence/storage bindings,
maintenance owner or worker bootstrap assigned to task 3.3.

The fresh v2 review corrects only the cumulative lease-release oracle. No
production or approved test changed during this evidence capture.

```text
$ php -l app/AssignmentOrderOriginal/AssignmentOrderOriginalApplication.php
No syntax errors detected in app/AssignmentOrderOriginal/AssignmentOrderOriginalApplication.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_parity_authorization_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_STREAM_STORAGE_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_repository_replay_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_REPLAY_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_lineage_cas_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_CONCURRENCY_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_FAILURE_MATRIX_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUDIT_PRECEDENCE_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
$ git diff --check
PASS (no output)
```

Task 3.2 is therefore checked. The larger Gate 4 and slice remain incomplete
until task 3.3 and its production/parser/MariaDB/maintenance/worker tests are
GREEN and independently reviewed at Gate 5.
