# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 2 matrix RED, part 2

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red2`

Scope: additive task 2.2 oracle/preflight coverage after owner-approved v6
checklist-evidence amendment at `7fc582d4dc1f5f85b1d21414e386a9b7d9f19469`.

Outcome: **INTENDED RED — production application seam absent**

## Added executable oracles

The new verifier fixes independently of future production:

- the literal positive PDF, `327` received bytes and SHA-256;
- the complete named owned-parser positive/adversarial corpus, including every
  forbidden action-key family and all approved structural bounds;
- exact `65,536` chunk and `20,971,520` inclusive received-byte limits;
- accepted versus abort storage-event order;
- typed correction/root/current/target/no-change outcomes;
- retryable stream, stage, finalize, rollback and unknown-outcome mappings;
- the closed `aoou-process-v1` no-mutation shape with independent
  `checklistSha256`;
- maintenance exact capability and closed
  `scanned = deleted + retained + failed` accounting;
- presence of all approved production verification/application, owned PDF,
  independent evidence-reader, maintenance and worker-bootstrap seams.

The data-only oracle cannot construct an application Result, mutate production
state, inspect a private implementation or select production adapters. All
values are literal deductions from executable specification v6.

## Reproduced RED

```text
$ php -l tests/Support/AssignmentOrderOriginalRemainingMatrix.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalRemainingMatrix.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved production seam FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
exit 0
```

This is append-only partial Gate 2 evidence. It does **not** claim task 2.2
complete: the oracles still need to drive the public command, real owned parser,
fresh-connection MariaDB evidence reader, five-FD worker race, maintenance seam
and injected commit/content-lease fault executions before the checkbox may be
marked. No production, approved prior test, review or OpenSpec task was edited.

## Exact reviewed inputs and added artifacts

```text
912b156b8dea9458506d44500c26036a02c5802e9e8c98d2c02bbada90bd93c2  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
49273e1715061eaf65cb3f1167656fa4a4dbed7c8581a21bf29b52af8b3df5d3  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
13608f50a2a2148cde77d0b0656cae9ad1f23f55da6e7d1ebb7e58560d23fed0  openspec/changes/replace-pilot-registration-with-original-upload/design.md
915e229b0f5ac04da49d207ea77031a74805dd227ec366d58bfbf6e69c1ea6e9  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
4794ca3bddee88cccd51fcb53cce03ef75e69cffa6d967dd7b5c7fde9233067c  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
6dfde57f38f48a748b3854cc55b1d53c8247b11988311789fd0a3d7c31e5c998  tests/Support/AssignmentOrderOriginalRemainingMatrix.php
28844c71d01e37309a806bc7d6afc4bb77d0253d850070364be5061528fdfc39  tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
```
