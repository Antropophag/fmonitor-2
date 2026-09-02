## Purpose

Определяет единый fail-closed authorization contract для local-authenticated
pilot actor и первый vertical consumer `GET /pilot/objects`; остальные
защищённые routes подключаются отдельными slices через тот же seam.

## ADDED Requirements

### Requirement: Local RBAC является единственным authority для первого migrated route
Система SHALL принимать решение о доступе actor к `GET /pilot/objects` только
по canonical local identity/RBAC facts: exact positive user ID текущей
authenticated session, активная local user row, `activation_state=active`,
активная назначенная local role и exact `objects.read` permission этой роли.
Legacy users, legacy roles/rights, `REMOTE_USER`, email/display name, role
code/name и один лишь факт authentication SHALL NOT разрешать доступ или служить
fallback. Следующие route migration slices MUST переиспользовать этот seam, но
их integration не входит в Done этого slice.

#### Scenario: Полный exact grant разрешает доступ
- **WHEN** authenticated actor `7301` ссылается на активного local user в состоянии активации `active`, пользователь назначен на активную роль `701`, роль имеет exact permission `objects.read`, а маршрут требует `objects.read`
- **THEN** public authorization seam возвращает разрешённого actor `7301`

#### Scenario: Legacy grant не заменяет local grant
- **WHEN** authenticated actor существует или имеет право в legacy identity/rights, но не имеет полного exact local RBAC grant
- **THEN** public authorization seam отклоняет доступ как `ACCESS_DENIED` без обращения к legacy authority для разрешения

#### Scenario: Имя не является полномочием
- **WHEN** display name, email либо role name actor совпадает со значением привилегированного пользователя или роли, но exact local permission отсутствует
- **THEN** public authorization seam отклоняет доступ как `ACCESS_DENIED`

### Requirement: Все звенья local grant обязательны
Система MUST fail closed, если отсутствует или неактивно хотя бы одно звено exact local grant. Активная учётная запись требует `status=1` и `activation_state=active`; назначение должно связывать этого пользователя с ролью; роль требует `status=1`; permission должен byte-exact совпадать с permission, запрошенным route mapping.

#### Scenario: Неактивный пользователь отклонён
- **WHEN** exact user существует, но `status` не равен `1`
- **THEN** public authorization seam возвращает `ACCESS_DENIED` и не запускает защищённое действие

#### Scenario: Активация не завершена
- **WHEN** exact user активен по `status`, но `activation_state` равен `invited`, `blocked` либо любому значению кроме `active`
- **THEN** public authorization seam возвращает `ACCESS_DENIED` и не запускает защищённое действие

#### Scenario: Нет активной назначенной роли
- **WHEN** exact user активен и активирован, но не имеет назначенной роли либо назначенная роль имеет `status` не равный `1`
- **THEN** public authorization seam возвращает `ACCESS_DENIED`

#### Scenario: Нет exact permission
- **WHEN** активная назначенная роль имеет `objects.read`, а маршрут требует `assignment_order.prepare`
- **THEN** public authorization seam возвращает `ACCESS_DENIED`; `objects.read` не наследует и не подразумевает `assignment_order.prepare`

#### Scenario: Permission объединяется по двум активным назначенным ролям
- **WHEN** первая active assigned role actor не имеет `objects.read`, а вторая
  active assigned role имеет exact `objects.read`
- **THEN** seam разрешает actor; после deactivation второй роли тот же новый
  invocation возвращает `ACCESS_DENIED`

#### Scenario: Permission сравнивается точно
- **WHEN** роль имеет near-match `Objects.Read`, `objects.read ` либо `objects.*`, а маршрут требует `objects.read`
- **THEN** public authorization seam возвращает `ACCESS_DENIED`

### Requirement: Первый route сохраняет собственную capability mapping
`GET /pilot/objects` SHALL передавать public authorization seam заранее
определённый exact `objects.read`. Authorization seam MUST NOT выводить
permission из URL, request payload, display name, legacy role или другого
user-controlled значения. Успешный результат разрешает только текущий route
invocation и не создаёт универсальной авторизации для иных routes.

#### Scenario: Один permission не открывает соседний маршрут
- **WHEN** actor имеет только `objects.read`, list/card route требует `objects.read`, а prepare route требует `assignment_order.prepare`
- **THEN** list/card authorization разрешена, prepare authorization отклонена как `ACCESS_DENIED`

#### Scenario: Клиент не выбирает требуемое право
- **WHEN** request передаёт параметр или header со значением разрешённого actor permission, отличным от permission route mapping
- **THEN** система игнорирует это значение и принимает решение только по exact permission route mapping

### Requirement: Решение принимается fail closed до защищённого handler-а
При отсутствии authenticated local actor seam SHALL вернуть `AUTHENTICATION_REQUIRED`. При корректно authenticated actor без полного grant seam SHALL вернуть `ACCESS_DENIED`. При недоступной, неоднозначной или несовместимой canonical local RBAC конфигурации seam SHALL вернуть `AUTHORIZATION_UNAVAILABLE`. Во всех отказах защищённый handler и его business/read persistence MUST NOT выполняться; HTTP adapter SHALL отобразить результаты соответственно в `401`, `403` и `503` без раскрытия внутренних причин.

#### Scenario: Нет authenticated actor
- **WHEN** session не содержит валидный положительный local user ID
- **THEN** seam возвращает `AUTHENTICATION_REQUIRED`, не читает route-owned business data и не запускает handler

#### Scenario: Конфигурация authorization недоступна
- **WHEN** canonical local RBAC read завершается DB/schema error либо даёт неоднозначный identity result
- **THEN** seam возвращает `AUTHORIZATION_UNAVAILABLE`, не использует legacy fallback и не запускает handler

#### Scenario: Trusted permission mapping невалиден
- **WHEN** application передаёт blank, malformed или unknown permission literal
- **THEN** seam возвращает `AUTHORIZATION_UNAVAILABLE` с safe internal category
  `AUTHORIZATION_CONFIGURATION_INVALID` и не запускает handler

#### Scenario: HTTP mapping не раскрывает причину
- **WHEN** seam возвращает один из трёх отказов
- **THEN** HTTP adapter выдаёт existing generic `401`, `403` либо `503`; для
  unavailable он включает opaque 12-hex correlation ID
- **AND** internal log содержит тот же ID и только stable safe category
  `AUTHORIZATION_CONFIGURATION_INVALID`, `AUTHORIZATION_SCHEMA_INVALID` либо
  `AUTHORIZATION_READ_FAILED`, без RBAC facts, SQL, credentials или schema names

### Requirement: Authorization read не изменяет историю и учитывает актуальный снимок
Проверка SHALL быть read-only: она MUST NOT изменять user, activation, role, permission, session version, login metadata, audit или domain facts. Повторная проверка одного неизменившегося committed snapshot MUST вернуть тот же результат. Каждая новая route invocation MUST проверять актуальный committed local RBAC snapshot; отзыв permission, деактивация роли/пользователя или блокировка активации MUST закрыть последующий доступ без legacy fallback.

#### Scenario: Повтор на неизменившемся снимке детерминирован
- **WHEN** один actor и exact permission проверяются дважды без committed изменения local RBAC facts
- **THEN** оба результата совпадают и ни одна таблица не изменена

#### Scenario: Отзыв действует на следующую проверку
- **WHEN** первая проверка разрешена, затем permission отозван committed изменением, после чего начинается новый route invocation
- **THEN** новая проверка возвращает `ACCESS_DENIED` и предыдущий успех не кэшируется как grant
