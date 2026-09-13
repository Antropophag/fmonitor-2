# DELIVERY-PROFILE-NETWORK-110 — profile route to canonical test services

## Простыми словами

Уже существующая тестовая MariaDB остаётся под управлением Make/Compose, но
`integration` и `browser` containers получают возможность обращаться к ней по
Compose DNS. Launcher только добавляет сетевой маршрут и не управляет БД.

## Нормативный контракт

- Идентификатор: `DELIVERY-PROFILE-NETWORK-110`.
- Actor: разработчик или существующий CI job.
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]`.
- Source oracle: issue #110 и подтверждённый владельцем prerequisite plan от
  2026-09-13; OpenSpec `connect-focused-profiles-to-test-services`.
- Preconditions: Docker daemon доступен; внешний caller при необходимости уже
  выполнил canonical `make test-db-reset migrate` либо `make test-env-up`.

### DPN110-01 — canonical network discovery

Для `integration` и `browser` launcher MUST прочитать фактическое имя default
network из результата `docker compose -f compose.test.yaml config --format
json`. Он MUST NOT строить имя как `<project>_default`. Если объявленная network
существует, profile container MUST быть присоединён к ней.

### DPN110-02 — container-side DB route

При присоединении к существующей canonical test network launcher MUST передать
profile container ровно `FMONITOR_TEST_DB_HOST=test-db` и
`FMONITOR_TEST_DB_PORT=3306`. Реальный `mysqli SELECT 1` MUST успешно выполняться
через оба profiles `integration` и `browser`, подтверждая Compose DNS и TCP route.

### DPN110-03 — сохранение lifecycle ownership

Launcher MUST NOT создавать network, запускать MariaDB, выполнять reset,
migrations или teardown. При отсутствующей network/service он MUST NOT пытаться
исправить состояние. DB-backed command может завершиться своей штатной ошибкой
доступности. `governance` MUST не зависеть от test Compose network и не получать
подмену test DB route.

Acceptance fixture MUST гарантировать внешний `make test-env-down` через
`finally`, даже если profile probe или `SELECT 1` падает.

### DPN110-04 — сохранение existing-command semantics

Launcher MUST сохранить точный argv и exit-code contract PR A, не
интерпретировать category и не менять test selection, inventory, sharding,
aggregation или Quality Graph. Launcher является execution primitive для
совместимых focused checks, а не обязательной оболочкой целой category.

```text
tools/delivery/run-in-profile integration php <DB-backed-focused-check>
tools/delivery/run-in-profile browser php <DB-backed-focused-check>
```

Целые `integration`/`e2e` categories содержат checks, которые сами владеют
Docker/runtime/browser contours. Этот срез MUST NOT передавать им Docker
socket/daemon, добавлять Docker CLI/tooling frontier или менять host-based full
Quality Graph. Blanket category adoption и PR B отклонены решением владельца.

## Неприменимые обязанности

Изменение не создаёт product/domain state, authorization decision, audit event,
replay/concurrency policy, UI return path, schema, backup/restore или deployment
behavior. Оно лишь маршрутизирует ephemeral test traffic внутри уже
существующего Docker Compose lifecycle.

## Done

- Behavioral Gate 2 test демонстрирует intended RED на отсутствии DB route.
- Independent Gate 3 `APPROVED` предшествует implementation.
- Меняется только `tools/delivery/run-in-profile`, менее 100 infrastructure LOC.
- Behavioral `mysqli SELECT 1` через `integration` и `browser`, strict OpenSpec
  и boundary checks GREEN на одном exact source; teardown выполнен.
- Independent Gate 5 `APPROVED`; отдельный prerequisite PR создан, а GREEN не
  заявляется до authoritative exact-source Quality Graph CI.
