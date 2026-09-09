# PRODUCTION-HTTP-RUNTIME-001 — штатный HTTP runtime и deployment migrations

## Простыми словами

Оператор запускает один образ FMonitor как nginx, PHP-FPM или CLI. Web напрямую
подключается к подготовленной MariaDB без DDL и сохраняет все текущие составные
маршруты. Подготовка private storage и migration catalogue выполняются отдельными
deployment-командами; health и перезапуск не создают domain facts.

Срез не выполняет TLS termination, admin bootstrap, import, worker/jobs, переключение
рабочего стенда или изменение продуктовых правил/HTML.

## 1. Actor и публичные seams

- Actor deployment: авторизованный оператор запускает `prepare`, canonical
  `migrate`, read-only `check`, web/app containers и их остановку.
- Actor HTTP: существующий пользователь вызывает nginx endpoint; nginx передаёт
  запрос PHP-FPM front controller, который делегирует `rapid-pilot/router.php` и
  существующим decorators/application owners.
- Публичные outcomes: exit code/ограниченная CLI diagnostic, HTTP status/headers/body,
  `/health/live`, `/health/ready`, сохранённые данные после restart.
- MariaDB и private files сохраняют существующих владельцев facts/history. Runtime
  composition и deployment tools не становятся владельцами domain state.

## 2. Exact production configuration

До DB access и приёма traffic MUST быть заданы и проверены:

- `FMONITOR_DB_HOST`, `FMONITOR_DB_PORT` (decimal 1..65535),
  `FMONITOR_DB_NAME`, `FMONITOR_DB_USER`, непустой injected external secret
  `FMONITOR_DB_PASSWORD`;
- `FMONITOR_PROCESS_TABLE_PREFIX` и `FMONITOR_LEGACY_TABLE_PREFIX`: existing
  canonical prefix syntax/length; для этого composite runtime значения MUST быть exact равны;
- `FMONITOR_SESSION_STATE_ROOT`, непустой `FMONITOR_SESSION_INSTANCE`,
  `FMONITOR_ARTIFACT_STORAGE_ROOT`, `FMONITOR_ORIGINAL_DB_PASSWORD_FILE`,
  `FMONITOR_ORIGINAL_SAFE_LOG_FILE`;
- `FMONITOR_TRUSTED_REQUEST_HOST` как один canonical Host и
  `FMONITOR_TRUSTED_REQUEST_SCHEME` как exact `http` либо `https`.

Production MUST игнорировать aliases/defaults и MUST NOT читать `FMONITOR_DEMO_*`,
active manifest, generation metadata или вычислять prefix из checkout/path. DB
connection идёт прямо на configured host/port без `socat`.

Password MUST NOT попадать в repository, filesystem кроме explicit original
credential file, HTTP/health result или log. Любая invalid/absent config возвращает
fail-closed outcome без secret/path value и до DB/domain/filesystem mutation.

## 3. Private storage preparation

`bin/fmonitor2-runtime-prepare.php` — единственный startup-related seam, которому
разрешено создавать runtime paths. Он MUST:

1. создать только отсутствующие session/artifact directories и safe-log parent с
   mode `0700` либо явно заданным canonical `0750`, владельцем effective prepare/runtime UID/GID;
2. создать отсутствующий safe log mode `0600` и отсутствующий
   `FMONITOR_ORIGINAL_DB_PASSWORD_FILE` mode `0600` того же effective owner, записав в него exact
   injected `FMONITOR_DB_PASSWORD` без вывода значения;
3. на repeat подтвердить exact regular type/owner/mode и exact password content,
   не переписывая content;
4. отвергнуть symlink, non-regular/wrong owner/wrong mode/content mismatch без
   chmod/chown/replacement существующего объекта.

Web, PHP-FPM startup, migrate и health/check MUST NOT выполнять prepare. Production
image фиксирует effective UID/GID `10001:10001` и MAY содержать empty mount paths с
этим ownership для volume initialization; native test/dev invocation проверяет
владельца своего effective UID/GID и не требует root/chown.

## 4. HTTP routing, trust и authorization

Nginx MUST слушать port 8080 внутри container; compose публикует `8093` по умолчанию.
PHP-FPM MUST слушать `php:9000` только во внутренней network без host publication.
Оба процесса работают foreground как UID/GID 10001.

Nginx MUST отвергать missing, duplicate и malformed raw Host своим HTTP parser до
PHP. Для синтаксически допустимого Host `public/runtime.php` MUST сравнить exact
значение с configured trusted Host до composite/application access. Client MUST NOT
устанавливать/переопределять FastCGI variable `FMONITOR_TRUSTED_REQUEST_HOST`;
forwarded Host и forwarded scheme не являются authority. Trusted scheme задаётся
только deployment config. Public static allowlist доступен напрямую;
произвольные PHP/source/private paths недоступны.

