# Production runtime: clean setup, restart and update

Это canonical operator path для production runtime. Нужны Git, Docker Engine и
Compose v2. TLS завершается внешним trusted proxy. Базовые разделы1–6 не включают
background workers; раздел7 описывает их явное подключение с approved конфигурацией. Перед production claim всё ещё
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

1. Остановить writers, включая включённые jobs services; сделать согласованные DB и
   platform volume snapshots и записать old source/image identity.
2. Получить clean checkout reviewed commit и собрать новый unique image tag.
3. Загрузить тот же private environment, изменить только
   `FMONITOR_RUNTIME_IMAGE`, выполнить one-shot `migrate`, затем recreate `php web`
   и ранее включённые jobs services из того же image.
4. Проверить live/ready, login, основной browser flow и сохранённые данные.
5. При rollback остановить new `web/php` и вернуть previous exact image. Не удалять
   additive schema/history и не выполнять `down --volumes`.

## 7. Фоновые задания — явный профиль jobs

Canonical v23 добавляет очередь, историю попыток, outbox, слоты scheduler и process
heartbeats. Миграция выполняется прежней отдельной командой; worker и scheduler
работают под тем же проверенным DML-only account и schema не исправляют.
По умолчанию профиль выключен: обычный `up php web` его не запускает.

Для кадрового транспорта оператор задаёт approved `FMONITOR_BITRIX_ORIGIN`,
`FMONITOR_BITRIX_WEBHOOK_USER_ID`, `FMONITOR_BITRIX_DEPARTMENT_IDS_JSON` и абсолютный
`FMONITOR_BITRIX_TOKEN_HOST_FILE` вне repository. Token — regular file mode0600,
доступный в контейнере runtime UID10001; deployment operator обеспечивает это
ownership до запуска. Для private CA дополнительно задаётся
`FMONITOR_BITRIX_CA_HOST_FILE`; для обычного trusted TLS он не нужен. Worker получает
эти файлы через read-only mounts. Scheduler не получает Bitrix configuration/files.
Отсутствующий или некорректный transport config не разрешает внешний запрос.

Для нового shell сначала загрузить private runtime environment. Создать отдельный
файл настроек (однократно) и заменить фиктивные значения approved значениями; token
в этом файле не хранится:

```sh
FMONITOR_PRIVATE_ROOT="${FMONITOR_PRIVATE_ROOT:-$HOME/.local/state/fmonitor2-production}"
FMONITOR_PRIVATE_ENV="$FMONITOR_PRIVATE_ROOT/runtime.env"
FMONITOR_JOBS_ENV="$FMONITOR_PRIVATE_ROOT/jobs.env"
umask 077
test ! -e "$FMONITOR_JOBS_ENV" && cat >"$FMONITOR_JOBS_ENV" <<'ENV'
FMONITOR_BITRIX_ORIGIN='https://approved-portal.example.invalid'
FMONITOR_BITRIX_WEBHOOK_USER_ID='7'
FMONITOR_BITRIX_DEPARTMENT_IDS_JSON='[71]'
FMONITOR_BITRIX_TOKEN_HOST_FILE="${FMONITOR_PRIVATE_ROOT}/bitrix-token"
FMONITOR_BITRIX_CA_HOST_FILE=''
ENV
chmod 600 "$FMONITOR_JOBS_ENV"
# Отредактировать private jobs.env: origin, webhook user и departments выше фиктивные.
set -a; . "$FMONITOR_PRIVATE_ENV"; . "$FMONITOR_JOBS_ENV"; set +a
```

Token source — уже утверждённый private файл, содержащий только токен. Следующий
одноразовый шаг копирует его без вывода значений и без сети; существующий destination
не перезаписывается. Указать абсолютный `FMONITOR_APPROVED_TOKEN_SOURCE`:

```sh
docker run --rm --interactive --network none --user 0:0 --entrypoint php \
  --mount "type=bind,source=$FMONITOR_APPROVED_TOKEN_SOURCE,target=/input/token,readonly" \
  --mount "type=bind,source=$FMONITOR_PRIVATE_ROOT,target=/output" \
  "$FMONITOR_RUNTIME_IMAGE" <<'PHP'
<?php
umask(0077);
$stage = null;
$status = 0;
try {
    $source = @fopen('/input/token', 'rb');
    $stat = $source === false ? false : fstat($source);
    if ($stat === false || ($stat['mode'] & 0170000) !== 0100000 || $stat['size'] < 1 || $stat['size'] > 1024) {
        throw new RuntimeException();
    }
    $bytes = stream_get_contents($source, 1025);
    fclose($source);
    if (!is_string($bytes) || preg_match('/^[A-Za-z0-9_-]{1,256}(?:\r?\n)?$/D', $bytes) !== 1) {
        throw new RuntimeException();
    }
    $candidate = '/output/.token-stage-'.bin2hex(random_bytes(16));
    if (!mkdir($candidate, 0700)) throw new RuntimeException();
    $stage = $candidate;
    $file = $stage.'/token';
    $handle = fopen($file, 'x');
    if ($handle === false || fwrite($handle, $bytes) !== strlen($bytes)) throw new RuntimeException();
    fclose($handle);
    unset($bytes);
    if (!chmod($file, 0600) || !chown($file, 10001) || !chgrp($file, 10001)) throw new RuntimeException();
    // Atomic publication: link fails if any destination entry already exists.
    if (!@link($file, '/output/bitrix-token')) throw new RuntimeException();
} catch (Throwable $error) {
    fwrite(STDERR, "Token staging failed; destination was not overwritten.\n");
    $status = 1;
} finally {
    if ($stage !== null) {
        @unlink($stage.'/token');
        @rmdir($stage);
    }
}
exit($status);
PHP
```

