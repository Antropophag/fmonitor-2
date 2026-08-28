# PROCESS-USER-DIRECTORY-001 — авторизовать подготовку и прочитать инженера из production-каталога пользователей

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР с capability `assignment_order.prepare`
- Публичный командный шов: `InstallationProcess.prepareAssignmentOrder(installationObjectId, installerTabIds[], controlEngineerUserId, actorId)`
- Публичный шов наблюдения: результат команды и `InstallationProcess.getInstallationObjectProcess(installationObjectId)` через новый экземпляр модуля
- Технический deployment seam: `ProcessUserCapabilitiesSchemaMigration.apply(connection, tablePrefix = '')`

## 1. Цель и граница

Подключить к успешной production-команде единый `MariaDbProcessUserDirectory`, который:

1. разрешает актору подготовку только по явной capability FMonitor 2.0;
2. возвращает выбранного инженера только по отдельной явной capability инженера строительного контроля.

Legacy `users` и `users_roles` остаются источником идентичности, ФИО и текущей активности пользователя/его единственной legacy-роли. Новая production-owned таблица хранит только process capabilities и должность, необходимую воспроизводимому снимку инженера. Legacy `users_rights2roles` не читается, не изменяется и не получает неявного соответствия новым capabilities.

Один composite object реализует оба внутренних метода окружения:

```text
MariaDbProcessUserDirectory.actorCanPrepareAssignmentOrder(actorId)
MariaDbProcessUserDirectory.findEngineerSnapshot(controlEngineerUserId)
```

Они не становятся публичными UI/API seams; тест вызывает только `InstallationProcess`.

## 2. Additive production schema v3

После migration v1 и v2 вызывается:

```php
$migrationResult = ProcessUserCapabilitiesSchemaMigration::apply($connection);
```

Migration создаёт одну таблицу `fm2_process_user_capabilities`:

| Поле | Контракт |
|---|---|
| `user_id` | `BIGINT UNSIGNED NOT NULL`; legacy `users.id` |
| `capability` | `VARCHAR(80) NOT NULL`; одно из двух точных значений ниже |
| `position_snapshot` | `VARCHAR(300) NULL`; обязательна и непуста для engineer capability, nullable для подготовки |

Точные capability strings:

```text
assignment_order.prepare
construction_control_engineer
```

Таблица использует `ENGINE=InnoDB`, `DEFAULT CHARSET=utf8mb4`, primary key `(user_id, capability)`, non-unique index `(capability, user_id)`, `CHECK` допустимых capability strings и `CHECK`, требующий `position_snapshot IS NOT NULL AND trim(position_snapshot) != ''` для `construction_control_engineer`. Для `assignment_order.prepare` значение может быть `NULL`; если оно заполнено, authorization его не читает.

Foreign key на legacy `users` намеренно отсутствует: process configuration проверяется join-ом при чтении, а развёртывание не меняет legacy schema/cascade semantics.

Production-вызов использует пустой `tablePrefix`. Непустой уникальный prefix разрешён интеграционному тесту и обязан соответствовать `/^[A-Za-z0-9_]*$/`.

## 3. Результат, повтор и конфликт migration

При отсутствующей таблице и совместимых v1/v2:

```text
applied = true
schemaVersion = 3
tablesCreated = [fm2_process_user_capabilities]
```

Таблица пуста; legacy, process и workforce tables/rows не изменены.

При полностью совместимой таблице повтор не выполняет DDL/DML и возвращает `applied = false`, `schemaVersion = 3`, `tablesCreated = []`. Все capability rows и ранее сохранённые process-факты остаются побайтно прежними.

Несовместимая таблица с целевым именем отклоняется до DDL:

```text
applied = false
schemaVersion = 3
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = [fm2_process_user_capabilities]
```

Конфликтом является отличие полей, типов, nullability, engine, charset, primary/index или `CHECK`. Недопустимый prefix даёт `InvalidArgumentException` до MariaDB. Destructive `down()` отсутствует.

