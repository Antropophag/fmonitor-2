## Purpose

Обеспечить DB-capable execution profiles сетевым маршрутом к уже подготовленным
canonical test services, не передавая launcher ответственность за их lifecycle.

## ADDED Requirements

### Requirement: Подключение к существующей test-service network
Launcher SHALL для profiles `integration` и `browser` использовать фактическое
имя default network, объявленное результатом canonical `docker compose -f
compose.test.yaml config --format json`, если эта network уже существует.
Launcher MUST NOT конструировать имя network из project name.

#### Scenario: Integration использует подготовленную MariaDB
- **WHEN** внешний lifecycle поднял canonical `test-db`, а через `integration`
  profile выполняется DB-backed command
- **THEN** command подключается к Compose DNS `test-db` на порту `3306` и реальный
  `mysqli SELECT 1` завершается успешно

#### Scenario: Browser использует подготовленную MariaDB
- **WHEN** внешний lifecycle поднял canonical `test-db`, а через `browser`
  profile выполняется DB-backed command
- **THEN** command подключается к Compose DNS `test-db` на порту `3306` и реальный
  `mysqli SELECT 1` завершается успешно

#### Scenario: Governance не зависит от test network
- **WHEN** command выполняется через `governance` profile
- **THEN** launcher не требует существования canonical test network и не
  подменяет test DB route

### Requirement: Lifecycle остаётся у существующих Make/Compose targets
Launcher SHALL только подключать profile container к уже существующей network и
передавать ему `FMONITOR_TEST_DB_HOST=test-db` и
`FMONITOR_TEST_DB_PORT=3306`. Launcher MUST NOT создавать network, запускать
service, выполнять reset/migrations или teardown.

#### Scenario: Test service не подготовлен
- **WHEN** canonical test network или `test-db` не подготовлены внешним lifecycle
- **THEN** launcher не пытается исправить состояние созданием или запуском
  ресурсов, а DB-backed command завершается ошибкой доступности

#### Scenario: Cleanup принадлежит внешнему acceptance lifecycle
- **WHEN** profile probe успешно или неуспешно завершился
- **THEN** внешний test owner выполняет `make test-env-down`, включая failure path

### Requirement: Сохранение произвольной команды
Сетевой prerequisite SHALL сохранять argv и exit-code semantics существующего
`run-in-profile`; он MUST NOT интерпретировать category, менять selection или
становиться владельцем test-service lifecycle. Launcher является execution
primitive только для совместимых focused checks и SHALL NOT быть обязательной
оболочкой целой Quality Graph category.

#### Scenario: Existing consumer command
- **WHEN** совместимая существующая focused command передана `integration` или `browser`
- **THEN** launcher выполняет ту же argv внутри выбранного profile с одним
  добавленным маршрутом к уже подготовленному `test-db`

#### Scenario: Blanket category adoption отклонён
- **WHEN** category содержит checks, владеющие Docker/runtime/browser contours
- **THEN** этот prerequisite не передаёт profile Docker daemon/socket или новый
  tooling frontier и не меняет host-based full Quality Graph
