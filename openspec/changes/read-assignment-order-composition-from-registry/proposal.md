## Why

Загрузка оригинала должна читать выбранный без PDF состав. Нынешний reader читает
только physical orders; approved disabled registry/selection schema уже позволяют
доставить совместимый reader без включения writers.

## What Changes

- Срез ASSIGNMENT-ORDER-REGISTERED-COMPOSITION-READER-001 добавляет unwired reader
  существующего `AssignmentOrderCompositionReader::find(caseId,orderId)`.
- Actor — original application после собственной authorization; reader не меняет
  полномочия, original revision, состав, opening или audit.
- Registry определяет единственный источник; legacy temporal/member semantics
  сохраняются, selection читается без temporal filter и проверяется по hash.
- Oracle: selection v0.8 section12, original approved composition contracts,
  schema Gate5ced7b77 и registry Gate5b6f619f. Только synthetic verification.
- Non-goals: production factory wiring, DDL, selection writer, rendering,
  effective applicability, HTTP, readiness/build manifest и canonical cutover.

## Capabilities

### New Capabilities

- `pilot/registered-assignment-order-composition`: read-only registry dispatch
  и fail-closed защита от orphan/dual/malformed source в одном snapshot.

### Modified Capabilities

Нет: существующий physical-only reader остаётся прежним до combined wiring gate.

## Impact

Owner AssignmentOrderOriginal; новый MariaDb-prefixed SQL adapter, public factory
для standalone reader и отдельная verification composition. Interface/snapshot
сохраняются. Registry/selection/legacy facts и architecture baseline не меняются.
Это обязательный launch blocker, не новый продуктовый выбор.
