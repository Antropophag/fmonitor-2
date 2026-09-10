# YII2-USER-ACCESS-001 — управление доступом через Yii2

## Простыми словами

Администратор приглашает пользователя, выдаёт новую ссылку, назначает роли и
блокирует/восстанавливает доступ в существующем интерфейсе. Получатель активирует
учётную запись и входит через Yii. Все изменения доступа принадлежат одному
IdentityAccess application owner, а не контроллеру. Рабочий pilot не переключается.

## Scope and public seams

- `YiiUserAccess` в IdentityAccess, одна Yii DB connection на атомарную операцию:
  `directory(actorId)`, `invite(actorId,email,fullName)`, `reissue(actorId,userId)`,
  `invitation(token)`, `activate(token,password,confirmation)`,
  `changeRole(actorId,userId,roleId,action)`, `changeStatus(actorId,userId,action)`.
- Real Yii HTTP: GET/HEAD `/pilot/admin/users` и `/pilot/users`; POST
  `/pilot/admin/users/invite`, `/{id}/invitation`, `/{id}/roles`,
  `/{id}/roles/{roleId}`, `/{id}/status`; GET/POST `/pilot/activate`.
- `directory` возвращает users/roles с нормализованными данными, при отсутствии
  полномочия бросает DomainException `ACCESS_DENIED`; секреты/hash не возвращает.
- Прикладные результаты команд: `issued` (userId/token), `activated`, `changed`,
  `unchanged`, `invalid`, `access_denied`; activation password validation может
  вернуть `invalid_password` с понятной причиной. `invitation` возвращает email
  только для действующей ссылки, иначе null. Infrastructure failure throws и HTTP503.
- Источники поведения: current UserDirectoryView + RapidPilotUserAccessView,
  MariaDbPilotUserDirectory, MariaDbReissueUserInvitation, MariaDbUserStatusApplication,
  RapidPilotLocalAuth::activationPage; роли/права из canonical local IdentityAccess.
  Наследуются YII2-AUTH-001, LOCAL-RBAC-AUTH-CONTRACT-001, IDENTITY-ACCESS-SCHEMA-001.

## Acceptance matrix

