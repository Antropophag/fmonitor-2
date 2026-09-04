# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private storage/maintenance GREEN

- Verified: `2026-09-04T07:03:53+03:00`
- Production commit: `93ba7ccc7a558ff34848c3aa09e012cbb8619b87`
- Approved RED: `7314dcc340969da612159bcc2cf1b5a33451fc8e`
- Gate 3 approval: `d2c65d964bb61a4aa6157c660ad62d54191ad7ac`
- Result: **focused GREEN**

Filesystem private storage now enumerates old abandoned stages and finalized
content in canonical timestamp/identity order, supports bounded opaque cursor
pagination, emits approved inventory JSON, deletes only validated owned
identities under digest locks, and verifies existing content bytes, size and
SHA-256 before returning `ALREADY_PRESENT_VERIFIED` with a lease.

Successful verification:

```text
php -l app/AssignmentOrderOriginal/AssignmentOrderOriginalPrivateStorageFactory.php
php tests/InstallationProcess/assignment_order_original_upload_001_private_storage_maintenance_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_maintenance_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_stream_storage_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_commit_lease_fault_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_owned_pdf_test.php
php tests/InstallationProcess/assignment_order_original_upload_001_structural_pdf_test.php
make architecture-check
openspec validate replace-pilot-registration-with-original-upload --strict
git diff --check
```

Observed markers include private-maintenance, maintenance, stream-storage,
initial, failure-matrix, PDF-boundary and structural-PDF OK; architecture passed
7 rules and OpenSpec strict validation passed. This is focused GREEN only; no
Gate 5 or integration claim is made.
