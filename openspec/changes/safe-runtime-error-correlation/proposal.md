## Why

Выбранные HTTP 5xx сейчас возвращают безопасный ответ, но не дают оператору доверенного идентификатора, по которому можно однозначно найти причину в штатном журнале. Срез #173 сокращает ручное расследование bootstrap- и controller-сбоев без раскрытия исключений и без создания отдельной observability-системы.

## What Changes

- Ввести server-generated error ID для выбранных 5xx и вернуть его в безопасном HTTP-заголовке без изменения существующих JSON/HTML envelopes, статусов, защитных заголовков и `Retry-After`.
- Записывать ровно одну компактную структурированную запись на выбранный failure: ID, UTC-время, ограниченный component/route template, статус, доказуемую категорию причины и доверенную build version либо `unknown`.
- Подключить общий механизм только к `public/runtime.php`, `SafeErrorHandler`, `ExecutionController`, `OriginalController` и `ChecklistController`, переиспользовав штатный logger/safe-log подход и ограниченный bootstrap fallback без Yii/БД.
- Категоризировать только по известному типу/коду или явному месту сбоя; неизвестное сохранять как `unexpected`, не анализируя произвольный exception message.
- Сделать отказ журналирования fail-open относительно исходного безопасного ответа: без повторения команды, новых domain writes и рекурсивного логирования.
- Добавить container-focused fault-injection/HTTP/privacy проверки и адресную регистрацию существующего verification-профиля; алгоритмы harness, FAST/CI admission и полный локальный suite не менять.

## Capabilities

### New Capabilities

- `runtime/safe-error-correlation`: безопасная корреляция выбранных runtime 5xx с одной компактной штатной журнальной записью.

### Modified Capabilities

Нет.

## Impact

- Актор: клиент получает непрозрачный server-owned ID; оператор ищет по нему запись в штатном журнале.
- Source oracle: issue #173 и существующее поведение `public/runtime.php`, `SafeErrorHandler` и трёх native controller boundaries.
- Public seams: реальные HTTP-ответы canonical runtime и фактически записанный runtime log.
- Затрагиваются только перечисленные runtime/controller файлы, минимальный общий safe-log модуль, профильные тесты/verification inventory и краткий пример поиска.
- Schema, доменные операции, jobs, UI/CSS, RBAC/CSRF, внешние сервисы, release system #172, массовая обработка `catch` и debug mode не входят.
- Release value: выбранные неразличимые 5xx становятся диагностируемыми по безопасному ID при сохранении текущего клиентского и предметного поведения.
