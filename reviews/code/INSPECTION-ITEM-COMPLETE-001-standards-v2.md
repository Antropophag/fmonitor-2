# INSPECTION-ITEM-COMPLETE-001 — Gate 5 Standards rereview v2

Date: 2026-09-01  
Reviewer: `/root/item_code_standards` (independently tasked; did not author the
reviewed specification, tests, implementation, or corrective changes)  
Reviewed fixed point: `9abe0c42913d0f2598e866d38b9b357327e48b13` plus the
current slice-owned working-tree changes  
Verdict: `CHANGES_REQUESTED`

## Reproduced evidence

```text
php tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring

php tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
PASS: InspectionEvidence SQL owner policy

tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

python3 tools/architecture/tests/test_debt_fingerprint.py
Ran 18 tests ... OK

git diff --check
PASS
```

I also inspected the independently approved v6 corrective test review and the
current production diff.

## Prior finding closure

### STANDARDS-01 — partially closed

The object-to-case query is no longer hidden in the legacy `ChecklistSync::case`
method. It is owned by the readable named adapter
`app/PilotHttp/MariaDbInstallationCaseIdResolver.php`, which is an allowed
MariaDB read-adapter location. The old `ChecklistSync` SQL fingerprint is
restored, and the architecture baseline now changes only for the reviewed
public seam. No new HTTP SQL-debt fingerprint is baselined.

One SQL-safety/composition defect remains; see STANDARDS2-01.

### STANDARDS-02 — closed

All seven new `MariaDbInspection*` adapters are conventionally formatted and
cohesively split. Each remains below 150 physical lines (41–120 lines), while
the behavior is now reviewable without minification. Bind types, nullable
snapshot fields, transaction locking, immutable inserts, and revision update
are readable. `InspectionEvidence.php` is also conventionally formatted and
remains below the hotspot threshold.

### STANDARDS-03 — superseded, with integration risk retained

The slice no longer changes `PilotE2ECoordinator` checklist admission or result
rendering; its diff there is namespace qualification only. Therefore the
previous demand for a new raw-endpoint test specifically to approve a changed
coordinator branch no longer applies to these corrective changes. The v6 public
`ChecklistSync::accept` test appropriately covers the adapter behavior actually
changed by this slice, including malformed delegation, typed mapping, resolver
outcomes, trusted actor, unequal object/case ids, and non-item isolation.

The unchanged endpoint restriction remains a release integration risk described
below and must not be represented as evidence that broad engineer access works
through the browser route.

## Blocking finding

### STANDARDS2-01 — resolver uses an unvalidated SQL identifier before factory validation

`MariaDbInstallationCaseIdResolver::__invoke()` interpolates
`$this->tablePrefix` directly into a table identifier:

```php
"SELECT id FROM `{$this->tablePrefix}fm2_installation_cases` ..."
```

The default `ChecklistSync` flow invokes this resolver before
`inspectionRecording()` constructs `ProductionInspectionEvidenceConfig` and
calls `ProductionInspectionEvidenceFactory::create()`. Consequently the
factory's canonical ASCII/0..25-byte prefix validation does **not** occur before
this database access.

This violates the approved production-composition contract, which says
`processTablePrefix` is validated with the canonical runner contract before DB
access. It is also a direct SQL-identifier safety defect: prepared parameters
protect `objectId`, but not the interpolated table prefix. Trusted deployment
configuration reduces exposure but does not make the adapter's contract safe or
coherent.

Validate the prefix before resolver SQL using the same canonical contract,
preferably through one shared validator/value object so the resolver and factory
cannot drift. Add a focused expectation proving an invalid prefix fails before
any resolver/database access. Because this is a new test expectation discovered
at Gate 5, it requires independent Gate 3 review before the next GREEN/rereview.

## Explicit high-risk integration observation

The real `PilotE2ECoordinator::checklist()` still performs the unchanged legacy
gate:

```text
opened && (checklist.edit || current control engineer)
```

before calling `ChecklistSync::accept()`. The target application correctly uses
exact `inspection.item.complete` and ignores current engineer assignment, but a
user who has only that exact capability and is not assigned can still be denied
by the outer route before reaching it. No current endpoint test demonstrates the
owner-approved statement that every capable engineer can mark every object via
the production browser/offline route.

This rereview does not reopen the earlier coordinator-change test finding,
because that coordinator behavior is unchanged in the slice. Nevertheless it
is an unverified and apparently contradictory release-entry-point condition.
The final Spec review/release decision must either demonstrate that every holder
of `inspection.item.complete` necessarily also receives `checklist.edit`, or
route `item_completed` according to the exact capability and cover that change
through Gates 2–5. It must not claim raw endpoint coverage from the direct
`ChecklistSync` test.

## Other standards observations

- The new inspection module contains no runtime DDL. The independently reviewed
  schema-drift test proves fail-closed behavior without schema repair or DML.
- Command values use prepared parameters, caller-owned connections remain open,
  the fixed/default clocks are explicit, and concurrent workers use separate
  application/connection instances.
- The architecture checker confines new inspection SQL to `MariaDb*` adapters,
  and its 18 policy tests pass. The only baseline additions are the interface
  and implementation detection of one reviewed application command seam.
- The existing schema-test fixture changes add the canonical identity,
  assignment, template payload, and revision inputs needed by the production
  adapter without weakening schema assertions.

## Gate decision

Gate 5 Standards remains `CHANGES_REQUESTED` solely on the new blocking
STANDARDS2-01 prefix-validation defect. After its reviewed correction, rerun the
focused tests and architecture checks and request a v3 Standards rereview. The
endpoint admission mismatch must additionally be resolved or explicitly closed
with evidence by the Spec/release review.
