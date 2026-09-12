## Purpose

Определяет безопасное переключение production HTTP front controller на единый
Yii2 runtime при сохранении публичных маршрутов, данных, прав и истории.

## ADDED Requirements

### Requirement: Production HTTP использует единую application composition
Система SHALL обслуживать `public/runtime.php` через Yii2 для `/health/*`, `/`,
всех действующих `/pilot/*` routes и assets. Production request MUST NOT загружать
`rapid-pilot/router.php`, legacy LocalAuth или legacy session composition.

#### Scenario: Действующий пользовательский маршрут
- **WHEN** авторизованный пользователь вызывает любой route из принятого production route inventory через `public/runtime.php`
- **THEN** ответ совпадает с утверждённым HTTP contract этого route и request выполняется без загрузки rapid-pilot runtime

#### Scenario: Атрибутируемый полный inventory
- **WHEN** Gate 3 выполняет каждый явный method/path/asset case
- **THEN** каждый case имеет независимо заданный accepted outcome и ровно одну include-frontier запись с case id, method и path

#### Scenario: Корневой маршрут
- **WHEN** клиент вызывает `GET /`
- **THEN** система возвращает безопасный redirect на `/pilot/objects` через тот же Yii2 runtime

#### Scenario: Неизвестный маршрут и неподдерживаемый метод
- **WHEN** клиент вызывает неизвестный URL либо неподдерживаемый методом действующий URL
- **THEN** система возвращает безопасный `404` либо `405` с прежним `Allow`, без session/domain/audit facts

### Requirement: Operational и security contract сохраняется при cutover
Production front controller MUST сохранить trusted-host validation, generic
startup/readiness errors, `Cache-Control: no-store` для динамических и error
responses, CSP/security headers, HEAD без body и отсутствие disclosure secrets,
paths, native exceptions или stack traces.

#### Scenario: Недоверенный Host
- **WHEN** запрос к production front controller содержит Host, отличный от configured trusted host
- **THEN** система отклоняет его до authentication/session/domain access и не раскрывает configuration

#### Scenario: Startup или storage failure
- **WHEN** Yii bootstrap, configuration, database либо session storage недоступны
- **THEN** система возвращает принятый generic unavailable response и не публикует частичный success

#### Scenario: Host проверяется до зависимостей
- **WHEN** Host недоверен, а database, session и artifact dependencies намеренно недоступны
- **THEN** система всё равно возвращает safe `400` без обращения к этим зависимостям

#### Scenario: Health probes
- **WHEN** client вызывает live или ready probe через production front controller
- **THEN** live остаётся независимым от БД, ready использует read-only readiness owner, и probes не создают session/cookie/write

### Requirement: Cutover сохраняет данные и rollback boundary
Cutover MUST NOT изменять schema, cookie payload policy, application command
owners или append-only facts. Повторные и конкурентные read requests MUST быть
идемпотентны; state-changing requests SHALL сохранять существующую транзакционную
и authorization семантику. Rollback SHALL быть возможен возвратом предыдущего
image/entrypoint без преобразования данных.

#### Scenario: Соседние golden flows
- **WHEN** выполняются login, FKR/card/document/inspection, user administration и OTIZ golden flows через production front controller
- **THEN** их публичные результаты, authorization, audit и append-only history совпадают с ранее принятыми Yii2 contracts

#### Scenario: Репетиция rollback границы среза
- **WHEN** image с cutover заменяется предыдущим image без schema changes этого среза
- **THEN** существующие БД, PDF/фото, users и jobs не требуют преобразования или удаления
