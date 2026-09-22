## Purpose

Обеспечить неизменяемую и безопасную привязку каждого принятого обращения к исходному пользовательскому экрану и реально работающей серверной сборке без тяжёлых вычислений в HTTP.

## ADDED Requirements

### Requirement: Безопасный исходный экран сохраняется по актуальному router
Система SHALL нормализовать переданный текущей feedback-ссылкой path на единственном application seam обращения и SHALL сохранять только разрешённый user-facing GET-контекст без query и fragment. Разрешены простые экраны `/pilot/objects`, `/pilot/installers`, `/pilot/construction-control`, `/pilot/calendar`, `/pilot/dashboard`, `/pilot/admin/users`, `/pilot/users`, `/pilot/admin/roles`, `/pilot/feedback`, `/pilot/admin/feedback`, `/pilot/otiz`, `/pilot/otiz/objects`, `/pilot/otiz/payments`, `/pilot/otiz/history`; объектные экраны `/pilot/objects/<objectId>`, `/checklist`, `/assignment-order/prepare`, `/assignment-order/selection`, `/execution`, `/deadline-certificates`; стройконтроль `/pilot/construction-control/objects/<objectId>/checklist`; формы и история оригинала `/pilot/objects/<objectId>/assignment-orders/<orderId>/originals/submit|history`; сохранённый расчёт `/pilot/otiz/snapshots/<snapshotId>`. Не перечисленный контекст SHALL давать fallback `/pilot/objects`.

#### Scenario: Календарь и дашборд
- **WHEN** пользователь открывает feedback-link с `/pilot/calendar` или `/pilot/dashboard`, отправляет форму и получает подтверждение
- **THEN** форма, запись, подтверждение, возврат и операторский список используют тот же разрешённый path без query/fragment и с `object_id=null`

#### Scenario: Действующие экраны ОТиЗ
- **WHEN** обращение отправлено из `/pilot/otiz`, `/pilot/otiz/objects`, `/pilot/otiz/payments`, `/pilot/otiz/history` или `/pilot/otiz/snapshots/731`
- **THEN** система сохраняет соответствующий path и безопасно возвращает на него, а `731` не сохраняется как object ID

#### Scenario: Объектный контекст
- **WHEN** обращение отправлено из карточки `/pilot/objects/1450`, стройконтроля `/pilot/construction-control/objects/1450/checklist` или формы оригинала `/pilot/objects/1450/assignment-orders/7/originals/submit`
- **THEN** система сохраняет разрешённый path и `object_id=1450`

#### Scenario: Другие доступные точки отправки
- **WHEN** обращение отправлено из актуального пользовательского экрана подготовки состава или справок `/pilot/objects/1450/assignment-order/prepare` либо `/pilot/objects/1450/deadline-certificates`
- **THEN** система сохраняет точный разрешённый path и `object_id=1450`

#### Scenario: Неподдерживаемый или чувствительный URL
- **WHEN** клиент передаёт внешний/сетевой URL, backslash, управляющие символы, percent-encoded path, oversized ID, произвольный `/pilot/*`, login/activation, sync-context, photo/original download, Excel export, mutating endpoint или URL с query/fragment
- **THEN** система не сохраняет query/fragment/секреты, не создаёт open redirect и использует только разрешённый path без параметров либо fallback `/pilot/objects` с `object_id=null`

### Requirement: Обращение фиксирует server-owned build identity
При первом успешном принятии нового обращения система MUST сохранить полный server-owned идентификатор реально работающей сборки длиной до 80 допустимых ASCII-символов. HTTP-контур SHALL читать только заранее сформированную immutable identity и MUST NOT вычислять source identity, обходить исходники, выполнять Git/Docker/API команды или внешние запросы. Клиент MUST NOT задавать или подменять build identity.

#### Scenario: Две контролируемые сборки
- **WHEN** новые обращения принимаются последовательно в сборках с identity `aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa` и `bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb`
- **THEN** каждое хранит полный соответствующий 64-символьный идентификатор, а оператор видит именно сохранённое значение

#### Scenario: Identity недоступна или недостоверна
- **WHEN** настроенный immutable build-файл отсутствует, недоступен, изменяем, является ссылкой или содержит недопустимое значение
- **THEN** новое обращение сохраняется с явным `unknown`, остальные экраны остаются доступны, source hashing не запускается, а строгие readiness-проверки не ослабляются

#### Scenario: Клиент пытается подменить версию
- **WHEN** клиент добавляет произвольное поле версии к форме или повторяет POST после смены сборки
- **THEN** поле игнорируется и build identity определяется только серверной конфигурацией при первом принятии

### Requirement: Replay сохраняет исторические факты
Система SHALL фиксировать context и build identity только при первой вставке. Fingerprint пользовательской команды SHALL зависеть от нормализованных description, page path и object ID, но MUST NOT зависеть от текущей build identity. Точный actor-scoped replay SHALL вернуть исходный id без новой записи или UPDATE; то же requestId с другим пользовательским содержанием SHALL вернуть conflict без изменения истории, включая конкурентные запросы.

#### Scenario: Повтор после потери ответа и обновления
- **WHEN** принято обращение сборки A, приложение обновлено до B и пользователь повторяет тот же requestId, description и path
- **THEN** возвращается исходный id, остаётся одна запись с build A и исходным context

#### Scenario: Конфликт после обновления
- **WHEN** после обновления повторяется тот же actor/requestId с другим description или нормализованным context
- **THEN** система возвращает conflict, не создаёт дубликат и не переписывает принятую запись

### Requirement: Оператор видит сохранённые context и build
Существующий защищённый административный список SHALL показывать для каждого обращения безопасный сохранённый исходный path и полный сохранённый build identifier с HTML-экранированием; он MUST NOT вычислять текущую версию при чтении. Доступ SHALL оставаться ограничен действующим полномочием `access.administer`.

#### Scenario: Просмотр старого обращения после обновления
- **WHEN** уполномоченный оператор открывает список после смены сборки A на B
- **THEN** обращение A показывает свой прежний path и build A, новое обращение B показывает build B, а не текущую версию вместо исторической

#### Scenario: Неуполномоченный просмотр
- **WHEN** обычный, заблокированный или неизвестный пользователь пытается прочитать список
- **THEN** существующий отказ доступа сохраняется и никакие сведения обращения не раскрываются
