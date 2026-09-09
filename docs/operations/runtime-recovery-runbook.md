# Production runtime recovery — operational draft

**Status: OPERATIONALLY CROSS-CHECKED; INDEPENDENTLY REVIEWED.** Команды ниже
собраны из public recovery CLI, canonical v23 contract и private drills. Они ещё не
являются утверждённой production recovery procedure. Retention, RPO и RTO остаются
решениями владельца.

Recovery bundle содержит private DB/state data. Его каталог имеет mode0700, файлы —
0600; bundle, environment и command output не помещаются в repository или общий log.
Команды выполняются из clean checkout exact reviewed commit с Docker Compose v2.

## 1. Exact source, image и private paths

Загрузить существующий private runtime environment из production runbook, затем
зафиксировать идентичность реально запущенного image:

```sh
set -a; . "$FMONITOR_PRIVATE_ENV"; set +a
FMONITOR_SOURCE_PROJECT="${COMPOSE_PROJECT_NAME:?private environment must name source project}"
FMONITOR_SOURCE_SESSION_INSTANCE="${FMONITOR_SESSION_INSTANCE:?private environment must name source session instance}"
FMONITOR_JOBS_ENV="$FMONITOR_PRIVATE_ROOT/jobs.env"
FMONITOR_SOURCE_COMMIT="$(git rev-parse HEAD)"
test -z "$(git status --porcelain)"
FMONITOR_IMAGE_ID="$(docker image inspect "$FMONITOR_RUNTIME_IMAGE" --format '{{.Id}}')"
FMONITOR_IMAGE_REVISION="$(docker image inspect "$FMONITOR_RUNTIME_IMAGE" --format '{{index .Config.Labels "org.opencontainers.image.revision"}}')"
test "$FMONITOR_IMAGE_REVISION" = "$FMONITOR_SOURCE_COMMIT"

umask 077
FMONITOR_RECOVERY_ROOT="$FMONITOR_PRIVATE_ROOT/recovery/$FMONITOR_SOURCE_COMMIT"
install -d -m 700 "$FMONITOR_RECOVERY_ROOT"
FMONITOR_BUNDLE_HOST="$FMONITOR_RECOVERY_ROOT/bundle-v23"
FMONITOR_RESULT_HOST="$FMONITOR_RECOVERY_ROOT/backup-result.json"
test ! -e "$FMONITOR_BUNDLE_HOST"
```

Каталог bundle должен быть доступен только runtime UID10001. Следующий одинаковый
для Linux/macOS шаг использует exact image без сети и не расширяет mode:

```sh
install -d -m 700 "$FMONITOR_RECOVERY_ROOT/bundles"
docker run --rm --network none --user 0:0 \
  --mount "type=bind,source=$FMONITOR_RECOVERY_ROOT/bundles,target=/b" \
  --entrypoint sh "$FMONITOR_RUNTIME_IMAGE" -c 'chown 10001:10001 /b && chmod 0700 /b'
FMONITOR_BUNDLE_HOST="$FMONITOR_RECOVERY_ROOT/bundles/source-v23"
```

## 2. Ordered quiesce и backup

Сначала остановить источник новых jobs, затем worker, затем HTTP writers. Не
использовать `down`, `--volumes` или быстрый kill. Проверить terminal container
status и сохранить его в private evidence до attestation:

Source DB при quiesce и backup остаётся запущенной: recovery CLI подключается к ней
для consistent dump. Если deployment использует дополнительные Compose override
files, тот же exact набор `--file` MUST добавляться к каждой source Compose команде.

```sh
docker compose --project-name "$FMONITOR_SOURCE_PROJECT" --file deploy/runtime/compose.yaml --profile jobs stop --timeout 60 jobs-scheduler
docker compose --project-name "$FMONITOR_SOURCE_PROJECT" --file deploy/runtime/compose.yaml --profile jobs stop --timeout 60 jobs-worker
docker compose --project-name "$FMONITOR_SOURCE_PROJECT" --file deploy/runtime/compose.yaml stop --timeout 60 web php
docker compose --project-name "$FMONITOR_SOURCE_PROJECT" --file deploy/runtime/compose.yaml --profile jobs ps --all \
  >"$FMONITOR_RECOVERY_ROOT/source-services-stopped.txt"
chmod 600 "$FMONITOR_RECOVERY_ROOT/source-services-stopped.txt"
```

