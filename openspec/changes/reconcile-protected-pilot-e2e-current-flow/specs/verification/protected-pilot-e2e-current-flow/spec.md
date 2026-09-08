## Purpose

Определяет защищённое сквозное доказательство актуального ручного пилота от очереди объектов до документального завершения, сохраняя security, append-only и persistence инварианты публичных seam.

## ADDED Requirements

### Requirement: Demo bootstrap SHALL validate current prerequisites and delegate the full journey
The demo bootstrap verifier SHALL require the literal canonical v19 catalogue,
the three imported-source tables in the same generation namespace, and an active
configured-email local actor with exact roles and permissions. It SHALL retain
launcher ownership, nonce, foreign-data cleanup and permission checks. Its own
durable mutation MAY stop at current composition selection only while the
mandatory protected child proves the complete current business journey.

#### Scenario: Legacy bootstrap journey is superseded without losing coverage
- **WHEN** the demo generation starts, is restarted, reset and cleaned up
- **THEN** the verifier does not invoke manual registration or require v4/eight tables
- **AND** current selection persists across restart and reset returns to the initial projection
- **AND** a missing canonical v19 table makes `status` incomplete despite an intact ready marker
- **AND** both independent nonce anchors and foreign data remain protected in the shared namespace
- **AND** the mandatory protected child proves original/correction, atomic opening, checklist and completion

### Requirement: Защищённый E2E SHALL проходить актуальный основной маршрут
Verifier SHALL через реальные HTTP/browser-compatible представления пройти очередь, выбрать объект и состав, при необходимости сформировать inline PDF-шаблон, загрузить и подтвердить original, исправить original, вернуться в карточку, выполнить явный атомарный `open_confirmed`, закрыть 41 монтажный пункт и фото до 85%, затем акт ПТО и обязательную декларацию до 100%.

#### Scenario: Успешный маршрут без ручной регистрации и отдельного apply UI
- **WHEN** активные пользователи с точными ролями выполняют актуальную последовательность над изолированным объектом
- **THEN** original upload/correction возвращает карточку с точным current original и составом, карточка содержит прямое opening с фактической датой, а отдельные manual registration и apply UI отсутствуют

#### Scenario: Необязательный шаблон остаётся inline и воспроизводимым
- **WHEN** авторизованный ФКР запрашивает шаблон до original upload
- **THEN** текущий POST endpoint возвращает один inline PDF с точными media/disposition/length/hash и passive semantic markers без browser download и без сохранения template bytes как версии original; GET/HEAD read-only contract сохраняется для current original download endpoint

### Requirement: Opening SHALL атомарно создавать application и opening facts
Verifier SHALL доказать, что `open_confirmed` проверяет exact current original revision, composition, expected application sequence, роль и фактическую дату, а затем в одной транзакции применяет подтверждённый состав при необходимости, открывает дело и связывает checklist template.

#### Scenario: Прямое успешное открытие из карточки
- **WHEN** пользователь с `installation.open` отправляет trusted CSRF, UUID request, exact order/revision, current sequence и допустимую фактическую дату
- **THEN** система создаёт ровно один application fact, один native opening event `installation_opened_from_original`, opening fields и одну применимую association шаблона и возвращает карточку

#### Scenario: Ошибка после application write откатывает весь переход
- **WHEN** confirmed original допустим, но применимый checklist template отсутствует
- **THEN** application, application attempt, opening, event и association snapshots остаются неизменными

#### Scenario: Replay и конфликт не дублируют историю
- **WHEN** тот же request повторён либо revision/sequence устарели
- **THEN** exact replay возвращает прежний terminal result без новых фактов, а stale request отклоняется без partial writes

### Requirement: Authorization и transport checks MUST сохраняться
Verifier MUST сохранить точные серверные проверки identity, role/capability, CSRF, Origin, method, canonical route, body shape/size и redacted infrastructure failures.

#### Scenario: Роли разделены
- **WHEN** viewer, uploader, opener и стройконтроль вызывают публичные seam
- **THEN** каждый видит и изменяет только разрешённые операции; opener с `installation.open` может выполнить compound opening без отдельного `assignment_order.composition.apply`, но standalone apply остаётся запрещён

#### Scenario: Некорректный transport не меняет домен
- **WHEN** запрос имеет wrong method/path/media/origin/CSRF, duplicate/unknown fields или forged actor
- **THEN** возвращается точный 4xx/Allow/redirect contract и business snapshots остаются неизменными

### Requirement: История и данные MUST оставаться append-only и воспроизводимыми
Verifier MUST доказать сохранность original revisions, composition/application/opening/checklist/completion events, точных actor/date/source facts и отсутствие изменений legacy source, workforce authority и чужих объектов.

#### Scenario: Исправление original до открытия
- **WHEN** принят correction original до opening
- **THEN** предыдущая revision остаётся доступной, карточка и opening используют только current revision и current application sequence, а applied/opening facts до POST не меняются

#### Scenario: Повторное чтение после нового подключения
- **WHEN** маршрут завершён и данные читаются новой MariaDB connection после reload/restart-compatible boundary
- **THEN** статус, 100% progress, team, documents, events, checklist photos и completion facts совпадают с созданной append-only историей

### Requirement: Bootstrap MUST запускать защищённый актуальный E2E без skip
DB/E2E bootstrap SHALL создать только синтетические prerequisites текущего flow, включая local roles, accepted original/application schemas, checklist template и completion schemas, и SHALL запускать protected verifier как обязательный тест.

#### Scenario: Чистый exact-SHA запуск
- **WHEN** dependencies установлены и disposable MariaDB готова
- **THEN** protected verifier стартует на чистом checkout, не зависит от owner data/stand/browser и failure учитывается как failure, а не skip

### Requirement: Superseded representation assertions MUST быть удалены явно
Verifier MUST удалить ожидания, которые противоречат более новым решениям владельца, сохранив независимые security и data assertions вокруг тех же операций.

#### Scenario: Устаревшие ожидания не возвращаются
- **WHEN** protected fixture обновляется
- **THEN** она не требует универсальный semantic queue без текущих фильтров/таблицы, ручной номер 1С ДО, legacy registration endpoint, отдельный apply CTA, старые status labels или отдельный appendix artifact
