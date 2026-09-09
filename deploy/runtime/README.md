# Production runtime

Этот каталог задаёт кандидат #33: один application image запускается отдельными
services `php`, `web` и deployment CLI. Он не запускает bootstrap, import или DDL
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
docker compose --file deploy/runtime/compose.yaml up --detach --wait php web
```

Web публикуется на `127.0.0.1:${FMONITOR_HTTP_PORT:-8093}`. PHP-FPM остаётся только
во внутренней Compose network. Health: `/health/live` и `/health/ready`.
