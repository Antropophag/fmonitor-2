## Why

Issue #110 требует запускать существующие DB-backed проверки внутри pinned
profiles, но `run-in-profile` сейчас изолирован от test MariaDB, поднятой
каноническими Make/Compose targets. Без узкого сетевого prerequisite последующий
PR B сломает категории `integration` и `e2e` вместо смены только окружения.

## What Changes

- `integration` и `browser` profiles получают маршрут к уже существующей default
  network из canonical `compose.test.yaml` и используют `test-db:3306`.
- Фактическое имя сети читается из `docker compose -f compose.test.yaml config
  --format json`, а не конструируется из project name.
- Launcher не создаёт network/service, не запускает MariaDB, не выполняет
  reset/migrations/teardown и не меняет selection либо lifecycle проверок.
- Behavioral acceptance выполняет реальный `mysqli SELECT 1` через оба profiles
  при внешне подготовленном `test-db` и гарантирует внешний teardown.

## Capabilities

### New Capabilities

- `delivery/profile-test-service-networking`: подключение DB-capable execution
  profiles к уже подготовленной canonical test-service network без передачи им
  lifecycle ownership.

### Modified Capabilities

Нет.

## Impact

Меняется только публичный launcher `tools/delivery/run-in-profile`, существующий
зарегистрированный behavioral contract test и delivery artifacts. Compose,
Makefile, Quality Graph, planner, selection, aggregation, verification inventory,
`harness.py`, product/domain behavior и PR B не меняются.
