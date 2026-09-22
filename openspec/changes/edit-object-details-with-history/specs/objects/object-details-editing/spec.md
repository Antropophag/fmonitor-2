## Purpose

Позволяет уполномоченным сотрудникам безопасно исправлять локальные непроцессные реквизиты существующего объекта, сохраняя отдельные effective values, происхождение и полную append-only историю без подмены интеграционных и процессных фактов.

## ADDED Requirements

### Requirement: Fixed editable field map
Система SHALL принимать обычную правку только для фиксированного серверного allowlist: `address`, `entrance`, `regnumber`, `zavnumber`, `floors`, `weight`, `speed`, `pittype`, `pitmaterial`, `lift_type`, `paired`. Плановые и скорректированные даты MUST NOT входить в этот редактор. Кшах MUST NOT приниматься как ввод: он SHALL вычисляться из effective `pitmaterial` по действующей нормативной таблице. Внутренние IDs, legacy identity, ERP facts, Bitrix links, workforce, документы/основания, открытие/завершение, распоряжения, состав/закрепления, inspections/checklist/photos/progress, сроки справок, status/readiness, расчёты, revisions/hashes/provenance/snapshots/payments MUST NOT быть свободно редактируемыми.

#### Scenario: Allowed field is filled or corrected independently
- **WHEN** уполномоченный actor отправляет одно допустимое поле с валидным новым значением, не заполняя остальные разрешённые поля
- **THEN** меняется только effective value этого поля, остальные значения и их provenance остаются прежними

#### Scenario: Mixed allowed and forbidden payload fails closed
- **WHEN** payload содержит допустимое поле вместе с `ptoactdate`, declaration/opening/ERP/progress/composition/system ID, `shaftBp` или неизвестным полем
- **THEN** команда отклоняется целиком и не меняет overrides, историю или процессные факты

#### Scenario: Integration outage does not unlock owned values
- **WHEN** значение integration-owned поля пусто либо соответствующий worker/source недоступен
- **THEN** поле не становится допустимым для обычной правки

### Requirement: Typed normalization and validation
Система SHALL сохранять номера строками с ведущими нулями, SHALL отклонять `zavnumber` длиннее 120 bytes без обрезания и SHALL не считать значение `0` валидным ERP candidate. Числа SHALL проверяться по действующим предметным диапазонам и точности; справочные значения SHALL проверяться по действующим кодам/значениям и сохранять согласованные `raw` и `display`. Запятая/точка в допустимом десятичном вводе SHALL нормализоваться без float. Пропущенное поле и пустой соседний ввод MUST NOT молча сбрасывать сохранённый override или подставлять `0`/`1`.

#### Scenario: Number-like identifiers preserve identity
- **WHEN** actor сохраняет `regnumber=00042` и `zavnumber=00123-А`
- **THEN** reload возвращает точные строки `00042` и `00123-А`, а ERP candidate использует `00123-А`, не числовые `42`/`123`

#### Scenario: Invalid field value makes no facts
- **WHEN** одно из присланных значений не проходит тип, диапазон, precision, byte-length или справочную проверку
- **THEN** вся команда получает стабильный validation rejection, пользовательский ввод может быть повторно показан формой, а overrides/history не меняются

#### Scenario: Unchanged legacy representation does not block an independent edit
- **WHEN** browser form показывает соседнее source value с неизвестным редактору legacy-кодом или различающимися `raw`/`display`, а actor изменяет другое допустимое поле
- **THEN** browser отправляет только действительно изменённое поле, независимая правка проходит обычную validation, а неизменённое соседнее значение и provenance сохраняются

### Requirement: Single authorized atomic command
Ровно один public application seam SHALL владеть изменением реквизитов и append-only историей. Команда SHALL принимать object identity, actor, `requestId`, `expectedRevision` и ограниченный field patch; author и server time MUST поступать из доверенного runtime. Capability `objects.details.edit` SHALL выдаваться только `fkr_operator` и `manager`; вместе с exact `objects.read` она даёт глобальный scope всех пилотных объектов. Отдельная actor↔object assignment model не создаётся. Access administrator/superadministrator без бизнес-роли MUST NOT получать capability автоматически. Canonical Yii POST SHALL применять обычные auth, CSRF и method rules и SHALL вызывать owner, а не владеть domain mutation.

#### Scenario: Authorized atomic multi-field save
- **WHEN** `fkr_operator` с доступом меняет заводской номер, этажность и материал стен шахты одним валидным request
- **THEN** все три effective values и одно history event фиксируются в одной transaction либо не фиксируется ничего

#### Scenario: Authorization and transport rejections write nothing
- **WHEN** actor неактивен, не имеет capability/доступа к объекту, CSRF неверен или HTTP method/path недопустим
- **THEN** запрос получает действующий sanitized rejection и не создаёт overrides/history

#### Scenario: Replay and concurrency are deterministic
- **WHEN** точный `requestId` повторяется после неизвестного ответа
- **THEN** owner возвращает исходный outcome без второго события; тот же ID с иным нормализованным patch конфликтует; stale `expectedRevision` конфликтует без writes; параллельные команды к одной revision дают не более одного winner

