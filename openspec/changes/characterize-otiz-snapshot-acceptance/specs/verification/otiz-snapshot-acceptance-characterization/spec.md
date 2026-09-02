## Purpose

Определяет воспроизводимый `PILOT_ONLY` oracle публичного принятия OTIZ
snapshot, чтобы будущий перенос финансового перехода обнаруживал drift, не
превращая случайности pilot в целевые продуктовые правила.

## ADDED Requirements

### Requirement: Oracle проходит настоящий публичный admission path

Focused characterization SHALL отправлять запросы через LocalAuth и router,
наблюдать текущую broad `otiz.manage` границу и отделять admission failure от
business acceptance. Oracle MUST маркировать эту границу как `PILOT_ONLY`, а не
как целевое полномочие будущего application seam.

#### Scenario: Неаутентифицированная сессия

- **WHEN** запрос принятия отправлен без действующей LocalAuth session
- **THEN** публичный path возвращает auth redirect до OTIZ constructor effects
- **AND** ни один business fact принятия не изменён

#### Scenario: Активный actor без broad OTIZ permission

- **WHEN** действующая сессия принадлежит активному actor без `otiz.manage`
- **THEN** публичный path возвращает текущий plain `403` раздела до OTIZ
  constructor DDL
- **AND** ни один business fact принятия не изменён

#### Scenario: Авторизованный actor с неверным CSRF

- **WHEN** действующая `otiz.manage` сессия отправляет неверный form CSRF
- **THEN** публичный path сначала выполняет текущие constructor schema checks,
  затем возвращает текущий plain `403` CSRF
- **AND** snapshot и acceptance events не изменены

### Requirement: Oracle фиксирует constructor-time schema ownership

Focused characterization SHALL доказать, что в namespace с заранее
подготовленными LocalAuth/RBAC prerequisites, но без OTIZ-owned schema,
авторизованный запрос до business validation создаёт семь OTIZ tables, migrated
decision table, три migrated projection/state tables и quarantine decision
table, включая проверку/ремонт `unique_reversal`. Oracle MUST считать это
architecture debt и MUST NOT утверждать runtime DDL как target behavior.

#### Scenario: Bad-CSRF schema probe

- **WHEN** авторизованный bad-CSRF запрос выполняется без двенадцати
  constructor-owned tables
- **THEN** после ответа присутствуют ровно ожидаемые двенадцать tables и
  `unique_reversal`
- **AND** business acceptance state отсутствует

#### Scenario: Bad-CSRF repair существующего payment-closures table

- **WHEN** авторизованный bad-CSRF запрос выполняется в отдельном namespace, где
  все двенадцать exact constructor-owned tables существуют, но у
  payment-closures table отсутствует только `unique_reversal`
- **THEN** constructor добавляет exact unique index `unique_reversal` до CSRF
  rejection
- **AND** ни одна table или business row не создаётся, не удаляется и не
  изменяется иначе

#### Scenario: Unauthorized request cannot bootstrap OTIZ

- **WHEN** активный actor без `otiz.manage` обращается до создания
  constructor-owned schema
- **THEN** ни одна из двенадцати tables и индекс не создаются этим запросом

### Requirement: Blocker-free draft принимается как единичный pilot fact

Для литерально подготовленного существующего `draft` без open blocker focused
characterization SHALL наблюдать успешный `303` с `Cache-Control: no-store`,
изменение только `status`, `accepted_at`, `accepted_by_user_id` в snapshot и
ровно один `snapshot_accepted` event. Expected actor, hash и неизменяемые fixture
значения MUST происходить из утверждённой executable specification, а не из
кода pilot.

#### Scenario: Успешное принятие литерального draft

- **WHEN** действующий `otiz.manage` actor с правильным CSRF принимает
  существующий draft без open blocker
- **THEN** ответ перенаправляет на тот же snapshot с `accepted=1`
- **AND** snapshot получает `accepted`, живые Moscow `accepted_at` и actor id
- **AND** ровно один event хранит snapshot id, `object_id=NULL`, actor,
  `snapshot_accepted` и payload с исходным `content_hash`

#### Scenario: Acceptance не переписывает содержимое

- **WHEN** успешный acceptance сравнивается с полным pre-request fixture
- **THEN** все остальные snapshot columns и все seeded object, allocation,
  issue и evidence rows остаются byte-for-byte равны