`--writers-stopped` — операторская attestation уже выполненного порядка, а не
команда остановки. Backup запускается тем же exact image, в source Compose network,
с source state/secrets volumes. Подставить фактическое Compose project name:

```sh
docker run --rm --user 10001:10001 \
  --network "${FMONITOR_SOURCE_PROJECT}_default" \
  --mount "type=volume,source=${FMONITOR_SOURCE_PROJECT}_state,target=/home/fmonitor/.local/state/fmonitor2" \
  --mount "type=volume,source=${FMONITOR_SOURCE_PROJECT}_secrets,target=/run/fmonitor-secrets" \
  --mount "type=bind,source=$FMONITOR_RECOVERY_ROOT/bundles,target=/backup" \
  --env FMONITOR_DB_HOST=db --env FMONITOR_DB_PORT=3306 \
  --env FMONITOR_DB_NAME --env FMONITOR_DB_USER --env FMONITOR_DB_PASSWORD \
  --env FMONITOR_PROCESS_TABLE_PREFIX --env FMONITOR_LEGACY_TABLE_PREFIX \
  --env FMONITOR_SESSION_INSTANCE --env FMONITOR_TRUSTED_REQUEST_HOST \
  --env FMONITOR_TRUSTED_REQUEST_SCHEME \
  --env FMONITOR_SESSION_STATE_ROOT=/home/fmonitor/.local/state/fmonitor2 \
  --env FMONITOR_ARTIFACT_STORAGE_ROOT=/home/fmonitor/.local/state/fmonitor2/artifacts \
  --env FMONITOR_ORIGINAL_DB_PASSWORD_FILE=/run/fmonitor-secrets/database-password \
  --env FMONITOR_ORIGINAL_SAFE_LOG_FILE=/home/fmonitor/.local/state/fmonitor2/log/original-safe.jsonl \
  --entrypoint php "$FMONITOR_RUNTIME_IMAGE" \
  bin/fmonitor2-runtime-recovery.php backup \
  --destination /backup/source-v23 \
  --source-commit "$FMONITOR_SOURCE_COMMIT" \
  --image-reference "$FMONITOR_RUNTIME_IMAGE" \
  --image-id "$FMONITOR_IMAGE_ID" --writers-stopped \
  >"$FMONITOR_RESULT_HOST" 2>"$FMONITOR_RECOVERY_ROOT/backup-error.log"
chmod 600 "$FMONITOR_RESULT_HOST" "$FMONITOR_RECOVERY_ROOT/backup-error.log"
test "$(php -r '$x=json_decode(file_get_contents($argv[1]),true);echo ($x["ok"]??false)&&($x["reason"]??"")==="BACKUP_CREATED"?"yes":"no";' "$FMONITOR_RESULT_HOST")" = yes
test ! -s "$FMONITOR_RECOVERY_ROOT/backup-error.log"
```

Не печатать manifest: он содержит inventory и source DB name. Ожидаемый v23 bundle
имеет format v1, schema23,69 tables,39 AUTO_INCREMENT records и `deferred=[]`.

## 3. Empty target contour

Restore выполняется в новый Compose project и пустые volumes. Не направлять его в
source project. Создать private `target.env` с новым project/database/loopback ports,
но тем же reviewed image и canonical prefixes. Пример override монтирует state
volume на родительский каталог: recovery target `fmonitor2` обязан отсутствовать.

