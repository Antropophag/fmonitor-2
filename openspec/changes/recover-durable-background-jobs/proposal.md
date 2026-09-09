## Why

После завершения #34 production contour содержит durable queue, leases, scheduler
heartbeats и outbox, а утверждённый #36 restore contract намеренно поддерживает
только canonical v22 и помечает jobs/outbox как deferred. Нужен отдельный
технический срез, который восстанавливает exact v23 state без скрытого retry,
потери append-only history или ложного обещания exactly-once внешней доставки.

## What Changes

- Добавить отдельный v23 recovery contract с exact 69-table schema inventory и 39
  AUTO_INCREMENT families, сохранив неизменным исторический v22 contract.
- Расширить операторскую attestation до ordered quiesce: scheduler → worker →
  web/php, включая graceful-finish и unknown-effect lease варианты.
- Восстанавливать ready/leased/completed/dead jobs, events, slots, heartbeats и
  pending/delivered/dead outbox rows byte/value-exact без queue mutation, sweep или
  transport внутри restore.
- После явного запуска jobs services проверить fake-only resume: sweep committed
  pending intent, lease expiry/reclaim, stale token, attempt5 dead/no6, stable
  outbox provider identity и authorized linked retry.
- Доказать forward path v22 restore → migration23 → v23 backup/restore и
  fail-closed отказ v22 tooling от v23 bundle без downgrade.
- Для v23 bundle установить technical `deferred: []`; retention/RPO/RTO остаются
  прежними NEEDS_GRILL и не получают новых значений.

## Capabilities

### New Capabilities

- `operations/durable-jobs-recovery`: согласованный backup/restore и fake-only
  recovery durable jobs/outbox state после canonical v23.

### Modified Capabilities

Нет.

## Impact

Новый нормативный `PRODUCTION-JOBS-RECOVERY-001`, отдельный OpenSpec delta,
deployment recovery schema v23, public recovery executable tests и production
runbook. Существующие v22 recovery files/contracts не заменяются. Product
notification triggers/templates, real Bitrix/email sends, broker, retention/RPO/RTO
policy и in-place/downgrade restore не входят в срез.
