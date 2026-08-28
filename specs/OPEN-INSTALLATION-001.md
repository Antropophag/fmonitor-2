# OPEN-INSTALLATION-001 — успешно открыть монтажные работы

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР, уполномоченный открывать монтажные работы
- Публичный командный шов: `InstallationProcess.openInstallation(installationObjectId, actualStartDate, actorId)`
- Публичный шов наблюдения: результат команды и `InstallationProcess.getInstallationObjectProcess(installationObjectId)`

## 1. Цель и граница

Зафиксировать успешное открытие работ по объекту монтажа на указанную фактическую дату после регистрации точной актуальной версии распоряжения. Команда повторно подтверждает кадровую допустимость текущего состава на фактическую дату, сохраняет отдельный факт открытия и делает чек-лист доступным.

Срез является одним in-memory domain tracer. Отказы, безопасный повтор/конкуренция, MariaDB persistence и постановка следующей задачи получают отдельные вертикальные спецификации.

## 2. Предусловия через публичные команды

Исходное состояние создаётся не прямой мутацией process-фактов, а утверждёнными публичными командами:

1. `prepareAssignmentOrder(4512, [1042], 73, 18)` создаёт версию `1` с датой распоряжения `2026-08-27`;
2. `confirmOrderRegistration(4512, 1, ' 12-Р ', 'manual', 18)` переводит её в `registered` и сохраняет номер `12-Р`.

Перед открытием:

- версия `1` является точной текущей и имеет status `registered`;
- состав версии содержит монтажника `1042` и инженера `73`;
- работы ещё не открыты: `actualStartDate = null`, `openedAt = null`, `openedByUserId = null`;
- `processState = assignment_order_prepared`, `installationOpened = false`, `checklistAvailable = false`;
- actor `18` авторизован на открытие;
- server clock для команды открытия равен `2026-08-28T12:45:00+03:00`, московская business-date — `2026-08-28`;
- current Workforce lookup для `1042` возвращает `status = employed`, `employedFrom = 2024-02-01`, `employedTo = null`;
- открытых process tasks нет.

## 3. Действие и обязательные успешные проверки

```php
$result = $process->openInstallation(4512, '2026-08-28', 18);
```

Для принятия именно этого вызова команда последовательно подтверждает:

1. actor авторизован через внутренний `actorCanOpenInstallation(actorId)`;
2. работы ещё не открыты;
3. точная актуальная версия существует и имеет status `registered` — наличие одного registration number не заменяет status gate;
4. `actualStartDate` является календарной датой `YYYY-MM-DD`;
5. `2026-08-28` не раньше неизменяемой `assignmentOrderDate = 2026-08-27`;
6. `2026-08-28` не позже московской business-date `2026-08-28`; равенство допустимо;
7. каждый монтажник состава текущей registered-версии повторно найден в current Workforce catalog и имеет `status = employed`, `employedFrom <= actualStartDate`, а `employedTo` отсутствует либо `employedTo >= actualStartDate`; границы включительны.

Повторная кадровая проверка выполняется через отдельный внутренний current-catalog seam:

```text
findCurrentInstallerSnapshot(installerTabId)
```

Он возвращает тот же контракт снимка, что Workforce adapter подготовки, но его результат используется только для решения на фактическую дату. Команда не заменяет им исторический installer snapshot распоряжения.

## 4. Точный результат команды

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

`processState = working` является следствием сохранённых registered-order и opening facts. Пользователь не передаёт status и не выбирает его из списка.

## 5. Публичная проекция после открытия

`getInstallationObjectProcess(4512)` возвращает:

- `installationObjectId = 4512`;
- `processState = working`;
- `actualStartDate = "2026-08-28"`;
- `openedAt = "2026-08-28T12:45:00+03:00"`;
- `openedByUserId = 18`;
- `installationOpened = true`, `checklistAvailable = true`;
- ровно одну registered-версию `1` с номером `12-Р` и registration metadata `REGISTRATION-CONFIRM-001`;
- побайтно неизменные assignment-order date, object/installer/engineer snapshots, organization form, composition и два artifacts;
- те же assignment links версии `1`; current Workforce response не переписывает их historical fields;
- `openTasks = []`;
- ровно три append-only события в порядке: `assignment_order_prepared`, `assignment_order_registered`, `installation_opened`.

Отдельная задача инспекции не создаётся этим срезом. Тип, assignee semantics, due-date/SLA и момент постановки такой задачи ещё не утверждены; они требуют отдельного task/queue slice. Отсутствие задачи не отменяет успешный факт открытия и доступность чек-листа.

## 6. Событие открытия

Третье событие имеет точную форму:

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

Событие не содержит ФИО, табельные номера, кадровые даты, registration number, содержимое/хэши документов, SQL или полное тело команды.

## 7. Атомарность и неизменяемая история

Одно атомарное сохранение:

- записывает actual-start/opened audit fields;
- выводит `processState = working`;
- добавляет ровно одно событие `installation_opened`;
- одновременно делает `installationOpened` и `checklistAvailable` истинными.

Команда не изменяет зарегистрированное распоряжение, состав, artifacts, прежние события или current Workforce catalog. Она не вызывает renderer, LegacyInstallationObject или User/Engineer directory: требуемые order date, состав и snapshots уже сохранены, а authorization и Workforce имеют отдельные seams.

