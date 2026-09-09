# Production runtime

Этот каталог задаёт штатный runtime: один application image запускается отдельными
services `php`, `web`, deployment CLI и опциональными jobs services. Он не запускает bootstrap, import или DDL
из HTTP startup. Полная production validation пока не завершена; рабочий стенд этим
контуром автоматически не заменяется.

Обязательные значения задаются оператором вне repository: `FMONITOR_DB_NAME`,
`FMONITOR_DB_USER`, `FMONITOR_DB_PASSWORD`, `FMONITOR_MIGRATION_DB_USER`,
`FMONITOR_MIGRATION_DB_PASSWORD`, равные process/legacy prefixes, session instance,
trusted host и scheme. Для reviewed exact image задайте `FMONITOR_RUNTIME_IMAGE`
digest reference; без него Compose использует локальный `fmonitor2-runtime`.

Порядок и rollback описаны в
[`docs/operations/production-runtime-runbook.md`](../../docs/operations/production-runtime-runbook.md).
Короткая локальная проверка конфигурации:

```sh
docker compose --file deploy/runtime/compose.yaml config --quiet
docker compose --file deploy/runtime/compose.yaml up --detach --wait db
docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm prepare
docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm migrate
docker compose --file deploy/runtime/compose.yaml --profile deployment run --rm \
  -e FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD --entrypoint php prepare \
  bin/fmonitor2-provision-initial-admin.php --email owner@shlz.ru
docker compose --file deploy/runtime/compose.yaml up --detach --wait php web
```

Web публикуется на `127.0.0.1:${FMONITOR_HTTP_PORT:-8093}`. PHP-FPM остаётся только
во внутренней Compose network. Health: `/health/live` и `/health/ready`.
Provisioning выполняется один раз после migrations. Exact повтор с теми же
email/password безопасен; существующих пользователей command не повышает и не repair-ит.

Профиль `jobs` включает `jobs-worker` и `jobs-scheduler` из того же image. Без
явного профиля они не стартуют и портов не публикуют. Worker получает read-only
Bitrix token/optional CA, scheduler этих credentials не получает. Настройка,
health, ручной retry и остановка перед backup — в разделе7 единого runbook.