Если требуется private CA, задать её абсолютный host path в `jobs.env` и обеспечить
читаемость runtime UID10001; файл CA не является token secret. Повторно загрузить
private environment после редактирования. Не печатать expanded Compose config.

```sh
docker compose --file deploy/runtime/compose.yaml --profile jobs up --detach --wait --wait-timeout 60 jobs-worker jobs-scheduler
docker compose --file deploy/runtime/compose.yaml --profile jobs exec -T jobs-worker   php bin/fmonitor2-jobs.php health
```

Scheduler ставит кадровое задание на последний наступивший московский слот HH:07;
повтор не создаёт второе задание, после простоя нет массового hourly backfill.
Outbox sweep ставит отдельные delivery jobs только для committed intents. Production
email transport не установлен: он возвращает `OUTBOX_TRANSPORT_UNCONFIGURED` без
отправки; продуктовые триггеры/шаблоны и подключение sender остаются в #11/#13.
Ночной smoke использует только локальный HTTPS fixture, а не реальный портал.

Worker и scheduler записывают `worker:<instance>` и `scheduler:<instance>` heartbeat
в БД. Команда `health` читает их и очередь, игнорируя старый ready-file; exit0 означает
здоровое состояние, exit70 — устаревший heartbeat, проблемную очередь или недоступную
инфраструктуру. Counter `deadJobs` сохраняет видимость терминальных failed rows;
ручной повтор не удаляет исходную историю. Для operator list/retry требуется доступ
deployment operator к private runtime configuration; authority из CLI arguments
не принимается.

```sh
docker compose --file deploy/runtime/compose.yaml --profile jobs exec -T jobs-worker   php bin/fmonitor2-jobs.php list-failed --page 1 --limit 20

# Выбрать jobId из показанного safe JSON; значение вводит deployment operator.
IFS= read -r FMONITOR_FAILED_JOB_ID
FMONITOR_RETRY_OPERATION_ID="$(docker compose --file deploy/runtime/compose.yaml --profile jobs exec -T jobs-worker php -r '$h=bin2hex(random_bytes(16));$h[12]="4";$h[16]=dechex((hexdec($h[16])&3)|8);echo substr($h,0,8)."-".substr($h,8,4)."-".substr($h,12,4)."-".substr($h,16,4)."-".substr($h,20,12);')"
FMONITOR_JOBS_NOW_UTC="$(date -u +%Y-%m-%dT%H:%M:%S.000000Z)"
docker compose --file deploy/runtime/compose.yaml --profile jobs exec -T jobs-worker   php bin/fmonitor2-jobs.php retry --job-id "$FMONITOR_FAILED_JOB_ID"   --operation-id "$FMONITOR_RETRY_OPERATION_ID" --now-utc "$FMONITOR_JOBS_NOW_UTC"
```

Для разового scheduler diagnostic используется тот же текущий UTC instant:

```sh
docker compose --file deploy/runtime/compose.yaml --profile jobs exec -T jobs-scheduler \
  php bin/fmonitor2-jobs.php schedule-once --now-utc "$FMONITOR_JOBS_NOW_UTC"
```

Retry создаёт linked job, не запускает handler inline и не меняет старый terminal
result. Повтор operation id возвращает тот же linked job. Lease равен5 минутам;
heartbeat продлевает его с cadence60 секунд. Retryable failures получают задержки
1м/5м/15м/1ч, пятая попытка становится dead. Кадровый owner распознаёт уже завершённую
job family; outbox сохраняет общий provider idempotency reference между попытками и
ручными циклами, но не обещает exactly-once при неизвестном ответе провайдера.

Остановка перед согласованным backup/update:

```sh
docker compose --file deploy/runtime/compose.yaml --profile jobs stop jobs-scheduler jobs-worker web php
```

SIGTERM прекращает новые claims. Worker даёт активному child55 секунд, затем завершает
и reaps его; неизвестный результат не объявляется success и lease становится доступен
после expiry. Container grace60 секунд. После update включать jobs только из того же
reviewed image; при rollback остановить новые jobs services и сохранить все queue/
outbox rows. Старый `rapid-pilot/workforce-worker.sh --once` лишь делегирует native
operator sync с explicit prefix; прежнего loop/manifest/ready-file пути больше нет.

Согласованное restore-доказательство развивается в #36. Простой SQL dump из раздела5
не является проверенной самостоятельной restore-процедурой: на MariaDB11.4 DDL
roundtrip исторической completion schema добавляет implicit FK index и не проходит
строгую readiness. Технический restore использует data-only dump и canonical schema
из exact source image с сохранением AUTO_INCREMENT; его v23-проверка, jobs recovery
и окончательная инструкция должны завершиться до заявления production restore readiness.

## Known limits

- TLS/proxy configuration находится вне этого Compose contour.
- Initial-owner CLI работает только для clean identity state или exact replay; это
  не repair/reset/password-change tool.
- Jobs включаются только явно через профиль и approved external configuration;
  реальные Bitrix imports и email sends ночью не выполняются.
- Runtime не переносит legacy data автоматически и не удаляет старые volumes.
- Full production claim зависит от фактически завершённых review/CI/deployment
  evidence для exact source/image; этот runbook сам по себе их не создаёт.
