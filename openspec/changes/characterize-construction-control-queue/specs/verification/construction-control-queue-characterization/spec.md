## Purpose

Зафиксировать воспроизводимый `PILOT_ONLY` oracle текущей read-only очереди стройконтроля, не утверждая наблюдаемые дефекты и временные projection semantics как целевое поведение продукта.

## ADDED Requirements

### Requirement: Публичная и изолированная characterization boundary

Characterization SHALL выполнять реальные `GET` и `HEAD` запросы к `/pilot/construction-control` через production HTTP composition для синтетических активных локальных пользователей и private prefixed MariaDB fixtures. Она MUST не читать production/primary evidence, secrets или соседний legacy repository и MUST доказать bounded cleanup с сохранением ambient decoy.

#### Scenario: Успешный bounded запуск
- **WHEN** verifier запускается с доступной test MariaDB, валидными локальными assets и уникальными owned prefixes
- **THEN** он наблюдает публичный HTTP ответ, удаляет только owned DB/files/process resources и оставляет заранее созданный ambient decoy неизменным

#### Scenario: Невозможна достоверная настройка
- **WHEN** обязательная test dependency, fixture preflight или cleanup ownership не доказаны
- **THEN** verifier MUST завершиться как `SETUP_FAILURE`, а не выдать успешный characterization verdict

### Requirement: Текущая authorization boundary является только oracle

Characterization SHALL наблюдать текущий admission активного пользователя с `construction_control.read`, denial активного пользователя без этого permission и inherited unauthenticated/inactive outcomes. Эти результаты MUST маркироваться `PILOT_ONLY` и MUST NOT утверждать target visibility, assignment policy или новую capability.

#### Scenario: Допущенный синтетический пользователь
- **WHEN** активный synthetic user имеет текущий permission `construction_control.read` и запрашивает очередь
- **THEN** `GET` SHALL вернуть успешное HTML-представление, а `HEAD` SHALL вернуть тот же status и существенные headers без body

#### Scenario: Permission отсутствует
- **WHEN** активный synthetic user без `construction_control.read` запрашивает очередь
- **THEN** запрос SHALL быть отклонён до чтения queue projection и не SHALL изменить security, process, checklist, session или filesystem facts

### Requirement: Наблюдаемая server projection фиксируется без продуктового одобрения

Characterization SHALL на буквальных synthetic fixtures наблюдать, что server projection выбирает только дела в текущем состоянии `working`, сортирует строки без checklist activity перед строками с activity, затем по текущему activity timestamp и object id, и применяет server pagination размером 50 до browser filtering. Она MUST фиксировать это как migration contrast, а не как target ordering, page size или work-ownership rule.

#### Scenario: Смешанная первая страница
- **WHEN** fixtures содержат non-working cases, working cases без activity и working cases с различными checklist operation device times
- **THEN** HTML SHALL исключать non-working cases и отражать текущий server order и pagination metadata для working cases

#### Scenario: Страница вне текущего диапазона
- **WHEN** допущенный пользователь запрашивает нецелую, неположительную или превышающую вычисленный диапазон страницу
- **THEN** public seam SHALL вернуть текущий infrastructure-failure outcome с zero product/session/file mutation

### Requirement: Engineer, activity и completion projections наблюдаемы как hazards

Characterization SHALL различать current engineer snapshot из последнего `control_engineer_changed` event и legacy fallback, отсутствие инженера, максимальный `device_time` любой checklist operation и legacy PTO-presence display flag. Transcript MUST называть эти поля `PILOT_ONLY` projections и MUST NOT использовать слова, подразумевающие принятое доказательство состоявшейся инспекции, завершения работ или ownership очереди.

#### Scenario: Event snapshot имеет precedence
- **WHEN** working case содержит валидный более новый engineer event и отличающийся legacy engineer fallback
- **THEN** rendered row SHALL показать event snapshot, canonical checklist href и не SHALL переписать event или legacy row

