# TEST-USER-READY repository audit — 2026-08-31

## Scope and method

Read-only audit of the checked-in product contracts, production/pilot code, tests, deployment configuration, and the legacy source in `../fmonitor`. Claims below cite the first-party source that owns them. The audit does not treat undocumented rapid-pilot behavior as an accepted requirement: the product contract says that state is derived from issued documents and confirmed facts, and screens must not directly edit history (`PRODUCT.md:34-39`, `PRODUCT.md:50-57`).

## Executive assessment

The repository has a substantial, reviewed application seam for assignment-order preparation, registration, opening work, authorization, artifacts, workforce catalog, and a real MariaDB E2E path. It is **not yet TEST-USER-READY as an autonomous delivery system**. The largest release risks are:

1. runtime schema ownership is split between the canonical v1–v4 runner and many `ensureSchema`/bootstrap DDL sites;
2. business SQL remains embedded in HTTP/view and rapid-pilot files;
3. there is no `make verify`, architecture check, clean DB migration/reset contract, or suite taxonomy;
4. rapid-pilot contains state-changing domain behavior and production routing, despite its own lighter delivery exception;
5. OpenSpec is not present, and the root `AGENTS.md` is still the workflow definition rather than a short constitution/navigation entrypoint;
6. the critical post-opening paths (inspection, checklist, completion, OTIZ/payment) are mostly rapid-pilot-owned and are not yet behind reviewed application seams.

## Current architecture

### Reviewed application core

`app/InstallationProcess/InstallationProcess.php` is the main explicit command seam. It is already exercised at the same seam by production HTTP composition (`app/PilotHttp/PilotE2ECoordinator.php:134-167`) and is backed by a composition root that wires MariaDB adapters (`app/InstallationProcess/ProductionInstallationProcessFactory.php:14-39`). The pilot data model explicitly requires tests and controllers to use the same `InstallationProcess` interface (`docs/fmonitor-2-pilot-data-model.md:185-195`).

The mature reviewed slice covers prepare → register → open. Its E2E verifier checks POST seams, CSRF, stale-revision rejection, immutable order history, exact capability authorization, append-only events, and durable opened state (`tests/InstallationProcess/pilot_e2e_flow_001_test.php:70-115`). Repository policy requires approved spec, RED, independent test review, implementation, and independent code review (`AGENTS.md:5-17`; `docs/development-process.md:11-59`), and corresponding records exist for this seam under `specs/`, `reviews/tests/`, and `reviews/code/`.

### HTTP and rapid-pilot composition

There are two overlapping delivery surfaces:

- `app/PilotHttp` provides the reviewed HTTP application and calls `InstallationProcess` for the core commands (`app/PilotHttp/PilotE2ECoordinator.php:134-167`).
- `rapid-pilot/router.php` intercepts authentication, user administration, scheduling, completion, object queue, calendar, and OTIZ before delegating remaining routes to the production HTTP entrypoint (`rapid-pilot/router.php:195-239`). It also patches JavaScript response bytes at runtime (`rapid-pilot/router.php:93-103`).

The rapid-pilot subtree explicitly exempts itself from mandatory SSD/TDD and independent reviews (`rapid-pilot/AGENTS.md:5-16`). That exception conflicts with the mission's new boundary if state-changing product behavior continues to land there; the root invariant still says commands change state and screens do not edit historical facts (`AGENTS.md:19-24`).

### Dependency direction

The core `InstallationProcess` namespace does not import `PilotHttp`, but it does mix application policy, concrete MariaDB adapters, migrations, renderer/storage, import tooling, and its composition root in one directory. Several application-facing services are concretely typed to MariaDB classes; for example `AssignmentOrderArtifactService` depends directly on `MariaDbProcessUserDirectory` (`app/InstallationProcess/AssignmentOrderArtifactService.php:8`). `ProductionInstallationProcessFacts` also takes three concrete MariaDB collaborators (`app/InstallationProcess/ProductionInstallationProcessFacts.php:7-13`). This is not a clean application→contract→adapter direction.

