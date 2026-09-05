# Selection authorization handoff — current evidence

Date: 2026-09-05. Author: `/root`.
Inspected base: `d4a40748752e8d97ebc8519ebae8f49d350e72c1`.

`MariaDbProcessUserDirectory::actorCanPrepareAssignmentOrder` checks
`assignment_order.prepare`. With local identity tables it reads role permission
rows; its legacy branch instead checks explicit process capability rows.

The original command's `AssignmentOrderOriginalMariaDbAuthorizer` reads local
users and active role assignments, and separately requires exact
`fm2_process_user_capabilities` for the requested upload/correct action. It does
not use the old prepare capability as a fallback.

The current local role catalogue grants prepare to FKR operator, not manager.
The owner-approved target permits original upload/correction for both FKR and
FKR manager; OTIZ global document read does not grant either action. Therefore
the new selection contract must explicitly specify its authority and handoff,
including two negative controls: prepare-only must not silently confer upload,
and original-read-only must not confer selection or upload.

This is not a new grant decision and does not choose a permission code. It
identifies two distinct persisted authorization mechanisms that the executable
Gate 1 must reconcile. No source fallback, wildcard or display-name permission
inference is authorized.

Exact SHA-256:

```text
be0e8dede13a68086bbcbc42bf8944c8b5cca721b40039641c3b16572d774768  app/InstallationProcess/MariaDbProcessUserDirectory.php
232c56f7009ee4416a35d3942dc2ee46053dc383fb0c11e769f418337f9a158d  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
16e1ac3b7314a773a87aab2515ac0dd8f69db66119558f759af4cdd50870ea6f  app/RapidPilot/LocalRoleCatalog.php
```

No production/tests/grants/spec changes; historical predecessor contracts remain
preserved. Independent selection planning review is pending separately.