```sh
FMONITOR_TARGET_PROJECT=fmonitor2-recovery-target
FMONITOR_TARGET_DB=fmonitor2_recovery_target
FMONITOR_TARGET_HTTP_PORT=18196
test "$FMONITOR_TARGET_PROJECT" != "$FMONITOR_SOURCE_PROJECT"
test "$FMONITOR_TARGET_HTTP_PORT" != "$FMONITOR_HTTP_PORT"
FMONITOR_TARGET_ROOT="$FMONITOR_RECOVERY_ROOT/target"
install -d -m 700 "$FMONITOR_TARGET_ROOT"
cat >"$FMONITOR_TARGET_ROOT/compose.override.yaml" <<'YAML'
services:
  prepare:
    volumes: !override
      - state:/home/fmonitor/.local/state
      - secrets:/run/fmonitor-secrets
  migrate:
    volumes: !override
      - state:/home/fmonitor/.local/state
      - secrets:/run/fmonitor-secrets
  php:
    volumes: !override
      - state:/home/fmonitor/.local/state
      - secrets:/run/fmonitor-secrets
  jobs-worker:
    volumes: !override
      - state:/home/fmonitor/.local/state
      - secrets:/run/fmonitor-secrets
      - type: bind
        source: "${FMONITOR_BITRIX_TOKEN_HOST_FILE:-/dev/null}"
        target: /run/fmonitor-secrets/bitrix-token
        read_only: true
      - type: bind
        source: "${FMONITOR_BITRIX_CA_HOST_FILE:-/dev/null}"
        target: /run/fmonitor-secrets/bitrix-ca.pem
        read_only: true
  jobs-scheduler:
    volumes: !override
      - state:/home/fmonitor/.local/state
      - secrets:/run/fmonitor-secrets
volumes:
  state:
    external: true
    name: "${FMONITOR_TARGET_PROJECT}_state"
  secrets:
    external: true
    name: "${FMONITOR_TARGET_PROJECT}_secrets"
YAML
chmod 600 "$FMONITOR_TARGET_ROOT/compose.override.yaml"
```

Создать target environment с новым project/database и новыми credentials. Значения
source commit/image выше не менять:

```sh
FMONITOR_TARGET_MIGRATION_DB_USER=root
FMONITOR_TARGET_MIGRATION_DB_PASSWORD="$(od -An -N32 -tx1 /dev/urandom | tr -d ' \n')"
FMONITOR_TARGET_DB_USER=fmonitor_runtime
FMONITOR_TARGET_DB_PASSWORD="$(od -An -N32 -tx1 /dev/urandom | tr -d ' \n')"
cat >"$FMONITOR_TARGET_ROOT/target.env" <<EOF
COMPOSE_PROJECT_NAME=$FMONITOR_TARGET_PROJECT
FMONITOR_TARGET_PROJECT=$FMONITOR_TARGET_PROJECT
FMONITOR_RUNTIME_IMAGE=$FMONITOR_RUNTIME_IMAGE
FMONITOR_DB_NAME=$FMONITOR_TARGET_DB
FMONITOR_DB_USER=$FMONITOR_TARGET_DB_USER
FMONITOR_DB_PASSWORD=$FMONITOR_TARGET_DB_PASSWORD
FMONITOR_MIGRATION_DB_USER=$FMONITOR_TARGET_MIGRATION_DB_USER
FMONITOR_MIGRATION_DB_PASSWORD=$FMONITOR_TARGET_MIGRATION_DB_PASSWORD
FMONITOR_PROCESS_TABLE_PREFIX=$FMONITOR_PROCESS_TABLE_PREFIX
FMONITOR_LEGACY_TABLE_PREFIX=$FMONITOR_LEGACY_TABLE_PREFIX
FMONITOR_SESSION_INSTANCE=$FMONITOR_SOURCE_SESSION_INSTANCE
FMONITOR_TRUSTED_REQUEST_HOST=127.0.0.1:$FMONITOR_TARGET_HTTP_PORT
FMONITOR_TRUSTED_REQUEST_SCHEME=http
FMONITOR_HTTP_PORT=$FMONITOR_TARGET_HTTP_PORT
EOF
chmod 600 "$FMONITOR_TARGET_ROOT/target.env"
set -a; . "$FMONITOR_TARGET_ROOT/target.env"; set +a
```

Ports должны быть свободны и не совпадать с source/retained contours. Затем:

