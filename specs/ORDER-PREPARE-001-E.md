# ORDER-PREPARE-001-E — аудит отклонения без частичного результата

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-27`
- Родительская спецификация: [`ORDER-PREPARE-001`](ORDER-PREPARE-001.md)
- Актор: сотрудник ФКР с полномочием `assignment_order.prepare`
- Командный шов: `InstallationProcess.prepareAssignmentOrder(...)`
- Шов наблюдения: `InstallationProcess.getInstallationObjectProcess(installationObjectId)`

## 1. Цель среза

Доказать, что отказ `INSTALLER_REQUIRED` не создаёт частичных доменных фактов, но сохраняет минимальный append-only аудит отклонённой команды.

Срез использует уже утверждённый Example A: актор уполномочен, инженер выбран, список монтажников пуст.

## 2. Предусловия

- объект монтажа `4512` существует и доступен актору `18`;
- процессное состояние дела — `needs_assignment_order`;
- версий распоряжения и назначений ещё нет;
- открыта одна существующая задача `prepare_assignment_order`;
- история процессных событий пуста;
- актор `18` имеет полномочие `assignment_order.prepare` и право читать аудит этого дела;
- серверные часы зафиксированы на `2026-08-27T10:15:30+03:00`;
- остальные предусловия Example A из `ORDER-PREPARE-001` выполнены.

## 3. Действие

```text
prepareAssignmentOrder(
  installationObjectId: 4512,
  installerTabIds: [],
  controlEngineerUserId: 73,
  actorId: 18
)
```

Команда возвращает утверждённый отказ `INSTALLER_REQUIRED`.

## 4. Публичная проекция процесса

Для этого среза `getInstallationObjectProcess(4512)` возвращает следующий минимальный контракт:

```text
{
  installationObjectId: int,
  processState: string,
  assignmentOrders: list,
  assignments: list,
  openTasks: list,
  events: list
}
```

Элементы `events` имеют публичную форму:

```text
{
  type: string,
  occurredAt: ISO-8601 string with offset,
  actorId: int,
  payload: object
}
```

Проекция не раскрывает имена таблиц, внутренние идентификаторы строк, stack trace или полное тело команды.

## 5. Наблюдаемый результат

После отказа `getInstallationObjectProcess(4512)` возвращает:

```text
{
  installationObjectId: 4512,
  processState: "needs_assignment_order",
  assignmentOrders: [],
  assignments: [],
  openTasks: [
    { type: "prepare_assignment_order", assigneeRole: "fkr" }
  ],
  events: [
    {
      type: "assignment_order_prepare_rejected",
      occurredAt: "2026-08-27T10:15:30+03:00",
      actorId: 18,
      payload: {
        reasonCodes: ["INSTALLER_REQUIRED"],
        installerCount: 0,
        controlEngineerProvided: true
      }
    }
  ]
}
```

По сравнению с проекцией до команды изменился только список `events`: в него добавлено ровно одно событие отклонения.

## 6. Инварианты

- `processState` остаётся `needs_assignment_order`;
- `assignmentOrders` и `assignments` остаются пустыми;
- существующая задача `prepare_assignment_order` остаётся открытой и не заменяется новой;
- документ и приложение не формируются;
- legacy-проекция не обновляется;
- событие аудита добавляется один раз на одну принятую сервером команду;
- аудит сохраняется в той же транзакционной границе, которая фиксирует результат отказа;
- событие не содержит ФИО, табельные номера, `controlEngineerUserId` или полное тело команды.

## 7. Отказы шва наблюдения

Проверка прав чтения `getInstallationObjectProcess` не входит в этот срез. Актор примера имеет требуемый доступ. Поведение для неизвестного объекта монтажа, недоступного объекта монтажа и ошибки хранилища будет задано отдельными спецификациями.

## 8. Независимое ожидаемое значение

- исходное состояние `needs_assignment_order`, пустые распоряжения/назначения и существующая задача заданы предусловиями, а не считываются из реализации;
- код `INSTALLER_REQUIRED` и поля аудита заданы утверждённой родительской спецификацией;
- время события задано фиксированными серверными часами;
- единственное допустимое изменение выведено из append-only требования аудита отказа.

## 9. Не входит в срез

- аудит `CONTROL_ENGINEER_REQUIRED`, объединённых причин и `FORBIDDEN`;
- успешное распоряжение;
- DB-схема и формат хранения события;
- HTTP-представление проекции;
- пагинация длинной истории;
- повтор одной команды и идемпотентность;
- проверка вызовов внутренних адаптеров через mocks.

## 10. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-27`
- Решение: `APPROVED`
- Комментарий: утверждено сообщением «ок» в рабочей сессии.

Gate 1 пройден. Следующий обязательный шаг — падающий тест по утверждённым командному и наблюдаемому швам.
