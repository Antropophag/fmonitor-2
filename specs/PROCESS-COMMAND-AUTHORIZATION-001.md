# PROCESS-COMMAND-AUTHORIZATION-001 — авторизовать подтверждение регистрации и открытие отдельными capabilities

> **PILOT DISPOSITION — 2026-09-02.** `assignment_order.confirm_registration`
> ниже — legacy predecessor. Target command требует exact
> `assignment_order.original.upload`/`.correct` по
> `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`; HTTP/local admission отложен.

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР с явно назначенными capabilities каждой команды
- Публичные командные seams: `prepareAssignmentOrder(...)`, `confirmOrderRegistration(...)`, `openInstallation(...)`
- Публичный шов наблюдения: command results и `getInstallationObjectProcess(installationObjectId)` новым MariaDB-соединением
- Secondary adapter seam: методы authorization `MariaDbProcessUserDirectory`
- Deployment seam: `ProcessCommandCapabilitiesSchemaMigration.apply(connection, tablePrefix = '')`

## 1. Цель и принцип наименьших полномочий

Подключить production authorization к ручному подтверждению регистрации и открытию работ без неявного наследования права подготовки. Каждое намерение имеет отдельную точную capability:

```text
assignment_order.prepare
assignment_order.confirm_registration
installation.open
construction_control_engineer
```

Первые три разрешают только соответствующие process-команды. `construction_control_engineer` остаётся признаком допустимого инженера, а не правом ФКР. Legacy role name/id и `users_rights2roles` не преобразуются в capabilities.

## 2. Forward schema migration v4

Migration v3 создала `fm2_process_user_capabilities` с CHECK, допускающим только prepare и engineer. После v1–v3 вызывается:

```php
$migrationResult = ProcessCommandCapabilitiesSchemaMigration::apply($connection);
```

V4 меняет только capability-value CHECK таблицы, расширяя точный enum до четырёх строк раздела 1. Она сохраняет без изменения:

- поля/types/nullability/charset/engine;
- primary key `(user_id, capability)`;
- index `(capability, user_id)`;
- все существующие rows;
- отдельный position CHECK: непустой `position_snapshot` обязателен только для `construction_control_engineer`; для трёх command capabilities position nullable;
- отсутствие cross-schema FK.

Нормативное имя нового v4 capability-ограничения — `ck_fm2_process_user_capability`. V4 одним forward ALTER заменяет v3 capability CHECK и не удаляет/пересоздаёт таблицу.

Историческая v3 schema не обязана иметь нормативные constraint names: MariaDB могла присвоить им generated names. Поэтому v4 preflight идентифицирует два v3 CHECK по нормализованной семантике `information_schema`, а не по имени:

- capability CHECK допускает точно `assignment_order.prepare` и `construction_control_engineer`, независимо от порядка эквивалентного `IN` expression;
- engineer-position CHECK требует non-null/nonblank position только для `construction_control_engineer` и не ограничивает prepare position.

Preflight обязан найти ровно по одному семантически соответствующему CHECK. Actual catalog name capability CHECK принимается только как DB metadata, проверяется как один MariaDB identifier длиной `1..64` из `[A-Za-z0-9_$]+`, quoted как identifier и только затем используется в DDL; command/user input в identifier не интерполируется. Engineer-position CHECK и его actual name сохраняются без DROP/rename.

## 3. Результат, repeat и conflict migration

Для exact v3 schema:

```text
applied = true
schemaVersion = 4
constraintsChanged = [ck_fm2_process_user_capability]
```

Все существующие rows, включая prepare/engineer assignments, побайтно прежние. Новые capability rows migration не создаёт.

Для exact v4 schema safe repeat не выполняет DDL/DML:

```text
applied = false
schemaVersion = 4
constraintsChanged = []
```

Preflight принимает только:

1. exact v3 semantics с любыми безопасными actual names двух однозначно найденных CHECK — как forward source;
2. exact v4 schema с нормативным именем `ck_fm2_process_user_capability`, exact четырьмя capability values и одним сохранённым semantically exact engineer-position CHECK — как completed repeat state.

