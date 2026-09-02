# PERSISTENCE-OPEN-001 — долговечно сохранить открытие монтажных работ

> **TARGET PREDECESSOR — 2026-09-02.** Legacy opening persistence ниже не
> является target acceptance oracle. Replacement: отдельный change
> `open-installation-from-assignment-order-original`.

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР, уполномоченный открывать монтажные работы
- Публичный командный шов: `InstallationProcess.openInstallation(installationObjectId, actualStartDate, actorId)`
- Публичный шов наблюдения: результаты `prepare → confirm → open` и `InstallationProcess.getInstallationObjectProcess(installationObjectId)` через новый экземпляр и новое MariaDB-соединение

## 1. Цель и граница

Подключить утверждённый успешный `OPEN-INSTALLATION-001 v0.2` к production MariaDB persistence. Один публичный вызов открытия должен атомарно сохранить root opening facts и событие, после чего полная opened-проекция восстанавливается новым соединением без in-memory состояния и внешних чтений.

Срез является durability tracer успешного пути. Он не добавляет task/queue behavior, новые бизнес-отказы или failure/reconciliation semantics.

## 2. Production assembly и предусловия

- production schema migrations v1 `MIGRATION-PROCESS-001`, v2 `WORKFORCE-CATALOG-001` и v3 `PROCESS-USER-DIRECTORY-001` применены с одним уникальным допустимым test-prefix;
- SQL fixture создаёт только пустое дело объекта монтажа `4512`, `process_state = needs_assignment_order`, revision `1`;
- production `MariaDbInstallationProcessEnvironment` является единственным process persistence adapter;
- current Workforce lookup использует production `fm2_workforce_catalog`/MariaDB delegate;
- строка монтажника `1042` строго равна example `WORKFORCE-CATALOG-001`: `employed`, `employedFrom = 2024-02-01`, `employedTo = null`, с утверждёнными ФИО/должностью/source/freshness;
- authorization, LegacyInstallationObject snapshot, engineer directory, clock и renderer предоставлены детерминированным составным delegate; actor `18` разрешён для prepare, confirm и open;
- reload delegate запрещает все внешние методы исключением;
- `fm2_process_user_capabilities` содержит независимую sentinel row только для проверки отсутствия внешних writes.

Production authorization/legacy/engineer/renderer не включаются, чтобы тест оставался чувствителен именно к open persistence и production Workforce recheck.

## 3. Предусловие через публичные команды

Fixture не вставляет распоряжение или opening facts SQL. Один production module выполняет:

```php
$prepare = $process->prepareAssignmentOrder(4512, [1042], 73, 18);
$confirm = $process->confirmOrderRegistration(4512, 1, ' 12-Р ', 'manual', 18);
```

Clock последовательно возвращает моменты утверждённых slices:

```text
prepare = 2026-08-26T21:30:00+00:00
confirm = 2026-08-28T12:15:30+03:00
```

Перед открытием полная публичная проекция строго равна `PERSISTENCE-REGISTRATION-001 v0.2`: одна registered-версия `1`, номер `12-Р`, неизменные snapshots/artifacts/assignments, два события, `processState = assignment_order_prepared`, root opening fields равны `null`, gates ложны, `openTasks = []`.

## 4. Действие открытия и точный результат

Clock переключается на `2026-08-28T12:45:00+03:00`. Выполняется ровно один вызов:

```php
$result = $process->openInstallation(4512, '2026-08-28', 18);
```

Production Workforce delegate перечитывает current row только для `installerTabId = 1042`; кадровая проверка на actual date успешна.

Команда возвращает:

```text
accepted = true
processState = working
actualStartDate = "2026-08-28"
openedAt = "2026-08-28T12:45:00+03:00"
openedByUserId = 18
installationOpened = true
checklistAvailable = true
assignmentOrderVersion = 1
```

## 5. Атомарное логическое изменение MariaDB

При row lock дела и ожидаемой revision production environment одним сохранением:

1. логически подтверждает текущую registered version `1` и её непустой installer composition;
2. обновляет `fm2_installation_cases`: `actual_start_date = 2026-08-28`, `opened_at = 2026-08-28T12:45:00+03:00`, `opened_by_user_id = 18`, `process_state = working`, `updated_at` и техническую revision;
3. добавляет ровно одно событие `installation_opened`;
4. подтверждает всё одним `COMMIT`.

Предметно наблюдаются одна версия, один состав, два artifacts и ровно три события без дублей. Registered order, its registration metadata, snapshots, assignments, artifacts и два прежних события не меняются. Task rows не создаются.

