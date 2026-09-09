# YII2-AUTH-001 — Yii login, session, logout и справочник ролей

- Статус: `DRAFT — intended RED и independent Gate 3 pending`
- Дата: `2026-09-09`
- Актор: локальный пользователь FMonitor
- Публичный seam: HTTP `GET|POST /pilot/login`, `POST /pilot/logout`,
  `GET /pilot/admin/roles` изолированного Yii runtime
- Oracle: canonical IdentityAccess schema/role catalog, current LocalAuth и approved
  local authorization contracts

## Простыми словами

Пользователь входит через привычные два шага — email, затем пароль — и открывает
справочник ролей только со своим точным правом. Yii владеет cookie, session, CSRF,
login и logout. Старый cookie не переносится: владелец разрешил всем войти заново.
Приглашения и изменение доступа будут отдельным следующим срезом.

## 1. Preconditions и inputs

Canonical database schema заранее подготовлена. Login принимает только normalized
корпоративный `@shlz.ru` email и password длиной не более 200 bytes. Forms принимают
только URL-encoded POST с CSRF текущей session. Protected route не принимает actor,
role или permission из query/body/header. Permission route задан byte-exact как
`access.administer`.

Новый cookie называется `fm2yii` в normal contour и `fm2yii_<decimal-port>` в
isolated local contour; Path `/pilot`, HttpOnly, SameSite Strict, Secure только при
trusted HTTPS. Legacy `fm2auth*` никогда не является identity input.

## 2. Login

`GET /pilot/login` возвращает email form. Valid email POST возвращает password form
с тем же normalized email и новым/текущим CSRF. Password POST допускает только одну
canonical identity с `status=1`, `activation_state=active`, credential hash и
правильным паролем. Existing Argon2id hash проверяется и не изменяется.

Каждый неуспешный password admission attempt записывает существующий auth-attempt
fact с normalized email; успешный вход очищает накопленные попытки этого email.
Invalid CSRF не является login attempt.
Действующий rate limit проверяется до password verification и не раскрывается.
Ошибки user/credential/password/rate limit нейтральны; blocked status может сохранить
утверждённое текущее пользовательское сообщение, но response не раскрывает hash,
роль, permission, SQL или внутреннее состояние.

Success регенерирует session ID и возвращает `303` на сохранённый safe local
`/pilot/*` return URL, иначе `/pilot/objects`. External/unknown/login/logout/assets
return targets заменяются default. Repeat successful login не создаёт access/domain
history и не меняет credential.

## 3. Identity/session lifecycle

Authenticated identity возобновляется только пока canonical user остаётся unique,
enabled и active, а stored auth key соответствует current `session_version`.
Committed block/disable/invited transition либо version change закрывает следующий
request. Session хранится в заранее подготовленном persistent runtime path и
переживает restart web process в пределах lifetime.

Session open, regeneration или close/write failure до response MUST вернуть generic
non-cacheable `503`, отменить success body/redirect и не выдать authenticated cookie.
Response/log не раскрывает credential, email, session ID/payload, private path, SQL
или exception. Это внешняя надежность, без требований к старому filesystem algorithm.

## 4. Authorization и roles read

Anonymous `GET /pilot/admin/roles` сохраняет safe return URL и возвращает
`303 Location: /pilot/login`. Authenticated identity получает `200` current roles
HTML только когда active assigned role имеет exact `access.administer`. Inactive role,
missing/near-match permission, implicit administrator, wildcard/hierarchy и legacy
fallback дают generic `403`. Canonical read/schema failure даёт safe `503`.

Authorization читает current committed snapshot на каждом request. Revoke после
success закрывает следующий request. Denial/unavailable не запускают protected
reader; read не меняет RBAC, audit, session version или domain facts.

## 5. Logout

Только authenticated `POST /pilot/logout` с valid CSRF завершает session, удаляет
cookie и возвращает `303 Location: /pilot/login`. GET logout возвращает `405`; POST
с invalid CSRF возвращает `400`. Оба отказа сохраняют действующую session. После
success прежний cookie не открывает protected route.

## 6. Acceptance examples

1. Active user `yii.auth.admin@shlz.ru`, existing Argon2id hash, assigned active role
   with exact `access.administer`: email step 200, password step 303 with rotated
   `fm2yii*`, roles GET 200 and expected catalog marker; hash byte-identical and
   accumulated failed-attempt rows cleared.
2. Та же identity invited, blocked или status0: password admission fails neutrally,
   attempt is failed and protected route remains unavailable.
3. Active identity with inactive role or `Access.Administer`: login may succeed,
   roles GET is 403 and reader has no observable output/state change.
4. Ten current failed attempts at existing threshold: correct password remains
   denied, next failed attempt recorded, authenticated cookie absent.
5. Valid session survives process restart; `session_version+1` then makes its next
   roles GET redirect to login.
6. Valid logout CSRF produces 303 and invalidates cookie; GET/invalid-CSRF logout
   is rejected and original cookie still reaches roles.
7. Direct login without a saved protected target redirects to `/pilot/objects`;
   legacy `fm2auth*` alone reaches login, never roles.

## 7. Non-goals и Gate 1

Invitation/activation/reissue, user/status/role writes, other routes, working-stand
cutover and legacy payload conversion are outside this slice. No production/test
fixture preservation is claimed. Gate 1/3 require independent review of observable
HTTP assertions, independently seeded expected facts, intended RED for missing Yii
auth behavior, deterministic isolation and absence of filesystem/private-method
assertions.
