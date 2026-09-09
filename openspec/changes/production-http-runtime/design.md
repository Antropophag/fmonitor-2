## Context

См. proposal.md и ADR0002. Текущий pilot image основан на PHP CLI: entrypoint
поднимает `socat`, сам запускает migrations/bootstrap и затем `php -S`; DB endpoint
и prefix связаны с demo generation. При этом `rapid-pilot/router.php` уже является
совместимым composite adapter к native application owners и должен остаться
поведенческим оракулом. Canonical runner существует, но весь catalogue пока не
защищён одним deployment lock.

## Goals / Non-Goals

**Goals:** выделенный production composition root; один immutable image для HTTP и
CLI; прямой fail-closed config; отдельный locked migration lifecycle; наблюдаемые
health/restart/graceful-stop guarantees; сохранение маршрутов и UID/GID 10001.

**Non-Goals:** новый domain owner, изменение route/UI semantics, встроенный TLS,
оркестрация worker/scheduler, изменение migration SQL ради runtime, удаление
исторического pilot contour и автоматическое переключение данных владельца.

## Decisions

### 1. Production runtime имеет отдельный deploy tree

Добавить `deploy/runtime/Dockerfile`, `compose.yaml`, `nginx.conf`, `php-fpm.conf`
и `php.ini`. Image собирает тот же PHP source, pinned TCPDF и pinned public
`shlz-ui` assets, что и CLI; image содержит PHP-FPM, nginx и CLI. Compose запускает
из exact одного image отдельные services: `web` как `nginx -g 'daemon off;'` под
UID 10001 на 8080, `app` как `php-fpm -F`, а migration/prepare/check как one-shot
CLI. FPM слушает `php:9000` только во внутренней network и не публикует host port.
Web/app задают `stop_signal: SIGQUIT` и `stop_grace_period: 60s`, используя native
graceful process handling без shell supervisor. Generated root Dockerfile/compose
остаются pilot compatibility artifacts.

Альтернатива — доработать CLI image и оставить `php -S` — не выполняет ADR0002 и
не даёт штатных process/health semantics. Отдельные web/CLI images создавали бы
риск version skew.

### 2. Nginx передаёт PHP только одному front controller

`public/runtime.php` — тонкий composition adapter: нормализует web-server request и
делегирует существующему `rapid-pilot/router.php`. Nginx обслуживает только
разрешённые public static assets и все application routes направляет в
`runtime.php`; произвольные `.php`, private artifacts и source недоступны извне.
FastCGI сохраняет method, URI/query, body, cookies, content headers, remote address
и trusted authorization variables. Nginx/front controller сравнивает incoming Host
с explicit trusted host, не принимает forwarded host как authority и передаёт
trusted host/scheme согласованно с PilotHttpRequestFactory/LocalAuth. В adapter не
добавляются domain rules.

Альтернатива — переписать route map под nginx/FPM — могла бы разойтись с работающим
composite behavior и создать второго владельца маршрутов.

### 3. RuntimeConfiguration единожды валидирует explicit config

`app/Runtime/RuntimeConfiguration.php` читает обязательные direct `FMONITOR_DB_*`,
где password injected как external secret, равные
`FMONITOR_PROCESS_TABLE_PREFIX`/`FMONITOR_LEGACY_TABLE_PREFIX`, session root/instance,
artifact root, original credential file, safe log path и trusted host/scheme,
проверяет port/prefix/path constraints и предоставляет immutable values composition
roots. Password допускается только как injected secret value и никогда не попадает
в health/log result. Production runtime не читает `FMONITOR_DEMO_*`, active manifest
или generation metadata. Все DB consumers получают прямой endpoint.

Владение facts не меняется: app modules владеют DML/history, MariaDB — persistence,
runtime composition только соединяет adapters. Architecture checker запрещает
demo/generation inference, runtime DDL и dependency app/domain → Runtime/rapid-pilot.

### 4. Deployment lock оборачивает CanonicalMigrationApplication

Существующий `bin/fmonitor2-migrate.php` остаётся публичной CLI-командой, а signature
`CanonicalMigrationApplication::run` сохраняется. Инфраструктурный статический
`MariaDbMigrationLock` получает lock перед любым
catalogue preflight. Lock name строится из стабильной namespace, database identity
и catalogue identity и укладывается в лимит MariaDB; timeout — fixed `0` для немедленного отказа.
`GET_LOCK` result `1` допускает run, `0` даёт distinct busy result, `NULL` —
infrastructure failure. Один connection удерживает lock через весь ordered catalogue.
`RELEASE_LOCK` вызывается в `finally`; исходная migration error не затирается ошибкой
release, но release anomaly после успешного catalogue делает command unsuccessful.