Если semantic normalization находит zero либо более одного candidate для capability CHECK или engineer-position CHECK, состояние неоднозначно и fail closed. Любое иное отличие целевой таблицы, включая missing/extra columns, keys/indexes, изменённый position CHECK, extra/overlapping semantic checks, unsafe catalog identifier, неизвестный capability CHECK или отсутствие таблицы v3, также fail closed до ALTER:

```text
applied = false
schemaVersion = 4
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = [fm2_process_user_capabilities]
```

Недопустимый prefix отклоняется `InvalidArgumentException` до MariaDB. Destructive down отсутствует; rollback application release не сужает CHECK и не удаляет новые capability rows.

Исполняемые migration examples:

- A: exact v3 CHECK имеют generated names `CONSTRAINT_1` (capability) и `CONSTRAINT_2` (engineer position). V4 успешно удаляет только safely quoted `CONSTRAINT_1`, сохраняет `CONSTRAINT_2` с прежним именем/семантикой и добавляет нормативный `ck_fm2_process_user_capability`; existing rows неизменны.
- B: v3 table содержит два разных actual constraints с одной и той же normalized capability semantics. Результат — exact `SCHEMA_MIGRATION_CONFLICT` выше; ни один constraint/data row не изменён.
- C: exact completed v4 с нормативным capability name повторно возвращает `applied = false`; engineer-position actual name может оставаться историческим `CONSTRAINT_2`.

## 4. Production directory methods

Composite `MariaDbProcessUserDirectory` сохраняет прежние методы и добавляет/реализует:

```text
actorCanPrepareAssignmentOrder(actorId)
actorCanConfirmOrderRegistration(actorId)
actorCanOpenInstallation(actorId)
findEngineerSnapshot(controlEngineerUserId)
```

Каждый authorization method возвращает true только при одновременном точном join:

- `users.id = actorId`;
- `users.status = 1`;
- `users_roles.id = users.role_id` и `users_roles.status = 1`;
- capability row того же `user_id` с единственной строкой, соответствующей методу:
  - prepare → `assignment_order.prepare`;
  - confirm → `assignment_order.confirm_registration`;
  - open → `installation.open`.

Наличие любой другой capability не заменяет требуемую. Position для command authorization не читается. Missing user/role/capability, inactive status или dangling role дают false. Queries параметризованы и не обращаются к `users_rights2roles`.

`findEngineerSnapshot` остаётся строго на `construction_control_engineer` и правилах `PROCESS-USER-DIRECTORY-001`; три command capabilities не делают пользователя инженером.

## 5. Исполняемый successful public chain

Production MariaDB fixtures:

```text
users_roles:
  { id: 5, name: "ФКР", status: 1 }
  { id: 8, name: "Строительный контроль", status: 1 }

users:
  { id: 18, name: "Сидоров Сергей Сергеевич", role_id: 5, status: 1 }
  { id: 73, name: "Петров Пётр Петрович", role_id: 8, status: 1 }

fm2_process_user_capabilities:
  { user_id: 18, capability: assignment_order.prepare, position_snapshot: null }
  { user_id: 18, capability: assignment_order.confirm_registration, position_snapshot: null }
  { user_id: 18, capability: installation.open, position_snapshot: null }
  { user_id: 73, capability: construction_control_engineer,
    position_snapshot: "Инженер строительного контроля" }
```

Production migrations v1–v4 и MariaDB process persistence применены. Object/Workforce facts соответствуют утверждённым examples; current Workforce recheck монтажника `1042` успешен. Clock и renderer могут оставаться детерминированными, чтобы authorization slice не смешивался с document bytes.

Один production module выполняет:

```php
$prepare = $process->prepareAssignmentOrder(4512, [1042], 73, 18);
$confirm = $process->confirmOrderRegistration(4512, 1, ' 12-Р ', 'manual', 18);
$open = $process->openInstallation(4512, '2026-08-28', 18);
```

