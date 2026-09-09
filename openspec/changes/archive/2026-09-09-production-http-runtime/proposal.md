## Why

Срез `PRODUCTION-HTTP-RUNTIME-001` реализует назначенный владельцем #33: оператор
развёртывания должен запускать один exact source как штатный HTTP runtime и CLI,
без `php -S`, `socat`, demo-generation и миграций в процессе старта приложения.
Текущий Docker-контур смешивает bootstrap, DDL и HTTP и потому не даёт безопасного
production restart при сохранении данных.

## What Changes

- Добавить production image с PHP-FPM и веб-сервером; тот же image предоставляет
  HTTP и существующие CLI-команды одной версии source/assets.
- Добавить прямую обязательную конфигурацию host/port/name/user/password MariaDB,
  где `FMONITOR_DB_PASSWORD` поступает как внешний secret, а также
  равных process/legacy prefixes, session root/instance, artifact/original storage,
  `FMONITOR_ORIGINAL_DB_PASSWORD_FILE`, safe log и trusted host/scheme, не выводимую
  из pilot manifest или generation metadata.
- Выполнять canonical migrations отдельной deployment-командой под отдельными
  полномочиями и одним ограниченным MariaDB advisory lock на весь catalogue,
  включая preflight; web/CLI runtime не выполняют DDL.
- Сохранить существующий composite router и его маршруты через production front
  controller, включая текущие HTML/assets/auth/CSRF outcomes.
- Добавить readiness/liveness checks и штатное graceful quit веб-процессов,
  сохранив UID/GID 10001 и доступ к существующим persistent files.
- Добавить отдельные CLI для fail-closed runtime readiness и безопасной подготовки
  private storage; HTTP/startup не создают каталоги, credential или log.
- Зарегистрировать следующую canonical migration v22 для существующей legacy object
  projection (`fm_maintable`), сохраняя совместимые данные и отвергая конфликт.
- Оставить созданные генератором pilot Dockerfile/compose совместимым временным
  контуром; production runtime живёт в отдельном `deploy/runtime`.

Не входят: TLS termination, worker/job architecture, Bitrix calls, перенос или
сброс данных, изменение доменных правил и экранов, удаление rapid-pilot adapter,
автоматический production deploy или изменение текущего manual-pilot stand.

## Capabilities

### New Capabilities

- `operations/production-http-runtime`: запуск, конфигурация, health, graceful stop
  и route compatibility штатного HTTP runtime из единого image.
- `operations/locked-schema-migrations`: отдельный сериализованный запуск
  canonical migration catalogue без DDL у web/обычных CLI principals.

### Modified Capabilities

Нет.

## Impact

Источник-оракул — ADR0002, существующие `Dockerfile`, `compose.yaml`,
`rapid-pilot/router.php`, `app/PilotHttp/production-entrypoint.php` и
`bin/fmonitor2-migrate.php`. Целевые публичные seams: HTTP endpoint через web server
→ PHP-FPM → composite router и deployment CLI `bin/fmonitor2-migrate.php`.
Затрагиваются runtime composition/configuration, storage preparation/readiness CLI,
image/compose, FPM/web-server config, canonical v22, migration application и проверки installation/runtime. Persistent MariaDB
и artifact/session volumes остаются вне image; секреты поступают извне.
