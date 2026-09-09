## 1. Contract and independent RED

- [x] 1.1 Создать нормативную `specs/PRODUCTION-HTTP-RUNTIME-001.md` из delta contracts: actors/seams, exact mandatory config, trusted Host/scheme, равные prefixes, private path modes, v22 predecessor outcomes, lock timeout/busy/release и graceful `SIGQUIT`; сверить с ADR0002 и получить отсутствие UNKNOWN semantics
- [x] 1.2 Добавить исполняемые RED tests на публичных seams: config rejection до DB access, prepare idempotency/symlink-mode-owner refusal, read-only readiness, FPM composite login/CSRF GET/POST/assets/404, DML-only route без достижимого DDL, v22 clean/predecessor/target/conflict/repeat, whole-catalogue lock concurrency/failure release и 60s graceful stop; сохранить команды и intended failures в `reviews/tests/PRODUCTION-HTTP-RUNTIME-001.md`
- [x] 1.3 Получить независимый Gate3 review нормативной spec, tests и RED evidence до production implementation; reviewer фиксирует reviewed source и `APPROVED` либо возвращает конкретные изменения

## 2. Explicit configuration and private storage

- [x] 2.1 Реализовать `app/Runtime/RuntimeConfiguration.php` с единым fail-closed parser всех mandatory values, равенством process/legacy prefixes, trusted Host/scheme, injected external `FMONITOR_DB_PASSWORD` и exact `FMONITOR_ORIGINAL_DB_PASSWORD_FILE` (absolute non-symlink regular 0600 UID10001, content совпадает с injected secret); никогда не логировать value/path, проверить valid config и каждый invalid class до DB/filesystem mutation
- [x] 2.2 Реализовать `bin/fmonitor2-runtime-prepare.php`: создать только отсутствующие canonical directories/files с 0700/0750, 0600 и effective prepare/runtime UID/GID (10001:10001 в production image), отвергать symlink/type/owner/mode mismatch без исправления/content rewrite; проверить native-current-UID, production10001, first run, replay и unsafe fixtures
- [x] 2.3 Реализовать `bin/fmonitor2-runtime-check.php` и HTTP readiness как read-only проверки config, direct MariaDB canonical schema и storage; проверить отсутствие session/domain/audit writes, DDL, bootstrap и secret leakage при success/failures
- [x] 2.4 Проверить отсутствие DDL в production HTTP/обычных CLI paths (`ChecklistSync::ensureSchema` уже был read-only в baseline), сохранив явный readiness failure; проверить основные GET/POST под DML-only principal и усилить architecture checker без расширения baseline

## 3. Locked canonical migrations

- [x] 3.1 Добавить инфраструктурный статический `MariaDbMigrationLock` вокруг сохраняющего signature `CanonicalMigrationApplication::run`: один bounded `GET_LOCK` до первого preflight, весь ordered catalogue на одном owning connection, distinct busy/infrastructure outcomes и `RELEASE_LOCK` в `finally`; проверить winner/loser subprocess, отсутствие loser preflight/mutation, success/failure release и сохранение исходной migration error
- [x] 3.2 Зарегистрировать canonical v22 через существующий `PilotLegacyObjectSchemaMigration::apply`; проверить clean create, additive upgrade известной populated 10-column shape семью columns, target no-op/repeat, incompatible zero-mutation и сохранение rows/ids/ambient objects без generation sentinel
- [x] 3.3 Обновить exact frontier/catalogue assertions с v21 на v22 и проверить, что `bin/fmonitor2-migrate.php` с прямой config возвращает machine-readable success/ненулевые failure outcomes, ordinary HTTP/readiness не вызывает migration seam/`GET_LOCK`, а runtime principal не может выполнить DDL

## 4. Production HTTP image and route adapter

- [x] 4.1 Добавить `public/runtime.php`, делегирующий существующему `rapid-pilot/router.php` и decorators без domain logic; проверить method/URI/query/body/cookies/auth/CSRF/status/headers/redirect/body/assets и 404 parity реальными FPM requests
- [x] 4.2 Добавить `deploy/runtime/Dockerfile`, `nginx.conf`, `php-fpm.conf`, `php.ini` и owned empty mount paths; проверить один image digest/source/assets для nginx, FPM и CLI, non-root UID/GID 10001, nginx:8080 и непубличный php:9000
- [x] 4.3 Добавить `deploy/runtime/compose.yaml` с services `db`, `migrate`, `php`, `web` и `prepare` в deployment profile из одного app image, external DB secret `/run/fmonitor-secrets/database-password`, database/state volumes, artifacts внутри private state root, web port 8093 по умолчанию, direct MariaDB и без `php -S`, `socat`, demo manifest/generation/bootstrap; проверить rendered compose config и отсутствие публикации FPM port
- [x] 4.4 Настроить Host allowlist и trusted scheme на nginx/front-controller boundary без доверия forwarded Host; проверить accepted canonical host, rejected spoof/mismatch и корректные auth redirects/origin
- [x] 4.5 Настроить `/health/live`, `/health/ready` и native foreground process lifecycle с `stop_signal: SIGQUIT`, `stop_grace_period: 60s`; проверить DB/schema/storage failure readiness, no-session health polling, active-request drain и отсутствие orphan nginx/FPM children

## 5. Integration, restart and Done

- [ ] 5.1 На изолированной MariaDB выполнить prepare → locked migrate → runtime check → web start, пройти headless browser smoke существующих composite routes и сохранить source/image digest, commands, console/network failures и focused results
- [x] 5.2 Сохранить domain fact/history, session и private artifact, перезапустить web/app с теми же DB/volumes и доказать их чтение/authorization и совместимое ownership; existing generated pilot Dockerfile/compose и текущий stand не переключать
- [x] 5.3 Выполнить syntax/config checks, `git diff --check`, focused installation/runtime suites и `make architecture-check`; затем получить независимый Gate5 review spec/tests/production diff/evidence с явным verdict
- [ ] 5.4 Запустить один полный CI `make test` по согласованной матрице и зафиксировать результат; не дублировать local-full и не заявлять production readiness без GREEN
- [ ] 5.5 Сверить Done: штатный nginx→FPM HTTP, один image для web/CLI, direct explicit config, external secrets/private storage, DML-only runtime без bootstrap/DDL, separate whole-catalogue lock и v22, route parity, health/graceful stop/restart persistence, Gate3/Gate5 и CI завершены; deployment рабочего contour и admin provisioning остаются отдельными авторизованными операциями
