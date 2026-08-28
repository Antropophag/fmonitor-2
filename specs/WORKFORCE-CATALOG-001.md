# WORKFORCE-CATALOG-001 — прочитать монтажника из production кадрового каталога

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР с полномочием `assignment_order.prepare`
- Публичный командный шов: `InstallationProcess.prepareAssignmentOrder(installationObjectId, installerTabIds[], controlEngineerUserId, actorId)`
- Публичный шов наблюдения: результат команды и `InstallationProcess.getInstallationObjectProcess(installationObjectId)` через новый экземпляр модуля
- Технический deployment seam: `WorkforceCatalogSchemaMigration.apply(connection, tablePrefix = '')`

## 1. Цель

Дать успешному production-пути правдивый источник всех кадровых фактов, уже требуемых `ORDER-PREPARE-003`. Additive migration v2 создаёт принадлежащую FMonitor 2.0 таблицу текущего интеграционного снимка, а MariaDB Workforce delegate читает из неё выбранных монтажников.

Legacy `fm_installators` не объявляется источником отсутствующих там кадровых периодов, provenance или свежести. Заполнение нового каталога является предусловием внешней цепочки `1С ЗУП → Битрикс → FMonitor`; importer/API этой цепочки не входит в срез.

## 2. Production-схема v2

После успешно применённой `MIGRATION-PROCESS-001` вызывается:

```php
$migrationResult = WorkforceCatalogSchemaMigration::apply($connection);
```

Production-вызов использует пустой `tablePrefix`; уникальный префикс разрешён интеграционному тесту и обязан соответствовать `/^[A-Za-z0-9_]*$/`.

Migration создаёт одну таблицу `fm2_workforce_catalog`:

| Поле | Контракт |
|---|---|
| `installer_tab_id` | `BIGINT UNSIGNED NOT NULL`; pilot identity и primary key |
| `fio` | `VARCHAR(300) NOT NULL`; актуальное ФИО из кадрового снимка |
| `position` | `VARCHAR(300) NOT NULL`; актуальная должность |
| `employment_status` | `VARCHAR(40) NOT NULL`; допустимые значения `employed`, `dismissed` |
| `employed_from` | `DATE NULL`; известная включительная дата начала трудовых отношений |
| `employed_to` | `DATE NULL`; известная включительная дата окончания трудовых отношений |
| `workforce_source` | `VARCHAR(80) NOT NULL`; стабильный идентификатор источника |
| `workforce_source_updated_at` | `VARCHAR(40) NOT NULL`; ISO-8601 момент свежести со смещением |

Таблица использует `ENGINE=InnoDB`, `DEFAULT CHARSET=utf8mb4`, primary key `(installer_tab_id)`, `CHECK (employment_status IN ('employed', 'dismissed'))` и non-unique index `(employment_status, employed_to)`. Cross-schema foreign keys отсутствуют.

В пилоте табельная идентичность является положительным целым `BIGINT`; `0` не является монтажником. Отображаемый `tab_id_char`, ведущие нули и переход к строковой идентичности не входят в этот срез. Публичная команда продолжает принимать существующий числовой tab ID.

## 3. Наблюдаемый результат migration

При отсутствующей таблице и совместимых шести таблицах schema v1:

```text
applied = true
schemaVersion = 2
tablesCreated = [fm2_workforce_catalog]
```

Таблица пуста; шесть process-таблиц, их строки, ключи и auto-increment не изменены.

Если таблица уже полностью соответствует разделу 2, безопасный повтор не выполняет DDL/DML:

```text
applied = false
schemaVersion = 2
tablesCreated = []
```

Если таблица с целевым именем существует, но не соответствует полям, типам, nullability, engine, charset, primary key, check или index, команда до DDL возвращает:

```text
applied = false
schemaVersion = 2
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = [fm2_workforce_catalog]
```

Существующие таблицы и данные не меняются. Недопустимый prefix отклоняется `InvalidArgumentException` до обращения к MariaDB. Migration не имеет destructive `down()`.

## 4. Workforce mapping

Production Workforce delegate ищет одну строку параметризованным точным равенством `installer_tab_id = requestedTabId` и возвращает:

| Поле внутреннего снимка | Поле каталога |
|---|---|
| `tabId` | integer `installer_tab_id` |
| `fullName` | `fio` без исправления внутреннего написания |
| `position` | `position` без исправления внутреннего написания |
| `status` | `employment_status` |
| `employedFrom` | `employed_from` как `YYYY-MM-DD` или `null` |
| `employedTo` | `employed_to` как `YYYY-MM-DD` или `null` |
| `source` | `workforce_source` |
| `sourceUpdatedAt` | точное ISO-8601 значение `workforce_source_updated_at` |

Отсутствующая строка возвращает внутреннее `null` и через существующее поведение публичной команды даёт `INSTALLER_NOT_IN_CATALOG`. Найденная строка проходит неизменные правила `ORDER-PREPARE-003`: успешна только при `status = employed`, доказанной `employedFrom` и периоде, покрывающем дату распоряжения и уже известное плановое завершение.

Текущий срез не вводит порог устаревания. `sourceUpdatedAt` обязателен, показывается как provenance и сохраняется в документальном снимке, но сам по себе не меняет допустимость до отдельного продуктового решения.

## 5. Исполняемый пример A — успешный production lookup

Независимые предусловия:

