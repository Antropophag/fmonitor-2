# ORDER-PREPARE-001-F — аудит отсутствующего инженера

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-27`
- Родительская спецификация: [`ORDER-PREPARE-001`](ORDER-PREPARE-001.md)
- Проекция аудита: [`ORDER-PREPARE-001-E`](ORDER-PREPARE-001-E.md)
- Актор: сотрудник ФКР с полномочием `assignment_order.prepare`
- Командный шов: `InstallationProcess.prepareAssignmentOrder(...)`
- Шов наблюдения: `InstallationProcess.getInstallationObjectProcess(installationObjectId)`

## 1. Цель среза

Доказать, что отказ `CONTROL_ENGINEER_REQUIRED` сохраняет минимальный append-only аудит и не создаёт частичных доменных фактов.

## 2. Предусловия

- объект монтажа `4512` существует и доступен актору `18`;
- процессное состояние дела — `needs_assignment_order`;
- версий распоряжения и назначений ещё нет;
- открыта одна задача `prepare_assignment_order` роли `fkr`;
- история событий пуста;
- монтажник с табельным идентификатором `1042` существует и допустим;
- актор `18` имеет полномочие `assignment_order.prepare` и право читать аудит;
- серверные часы зафиксированы на `2026-08-27T10:20:00+03:00`;
- остальные предусловия Example B из `ORDER-PREPARE-001` выполнены.

## 3. Действие

```text
prepareAssignmentOrder(
  installationObjectId: 4512,
  installerTabIds: [1042],
  controlEngineerUserId: null,
  actorId: 18
)
```

Команда возвращает утверждённый отказ `CONTROL_ENGINEER_REQUIRED`.

## 4. Наблюдаемый результат

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
      occurredAt: "2026-08-27T10:20:00+03:00",
      actorId: 18,
      payload: {
        reasonCodes: ["CONTROL_ENGINEER_REQUIRED"],
        installerCount: 1,
        controlEngineerProvided: false
      }
    }
  ]
}
```

По сравнению с проекцией до команды изменился только список `events`: добавлено ровно одно событие.

## 5. Инварианты

- `processState`, распоряжения, назначения и открытые задачи не меняются;
- документ, приложение и legacy-проекция не создаются и не обновляются;
- событие содержит код отказа, количество нормализованных уникальных монтажников и признак наличия инженера;
- событие не содержит табельный номер `1042`, ФИО, полное тело команды или внутренние идентификаторы хранения;
- публичная форма проекции совпадает с утверждённой в `ORDER-PREPARE-001-E`.

## 6. Независимое ожидаемое значение

- исходная проекция задана предусловиями;
- `CONTROL_ENGINEER_REQUIRED` и его единственность следуют из Example B;
- `installerCount = 1` следует из одного переданного допустимого идентификатора;
- `controlEngineerProvided = false` следует из `null`;
- время события задано фиксированными серверными часами.

## 7. Не входит в срез

- аудит объединённых причин и `FORBIDDEN`;
- проверка кадровой допустимости монтажника `1042`;
- успешное формирование распоряжения;
- формат хранения события и вызовы внутренних адаптеров;
- HTTP-представление и права чтения проекции.

## 8. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-27`
- Решение: `APPROVED`
- Комментарий: утверждено сообщением «ок» в рабочей сессии.

Gate 1 пройден. Следующий обязательный шаг — падающий тест по утверждённым швам.
