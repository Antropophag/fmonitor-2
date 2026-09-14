## Context

См. `proposal.md`. Canonical `compose.test.yaml` публикует MariaDB на host
loopback для host commands и одновременно включает service `test-db` в default
Compose network. Обычный `docker run` launcher к этой network не присоединён.

## Goals / Non-Goals

**Goals:**

- переиспользовать уже созданную Compose network и её DNS;
- одинаково работать с Docker Engine в GitHub Actions Linux и Docker
  Desktop/macOS;
- сохранить launcher прозрачным argv adapter с infrastructure delta менее 100 LOC.

**Non-Goals:**

- создание, reset, migration или teardown test DB;
- изменение Compose/Make topology, Quality Graph, planner, selection,
  aggregation, inventories или harness;
- `--network host`, `host.docker.internal`, новый orchestrator или PR B.
- Git metadata mounts, Docker CLI/socket внутри profiles и blanket category
  adoption; эти требования исключены подтверждённым owner decision после
  consumer verification.

## Decisions

1. Owning seam остаётся `tools/delivery/run-in-profile <profile> <command>
   [args...]`; persistence и domain state отсутствуют, rapid-pilot не участвует,
   architecture boundary не меняется.
2. Фактическое имя default network читается из JSON, возвращаемого canonical
   `docker compose -f compose.test.yaml config --format json`. Ручная сборка
   `<project>_default` отвергнута как дублирование Compose naming policy.
3. Только `integration` и `browser`, и только при уже существующей объявленной
   network, получают `docker run --network <declared-name>` и container env
   `FMONITOR_TEST_DB_HOST=test-db`, `FMONITOR_TEST_DB_PORT=3306`.
   `governance` остаётся независимым.
4. Отсутствующая network не создаётся. Launcher продолжает запуск command без
   service route; DB-backed consumer сам выдаёт штатную ошибку доступности.
5. Gate 2 расширяет уже зарегистрированный contract test: внешний test fixture
   поднимает `test-db`, выполняет настоящий `mysqli SELECT 1` через оба profiles
   и гарантирует `make test-env-down` через `finally`. Inventory не меняется.
6. Consumer verification отклонила целые `integration`/`e2e` categories как
   profile boundary: они содержат владельцев собственных Docker/runtime/browser
   contours. Host-based full Quality Graph остаётся неизменным; Docker socket,
   CLI expansion и execution classifier не добавляются.

## Risks / Trade-offs

- [Compose JSON не содержит default network name] → завершить route discovery
  без создания ресурсов; behavioral DB probe остаётся RED/ошибкой, не скрывать её.
- [Параллельные checkout используют один canonical project] → prerequisite не
  меняет существующую ownership/collision semantics; focused verification
  выполняется последовательно.
- [Cleanup потерян при assertion failure] → fixture держит teardown в `finally`
  и отдельно проверяет результат cleanup.

## Migration Plan

1. Gate 2/3 утверждают новый stable spec и behavioral RED.
2. Отдельный executor меняет только launcher и выполняет behavioral DB-backed
   focused probe; full categories не являются acceptance prerequisite.
3. После Gate 5 создать отдельный prerequisite PR и дождаться exact-source GREEN.
4. Rollback возвращает единственный launcher diff; Compose data/topology не
   мигрируются. PR B отменён owner decision; после merge/CI вывод consumer
   verification фиксируется в issue #110, затем отдельно начинается #118.
