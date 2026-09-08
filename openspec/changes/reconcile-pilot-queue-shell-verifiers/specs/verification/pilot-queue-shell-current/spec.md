## Purpose

Фиксирует representation native queue/shared shell без смешения с manual rapid-pilot adapter behavior.

## ADDED Requirements

### Requirement: Native очередь объектов
Native `public/router.php` `GET/HEAD /pilot/objects` SHALL после exact `objects.read` показывать rich table/current count/status и полный unfiltered set. Любые query keys MUST игнорироваться. До 500 объектов MUST возвращаться целиком; 501 MUST вернуть inherited redacted 503 без partial rows. GET/HEAD MUST не создавать business facts. Manual `rapid-pilot/router.php` SHALL отдельно сохранять approved q/status/page и 50-row pagination; это проверяется существующими rapid-pilot verifier/E2E.

#### Scenario: Допустимый set и query compatibility
- **WHEN** active reader открывает native queue с любыми query keys и set не больше 500
- **THEN** ответ 200 содержит полный table, canonical labels/count/card links и byte-equivalent ignored-query representation

#### Scenario: Overflow
- **WHEN** native fixture содержит 501 объект
- **THEN** ответ 503 и partial list отсутствует

### Requirement: Native shared shell
Configured native root SHALL сохранять body «Моя работа», но navigation MUST не содержать link/root destination «Моя работа». `objects.read` SHALL показывать «Объекты монтажа»; `construction_control.read` — «Стройконтроль»; `installers.read` — «Монтажники»; `access.administer` — «Пользователи» и «Роли». Calendar/ОТиЗ links принадлежат approved rapid decorators. Отсутствующее permission MUST не создавать link. Muted «Распоряжения», «Расчёты ОТиЗ», «Контроль» MUST быть неинтерактивными без href/role/tabindex. Shell MUST сохранять identity/current marker/skip link, exact local scripts/styles/CSP, no remote/preload resources, safe escaping, named controls и no inline execution.

#### Scenario: Configured root и переходы
- **WHEN** пользователь открывает native root, queue и card
- **THEN** root body остаётся compatibility body, navigation не содержит My Work destination, а разрешённые links и current marker следуют permissions

#### Scenario: HEAD и hostile identity
- **WHEN** выполняется HEAD либо identity содержит hostile markup
- **THEN** headers равны GET, HEAD body пуст, identity escaped и dangerous/unnamed controls отсутствуют

### Requirement: Независимость verifier
Verifier SHALL сравнивать literal DOM/header outcomes и MUST не считать собственное чтение файла mutation из-за atime.

#### Scenario: Sentinel read
- **WHEN** test читает foreign sentinel
- **THEN** content/mode/ownership/size/mtime сохраняются, atime исключён

### Requirement: Current native card representation
Native card SHALL проверять identity header отдельными DOM fields: full object id, address/entrance, full registration number and canonical status badge. Planned/actual dates MUST сохранять exact `<time datetime>` и локализованный visible value. Current panels SHALL быть «Сроки работ», «Команда объекта», «Распоряжение», «Проблемы», «Последние события». «Команда объекта» показывает current control-engineer projection отдельно от immutable engineer snapshot распоряжения: отсутствие current assignment отображается как «Не назначен» и не подменяется старым snapshot. Current history показывает до пяти newest-by-append-order events с локализованными labels, exact timestamps и actor names; raw payload не виден. RBAC MUST управлять action cardinality; GET/HEAD no-write guarantees сохраняются.

Standalone native directory composition сохраняет manual-pilot delivery behavior из `067e624d`: trusted `REMOTE_USER` сначала разрешается как active local profile по exact email, затем как active legacy identity, если active local profile отсутствует. Это отдельный read-only directory admission от configured E2E local-ID authority: verifier не должен приписывать standalone route запрет legacy fallback из более позднего `FMONITOR_AUTH_USER_ID` seam. Недоступность нужных directory tables остаётся redacted `503`. Полная унификация authority отложена manual-pilot решением и этим verifier reconciliation не объявляется завершённой.

#### Scenario: Incapable reader
- **WHEN** active reader без mutation capability открывает card
- **THEN** exact facts/panels видимы, mutation controls отсутствуют, scripts/security contracts сохранены

#### Scenario: Capable actor и opened case
- **WHEN** actor имеет approved capability либо дело открыто
- **THEN** card показывает ровно применимые current actions/status/documents/history без predecessor copy или fictional facts
