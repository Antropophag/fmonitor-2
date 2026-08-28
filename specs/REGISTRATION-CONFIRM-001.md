# REGISTRATION-CONFIRM-001 — вручную подтвердить регистрацию подготовленного распоряжения

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР, уполномоченный подтверждать регистрацию распоряжения
- Публичный командный шов: `InstallationProcess.confirmOrderRegistration(installationObjectId, assignmentOrderVersion, registrationNumber, source, actorId)`
- Публичный шов наблюдения: результат команды и `InstallationProcess.getInstallationObjectProcess(installationObjectId)`

## 1. Цель и граница

Подтвердить вручную, что точная текущая подготовленная версия распоряжения получила регистрационный номер в 1С ДО. Команда атомарно переводит ту же версию `prepared → registered`, добавляет регистрационные реквизиты и аудит, не пересобирая документ и не создавая нового этапа монтажного дела.

Это первый доменный tracer команды в in-memory окружении. MariaDB persistence регистрации, production authorization, HTTP/UI, безопасный повтор и конкурентные варианты получают отдельные вертикальные срезы.

## 2. Предусловия

- объект монтажа `4512` находится в публичном состоянии после успешного `ORDER-PREPARE-002` example A;
- его точная текущая версия распоряжения `1` имеет `status = prepared` и `registrationNumber = null`;
- версии `2` нет;
- сотрудник `18` авторизован на ручное подтверждение регистрации;
- серверный момент команды: `2026-08-28T12:15:30+03:00`;
- process-state `assignment_order_prepared`, состав, два артефакта, предварительные назначения и одно событие подготовки совпадают с утверждённым примером;
- работы не открыты, чек-лист недоступен, открытых процессных задач нет.

Авторизация подтверждения является отдельным внутренним решением окружения `actorCanConfirmOrderRegistration(actorId)`. Этот in-memory срез не назначает ей production capability и не расширяет capability `assignment_order.prepare` неявно.

## 3. Вход и нормализация

Действие:

```php
$result = $process->confirmOrderRegistration(
    4512,
    1,
    ' 12-Р ',
    'manual',
    18,
);
```

Для ручного источника:

- `registrationNumber` нормализуется удалением окружающего whitespace;
- внутренние символы, регистр и знак `Р` не исправляются;
- сохранённое значение равно `12-Р`;
- `source` имеет точное значение `manual`;
- `registrationActorType = user`;
- `registrationActorId = actorId`;
- `externalRegistrationId = null`.

Непустой номер является предусловием этого успешного tracer. Точный отказ для пустого после trim номера зарезервирован как `REGISTRATION_NUMBER_REQUIRED`, но не реализуется и не тестируется этим срезом.

## 4. Точный результат команды

Команда возвращает:

```text
accepted = true
assignmentOrderVersion = 1
status = registered
registrationNumber = "12-Р"
registeredAt = "2026-08-28T12:15:30+03:00"
registrationActorType = user
registrationActorId = 18
registrationSource = manual
externalRegistrationId = null
processState = assignment_order_prepared
```

`processState` намеренно не переименовывается и не переводится в отдельное `ready_to_open`: подтверждение регистрации не является новым этапом монтажного дела. Будущая `openInstallation` определяет допустимость по статусу точной актуальной версии `registered`, а не по process-state, наличию номера или отдельной задаче.

## 5. Точная публичная проекция после команды

`getInstallationObjectProcess(4512)` возвращает:

- `installationObjectId = 4512`;
- `processState = assignment_order_prepared`;
- ровно одну версию распоряжения `1`;
- для версии `1`: `status = registered`, `registrationNumber = "12-Р"`, `registeredAt = "2026-08-28T12:15:30+03:00"`, `registrationActorType = user`, `registrationActorId = 18`, `registrationSource = manual`, `externalRegistrationId = null`;
- неизменную `assignmentOrderDate = 2026-08-27` и `organizationType = individual`;
- побайтно неизменные снимок объекта монтажа, снимок монтажника `1042`, снимок инженера `73`, метаданные/размеры/SHA-256 двух артефактов;
- те же два предварительных assignment links версии `1`; команда не изменяет их участников, интервалы или исторические значения, а окончательность основания следует из `assignmentOrders[0].status = registered`;
- `openTasks = []`: отдельная задача регистрации не создаётся и задача открытия этим срезом также не создаётся;
- `installationOpened = false` и `checklistAvailable = false`;
- ровно два append-only события в порядке совершения: прежнее `assignment_order_prepared`, затем новое `assignment_order_registered`.