```text
fm2_workforce_catalog:
  installer_tab_id = 1042
  fio = "Иванов Иван Иванович"
  position = "Электромеханик по лифтам"
  employment_status = employed
  employed_from = 2024-02-01
  employed_to = null
  workforce_source = one_c_zup_via_bitrix
  workforce_source_updated_at = "2026-08-26T18:00:00+03:00"

installationObjectId = 4512
plannedStartDate = 2026-10-05
plannedFinishDate = 2026-12-20
server time = 2026-08-26T21:30:00+00:00
actorId = 18
controlEngineerUserId = 73
```

Fixture напрямую вставляет строку каталога как результат уже состоявшейся внешней синхронизации. Остальные внешние факты и renderer детерминированы литералами `PERSISTENCE-PREPARE-001`.

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

После уничтожения исходного модуля и соединения новый экземпляр возвращает полную проекцию `PERSISTENCE-PREPARE-001`. В ней ровно один снимок монтажника:

```text
tabId = 1042
fullName = "Иванов Иван Иванович"
position = "Электромеханик по лифтам"
status = employed
employedFrom = 2024-02-01
employedTo = null
source = one_c_zup_via_bitrix
sourceUpdatedAt = "2026-08-26T18:00:00+03:00"
```

Остальные литералы проекции — версия, объект, инженер, два renderer-артефакта с утверждёнными metadata/SHA-256, предварительные назначения, одно событие успеха и закрытые work/checklist gates — строго наследуются из `PERSISTENCE-PREPARE-001`.

## 6. Иммутабельность снимка и границы записи

`prepareAssignmentOrder` и MariaDB Workforce delegate выполняют только `SELECT` из `fm2_workforce_catalog`. До и после команды строка каталога совпадает побайтно; process-команда не вставляет, не обновляет и не удаляет текущий каталог.

После успешной подготовки fixture имитирует будущую синхронизацию, изменяя текущие `fio`, `position`, `employment_status`, `employed_to` и `workforce_source_updated_at`. `getInstallationObjectProcess(4512)` новым соединением по-прежнему возвращает литеральный снимок раздела 5 из `fm2_order_installers` и не перечитывает каталог. Текущий каталог изменяем, исторический снимок распоряжения — нет.

Право `INSERT`/`UPDATE`/upsert в таблицу будет принадлежать только будущему интеграционному входу. Универсальный CRUD и ручное исправление сотрудником ФКР не допускаются.

## 7. Отказы, авторизация и аудит

Новых публичных business-кодов нет. Отсутствие строки и кадровая недопустимость используют точные причины и аудит `ORDER-PREPARE-003`. Ошибка MariaDB не маскируется как `INSTALLER_NOT_IN_CATALOG`: она fail closed до инженера, renderer и сохранения версии; типизированный инфраструктурный caller-result требует отдельного среза.

`FORBIDDEN`, обязательный состав, реквизиты объекта и существующая актуальная версия проверяются в утверждённом порядке до Workforce lookup. Авторизацию выполняет `InstallationProcess`; delegate не является пользовательской командой.

При успехе сохраняется ровно одно событие `assignment_order_prepared` утверждённого примера. Audit не содержит SQL, DB credentials или отдельное событие чтения каталога. Migration — deployment action и не создаёт process/security-событие.

## 8. Публичный seam теста

Интеграционный тест с уникальным допустимым prefix:

1. применяет production schema migration v1 и additive Workforce migration v2;
2. fixture-SQL создаёт только process-предусловие и вставляет одну строку внешнего текущего каталога из раздела 5;
3. собирает production MariaDB process persistence и Workforce delegate; прочие внешние факты остаются детерминированными;
4. вызывает только публичную `prepareAssignmentOrder(...)` и наблюдает только публичную проекцию новым module/connection;
5. доказывает read-only границу каталога сравнением fixture до/после;
6. меняет текущий fixture после успеха и доказывает неизменность сохранённой публичной проекции.

Ожидаемые кадровые поля — независимые литералы раздела 5, а не результат production SQL/delegate. Тестовая сборка не предоставляет альтернативный in-memory Workforce lookup и не читает `fm_installators`, поэтому отсутствие production table/delegate не может дать ложный green.

## 9. Не входит в срез

- importer, API, расписание синхронизации и upsert-реализация;
- mapping из Битрикс/ЗУП payload и reconciliation удалённых работников;
- порог свежести, outage policy и пользовательское сообщение об устаревании;
- неявки, больничные, отпуска, квалификации и допуски;
- строковая/ведуще-нулевая табельная идентичность и `tab_id_char`;
- production UserDirectory, authorization, clock и DocumentRenderer delegates;
- UI справочника монтажников;
- изменение `fm_installators` или объявление этой legacy-таблицы кадровым источником;
- destructive rollback.

## 10. Решения и доказательства

- `PRODUCT.md` и `CONTEXT.md`: назначение только из актуального интеграционного каталога, provenance и датированные кадровые факты.
- `specs/ORDER-PREPARE-003.md`: точные поля, допустимость периода и существующие причины отказа.
- `specs/PERSISTENCE-PREPARE-001.md`: полный успешный результат и неизменяемый снимок в `fm2_order_installers`.
- `specs/MIGRATION-PROCESS-001.md`: additive production migration не меняет шесть process-таблиц и не удаляет факты.
- `docs/fmonitor-2-pilot-data-model.md`: Workforce является внутренним adapter seam; исторический документ не переписывается актуальным каталогом.

## 11. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать работу, принимать лучшие решения и явно выбрал production-owned Workforce catalog, additive migration и MariaDB delegate следующим единичным SSD + TDD-срезом.

Gate 2 разрешён для версии `0.1`.
