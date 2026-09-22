## Context

`public/runtime.php` performs storage and DB availability checks before Yii;
`HealthController` then calls `RuntimeReadiness`, whose MariaDB implementation
walks all schema fingerprints. Both PHP and nginx Compose healthchecks hit this
route every five seconds. `make up` already invokes canonical migrations and the
full `bin/fmonitor2-runtime-check.php`, but Compose does not retain its outcome.

## Goals / Non-Goals

Goal: bounded fail-closed steady readiness, mandatory full check at deployment,
exact DB/schema/build binding and unchanged public health contract. Non-goals:
generic attestation/release framework, migration fingerprint rewrites, interval
tuning, external dependency health or ordinary query optimization.

## Decisions

Add one singleton readiness marker through `ProductionPilotMigrationCatalogue`.
The existing deep CLI reads the current marker only after all existing schema
checks succeed and atomically publishes a private attestation. A deterministic
build identity is baked into the application image; focused native tests may
provide an explicit test identity. Regular readiness reconnects, runs `SELECT 1`,
reads only the singleton marker and compares the private attestation.

Compose gains a one-shot startup-check after migrate and before php. It does not
call HTTP, so no readiness cycle exists. Web still waits for healthy php. The
HTTP bootstrap availability check remains current and cannot be hidden by cache.

## Risks / Trade-offs

The attestation proves compatibility at startup plus continued identity/current
connectivity; it does not continuously detect arbitrary post-start DDL corruption.
Such drift is outside normal ownership and is caught by marker loss/change,
restart/update deep check, domain guards and exact startup deployment. The marker
is schema state, not business history.

## Verification impact

Runtime/deployment/schema tests and current-schema inventories are applicable.
Backup/restore must include the marker through existing prefixed-table inventory.
Authorization/audit and business replay are inapplicable because health remains
read-only and no domain fact changes. Focused UI smoke protects adjacent routes.