Если атомарное сохранение не подтверждено, состояние с частично доступным чек-листом не является допустимым успехом.

## 8. Авторизация, аудит, повтор и отказы

### Integrity rejection: зарегистрированная версия без монтажников

После проверки авторизации, отсутствия предыдущего открытия и status актуальной версии команда обязана убедиться, что `assignmentOrders[current].installers` является непустым списком. Пустой/отсутствующий список в registered-версии означает повреждённое persistence/import state, а не возможность открыть работы без состава.

Команда fail closed до current Workforce lookup, валидации/сравнения actual-start date и сохранения opening fields. Она возвращает:

```text
accepted = false
violations = [
  {
    code: REGISTERED_ORDER_COMPOSITION_INVALID,
    message: "Зарегистрированное распоряжение не содержит ни одного монтажника. Открытие работ невозможно.",
    field: null
  }
]
```

Добавляется ровно одно process-событие отказа:

```text
type = installation_open_rejected
occurredAt = "2026-08-28T12:45:00+03:00"
actorId = 18
payload = {
  reasonCodes: [REGISTERED_ORDER_COMPOSITION_INVALID],
  assignmentOrderVersion: 1,
  installerCount: 0
}
```

Clock вызывается только для обязательного audit timestamp; actual-start не сохраняется. Событие не содержит registration number, ФИО/ID инженера, actualStartDate, кадровые факты или внутренние DB identifiers. Security-событие не создаётся, потому что actor авторизован, а отказ относится к integrity process-state.

После отказа `processState = assignment_order_prepared`, `actualStartDate/openedAt/openedByUserId = null`, `installationOpened = false`, `checklistAvailable = false`; registered-версия, assignments, artifacts и прежние события не изменены. Добавлено только событие отказа. Current Workforce, renderer, LegacyInstallationObject и User/Engineer directory не вызываются.

### Остальная граница

Срез не вводит точные публичные результаты для:

- неавторизованного актора;
- отсутствующей или не-`registered` актуальной версии;
- пустой/невалидной даты, даты раньше распоряжения или позже business-date;
- уже открытых работ;
- отсутствующего/уволенного на actualStartDate текущего монтажника;
- MariaDB/clock/Workforce failure;
- повторного или конкурентного вызова.

Эти проверки обязательны для успешного пути раздела 3, но их reasons/audit требуют отдельных red tests/specifications. Сквозной default безопасного повтора и конкуренции наследуется: повтор не может создать второе открытие или событие и не переписывает первый факт.

Успешный аудит представлен единственным событием раздела 6. Security-событие при успехе не создаётся.

## 9. Публичный seam теста и независимые литералы

In-memory интеграционный тест:

1. создаёт зарегистрированную версию исключительно через публичные prepare/confirm commands;
2. переключает clock на `2026-08-28T12:45:00+03:00`;
3. предоставляет actor-open authorization и current Workforce literal для `1042`;
4. вызывает только публичную `openInstallation(...)` один раз;
5. наблюдает только command-result и полную `getInstallationObjectProcess(4512)`;
6. запрещает renderer, object и engineer-directory reads во время открытия;
7. проверяет, что current Workforce вызван ровно для единственного участника `[1042]`.

Actual date, clock, actor, version и event payload заданы этой спецификацией до реализации. Полная неизменная order projection наследуется из `ORDER-PREPARE-002` и `REGISTRATION-CONFIRM-001`, а не копируется из post-command production state.

Отдельный integrity test сначала создаёт version `1` через те же публичные prepare/confirm commands, затем test-support намеренно имитирует допустимое для защитной проверки повреждение persistence/import state: заменяет только внутренний `assignmentOrders[0].installers` на пустой список. После публичного `openInstallation(4512, '2026-08-28', 18)` он ожидает точный отказ и projection раздела 8. Прямая test-support corruption является только fixture предусловием; действие и все assertions остаются на публичном seam. Workforce fixture запрещает любой вызов.

## 10. Не входит в срез

- остальные exact rejected command results и rejected audit;
- production open capability mapping;
- MariaDB persistence/hydration открытия;
- safe repeat, optimistic concurrency, rollback и unknown commit;
- Workforce outage/freshness threshold;
- повторная проверка planned finish после подготовки;
- создание задачи/очереди инженера и SLA первой инспекции;
- checklist content, inspection и progress;
- legacy projection;
- HTTP/UI.

## 11. Решения и доказательства

- `PRODUCT.md`: открытие требует фактическую дату и зарегистрированное распоряжение; status следует из фактов; checklist закрыт до открытия.
- `docs/fmonitor-2-primary-spec.md`: `openInstallation`, status gate, дата не раньше order date и не позже business-date.
- `docs/fmonitor-2-pilot-data-model.md`: actual-start/open audit fields, atomic command and repeated Workforce check at opening.
- `specs/REGISTRATION-CONFIRM-001.md`: exact registered precondition with no separate stage/task.
- `specs/ORDER-PREPARE-003.md`: dated employment facts and inclusive employment boundaries.

## 12. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать работу и выбрал in-memory opening tracer следующим единичным SSD + TDD-срезом; версия 0.2 повторно утверждена с fail-closed integrity rejection для повреждённой registered-версии без монтажников.

Gate 2 разрешён для версии `0.2`.