There is also a reverse production-to-pilot asset dependency: the production PDF renderer reads a logo from `rapid-pilot/assets` (`app/InstallationProcess/ProductionPdfAssignmentOrderRenderer.php:114`). This violates the intended rapid-pilot-as-adapter/reference boundary.

## DDL ownership and canonical migrations

### What is canonical today

`bin/fmonitor2-migrate.php` is a credible canonical migration entrypoint for schema versions 1–4. It validates configuration, connects explicitly, applies an ordered catalog, reports conflicts distinctly, and returns `schemaVersion: 4` (`bin/fmonitor2-migrate.php:32-60`, `bin/fmonitor2-migrate.php:79-115`). The catalog owns:

- v1 process tables;
- v2 workforce catalog;
- v3 process-user capabilities;
- v4 command-capability expansion (`bin/fmonitor2-migrate.php:79-84`).

The migration classes inspect existing schema before applying or rejecting conflicts; for example v1 refuses absent/incompatible tables through `MariaDbSchemaInspector` (`app/InstallationProcess/ProductionProcessSchemaMigration.php:21-40`, `app/InstallationProcess/ProductionProcessSchemaMigration.php:63-147`).

### Runtime DDL that bypasses it

The deployed Docker bootstrap does not invoke the canonical runner. It creates the legacy projection table, invokes only selected migration classes, creates a generation sentinel, drops capability/user tables, bootstraps OTIZ, and calls inspection `ensureSchema` (`rapid-pilot/docker-bootstrap.php:40-73`). Consequently a "fresh" environment schema is defined partly by startup code and partly by whatever screens execute later.

Production HTTP checklist requests call `ChecklistSync::ensureSchema()` before reads and writes (`app/PilotHttp/PilotE2ECoordinator.php:64-78`), and that class owns `CREATE TABLE IF NOT EXISTS` DDL (`app/PilotHttp/ChecklistSync.php:11-20`). Other runtime DDL includes:

- inspection scheduling (`rapid-pilot/InspectionSchedule.php:55-74`);
- completion facts (`rapid-pilot/CompletionFlow.php:123`);
- OTIZ schema on construction/request (`rapid-pilot/Otiz.php:41-44`, `rapid-pilot/Otiz.php:552-589`);
- migration/quarantine/projection ledgers through `ensureSchema` (`rapid-pilot/legacy-migration/MigrationQuarantineDecisionLedger.php:7`, `rapid-pilot/legacy-migration/MigratedEvidenceProjectionStore.php:9`, `rapid-pilot/legacy-migration/MigrationQuarantineRegistry.php:11`);
- history/template/baseline migration helpers (`rapid-pilot/legacy-migration/LegacyHistoryMigration.php:115-117`, `rapid-pilot/legacy-migration/LegacyChecklistTemplateSnapshot.php:39-40`, `rapid-pilot/legacy-migration/ActiveBaselineOperationalCaseConnector.php:28`).

The demo runner also creates/alters/drops tables itself (`bin/fmonitor2-pilot-demo.php:133-150`, `bin/fmonitor2-pilot-demo.php:344`). Test-local DDL is appropriate for isolated fixtures, but deployment/runtime DDL is not.

**Risk:** there is no single reproducible production schema version. A successful v4 migration does not prove checklist, inspection, completion, identity, OTIZ, and legacy-migration tables exist or match runtime expectations.

## SQL ownership

Business persistence SQL is widely distributed. The audit found SQL-bearing files in the HTTP layer (`app/PilotHttp/AccessPolicy.php`, `ChecklistSync.php`, `InstallerDirectoryView.php`, `PilotE2ECoordinator.php`, `PilotHttp.php`, `UserDirectoryView.php`) and throughout rapid-pilot screens/controllers (`Calendar.php`, `CompletionFlow.php`, `InspectionSchedule.php`, `ObjectDetails.php`, `ObjectQueue.php`, `Otiz.php`, `UserAccessView.php`).