Точные results наследуются соответственно из `PERSISTENCE-PREPARE-001`, `PERSISTENCE-REGISTRATION-001 v0.2` и `PERSISTENCE-OPEN-001`. После уничтожения исходных objects/connection новый модуль возвращает полную opened projection: `working`, root opening fields, registered version `1`, immutable snapshots/artifacts, gates true, tasks empty, три exact events.

User/role/capability rows до и после публичной цепочки побайтно одинаковы. Process-команды только читают authorization configuration.

## 6. Exact capability separation через secondary seam

Чтобы доказать отсутствие converse/accidental authorization без расширения этого среза production security-audit persistence, прямой adapter test использует отдельные active users/roles:

```text
user 91: only assignment_order.prepare
user 92: only assignment_order.confirm_registration
user 93: only installation.open
user 94: only construction_control_engineer (position configured)
```

Для каждого user все четыре adapter methods вызываются напрямую. Exact matrix:

| User | prepare | confirm | open | engineer snapshot |
|---|---:|---:|---:|---|
| `91` | `true` | `false` | `false` | `null` |
| `92` | `false` | `true` | `false` | `null` |
| `93` | `false` | `false` | `true` | `null` |
| `94` | `false` | `false` | `false` | canonical engineer snapshot |

Дополнительный active user `95` имеет все три command capabilities, но его `users_roles.status = 0`: три authorization methods возвращают false. Active-role user `96` с отсутствующими capability rows также получает false по всем трём.

Secondary seam проверяет production authorization adapter, но не выполняет process mutation. Public chain раздела 5 остаётся обязательным доказательством, что реальные confirm/open commands используют эти методы.

## 7. Порядок, отказ и аудит

Каждая public command вызывает только свой authorization method первым, до раскрытия process/object/composition facts. False преобразуется в унаследованный `FORBIDDEN` конкретной команды без fallback к prepare capability или legacy rights.

Successful chain создаёт только утверждённые три domain events; authorization lookup отдельного события не добавляет. Rejected public security-audit persistence для confirm/open не входит: exact false mapping проверяется adapter matrix, а process-level unauthorized audit требует отдельного вертикального slice.

Ошибка MariaDB fail closed и не превращается в true/другую capability. SQL, role names и capability rows не раскрываются command result.

## 8. Публичный Gate 2 seam и observability

Интеграционный тест:

1. применяет migrations v1–v4 под уникальным prefix и создаёт минимальные prefixed legacy users/roles fixtures;
2. вставляет configuration rows разделов 5–6 как deployment/admin precondition;
3. собирает production `MariaDbProcessUserDirectory` внутри process environment;
4. выполняет public successful chain и full reload projection;
5. проверяет SQL equality только external/configuration user/role/capability tables, не process rows;
6. отдельно вызывает secondary adapter seam для exact separation matrix.

Ожидания matrix и public results — независимые literals спецификации. `users_rights2roles` fixture намеренно отсутствует: случайный query к ней делает тест красным.

## 9. Не входит в срез

- UI/API назначения capabilities и audit configuration changes;
- automatic mapping legacy roles/rights;
- production security-audit persistence для rejected confirm/open;
- multiple legacy roles/role periods;
- safe repeat/concurrency/rollback/unknown commit;
- transport/session authentication;
- destructive rollback v4.

## 10. Решения и доказательства

- `specs/PROCESS-USER-DIRECTORY-001.md`: active user/role join, explicit process capability table and engineer mapping.
- `specs/REGISTRATION-CONFIRM-001.md`, `OPEN-INSTALLATION-001.md`: separate authorization seams precede each command.
- `specs/PERSISTENCE-REGISTRATION-001.md`, `PERSISTENCE-OPEN-001.md`: real MariaDB public chain/projection.
- `PRODUCT.md`: server-side authorization; admin access does not imply process authority.

## 11. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать работу и выбрал distinct production capabilities для confirm/open и forward schema v4 следующим единичным SSD + TDD-срезом; версия 0.2 повторно утверждена с совместимостью generated CHECK names исторической v3 schema и fail-closed semantic disambiguation.

Gate 2 разрешён для версии `0.2`.
