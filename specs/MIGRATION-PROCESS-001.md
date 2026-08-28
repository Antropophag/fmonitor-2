# MIGRATION-PROCESS-001 — развернуть production-схему первого process persistence

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-28`
- Актор: оператор развёртывания FMonitor
- Публичный командный шов: `ProductionProcessSchemaMigration.apply(connection, tablePrefix = '')`
- Публичный шов наблюдения: результат миграции, каталог MariaDB и уже утверждённые публичные методы `InstallationProcess`

## 1. Цель

Развернуть в production MariaDB минимальную процессную схему, необходимую завершённому срезу `PERSISTENCE-PREPARE-001`: монтажное дело, версии распоряжений, снимки монтажников, метаданные артефактов, задачи и append-only события.

Срез создаёт только структуру. Он не переносит legacy-факты, не создаёт монтажные дела и не подключает production callers.

## 2. Предусловия и вход

- соединение указывает на MariaDB базы FMonitor и имеет право создавать таблицы и индексы;
- таблицы legacy, включая `fm_maintable`, не изменяются;
- production-вызов использует пустой `tablePrefix`; непустой префикс разрешён только изолированному интеграционному тесту;
- кодировка соединения поддерживает `utf8mb4`;
- входом команды являются соединение и допустимый префикс `/^[A-Za-z0-9_]*$/`.

Оператор выполняет миграцию один раз через версионированный migration runner приложения. Runner вызывает:

```php
$result = ProductionProcessSchemaMigration::apply($connection);
```

## 3. Точный контракт схемы

Команда создаёт ровно следующие шесть таблиц с `ENGINE=InnoDB` и `DEFAULT CHARSET=utf8mb4`:

1. `fm2_installation_cases`;
2. `fm2_assignment_orders`;
3. `fm2_order_installers`;
4. `fm2_order_artifacts`;
5. `fm2_process_tasks`;
6. `fm2_process_events`.

Поля и nullability являются нормативными:

| Таблица | Поля |
|---|---|
| `fm2_installation_cases` | `id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL`; `legacy_installation_object_id BIGINT UNSIGNED NOT NULL`; `process_state VARCHAR(80) NOT NULL`; `actual_start_date DATE NULL`; `opened_at VARCHAR(40) NULL`; `opened_by_user_id BIGINT UNSIGNED NULL`; `created_at VARCHAR(40) NOT NULL`; `updated_at VARCHAR(40) NOT NULL`; `lock_version INT UNSIGNED NOT NULL` |
| `fm2_assignment_orders` | `id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL`; `installation_case_id BIGINT UNSIGNED NOT NULL`; `version_no SMALLINT UNSIGNED NOT NULL`; `kind VARCHAR(40) NOT NULL`; `status VARCHAR(40) NOT NULL`; `order_date DATE NOT NULL`; `registration_number VARCHAR(120) NULL`; `registered_at VARCHAR(40) NULL`; `registration_actor_type VARCHAR(40) NULL`; `registration_actor_id VARCHAR(120) NULL`; `registration_source VARCHAR(40) NULL`; `external_registration_id VARCHAR(120) NULL`; `control_engineer_user_id BIGINT UNSIGNED NOT NULL`; `control_engineer_fio_snapshot VARCHAR(300) NOT NULL`; `control_engineer_position_snapshot VARCHAR(300) NOT NULL`; `organization_form VARCHAR(40) NOT NULL`; `previous_assignment_order_id BIGINT UNSIGNED NULL`; `object_address_snapshot VARCHAR(500) NOT NULL`; `entrance_snapshot VARCHAR(80) NOT NULL`; `object_registration_number_snapshot VARCHAR(120) NOT NULL`; `planned_start_date_snapshot DATE NOT NULL`; `planned_finish_date_snapshot DATE NOT NULL`; `pto_act_date_snapshot DATE NULL`; `prepared_at VARCHAR(40) NOT NULL`; `prepared_by_user_id BIGINT UNSIGNED NOT NULL` |
| `fm2_order_installers` | `assignment_order_id BIGINT UNSIGNED NOT NULL`; `installer_tab_id BIGINT UNSIGNED NOT NULL`; `fio_snapshot VARCHAR(300) NOT NULL`; `position_snapshot VARCHAR(300) NOT NULL`; `employment_status_snapshot VARCHAR(40) NOT NULL`; `employed_from_snapshot DATE NOT NULL`; `employed_to_snapshot DATE NULL`; `workforce_source_snapshot VARCHAR(80) NOT NULL`; `workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL`; `valid_from DATE NOT NULL`; `valid_to DATE NULL`; `change_action VARCHAR(40) NOT NULL` |
| `fm2_order_artifacts` | `assignment_order_id BIGINT UNSIGNED NOT NULL`; `artifact_type VARCHAR(40) NOT NULL`; `filename VARCHAR(500) NOT NULL`; `media_type VARCHAR(120) NOT NULL`; `byte_size BIGINT UNSIGNED NOT NULL`; `sha256 CHAR(64) NOT NULL` |
| `fm2_process_tasks` | `id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL`; `installation_case_id BIGINT UNSIGNED NOT NULL`; `task_type VARCHAR(80) NOT NULL`; `assignee_user_id BIGINT UNSIGNED NULL`; `assignee_role VARCHAR(80) NULL`; `due_date DATE NULL`; `status VARCHAR(40) NOT NULL`; `completed_at VARCHAR(40) NULL`; `completed_by_user_id BIGINT UNSIGNED NULL`; `created_at VARCHAR(40) NOT NULL` |
| `fm2_process_events` | `id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL`; `installation_case_id BIGINT UNSIGNED NOT NULL`; `event_type VARCHAR(80) NOT NULL`; `occurred_at VARCHAR(40) NOT NULL`; `actor_user_id BIGINT UNSIGNED NOT NULL`; `payload_json JSON NOT NULL` |

`VARCHAR(40)` у временных полей намеренно сохраняет полное ISO-8601 значение с часовым смещением, которое уже является частью публичной проекции `PERSISTENCE-PREPARE-001`; преобразование в серверную часовую зону не допускается.

Нормативные ключи и ограничения:

- primary key `fm2_installation_cases(id)` и unique key `(legacy_installation_object_id)`;
- primary key `fm2_assignment_orders(id)`, unique key `(installation_case_id, version_no)` и index `(installation_case_id, status)`;
- primary key `fm2_order_installers(assignment_order_id, installer_tab_id)`;
- primary key `fm2_order_artifacts(assignment_order_id, artifact_type)`;
- primary key `fm2_process_tasks(id)` и index `(status, assignee_role, due_date)`;
- primary key `fm2_process_events(id)` и index `(installation_case_id, occurred_at)`;
- foreign keys: `assignment_orders.installation_case_id → installation_cases.id`, `assignment_orders.previous_assignment_order_id → assignment_orders.id`, `order_installers.assignment_order_id → assignment_orders.id`, `order_artifacts.assignment_order_id → assignment_orders.id`, `process_tasks.installation_case_id → installation_cases.id`, `process_events.installation_case_id → installation_cases.id`.

Удаление родительских process-фактов каскадом не задаётся: все шесть foreign keys используют запрещающее удаление поведение MariaDB по умолчанию. Ссылки на legacy-объект, пользователей и табельные идентификаторы намеренно не получают cross-schema foreign keys; их допустимость проверяет модуль, а исторические снимки должны переживать изменения внешних каталогов.

## 4. Успешный наблюдаемый результат

При исходно отсутствующих шести таблицах команда:

```text
applied = true
schemaVersion = 1
tablesCreated = [
  fm2_installation_cases,
  fm2_assignment_orders,
  fm2_order_installers,
  fm2_order_artifacts,
  fm2_process_tasks,
  fm2_process_events
]
```

Каталог MariaDB показывает литеральные 6 таблиц, 68 полей, 6 primary keys, 2 дополнительных unique keys, 3 дополнительных non-unique indexes и 6 foreign keys из раздела 3. Все таблицы пусты.

После создания fixture одного дела утверждённый пример `PERSISTENCE-PREPARE-001` выполняется через `InstallationProcess.prepareAssignmentOrder(...)` и читается новым соединением через `getInstallationObjectProcess(...)` без создания таблиц тестом вручную. Полный ожидаемый business-result наследуется из `PERSISTENCE-PREPARE-001` и не выводится из production migration или adapter.

## 5. Безопасный повтор

Если все шесть таблиц уже существуют и полностью соответствуют разделу 3, повторный вызов не выполняет DDL и возвращает:

```text
applied = false
schemaVersion = 1
tablesCreated = []
```

Повтор не удаляет и не изменяет строки, auto-increment, ключи или таблицы. До и после повтора проекция ранее сохранённого примера `PERSISTENCE-PREPARE-001` строго одинакова.

## 6. Несовместимое или частичное состояние

Перед первым DDL команда проверяет все существующие таблицы с целевыми именами. Если хотя бы одна существует, но не соответствует полям, типам, nullability, engine, charset, ключам или foreign keys раздела 3, команда отклоняется стабильной технической причиной:

```text
applied = false
schemaVersion = 1
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = <уникальные имена в нормативном порядке раздела 3>
```

При preflight-конфликте DDL и DML не выполняются, существующая схема и данные не изменяются, отсутствующие таблицы не создаются. В результат не попадают SQL, параметры подключения или сообщения драйвера.

Если предыдущий запуск был прерван после создания только совместимого префикса таблиц, повтор проверяет созданные таблицы, создаёт недостающие в порядке зависимостей и возвращает `applied = true` со списком только фактически созданных таблиц. После успеха наблюдается весь контракт раздела 3. Это восстановление развёртывания, а не откат или изменение process-фактов.

Недопустимый `tablePrefix` отклоняется до обращения к MariaDB исключением `InvalidArgumentException`; текст входа не интерполируется в SQL.

## 7. Публичный seam теста и независимые литералы

Интеграционный тест вызывает production migration object с новым уникальным допустимым префиксом. Он не копирует `app/demo/schema.sql` и не создаёт целевые таблицы fixture-SQL.

Тест наблюдает схему через стандартный каталог MariaDB, затем создаёт только предусловие — литеральное дело объекта монтажа `4512` в состоянии `needs_assignment_order`, с timestamps `2026-08-20T09:00:00+03:00` и `lock_version = 1`. После этого он повторяет утверждённые независимо определённые вход и ожидаемую проекцию `PERSISTENCE-PREPARE-001` (`actorId = 18`, `installerTabId = 1042`, `controlEngineerUserId = 73`, business-date `2026-08-27`).

Чувствительность повторного сценария доказывается сохранением одного литерального события `assignment_order_prepared` и одной версии распоряжения: реализация, которая пересоздаёт или очищает таблицы, теряет данные или создаёт дубль, не проходит тест.

Отдельный минимальный rejected-case использует заранее созданную несовместимую `fm2_installation_cases` только с полем `id`; ожидает `SCHEMA_MIGRATION_CONFLICT`, список `[fm2_installation_cases]` и отсутствие остальных пяти таблиц.

## 8. Авторизация, аудит и эксплуатационная безопасность

Migration object не является HTTP/UI-командой и не выполняет предметную авторизацию: доступ к нему ограничивает deployment runner и DB credentials. Срез не создаёт process/security-событий, потому что создание пустой технической схемы не является действием пользователя над объектом монтажа. История применения версии остаётся ответственностью существующего migration runner.

Обратная миграция, удаляющая таблицы или данные, намеренно отсутствует. Откат релиза не удаляет уже сохранённые process-факты.

## 9. Не входит в срез

- создание дел для пилотных или всех строк `fm_maintable`;
- перенос `installator*`, распоряжений, задач, событий или иных legacy-значений;
- проверка качества legacy-объектов и сочетания `needs_assignment_order` с Актом ПТО;
- изменение `fm_maintable` и совместимая legacy-проекция;
- production LegacyInstallationObject, Workforce, UserDirectory, authorization, clock и DocumentRenderer delegates;
- transport/controller и автоматический запуск миграции при web-запросе;
- новые таблицы operation reconciliation;
- destructive `down()`.

## 10. Решения и доказательства

- `specs/PERSISTENCE-PREPARE-001.md`: публичный durability-сценарий и минимальный набор используемых process-таблиц.
- `docs/fmonitor-2-pilot-data-model.md`: утверждённые поля, ключи, append-only история и вторичность legacy-проекции.
- `docs/development-process.md`: migration проходит тот же SSD + TDD workflow через наблюдаемый production seam.
- `docs/fmonitor-2-session-handoff.md`: production migration является следующим рекомендованным единичным срезом; `app/demo/schema.sql` не является production-контрактом.

## 11. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь явно выбрал следующим срезом production migration утверждённых `fm2_*` таблиц, поручил принимать лучшие решения самостоятельно и потребовал продолжать обязательный SSD + TDD workflow.

Gate 2 разрешён для версии `0.1`.