Concrete examples:

- the user-status HTTP handler performs authorization, `SELECT ... FOR UPDATE`, and mutations in the view class (`rapid-pilot/UserAccessView.php:70-80`);
- the object queue ensures schemas and issues its own read-model SQL (`rapid-pilot/ObjectQueue.php:20-44`);
- OTIZ initializes schema and ledgers directly in its UI/controller constructor (`rapid-pilot/Otiz.php:35-45`);
- `PilotE2ECoordinator` creates a concrete process per request rather than depending on a public application contract (`app/PilotHttp/PilotE2ECoordinator.php:134-167`).

**Risk:** HTTP/UI owns business persistence and transaction semantics for several critical actions, so authorization and append-only rules cannot be proven at one public seam.

## Hotspot baseline

Current production-code line-count baseline (retain as a ratchet; size alone is not a refactoring mandate):

| File | Lines | Risk concentration |
|---|---:|---|
| `rapid-pilot/pilot.css` | 1692 | all pilot visual policy |
| `app/InstallationProcess/InstallationProcess.php` | 667 | multiple commands and domain rules |
| `rapid-pilot/Otiz.php` | 598 | UI, authorization, schema, calculation/decision persistence |
| `app/PilotHttp/PilotHttp.php` | 551 | HTTP types, routing/support implementation |
| `bin/fmonitor2-pilot-demo.php` | 376 | provisioning, DDL, lifecycle, serving |
| `app/InstallationProcess/BitrixWorkforceHistorySchemaMigration.php` | 318 | large schema migration |
| `app/PilotHttp/PilotE2ECoordinator.php` | 261 | routing, validation, command composition |
| `rapid-pilot/router.php` | 248 | interception, asset patching, composition |

No machine-checkable hotspot baseline or justification mechanism exists. Adding further behavior to `InstallationProcess.php`, `Otiz.php`, `PilotHttp.php`, or `router.php` should require an explicit justification and a non-growth default.

## Test, migration, and deployment harness

### Existing strengths

- Docker Compose pins MariaDB `11.4.7-noble`, waits for service health, keeps DB and artifact state in named volumes, and binds the pilot to loopback (`compose.yaml:3-32`, `compose.yaml:64-83`).
- `make up`, `make down`, `make reset`, and an opt-in Bitrix profile exist (`Makefile:1-38`, `compose.yaml:34-62`).
- The deployment contract verifier checks that normal startup does not depend on the sibling legacy checkout, secrets stay out of Make parsing, Bitrix is opt-in, imports are untruncated/idempotent, diagnostics redact passwords, and README documents fresh import (`rapid-pilot/verify-deployment-contract.php:18-40`).
- The suite contains isolated unit/contract tests, real MariaDB migration/persistence tests, production HTTP tests, and a full golden prepare/register/open journey (`tests/InstallationProcess/pilot_e2e_flow_001_test.php:54-118`).
- Numerous rapid-pilot behavior verifiers exist, including calendar, checklist, completion, migration routing, premium calculation, OTIZ workflow, deployment, focus, and visual contracts.

### Missing clean-checkout contract

The Makefile only exposes environment lifecycle and production import; it has no migration, unit, DB, characterization, E2E, architecture, or full verification targets (`Makefile:1-38`). Composer declares one runtime PDF dependency and no scripts/dev test runner (`composer.json:1-7`). There is no documented command that discovers and runs all `tests/InstallationProcess/*_test.php` and rapid-pilot verifiers, and no standard classification of exit/failure output as setup failure versus intended RED versus regression.

`make reset` deletes volumes (`Makefile:38`) but does not demonstrate canonical migrations independently of runtime bootstrap. The Compose healthcheck only checks two GET pages (`compose.yaml:26-31`), so it does not prove schema version, fixtures, mutation authorization, golden journey, or artifact downloads.