Все неизменные литералы первого события, состава и артефактов наследуются из `ORDER-PREPARE-002` example A. В частности, подготовленный документ не вызывается повторно, его filenames, byte sizes и SHA-256 не меняются.

## 6. Событие регистрации

Второе событие имеет точную форму:

```text
type = assignment_order_registered
occurredAt = "2026-08-28T12:15:30+03:00"
actorId = 18
payload = {
  assignmentOrderVersion: 1,
  registrationNumber: "12-Р",
  registrationSource: manual,
  registrationActorType: user
}
```

Событие не дублирует снимки объекта/людей, содержимое или хэши документов, DB identifiers и исходную строку с окружающими пробелами. Отдельное security-событие при успехе не создаётся.

## 7. Атомарность и неизменяемая история

Одно сохранение окружения атомарно меняет только регистрационные поля точной версии `1`, её status и добавляет одно событие. Оно не:

- создаёт версию `2`;
- меняет `order_date`, состав, снимки, артефакты или событие подготовки;
- формирует документы повторно;
- открывает работы или чек-лист;
- создаёт/завершает process task;
- обновляет legacy-проекцию.

Если атомарное сохранение не подтверждено, частично зарегистрированная версия не является допустимым наблюдаемым состоянием. Persistence failure behavior наследуется общими контрактами, но отдельный failure-example не входит в этот in-memory success-slice.

## 8. Авторизация, точность версии и повтор

Авторизация выполняется до раскрытия состояния объекта/версии. Точная версия `1` должна быть текущей и иметь status `prepared`; команда не ищет «любую подготовленную» версию и не переносит номер на другую версию.

Унаследованный сквозной инвариант запрещает дубль события и перезапись результата при повторе/конкурентной отправке. Точные публичные результаты для неавторизованного актора, отсутствующей/неактуальной версии, status не `prepared`, безопасного повтора и конкуренции намеренно остаются следующими спецификациями; этот тест выполняет ровно один успешный вызов.

Область уникальности номера `12-Р`, исправление ошибочного номера и конфликт с номером другого распоряжения не решаются этим примером. Он утверждает только сохранение одного независимо заданного непустого номера на одной точной версии.

## 9. Публичный seam теста и независимые литералы

Тест:

1. создаёт `InMemoryInstallationProcessEnvironment` в точном публичном состоянии `ORDER-PREPARE-002` example A;
2. разрешает актору `18` подтверждение и задаёт clock `2026-08-28T12:15:30+03:00`;
3. вызывает только публичную `confirmOrderRegistration(...)` один раз;
4. наблюдает только command-result и публичную `getInstallationObjectProcess(4512)`;
5. запрещает renderer и внешние object/workforce/user directory reads, чтобы регистрация не пересобирала и не восстанавливала подготовленную версию.

Номер `12-Р`, момент регистрации, actor metadata, source и event payload заданы этой спецификацией до реализации. Неизменные expected values копируются из нормативного example A, а не из текущего in-memory state после production-команды.

## 10. Не входит в срез

- MariaDB persistence/hydration регистрационных полей;
- production authorization capability подтверждения;
- `source = one_c_do` и integration principal;
- `externalRegistrationId` и интеграционная идемпотентность;
- пустой номер и неподдерживаемый source;
- unauthorized, wrong-version, non-current-version и wrong-status examples;
- повтор, concurrency, rollback и unknown `COMMIT`;
- уникальность номера, исправление и отмена регистрации;
- финальный файл из 1С ДО;
- `openInstallation`, задача открытия и доступ к чек-листу;
- HTTP/UI.

## 11. Решения и доказательства

- `docs/fmonitor-2-primary-spec.md`: общий command seam, manual/one_c_do sources, точная версия, отсутствие отдельного этапа/задачи.
- `docs/fmonitor-2-pilot-data-model.md`: регистрационные поля, атомарный `prepared → registered`, неизменность документа и gate будущего открытия по status.
- `specs/ORDER-PREPARE-002.md`: точное исходное состояние, snapshots, artifacts, assignments и событие подготовки.
- `specs/ORDER-PREPARE-005.md`: зарегистрированная версия неизменяема и не заменяется повторной первой подготовкой.

## 12. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать работу, принимать лучшие решения и выбрал успешное ручное подтверждение регистрации следующей единичной доменной вертикалью.

Gate 2 разрешён для версии `0.1`.
