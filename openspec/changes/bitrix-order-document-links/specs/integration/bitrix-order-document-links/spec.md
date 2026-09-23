## Purpose

Минимальный контракт ссылок технической документации Битрикс для карточки объекта.

## ADDED Requirements

### Requirement: Read-only Bitrix delivery
Система SHALL постранично читать direct child folders настроенного root через `disk.folder.getchildren`, переиспользовать проверенные текущие URL для неизменившихся exact folder ID/name и получать остальные URL через `disk.folder.getExternalLink` пакетами не более 50 команд. Delivery SHALL принимать не менее 25 000 direct children и fail closed при configuration, transport, API, pagination, schema или limit error; partial rows MUST NOT публиковаться. URL SHALL быть HTTPS, без credentials и принадлежать настроенному Bitrix origin либо exact official external-link host `bitrix24public.com`. FMonitor MUST NOT загружать, проксировать или хранить содержимое документов.

#### Scenario: Полная выдача
- **WHEN** все страницы и ссылки успешно проверены
- **THEN** delivery возвращает полный validated list

#### Scenario: Ошибка после частичного чтения
- **WHEN** любая страница или ссылка не прошла проверку
- **THEN** delivery возвращает failure без partial list

### Requirement: Exact mapping по номеру заказа
Ключом SHALL быть строковый `fm_maintable.zavnumber`; `regnumber` MUST NOT использоваться как ключ или fallback. Обычное имя папки SHALL сохраняться byte-exact. Форма `A.F-B.F` с одинаковой дробной частью SHALL раскрываться включительно; иное дефисное имя SHALL отклонять refresh как неоднозначное.

#### Scenario: Ведущий ноль и другой regnumber
- **WHEN** `zavnumber="0012.03"`, `regnumber="77-000123"` и folder name `0012.03`
- **THEN** ссылка сопоставляется только с `0012.03`

#### Scenario: Legacy range
- **WHEN** folder name равен `1.3-3.3`
- **THEN** URL сопоставляется `1.3`, `2.3` и `3.3`

### Requirement: Безопасная актуализация текущих ссылок
Единственный public application owner SHALL в одной transaction заменить current owned projection только полным validated list. Duplicate exact rows SHALL схлопываться. Complete empty list SHALL очистить projection. Delivery/application failure MUST оставить предыдущую projection без изменений.

#### Scenario: Успешная замена
- **WHEN** следующий complete list добавляет, меняет или удаляет ссылки
- **THEN** после commit читается ровно новый набор

#### Scenario: Неуспешный refresh
- **WHEN** refresh завершается failure
- **THEN** ранее опубликованный набор остаётся доступен

### Requirement: Read owner и карточка
Managed mirror/import SHALL переносить nullable string `zavnumber`. Авторизованный read owner SHALL выбирать все distinct ссылки exact-matching order number. Несколько объектов одного заказа SHALL получать одинаковый список; иные заказы MUST NOT смешиваться. Секция SHALL находиться в существующей карточке под `objects.read`; construction-control queue SHALL показывать count без URL, а авторизованный checklist SHALL давать touch-friendly ссылки в рабочем контексте инженера. Все поверхности SHALL экранировать source name и различать missing order, empty и unavailable без отказа основного экрана.

#### Scenario: Разрешённая карточка
- **WHEN** пользователь с `objects.read` открывает объект с ссылками
- **THEN** карточка показывает escaped names и HTTPS links

#### Scenario: Запрещённая карточка
- **WHEN** existing object-card authorization отказывает
- **THEN** сведения о ссылках не раскрываются

### Requirement: Явный console trigger
Yii2 console command SHALL выполнить один bounded delivery и передать complete list public application owner. Command MUST NOT писать projection напрямую и MUST возвращать safe result без credentials/raw upstream body.

#### Scenario: Ручное обновление
- **WHEN** оператор запускает настроенную command
- **THEN** command возвращает published либо safe failure, а изменение данных принадлежит application owner

### Requirement: Hourly native schedule
Existing native Jobs scheduler SHALL ставить один sync job на каждый часовой slot в timezone `Europe/Moscow`. Job SHALL вызывать ту же bounded delivery/application composition, что manual console trigger, и MUST NOT писать links projection напрямую. Повтор scheduler для того же slot MUST NOT создавать duplicate job; после паузы SHALL ставиться только текущий due slot без накопления параллельного backlog.

#### Scenario: Очередной час
- **WHEN** scheduler обрабатывает новый московский часовой slot
- **THEN** в existing Jobs queue появляется ровно один Bitrix document-links sync job

#### Scenario: Повтор того же slot
- **WHEN** scheduler повторно обрабатывает тот же час
- **THEN** второй job не создаётся