```sh
# Fail closed before the first target Docker mutation. Project names are not adopted.
test -z "$(docker ps --all --quiet --filter "label=com.docker.compose.project=$FMONITOR_TARGET_PROJECT")"
for volume in database state secrets; do
  if docker volume inspect "${FMONITOR_TARGET_PROJECT}_${volume}" >/dev/null 2>&1; then exit 66; fi
done
if docker network inspect "${FMONITOR_TARGET_PROJECT}_default" >/dev/null 2>&1; then exit 66; fi

docker volume create "${FMONITOR_TARGET_PROJECT}_state" >/dev/null
docker volume create "${FMONITOR_TARGET_PROJECT}_secrets" >/dev/null
docker compose --project-name "$FMONITOR_TARGET_PROJECT" \
  --file deploy/runtime/compose.yaml \
  --file "$FMONITOR_TARGET_ROOT/compose.override.yaml" up --detach --wait db

# Volume root существует, но recovery target /s/fmonitor2 остаётся отсутствующим.
docker run --rm --user 0:0 \
  --mount "type=volume,source=${FMONITOR_TARGET_PROJECT}_state,target=/s,volume-nocopy" \
  --entrypoint sh "$FMONITOR_RUNTIME_IMAGE" -c 'test ! -e /s/fmonitor2 && chown 10001:10001 /s && chmod 0700 /s'
docker run --rm --user 0:0 \
  --mount "type=volume,source=${FMONITOR_TARGET_PROJECT}_secrets,target=/s" \
  --entrypoint sh "$FMONITOR_RUNTIME_IMAGE" -c 'chown 10001:10001 /s && chmod 0700 /s'

# В private secret volume временно положить target migration credential для restore readiness.
docker run --rm --user 10001:10001 --network none \
  --mount "type=volume,source=${FMONITOR_TARGET_PROJECT}_secrets,target=/s" \
  --env P="$FMONITOR_TARGET_MIGRATION_DB_PASSWORD" --entrypoint php "$FMONITOR_RUNTIME_IMAGE" \
  -r 'umask(0077);file_put_contents("/s/database-password",getenv("P"),LOCK_EX);chmod("/s/database-password",0600);'
```

## 4. Restore exact bundle

Restore использует target migration principal, потому что target DB ещё пуст. Bundle
монтируется read-only; state target создаёт только recovery CLI.

```sh
docker run --rm --user 10001:10001 \
  --network "${FMONITOR_TARGET_PROJECT}_default" \
  --mount "type=volume,source=${FMONITOR_TARGET_PROJECT}_state,target=/home/fmonitor/.local/state,volume-nocopy" \
  --mount "type=volume,source=${FMONITOR_TARGET_PROJECT}_secrets,target=/run/fmonitor-secrets" \
  --mount "type=bind,source=$FMONITOR_RECOVERY_ROOT/bundles,target=/backup,readonly" \
  --env FMONITOR_DB_HOST=db --env FMONITOR_DB_PORT=3306 \
  --env FMONITOR_DB_NAME="$FMONITOR_TARGET_DB" \
  --env FMONITOR_DB_USER="$FMONITOR_TARGET_MIGRATION_DB_USER" \
  --env FMONITOR_DB_PASSWORD="$FMONITOR_TARGET_MIGRATION_DB_PASSWORD" \
  --env FMONITOR_PROCESS_TABLE_PREFIX --env FMONITOR_LEGACY_TABLE_PREFIX \
  --env FMONITOR_SESSION_INSTANCE --env FMONITOR_TRUSTED_REQUEST_HOST \
  --env FMONITOR_TRUSTED_REQUEST_SCHEME \
  --env FMONITOR_SESSION_STATE_ROOT=/home/fmonitor/.local/state/fmonitor2 \
  --env FMONITOR_ARTIFACT_STORAGE_ROOT=/home/fmonitor/.local/state/fmonitor2/artifacts \
  --env FMONITOR_ORIGINAL_DB_PASSWORD_FILE=/run/fmonitor-secrets/database-password \
  --env FMONITOR_ORIGINAL_SAFE_LOG_FILE=/home/fmonitor/.local/state/fmonitor2/log/original-safe.jsonl \
  --entrypoint php "$FMONITOR_RUNTIME_IMAGE" \
  bin/fmonitor2-runtime-recovery.php restore \
  --bundle /backup/source-v23 \
  --target-state-root /home/fmonitor/.local/state/fmonitor2 \
  --source-commit "$FMONITOR_SOURCE_COMMIT" \
  --image-reference "$FMONITOR_RUNTIME_IMAGE" --image-id "$FMONITOR_IMAGE_ID" \
  >"$FMONITOR_TARGET_ROOT/restore-result.json" 2>"$FMONITOR_TARGET_ROOT/restore-error.log"
chmod 600 "$FMONITOR_TARGET_ROOT/restore-result.json" "$FMONITOR_TARGET_ROOT/restore-error.log"
test "$(php -r '$x=json_decode(file_get_contents($argv[1]),true);echo ($x["ok"]??false)&&($x["reason"]??"")==="RESTORE_COMPLETED"?"yes":"no";' "$FMONITOR_TARGET_ROOT/restore-result.json")" = yes
test ! -s "$FMONITOR_TARGET_ROOT/restore-error.log"
```