Все application requests MUST идти через `public/runtime.php` в существующий
`rapid-pilot/router.php` и decorators. Для известных routes сохраняются method,
URI/query, body, cookies, authorization/CSRF context, status, headers, redirects,
HTML/assets; unknown /pilot/* route возвращает current `404`. Unauthorized/invalid-CSRF POST
не создаёт success fact/audit. Новая domain logic или второй route owner запрещены.

## 5. Runtime без DDL и bootstrap

Web и ordinary CLI runtime principals MUST иметь только необходимые read/DML права.
Startup, readiness и любой достижимый GET/POST, включая
`PilotE2ECoordinator` → checklist sync, MUST NOT выполнять CREATE/ALTER/DROP.
Отсутствующая/несовместимая canonical schema даёт not-ready/infrastructure outcome,
а не runtime repair.

Production startup MUST NOT запускать docker/demo bootstrap, создавать admin user
или другие identity/domain facts. Initial admin provisioning — отдельная
авторизованная application operation.
Advisory lock не является authorization barrier: ordinary HTTP/readiness MUST NOT
вызывать migration seam или `GET_LOCK`; запрет schema mutation обеспечивают
отдельный deployment seam и отсутствие DDL privileges у runtime principal.

## 6. Canonical migration command and lock

`bin/fmonitor2-migrate.php` запускается отдельно под migration principal с DDL.
Инфраструктурный `MariaDbMigrationLock` MUST оборачивать неизменённый public signature
`CanonicalMigrationApplication::run` одним connection-scoped MariaDB advisory lock:

- deterministic bounded lock name включает stable namespace, database и catalogue identity;
- `GET_LOCK(..., 0)` выполняется до первого catalogue preflight/read и немедленно отказывает при занятом lock;
- result `1` допускает ordered catalogue, `0` даёт distinct busy nonzero exit без
  preflight/mutation, `NULL` даёт infrastructure nonzero exit;
- lock удерживается через весь catalogue; первый failure прекращает последующие migrations;
- `RELEASE_LOCK` выполняется в `finally`; migration error сохраняет первенство,
  release anomaly после успешного catalogue делает run unsuccessful;
- connection loss допускает MariaDB release.

Lock не заменяет per-migration read-only preflight, compatible no-op и fail-closed
behavior и не обещает транзакционность DDL catalogue. Повтор completed catalogue
MUST быть no-op с exit 0 и сохранять data/history/ambient objects.

## 7. Canonical v22 legacy projection

После current frontier v21 catalogue MUST зарегистрировать v22 через существующий
`PilotLegacyObjectSchemaMigration::apply`. На clean DB v22 создаёт exact prefixed
`fm_maintable`. Известную populated 10-column predecessor shape v22 additive
обновляет exact семью недостающими columns, сохраняя каждый row/id и ambient object.
Exact target shape — no-op. Иная shape даёт conflict до mutation. Generation
sentinel не требуется; `id` остаётся вручную назначаемым primary key.

Под совместимой схемой здесь понимаются заданные имена, порядок, типы и nullable
columns, единственный PRIMARY KEY по `id`, InnoDB и utf8mb4 для таблицы и строковых
columns. Дополнительные non-unique indexes и совместимые utf8mb4 collations допустимы;
дополнительные ограничения уникальности не должны менять допустимость данных.
Та же проверка storage и ключа выполняется до ALTER известного predecessor.

## 8. Health, graceful stop и restart

`GET /health/live` MUST отражать живой nginx process без session/domain write.
`GET /health/ready` MUST выполнить read-only проверку exact config, direct DB,
canonical schema и доступа UID10001 к обязательным paths без раскрытия secrets.
Compose health использует readiness; DB/schema/storage failure возвращает non-success.

Services `web` и `php` MUST использовать `stop_signal: SIGQUIT` и
`stop_grace_period: 60s`. Nginx/PHP-FPM прекращают принимать новые requests, дают
active bounded request завершиться и выходят со всеми children до timeout.

После записи domain fact/history, session и private artifact restart web/php с теми
же database/state volumes MUST сохранить bytes, ownership и authorized read. Startup
и health polling не добавляют session/domain/audit facts.

## 9. Image and compose contract

Один exact application image digest MUST содержать один source/assets revision,
nginx, PHP-FPM и CLI. Compose services: `db`, one-shot `migrate`, `php`, `web`;
`prepare` доступен только в deployment profile. Default FPM command — foreground;
web command — `nginx -g 'daemon off;'`. Ни один service не использует `php -S`,
`socat`, demo manifest/generation/bootstrap.

Database и state являются persistent volumes. State root —
`/home/fmonitor/.local/state/fmonitor2`, artifact root — private subdirectory.
Original credential file монтируется/готовится по
`FMONITOR_ORIGINAL_DB_PASSWORD_FILE` (production compose path
  `/run/fmonitor-secrets/database-password`); safe log — private
  `/home/fmonitor/.local/state/fmonitor2/log/original-safe.jsonl`. Runtime DML user provisioning и root DB
credential не выполняются application startup.

## 10. Acceptance matrix

Для параллельных запросов действует additive extension
`PILOT-SESSION-STORAGE-001`: native WOULD_BLOCK отличается от постоянного отказа;
`start(existingId)` повторяет краткую конкуренцию до 2 секунд и читает bytes после
получения lock. Запись/регенерация с заранее построенным payload остаётся
fail-closed после конкуренции. Формат сессии и полномочия не меняются.

- Invalid config/prefix/Host/path fails before DB/domain mutation and leaks no secret.
- Prepare first/repeat/unsafe cases prove exact owner/mode/content preservation.
- Real FPM login, authorized GET, CSRF POST, assets and 404 match composite oracle.
- DML-only principal passes readiness and ordinary GET/POST with zero DDL; missing
  schema stays not-ready.
- Two real migration processes prove one winner, loser no-preflight/no-mutation,
  bounded busy outcome and lock release after success/failure.
- v22 proves clean, populated predecessor upgrade, target repeat and incompatible
  zero-mutation cases.
- Health polling creates no session; DB/schema/storage failures affect readiness.
- Active request drains on SIGQUIT within 60s without orphan process.
- Restart preserves fact/history/session/artifact and UID/GID compatibility.
- Focused checks, `make architecture-check`, independent Gate3/Gate5 and one full
  CI `make test` MUST pass before production-readiness claim.
