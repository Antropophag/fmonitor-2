## Purpose

Определяет наблюдаемый production/pilot-контур автоматической hourly ERP-синхронизации оборудования: безопасную конфигурацию, bounded чтение, durable-job исполнение, readiness и state-preserving эксплуатацию.

## ADDED Requirements

### Requirement: Версионированное исполнение ERP job через публичный jobs seam
Система SHALL принимать ровно зарегистрированный versioned job type `erp.equipment-facts.sync` через тот же публичный claim/handler/worker seam, что и остальные durable jobs. Успешно завершённый application run SHALL завершать job; техническая недоступность источника SHALL создавать безопасный failed run через единственного владельца equipment facts и классифицировать job как retryable. Неизвестная версия или иной job type MUST отклоняться как configuration error без вызова ERP.

#### Scenario: Успешная claimed job
- **WHEN** worker claim-ит поддерживаемую ERP sync job, источник возвращает валидный bounded результат и application owner выдаёт completed receipt
- **THEN** worker помечает job completed и не создаёт дополнительный run или history вне application seam

#### Scenario: Недоступный источник
- **WHEN** поддерживаемая claimed job получает `SOURCE_UNAVAILABLE` из-за соединения, авторизации, timeout или любого незавершённого source chunk
- **THEN** application seam атомарно фиксирует безопасный failed run, projection и last-success остаются прежними, а job получает retryable outcome без DSN, SQL, credentials или source rows

#### Scenario: Неподдерживаемый claim
- **WHEN** worker получает неизвестную версию или незарегистрированный job type
- **THEN** claim отклоняется с allowlisted configuration reason до обращения к ERP и без изменения equipment facts

### Requirement: Hourly и ручной запуск используют один canonical sync
Scheduler SHALL создавать не более одной ERP job для текущего часового slot, а запущенные scheduler и worker SHALL доводить её до terminal outcome через canonical sync. Ручной owner-approved запуск SHALL использовать тот же application/job contract. Возобновление после паузы MUST NOT создавать backlog за пропущенные часы.

#### Scenario: Первый tick после старта
- **WHEN** scheduler стартует и обрабатывает текущий часовой slot при healthy worker
- **THEN** job текущего slot создаётся и реально исполняется до completed либо retryable terminal attempt

#### Scenario: Повторный tick того же часа
- **WHEN** scheduler повторно обрабатывает тот же slot
- **THEN** второй durable job не создаётся и идентичное ERP state не добавляет equipment history

#### Scenario: Ручной запуск
- **WHEN** оператор выполняет документированный ручной sync с корректной конфигурацией
- **THEN** запускается тот же canonical sync, возвращается безопасный run receipt и соблюдаются те же atomicity, retry и idempotency rules

### Requirement: Bounded source scope определяется локальными exact order numbers
Система SHALL до обращения к ERP получить множество trim-нормализованных уникальных ненулевых `fm_maintable.zavnumber`, исключить пустые и `0`, разбить множество на конфигурируемые bounded chunks и безопасно параметризовать каждый source query. Она MUST NOT читать или материализовывать глобальный ERP-каталог и MUST NOT считать глобальный размер более 10 000 ограничением sync. Результат каждого chunk SHALL приниматься только после успеха полного набора запросов; failure любого chunk делает весь batch неприменимым.

#### Scenario: Глобальный каталог больше 10 000
- **WHEN** ERP содержит более 10 000 заказов, а локально существует bounded множество релевантных номеров
- **THEN** source запрашивает только эти номера chunks и полный локальный batch может завершиться независимо от размера глобального каталога

#### Scenario: Нулевые и пустые номера
- **WHEN** локальные объекты имеют `zavnumber` равный `0`, пустой строке или whitespace
- **THEN** эти значения не входят в параметры ERP query, не сопоставляются и не создают произвольных writes

#### Scenario: Неоднозначный локальный номер
- **WHEN** один нормализованный ненулевой `zavnumber` принадлежит нескольким локальным объектам
- **THEN** ERP record может быть прочитан один раз, но ни один из этих объектов не обновляется, а diagnostic не содержит raw `zavnumber`

