## Purpose

Определяет отдельный operational contour нового application runtime, чтобы оператор мог проверить web и console lifecycle до переноса пользовательских маршрутов и без переключения рабочего стенда.

## Requirements

### Requirement: Web health имеет единый безопасный контракт
Изолированный runtime SHALL предоставлять `GET` и `HEAD` seams `/health/live` и
`/health/ready`. Успешный `GET /health/live` MUST возвращать status `200`, JSON
`{"ok":true}` и `Cache-Control: no-store`; `HEAD` MUST сохранять status и headers
при пустом body. Liveness SHALL NOT зависеть от database, private storage или
полноты application configuration.

`GET /health/ready` SHALL использовать существующую read-only runtime readiness
operation. При отсутствующей, недопустимой или недоступной configuration, storage,
database либо schema он MUST вернуть status `503`, JSON
`{"ok":false,"reason":"SERVICE_UNAVAILABLE"}` и `Cache-Control: no-store` без
исходного exception, credential, configuration value или private path.

#### Scenario: Живой process без готовой инфраструктуры
- **WHEN** оператор вызывает `GET /health/live` в изолированном runtime без database и runtime configuration
- **THEN** response имеет status `200`, JSON `{"ok":true}` и `Cache-Control: no-store`
- **AND** response не создаёт session, cookie, private path, database object, domain fact или audit fact

#### Scenario: HEAD liveness
- **WHEN** оператор вызывает `HEAD /health/live`
- **THEN** response сохраняет status `200`, JSON content type и `Cache-Control: no-store`, но имеет пустой body

#### Scenario: Readiness недоступна
- **WHEN** оператор вызывает `GET` либо `HEAD /health/ready` с отсутствующей, недопустимой или недоступной runtime configuration
- **THEN** response имеет status `503`, safe JSON reason `SERVICE_UNAVAILABLE` и `Cache-Control: no-store`
- **AND** body и headers не раскрывают credential, configuration value, private path, exception или stack trace
- **AND** проверка не выполняет prepare, migration, DDL, session start, domain write или audit write

#### Scenario: Readiness готова
- **WHEN** operator contour имеет valid configuration, заранее подготовленные private paths, доступную database и exact compatible canonical schema
- **THEN** `GET /health/ready` возвращает status `200`, JSON `{"ok":true}` и `Cache-Control: no-store`
- **AND** доказательство выполняется через inherited read-only runtime readiness owner без изменения проверяемого состояния

### Requirement: Routing отклоняет неподдерживаемые requests без состояния
Неизвестный web route SHALL возвращать `404`. Метод кроме `GET` и `HEAD` для
обоих health routes SHALL возвращать `405` и объявлять разрешённые методы.
Error responses SHALL быть безопасными, non-cacheable и MUST NOT создавать
session/cookie, выполнять prepare/DDL или записывать domain/audit facts.

#### Scenario: Неизвестный route
- **WHEN** client вызывает неизвестный route изолированного runtime
- **THEN** response имеет status `404` и `Cache-Control: no-store`
- **AND** response не создаёт cookie, session, private path или persisted fact

#### Scenario: Неподдерживаемый health method
- **WHEN** client вызывает `POST /health/live` либо `POST /health/ready`
- **THEN** response имеет status `405`, объявляет `GET, HEAD` и содержит безопасное non-cacheable представление
- **AND** request не обращается к readiness infrastructure и не создаёт persisted state

### Requirement: Console health совпадает с web semantics
Изолированный runtime SHALL предоставлять console actions `health/live` и
`health/ready` через общий application configuration и один dependency lock с web.
`health/live` MUST вывести ровно один JSON object `{"ok":true}` и завершиться с
code `0`. `health/ready` MUST использовать inherited read-only runtime readiness;
его infrastructure/configuration failure SHALL вывести ровно один safe JSON object
с reason `SERVICE_UNAVAILABLE` и завершиться nonzero без secret/path/exception.

#### Scenario: Console liveness
- **WHEN** оператор запускает console action `health/live` без database configuration
- **THEN** stdout содержит `{"ok":true}`, stderr пуст и exit code равен `0`
- **AND** action не создаёт private path, session, database object или persisted fact

#### Scenario: Console readiness недоступна
- **WHEN** оператор запускает `health/ready` с отсутствующей, недопустимой или недоступной configuration
- **THEN** stdout содержит один JSON object с `ok=false` и reason `SERVICE_UNAVAILABLE`, stderr пуст и exit code nonzero
- **AND** output не раскрывает credential, configuration value, private path, exception или stack trace
- **AND** action не выполняет prepare, migration, DDL или domain/audit write

### Requirement: Foundation остаётся изолированной
Web и console SHALL использовать одну pinned dependency graph и общую runtime
configuration composition. Operational foundation MUST работать в отдельном
contour на PHP 8.4 с nginx/PHP-FPM и SHALL NOT переключать существующий production
entrypoint, переносить auth/session/user routes, обслуживать working data или
становиться владельцем domain facts. Появление health GREEN SHALL NOT означать
готовность stage 2 пользовательских routes, HTML, assets или auth parity.

#### Scenario: Один dependency и configuration source
- **WHEN** собираются web и console entrypoints одного exact source
- **THEN** оба используют один repository Composer lock и одну общую configuration composition
- **AND** dependency graph содержит exact выбранную Yii 2.0 release в approved range `>=2.0.50,<2.1`

#### Scenario: Изолированный запуск
- **WHEN** оператор запускает foundation contour
- **THEN** он использует отдельный PHP 8.4 FPM/nginx runtime без переключения текущего production entrypoint или изменения его volumes
- **AND** отсутствующие пользовательские routes, HTML/assets и auth остаются явно незавершённым продолжением #76

