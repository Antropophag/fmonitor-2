# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — identical race Gate 4 GREEN evidence

- Date: `2026-09-05`
- Production author: `Codex agent /root/command_gate5_domain_green`
- Main fingerprint-barrier executable Gate 3: `c00f8ae18b09cd9e23f4790886a8bec0cfd06aab`
- Main test commit: `bc05af1`
- Isolated fingerprint-barrier executable Gate 3: `18fafa418cb5962a4ba7d0b819f6be40a1498eaa`
- Isolated test commit: `0b087a3`
- Storage correction baseline: `6be7fa6423cfa46564d03696233623b0f466c31d`

## Exact identities

```text
207f304502191cb522581900c6a086080932d9b77ba23e80afa7e19286e3cf3f  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
7b6297b8bed813f682db82a496dd599f17a89cea6c85aae9554300483a036b4f  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
617019a303efd714c9e6348d99e05aaffb57f6fee1212d176f29e7907b182693  tests/Support/assignment_order_original_isolated_races.php
```

## Minimal correction

After a real post-fingerprint barrier and private finalize, a correction worker
that observes a changed current revision now rechecks its already-computed
semantic fingerprint while still holding the finalized-content lease. A found
winner returns the winner evidence as `REPLAYED` with the losing request ID; an
unavailable lookup fails closed; a fingerprint miss preserves the existing
`STALE_REVISION` conflict and atomic attempt/audit path. No test, specification,
task, storage, parser or boundary file was changed by this production author.

## GREEN transcript

```text
$ php tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_WORKER_TRANSPORT_OK

$ php tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_GATE5_DOMAIN_RED_001_OK
$ php tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_GATE5_MARIADB_RED_001_OK
$ php tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_WORKER_POST_FINALIZE_NEGATIVE_OK
$ php tests/InstallationProcess/assignment_order_original_private_bytes_restart_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_PRIVATE_BYTES_RESTART_OK
$ php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_PRODUCTION_BOUNDARY_OK
$ php tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_PDF_BOUNDARY_OK
$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_INITIAL_OK
$ php tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK
$ php tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_AUTHORIZATION_OK
$ php tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_LEASE_RACE_OK
$ php tests/InstallationProcess/assignment_order_original_worker_protocol_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_WORKER_PROTOCOL_OK
$ php tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_EVIDENCE_READER_OK
$ php tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
```

All commands exited zero without warnings or leaked child output. PHP lint for
the changed runtime passed and `git diff --check` exited zero. This is Gate 4
evidence only; it grants no Gate 5 approval, and the author is ineligible to
review this correction.
