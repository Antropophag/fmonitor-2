# Production runtime runbook — validation pending

Status: **CANDIDATE / VALIDATION PENDING**. Документ готовит #27 после #33 и не
утверждает production readiness, deployment или замену текущего manual-pilot stand.

## Preconditions

- Reviewed exact source и immutable `FMONITOR_RUNTIME_IMAGE` digest известны.
- MariaDB backup и snapshot named volumes `database`, `state`, `secrets` сделаны и
  проверены вне repository. Старые volumes и session instance не удалены.
- Файл environment/secret source хранится вне repository с доступом только оператора.
- Runtime database principal существует отдельно от migration principal и имеет
  только нужные `SELECT, INSERT, UPDATE, DELETE` на целевой database.
- UID/GID persistent files совместимы с `10001:10001`; state root остаётся
  `/home/fmonitor/.local/state/fmonitor2`, существующий `FMONITOR_SESSION_INSTANCE`
  сохраняется при restart, если требуется продолжить активные сессии.

Обязательные operator inputs:

```text
FMONITOR_RUNTIME_IMAGE=<registry/image@sha256:...>
FMONITOR_HTTP_PORT=8093
FMONITOR_DB_NAME=<database>
FMONITOR_DB_USER=<dml-user>
FMONITOR_DB_PASSWORD=<external-secret>
FMONITOR_MIGRATION_DB_USER=<ddl-user>
FMONITOR_MIGRATION_DB_PASSWORD=<external-secret>
FMONITOR_PROCESS_TABLE_PREFIX=<prefix>
FMONITOR_LEGACY_TABLE_PREFIX=<same-prefix>
FMONITOR_SESSION_INSTANCE=<existing-or-explicit-new-instance>
FMONITOR_TRUSTED_REQUEST_HOST=<canonical-host[:port]>
FMONITOR_TRUSTED_REQUEST_SCHEME=http|https
```

Не печатать rendered Compose config или environment в общий log: они содержат
credentials. TLS завершается внешним trusted proxy; forwarded Host/scheme не
заменяют explicit trusted values.

## DML account issuance

DBA выполняет это отдельно от application startup, под DDL/account principal.
Подставлять identifiers/secret через защищённый операторский механизм, не shell
history и не committed SQL:

```sql
CREATE USER '<runtime-user>'@'%' IDENTIFIED BY '<runtime-secret>';
GRANT SELECT, INSERT, UPDATE, DELETE ON `<database>`.* TO '<runtime-user>'@'%';
```

Проверить отдельным соединением runtime user: `SELECT` проходит, пробный
`CREATE TABLE` получает access denied. `GET_LOCK` не является проверкой прав;
HTTP/readiness просто не вызывают migration seam.

## First candidate start

Все команды запускаются из repository root с уже injected environment:

```sh
docker compose --file deploy/runtime/compose.yaml config --quiet
docker compose --file deploy/runtime/compose.yaml up --detach --wait db
docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm prepare
docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm migrate
docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm --entrypoint php prepare bin/fmonitor2-runtime-check.php
docker compose --file deploy/runtime/compose.yaml up --detach --wait php web
```

`prepare` разрешено создавать только отсутствующие private paths. Оно должно
оставить state/artifacts/log/secrets directories `0700`, original credential и safe
log `0600`, owner `10001:10001`. Existing symlink/type/owner/mode/content mismatch —
остановка, а не автоматический chown/chmod/rewrite.

`migrate` — one-shot command под migration credentials. Busy catalogue lock
завершается немедленным ненулевым outcome. Не запускать migration через `php`/`web`.

Проверка после старта:

```sh
curl --fail --header "Host: ${FMONITOR_TRUSTED_REQUEST_HOST}" "http://127.0.0.1:${FMONITOR_HTTP_PORT:-8093}/health/live"
curl --fail --header "Host: ${FMONITOR_TRUSTED_REQUEST_HOST}" "http://127.0.0.1:${FMONITOR_HTTP_PORT:-8093}/health/ready"
```

После health выполнить approved login/CSRF/route browser smoke и restart persistence
check. До завершения independent code review и полного CI contour остаётся candidate.

## Initial administrator

Production startup намеренно не вызывает `RapidPilotIdentityBootstrap`, не читает
`FMONITOR_BOOTSTRAP_SUPERADMIN_*` и не создаёт пользователя. На текущем source нет
утверждённой отдельной production CLI для первого администратора. #27 должен задать
и независимо проверить явную operator application operation для initial admin;
до этого использовать только заранее существующую подтверждённую admin identity.
Не вставлять credential/role rows вручную и не запускать demo bootstrap.

## Restart and graceful stop

```sh
docker compose --file deploy/runtime/compose.yaml restart php web
docker compose --file deploy/runtime/compose.yaml stop php web
```

Services используют `SIGQUIT` и grace period 60 секунд. После restart проверить
readiness, сохранённый domain fact/history, session того же instance и private
artifact. Не менять UID, state path, prefixes или session instance в одном restart.

## Rollback

1. Остановить candidate `web`/`php`; не удалять volumes и не выполнять `down --volumes`.
2. Сохранить DB/state/secrets snapshot и diagnostics без secret values.
3. Вернуть предыдущий reviewed exact image/runtime, подключив прежние volumes,
   prefixes и session instance только если он совместим с additive schema v22.
4. Не выполнять destructive down migration и не удалять append-only history.
5. Если предыдущий runtime требует `php -S`/pilot adapter, считать это временным
   rollback contour, а не production-ready состоянием.

Переключение реального stand, восстановление backup и initial-admin provisioning
требуют отдельной явной авторизации и завершённых validation/review records.