- **AND** oracle не заявляет защиту этих rows от других writers

### Requirement: Rejected states сохраняют business facts

Focused characterization SHALL различать missing snapshot, уже accepted
snapshot и open blocker по текущим публичным ответам. Каждый rejected case MUST
доказывать отсутствие изменения snapshot, дочернего содержимого и acceptance
events после допустимых constructor schema checks.

#### Scenario: Snapshot отсутствует

- **WHEN** авторизованный valid-CSRF request указывает отсутствующий id
- **THEN** публичный path возвращает текущий plain `404` «Срез не найден.»
- **AND** business facts не изменены

#### Scenario: Snapshot уже accepted

- **WHEN** request указывает snapshot со status `accepted`
- **THEN** публичный path возвращает `303` с `error=immutable`
- **AND** actor/time/content/event history не переписаны

#### Scenario: Есть open blocker

- **WHEN** draft содержит issue с `severity=blocker` и `state=open`
- **THEN** публичный path возвращает `303` с `error=blockers`
- **AND** draft и event history не изменены

#### Scenario: Warning не считается blocker

- **WHEN** draft не содержит open blocker, но содержит open warning
- **THEN** текущий pilot принимает snapshot
- **AND** oracle маркирует этот результат `PILOT_ONLY`

#### Scenario: Resolved blocker не считается open blocker

- **WHEN** draft содержит только blocker со `state=resolved`
- **THEN** текущий pilot принимает snapshot
- **AND** oracle маркирует этот результат `PILOT_ONLY`

### Requirement: Replay и concurrency наблюдаемы без усиления семантики

Focused characterization SHALL доказать текущий non-idempotent-success replay и
row-lock serialization. Конкурентный сценарий MUST использовать два реально
одновременных worker/connection с общей start barrier, а transcript MUST быть
winner-neutral.

#### Scenario: Последовательный replay

- **WHEN** тот же valid acceptance повторяется после commit
- **THEN** второй ответ содержит `error=immutable`
- **AND** первоначальные accepted actor/time и единственный event сохранены

#### Scenario: Два конкурентных acceptance

- **WHEN** два авторизованных worker одновременно принимают один draft
- **THEN** один ответ является success redirect, другой immutable redirect
- **AND** snapshot принят ровно один раз и существует ровно один acceptance
  event независимо от победителя

### Requirement: Oracle изолирован и детерминирован

Focused characterization SHALL владеть приватным bounded SQL namespace и только
точно созданными им LocalAuth session artifacts. Он MUST использовать уникальные
loopback port/cookie/session ids, отключить session GC, сохранить посторонние
tables/rows/session files, классифицировать setup failure отдельно от assertion
regression и удалить свои artifacts при success и failure.

Live Moscow timestamps SHALL проверяться независимыми whole-second bounds с
нормализацией конкретных значений; `accepted_at` и event `occurred_at` MUST NOT
требоваться равными. Любой bounded retry MUST сначала удалить только приватный
fixture namespace.

#### Scenario: Success cleanup и ambient preservation

- **WHEN** oracle завершается успешно рядом с заранее созданными decoy
  table/row/session file
- **THEN** все verifier-owned DB/session/process artifacts удалены
- **AND** каждый decoy сохранён byte-for-byte

#### Scenario: Failure cleanup и классификация

- **WHEN** setup или assertion преднамеренно терпит failure
- **THEN** transcript различает `SETUP_FAILURE` и `REGRESSION`
- **AND** verifier-owned artifacts удалены, а ambient decoys сохранены

#### Scenario: Повторный запуск

- **WHEN** oracle выполняется два раза последовательно
- **THEN** оба нормализованных transcript идентичны
- **AND** второй запуск не зависит от state первого

### Requirement: Oracle входит в canonical verification

Focused characterization SHALL запускаться ровно один раз в canonical
characterization stage и наследовать его setup-blocking contract. Regression в
oracle MUST сделать stage и aggregate verification красными без маскировки
других завершённых stage results.

#### Scenario: Canonical green run

- **WHEN** test environment готов и все assertions oracle проходят
- **THEN** characterization stage сообщает отдельный green result OTIZ
  acceptance

#### Scenario: Canonical regression

- **WHEN** чувствительная acceptance assertion нарушена
- **THEN** characterization stage и aggregate verification завершаются
  regression failure
