# INSPECTION-ITEM-COMPLETE-001 HTTP wiring RED evidence v3

Date: 2026-09-01

This append-only Gate 2 record supersedes v2 for independent rereview. It is
bound to the final Gate 1 executable spec and OpenSpec delta approved by the
owner. No production, specification, migration, or legacy-operation file was
edited by this test revision.

## Review corrections

`tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php`
uses observable resolver and recording spies. It now asserts:

- every `item_completed` attempt resolves external `objectId=4512` exactly
  once, never resolves the case id again, and invokes `completeItem` exactly
  once when resolution returns canonical case `9512`;
- `INSPECTION_SCHEMA_UNAVAILABLE` is returned by exactly one recording call
  before `ChecklistSync` maps it to `PilotHttpInfrastructureUnavailable`;
- zero current cases returns exact `{status: rejected, revision: 0}` after one
  resolver call and no recording call;
- ambiguity and raw resolver/database failure each make one resolver call, no
  recording call, and use `PilotHttpInfrastructureUnavailable`;
- the syntactically valid `photo_uploaded` contour makes zero resolver calls
  and zero recording calls while reaching the retained legacy DB-backed path.

The previously approved complete status/revision mapping matrix, trusted actor
translation, unequal object/case identifiers, and SQL ownership guard remain
unchanged.

## Reproduction

```text
$ php -l tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php

$ tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
PHP Fatal error: Uncaught Error: Unknown named parameter $inspectionRecording
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php

$ php tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
PASS: InspectionEvidence SQL owner policy
```

The missing injected recording/resolver composition is the intended product
behavior failure. PHP and the test bootstrap start normally and this contour
requires no database service, so the runner classification is `RED_ASSERTION`,
not setup failure.

## Exact Gate 1 and executable hashes

```text
c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb  specs/INSPECTION-ITEM-COMPLETE-001.md
d3d2b90ef251e9fed550d23c666a4dd42b69eefcffc95e9036857bf2f949262c  openspec/changes/migrate-inspection-item-completion/specs/inspection-evidence/item-completion/spec.md
b78cfe90c90826d6185cf41a883213cf0643f9685cb9120bdd6dd82abfe6eb04  docs/operations/inspection-item-completion-gate1-owner-approval.md
8f68744fc4d0409ef27508fb3943328ea48ef5d75c62eaf4658f86b8c758bd86  docs/operations/inspection-item-completion-gate1-rereview.md
638658b9ed5300d0ae3f6dfb32d10cf4600ea1587415c3d50228ed0185de67c7  docs/operations/inspection-item-completion-gate1-composition-amendment-approval.md
8c02aa99713b7307406f787a4ee3a7d9b197830d700337f1f041fafb539333f5  docs/operations/inspection-item-completion-gate1-composition-amendment-rereview.md
f6021844f2ab42b8a09d556b5ef9f043a54cbd4f8e28e4d7c0cae59b72282052  tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
2d60153605fbd51aebb1ddaa875f1cbce26acaddb45a25ef11bde786c7b240a3  tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
eba45bff34689c54088e89b9d4801c8c16bef2b420b3abdaed7f9f98e8c7bef6  app/PilotHttp/ChecklistSync.php
edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd  tools/verification/run.sh
bcf507eba9010d9a0b1ced6101b7800fa919bcae88eae1d2b9f81c9837156b22  tools/architecture/check.py
```

This evidence does not mark independent test review, Gate 3, implementation,
or code review complete.
