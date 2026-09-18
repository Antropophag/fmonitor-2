# INITIAL-OWNER-PROVISIONING-001 — initial owner и повторный local up

## Простыми словами

Первый запуск на чистой migrated database создаёт первоначального владельца. Обычная production-команда остаётся строгой. Локальный `make up` явно выбирает один IdentityAccess-owned режим: он создаёт владельца только на полностью пустой identity либо read-only подтверждает ранее созданного bootstrap-владельца. Входы, дополнительные пользователи и история не ломают повторный запуск и ничего не сбрасывают.

## Actor и public seams

Actor — оператор FMonitor, авторизующий bootstrap только выбранной database/prefix.

- Strict production seam: `php bin/fmonitor2-provision-initial-admin.php --email <owner@shlz.ru>`.
- Explicit local startup: `php bin/fmonitor2-provision-initial-admin.php --resume-existing-local --email <owner@shlz.ru>`, вызываемый repository-owned `make up` после migrations. Название flag сохраняется для совместимости; режим атомарно выбирает clean create либо existing-owner resume внутри IdentityAccess.
- Application owner: `FMonitor2\IdentityAccess\MariaDbInitialOwnerProvisioning`; CLI и Make не владеют identity facts.

Оба seam принимают exact direct DB/prefix config, normalized `@shlz.ru` email и внешний `FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD`. Secrets, email и DB details не выводятся. Local startup использует secret только при clean create; при existing-owner resume не сравнивает и не изменяет текущий password hash.

## A1 — clean create и strict production replay

- На clean canonical identity одна transaction создаёт current `LocalRoleCatalog`, одного active user с `full_name`, равным normalized email, Argon2id credential и exact grants `user` plus `superadministrator`, `origin=bootstrap`, null actor; exit 0/status `created`.
- Strict exact repeat требует ровно одного такого user, empty phone/session_version 1, `password_verify`, exact role catalogue/bootstrap grants и пустые invitations/role events/auth attempts/status events; zero mutation, exit 0/status `already_provisioned`.
- Любой иной existing/partial identity state на strict seam: zero mutation, exit 65/reason `IDENTITY_NOT_EMPTY`; никакого promotion/repair.

## A2 — read-only local continuation

Local continuation успешен только если requested normalized email соответствует ровно одному active/unblocked user с существующим credential, а его действующие назначения `user` и `superadministrator` имеют `origin=bootstrap`, null actor и через active role definitions предоставляют `objects.read`, `access.administer`, `access.superadminister` и `access.audit.read`.

Допустимы и не являются причиной отказа:

- другие пользователи и их credentials/grants;
- invitations, auth attempts, role/status events и login history;
- законно изменённые password hash, profile, phone и session version владельца;
- дополнительные непротиворечивые non-bootstrap roles владельца.

Успех возвращает exit 0/status `already_provisioned`. Полный identity preimage остаётся byte/value identical: users, credentials, sessions/profile, roles/permissions/grants, invitations и history не изменяются, новые факты не добавляются.

## A3 — local rejection без изменений

Local continuation возвращает exit 65/reason `LOCAL_OWNER_NOT_RESUMABLE` и не меняет данные, если:

- requested email чужой или совпадает только по email без bootstrap provenance;
- bootstrap-owner отсутствует или неоднозначен, включая другого пользователя с owner bootstrap provenance;
- expected owner disabled, blocked или не active;
- credential отсутствует;
- обязательная `user` либо `superadministrator` bootstrap grant отсутствует, неактивна, имеет non-bootstrap origin/actor или была отозвана;
- active roles больше не дают полный набор необходимых owner permissions.

Отказ не seed-ит catalogue, не создаёт пользователя, не ремонтирует state, не повышает права и не восстанавливает отозванные полномочия. Произвольный `IDENTITY_NOT_EMPTY` не считается успехом.

## A4 — real local-up route и production isolation

`Makefile up` после canonical migrations вызывает CLI только с explicit `--resume-existing-local`. Под существующим advisory lock IdentityAccess SHALL выбрать ровно одно поведение по coherent identity state:

- полностью пустая canonical identity → выполнить существующее строгое создание A1 и вернуть `created`;
- однозначный пригодный bootstrap-owner → выполнить read-only continuation A2 и вернуть `already_provisioned`;
- любое непустое partial/conflicting/ineligible состояние → вернуть `LOCAL_OWNER_NOT_RESUMABLE` по A3 без попытки создать замену.

Direct CLI без этого flag сохраняет strict production semantics A1. Web/FPM, migration runner и production deployment startup local mode не выбирают. Make/CLI не выполняют SQL-классификацию, не подавляют отказ и не создают второго bootstrap/persistence owner.

## A5 — atomicity, concurrency и ошибки

- Invalid args/config: exit 64/`CONFIGURATION_INVALID`, zero mutation.
- Missing/incompatible schema: exit 70/`SCHEMA_NOT_READY`, без DDL/repair.
- Per-database/prefix advisory lock busy: exit 75/`PROVISIONING_BUSY`.
- Unexpected failure: exit 70/`PROVISIONING_UNAVAILABLE`, details redacted.
- Concurrent clean calls создают не более одного owner/grant set; concurrent resume сериализуется тем же lock и выполняет только coherent read либо возвращает busy/conflict.
- Любая ошибка clean create до commit сохраняет полный preimage. Lock всегда освобождается.

## Acceptance examples

1. Clean DB + explicit local-mode `owner@shlz.ru` → `created`, один owner и две bootstrap grants.
2. Immediate local-mode repeat → `already_provisioned`, snapshot unchanged.
3. После login, смены password/profile/session, invitation и появления дополнительного user explicit local repeat → `already_provisioned`, весь snapshot unchanged.
4. После удаления `superadministrator` grant, блокировки owner, подмены provenance, второго bootstrap-owner либо вызова с чужим email explicit local repeat → `LOCAL_OWNER_NOT_RESUMABLE`, snapshot unchanged.
5. Partial nonempty identity без пригодного bootstrap-owner через local mode → `LOCAL_OWNER_NOT_RESUMABLE`, snapshot unchanged и replacement owner не создаётся.
6. Тот же developed state через direct production CLI без flag → `IDENTITY_NOT_EMPTY`, snapshot unchanged.

Не входят schema migration, demo/rebuild bootstrap, обычные invitation/role/status operations, POSIX modes, file UID, VPN route, import filters, engineers, chunking и #182.