Lock сериализует runners, не заменяя per-migration compatible preflight/no-op и не
обещая общую транзакционность DDL. Production web principal отделён от migration
principal и не имеет DDL. Альтернативы — lock на каждую migration или startup lock —
оставляют interleaving/DDL ownership у runtime; filesystem lock не координирует hosts.

### 5. Storage preparation и readiness — отдельные CLI seams

`bin/fmonitor2-runtime-prepare.php` — единственный deployment seam для создания
private paths. Он создаёт отсутствующие directories как 0700/0750 и credential/log
как 0600 с effective prepare/runtime UID/GID (10001:10001 в production image), а существующие symlink/type/owner/mode mismatch отвергает
без исправления или перезаписи. Image содержит owned empty mount points для корректной
инициализации volumes. `bin/fmonitor2-runtime-check.php` использует общий config
parser и выполняет read-only DB/schema/storage readiness. Prepare создаёт отсутствующий
`FMONITOR_ORIGINAL_DB_PASSWORD_FILE` mode 0600 UID10001 из injected
`FMONITOR_DB_PASSWORD`; существующий file обязан совпасть, а symlink/type/owner/mode/
content mismatch отклоняется без rewrite. Secret/value/path не включаются в
exception/log/response. Ни web startup, ни check
не выполняют prepare/bootstrap; создание первого admin остаётся будущим #27/отдельной
явно авторизованной операцией.

### 6. Catalogue frontier получает additive v22

После текущего v21 зарегистрировать v22 adapter для существующей
`PilotLegacyObjectSchemaMigration::apply`, чтобы clean production catalogue создавал
нужную composite router таблицу `fm_maintable` без вызова docker bootstrap и без
generation sentinel. Adapter сохраняет target table, additive обновляет известную
populated 10-column predecessor shape семью columns без потери rows/ids и fail-closed
до mutation при иной форме. Tests, фиксирующие frontier 21, обновляются на 22 вместе
с регистрацией, без grandfather exception.

### 7. Health разделён на liveness и readiness

Nginx отдаёт `/health/live` только после наличия живого process tree. `/health/ready` проходит
PHP composition: валидный config, прямое DB connection и read-only canonical schema
check, а также доступ runtime UID к обязательным storage paths. Endpoint не создаёт
сессию, не пишет domain/audit fact и не раскрывает config. Compose healthcheck
использует readiness. Graceful stop сначала прекращает новый traffic, затем даёт
FPM bounded timeout и завершает process tree.

## Risks / Trade-offs

- [Одинаковый image запускает разные process roles] → exact digest assertion,
  явные compose commands и native foreground processes без shell supervisor.
- [Старые routes зависят от CLI-server globals] → characterization через реальные
  login/CSRF GET/POST/assets/404 до и после FPM adapter.
- [Advisory lock остаётся connection-scoped] → один выделенный connection на run,
  deterministic concurrent subprocess test и `finally` release.
- [Migration DDL частично применена при ошибке] → сохранять текущие preflight/no-op,
  прекращать catalogue на первом failure; destructive rollback не выполнять.
- [Existing volume permissions различаются] → UID/GID 10001 по умолчанию и явный
  startup/readiness failure; автоматический recursive chown запрещён.

## Migration Plan

1. Зафиксировать исполняемый RED для FPM route compatibility, direct config,
   DML-only runtime, whole-catalogue lock и graceful stop; получить Gate3 review.
2. Собрать image и config, проверить services `db`, `migrate`, `php`, `web` на
   изолированной MariaDB/volumes; `prepare` доступен только в deployment profile,
   migrations выполняются one-shot до допуска web readiness. Web публикует 8093 по
   умолчанию; state volume монтируется в `/home/fmonitor/.local/state/fmonitor2`,
   artifacts и safe log — его private subdirectories, DB secret —
   `/run/fmonitor-secrets/database-password` через `FMONITOR_ORIGINAL_DB_PASSWORD_FILE`.
3. Проверить restart с сохранёнными facts/session/artifact и browser smoke основных
   composite routes. Получить независимый Gate5 review и выполнить один полный CI.
4. Развёртывание на рабочем contour — отдельное owner-authorized operation с backup,
   exact image digest и сохранением volumes. Откат переключает web image на прежний
   runtime; additive canonical schema и append-only данные не удаляются.
