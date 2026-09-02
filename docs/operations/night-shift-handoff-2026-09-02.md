# Night shift handoff — 2026-09-02

Window start: 2026-09-01 23:13 MSK. This record distinguishes completed gated
work from planning/WIP; it is not an approval substitute.

## TEST-USER-READY status

**AMBER.** Canonical completion v10 and planning v9 ownership are DONE.
Architecture, migration, unit and characterization contours are materially
stronger. Full verification remains blocked only by separately owned RBAC/UI
fixtures, host session storage and combined-PDF E2E.

## Completed tonight

### INSTALLATION-COMPLETION-SCHEMA-001 — DONE 15/15

- Owner approved exact Gate 1 reviewed hash.
- Multiple true RED/test-review correction cycles removed false fixture causes.
- Canonical v10 owns immutable roots + append-only correction chain.
- Runtime completion DDL removed from queue/card/checklist/POST/bootstrap.
- Exact missing/drift/DML-only, preservation, prefix/collation, crash-index and
  caller `FOREIGN_KEY_CHECKS` matrices GREEN.
- Migration refactored to one structured descriptor + DDL renderer + derived
  exact fingerprint, without threshold gaming.
- Built image/fresh lifecycle/architecture 7/7 and v10 catalogue integrations
  GREEN; final code review APPROVED:
  `reviews/code/INSTALLATION-COMPLETION-SCHEMA-001.md`
  (`8dc867197436386c4215a9d6d05854fb64e08e6cbd098ce5dec7599e644be704`).

### INSPECTION-PLANNING-SCHEMA-001 — DONE 13/13

- Canonical v9 exact schedules/events ownership and DML-only consumers landed.
- Runtime scheduling/calendar/queue/control/bootstrap DDL removed.
- Schema/runtime/calendar/schedule characterization, v10 runner integration,
  architecture/lint GREEN.
- Gate 5 maintainability correction replaced compressed duplicated DDL/manifest
  with structured definition/renderer/fingerprint modules.
- Final code review APPROVED:
  `reviews/code/INSPECTION-PLANNING-SCHEMA-001.md`
  (`a0fd1eb627f119f33e413c3711b3f38194eba023b0ca961c091a3401ce306500`).

### Verification/harness integration

- All v10 full-catalogue fixtures independently reviewed and approved.
- Completion, calendar and OTIZ characterization GREEN on v10.
- Built-image canonical runner updated/reviewed and GREEN.
- `make fresh-test-verify` reproduced classified failures and completed teardown
  (`teardown_status=0`, test Compose empty).
- Latest `make verify`: reset/migrate v10, architecture, lint, unit,
  completion/planning/catalogue DB, characterization and diff-check PASS;
  terminal `count=2 stages=db-test,e2e-test` from the remaining slices below.

## Current work / READY queue

### Local RBAC contract — 16/17

Stable application seam and first `GET /pilot/objects → objects.read` consumer
have approved Gates 1–5. Task 6.2 remains open until follow-up fixtures/full
verify GREEN. Four planning packages are 4/4 strict-valid and independently
`APPROVED_FOR_GATE1_DRAFT`:

- `pilot-object-read-rbac-fixtures` — exact GET list-only fixture scope;
- `pilot-prepare-rbac-fixtures` — explicit GET route migration, separate local
  permission and process capability gates, POST excluded;
- `pilot-e2e-rbac-fixtures` — actor18 canonical list grant, actor19 legacy-only
  denial, isolated revoke/main grant preservation;
- `pilot-e2e-combined-pdf` — downstream artifact dependency.

Planning reviews:

- `docs/operations/pilot-object-prepare-rbac-fixtures-planning-rereview-v2.md`
  (`6d696e1d...`);
- `docs/operations/pilot-e2e-rbac-combined-pdf-planning-rereview-v2.md`
  (`c8955772...`).

Existing `LocalRbacFixture` and four route test edits are explicitly **WIP / NOT
READY**; residuals remain card 503, prepare 403, list/UI representation mismatch.
Do not treat their hashes as approved tests or close RBAC Done.

### Combined PDF

OpenSpec `pilot-e2e-combined-pdf` is 4/4 strict-valid and planning-approved for
Gate 1 drafting. It requires a complete `PILOT-E2E-FLOW-001 v0.5` amendment
superseding v0.4 two-HTML routes/oracle, exact one-PDF GET/HEAD/403/404/503,
semantic three-page decode, fault/reload/concurrency snapshots and cleanup.
Current E2E reproducibly reaches artifact boundary then fails stale appendix
download with `ArtifactNotFoundException`; it is not an RBAC failure.

### Session storage

OpenSpec `define-pilot-session-storage-contract` is 4/4 strict-valid and
planning-approved for Gate 1 drafting. It replaces two hardcoded session owners
with exact config/root/instance modes, an explicit buffered handler lifecycle,
fail-safe old-ID→tombstone→new-ID regeneration, literal GET/HEAD/POST 503
mapping, cookie/GC/restart preservation and task-owned cleanup.
Planning review:
`docs/operations/pilot-session-storage-planning-rereview-v2.md`
(`81b9aca0...`). Current host login test still fails before cookie because
`/home/fmonitor/...` does not exist.

## NEEDS_OWNER

One exact Gate 1 decision is ready now:

```text
CLASSIFICATION-PROVENANCE-SCHEMA-001
SHA-256 a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed
```

Independent verdict `READY_FOR_OWNER_APPROVAL`:
`docs/operations/classification-provenance-schema-gate1-rereview-v2.md`
(`6a731ecb...`). It fixes canonical v11, exact ten-column table, prefix 25/26,
no-ledger bounded winner/loser race, exact pre-source native/history/active CLI
failures and mandatory native PILOT_ONLY non-atomic contrast. Gate 2 is
prohibited until explicit approval.

No other product decision is ready/required: RBAC/PDF/session packages still
need executable Gate 1 drafts and independent reviews first.

### Update after initial handoff

Exact Gate 1 drafts/reviews для RBAC/PDF/session завершены. Controlling morning
batch теперь:
`docs/operations/morning-owner-approval-batch-2026-09-02.md`.
Он содержит шесть independently reviewed decisions (classification, object
list RBAC, prepare GET/HEAD RBAC, E2E list RBAC, joint combined PDF/E2E v0.5,
session storage). Фраза выше про ещё не готовые executable drafts superseded.

## Remaining full-verify blockers

1. RBAC/UI fixtures: object card/list/prepare/UI shell (separate planned slices).
2. Host login session root: hardcoded `/home/fmonitor` (session-storage slice).
3. Combined PDF: direct E2E and inherited demo-bootstrap observation of same
   stale appendix contract (combined-PDF slice).

`define-pilot-route-csp` remains 12/14 because demo-bootstrap entrypoint cannot
PASS until combined-PDF E2E does; CSP-owned classifier/assets/HTTP auth/final
completion HTML/architecture are GREEN.

## Safety / worktree

- Shared dirty worktree preserved; no pre-existing file or history deleted.
- No destructive production/reset operation performed.
- Disposable test Compose was torn down successfully after fresh lifecycle.
- Architecture check: PASS, 7 rules.
- Key active/completed OpenSpec changes strict-valid; `git diff --check` PASS.