Ожидается exact `RESTORE_COMPLETED`. Ошибки identity/inventory/hash дают
`BUNDLE_INVALID` до target mutation; непустой target — `TARGET_NOT_EMPTY`.

## 5. Runtime principal, readiness и explicit resume

После restore создать target DML-only account по exact recipe production runbook и
атомарно заменить `database-password` в target secret volume на runtime credential.
Затем проверить schema/storage отдельно от operational Jobs health:

```sh
docker compose --project-name "$FMONITOR_TARGET_PROJECT" \
  --file deploy/runtime/compose.yaml --file "$FMONITOR_TARGET_ROOT/compose.override.yaml" \
  exec -T db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot' <<SQL
CREATE USER '${FMONITOR_TARGET_DB_USER}'@'%' IDENTIFIED BY '${FMONITOR_TARGET_DB_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${FMONITOR_TARGET_DB}\`.* TO '${FMONITOR_TARGET_DB_USER}'@'%';
SQL

docker run --rm --user 10001:10001 --network none \
  --mount "type=volume,source=${FMONITOR_TARGET_PROJECT}_secrets,target=/s" \
  --env P="$FMONITOR_TARGET_DB_PASSWORD" --entrypoint php "$FMONITOR_RUNTIME_IMAGE" \
  -r '$p="/s/database-password.new";umask(0077);file_put_contents($p,getenv("P"),LOCK_EX);chmod($p,0600);rename($p,"/s/database-password");'

docker compose --project-name "$FMONITOR_TARGET_PROJECT" \
  --file deploy/runtime/compose.yaml --file "$FMONITOR_TARGET_ROOT/compose.override.yaml" \
  --profile deployment run --rm --entrypoint php prepare bin/fmonitor2-runtime-check.php

# Stale heartbeat, expired lease или dead backlog могут честно дать exit70 здесь;
# это не означает повреждение успешно восстановленного bundle.
docker compose --project-name "$FMONITOR_TARGET_PROJECT" \
  --file deploy/runtime/compose.yaml --file "$FMONITOR_TARGET_ROOT/compose.override.yaml" \
  --profile jobs run --rm --entrypoint php jobs-worker bin/fmonitor2-jobs.php health \
  >"$FMONITOR_TARGET_ROOT/jobs-health-before-resume.json" || test "$?" -eq 70
chmod 600 "$FMONITOR_TARGET_ROOT/jobs-health-before-resume.json"
```

Ни restore, ни readiness не выполняют queue transition или transport. HTTP можно
запустить после проверки schema/data, не запуская jobs:

```sh
docker compose --project-name "$FMONITOR_TARGET_PROJECT" \
  --file deploy/runtime/compose.yaml --file "$FMONITOR_TARGET_ROOT/compose.override.yaml" \
  up --detach --wait php web
```

Recovery acceptance с fake/local transport выполняется отдельным test harness;
Compose jobs services в этой ветке остаются остановленными. Она не наследует
ambient Bitrix environment и не разрешает live call.

```sh
unset FMONITOR_BITRIX_ORIGIN FMONITOR_BITRIX_WEBHOOK_USER_ID \
  FMONITOR_BITRIX_DEPARTMENT_IDS_JSON FMONITOR_BITRIX_TOKEN_HOST_FILE \
  FMONITOR_BITRIX_CA_HOST_FILE
docker compose --project-name "$FMONITOR_TARGET_PROJECT" \
  --file deploy/runtime/compose.yaml --file "$FMONITOR_TARGET_ROOT/compose.override.yaml" \
  --profile jobs stop --timeout 60 jobs-scheduler jobs-worker
```

Production jobs resume — отдельная ветка после operator authorization и подготовки
token/CA по jobs-разделу основного runbook. Начать с очищенного environment, затем
загрузить только target и approved jobs files:

```sh
unset FMONITOR_BITRIX_ORIGIN FMONITOR_BITRIX_WEBHOOK_USER_ID \
  FMONITOR_BITRIX_DEPARTMENT_IDS_JSON FMONITOR_BITRIX_TOKEN_HOST_FILE \
  FMONITOR_BITRIX_CA_HOST_FILE
test "$FMONITOR_JOBS_ENV" = "$FMONITOR_PRIVATE_ROOT/jobs.env"
test -f "$FMONITOR_JOBS_ENV" && test ! -L "$FMONITOR_JOBS_ENV"
set -a; . "$FMONITOR_TARGET_ROOT/target.env"; . "$FMONITOR_JOBS_ENV"; set +a
case "$FMONITOR_BITRIX_ORIGIN" in https://*) ;; *) exit 64 ;; esac
test -n "$FMONITOR_BITRIX_WEBHOOK_USER_ID"
test -n "$FMONITOR_BITRIX_DEPARTMENT_IDS_JSON"
test -f "$FMONITOR_BITRIX_TOKEN_HOST_FILE" && test ! -L "$FMONITOR_BITRIX_TOKEN_HOST_FILE"
if test -n "${FMONITOR_BITRIX_CA_HOST_FILE:-}"; then
  test -f "$FMONITOR_BITRIX_CA_HOST_FILE" && test ! -L "$FMONITOR_BITRIX_CA_HOST_FILE"
fi
docker compose --project-name "$FMONITOR_TARGET_PROJECT" \
  --file deploy/runtime/compose.yaml --file "$FMONITOR_TARGET_ROOT/compose.override.yaml" \
  --profile jobs up --detach --wait --wait-timeout 60 jobs-worker jobs-scheduler
docker compose --project-name "$FMONITOR_TARGET_PROJECT" \
  --file deploy/runtime/compose.yaml --file "$FMONITOR_TARGET_ROOT/compose.override.yaml" \
  --profile jobs exec -T jobs-worker php bin/fmonitor2-jobs.php health
```

## 6. Rollback boundary

Additive schema downgrade не существует. При pending/leased/ambiguous v23 work
нельзя направлять полный contour на v22 binary или импортировать v23 bundle через
v22 tooling. V22 обязан вернуть `BUNDLE_INVALID` без удаления Jobs/history.

HTTP-only rollback к предыдущему image допустим только после отдельного доказательства
его совместимости с текущей schema. При таком rollback `jobs-scheduler` и
`jobs-worker` остаются остановленными; background health/recovery не объявляются
работающими. Полный rollback выполняется созданием нового пустого contour из
подходящего исторического bundle и исправленной forward migration, а не schema
downgrade и не `down --volumes` на source.

## 7. Cross-check evidence и cleanup policy

Command-level сверка выполнена с private v23 drill
`/tmp/fmonitor2-restore36-v23-drill.BxOeX7`: source
`e5d1b34e2f902b9aec9a99a00c632e82b21fef5b`, image
`sha256:82c0a8214b35ab80de1c036f9a91a2c9451909f473c3202426e89a08c8a158cb`,
schema23/69 tables/39 AUTO_INCREMENT и exact DB/state до resume. Jobs health перед
resume честно вернул stale worker/scheduler; fake-only resume сохранил history и
доставил один committed intent. Backup `1.190s` и restore `1.787s` — только wall
time соответствующего recovery CLI `docker run`; они исключают image build, DB
startup, credential provisioning, проверки данных/browser и не являются RTO.

Update/rollback appendix проверил append после recreate и HTTP-only возврат exact
f22 image при остановленных jobs: health, owner session, checklist revision58,
7 photos, private original327 bytes и OTIZ HTTP сохранились. После возврата current
image три HTTP append подняли checklist revision до61, сохранив прежние history/state
и original327 bytes. DB downgrade не выполнялся. Target18196 намеренно сохранён до
owner evidence review; final summary находится в private evidence
`evidence/final-post-update-summary.json`. После завершения source proof его DB можно только остановить,
не удаляя source volumes:

```sh
docker compose --project-name "$FMONITOR_SOURCE_PROJECT" \
  --file deploy/runtime/compose.yaml stop --timeout 60 db
```

Удалять task-owned target можно только после явного approval. External volumes не
удаляются `compose down --volumes`, поэтому cleanup выполняется явно и только для
проверенного target project:

```sh
test "$FMONITOR_TARGET_PROJECT" = fmonitor2-recovery-target
docker compose --project-name "$FMONITOR_TARGET_PROJECT" \
  --file deploy/runtime/compose.yaml --file "$FMONITOR_TARGET_ROOT/compose.override.yaml" \
  --profile jobs down --volumes --remove-orphans
docker volume rm "${FMONITOR_TARGET_PROJECT}_state" "${FMONITOR_TARGET_PROJECT}_secrets"
```

Source, retained contours и их volumes этой командой не очищаются.
Retention/RPO/RTO не выводятся из drill и остаются решениями владельца.