## 4. Точное правило авторизации актора

`actorCanPrepareAssignmentOrder(actorId)` возвращает `true` только если одним параметризованным lookup подтверждены все факты:

- `users.id = actorId`;
- `users.status = 1`;
- существует `users_roles.id = users.role_id` и `users_roles.status = 1`;
- существует точная строка `(user_id = actorId, capability = 'assignment_order.prepare')` в `fm2_process_user_capabilities`.

Имя/номер legacy-роли и `users_rights2roles` не участвуют. Отсутствие пользователя, роли или capability, любой status кроме числового/DB-эквивалентного `1`, либо dangling `role_id` возвращает `false` и наследует публичный `FORBIDDEN` до чтения объекта, состава и каталогов.

## 5. Точное правило инженера

`findEngineerSnapshot(controlEngineerUserId)` возвращает снимок только если одновременно:

- `users.id = controlEngineerUserId`;
- `users.status = 1`;
- связанная `users_roles.status = 1`;
- существует точная capability `construction_control_engineer` этого пользователя;
- её `position_snapshot` непуста по schema contract.

Успешный mapping:

```text
userId = integer users.id
fullName = exact users.name
position = exact fm2_process_user_capabilities.position_snapshot
active = true
role = construction_control_engineer
```

Legacy role name не преобразуется в каноническую роль и не возвращается. Capability является единственным основанием канонического `role`. При невыполнении любого условия метод возвращает внутреннее `null`, а публичная команда наследует единый `CONTROL_ENGINEER_NOT_ELIGIBLE` из `ORDER-PREPARE-004`.

Capability `assignment_order.prepare` сама по себе не делает пользователя инженером; `construction_control_engineer` сама по себе не разрешает подготовку распоряжения.

## 6. Исполняемый успешный пример

Интеграционный fixture содержит реальные минимальные legacy-таблицы и строки:

```text
users_roles:
  { id: 5, name: "ФКР", status: 1 }
  { id: 8, name: "Строительный контроль", status: 1 }

users:
  { id: 18, name: "Сидоров Сергей Сергеевич", role_id: 5, status: 1 }
  { id: 73, name: "Петров Пётр Петрович", role_id: 8, status: 1 }

fm2_process_user_capabilities:
  { user_id: 18, capability: assignment_order.prepare, position_snapshot: null }
  { user_id: 73, capability: construction_control_engineer,
    position_snapshot: "Инженер строительного контроля" }
```

Остальные production facts:

- process migrations v1–v3 применены;
- объект монтажа `4512` читается production LegacyInstallationObject delegate и имеет снимок `LEGACY-OBJECT-SNAPSHOT-001` example A;
- монтажник `1042` читается production Workforce delegate из `fm2_workforce_catalog` example `WORKFORCE-CATALOG-001`;
- пустое дело `needs_assignment_order`, revision `1`, сохраняется production MariaDB process persistence;
- только clock (`2026-08-26T21:30:00+00:00`) и renderer остаются детерминированными литералами `PERSISTENCE-PREPARE-001`.

Действие:

```php
$result = $process->prepareAssignmentOrder(4512, [1042], 73, 18);
```

Точный результат:

```text
accepted = true
assignmentOrderVersion = 1
status = prepared
assignmentOrderDate = 2026-08-27
organizationType = individual
```

После уничтожения исходного module/delegates/connection новый экземпляр и новое соединение возвращают полную проекцию `PERSISTENCE-PREPARE-001`. Снимок инженера литерально равен:

```text
userId = 73
fullName = "Петров Пётр Петрович"
position = "Инженер строительного контроля"
active = true
role = construction_control_engineer
```

Остальная проекция — объект, монтажник, два renderer-артефакта с утверждёнными metadata/SHA-256, предварительные назначения, одно событие `assignment_order_prepared`, отсутствие открытых задач и закрытые work/checklist gates — строго наследуется из утверждённых production slices.

## 7. Read-only и иммутабельность

