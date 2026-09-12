## Purpose

Определяет наблюдаемый fail-closed контракт, при котором delivery harness до публикации exact commit обнаруживает известные расхождения между локальным verification plan и первой CI-средой.

## ADDED Requirements

### Requirement: Verification plan замыкает известные repository contracts
Для изменённых generated artifacts, inventory, entrypoints, shared bootstrap/error envelope, Dockerfile/Compose и verification catalogs planner SHALL транзитивно включать известные generator checks и всех зарегистрированных consumers. Неизвестная либо неоднозначная связь MUST давать `UNKNOWN` и блокировать publication package, а не считаться покрытой generic architecture check.

#### Scenario: Изменён только generated Dockerfile
- **WHEN** candidate меняет generated Dockerfile, но соответствующий template/pins остаётся прежним
- **THEN** plan включает generator `--check`, а preflight завершается ненулево из-за drift до публикации

#### Scenario: Расширен E2E inventory
- **WHEN** candidate добавляет member в E2E inventory
- **THEN** plan включает verification-composition consumer и отклоняет stale expected composition

#### Scenario: Неизвестный adjacent consumer
- **WHEN** изменён contract surface, для которого registry не позволяет однозначно определить consumers
- **THEN** planner возвращает `UNKNOWN`, сохраняет причину и не выпускает publication-ready package

### Requirement: Test dependencies соответствуют CI category environment
Каждая новая или изменённая test command SHALL иметь machine-readable runtime prerequisites и выбранную suite/category. Preflight MUST исполнять или валидировать команду в среде, не более богатой, чем соответствующий CI job, и MUST отклонять undeclared imports или доступ к отсутствующему service до Gate 5.

#### Scenario: Undeclared Python import
- **WHEN** новый Python test импортирует пакет, отсутствующий в declared CI dependencies
- **THEN** dependency preflight завершается ненулево до push и называет import и целевую CI category

#### Scenario: Repository-local sibling import
- **WHEN** Python test импортирует существующий module из своего repository test directory
- **THEN** dependency preflight принимает module как source dependency без объявления его third-party package

#### Scenario: Node.js built-in import
- **WHEN** JavaScript test импортирует runtime module через canonical `node:` specifier
- **THEN** dependency preflight принимает built-in без объявления его third-party package

#### Scenario: Unit test требует MariaDB
- **WHEN** test обращается к MariaDB до проверяемого assertion, но зарегистрирован в unit category без DB service
- **THEN** catalog/preflight отклоняет category mismatch даже если developer environment предоставляет MariaDB

#### Scenario: Dependency environment совпадает
- **WHEN** imports, services и generated dependency trees объявлены и доступны в целевой CI category
- **THEN** evidence фиксирует их фактическую доступность и может подтверждать эту category

### Requirement: Reviewer evidence сохраняет назначение каждого record
Reviewer package SHALL принимать все обязательные records generated plan и различать `acceptance`, `boundary` и `category` evidence. Plan-owned additional evidence MUST NOT отклоняться только потому, что оно не сопоставлено отдельной acceptance; произвольное unrelated evidence MUST оставаться отклонённым.

#### Scenario: Дополнительная обязательная category check
- **WHEN** record соответствует required command generated plan, но не отдельной acceptance
- **THEN** reviewer package принимает record с типом `category` и сохраняет его provenance

#### Scenario: Произвольная дополнительная команда
- **WHEN** evidence не соответствует ни acceptance mapping, ни required plan command
- **THEN** package отклоняется fail-closed с точной причиной

### Requirement: Post-implementation test delta допускает независимый Gate 3
Gate 3 package SHALL представлять исправление теста после реализации как current GREEN, linked historical `INTENDED_RED`, exact test delta и неизменённую acceptance mapping. Package MUST NOT требовать фиктивного RED на current production bytes и MUST отклонять отсутствующий или несопоставимый historical RED.

#### Scenario: Коррекция чувствительности теста после реализации
- **WHEN** test delta исправляет проверку без изменения acceptance и current candidate GREEN
- **THEN** package связывает exact delta с historical intended RED и готов к новому независимому Gate 3 review

#### Scenario: Historical RED относится к другой acceptance
- **WHEN** linked RED не подтверждает изменённый test/acceptance
- **THEN** package отклоняется до reviewer

### Requirement: Executable source identity отделена от lifecycle metadata
Harness SHALL фиксировать полный candidate source и отдельный executable digest, включающий production, tests, verification code и исполняемую конфигурацию, но исключающий только явно классифицированные review/lifecycle metadata. Evidence MAY пережить добавление append-only verdict или task checkbox только когда executable digest неизменен; любое executable изменение MUST инвалидировать его.

#### Scenario: Добавлен review verdict
- **WHEN** к неизменённому executable candidate добавлен append-only review record
- **THEN** полный source меняется, executable digest остаётся прежним, а package явно показывает оба значения и повторно использует только совместимое evidence

#### Scenario: Изменён test или verification catalog
- **WHEN** меняется test, generator, dependency declaration либо verification catalog
- **THEN** executable digest меняется и прежний GREEN record не подтверждает новый candidate

### Requirement: Dependency workspace объявлен и не входит в deliverable
Package SHALL описывать ignored/generated dependency workspace, его provenance, immutable identity и доступность для каждой команды. Workspace MUST быть ограничен разрешёнными roots и MUST NOT включаться в source digest или deliverable; отсутствующая, изменённая либо неразрешённая dependency MUST давать fail-closed outcome.

#### Scenario: Разрешённый vendor workspace
- **WHEN** Yii command использует ignored `vendor` с объявленным lock/source identity
- **THEN** package допускает workspace, evidence фиксирует его identity, а deliverable source не включает его файлы

#### Scenario: Произвольный dependency symlink
- **WHEN** command зависит от symlink вне разрешённого workspace contract
- **THEN** prepare/preflight возвращает ненулевой результат до исполнения команды

### Requirement: Publication требует bounded CI-parity preflight
Перед push или созданием PR harness SHALL сформировать exact-commit preflight из generated plan и успешно проверить generator drift, inventory composition, declared test dependencies и category environments. `UNKNOWN`, missing evidence и failure MUST блокировать publication package; preflight не заменяет exact-source GitHub CI и не запускает локально полный product matrix.

#### Scenario: PR #98 bad candidate
- **WHEN** deterministic fixture воспроизводит исходный Dockerfile drift, stale consumers и undeclared Python dependency PR #98
- **THEN** corrected harness блокирует первый candidate и перечисляет все обнаруженные primary obligations до публикации

#### Scenario: PR #100 category mismatch
- **WHEN** fixture воспроизводит DB-dependent test в unit environment из PR #100
- **THEN** preflight отклоняет candidate как environment mismatch до GitHub CI

#### Scenario: Полный bounded preflight GREEN
- **WHEN** все plan-owned bounded commands прошли на одном executable digest в соответствующих CI environments
- **THEN** publication package становится готовым, сохраняя GitHub PR/CI как `UNKNOWN` до их фактического появления
