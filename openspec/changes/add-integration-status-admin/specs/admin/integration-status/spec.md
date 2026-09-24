## Purpose

Даёт системному администратору безопасное read-only представление сохранённого состояния подключённых интеграций и очереди без доступа к секретам или запуска операций.

## ADDED Requirements

### Requirement: Серверно защищённый read-only экран
Система SHALL обслуживать `GET` и `HEAD /pilot/admin/integrations` только для активного аутентифицированного пользователя с `access.administer`, проверяя capability через canonical access. Гость SHALL возвращаться к входу с безопасным return path; активный пользователь без capability и blocked user SHALL не получать содержимое. Иные методы MUST не выполнять действий.

#### Scenario: Допущенный администратор
- **WHEN** активный пользователь с `access.administer` открывает экран
- **THEN** система возвращает страницу «Состояние интеграций» и показывает административную ссылку

#### Scenario: Недопущенные actors
- **WHEN** экран запрашивает гость, blocked user или активный пользователь без capability
- **THEN** система возвращает действующий authentication/access-denial outcome без защищённых данных

#### Scenario: Без побочных эффектов
- **WHEN** любой actor выполняет GET, HEAD или неподдерживаемый метод
- **THEN** система не создаёт и не изменяет integration facts, jobs, outbox, audit/history и не вызывает внешние transports, retry, cron или worker

### Requirement: Раздельная достоверная свежесть источников
Для Bitrix workforce и ERP equipment facts экран SHALL раздельно показывать последнюю сохранённую попытку и последний сохранённый успех, outcome последней попытки, доступные счётчики и безопасный allowlisted diagnostic code. Отсутствие run SHALL отображаться как «Не запускалась», а не успех или disabled. Ошибка чтения SHALL отличаться от успешного пустого результата.

#### Scenario: Сбой после успеха
- **WHEN** последняя попытка источника failed, а более ранний run completed
- **THEN** экран показывает время и причину последней попытки отдельно от времени последнего успеха

#### Scenario: Успешный пустой результат
- **WHEN** сохранён completed run с нулевыми счётчиками
- **THEN** экран показывает успешный outcome и нули, не «Нет данных»

#### Scenario: Источник не запускался или недоступен
- **WHEN** run отсутствует либо durable projection нельзя прочитать
- **THEN** экран показывает соответственно «Не запускалась» либо безопасное состояние «Данные недоступны» без предположения об успехе

### Requirement: Bounded diagnostics и failed jobs
Экран SHALL показывать только реально сохранённые workforce `missing_from_delivery`, ERP `OBJECT_NOT_FOUND`/`OBJECT_AMBIGUOUS` diagnostics и dead jobs. Каждый список SHALL иметь независимую серверную пагинацию с фиксированным максимальным размером страницы; reader MUST выполнять bounded SQL и не materialize весь журнал в PHP.

#### Scenario: Независимая пагинация
- **WHEN** любой список длиннее одной страницы и администратор меняет его page parameter
- **THEN** меняется только соответствующий bounded список, сохраняются остальные page parameters и показываются total/page bounds

#### Scenario: Несуществующая страница
- **WHEN** page parameter невалиден или выходит за допустимый диапазон
- **THEN** система нормализует его к безопасной странице без unbounded query или ошибки с внутренними данными

#### Scenario: Durable gap
- **WHEN** конкретный unmatched-detail или показатель источником не сохраняется
- **THEN** экран явно показывает «Не регистрируется»/«Нет данных», не синтезирует detail и не объявляет весь issue #30 завершённым

### Requirement: Безопасное представление
HTML SHALL использовать существующие shlz-ui card/table/empty/error/pagination contracts, быть понятным на desktop и narrow viewport и экранировать сохранённые значения. Экран MUST не выводить secrets, webhook URLs, DSN, tokens, raw payload, stack trace или произвольный exception message.

#### Scenario: Вредоносное сохранённое значение
- **WHEN** безопасно отображаемое текстовое поле содержит HTML/script markup
- **THEN** браузер показывает его как текст и не исполняет markup

#### Scenario: Ошибка reader
- **WHEN** чтение durable projection выбрасывает исключение с чувствительным текстом
- **THEN** ответ содержит только стабильное безопасное сообщение и correlation context согласно действующему error contract
