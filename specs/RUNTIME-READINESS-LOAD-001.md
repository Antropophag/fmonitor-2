# RUNTIME-READINESS-LOAD-001 — bounded regular readiness

## Простыми словами

Регулярная проверка готовности подтверждает живое соединение с нужной MariaDB,
локальное storage и применимый результат полной startup-проверки, но не повторяет
сотни запросов schema fingerprints. Полная проверка остаётся обязательной после
миграций и до запуска PHP/web. Срез не меняет пользовательские функции,
интеграции, бизнес-данные или правила предметных операций.

## 1. Публичные seams и результат

- `GET|HEAD /health/live` сохраняет текущий JSON/status и не зависит от БД.
- `GET|HEAD /health/ready` сохраняет текущий JSON и HTTP 200/503 semantics.
- `bin/fmonitor2-runtime-check.php` остаётся deployment CLI полной read-only
  проверки совместимости и при успехе атомарно публикует startup attestation.
- Canonical migration catalogue остаётся единственным каталогом схемы. Bounded
  identity существующей canonical DB/table metadata идентифицирует database/schema
  instance; нового marker/table, каталога или health framework нет.

## 2. Startup attestation

После успешной canonical migration штатный compose обязан выполнить ровно один
startup-check до допуска `php`; `php` и `web` не должны зависеть от HTTP readiness
для создания attestation. Startup-check выполняет существующую полную проверку
schema fingerprints и required local storage. Failure не публикует применимый
результат и блокирует запуск dependents.

Успешный attestation связывается как минимум с:

- database identity из server/database/canonical-table metadata, отличающим другую
  или пересозданную БД;
- current catalogue schema version;
- deterministic identity exact application build;
- process table prefix и configured database name;
- версией формата attestation.

Файл публикуется атомарно в private runtime state, имеет regular-file mode 0600,
одну hard link, current UID/GID и не раскрывает credentials. Неполный, чужой,
устаревший или подменённый файл не применим. Повторный startup/update выполняет
полную проверку заново и заменяет результат только после успеха. Старый success
не подходит другой БД, prefix, schema frontier или build.

## 3. Регулярная готовность

Каждый readiness probe обязан заново:

1. проверить current required local storage;
2. установить direct connection к configured MariaDB с текущими timeout;
3. выполнить дешёвый `SELECT 1`;
4. одним bounded lookup прочитать current database identity;
5. проверить private startup attestation против identity, schema version, build,
   database и prefix.

Steady probe не выполняет schema fingerprints, migrations, advisory locks, DDL,
external requests или запись attestation. Его число SQL commands ограничено
константой и не зависит от числа canonical tables/migrations. Прошлый success не
скрывает текущую недоступность БД. `SELECT 1` без применимого attestation не даёт
HTTP 200.

## 4. Отказы и конкуренция

- Missing/incompatible schema, failed/incomplete migration или failed deep check
  не допускают `php` и не дают readiness success.
- Смена database/server/table identity, prefix/build или rollback state без
  соответствующего identity дают 503 до нового успешного startup-check.
- Несколько одновременных HTTP probes только читают identity/attestation и никогда
  не запускают несколько deep checks.
- DB unavailable после startup немедленно даёт readiness 503, while liveness
  продолжает отвечать в прежнем формате.
- ERP, Bitrix и SMTP не вызываются и не являются условиями readiness.

## 5. Проверка и измерения

На одном изолированном Compose project с отдельными database, volumes и ports:

- доказать healthy current schema, DB loss with live success, incompatible/missing
  schema, failed migration/deep check, build/database/state mismatch, repeat/update,
  concurrent probes и startup without readiness cycle;
- выполнить адресные smoke login, object card/editor, construction control и OTIZ;
- сравнить один и последовательную/4-concurrent серию probes по SQL commands,
  `Created_tmp_tables`, `Created_tmp_disk_tables` и elapsed time; отдельно показать
  initial deep-check cost;
- для global MariaDB counters снять фон control interval и явно не приписывать
  сторонние health/jobs запросы измеряемому HTTP request.

Локальный полный `make test`/`make verify` запрещён; обязательны focused checks,
independent planner-required reviews и один exact-source CI run.
