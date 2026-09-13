# YII2-DISPOSABLE-RESTORE-REHEARSAL-001 — реальный disposable backup/restore и rollback

## Простыми словами

Уполномоченный оператор может восстановить только заранее созданный одноразовый
стенд, чьи реальные Docker/MariaDB/volume identities точно совпали с подписанным
пакетом операции. Новый Yii2 backup создаёт настоящий bundle, существующий
`StandRestoreApplication` управляет настоящим restore и объявляет успех лишь после
restart/readiness и независимой проверки данных. Production этим не переключается.

## 1. Actor, authority, preconditions и public seams

Spec ID: `YII2-DISPOSABLE-RESTORE-REHEARSAL-001`. Actor — deployment operator.
Owner scope — issue #76 после merged PR #124, base
`e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`. Эта authority разрешает разработку
и недеструктивные проверки, но не разрешает destructive rehearsal до отдельного
exact action package и не разрешает production deployment/cutover.

Публичные application seams остаются `php bin/yii stand-backup/create|verify` и
`php bin/yii stand-restore/run --interactive=0`. `StandRestoreApplication` MUST
оставаться единственным владельцем restore admission, bundle validation,
ledger/lease/replay/conflict, effect ordering и confirmed outcome. Driver является
узким adapter; controller, compose/runbook и tests MUST NOT публиковать success.

Production-shaped invocation добавляет только absolute authorization manifest
reference. Manifest MUST canonical-bound связывать authorization id/scope/expiry,
отдельный operation UUID, bundle и target digests, pinned source/image digest,
absolute compose file, exact services, database identity, database/state/secrets
volume names и observed engine IDs, disposable marker, credential-file references,
health URLs и evidence root. Secret values в manifest запрещены.

## 2. Authorization и target attestation до effects

До credentials, subprocess и любого stop/drop/clear/import/replace/restart effect
application MUST проверить existing bundle, exact operation binding, unexpired
explicit disposable authority, canonical manifest, pinned image/source, allowlisted
absolute paths и все observed immutable container/network/database/volume identities.
Имя project/database/volume само по себе не является authority. Отсутствие,
дубликат, reuse, mismatch, symlink, unsafe owner/mode или production-like target
дают `TARGET_INVALID` до effects и оставляют target/evidence byte-identical.

Непосредственно перед каждым destructive phase driver MUST повторно наблюдать и
сравнивать exact identities. Drift до первого effect даёт definite rejection без
effect; drift после возможного effect даёт `OUTCOME_UNKNOWN`, retained lease и
append-only phase evidence без confirmed pointer.

Credentials MUST читаться только из absolute regular non-symlink private files
после admission. Значения MUST NOT попадать в argv, stdout/stderr, exception,
ledger, trace, repository summary или persisted authorization evidence.

## 3. Production-shaped backup и restore effects

Поскольку поставленный stand-backup тоже имеет только fixture effect path, minimal
backup adapter MUST получить consistent MariaDB dump с schema/AUTO_INCREMENT,
tar persistent artifacts с modes и canonical session payload из exact attest-нутых
read-only sources. Bundle publication и verify semantics существующего
`StandBackupApplication` сохраняются; отдельный backup protocol запрещён.

Restore driver получает bytes/references только после immutable bundle admission.
Он MUST quiesce exact writers, восстановить MariaDB migration principal-ом в exact
empty/recreated disposable database, materialize artifacts/sessions через safe
staging/fsync/rename в attest-нутых roots, запустить pinned compose topology и
проверить fresh `/health/live` и `/health/ready`. Partial effect, timeout,
interrupt, subprocess loss, non-zero exit, stale health или недоказуемый result
MUST приводить к `OUTCOME_UNKNOWN`; `RESTORE_VERIFIED` до post-restart observations
запрещён.

Same operation UUID и exact arguments воспроизводит durable result без новых
effects. UUID с другим authorization/bundle/target digest даёт
`OPERATION_CONFLICT`. Rollback MUST иметь новый UUID и отдельную explicit authority;
он не является retry либо изменением прежней ledger record.

## 4. Independently specified known state и roundtrip

Disposable known state задаётся до backup: literal sentinel business/history rows,
canonical migration inventory, таблица с заранее определённым следующим
AUTO_INCREMENT, immutable PDF/artifact bytes и modes, server session с ожидаемым
identity outcome, applicable queued/leased/outbox/recovery facts. Expected values
берутся из setup commands, не из backup/restore implementation.

После public backup/create и independent backup/verify authorized preparation MUST
остановить exact stand и уничтожить/пересоздать только attest-нутые disposable DB,
artifact и session targets. Затем public stand-restore/run MUST завершить real
MariaDB/filesystem effects, restart и fresh health checks.

Success требует независимых post-restart assertions: exact DB facts и append-only
history; exact schema inventory; controlled real insert получает ожидаемый next id;
artifact bytes/hash/mode; session identity либо заранее согласованный relogin outcome;
jobs/outbox/lease/recovery без дублей; основные anonymous/authenticated/FKR/
construction-control/OTIZ golden smoke flows. Synthetic `database.json` или
`readiness.json` fixture не удовлетворяет acceptance.

## 5. Failure predicate и rollback rehearsal

Candidate считается failed при любом non-zero/unknown outcome, identity drift,
missing/mismatched DB/history/schema/next-id/artifact/session/job fact, health
timeout либо failed golden smoke. Rehearsal MUST сохранить этот failed outcome.

Отдельный rollback action package связывает новый UUID с ранее independently
verified known-good bundle и заново attest-нутым тем же disposable target. После
rollback обязательны второй restart, fresh live/ready, DB/history/schema/next-id,
artifact/mode, session/job и golden subset assertions. Запуск rollback command сам
по себе не означает success.

## 6. Audit, evidence, rejected cases и Done

Jobs worker на disposable runtime MUST получать `FMONITOR_BITRIX_CONFIG` только
как фиксированную ссылку `/run/fmonitor-secrets/bitrix-config.json` внутри уже
смонтированного private secrets volume. Caller environment или compose variable
MUST NOT подменять этот path либо раскрывать token/config bytes в rendered
configuration. До запуска operator MUST положить canonical config как regular
non-symlink mode 0600 file в exact attest-нутый secrets volume. Scheduler не
получает Bitrix config. Существующий `jobs/health --interactive=0` healthcheck и
fail-closed startup/readiness MUST сохраняться без ослабления.

Ledger и safe summary содержат digests, operation/authorization ids, observed target
ids, phases/timestamps и assertion outcomes, но не secrets, payloads или private
paths. Полные логи/dumps/artifacts/cookies остаются во внешнем evidence root.

`UNKNOWN` PR/CI/deployment не является GREEN. `RuntimeRecovery` и legacy CLI MUST
остаться, пока executable inventory подтверждает old-format restore, historical
v22/v23 forward migration, schema v22–v24 compatibility, jobs recovery и legacy
runbook responsibilities. Retirement — отдельный срез после полного replacement.

Done этого этапа: intended RED, independent Gate 3 APPROVED, отдельный executor,
focused generated plan GREEN, independent Gate 5 APPROVED, отдельно разрешённые
real disposable roundtrip и rollback с exact evidence и один exact-source CI
`VERIFY_OK`. Production deployment/cutover остаётся не выполненным.
