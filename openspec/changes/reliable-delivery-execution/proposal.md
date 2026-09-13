## Why

Delivery harness должен fail-closed связывать admission и CI с точным кандидатом.

## What Changes

Поставляется только I1: единый evaluator, честные verdict/applicability и scoped
reporting. Docker/I2, snapshot/I3 и resume/I4 исключены.

## Capabilities

### New Capabilities

- `delivery/reliable-admission`: достоверное состояние и допуск кандидата.

## Impact

Изменяются harness, CI evaluator, bounded verification inventory и I1 tests.
