# YII2-PRODUCTION-IMAGE-001 — production image без rapid-pilot

## 1. Scope и seam

Actor — оператор, собирающий immutable production runtime image. Public seam —
`docker build -f deploy/runtime/Dockerfile .` и процессы внутри полученного image.
Source oracle: принятые `YII2-PRODUCTION-WEB-CUTOVER-001`,
`YII2-CANONICAL-MIGRATIONS-001`, `YII2-JOBS-CONSOLE-001` и locked build recipe.

Срез удаляет временный runtime из production artifact, но не удаляет repository
demo/oracle и не переключает stand. Schema, данные, сессии, файлы, jobs и
authorization не изменяются.

## 2. A1 — artifact closure

Canonical template и generated production Dockerfile MUST NOT копировать,
исполнять либо ссылаться на `rapid-pilot`. Fresh-built image MUST NOT содержать
`/workspace/fmonitor-2/rapid-pilot` и MUST содержать executable `bin/yii`,
`public/runtime.php`, Yii configuration, application code, runtime assets и locked
Composer vendor. `tests`, `reviews`, `specs`, `docs`, `tools`, `.git`, `.local` и
secret-like repository files (`.env*`, `auth.json`, `*.dump`, `*.sql.gz`,
`*.bak`, `*.key`, `*.pem`, `*.p12`, `*.pfx`, `*.pk8`, `*.crt`, `*.cer`,
`*.der`, `*.p7b`, `*.p7c`, `*.msg`) MUST NOT попасть в image.

## 3. A2 — executable parity

Image MUST работать от uid/gid 10001 для штатного runtime. Без DB configuration
`php bin/yii schema-migrate/run --interactive=0` MUST вернуть exit 64, stdout
`{"ok":false,"reason":"CONFIGURATION_INVALID"}\n` и пустой stderr.

`GET /health/live` через packaged `public/runtime.php` MUST вернуть accepted Yii2
live JSON. Отсутствие каталога `rapid-pilot` является fail-closed гарантией рядом
с этой executable проверкой.

## 4. A3 — invariants и rejected cases

Build failure, отсутствующий Yii/vendor asset или изменение safe CLI/live output
является failure, не UNKNOWN/GREEN. Проверка не использует production systems,
не выполняет DDL/DML и не создаёт domain/audit facts. Повторная сборка одного
source должна давать те же проверяемые paths и contracts; Docker layer digest не
нормируется.

## 5. Done

Intended RED падает на текущих `COPY rapid-pilot`/verifier. После independent
Gate 3 отдельный executor делает минимальную recipe correction, focused contract
и renderer проходят, Gate 5 независимо одобряет exact source, затем один полный
CI. Deployment и полное закрытие №76 остаются отдельными gates.
