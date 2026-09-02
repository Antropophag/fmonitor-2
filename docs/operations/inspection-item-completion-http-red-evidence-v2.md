# INSPECTION-ITEM-COMPLETE-001 HTTP wiring RED evidence v2

Date: 2026-09-01

This append-only Gate 2 record supersedes the HTTP behavior contour in v1 after
owner approval of the exact adapter result mapping. Production, approved
artifacts, migrations and legacy operation implementations were not edited.

## Corrected executable contour

`tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php`
now fixes the complete approved mapping matrix:

- `ACCEPTED` -> `accepted`;
- `DUPLICATE` -> `duplicate`;
- `STALE_REVISION` and `OPERATION_PAYLOAD_CONFLICT` -> `conflict`;
- `INSPECTION_SCHEMA_UNAVAILABLE` ->
  `PilotHttpInfrastructureUnavailable`, the existing retryable HTTP path;
- `ACTOR_NOT_AUTHORIZED`, `INVALID_COMMAND`, `CASE_NOT_FOUND`,
  `CASE_NOT_WORKING`, `CHECKLIST_TEMPLATE_UNAVAILABLE`,
  `CHECKLIST_ITEM_UNKNOWN`, `INSTALLER_NOT_ASSIGNED`, and
  `INSTALLER_SNAPSHOT_INCOMPLETE` -> `rejected`.

Every ordinary mapping asserts the returned revision and exactly one invocation
of public `InspectionRecording::completeItem`. The command projection proves
that HTTP object `4512` is resolved to distinct installation case `9512`, so the
test no longer assumes equality. It also proves the trusted authenticated user
id overrides an untrusted client `actorUserId`.

The final approved resolver semantics are covered independently: no current
case returns exact `{status: rejected, revision: 0}` without a seam call;
multiple current cases propagate `PilotHttpInfrastructureUnavailable`; and an
unexpected resolver/database exception is translated to that same retryable
infrastructure exception. None of these three paths invokes `completeItem`.

A syntactically valid `photo_uploaded` operation is sent after the item matrix.
With the deliberately unconnected test `mysqli`, reaching its retained legacy
DB-backed path produces the expected mysqli failure, while the spy invocation
count remains unchanged. This distinguishes branch isolation from an early
invalid-envelope rejection.

The SQL-owner guard from v1 remains independently green.

## Reproduction and classification

```text
$ php -l tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php

$ tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
PHP Fatal error: Uncaught Error: Unknown named parameter $inspectionRecording
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
```

The current adapter lacks the injected public application seam (and therefore
the narrower object-to-case resolver required to build its command). PHP starts
normally and no external service is required. This is the intended
`RED_ASSERTION`, not setup failure. Later assertions remain reviewable and will
exercise the full mapping once minimal composition exists.

## SHA-256

```text
c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb  specs/INSPECTION-ITEM-COMPLETE-001.md
d3d2b90ef251e9fed550d23c666a4dd42b69eefcffc95e9036857bf2f949262c  openspec/changes/migrate-inspection-item-completion/specs/inspection-evidence/item-completion/spec.md
b78cfe90c90826d6185cf41a883213cf0643f9685cb9120bdd6dd82abfe6eb04  docs/operations/inspection-item-completion-gate1-owner-approval.md
927c380a70bf5636df68df62ae649ac130d964f997abc4fa20d197af9cef6b4f  tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
2d60153605fbd51aebb1ddaa875f1cbce26acaddb45a25ef11bde786c7b240a3  tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
eba45bff34689c54088e89b9d4801c8c16bef2b420b3abdaed7f9f98e8c7bef6  app/PilotHttp/ChecklistSync.php
```

This evidence does not mark independent test review, Gate 3, or implementation
complete.
