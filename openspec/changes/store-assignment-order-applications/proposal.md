## Why

Одобренному application/reapplication command нужно собственное immutable хранилище;
существующие selection/original facts не являются фактом применения состава.

## What Changes

- Exact additive storage двух таблиц application facts и command attempt audits.
- Native schema readiness, preflight/preservation и interrupted-prefix recovery.

## Capabilities

### New Capabilities
- `pilot/assignment-order-application-storage`: production-owned application storage.

### Modified Capabilities

## Impact

DDL owner InstallationProcess; application SQL owner AssignmentOrderComposition.
Нет runtime writes/legacy migration/canonical version registration в engine slice.
Canonical registration остаётся обязательной integration task после writer gates.
Executable contract ASSIGNMENT-ORDER-APPLICATION-SCHEMA-001.
