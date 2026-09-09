# Production runtime: clean setup, restart and update

Это canonical operator path для production runtime. Нужны Git, Docker Engine и
Compose v2. TLS завершается внешним trusted proxy. Команды не включают реальные
imports, Bitrix/email sends или background workers. Перед production claim всё ещё
нужны согласованные integration/CI evidence для выбранного exact source.

## 1. Clean checkout и private environment

```sh
git clone https://github.com/Antropophag/fmonitor-2.git
cd fmonitor-2
git checkout --detach <reviewed-commit>
test -z "$(git status --porcelain)"

umask 077
FMONITOR_PRIVATE_ROOT="${FMONITOR_PRIVATE_ROOT:-$HOME/.local/state/fmonitor2-production}"
install -d -m 700 "$FMONITOR_PRIVATE_ROOT"
FMONITOR_PRIVATE_ENV="$FMONITOR_PRIVATE_ROOT/runtime.env"
FMONITOR_SOURCE_REVISION="$(git rev-parse HEAD)"
FMONITOR_DB_PASSWORD="$(od -An -N32 -tx1 /dev/urandom | tr -d ' \n')"
FMONITOR_MIGRATION_DB_PASSWORD="$(od -An -N32 -tx1 /dev/urandom | tr -d ' \n')"
FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD="$(od -An -N32 -tx1 /dev/urandom | tr -d ' \n')"

printf '%s\n' \
  "COMPOSE_PROJECT_NAME=fmonitor2-production" \
  "FMONITOR_RUNTIME_IMAGE=fmonitor2-runtime:$FMONITOR_SOURCE_REVISION" \
  "FMONITOR_HTTP_PORT=8093" \
  "FMONITOR_DB_NAME=fmonitor2" \
  "FMONITOR_DB_USER=fmonitor_runtime" \
  "FMONITOR_DB_PASSWORD=$FMONITOR_DB_PASSWORD" \
  "FMONITOR_MIGRATION_DB_USER=root" \
  "FMONITOR_MIGRATION_DB_PASSWORD=$FMONITOR_MIGRATION_DB_PASSWORD" \
  "FMONITOR_PROCESS_TABLE_PREFIX=fm2_" \
  "FMONITOR_LEGACY_TABLE_PREFIX=fm2_" \
  "FMONITOR_SESSION_INSTANCE=production" \
  "FMONITOR_TRUSTED_REQUEST_HOST=127.0.0.1:8093" \
  "FMONITOR_TRUSTED_REQUEST_SCHEME=http" \
  "FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD=$FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD" \
  >"$FMONITOR_PRIVATE_ENV"
chmod 600 "$FMONITOR_PRIVATE_ENV"
unset FMONITOR_DB_PASSWORD FMONITOR_MIGRATION_DB_PASSWORD FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD
set -a; . "$FMONITOR_PRIVATE_ENV"; set +a
```

Для внешнего hostname/TLS заменить port/host/scheme до первого запуска. Файл нельзя
класть в checkout, печатать через `docker compose config` или прикладывать к logs.
Email первого владельца задаётся отдельно:

```sh
FMONITOR_INITIAL_OWNER_EMAIL='<owner@shlz.ru>'
```

## 2. Exact image

```sh
docker build --pull --file deploy/runtime/Dockerfile \
  --tag "$FMONITOR_RUNTIME_IMAGE" .
docker image inspect "$FMONITOR_RUNTIME_IMAGE" --format '{{.Id}}' \
  >"$FMONITOR_PRIVATE_ROOT/image-id-$FMONITOR_SOURCE_REVISION.txt"
printf '%s\n' "$FMONITOR_SOURCE_REVISION" \
  >"$FMONITOR_PRIVATE_ROOT/source-$FMONITOR_SOURCE_REVISION.txt"
chmod 600 "$FMONITOR_PRIVATE_ROOT"/*.txt
```

`web`, `php`, migrations и CLI используют этот image tag. Для registry release
заменить tag immutable digest `registry/image@sha256:...` после push/verification.

## 3. Database, DML account и schema

```sh
docker compose --file deploy/runtime/compose.yaml up --detach --wait db

docker compose --file deploy/runtime/compose.yaml exec -T db \
  sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -uroot' <<SQL
CREATE USER 'fmonitor_runtime'@'%' IDENTIFIED BY '${FMONITOR_DB_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`fmonitor2\`.* TO 'fmonitor_runtime'@'%';
FLUSH PRIVILEGES;
SQL

docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm prepare
docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm migrate
docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm \
  --entrypoint php prepare bin/fmonitor2-runtime-check.php
```

Проверка прав выполняется без создания schema object:

