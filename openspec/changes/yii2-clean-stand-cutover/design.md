## Context

См. `proposal.md`. Проверенный `origin/main` `f4dab7d6b576edc3bb6e9578a939e90b9a1f36ae` содержит canonical production Compose, Yii2 migrations, authentication/access, initial owner provisioning, web cutover, jobs commands и production image closure. Backup/restore также landed как optional offline capability. Новый owner decision устраняет legacy preservation и restore/rollback из critical path; этот change должен сначала проверить composition целиком и добавлять production code только при честном executable RED.

## Goals / Non-Goals

**Goals:**

- Один reconstructible acceptance package для exact clean candidate и explicit disposable target.
- Реальные MariaDB, filesystem, container, HTTP и long-running jobs boundaries.
- Переиспользование существующих public owners без второй provisioning/migration/jobs implementation.
- Отдельность verification, disposable deployment и будущего production cutover authorizations.

**Non-Goals:**

- Legacy data migration, backup restore, rollback или UNKNOWN reconciliation.
- Изменение бизнес-workflows либо schema semantics.
- Удаление `RuntimeRecovery`, historical verifiers или offline backup/restore.
- Production traffic switch или удаление старого stand.

## Decisions

### 1. Canonical Compose является topology owner

Acceptance использует `deploy/runtime/compose.yaml` с одним exact image digest и его существующими `db`, `prepare`, `migrate`, `php`, `web`, `jobs-worker`, `jobs-scheduler` services. Второй rehearsal Compose или синтетическая JSON topology отвергнуты: они не докажут production boundaries.

### 2. Provisioning оркестрирует существующие public seams

Порядок: exact attestation/authorization → DB creation by deployment principal → runtime prepare → `php bin/yii schema-migrate/run --interactive=0` → existing initial-admin provisioning → runtime startup. Persistence owners остаются в canonical migration catalogue и IdentityAccess provisioning; acceptance harness только вызывает и наблюдает их. Secrets передаются private file/environment references и не копируются в evidence.

### 3. Acceptance harness — внешний observer, не новый application owner

Root-owned executable contract может добавить test/deployment orchestration и evidence composition. Он не владеет domain mutations и не создаёт общий workflow engine. Если existing main проходит весь contract, production delta равен нулю; review охватывает spec/tests/operational evidence.

### 4. Golden flows используют isolated acceptance-only setup

Нужный минимальный object/user configuration создаётся private setup principal и adapter, подключёнными только отдельным acceptance Compose override. Adapter может использовать internal DB/application boundaries, но не поставляется в production image, не добавляет public command/route и недоступен normal topology. HTTP assertions затем вызывают неизменённый production `public/runtime.php`; expected domain facts берутся из landed FKR, construction-control, checklist и OTIZ contracts. Legacy snapshot не используется.

### 5. Jobs proof наблюдает реальный цикл

Worker и scheduler остаются long-running production Compose services. Acceptance-only workload adapter создаёт только initial ready work в isolated target; claim/lease, terminal history, outbox attempt/history и heartbeat создают настоящие worker/scheduler owners. Readiness не подменяется ручной вставкой terminal/heartbeat либо synthetic JSON.

### 6. Legacy closure доказывается тремя независимыми наблюдениями

Image inventory доказывает отсутствие `rapid-pilot`; Compose/process commands доказывают canonical Yii2 entrypoints; attributable loaded-file traces собирает acceptance-only mounted probe без изменения image/routes/commands. `bin/fmonitor2-runtime-recovery.php` и schema v22–v24 остаются historical/offline compatibility и не удаляются.

### 7. Acceptance topology не является production topology

Отдельный override и adapters хранятся под `tests/Support/clean-stand/`. Production Compose не ссылается на них, Dockerfile их не копирует, Yii route/command registry их не регистрирует. Architecture test сравнивает production files с baseline/current normal contracts и исполняет rejection adapters вне exact disposable context.

### 8. Fresh target identity имеет две attestation фазы

До create package связывает exact namespace/resource names и доказывает их отсутствие/non-overlap; будущие Docker IDs заранее неизвестны. Сразу после Compose create harness наблюдает actual IDs/labels/image/network/mounts и повторно attest-ит ownership до database/bootstrap. Неожиданный pre-existing resource либо post-create drift блокирует последующие effects без cleanup masking.

### 9. State-changing execution имеет отдельные authorization boundaries

Planning, RED/Gates и package generation non-destructive. Создание clean disposable target требует exact package authorization. Успешный acceptance не разрешает production traffic switch или удаление старого stand; для них готовится следующий отдельный authorization.

## Risks / Trade-offs

- [Existing focused tests GREEN, но integrated topology сломана] → реальный disposable Compose acceptance с readiness, jobs и HTTP flows.
- [Test orchestration незаметно становится вторым owner] → все mutations только через existing public seams; direct SQL разрешён deployment principal только для fresh DB/principal bootstrap и read-only observation после этого.
- [Synthetic golden data расходится с product contract] → seed и expectations ссылаются на landed executable specs и создаются заново на fresh schema.
- [Historical jobs PR имел неуспешный aggregate] → не наследовать старый verdict; новый exact-source jobs/runtime proof и один final CI обязательны.
- [Acceptance ошибочно принимают за production cutover] → отдельный result name, authorization package и явный запрет traffic switch/old-stand deletion.

## Migration Plan

1. От актуального `main` создать normative executable spec и verification input; подготовить Quality Graph plan.
2. Root пишет intended RED integrated acceptance; independent Gate 3 проверяет sensitivity и реальные boundaries.
3. Отдельный executor добавляет только выявленный минимальный operational/production delta; при already-GREEN main production code не меняется.
4. Выполнить focused plan, independent Gate 5 и один exact-source full CI.
5. Сформировать exact disposable clean-provision authorization package и остановиться.
6. После отдельной authorization развернуть clean candidate, выполнить acceptance и сохранить внешний evidence.
7. Production cutover и последующее удаление старого stand планируются/авторизуются отдельно; restore/rollback не являются prerequisite этого change.