**Risk:** individual verifiers are valuable but discoverability and orchestration are manual; a clean checkout can become green through runtime table creation without proving migration completeness.

## Rapid-pilot behavior inventory (audit-level)

This is an initial source inventory, not acceptance of every behavior as a requirement.

| Capability | Actor / intent | State change / observable result | Source / verifier | Status evidence |
|---|---|---|---|---|
| Prepare, register and open installation | FKR prepares immutable order, records 1C number, opens actual work | order/artifacts/events; case becomes `working` | `app/InstallationProcess/InstallationProcess.php`; `tests/InstallationProcess/pilot_e2e_flow_001_test.php:81-115` | Contracted and reviewed; strongest release path |
| Checklist operation sync | control engineer records item/photo/crew evidence, including offline sync | append operation/revision/photo projection | `app/PilotHttp/ChecklistSync.php:11-20`; `app/PilotHttp/PilotE2ECoordinator.php:60-122`; `rapid-pilot/verify-checklist-current-crew.php` | Partly reviewed, but runtime DDL remains |
| Inspection scheduling | control role schedules inspections; queue/calendar reflect plan | schedule row and queue/calendar projection | `rapid-pilot/InspectionSchedule.php:21-90`; `rapid-pilot/verify-calendar-projections.php` | Rapid-pilot-owned; needs application seam/spec decision |
| Completion evidence | authorized actor records PTO act/declaration and blocks legacy completion mutation | completion fact and status projection | `rapid-pilot/CompletionFlow.php:21-123`; `rapid-pilot/verify-completion-flow.php` | Rapid-pilot-owned runtime table |
| OTIZ premium workflow | OTIZ reviews operands, calculation, decision/payment state | calculation/decision/payment and reconciliation projections | `rapid-pilot/Otiz.php:35-45`; `rapid-pilot/verify-premium-calculation.php`; `rapid-pilot/verify-otiz-workflow.php` | Critical financial path; hotspot and runtime schema owner |
| User invitation/block/unblock and role access | administrator manages pilot access | identity/status/role/audit mutations | `rapid-pilot/UserAccessView.php:70-80`; `rapid-pilot/verify-auth-hot-path.php` | Security-critical UI-owned SQL |
| Workforce sync/reconciliation | scheduled adapter ingests Bitrix workforce evidence | current catalog/history/reconciliation | `compose.yaml:34-62`; `rapid-pilot/hourly-bitrix-workforce.php`; workforce verifiers | Has worker boundary; schema ownership split |
| Legacy active/history migration and quarantine | migration operator classifies/imports/reconciles evidence | snapshots, baselines, quarantine and decisions | `rapid-pilot/legacy-migration/*`; associated `verify-*` scripts | Migration tooling, not ordinary user behavior; many self-provision schemas |
| Object queue/card/calendar/directory | staff finds next work and reads state | read projections only | `rapid-pilot/ObjectQueue.php`, `ObjectDetails.php`, `Calendar.php`; corresponding verifiers | Useful oracle; must not acquire command ownership |

Correction 2026-09-02: the owner explicitly rejected the weekly-inspection
claim that this audit previously treated as product contract. No weekly or
seven-day cadence is normative. The remaining product contract distinguishes
declared versus observed crews, requires reproducible premium calculations and
role-specific next work, makes assignment changes append-only, and requires a
registered order before opening. Those facts still support inspection/checklist
and OTIZ discovery, but they do not settle scheduling cadence or pilot
rejection/payment semantics.

## Legacy source findings

The sibling `../fmonitor` is correctly designated read-only (`AGENTS.md:19-23`). The checked-in legacy-estate audit already establishes that `fm_maintable` is only a wide projection and that checklist values/logs/photos, installer attribution, workforce observations, orders, users/roles/rights and other evidence live elsewhere (`docs/fmonitor-2-legacy-data-estate-audit.md:8-10`). It identifies 38 fixed table names plus a dynamic MDM family from first-party source and warns that only live metadata can prove the complete schema (`docs/fmonitor-2-legacy-data-estate-audit.md:55-61`).