#### Scenario: Normalized no-op creates no event
- **WHEN** patch после нормализации равен текущим effective values
- **THEN** команда возвращает no-op и не создаёт override или history event

### Requirement: Separate overrides and immutable append-only diff
Ручные поправки SHALL храниться отдельно от legacy mirror и импортного technical snapshot. Каждое успешное non-no-op сохранение SHALL создавать одно immutable event с actor identity/display snapshot, server timestamp и полным списком изменённых полей; для каждого поля event MUST хранить field identity, прежние и новые typed/raw/display значения и units/справочные подписи, достаточные для восстановления без нынешнего object state или изменившегося справочника.

#### Scenario: Repeated import preserves correction and source bytes
- **WHEN** после ручной поправки выполняется идентичный legacy/object-detail import
- **THEN** исходные mirror/snapshot/hash остаются прежними, override и event сохраняются, а importer не создаёт новый conflict из-за пользовательской поправки

#### Scenario: Later correction preserves earlier event
- **WHEN** actor второй раз исправляет ранее изменённое поле
- **THEN** появляется новый event со своим old → new, а первый event и его display snapshots не изменяются

#### Scenario: Persistence failure rolls back both halves
- **WHEN** storage failure происходит между подготовкой override и event
- **THEN** transaction не оставляет ни нового effective value без истории, ни history event без состоявшейся правки

### Requirement: Effective values reach current consumers without rewriting history
Карточка, object queue/search/filter/pagination, применимые календарь/агрегаты/отчёты, новые документы и новые расчёты SHALL использовать effective value там, где реквизит является текущим входом. Bitrix technical links, ERP candidates и local ERP matching SHALL использовать effective `zavnumber`; его изменение MUST NOT запускать внешний запрос при Save и MUST NOT выдавать прежние ERP facts как свежие для нового ключа. Без ручного Кшах новый расчёт SHALL вычислять его из effective `pitmaterial`; иные технические поправки SHALL входить в новые расчёты с правдивыми source/version/hash operands. Старые документы, принятые/опубликованные snapshots, выплаты и процессные факты MUST оставаться неизменными.

#### Scenario: Factory-number correction changes matching, not historical facts
- **WHEN** effective `zavnumber` меняется с `00100` на `00101`
- **THEN** новые link/matching reads используют `00101`, Save не делает network call, прежние ERP facts не подтверждают `00101`, неоднозначность fail closed, а прежняя история сохраняется

#### Scenario: Material correction recomputes future Kshah
- **WHEN** effective `pitmaterial` меняется на другое допустимое значение
- **THEN** новый OTIZ calculation вычисляет Кшах из нового материала и фиксирует правдивое evidence, но ранее опубликованный calculation не пересчитывается

### Requirement: Card editor and complete visible history
Карточка SHALL сохранить существующую компоновку и добавить только компактную доступную кнопку-иконку редактирования. Одна modal на публичных `shlz-ui` components SHALL содержать две группы: «Идентификация и размещение» и «Классификация оборудования»; числовые характеристики входят во вторую, Кшах отсутствует. Cancel, GET и открытие modal MUST NOT писать данные. После success/reload карточка SHALL показывать effective values и отличать manual source. Общая вкладка истории SHALL показывать событие «Данные объекта изменены», server time в принятой UI timezone, actor identity/display и каждое old → new; `Не указано` SHALL заменять отсутствие. Пользователь SHALL иметь доступ к событиям старше восьми через простой cursor/«Показать ещё» без отдельного audit screen. Значения SHALL безопасно экранироваться, а history access MUST NOT быть шире object-field access.

#### Scenario: Browser edits and sees grouped history
- **WHEN** actor через canonical карточку меняет три поля, сохраняет, открывает «Историю», затем делает вторую правку и reload
- **THEN** обе операции видны отдельными events, первый содержит все три field diffs, actor/time и неизменные old/new displays

#### Scenario: More than eight events remain reachable
- **WHEN** объект имеет более восьми доступных process/detail events
- **THEN** карточка показывает первую страницу общей хронологии и позволяет получить более старую правку без потери прежних process events

#### Scenario: Shared modal close affordance is usable
- **WHEN** пользователь открывает любую `shlz-modal` в приложении
- **THEN** close control имеет понятную icon-system форму, доступное имя, keyboard focus и полноценную touch/click target вместо микроскопического текстового glyph

### Requirement: Additive schema lifecycle and recovery inventory
Новая persistence SHALL добавляться canonical additive migration без DDL в runtime. Fresh/repeat/upgrade/compatible-partial/incompatible/concurrent migration outcomes SHALL сохранять прежние rows/history и fail closed без destructive repair. Current-image backup/restore inventory SHALL включать новые tables и auto-increment frontiers; restore SHALL сохранять overrides, events, replay identity и возможность следующей правки.

#### Scenario: Backup restore preserves editing history
- **WHEN** объект с несколькими поправками и replay record проходит штатный backup/restore
- **THEN** effective values и все events читаются неизменно, exact replay не дублируется и новая команда может продолжить revision sequence
