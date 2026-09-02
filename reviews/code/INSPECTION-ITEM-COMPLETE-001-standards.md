# INSPECTION-ITEM-COMPLETE-001 — Gate 5 Standards review

Date: 2026-09-01  
Reviewer: `/root/item_code_standards` (independently tasked; did not author the
specification, tests, production implementation, or verification evidence)  
Reviewed fixed point: `9abe0c42913d0f2598e866d38b9b357327e48b13` plus the
slice-owned working-tree changes for `INSPECTION-ITEM-COMPLETE-001`  
Verdict: `CHANGES_REQUESTED`

## Scope and evidence

I reviewed the approved executable specification, Gate 1 records, latest
independent test approvals, the new `app/InspectionEvidence` module,
`ChecklistSync` composition, `PilotE2ECoordinator` admission, architecture
checker/baseline changes, and the schema-test fixture adjustment. Unrelated
dirty worktree changes, including the preserved rapid-pilot changes, were not
treated as part of this slice.

Independent checks executed:

```text
tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

php -l app/PilotHttp/ChecklistSync.php
No syntax errors detected

php -l app/InspectionEvidence/*.php   # each file individually
all passed

php tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring
```

The focused GREEN and current architecture-check result are credible, but they
do not close the standards findings below.

## Blocking findings

### STANDARDS-01 — case resolution adds HTTP-owned SQL debt and rebaselines it

`ChecklistSync::resolveInstallationCaseId()` delegates to the legacy private
`case()` query in `app/PilotHttp/ChecklistSync.php`. The slice changes that SQL
owner's behavior so multiple rows now throw `AMBIGUOUS_INSTALLATION_CASE`, and
then adds the resulting new SQL fingerprint to
`tools/architecture/baseline.json` (`27dc2b943a426fca`) while removing the old
fingerprint.

This conflicts with `docs/architecture/guardrails.md`: new business persistence
SQL belongs in a named MariaDB adapter, the baseline is not an allow-list, and a
deliberate baseline exception requires an ADR plus explicit architecture
review. No slice ADR approving new HTTP SQL ownership is present. The approved
spec already supplies the right design direction: an injected case-id resolver
seam. Its production implementation should be a named MariaDB read adapter (or
another compliant application adapter), leaving HTTP as translation and
composition only. The baseline should not acquire a new HTTP SQL fingerprint.

This is not cosmetic. Resolver ambiguity is new target behavior, so hiding its
query inside pre-existing HTTP debt makes the target boundary harder to enforce
and allows future domain lookup changes to grow in the adapter.

### STANDARDS-02 — new MariaDB production adapters are not maintainable source

The following new production files are almost entirely single physical lines:

- `MariaDbInspectionAuthorization.php`
- `MariaDbInspectionTransaction.php`
- `MariaDbInspectionCaseDirectory.php`
- `MariaDbInspectionTemplateDirectory.php`
- `MariaDbInspectionEvidenceWriter.php`
- `MariaDbInspectionEvidenceReader.php`
- `MariaDbInspectionEvidenceEnvironment.php`

They combine SQL, transaction state, result mapping, loops, and exception
behavior without normal line breaks or readable structure. This appears to
evade the 150-line hotspot ratchet by compressing physical lines rather than by
creating genuinely deep, reviewable modules. It materially obstructs review of
bind types, nullable fields, transaction behavior, and append-only guarantees.
Reformat these classes conventionally, retaining the existing module split and
keeping each class under the hotspot threshold through cohesive design rather
than minification. `InspectionEvidence.php` and the hand-written `foreach`
require list in `ChecklistSync.php` should receive the same readability pass.

### STANDARDS-03 — changed endpoint admission and failure rendering lack a real endpoint test

`PilotE2ECoordinator::checklist()` changes authorization admission for operation
POSTs: `item_completed` can bypass the legacy `$allowed` object-assignment/editor
condition when `HttpUser::can('inspection.item.complete')` succeeds, while the
application seam performs the authoritative database recheck. It also relies on
the outer coordinator catch to render resolver/schema failures as HTTP 503 and
then appends a live projection to successful/domain-rejected responses.

The approved HTTP wiring test calls `ChecklistSync::accept()` directly with
spies. It does not execute the real route, trusted identity/session/CSRF path,
the altered admission expression, HTTP status mapping, projection append, or
the 503 catch. Therefore a plausible endpoint regression could still ship—for
example, an engineer with the exact capability but no assignment being rejected
before the seam, a user without the capability reaching mutation, or
infrastructure failure being rendered as 422/empty response. Because this is
new security-sensitive entry-point behavior, Gate 5 cannot infer it from the
application test. Add the smallest deterministic real-endpoint test covering:

1. active exact-capability engineer, not assigned to the object, reaches the
   public application seam;
2. absent exact capability cannot mutate (regardless of legacy checklist
   access/assignment);
3. resolver/schema infrastructure failure renders HTTP 503 `retryable`;
4. an accepted result has the intended HTTP status and response shape.

Per `docs/development-process.md`, this newly required test must return to Gate
2 and receive independent Gate 3 approval before the corresponding GREEN is
reviewed again.

## Non-blocking observations

- The production factory validates the table prefix, sets `utf8mb4`, accepts a
  caller-owned connection, and supports an injected clock. `ChecklistSync`
  creates it lazily only for `item_completed`; the valid non-item probe supports
  preservation of legacy branches.
- Prepared statements are used for command values. Table identifiers are
  derived from the factory-validated prefix in the new MariaDB module. No
  runtime DDL was found in the module.
- The schema-test fixture expansion is scoped to making the existing runtime
  fixture structurally representative of canonical identity/template/order
  inputs. It does not weaken the exact schema assertions.
- The architecture checker correctly recognizes `MariaDb*` files as the only
  SQL owners under `app/InspectionEvidence`, and the supporting policy test is
  useful. The deliberate public seam addition is in one application module,
  although listing both interface and implementation reflects the checker's
  syntactic detection rather than two domain commands.
- `InspectionEvidence` rechecks active status and exact capability before replay
  and transaction work, keeps assignment as audit context rather than
  authorization, and rolls back rejected transactional paths. Focused tests
  cover these application-level invariants.

## Gate decision

Gate 5 Standards is `CHANGES_REQUESTED`. Approval requires closing
STANDARDS-01 and STANDARDS-02, and adding/reviewing the security-sensitive real
endpoint test required by STANDARDS-03. After those changes, rerun focused
tests, architecture checks, and the relevant verification suite and request an
independent standards rereview.
