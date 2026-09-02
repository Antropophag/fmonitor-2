# INSPECTION-ITEM-COMPLETE-001 — Gate 5 Standards rereview v3

Date: 2026-09-01  
Reviewer: `/root/item_code_standards` (independently tasked; did not author the
reviewed specification, tests, implementation, or corrective changes)  
Reviewed fixed point: `9abe0c42913d0f2598e866d38b9b357327e48b13` plus the
current slice-owned working-tree changes  
Verdict: `APPROVED`

## Scope

I reinspected the full slice, including the approved executable specification,
latest independent test approvals, application policy and transaction flow,
MariaDB adapters, production factory/configuration, object-to-case resolver,
`ChecklistSync` composition, raw `PilotE2ECoordinator` endpoint admission,
schema fixture adjustment, architecture checker and baseline.

Unrelated dirty worktree changes were excluded from the verdict.

## Independent verification

With a healthy isolated `compose.test.yaml` database I ran:

```text
php tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 prefix validation before DB access

php tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring

php tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 raw HTTP endpoint admission

php tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
PASS: InspectionEvidence SQL owner policy

tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

python3 tools/architecture/tests/test_debt_fingerprint.py
Ran 18 tests ... OK

git diff --check
PASS
```

I then ran `docker compose -f compose.test.yaml down -v --remove-orphans`.
Final `docker compose ... ps --all` was empty.

## Finding closure

### STANDARDS2-01 — closed

The canonical prefix contract now belongs to
`ProductionInspectionEvidenceConfig` itself: construction rejects non-ASCII
identifier characters and prefixes longer than 25 bytes. `ChecklistSync`
constructs this validated configuration before installing either the default
resolver or application composition. `MariaDbInstallationCaseIdResolver`
accepts the validated config value object rather than a raw string, so its first
SQL identifier cannot precede validation. The factory consumes that same config,
eliminating resolver/factory grammar drift.

The independently approved focused test uses an unconnected handle and proves
that `bad-prefix`, 26 ASCII bytes and non-ASCII input all fail before database
access. Canonical characterization fixture prefixes were brought within the
same <=25-byte contract rather than weakening validation.

### Endpoint admission risk — closed

The real operations POST now preserves legacy admission for photos and other
checklist operations while permitting `item_completed` to reach the application
when the authenticated active user holds exact
`inspection.item.complete`, independent of current control-engineer assignment.
The application remains the authoritative receipt-time database recheck.

Sync-context admission includes the same exact capability so a capability-only
engineer can obtain a real session/CSRF token. The independently approved raw
HTTP test starts the production router, uses a capability-only actor distinct
from the assigned engineer, sends actual HTTP bytes, and proves admission,
mapping, unchanged evidence for its rejected probe, non-item isolation, and
bounded cleanup. The earlier direct-`ChecklistSync` test is no longer the sole
endpoint evidence.

### Prior structural findings — remain closed

- Object-to-case resolution is a named MariaDB read adapter; the legacy
  `ChecklistSync` SQL fingerprint is restored and no new HTTP SQL debt is added
  to the baseline.
- The seven `MariaDbInspection*` adapters are conventionally formatted,
  cohesively split and each below the 150-line hotspot threshold.
- The new module contains no runtime DDL. Schema drift fails closed without
  repair or business mutation.
- Command values use prepared parameters. Table identifiers derive from the
  validated config. Connections remain caller-owned, clocks are injectable,
  and concurrent applications use separate connections.
- Authorization is rechecked before replay; assignment is audit context rather
  than object scope. Accepted facts and installer snapshots are appended inside
  the revision-locked transaction, while rejected paths roll back.
- The architecture checker confines inspection SQL to named MariaDB adapters.
  The baseline adds only the reviewed application command seam detections.
- The schema-test fixture expansion supplies canonical identity, assignment,
  template payload and revision inputs without weakening exact schema checks.

## Gate decision

All Standards findings are closed for the reviewed slice. Focused public-seam,
raw endpoint, SQL-owner and architecture verification is green, and the test
environment was removed. Gate 5 Standards v3 is `APPROVED`.