Физическая реализация обязана использовать `UPDATE` существующей case row и append одного event, не удаляя/пересоздавая case/order/children/history. Это Gate 5 implementation invariant из append-only истории и foreign keys; скрытый row identity не заявляется наблюдаемым через Gate 2 public seam.

## 6. Наблюдение новым соединением

После успеха исходные module/environment/delegates/connection уничтожаются. Новый production environment получает новое MariaDB-соединение и delegate, запрещающий authorization, clock, renderer, LegacyInstallationObject, Workforce и UserDirectory reads. Новый модуль вызывает:

```php
$projection = $reloadedProcess->getInstallationObjectProcess(4512);
```

Полная проекция строго равна `OPEN-INSTALLATION-001 v0.2` success projection:

- `installationObjectId = 4512`, `processState = working`;
- `actualStartDate = 2026-08-28`, `openedAt = 2026-08-28T12:45:00+03:00`, `openedByUserId = 18`;
- `installationOpened = true`, `checklistAvailable = true`;
- ровно registered version `1` с exact registration metadata;
- неизменные object/installer/engineer snapshots, order date, organization form, assignments и два exact artifacts `PERSISTENCE-PREPARE-001`;
- `openTasks = []`;
- события в точном порядке `assignment_order_prepared`, `assignment_order_registered`, `installation_opened`.

Третье событие:

```text
type = installation_opened
occurredAt = "2026-08-28T12:45:00+03:00"
actorId = 18
payload = {
  actualStartDate: "2026-08-28",
  assignmentOrderVersion: 1,
  installerCount: 1
}
```

Root `actualStartDate`, `openedAt` и `openedByUserId` являются обязательными полями public hydration; вычислить только booleans по наличию даты недостаточно.

## 7. Неизменность внешних источников

До `prepare` test фиксирует литеральные rows `fm2_workforce_catalog` и sentinel `fm2_process_user_capabilities`. После открытия их SQL equality сохраняется: process commands и Workforce delegate выполняют только чтение current catalogs.

Детерминированные legacy/user fixtures не получают write-вызовов. Historical installer snapshot в order остаётся значением момента подготовки; current Workforce recheck не переписывает его и не создаёт второй assignment.

## 8. Повтор, конкурентность, отказы и аудит

Срез выполняет один успешный open. Унаследованный default запрещает второе открытие, дубль события и перезапись first opening facts, но exact outcomes безопасного повтора, concurrency/stale revision, confirmed rollback, unknown `COMMIT` и reconciliation остаются отдельными MariaDB slices.

`REGISTERED_ORDER_COMPOSITION_INVALID` из `OPEN-INSTALLATION-001 v0.2` остаётся доменным инвариантом, но corrupted MariaDB fixture не входит в этот success tracer. Остальные auth/status/date/Workforce rejected paths также отдельно тестируются.

При неподтверждённом сохранении частичная opened projection недопустима. DB/SQL details не становятся business response.

## 9. Публичный Gate 2 seam

Интеграционный тест:

1. применяет production migrations; fixture SQL создаёт initial case и external-current/sentinel rows, но не order/events/opening facts;
2. вызывает публичные prepare, confirm и один open;
3. проверяет точные command results;
4. уничтожает исходные объекты/connection;
5. новым connection вызывает публичную полную projection с external reads forbidden;
6. использует SQL equality только для current external tables `fm2_workforce_catalog` и `fm2_process_user_capabilities`;
7. не выполняет SQL assertions к installation case/order/installers/artifacts/tasks/events.

Expected values наследуются из утверждённых domain/persistence examples и exact literals разделов 3–6, а не вычисляются из DB rows или production output. Strict public equality чувствительна к missing root hydration/update/event, duplicate domain facts, changed history, external rehydration или случайно созданной task. Физический способ update проверяется Gate 5, не hidden SQL assertion.

## 10. Не входит в срез

- production authorization/LegacyInstallationObject/UserDirectory/renderer assembly;
- task/engineer queue и inspection SLA;
- rejected/integrity fixtures;
- safe repeat, concurrency, rollback и unknown commit;
- Workforce freshness/outage policy;
- legacy projection;
- HTTP/UI.

## 11. Решения и доказательства

- `specs/OPEN-INSTALLATION-001.md` v0.2: exact success result/projection/event and integrity guard.
- `specs/PERSISTENCE-REGISTRATION-001.md` v0.2: durable registered precondition and public-only process observation.
- `specs/WORKFORCE-CATALOG-001.md`: production current catalog lookup and immutable historical installer snapshot.
- `docs/fmonitor-2-pilot-data-model.md`: root opening columns, atomic command and checklist gate.

## 12. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать работу и выбрал real MariaDB durability tracer успешного открытия следующим единичным SSD + TDD-срезом.

Gate 2 разрешён для версии `0.1`.
