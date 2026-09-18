# LEGACY-CONTROL-ENGINEER-IMPORT-001 — инженеры и связи объектов

Status: `ACCEPTED_FOR_GATE_2`
Owner decision: 2026-09-18
Public seam: `php bin/yii legacy-import/run --interactive=0`

## A1. Source snapshot

Один read-only consistent snapshot SHALL прочитать eligible objects и referenced legacy `users`/`users_roles`. Identity key — exact positive `users.id`; ФИО/email MUST NOT применяться для auto-match. Допустимы active users и active roles exact IDs `16` и `18`. Нулевая ссылка означает unassigned object. Missing, inactive, wrong-role, duplicate или malformed positive reference SHALL fail closed до target effects.

## A2. Pending identity

Import SHALL идемпотентно создать один local user со source name/email, `status=1`, состоянием «ожидает приглашения», без credential/password/session/invitation; назначить existing `construction_control_engineer` role/capability и append-only legacy link. Email collision или explicit corrected link SHALL вернуть conflict без auto-merge. Пользователь MUST NOT войти. Existing authorized admin invitation command позже создаёт первое invitation для того же user; rejected command не создаёт фактов.

## A3. Case assignment

В той же target transaction import SHALL создать canonical source assignment `installation_case → local engineer` с legacy object/user IDs, local user ID, cutoff и provenance. Нулевая ссылка сохраняет дело unassigned. Positive unresolved identity откатывает invocation. Identical replay не добавляет facts; changed source engineer либо manual/native correction SHALL вернуть conflict и MUST NOT изменять текущую связь или историю.

## A4. Reads

Authorized object queue/card SHALL читать canonical assignment, показывать engineer name и pending/active status, а unassigned — явно без инженера. HTTP reads MUST NOT обращаться к live legacy. Unauthorized actor не видит identity. Admin directory показывает «Ожидает приглашения» и explicit invitation action.

## A5. Result and atomicity

Success JSON сохраняет прежние counts и добавляет `engineersReferenced`, `engineersCreated`, `engineersAlreadyPresent`, `objectsLinked`, `objectsUnassigned`; PII/secrets отсутствуют и `eligible = objectsLinked + objectsUnassigned`. Source unchanged. Любой conflict возвращает stable non-zero без partial target facts. Concurrent identical imports оставляют одну identity/link/assignment.

## A6. Schema and boundaries

Migration additive/reapplicable/fail-safe. Production closure MUST NOT загружать `rapid-pilot`. Console/source/HTTP adapters не владеют writes; state changes принадлежат import application seam через `IdentityAccess` и `InstallationProcess`.
