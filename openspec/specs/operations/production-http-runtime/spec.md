# production-http-runtime Specification

## Purpose

Задаёт наблюдаемый production HTTP runtime, который запускает текущий FMonitor из
явной конфигурации, сохраняет совместимость маршрутов и безопасно перезапускается.

## Requirements

### Requirement: Краткая конкуренция за чтение сессии не вызывает отказ
Native session port SHALL отличать `WOULD_BLOCK` по третьему аргументу `flock`
от постоянного native false/warning/exception. `start(existingId)` SHALL повторять
только `WOULD_BLOCK` до прежнего monotonic deadline 2 секунды и после получения
lock читать актуальные committed bytes. Поведение contended write/regenerate
SHALL оставаться fail-closed: ожидание не делает старый payload актуальным.

#### Scenario: Параллельное чтение после краткого удержания lock
- **WHEN** другой процесс освобождает session lock в пределах двух секунд после подтверждённой неудачной попытки чтения
- **THEN** public start возвращает OK и актуальный payload без изменения bytes

#### Scenario: Постоянный отказ или истёкший deadline
- **WHEN** native primitive сообщает постоянный отказ либо lock остаётся занят после deadline
- **THEN** операция сохраняет существующий typed failure или LOCK_TIMEOUT, не изменяя сессию

### Requirement: Один образ исполняет HTTP и CLI одной версии
Production image SHALL содержать один exact source и собранные assets и SHALL
запускать из него штатный HTTP runtime либо выбранную оператором CLI-команду.
HTTP SHALL обслуживаться web server через PHP-FPM без `php -S` и `socat`.

#### Scenario: Web и CLI имеют один source
- **WHEN** оператор запускает web service и CLI-команду из одного image digest
- **THEN** оба процесса используют один source/assets revision, а HTTP принимает запрос через web server и PHP-FPM

### Requirement: Runtime использует явную прямую DB-конфигурацию
Runtime SHALL требовать `FMONITOR_DB_HOST`, `FMONITOR_DB_PORT`,
`FMONITOR_DB_NAME`, `FMONITOR_DB_USER`, `FMONITOR_DB_PASSWORD` как внешний secret и явные допустимые
`FMONITOR_PROCESS_TABLE_PREFIX`/`FMONITOR_LEGACY_TABLE_PREFIX`, которые в этом
production composite SHALL быть равны. Он SHALL также требовать session root и
instance, artifact root, original DB password file, safe log path и trusted
host/scheme, SHALL проверить их до приёма пользовательского трафика и SHALL
подключаться прямо к указанному MariaDB endpoint. Он SHALL NOT выводить endpoint
или prefix из demo manifest, generation metadata либо локального TCP proxy.
`FMONITOR_DB_PASSWORD` SHALL передаваться существующим DB consumers без записи в
repository/filesystem/log или response. `FMONITOR_ORIGINAL_DB_PASSWORD_FILE` SHALL
быть absolute non-symlink regular file mode `0600` владельца UID 10001; его content
SHALL совпадать с injected secret. Path/content SHALL NOT появляться в response,
health output или log.

#### Scenario: Допустимая конфигурация
- **WHEN** оператор передаёт все обязательные значения, port 1..65535 и допустимые prefixes
- **THEN** runtime подключается прямо к этому host/port/name под указанным runtime principal и становится ready после проверки schema

#### Scenario: Неполная или некорректная конфигурация
- **WHEN** отсутствует обязательное значение, port вне диапазона или prefix не проходит установленный формат/длину
- **THEN** runtime завершается либо остаётся not-ready с диагностикой без значения password и не принимает прикладной трафик

#### Scenario: Небезопасный original credential file
- **WHEN** `FMONITOR_ORIGINAL_DB_PASSWORD_FILE` относительный, отсутствует, является symlink/non-regular, имеет не `0600`, другого owner или content не совпадает с injected secret
- **THEN** config fail-closed до DB access без вывода path/content/secret

#### Scenario: Prefixes расходятся
- **WHEN** process и legacy prefix различаются
- **THEN** production composite fail-closed до DB access и не принимает трафик

#### Scenario: Trusted request boundary
- **WHEN** request имеет Host либо scheme вне явно настроенных trusted значений
- **THEN** runtime отвергает его и не строит redirect/origin из недоверенного заголовка

### Requirement: Production front controller сохраняет composite routes
Production HTTP seam SHALL передавать запросы существующему `rapid-pilot/router.php`
и его текущим decorators и
SHALL сохранять текущие методы, paths, query/body, cookies, remote-user/auth/CSRF
контекст, status, headers, redirects, HTML и assets для всех известных pilot routes.
Неизвестный маршрут SHALL сохранять текущий `404` outcome.

#### Scenario: Обычный составной маршрут
- **WHEN** авторизованный пользователь вызывает существующий GET либо POST route через production web endpoint
- **THEN** запрос достигает того же application owner и возвращает совместимый status/headers/body без второго domain implementation

#### Scenario: Защищённое изменение
- **WHEN** пользователь без полномочия либо с отсутствующим/неверным CSRF вызывает существующий изменяющий route
- **THEN** production runtime сохраняет текущий серверный отказ и не добавляет domain fact или audit event успеха