#### Scenario: Ошибка одного chunk
- **WHEN** хотя бы один source chunk не получен или не прошёл полную validation
- **THEN** ни одна projection/history/last-success запись этого batch не изменяется, а failed run фиксируется безопасно

#### Scenario: Stage существует вне authoritative order aggregate
- **WHEN** bounded shipment query возвращает stage для локального номера, которого нет в успешно полученном order aggregate
- **THEN** stage игнорируется без source record, clear, diagnostic или failure, а остальные authoritative order records применяются

### Requirement: Реальный fully-qualified legacy ERP contract
Read-only source SHALL обращаться к таблицам legacy ERP только с fully-qualified prefix `[1c-erp].[...]`, независимо от default database login. Orders SHALL связываться по `prod.Номер = sale.Номер` и `sroki.ЗаказКлиента = sale.Ссылка`, возвращая `sale.Номер`, `MAX(sroki.ДатаКомплектности)` и `MAX(sroki.ДатаПолнойОтгрузки)`. Shipment stages SHALL связываться по `prod.Ссылка = etap.Распоряжение`, `sale.Ссылка = prod.ДокументОснование`, `type.Ссылка = sale.shlz_ТипЗаказа`, фильтровать `etap.ПометкаУдаления = 0` и `type.Наименование = N'ЛифтовоеОборудование'`, а `NULL` и `0001-01-01` исключать до вычисления `MIN(ДатаОтгрузки)`.

#### Scenario: Default database не равна ERP database
- **WHEN** read-only login успешно соединяется с сервером, но его default database не `1c-erp`
- **THEN** bounded query работает через fully-qualified identifiers без попытки открыть `1c-erp` как default database

#### Scenario: Owner-approved pilot TLS compatibility
- **WHEN** текущий изолированный pilot подключается к legacy SQL Server без предоставленного корпоративного CA/server certificate
- **THEN** transport использует encrypted `Encrypt=yes;TrustServerCertificate=yes` как явное owner-approved pilot exception, никогда не отключает encryption и не распространяет ERP credentials за jobs contour

#### Scenario: Sentinel и реальная shipment date
- **WHEN** для одного заказа существуют sentinel `0001-01-01`, `NULL` и одна или несколько реальных дат отгрузки
- **THEN** первой отгрузкой является минимальная реальная дата, а sentinel и `NULL` не влияют на результат

#### Scenario: Sentinel агрегированной order date
- **WHEN** readiness либо full-shipment aggregate возвращает legacy sentinel `0001-01-01`
- **THEN** source record содержит `NULL` для этого факта, а не invalid batch или sentinel date

### Requirement: Authoritative применение и приватность остаются атомарными
Присутствующая source record SHALL authoritative задавать все три nullable facts: `NULL` явно очищает соответствующий факт. Отсутствующий среди успешно запрошенных локальных номеров order MUST NOT очищать сохранённые факты. Exact unique mapping SHALL обновляться только через application owner; повтор состояния MUST NOT создавать history. Raw `zavnumber`, source rows, SQL, DSN и credentials MUST NOT храниться в equipment history/diagnostics и MUST NOT попадать в receipts, health output, logs или review evidence.

#### Scenario: Exact unique order
- **WHEN** один ненулевой локальный `zavnumber` точно соответствует одной валидной source record
- **THEN** application owner обновляет ровно этот объект и связывает projection/history с безопасным provenance run

#### Scenario: Explicit clear и отсутствующая record
- **WHEN** присутствующая record содержит `NULL` для ранее заполненного факта, а другой локальный order отсутствует в source result
- **THEN** первый факт очищается с append-only history, а факты отсутствующего order остаются неизменными

#### Scenario: Повтор того же состояния
- **WHEN** следующий успешный run возвращает те же факты
- **THEN** last-success/freshness отражает новый успешный run, но новые fact-change history rows не появляются

