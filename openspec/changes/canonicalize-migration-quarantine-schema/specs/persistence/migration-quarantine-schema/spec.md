## Purpose

Определяет проверяемое каноническое владение трёхтабличной PILOT_ONLY схемой migration-quarantine без изменения её данных и доменной семантики.

## ADDED Requirements

### Requirement: Канонический runner владеет полной семьёй
Система SHALL создавать registry, observations и decisions только через одну
аддитивную каноническую миграцию, зарегистрированную после фактически
приземлившихся предшественников. Runtime-пути SHALL проверять точную
совместимость схемы и SHALL NOT выполнять DDL.

#### Scenario: Чистая база
- **WHEN** production migration runner выполняется на чистой базе с допустимым префиксом
- **THEN** он создаёт все три точных совместимых члена семьи и один раз записывает свою версию в ledger

#### Scenario: Runtime без DDL
- **WHEN** registration, read-model или decision path запускается с запрещённым DDL после миграции
- **THEN** прежнее характеризованное поведение сохраняется без попытки создать или изменить таблицу

#### Scenario: Отсутствующая схема на runtime path
- **WHEN** state-changing runtime path получает отсутствующий или несовместимый обязательный член семьи
- **THEN** он fail-closed до записи бизнес-данных и не пытается ремонтировать схему

### Requirement: Preflight атомарно решает совместимость семьи
До первого DDL система MUST проверить все существующие члены семьи. Точная
совместимость определяется семантическими типами, nullability/default/extra,
engine, разрешённой explicit-utf8mb4 collation и упорядоченной семантикой
индексов; presentation-имена индексов SHALL NOT быть контрактом.

#### Scenario: Все восемь совместимых partial states
- **WHEN** любая из `2^3 = 8` комбинаций членов отсутствует либо уже точно совместима
- **THEN** миграция создаёт только отсутствующие таблицы и завершает семью

#### Scenario: Несовместимый член
- **WHEN** любой существующий член имеет конфликт колонки, индекса, engine или collation
- **THEN** миграция сообщает schema conflict до первого DDL и не изменяет таблицы, строки, counters, decoys или migration ledger

#### Scenario: Повтор
- **WHEN** миграция повторно проверяет уже завершённую совместимую семью
- **THEN** схема, данные, counters и единственная ledger-запись остаются неизменными

### Requirement: Данные и частичные состояния сохраняются
Миграция SHALL сохранять все строки и следующие AUTO_INCREMENT значения и
SHALL быть restart-safe после каждого implicit-commit DDL boundary. Она SHALL
NOT регистрировать источники, классифицировать данные, добавлять решения,
выполнять reconciliation или cleanup.

#### Scenario: Populated compatible family
- **WHEN** один или несколько совместимых членов содержат sentinel rows и неначальные counters
- **THEN** миграция сохраняет точные строки и следующие counters, создавая только отсутствующие члены

#### Scenario: Прерывание между DDL
- **WHEN** предыдущий запуск остановился после создания любого подмножества таблиц
- **THEN** следующий запуск preflight-проверяет всю наблюдаемую семью и безопасно достраивает её

#### Scenario: Ambient decoys
- **WHEN** в базе присутствуют не принадлежащие verifier/migration таблицы и строки
- **THEN** успешный, повторный и конфликтный запуски оставляют их неизменными

### Requirement: Точный перенос наблюдаемого schema contract
Система SHALL сохранять три AUTO_INCREMENT primary key, все NOT NULL колонки
без semantic defaults, plain-TEXT JSON fields, две registry uniqueness,
observation replay uniqueness, operation-id uniqueness и ordered reference
index. Все таблицы SHALL использовать InnoDB и явно зафиксированную
предварительно проверенную collation для `utf8mb4`; foreign keys и CHECK SHALL
отсутствовать.

#### Scenario: Semantic fingerprint
- **WHEN** verifier читает `information_schema` после чистой или partial-state миграции
- **THEN** fingerprint всех трёх таблиц совпадает с утверждённым Gate 1 manifest независимо от presentation-имён индексов

#### Scenario: Plain text JSON
- **WHEN** verifier сравнивает JSON-bearing registry columns
- **THEN** они остаются plain `TEXT NOT NULL` без JSON CHECK и без неутверждённого усиления semantics

### Requirement: Префикс безопасен для полного каталога
Composed runner MUST принимать valid ASCII process prefix длиной не более 25
байт и MUST отклонять 26-byte/invalid/non-ASCII input до DB connection/access.
Собственная family-local граница равна 27 байтам при 37-byte longest basename и
не является production configuration support.

#### Scenario: Граничный префикс
- **WHEN** чистая composed migration запускается с валидным 25-байтным ASCII prefix
- **THEN** все таблицы создаются без превышения MariaDB identifier limit

#### Scenario: Слишком длинный префикс
- **WHEN** runner получает valid-character 26-byte, invalid или non-ASCII prefix
- **THEN** он завершается setup/configuration error до DB connection/access
- **AND** не изменяет ledger, schema, rows, counters или ambient database objects

### Requirement: Slice остаётся PILOT_ONLY
Миграция SHALL NOT превращать текущие quarantine taxonomy, outcomes, actors,
retention, source cutover или financial use в target requirements.

#### Scenario: Неразрешённая семантика
- **WHEN** planning или verification встречает значение quarantine/decision поля
- **THEN** оно используется только как literal preservation fixture и не объявляется утверждённой target domain semantics