#### Scenario: Только legacy fallback
- **WHEN** working case не содержит engineer-change event, но содержит разрешимый legacy engineer id
- **THEN** rendered row SHALL показать текущий fallback snapshot без создания нового assignment/event fact

#### Scenario: Activity и PTO являются presentation signals
- **WHEN** case содержит checklist operations разных типов/device times и ненулевую legacy PTO date
- **THEN** row SHALL отразить текущий maximum-device-time label и PTO-derived completed marker только как наблюдаемую presentation projection

### Requirement: HTML safety и read-only history

Characterization SHALL использовать специально размеченные UTF-8/HTML-sensitive synthetic values и доказать escaping visible text и canonical positive-id checklist href. Полный DB fingerprint, owned file tree и session namespace до/после каждого read SHALL быть равны; repeated и параллельные reads MUST не создавать audit/history/idempotency facts. Отдельный privileged fixture connection MUST сохранить setup capability, а четыре exact runtime principals для serial slot, concurrent slots A/B и sensitivity slot MUST иметь одинаковые read-only grants. Каждый slot MUST держать не более одного active connection. Test-owned observer SHALL независимо связать каждый request/worker slot с единственным exact runtime MariaDB thread и SHALL проверить уже включённую `performance_schema` statement history на любую DML/DDL attempt, включая denied, rolled-back или восстановленную до ответа.

#### Scenario: Escaped row и canonical link
- **WHEN** валидная working row содержит HTML-sensitive address, registration number и engineer name
- **THEN** representation SHALL показывать escaped text, SHALL содержать только canonical `/pilot/construction-control/objects/{positive-id}/checklist` href и MUST NOT выполнять injected markup

#### Scenario: Повторные и параллельные reads
- **WHEN** два independently authenticated workers читают один immutable fixture snapshot
- **THEN** оба SHALL наблюдать эквивалентную normalized projection, а DB/files/sessions SHALL остаться неизменными

#### Scenario: Runtime пытается временно изменить persistence
- **WHEN** sensitivity double на отдельном guarded sensitivity principal с тем же exact grant profile выполняет DML attempt и затем пытается rollback либо restore
- **THEN** least-privilege guard SHALL не допустить persisted write, independent statement audit SHALL наблюдать attempt, и meta-test SHALL завершиться assertion failure, а не успешным verdict

#### Scenario: Statement audit недоступен
- **WHEN** `performance_schema` statement instrumentation/history/consumer не включены заранее, любой slot имеет missing/extra active connection, thread нельзя однозначно связать с exact principal/request или history могла быть вытеснена до чтения
- **THEN** verifier SHALL завершиться `SETUP_FAILURE` без изменения global server configuration

### Requirement: Browser behavior и target semantics исключены

Characterization MUST NOT исполнять или утверждать `mine/all`, completed/search filters, sessionStorage, IndexedDB, service worker, prefetch или offline synchronization. Она MUST сохранить как `NEEDS_GRILL` target visibility by assignment, meaning of «last inspection»/«completed», target ordering/pagination and target public read-model seam.

#### Scenario: Oracle не становится target contract
- **WHEN** characterization artifacts используются будущим migration или redesign slice
- **THEN** они SHALL цитироваться только как `PILOT_ONLY` compatibility contrast, а любое target requirement MUST получить отдельный Gate 1 owner approval

### Requirement: Обязательные delivery gates

Exact executable characterization spec MUST получить fresh independent review и явное owner approval exact hash до RED. После этого RED author, independent test reviewer, minimal GREEN implementer и fresh independent code reviewer SHALL быть отдельно назначенными ролями; ни один reviewer MUST NOT утверждать собственную работу.

#### Scenario: Gate 1 ещё не одобрен
- **WHEN** planning package готов, но append-only owner decision для exact reviewed hash отсутствует
- **THEN** tests, verifier implementation, production code и canonical characterization registration MUST оставаться неизменными
