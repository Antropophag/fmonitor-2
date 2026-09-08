## Purpose

Доставить через портал загрузку, историю и скачивание подписанного оригинала распоряжения с сохранением application authorization и неизменяемых доказательств. Mapping читателей одобрен владельцем 2026-09-05; exact executable HTTP contract ещё требует Gate 1.

## ADDED Requirements

### Requirement: Авторизованная загрузка через application command
Портал SHALL передавать initial upload/correction единственному владельцу original facts. Actor MUST происходить из действующей session, initial и correction MUST требовать разные exact capabilities. HTTP layer MUST проверять CSRF и admission до вызова команды; ручной номер не является входом нового workflow.

#### Scenario: Оригинал после шаблона или напрямую
- **WHEN** сотрудник либо Руководитель ФКР с exact capability передаёт один PDF, выбранное распоряжение, подтверждение состава и дату документа
- **THEN** портал возвращает результат application command; original evidence сохраняется один раз, а opening и интервалы состава не меняются

#### Scenario: Отказ в доступе
- **WHEN** session, CSRF либо exact capability не допускает операцию
- **THEN** original command не выполняется, evidence и private storage не изменяются; публичный ответ не раскрывает документ

### Requirement: Replay и correction сохраняют историю
Портал SHALL сохранять request identity для повтора того же intent после потери ответа и передавать exact revision identities для correction. Новое намерение MUST использовать новый request identity. Ответы SHALL сохранять различие accepted/replayed/rejected/conflict/retryable failure из application contract.

#### Scenario: Повтор после потери ответа
- **WHEN** принятый запрос повторён с той же identity действующим authorized actor
- **THEN** портал показывает сохранённый результат без новой revision; accepted replay следует approved original command audit policy

#### Scenario: Устаревшее исправление
- **WHEN** actor исправляет уже сменившуюся current revision новым intent
- **THEN** портал сообщает конфликт; исходные bytes/date/history сохранены

### Requirement: Авторизованное чтение immutable evidence
Metadata/history/download SHALL выполняться через read-only application seam и exact local permission `assignment_order.original.read`. Active сотрудник ФКР и Руководитель ФКР SHALL читать распоряжения доступных им объектов; active инженер стройконтроля SHALL читать распоряжения закреплённых за ним объектов; active специалист ОТиЗ SHALL читать все распоряжения. Все эти grants включают прошлые immutable revisions. Административная роль MUST NOT автоматически давать это permission. Runtime MUST проверять explicit permission и соответствующий scope до выдачи bytes и не выводить read permission из upload/correct.

#### Scenario: Чтение сохранённой revision
- **WHEN** пользователь с утверждённым read permission и доступом к объекту выбирает сохранённую revision
- **THEN** metadata и скачанные bytes относятся к одной immutable revision; SHA-256 и размер совпадают с accepted evidence, domain history не меняется

#### Scenario: ОТиЗ читает любое распоряжение
- **WHEN** active специалист ОТиЗ с exact read permission выбирает распоряжение любого объекта, включая прошлую revision
- **THEN** metadata/history/download доступны независимо от закрепления инженера; чтение не разрешает upload/correction/opening

#### Scenario: Инженер читает только закреплённые объекты
- **WHEN** инженер с exact read permission запрашивает распоряжение объекта, за которым он не закреплён, и не имеет иной явно разрешающей роли
- **THEN** metadata и bytes не выдаются, включая прошлые revisions

#### Scenario: Администрирование не даёт document read
- **WHEN** пользователь имеет только административную роль без explicit original read grant
- **THEN** metadata/history/download недоступны без раскрытия original evidence

#### Scenario: Недоступный original
- **WHEN** revision отсутствует, объект недоступен либо private bytes невозможно надёжно прочитать
- **THEN** документ не выдаётся; exact not-found/forbidden/unavailable HTTP mapping определяется executable Gate 1 без path/secret leakage и без частичного успешного ответа

### Requirement: Закрытая HTTP поверхность
До реализации executable spec MUST определить exact method/path, поля, limits multipart overhead, result mapping, metadata DTO, download headers и legacy-route disposition. Download SHALL отдавать PDF как attachment с безопасным именем и запретом MIME sniffing; приватные bytes MUST NOT становиться web-root файлами. Ошибки admission и транспорта MUST иметь безопасный audit по утверждённому session/security contract.

#### Scenario: Неподдерживаемый upload
- **WHEN** transport содержит несколько файлов, неподдерживаемый media type либо превышает утверждённые transport bounds
- **THEN** HTTP adapter отклоняет transport без accepted fact; application received-byte limit остаётся `20,971,520` и не ослабляется

### Requirement: Approved selected-original binding
Fresh HTTP SHALL вызывать approved createForSelections original factory. Optional
PDF SHALL использовать approved on-demand owner, без файлов/версий и hidden prepare.

#### Scenario: Прямой оригинал после выбора
- **WHEN** состав сохранён native selection без формирования PDF
- **THEN** original HTTP использует эту identity и разрешает ввод documentDate по оригиналу; template не требуется