| ID | Команда / условия | Результат и persisted facts / return |
| --- | --- | --- |
| read | active actor с exact `access.administer` | 200 HTML: имя/email/телефон, active/invited/blocked, роли и итоговые permissions только активных ролей, invitation-valid flag; поиск и фильтры статуса/роли, формы всех перечисленных команд, ссылки users↔roles и POST logout; данные HTML escaped. Read не меняет identity/audit. HEAD пустой с теми же headers. |
| admission | guest GET, active без права, inactive role, near-match/revoked permission, spoofed actor fields/headers | guest303 login с safe return; остальные403 без protected data/facts; actor только Yii identity. Владелец заново проверяет active actor/role/exact permission внутри транзакции для каждой команды. DB/schema failure503 без SQL/secrets/cookie/redirect. |
| request | command GET/HEAD, missing/invalid/stale CSRF, malformed scalar fields | 405 для wrong method, 400 для CSRF/type rejection, no identity/audit facts. Yii Request/Session владеют CSRF; успешная administrative mutation ротирует session-bound CSRF, повтор той же формы400 без повторной записи. Это сохраняет одноразовость прежних административных форм без старого session/CSRF framework. Existing login/logout/OTIZ CSRF flows остаются работоспособны. |
| invite | normalized corporate email, nonblank fullName≤300 chars, отсутствующий email, active default `user` role | одна invited/status1 identity, credential с null hash, одна default-role assignment с actor/time, один invitation с hash 32-byte CSPRNG token (base64url43), expiry24h и creator/time. HTTP303 users; ссылка показывается один раз через session flash и не появляется при следующем чтении. Raw token не хранится в DB/log. |
| invite-reject | внешний/невалидный email, пустое/длинное имя, duplicate email | invalid; HTTP303 users с понятной ошибкой; никаких частичных users/credentials/assignments/invitations. Concurrent duplicate invite даёт ровно одну identity/ссылку; другой результат invalid. |
| reissue | target status1/invited | issued; все предыдущие неиспользованные и неотозванные invitations получают revoked_at; добавлена новая строка24h. Old identity/roles и все прежние invitation rows сохранены. HTTP303 users, новый token flash один раз. Active/blocked/missing target invalid без фактов; предыдущие revoked/expired tokens не активируют. |
| activation | действующий token, matching password14..200 bytes без email local-part (если local-part≥4 bytes) | GET200 email и рабочая форма с Yii CSRF; POST200 подтверждение и ссылка login, без auto-login. Одна transaction: Argon2id hash устанавливается только вместо null, used_at invitation, activation_state active и updated_at; права не расширяются. Вход с новым password успешен; initial hash после login не меняется. |
| activation-reject | malformed/expired/revoked/used token, blocked/disabled/active identity; short/long/email-containing/nonmatching password | generic invalid link либо прежняя понятная password error, HTTP200 с возможностью исправить password; без изменений facts/hash. Missing/bad CSRF400 без активации. Token single-use: concurrent/repeated activation максимум один activated; прежний hash не перезаписывается. |
| role | attach active role / detach assigned role | changed; projection assignment добавлена/удалена и ровно один append-only role event с actor/time; идентичный повтор owner unchanged без второго event. Alternate URL roleId и body roleId дают одинаковую семантику. Invalid target/role/action и inactive attach invalid. Default user detach запрещён. Superadministrator attach/detach требует ещё exact `access.superadminister`; последний active superadministrator не удаляется. HTTP303 users после успеха, invalid400, access_denied403. |
| status | non-self active→block или blocked→unblock | changed: status0/blocked либо status1/active, session_version+1, один append-only status event с actor/time; HTTP303 users. Самого себя менять нельзя (403); missing/invited/invalid/already-state invalid400 без facts. Block закрывает следующую старую Yii session; unblock старую session не оживляет, нужен новый login. Last active superadministrator защищён. |
| serialization | конкурирующие activate/reissue, role removal/block разных последних superadministrators | согласованный lock order; ровно один действующий activation outcome, максимум одна текущая invitation, минимум один enabled active superadministrator остаётся. Никакой транзакции с двумя DB connections. |
| rollback | DB failure после начала mutation (credential/role/status/invitation write) | вся операция откатывается: no partial state/history; приложение не раскрывает driver details. HTTP503 safe. Session delivery failure не подтверждает успех; после возможного committed invite администратор может получить новую ссылку reissue, не требует raw-token хранения. |
| isolation | public Yii admin/activation entrypoints и assets | не загружают rapid-pilot, старые PilotHttp handlers/auth/session decorators и не выполняют DDL/bootstrap. Все новые views через Yii view/assets, корпоративные shlz классы/геометрия сохраняются. |

## Independently determined examples

Fixture actor9101 имеет access.administer; role9210 `user`, role9211
`superadministrator`, role9212 `control_engineer` с checklist.edit. При invite
` New.Person@SHLZ.RU ` получается `new.person@shlz.ru`, только role9210 и null hash.
Назначение9212 добавляет один role_attached; повтор не добавляет события; detach
добавляет один role_detached, исходный event сохранён. Block→unblock увеличивает
session_version с1 до3 и добавляет два разных события. Один superadministrator
9402 не может быть заблокирован/лишён роли; при наличии9403 конкурентные операции
могут убрать только одного. Старый invitation token после reissue недействителен;
активация по новому token не меняет role assignments.

## Verification and migration boundary

Tests вызывают реальный HTTP/application seam; SQL fixture используется только
для setup/fault injection и независимого observation заранее описанных persisted
facts, не для реализации проверяемого действия. Один полный candidate включает
owner semantics/concurrency, HTTP форм/redirects/denials и browser golden flow.
Схема остаётся v24. Старый runtime/stand остаётся oracle до отдельного cutover;
его сохранённые adapters перечислены в migration inventory и не участвуют в Yii
request. Удаление прежнего runtime целиком остаётся финальной частью #76; наличие
этого среза не закрывает #71/#76 и не означает переключение production.
