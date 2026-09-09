## Purpose

Определяет первый защищённый пользовательский маршрут Yii2: локальный вход,
устойчивая session, exact RBAC admission справочника ролей и безопасный выход.

## ADDED Requirements

### Requirement: Локальный пользователь входит через двухшаговую форму
Система SHALL предоставлять `GET|POST /pilot/login`. Первый шаг SHALL принимать
корпоративный email, второй — пароль для найденной canonical identity. Оба POST
MUST требовать действующий request CSRF token. Успешный вход MUST принимать только
уникального пользователя с `status=1`, `activation_state=active`, существующим
password hash и правильным паролем, очистить накопленные попытки, регенерировать
session ID и вернуть `303` на ранее сохранённый безопасный `/pilot/*` URL либо
`/pilot/objects`. Password hash MUST оставаться неизменным.

#### Scenario: Успешный вход с существующим Argon2id hash
- **WHEN** active пользователь проходит email step и password step с правильным паролем и действующим CSRF
- **THEN** система очищает накопленные попытки, выдаёт новый session cookie и возвращает `303` на безопасный protected URL
- **AND** новый cookie открывает authenticated session, а сохранённый password hash не изменён

#### Scenario: Старый cookie не аутентифицирует
- **WHEN** клиент приходит только с legacy `fm2auth` cookie
- **THEN** система считает его гостем и требует вход с новым session cookie namespace

#### Scenario: Учётная запись не допущена
- **WHEN** email относится к invited, blocked либо `status=0` пользователю, credential отсутствует или пароль неверен
- **THEN** система не создаёт authenticated identity и показывает нейтральную ошибку без раскрытия состояния пользователя
- **AND** попытка записана как неуспешная

#### Scenario: Rate limit закрывает проверку пароля
- **WHEN** для normalized email уже достигнут действующий предел неуспешных попыток
- **THEN** следующий login POST остаётся неуспешным, записывает попытку и не создаёт authenticated identity

#### Scenario: Login CSRF отклонён
- **WHEN** email либо password POST не содержит действующего CSRF token текущей session
- **THEN** response имеет status `400` и не записывает login attempt, identity или access/domain fact

### Requirement: Session сохраняет identity безопасно и переживает restart
Новый cookie SHALL иметь отдельное от legacy имя, Path `/pilot`, HttpOnly,
SameSite Strict и Secure только в HTTPS contour. Session SHALL храниться в
operator-prepared persistent path и сохранять authenticated identity после
перезапуска web process до logout, expiry, блокировки пользователя либо изменения
его `session_version`. Session open, regeneration или closing persistence failure
MUST отменить success/redirect и вернуть generic non-cacheable `503` без cookie,
credential, private path, payload, SQL или exception.

#### Scenario: Authenticated session переживает process restart
- **WHEN** клиент успешно вошёл, web process перезапущен с тем же persistent session path и клиент повторяет protected GET с новым cookie
- **THEN** та же active identity продолжает request без повторного входа

#### Scenario: Identity стала недействительной
- **WHEN** authenticated пользователь committed как blocked/disabled/invited либо его `session_version` изменился
- **THEN** следующий request не использует прежнюю identity и требует вход

#### Scenario: Session persistence недоступна
- **WHEN** session нельзя открыть, регенерировать или надёжно закрыть перед отправкой response
- **THEN** система возвращает generic non-cacheable `503` без success redirect и без нового authenticated cookie

### Requirement: Справочник ролей требует exact permission
`GET /pilot/admin/roles` SHALL требовать authenticated active identity и exact
permission `access.administer` через assigned active role. Permission MUST читаться
из текущего committed canonical snapshot без legacy fallback, wildcard, prefix,
case folding, implicit administrator grant или cross-request grant cache.

#### Scenario: Разрешённый пользователь читает роли
- **WHEN** authenticated active пользователь имеет назначенную active роль с exact `access.administer`
- **THEN** `GET /pilot/admin/roles` возвращает `200` и current role catalog HTML
- **AND** read не изменяет user, role, audit, session-version или domain facts

#### Scenario: Гость перенаправлен на login
- **WHEN** anonymous client вызывает `GET /pilot/admin/roles`
- **THEN** response сохраняет этот безопасный return URL и возвращает `303 Location: /pilot/login`

#### Scenario: Permission отсутствует или отозван
- **WHEN** authenticated identity не имеет exact permission, роль неактивна либо permission committed отозван после предыдущего success
- **THEN** текущий request получает generic `403`, protected reader не выполняется и persisted facts не меняются

#### Scenario: Authorization read недоступен
- **WHEN** canonical authorization facts нельзя прочитать однозначно и согласованно
- **THEN** response имеет generic non-cacheable `503`, protected reader не выполняется и private facts не раскрываются

### Requirement: Выход требует POST и CSRF
`POST /pilot/logout` SHALL требовать authenticated identity и действующий request
CSRF token, завершить session и вернуть `303 Location: /pilot/login`. `GET logout`
и POST с неверным CSRF MUST быть отклонены без завершения действующей session.

#### Scenario: Успешный выход
- **WHEN** authenticated пользователь отправляет POST logout с действующим CSRF
- **THEN** session уничтожена, cookie удалён и response возвращает `303 Location: /pilot/login`
- **AND** повторный protected GET требует новый вход

#### Scenario: Неверный метод или CSRF
- **WHEN** клиент вызывает GET logout либо POST logout с отсутствующим/неверным CSRF
- **THEN** система отклоняет request и не завершает действующую authenticated session
