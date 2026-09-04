# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — structural parser GREEN

- Verified: `2026-09-04T06:48:28+03:00`
- Production commit: `442af7b5cbb9b090ec8e2360adeb1a7378aaa711`
- Approved RED: `c4643bb277d02313ebad7b046bcf0b9e7c9c6282`
- Gate 3 approval: `a667598a42978730b3d8c0e07b0cea787a503ecc`
- Result: **focused GREEN**

The owned `fmonitor-passive-pdf-v1` inspector now resolves bounded classic xref
and xref streams, follows acyclic `Prev` chains, validates offsets/generations
and duplicate entries, decodes bounded Flate structural/object streams, builds
the active object graph, and rejects forbidden actions, encryption, malformed
graphs, cycles, zero pages and approved object/decompression limit violations.

Exact successful commands:

```text
php -l app/AssignmentOrderOriginal/FMonitorPassivePdfInspector.php
php tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_owned_pdf_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_repository_replay_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_lineage_cas_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_parity_authorization_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_audit_precedence_test.php
make architecture-check
openspec validate replace-pilot-registration-with-original-upload --strict
git diff --check
```

Observed suite markers, in command order:

```text
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_STRUCTURAL_PDF_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_STREAM_STORAGE_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_REPLAY_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_CONCURRENCY_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_FAILURE_MATRIX_OK
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUDIT_PRECEDENCE_OK
ARCHITECTURE CHECK PASSED (7 rules)
Change 'replace-pilot-registration-with-original-upload' is valid
```

This is focused GREEN only. Factory/worker, maintenance, application-totality
and repository-wide Gate 5 findings remain separate and no integration claim is
made.
