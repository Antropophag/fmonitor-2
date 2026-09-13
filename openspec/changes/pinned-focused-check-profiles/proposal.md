## Why

Issue #110 требует воспроизводимых focused checks, но PR #117 связал простой
container route с отдельной моделью provenance, snapshot lifecycle и тяжёлой
self-test матрицей. Поручение владельца от 2026-09-13 сужает scope: локальный
запуск и CI должны выполнять существующие команды в одинаковых pinned container
environments.

## What Changes

- PR A добавляет ровно три execution profile: `governance`, `integration`,
  `browser`, и launcher с семантикой `run-in-profile <profile> <command>`.
- PR A доказывает совпадение runtime/dependency versions локально и в CI, не
  меняя selection или aggregation существующих тестов.
- После merge PR A отдельный PR B переводит текущие Quality Graph jobs `unit`,
  `integration`, `e2e`, `governance` на launcher, сохраняя planner, selection и
  aggregation. `setup-runtime` остаётся до подтверждённого GREEN.
- Evidence одного запуска ограничено git SHA, командой, profile/image digest,
  exit code и duration.
- Из scope исключены архитектура PR #117, lifecycle hashing, event/provenance
  stores, fixture/environment identity, I2 self-test matrix и новый CI/runtime
  framework в `harness.py`.

## Capabilities

### New Capabilities

- `delivery/pinned-focused-check-profiles`: выполнение произвольной существующей
  команды проверки в одном из трёх закреплённых container profiles.

### Modified Capabilities

Нет.

## Impact

Затрагиваются только delivery container recipe/pins, минимальный launcher,
focused verification и, в PR B, существующие команды Quality Graph. Product и
domain behavior, test inventory, selection/aggregation policy, deployment и
repository settings не меняются.
