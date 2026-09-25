## Purpose

Определяет компактный responsive-список стройконтроля, в котором инженер различает состояние объекта и независимо открывает документацию, план инспекции и чек-лист.

## ADDED Requirements

### Requirement: Компактная идентификация объекта
Список стройконтроля SHALL показывать для каждой записи полный effective адрес, подъезд, регистрационный номер и заводской номер. Поиск SHALL находить запись по адресу, регистрационному или заводскому номеру. Отсутствующий заводской номер SHALL отображаться как `Заводской номер не указан`, без подстановки иной identity.

#### Scenario: Длинный адрес на телефоне
- **WHEN** пользователь открывает список на viewport 320 CSS px и адрес переносится на несколько строк
- **THEN** адрес и все три реквизита остаются читаемыми без горизонтального overflow документа

#### Scenario: Поиск по заводскому номеру
- **WHEN** пользователь вводит существующий заводской номер в серверный поиск
- **THEN** ответ содержит matching объект и сохраняет выбранные ownership/completed filters

### Requirement: Responsive header and filters
Desktop SHALL сохранять заголовок, описание раздела, результат count и белую toolbar-панель поиска/фильтров. На viewport до 680 CSS px заголовок, описание и дублирующий count SHALL быть скрыты, а белая панель SHALL первой показывать поиск, `Мои / Все` и `Показывать завершённые`. Фильтры, clear behavior, pagination reset и server-side selection SHALL сохранять существующий контракт.

#### Scenario: Mobile first viewport
- **WHEN** пользователь открывает список на viewport 390 CSS px
- **THEN** первым content surface является белая панель поиска/фильтров без заголовка и описания раздела

#### Scenario: Desktop context
- **WHEN** пользователь открывает список на desktop viewport
- **THEN** заголовок, описание, count, toolbar и table headings доступны

### Requirement: Независимые действия записи
Запись SHALL иметь три независимых affordance: technical documents, inspection plan и checklist. Available technical document SHALL быть круглой primary `shlz-ui` icon-link на exact effective HTTPS Bitrix URL с `target=_blank` и `rel="noopener noreferrer"`; unavailable/missing/empty document SHALL быть видимой disabled-кнопкой без URL. Inspection affordance SHALL быть primary при отсутствии плана и outlined brand-blue при наличии плана. Checklist SHALL открываться только отдельной правой областью на всю высоту записи со штатным `chevron-right-duo` и вертикальным разделителем; остальная запись MUST NOT быть checklist link.

#### Scenario: Документ доступен
- **WHEN** queue projection содержит один effective technical document
- **THEN** document affordance открывает exact URL в новом безопасном tab

#### Scenario: Документ недоступен
- **WHEN** document status не `available`
- **THEN** запись показывает disabled document affordance и не публикует document URL

#### Scenario: Открытие чек-листа
- **WHEN** пользователь активирует правую checklist area pointer или keyboard
- **THEN** браузер переходит к существующему checklist route данного объекта

### Requirement: План инспекции
Список SHALL использовать существующий inspection-planning command seam. Без плана calendar-кнопка SHALL открыть create dialog. При current plan она SHALL открыть reschedule dialog с доступной отменой текущего плана. Метка SHALL быть `Инспекция сегодня` без повторной даты для Moscow today; иные current plans SHALL показывать `Инспекция запланирована на ДД.ММ.ГГГГ`. Existing authorization, CSRF, request identity, optimistic version, error retention, redirect filters, append-only history and calendar projection MUST сохраняться.

#### Scenario: План отсутствует
- **WHEN** объект не имеет current inspection plan
- **THEN** primary calendar affordance открывает create dialog

#### Scenario: Инспекция сегодня
- **WHEN** current inspection date равна Moscow today
- **THEN** запись показывает ровно `Инспекция сегодня` без второй даты

#### Scenario: План существует
- **WHEN** объект имеет future current plan
- **THEN** outlined calendar affordance открывает reschedule dialog, где пользователь может перенести либо отменить план

### Requirement: Операционные индикаторы
Запись SHALL сохранять существующий colored local-sync indicator и его accessible state label. Full/partial equipment shipment SHALL показывать штатный `delivery-box` с существующим accessible label; unknown shipment SHALL не изображать подтверждённую отгрузку. `Готов к открытию`, last activity и `Инспекций ещё не было` SHALL сохранять существующую read semantics.

#### Scenario: Локальная очередь синхронизации
- **WHEN** у объекта существует queued, sending, retryable или blocked local operation
- **THEN** индикатор получает существующие цвет, `data-state` и accessible label без изменения server facts

#### Scenario: Отгрузка оборудования
- **WHEN** projection сообщает full либо first shipment date
- **THEN** запись показывает delivery marker с соответствующим existing label

### Requirement: Design-system and interaction quality
Интерфейс SHALL использовать shipped `shlz-ui` buttons, fields, segments, choices, colors and icons. Icon actions SHALL иметь доступные имена, keyboard focus и touch target не меньше 40x40 CSS px. Press feedback SHALL быть коротким и отключаться при `prefers-reduced-motion`. Viewports 320, 390 и desktop MUST NOT иметь document-level horizontal overflow.
На viewport до 680 CSS px primary navigation SHALL оставаться однострочной production-like панелью с actions 48x48 CSS px; все permission-allowed links SHALL оставаться доступны через horizontal swipe при скрытом scrollbar.

#### Scenario: Production-like bottom navigation
- **WHEN** пользователю разрешены все разделы и viewport равен 390 CSS px
- **THEN** все links находятся в одном 48px ряду, последний link доступен horizontal swipe, а document не имеет horizontal overflow

#### Scenario: Keyboard navigation
- **WHEN** пользователь проходит controls клавишей Tab
- **THEN** document, inspection and checklist affordances получают различимый focus и активируются стандартной клавиатурой

#### Scenario: Reduced motion
- **WHEN** ОС сообщает `prefers-reduced-motion: reduce`
- **THEN** необязательные transitions/animations отключены без потери состояния или действия