```sh
docker compose --file deploy/runtime/compose.yaml run --rm --entrypoint php php -r '
$d=new mysqli(getenv("FMONITOR_DB_HOST"),getenv("FMONITOR_DB_USER"),getenv("FMONITOR_DB_PASSWORD"),getenv("FMONITOR_DB_NAME"),(int)getenv("FMONITOR_DB_PORT"));
if(!$d->query("SELECT 1")->fetch_row())exit(1);
$grantee="CONCAT(QUOTE(SUBSTRING_INDEX(CURRENT_USER(),\"@\",1)),\"@\",QUOTE(SUBSTRING_INDEX(CURRENT_USER(),\"@\",-1)))";
$q=$d->query("SELECT (SELECT COUNT(*)<>1 OR COALESCE(SUM(PRIVILEGE_TYPE=\"USAGE\"),0)<>1 FROM information_schema.USER_PRIVILEGES WHERE GRANTEE={$grantee})+(SELECT COUNT(*)<>4 OR COALESCE(SUM(TABLE_SCHEMA=DATABASE() AND PRIVILEGE_TYPE IN (\"SELECT\",\"INSERT\",\"UPDATE\",\"DELETE\")),0)<>4 OR COUNT(DISTINCT PRIVILEGE_TYPE)<>4 FROM information_schema.SCHEMA_PRIVILEGES WHERE GRANTEE={$grantee})+(SELECT COUNT(*)<>0 FROM information_schema.TABLE_PRIVILEGES WHERE GRANTEE={$grantee})+(SELECT COUNT(*)<>0 FROM information_schema.COLUMN_PRIVILEGES WHERE GRANTEE={$grantee})");
exit((int)$q->fetch_column()===0?0:1);'
```

Migrations выполняются только one-shot service под root/migration credential.
Probe требует exact global `USAGE`, ровно `SELECT/INSERT/UPDATE/DELETE` только на
current database и ноль table/column grants; любые дополнительные data/DDL/routine/event/
grant privileges дают отказ. Runtime account не получает DDL. Повтор `CREATE USER` не является update path: для
существующей installation использовать проверенный account и начинать с backup.
Имена `fmonitor2` и `fmonitor_runtime` в этом clean-install recipe являются exact
неизменяемыми identifiers, а не шаблонами для shell interpolation. Другие identifiers
требуют отдельного reviewed provisioning recipe; secrets никогда не подставляются в identifiers.

## 4. Первый owner-admin и start

```sh
docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm \
  -e FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD --entrypoint php prepare \
  bin/fmonitor2-provision-initial-admin.php --email "$FMONITOR_INITIAL_OWNER_EMAIL"

docker compose --file deploy/runtime/compose.yaml up --detach --wait php web
curl --fail --header "Host: $FMONITOR_TRUSTED_REQUEST_HOST" \
  "http://127.0.0.1:$FMONITOR_HTTP_PORT/health/live"
curl --fail --header "Host: $FMONITOR_TRUSTED_REQUEST_HOST" \
  "http://127.0.0.1:$FMONITOR_HTTP_PORT/health/ready"
```

Command создаёт owner только когда все canonical identity tables пусты. Exact retry
с теми же email/password возвращает `already_provisioned` без mutation. Любое partial
или другое existing state возвращает `IDENTITY_NOT_EMPTY`; не использовать demo
bootstrap, rebuild или ручные INSERT. После входа владелец приглашает остальных
пользователей через штатный UI. Удалить bootstrap secret из постоянного файла после
подтверждённого login, сохранив его только в approved credential store:

```sh
tmp="$FMONITOR_PRIVATE_ENV.new"
grep -v '^FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD=' "$FMONITOR_PRIVATE_ENV" >"$tmp"
chmod 600 "$tmp" && mv "$tmp" "$FMONITOR_PRIVATE_ENV"
unset FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD
```

## 5. Backup, restart и version evidence

Создать backup перед каждым update. Output остаётся private mode 600:

```sh
backup="$FMONITOR_PRIVATE_ROOT/db-$FMONITOR_SOURCE_REVISION.sql"
umask 077
docker compose --file deploy/runtime/compose.yaml exec -T db \
  sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb-dump -uroot --single-transaction --databases fmonitor2' \
  >"$backup"
chmod 600 "$backup"
docker compose --file deploy/runtime/compose.yaml ps \
  >"$FMONITOR_PRIVATE_ROOT/services-$FMONITOR_SOURCE_REVISION.txt"
docker image inspect "$FMONITOR_RUNTIME_IMAGE" --format '{{.Id}}' \
  >"$FMONITOR_PRIVATE_ROOT/image-id-$FMONITOR_SOURCE_REVISION.txt"
```

DB dump не включает state/secrets volumes. Их snapshot выполняется средствами
volume/storage platform при остановленных writers; volume не удалять.

```sh
docker compose --file deploy/runtime/compose.yaml restart php web
curl --fail --header "Host: $FMONITOR_TRUSTED_REQUEST_HOST" \
  "http://127.0.0.1:$FMONITOR_HTTP_PORT/health/ready"
```

После restart проверить existing login/session и private artifact. `web`/`php`
используют graceful `SIGQUIT`/60s. Остановка без удаления данных:

```sh
docker compose --file deploy/runtime/compose.yaml stop web php
```

## 6. Reviewed update

1. Сделать DB и platform volume snapshots; записать old source/image identity.
2. Получить clean checkout reviewed commit и собрать новый unique image tag.
3. Загрузить тот же private environment, изменить только
   `FMONITOR_RUNTIME_IMAGE`, выполнить one-shot `migrate`, затем recreate `php web`.
4. Проверить live/ready, login, основной browser flow и сохранённые данные.
5. При rollback остановить new `web/php` и вернуть previous exact image. Не удалять
   additive schema/history и не выполнять `down --volumes`.

## Known limits

- TLS/proxy configuration находится вне этого Compose contour.
- Initial-owner CLI работает только для clean identity state или exact replay; это
  не repair/reset/password-change tool.
- Real Bitrix imports, email sends и background job scheduler не включаются.
- Runtime не переносит legacy data автоматически и не удаляет старые volumes.
- Full production claim зависит от фактически завершённых review/CI/deployment
  evidence для exact source/image; этот runbook сам по себе их не создаёт.
