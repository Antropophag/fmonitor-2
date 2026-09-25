## Purpose

Даёт администратору честное read-only состояние очереди и сохранённых попыток синхронизации технической документации Битрикс без запуска интеграции и раскрытия секретов.

## ADDED Requirements

### Requirement: Состояние строится только из durable фактов документной синхронизации
На `GET|HEAD /pilot/admin/integrations` система SHALL читать только jobs и events с `job_type=bitrix.order-document-links.sync`. Время начала попытки MUST происходить из `claimed` event, а не из enqueue/created time. Отсутствие job или claimed event MUST NOT называться отключённой интеграцией или состоявшимся запуском.

#### Scenario: Очередь без запуска
- **WHEN** документное задание сохранено в `ready` с attempt 0 и не имеет `claimed` event
- **THEN** экран показывает «Ожидает запуска», не показывает время начала попытки и сообщает, что зарегистрированных запусков нет

#### Scenario: Чужие задания
- **WHEN** очередь содержит workforce, ERP, outbox или другие job types
- **THEN** они не влияют на сводку, историю, total и пагинацию документной синхронизации

#### Scenario: Нет заданий
- **WHEN** durable jobs документного типа отсутствуют
- **THEN** экран показывает «Нет зарегистрированных запусков», не «Отключено» и не успех

### Requirement: Последняя попытка и последний успех различимы
Система SHALL показывать последнюю фактически начавшуюся попытку по newest `claimed` event и её известный outcome по сохранённым событиям той же пары job/attempt. Последний подтверждённый успех SHALL определяться отдельно по durable `completed` event и валидному allowlisted `result_json.published`; сохранённый неотрицательный integer SHALL называться количеством опубликованных связей «заказ — папка», но не количеством PDF, файлов или объектов.

#### Scenario: Успех A и более поздний сбой B
- **WHEN** попытка A имеет completed event и валидный published count, а более поздняя попытка B имеет retry_scheduled либо dead event
- **THEN** экран показывает B как последнюю попытку с её сохранённым failure code, а A — отдельно как последний подтверждённый успех с его временем и счётчиком

#### Scenario: Новый успех
- **WHEN** newest claimed attempt имеет completed event и валидный published count
- **THEN** эта попытка является и последней попыткой, и последним подтверждённым успехом с одним и тем же durable receipt

#### Scenario: Общая причина producer
- **WHEN** producer сохранил только `BITRIX_ORDER_DOCUMENT_LINKS_SYNC_FAILED`
- **THEN** экран показывает только этот безопасный код и не придумывает transport, scope, credential или иную точную причину

#### Scenario: Повреждённый receipt
- **WHEN** completed row/event не имеет валидного неотрицательного integer `published`
- **THEN** экран не показывает нулевой счётчик и не считает запись подтверждённым успехом; состояние данных обозначается как недоступное/неполное

### Requirement: Состояние очереди не переоценивает lease
Система SHALL различать ready до первого claim, ready после retry, действующий leased, просроченный/противоречивый leased, completed и dead на основании allowlisted текущих полей и durable events. Очищенные lease-поля и сброшенный при новом claim `failure_code` MUST NOT стирать outcome предыдущей попытки из представления.

#### Scenario: Повторная попытка ожидает
- **WHEN** job вернулся в ready после `retry_scheduled`
- **THEN** экран показывает «Ожидает повторной попытки», а причина предыдущей попытки читается из event details

#### Scenario: Текущая и сомнительная обработка
- **WHEN** leased job имеет непросроченный durable lease
- **THEN** экран показывает «Выполняется»; при просроченном или противоречивом lease экран показывает неизвестное состояние и не утверждает, что внешний запрос выполняется

#### Scenario: Terminal failure
- **WHEN** durable event фиксирует dead outcome
- **THEN** экран показывает «Завершено с ошибкой» и только сохранённый allowlisted failure code

### Requirement: История попыток пагинируется на сервере
Экран SHALL показывать по одному элементу на каждый `claimed` event, в стабильном порядке `occurred_at_utc DESC, event_id DESC`, с фиксированным page size и независимым `documentsPage`. SQL MUST фильтровать тип до COUNT/LIMIT и читать outcomes только для bounded page; весь журнал MUST NOT materialize в PHP.

#### Scenario: Несколько попыток одной задачи
- **WHEN** один job был claimed несколько раз
- **THEN** каждая попытка показана отдельной строкой с собственным началом и сохранённым outcome

#### Scenario: Вторая страница
- **WHEN** attempts больше page size и запрошен `documentsPage=2`
- **THEN** сервер возвращает вторую bounded страницу, сохраняет остальные page parameters и pager ведёт на реальный URL

#### Scenario: Невалидная страница
- **WHEN** page parameter отрицательный, нечисловой или чрезмерный
- **THEN** он нормализуется к безопасной существующей странице без unbounded чтения

### Requirement: Экран безопасен, read-only и адаптивен
Существующая canonical проверка active user и `access.administer` SHALL защищать новый блок. GET/HEAD MUST NOT создавать facts, enqueue/retry, вызывать transport или удерживать writer lock. HTML SHALL выводить только allowlisted status, attempt, safe failure code, timestamps и published count, экранировать значения и давать ясную reading order на desktop/narrow с keyboard-operable пагинацией.

#### Scenario: Доступ и отсутствие побочных эффектов
- **WHEN** admin, guest, blocked или denied user запрашивает GET/HEAD
- **THEN** сохраняется действующий access outcome, а jobs/events и соседние таблицы остаются byte-for-byte неизменными и внешние вызовы не выполняются

#### Scenario: Secrets и повреждённые данные
- **WHEN** payload/result/details/exception содержат URL, token, credentials, stack trace, HTML или неожиданные ключи
- **THEN** ни raw JSON, ни secret-bearing values не попадают в HTML/error; неизвестный код отображается только как безопасное общее состояние

#### Scenario: Desktop и narrow
- **WHEN** экран открыт с шириной 1280 и 360 CSS px и история длиннее страницы
- **THEN** главная сводка читается первой, секции визуально разделены, таблицы не ломают viewport, а ссылки пагинации доступны фокусом и меняют нужную страницу
