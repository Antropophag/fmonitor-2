# INSPECTION-ITEM-COMPLETE-001 HTTP wiring RED evidence

Date: 2026-09-01

This Gate 2 increment is limited to the HTTP/ChecklistSync adapter boundary of
the approved `INSPECTION-ITEM-COMPLETE-001` contract. It does not edit
production, the approved spec, migrations, or other operation branches.

## Executable contour

`tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php`
uses a spy implementing the public `InspectionRecording` interface and an
intentionally unconnected `mysqli`. A conforming `item_completed` branch must
therefore translate and delegate without touching legacy SQL. The assertions
pin:

- exactly one `completeItem` call;
- trusted `HttpUser::id` as `actorUserId`, ignoring the client-supplied field;
- all remaining `CompleteInspectionItem` scalar/list fields;
- `ACCEPTED` and receipt-time `ACTOR_NOT_AUTHORIZED` result translation;
- no `completeItem` call from a non-item operation and preservation of its
  existing invalid-envelope result.

The independently runnable
`tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php`
ratchets module SQL ownership: a PHP file below `app/InspectionEvidence` may
contain business DML keywords only when its basename starts with `MariaDb`.

## Reproduction

Commands:

```text
php -l tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php -l tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
```

Observed:

```text
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
No syntax errors detected in tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
PHP Fatal error: Uncaught Error: Unknown named parameter $inspectionRecording
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
PASS: InspectionEvidence SQL owner policy
```

The failure is the intended behavior assertion: the production `ChecklistSync`
composition seam is absent. Construction reached PHP normally and required no
database or external fixture, so this is not an environment/setup failure.
The policy guard is already green and is recorded separately from the required
HTTP behavior RED.

## SHA-256

```text
cdd85ba009e3bbb6993fd50b26ab199caf5017086d43d43bc474586ff0982e7b  specs/INSPECTION-ITEM-COMPLETE-001.md
000eb8e81fc3c4e016e67edf26be9e322d7a553dc3a72dd9184e8fa0f7858f0f  tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
2d60153605fbd51aebb1ddaa875f1cbce26acaddb45a25ef11bde786c7b240a3  tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
eba45bff34689c54088e89b9d4801c8c16bef2b420b3abdaed7f9f98e8c7bef6  app/PilotHttp/ChecklistSync.php
bcf507eba9010d9a0b1ced6101b7800fa919bcae88eae1d2b9f81c9837156b22  tools/architecture/check.py
```

This evidence does not mark independent test review, Gate 3, or implementation
complete.