### Requirement: ERP runtime configuration задаётся непосредственно в локальном `.env`
Pilot runtime SHALL принимать `FMONITOR_ERP_HOST`, `FMONITOR_ERP_DATABASE`, `FMONITOR_ERP_USER`, `FMONITOR_ERP_PASSWORD` и `FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY` непосредственно из ignored `.env`, а также только явно allowlisted bounded timeout/maxRows/chunk-size параметры. Password/HMAC file variables MUST NOT быть обязательными. Canonical Compose и generated Compose SHALL передавать одинаковый allowlisted набор в worker/scheduler consumers; `.env.example` SHALL содержать только безопасные placeholders, а реальный `.env` MUST оставаться untracked с ограниченными правами.

#### Scenario: Полная корректная конфигурация
- **WHEN** все обязательные ERP переменные непусты и bounded numeric параметры валидны
- **THEN** worker и scheduler стартуют с одинаковой конфигурацией без чтения отдельных secret files

#### Scenario: Отсутствующая или пустая переменная
- **WHEN** хотя бы одна обязательная ERP переменная отсутствует или пуста либо bounded параметр выходит из разрешённого диапазона
- **THEN** canonical startup fail-fast возвращает `CONFIGURATION_INVALID` до фонового restart-loop и не выполняет ERP query

#### Scenario: Неверные, но непустые credentials
- **WHEN** конфигурация структурно валидна, но ERP отклоняет read-only доступ
- **THEN** запущенная job получает безопасный failed run и retryable outcome без раскрытия значения или connection details

### Requirement: Штатный pilot startup и readiness охватывают jobs-контур
Один документированный owner-approved штатный startup SHALL state-preserving поднимать web/db/php вместе с jobs-worker и jobs-scheduler. Pilot process readiness SHALL быть GREEN только когда обязательные HTTP/runtime dependencies healthy, оба jobs process присутствуют и их наблюдаемые heartbeat state находятся в допустимом freshness window. Остановка, crash, stale heartbeat или invalid ERP configuration любого обязательного jobs process MUST делать process readiness non-GREEN. Append-only dead-job/queue diagnostics SHALL оставаться в отдельном operator health и MUST NOT навсегда блокировать process readiness после восстановления процессов.

#### Scenario: Complete contour healthy
- **WHEN** штатный startup поднял все services, scheduler тикает, worker способен claim-ить job и конфигурация валидна
- **THEN** readiness сообщает healthy без раскрытия секретов

#### Scenario: UI работает без worker или scheduler
- **WHEN** web отвечает, но worker или scheduler отсутствует, остановлен, crash-looping либо stale
- **THEN** readiness сообщает non-GREEN с allowlisted component reason и не создаёт ложное впечатление работающей hourly integration

#### Scenario: State-preserving deployment
- **WHEN** оператор обновляет pilot обычным документированным способом
- **THEN** существующие database volumes, sessions и artifacts сохраняются, schema migration выполняется additive/recoverable способом и reset/delete-volume не является частью штатного пути

### Requirement: Карточка и operational qualification подтверждают end-to-end результат
После успешного run карточка `/pilot/objects/<id>` SHALL показывать три независимые даты и время последней успешной синхронизации согласно существующему read contract. Локальная qualification на `127.0.0.1:8093` SHALL сохранить данные стенда, доказать healthy scheduler/worker, successful run receipt, заполнение подтверждённых объектов 1226, 1427, 2238 и 2239 и отсутствие duplicate history после same-slot/identical-state повтора. Объект 1318 с `zavnumber=0` MUST NOT сопоставляться.

#### Scenario: Подтверждённые объекты после live read-only sync
- **WHEN** стенд настроен локальными legacy credentials и canonical hourly job успешно завершён
- **THEN** projections и защищённые карточки 1226, 1427, 2238 и 2239 показывают полученные facts и last successful sync, а safe receipt идентифицирует completed run без source data

#### Scenario: Нулевой номер на стенде
- **WHEN** qualification проверяет объект 1318 с `zavnumber=0`
- **THEN** объект не запрашивается и не получает ERP equipment projection по произвольному совпадению

#### Scenario: Повтор qualification без разрушения состояния
- **WHEN** повторяется tick того же slot или тот же authoritative state
- **THEN** durable job/history остаются без дублей, а database, sessions, artifacts и volumes не удаляются и не reset-ятся
