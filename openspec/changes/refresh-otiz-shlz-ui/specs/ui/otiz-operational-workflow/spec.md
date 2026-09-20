## Purpose

Задаёт наблюдаемый контракт цельного, адаптивного и доступного рабочего интерфейса ОТиЗ поверх неизменных финансовых команд и фактов.

## ADDED Requirements

### Requirement: Цельный workflow периода
Раздел ОТиЗ SHALL объединять выбранный период, readiness, финансовый итог и ровно одно основное разрешённое действие в одном workflow header; overview, register, snapshot и history SHALL сохранять узнаваемую навигацию и текущий контекст.

#### Scenario: Подготовка и проверка расчёта
- **WHEN** уполномоченный сотрудник открывает подготовку выплат либо draft/accepted snapshot
- **THEN** период, состояние готовности, итог и доступное primary action MUST быть видимы вместе, а недоступные действия MUST NOT отображаться

### Requirement: Объектная связь доказательств и нарушений
Каждый объект SHALL образовывать semantic region с идентичностью, начислениями, доказательствами, распределениями и нарушениями этого объекта; status и текст MUST передавать связь без зависимости только от цвета или раскрытого hover-state.

#### Scenario: Объект с доказательствами и нарушением
- **WHEN** snapshot содержит allocation, trace/evidence и blocker либо warning для объекта
- **THEN** пользователь MUST однозначно определить, к какому объекту относятся факты и кто владеет исправлением, не переходя к другому экрану

### Requirement: Семантика действий
Изменяющие состояние команды SHALL отображаться как `shlz-ui` button compositions с primary, secondary или danger иерархией по последствиям; навигационные переходы SHALL оставаться ссылками, стилизованными согласно их роли.

#### Scenario: Выплата, удержание и сторно
- **WHEN** permission и состояние разрешают принять расчёт, добавить удержание, завершить выплату либо сторнировать запись
- **THEN** действие MUST иметь различимую визуальную и текстовую семантику, keyboard activation и неизменные method, route, CSRF и form payload

### Requirement: Адаптивные таблицы и input modes
Каждая таблица SHALL иметь явную mobile strategy: labelled rows для object/register данных либо contained horizontal scroll для ledger, где сохранение колонок существенно. На 320, 768, 1024 и 1440 CSS px, при 200% zoom, keyboard-only и coarse pointer SHALL отсутствовать horizontal page overflow; coarse-pointer targets MUST быть не менее 44×44 CSS px.

#### Scenario: Mobile, zoom и coarse pointer
- **WHEN** пользователь открывает register, snapshot или ledger в любом указанном viewport/input context
- **THEN** content и actions MUST оставаться читаемыми и достижимыми, focus MUST быть видим, а overflow MUST быть ограничен самим table container, если выбран contained-scroll

### Requirement: Progressive enhancement и reduced motion
Authenticated SSR SHALL содержать основной контент, навигацию и native forms без JavaScript. Motion SHALL быть необязательным enhancement и MUST отключаться при `prefers-reduced-motion: reduce` без потери состояния или действия.

#### Scenario: JavaScript выключен
- **WHEN** пользователь открывает любой GET экран ОТиЗ с disabled JavaScript
- **THEN** workflow context, object facts, evidence/issues и все разрешённые native links/forms MUST оставаться доступными

#### Scenario: Reduced motion
- **WHEN** платформа сообщает `prefers-reduced-motion: reduce`
- **THEN** интерфейс MUST исключить необязательные transitions/animations и сохранить ту же информацию и tab order

### Requirement: Финансовая и авторизационная неизменность
Presentation change MUST сохранять текущие permissions, authorization-before-validation, GET/HEAD read-only behavior, formulas, routes, methods, CSRF, field names/values, return paths, replay/idempotency/concurrency outcomes, append-only audit/history и отсутствие новых фактов при rejected commands.

#### Scenario: Разрешённая команда и replay
- **WHEN** уполномоченный актор отправляет существующую команду и затем её exact replay через обновлённую форму
- **THEN** observable response, persisted facts и replay result MUST совпасть с действующим финансовым контрактом без дополнительного денежного или audit-факта

#### Scenario: Запрет и невалидный ввод
- **WHEN** неуполномоченный актор либо невалидный payload достигает публичного HTTP seam
- **THEN** текущий denial/error outcome MUST сохраниться и новые snapshot, closure, operation или event facts MUST NOT появиться

### Requirement: Полный реестр экономики объектов
Yii object register SHALL показывать объект, прогресс, фонд премии, Кшах,
заработано, выплачено, удержано, остаток фонда и состояние. Значения и глобальная
сводка MUST поступать из существующего `ObjectRegister`; view MUST NOT заменять
неизвестные значения нулями или вычислять финансовые формулы самостоятельно.

#### Scenario: Заполненная экономическая строка
- **WHEN** объект имеет доказанные входы нормы и рассчитанные суммы
- **THEN** все девять колонок MUST содержать соответствующие значения, а summary MUST учитывать весь реестр независимо от текущей страницы

#### Scenario: Неполный норматив
- **WHEN** материал либо другой обязательный вход нормы неизвестен
- **THEN** денежное значение MUST отображаться как неизвестное, состояние MUST быть понятным пользователю `missing_norm`, и система MUST NOT показывать вымышленный `0,00 ₽`

### Requirement: Доказанное разрешение материала шахты
Импортированный legacy identifier материала SHALL разрешаться через существующий
публичный legacy reference source до применения `NativePremiumNorms`. Known
mapping MUST давать канонический display-value и provenance; unknown mapping MUST
оставаться неизвестным и MUST NOT подбираться эвристически.

#### Scenario: Known legacy material identifier
- **WHEN** карточка содержит identifier с доказанным справочным соответствием
- **THEN** register MUST использовать канонический материал и вычислить фонд по действующей норме

#### Scenario: Unknown legacy material identifier
- **WHEN** справочник не доказывает соответствие identifier
- **THEN** объект MUST остаться `missing_norm` без записи или изменения бизнес-фактов

### Requirement: Серверная пагинация реестра
OTIZ register SHALL выводить текущий диапазон, page size и доступные страницы из
существующего server-side page result. Search, state, sort и pageSize MUST
сохраняться в GET controls/links; смена отбора MUST начинать page 1.

#### Scenario: Реестр из 334 объектов
- **WHEN** пользователь открывает первую страницу размера 50
- **THEN** DOM MUST содержать не более 50 object rows, summary MUST сообщать полный count, а keyboard-accessible pager MUST вести на страницу 2 с сохранённым query context

### Requirement: Единая пагинация справочников и реестров
Все существующие pageable Yii списки — объекты монтажа, монтажники,
стройконтроль и ОТиЗ — SHALL использовать reusable public `shlz-pagination`
composition. Markup MUST содержать purpose-specific accessible name, list/item
structure, current page, disabled directions и ellipsis согласно public export;
локальные button-like pagination substitutes MUST NOT использоваться.

#### Scenario: Первая, средняя и последняя страницы
- **WHEN** пользователь открывает любую pageable Yii surface на первой, средней или последней странице
- **THEN** pager MUST иметь согласованную shlz-ui геометрию и состояния, сохранять surface filters и работать клавиатурой без page-level overflow
