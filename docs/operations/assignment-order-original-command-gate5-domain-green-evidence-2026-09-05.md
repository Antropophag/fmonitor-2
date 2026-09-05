# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — Gate 4 findings 3–6 GREEN evidence

- Date: `2026-09-05`
- Production author: `Codex agent /root/command_gate5_domain_green`
- Approved executable-test Gate 3: `7b56be9eb88a6d8509edb9b8f4d91efc17e8c170`
- Test baseline through: `183171ac`
- Repository HEAD while evidence was captured: `1a2f8de44693da689d8144a7ca4bd7de5b70e606`

## Exact executable identities

```text
f788e80143c25cda53fb02d79a4089248ce6079fcf1586b6aeb65b53d5ba6486  tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
40545c57c70239975062e0e677d3f4f82e89e5b0ef7944ffa270949468a8c916  tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php
84cd4b2a8a8cf751eb461de1fc1a48f042194489e8bd0dde045ba25c1043dea0  tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php
```

## Exact production identities

```text
295fe17bc410bc3e07a392d1f1fb959d40ba3d5d7dfad4f8c7304914dd56c347  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
8bb816ad16d113ce4546ffabed325ae160068d68906672d7195a147dea50b234  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
ce1e072b08705f6347de23e2867ee53c8e774c528b3abf5f1c5b121d9a21bc3c  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
```

## GREEN transcript

```text
$ php tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_GATE5_DOMAIN_RED_001_OK

$ php tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_GATE5_MARIADB_RED_001_OK

$ php tests/InstallationProcess/assignment_order_original_worker_post_finalize_negative_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_WORKER_POST_FINALIZE_NEGATIVE_OK

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

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
```

PHP lint passed for all three changed production files and `git diff --check`
exited zero.

The production correction makes authorization and repository unavailability
fail with the exact retryable technical result, requires atomic attempt/audit
persistence, validates every composition row before member exclusion, keeps
correction lineage/drift checks before stream access, distinguishes MariaDB
technical rollback from duplicate-key CAS conflict, scopes INITIAL conflict
lineage by assignment order where the production repository supports it, and
emits the post-finalize barrier only from the real application lifecycle while
the finalized-content lease is held.

## Explicit non-GREEN observations

`assignment_order_original_worker_transport_001_test.php` is not claimed
GREEN. Its old fingerprint-miss race oracle requires private blob inventory to
remain byte-for-byte unchanged even though completed in-flight stages already
exist when the application can compute the fingerprint. Correction commit
`1a2f8de44693da689d8144a7ca4bd7de5b70e606` has no approved Gate 3: review
rejected its dependency on uncommitted production and its failure cleanup did
not safely reap all children. No production workaround was added.

`assignment_order_original_private_bytes_restart_001_test.php` remains outside
this correction: its missing persisted content-byte failure belongs to the
separately reviewed storage boundary findings 1–2. No storage file was changed
here.

This is Gate 4 evidence only. It grants no Gate 5 approval; this production
author is in review-ineligible for its own correction.