`prepareAssignmentOrder` и `MariaDbProcessUserDirectory` выполняют только `SELECT` из `users`, `users_roles` и `fm2_process_user_capabilities`. Все fixture rows до и после команды побайтно одинаковы. Настройка capability является отдельным административным/deployment действием, не process-командой ФКР.

После успешной подготовки fixture меняет `users.name`, statuses роли/пользователя и capability position либо удаляет capability. Новое чтение уже подготовленной версии всё равно возвращает снимок инженера раздела 6 из `fm2_assignment_orders`; исторический документ не перечитывает текущий каталог и не меняется.

## 8. Авторизация, отказ и аудит

Срез не вводит новых business-кодов. Неуспешная авторизация наследует `FORBIDDEN` и закрытый security-аудит `ORDER-PREPARE-001-H`; недопустимый инженер наследует `CONTROL_ENGINEER_NOT_ELIGIBLE` и process-аудит `ORDER-PREPARE-004`. Эти rejected paths нормативны, но отдельные production fixture-примеры не добавляются в этот единичный success-slice.

Ошибка MariaDB fail closed и не маскируется как отсутствие capability/инженера; типизированный infrastructure-result остаётся отдельным срезом. Успех добавляет ровно одно событие `assignment_order_prepared` без SQL, legacy role name или capability configuration.

## 9. Публичный seam теста

Интеграционный тест:

1. применяет production migrations v1, v2 и v3 с одним уникальным допустимым prefix для `fm2_*`;
2. fixture-SQL создаёт под отдельным уникальным допустимым test-prefix минимальные реальные по колонкам `fm_maintable`, `users`, `users_roles` и вставляет external/configuration preconditions; production delegates получают этот fixture-prefix явно, а `users_rights2roles` намеренно не создаётся;
3. собирает production process persistence, LegacyInstallationObject, Workforce и composite ProcessUserDirectory; только clock/renderer детерминированы;
4. вызывает только публичную команду и читает только публичную проекцию новым module/connection;
5. проверяет неизменность всех user/capability rows после команды и неизменяемость сохранённого снимка после последующего изменения текущих facts.

Ожидаемые actor/engineer facts являются независимыми литералами раздела 6. Тест не вызывает методы directory напрямую, не выводит ожидания из SQL/production output и не предоставляет fallback authorization/UserDirectory delegate. Отсутствие `users_rights2roles` делает случайную связь с legacy rights чувствительной ошибкой setup/implementation, а не скрытым источником green.

## 10. Не входит в срез

- UI/API управления capabilities и аудит их изменения;
- автоматический mapping legacy roles/rights в capabilities;
- `users_rights2roles`, legacy right IDs и role names как authorization source;
- множественные legacy-роли пользователя и периоды роли;
- position из `users`, departments или свободного ввода;
- production clock и DocumentRenderer;
- security-audit production persistence;
- типизированные ошибки недоступности MariaDB;
- transport/controller;
- destructive rollback.

## 11. Решения и доказательства

- `specs/ORDER-PREPARE-001.md` и `ORDER-PREPARE-001-H.md`: exact capability `assignment_order.prepare`, приоритет авторизации и `FORBIDDEN`.
- `specs/ORDER-PREPARE-004.md`: active engineer, canonical `construction_control_engineer`, снимок и единый отказ.
- `specs/PERSISTENCE-PREPARE-001.md`: долговечная полная проекция и исторический снимок инженера.
- `specs/WORKFORCE-CATALOG-001.md` и `LEGACY-OBJECT-SNAPSHOT-001.md`: остальные production delegates успешного пути.
- `../fmonitor/application/controllers/Users.php` и `Auth.php`: legacy identity/role join и отдельные status пользователя/роли; это read-only evidence, а не permission mapping.

## 12. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать работу, принимать лучшие решения и выбрал production authorization, control-engineer directory и additive capability migration единым следующим SSD + TDD-срезом.

Gate 2 разрешён для версии `0.1`.