#### Scenario: Неизвестный маршрут
- **WHEN** клиент запрашивает path вне composite route catalogue
- **THEN** endpoint отвечает `404` и не выполняет state change

### Requirement: Web runtime не изменяет schema и сохраняет историю
Web и обычные CLI runtime principals SHALL работать без DDL privileges. Startup,
health checks и пользовательские requests, включая достижимые checklist sync paths,
SHALL NOT создавать, изменять или
удалять schema. Restart SHALL сохранять MariaDB facts, append-only history,
sessions и private artifacts в подключённых persistent stores.

#### Scenario: Работа под DML-only principal
- **WHEN** canonical schema заранее применена и runtime principal имеет только необходимые DML/read privileges
- **THEN** readiness и обычные GET/POST проходят без DDL statement

#### Scenario: Schema не готова
- **WHEN** обязательная canonical schema отсутствует или несовместима
- **THEN** readiness остаётся неуспешной, пользовательский трафик не объявляется готовым и runtime не пытается исправить schema

#### Scenario: Перезапуск runtime
- **WHEN** после сохранения фактов, сессии и файла оператор останавливает и снова запускает services с теми же persistent stores
- **THEN** факты и append-only history читаются без изменения, сессия и файл остаются доступны согласно текущим authorization rules

### Requirement: Health и остановка отражают способность обслуживать трафик
Runtime SHALL предоставлять liveness на `/health/live` и readiness на
`/health/ready` через web-server endpoint.
Readiness SHALL быть успешной только при корректной конфигурации, доступной DB и
готовой canonical schema. Web и app containers SHALL использовать `SIGQUIT` как
stop signal; nginx и PHP-FPM SHALL прекратить приём новых запросов, дать активным
запросам ограниченное время завершиться, завершить children и выйти без orphan
process в пределах 60 секунд.

#### Scenario: Healthy runtime
- **WHEN** процессы живы, конфигурация корректна, DB доступна и schema готова
- **THEN** liveness и readiness возвращают успешный status без раскрытия secrets

#### Scenario: Потеря DB или schema readiness
- **WHEN** DB недоступна либо readiness schema не проходит
- **THEN** readiness возвращает неуспешный status, а liveness продолжает отражать состояние процессов

#### Scenario: Graceful stop
- **WHEN** web/app containers получают настроенный `SIGQUIT` во время активного ограниченного HTTP-запроса
- **THEN** новые запросы перестают приниматься, активный запрос получает установленное grace time, все nginx/FPM children завершаются и containers выходят в пределах stop timeout

### Requirement: Persistent file identity совместима с текущим контуром
Production processes SHALL по умолчанию работать как UID/GID `10001:10001` и SHALL
читать/писать разрешённые session/artifact paths существующего владельца без
рекурсивного изменения ownership при каждом startup.

#### Scenario: Existing owned storage
- **WHEN** подключён volume с файлами, принадлежащими `10001:10001`, и допустимыми permissions
- **THEN** runtime читает существующие данные и создаёт новые с совместимым ownership

#### Scenario: Storage недоступен
- **WHEN** обязательный persistent path нельзя прочитать или записать под runtime identity
- **THEN** readiness завершается отказом без изменения ownership и без потери существующих файлов

### Requirement: Private storage готовится только отдельной CLI-командой
Deployment operator SHALL иметь отдельную idempotent storage-preparation command,
которая создаёт отсутствующие session/artifact directories, original credential
file и safe log с ownership effective prepare/runtime UID/GID и canonical modes. Production
image фиксирует этот identity как 10001:10001. Команда SHALL отвергать symlink, wrong type,
wrong owner или permissive mode без перезаписи существующего content. HTTP startup
и readiness SHALL только проверять эти paths и SHALL NOT создавать или исправлять их.

#### Scenario: Первичная подготовка
- **WHEN** оператор запускает prepare command на отсутствующих разрешённых paths с injected `FMONITOR_DB_PASSWORD`
- **THEN** directories создаются с `0700` либо согласованным `0750`, original credential записывается этим secret, credential/log получают `0600`, ownership равен effective prepare/runtime UID/GID, и команда сообщает success

#### Scenario: Повтор подготовки
- **WHEN** все paths уже имеют canonical type/owner/mode
- **THEN** команда завершается успешно без изменения content

#### Scenario: Небезопасный существующий path
- **WHEN** path является symlink, имеет неверный type/owner или более широкие permissions
- **THEN** команда fail-closed, не заменяет и не chmod/chown этот path, а startup/readiness остаются неуспешными

### Requirement: Startup не выполняет bootstrap
Production web/CLI startup SHALL NOT запускать demo bootstrap, создавать admin user
или изменять identity/domain facts. Initial admin provisioning остаётся отдельным
явно авторизованным application operation.

#### Scenario: Чистый запуск после migrations
- **WHEN** canonical schema и storage готовы, но admin provisioning не выполнялся
- **THEN** runtime запускается без создания пользователя и без вызова demo bootstrap