This supports treating legacy as an oracle/integration source rather than target architecture. The existing ADR already excludes generic MDM and custom-field/view-builder platforms from new runtime behavior (`docs/adr/0001-no-generic-legacy-metadata-platform.md:7-12`).

## OpenSpec and architecture automation gaps

No OpenSpec configuration/change tree or OpenSpec command is present in the repository. Existing `specs/*.md` and review records are strong historical SSD evidence, but they have no structured lifecycle state or migration backlog linkage.

No architecture-check command exists. There is currently no automated prohibition for:

- production DDL outside canonical migrations;
- business SQL in HTTP/UI;
- application dependency on HTTP, rapid-pilot, or concrete MariaDB classes;
- new domain logic under rapid-pilot;
- hotspot growth without justification;
- multiple owners for a state-changing capability.

## Release gaps and risks, ordered by urgency

| Priority | Gap / risk | Why it threatens 2026-09-09 |
|---:|---|---|
| P0 | Runtime DDL in HTTP, screens and bootstrap | fresh environments can differ by visited routes; migration rollback/version is unknowable |
| P0 | No top-level deterministic verification contract | agents and deployers cannot distinguish setup failure from RED/regression or prove the same release gate |
| P0 | OTIZ/payment and access administration lack reviewed application seams | financial/security mutations remain UI-owned and hard to authorize/test consistently |
| P0 | No architecture guardrails | rapid migration can increase the exact debt the mission forbids |
| P1 | Checklist/inspection/completion schema and commands are split across HTTP/rapid-pilot | critical golden journey after opening is not governed at one seam |
| P1 | Rapid-pilot SSD/TDD exemption remains broad | new domain logic can bypass mandatory independent reviews |
| P1 | Canonical runner stops at v4 while deployed schema is larger | `schemaVersion: 4` is a false completeness signal |
| P1 | Hotspots lack a ratchet | parallel agents may collide and concentrate more behavior |
| P2 | Production code reads rapid-pilot asset | boundary prevents retiring/strangling pilot cleanly |
| P2 | Compose healthcheck is read-only and shallow | healthy container is not evidence of a usable test-user journey |

## Recommended bootstrap order

1. Establish one architecture scanner with a checked-in baseline: DDL allowlist, SQL-layer allowlist, forbidden dependencies, hotspot line counts, and rapid-pilot new-domain-code ratchet.
2. Move every deployed table definition into forward canonical migrations; retain test fixture DDL only in tests/demo-specific harnesses. Make bootstrap call the runner, never migration classes or `ensureSchema` directly.
3. Add explicit commands for environment up/down, DB reset, migrate, unit, DB, characterization, E2E, architecture, and `make verify`; ensure each prints a stable failure category.
4. Adopt OpenSpec incrementally for new migration slices and link existing approved specs rather than rewriting them.
5. Use prepare/register/open as the existing golden foundation; extend one small, understood state-changing calibration slice after opening (inspection scheduling is a likely candidate, subject to product semantics).
6. Prioritize separate application seams for checklist evidence, inspection scheduling, completion evidence, access administration, and premium decision/payment. Keep the rapid-pilot router as presentation/wiring only.

## Audit conclusion

The repository is ahead of a greenfield bootstrap: it already proves one important state-changing journey with authorization, concurrency, artifacts and append-only history. The fastest safe path is not a redesign. It is to make the existing migration/test discipline executable at repository level, ratchet current architecture, then move one rapid-pilot capability at a time behind similarly reviewed public seams. Until runtime DDL and the missing full verification contract are resolved, a successful local demo is not equivalent to a reproducible TEST-USER-READY environment.
