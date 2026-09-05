## Purpose

Сохранять выбранный состав распоряжения как самостоятельное основание для загрузки оригинала без обязательного формирования PDF-шаблона. Пакет planning-only; exact executable selection contract требует Gate 1.

## ADDED Requirements

### Requirement: Выбор состава не требует renderer

Система SHALL предоставлять публичное действие выбора одного или нескольких уникальных монтажников из кадрового каталога и ровно одного инженера для одного объекта. Успех MUST сохранять immutable composition identity и order identity, пригодные для original upload, без вызова renderer, создания template metadata или записи template bytes.

#### Scenario: Прямая загрузка начинается с выбора
- **WHEN** authorized сотрудник либо Руководитель ФКР выбирает допустимый состав на eligible объекте без ранее сформированного шаблона
- **THEN** система сохраняет выбранный состав и возвращает identity для original upload; отсутствие renderer/template storage не препятствует этому действию

### Requirement: Authorization и prerequisites сохраняются

Selection MUST проверять active actor, exact capability и object/workforce/engineer eligibility до mutation. Exact permission mapping и stable rejected codes SHALL быть закреплены executable Gate 1; read permission ОТиЗ или administrator role не дают selection автоматически.

#### Scenario: Некорректный состав
- **WHEN** отсутствует монтажник/инженер либо выбранный человек не соответствует approved eligibility
- **THEN** selection отклоняется с exact причиной Gate 1, order/composition/history не создаются

#### Scenario: Read-only actor
- **WHEN** actor имеет только original read permission без selection authority
- **THEN** selection запрещён до domain mutation

### Requirement: Selection не применяет состав и не открывает работы

Сохранение выбранного состава SHALL быть отдельным фактом подготовки. Оно MUST NOT менять действующие интервалы назначений, actual start, opening snapshot, checklist availability или accepted original evidence. Audit SHALL сохранять actor/time/основание selection append-only.

#### Scenario: Состав выбран, original ещё отсутствует
- **WHEN** selection завершён
- **THEN** объект остаётся закрыт для работ и checklist; original upload возможен только отдельной командой, а opening — после applicable original отдельным действием

#### Scenario: Новый выбор сохраняет прежнее действующее назначение
- **WHEN** у объекта уже есть applicable order, а пользователь сохраняет новую неподписанную selection
- **THEN** directory assignments/availability и inspection engineer/installer attribution прежнего applicable order остаются неизменными; новый MAX(version) не скрывает действующее основание

### Requirement: Replay и конкурентные selections сохраняют единственную историю

Exact retry SHALL не создавать duplicate version/audit. Конкурентные изменения MUST проверять ожидаемую identity/version и не переписывать ранее сохранённый состав. Exact request/replay/stale и pre-original correction contract SHALL быть определён до Gate 1; последующая смена действующего состава относится к отдельному lifecycle slice.

#### Scenario: Повтор после потери ответа
- **WHEN** тот же selection intent повторяется после успешного commit
- **THEN** возвращается исходная identity без нового order/composition/audit

#### Scenario: Два несовместимых выбора
- **WHEN** два callers предлагают разные составы для одного expected state
- **THEN** не возникает двух неразличимых current selections; loser получает предусмотренный Gate 1 conflict, а winner/history сохраняются

### Requirement: Необязательный шаблон использует сохранённый состав

При последующем запросе шаблона система SHALL использовать ту же immutable composition identity; renderer failure MUST NOT удалять выбор или вынуждать менять состав. Generated template не является signed original или gate открытия.

#### Scenario: Renderer недоступен
- **WHEN** после successful selection optional render не может завершиться
- **THEN** сохранённый состав остаётся доступным для прямой загрузки готового original PDF
