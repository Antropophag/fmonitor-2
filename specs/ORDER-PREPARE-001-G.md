# ORDER-PREPARE-001-G — аудит объединённых причин

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-27`
- Родительская спецификация: [`ORDER-PREPARE-001`](ORDER-PREPARE-001.md)
- Проекция аудита: [`ORDER-PREPARE-001-E`](ORDER-PREPARE-001-E.md)
- Актор: сотрудник ФКР с полномочием `assignment_order.prepare`
- Командный шов: `InstallationProcess.prepareAssignmentOrder(...)`
- Шов наблюдения: `InstallationProcess.getInstallationObjectProcess(installationObjectId)`

## 1. Цель среза

Доказать, что один отказ команды с отсутствующими монтажником и инженером создаёт ровно одно событие аудита с обеими причинами в нормативном порядке и не создаёт частичных доменных фактов.

## 2. Предусловия

- объект монтажа `4512` существует и доступен актору `18`;
- процессное состояние дела — `needs_assignment_order`;
- распоряжений и назначений нет;
- открыта одна задача `prepare_assignment_order` роли `fkr`;
- история событий пуста;
- актор `18` имеет полномочие `assignment_order.prepare` и право читать аудит;
- серверные часы зафиксированы на `2026-08-27T10:25:00+03:00`;
- остальные предусловия Example C из `ORDER-PREPARE-001` выполнены.

## 3. Действие

```text
prepareAssignmentOrder(
  installationObjectId: 4512,
  installerTabIds: ["", "   "],
  controlEngineerUserId: null,
  actorId: 18
)
```

Команда возвращает две утверждённые причины в порядке:

1. `INSTALLER_REQUIRED`;
2. `CONTROL_ENGINEER_REQUIRED`.

## 4. Наблюдаемый результат

После отказа `getInstallationObjectProcess(4512)` возвращает исходное состояние и одно событие:

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
      occurredAt: "2026-08-27T10:25:00+03:00",
      actorId: 18,
      payload: {
        reasonCodes: [
          "INSTALLER_REQUIRED",
          "CONTROL_ENGINEER_REQUIRED"
        ],
        installerCount: 0,
        controlEngineerProvided: false
      }
    }
  ]
}
```

## 5. Инварианты

- две причины одной команды сохраняются одним событием, а не двумя;
- порядок `reasonCodes` совпадает с порядком причин в результате команды;
- `installerCount` вычисляется после удаления пустых и пробельных элементов;
- `processState`, распоряжения, назначения и открытые задачи не меняются;
- документ, приложение и legacy-проекция не создаются и не обновляются;
- событие не содержит исходный массив, идентификатор инженера, ФИО или внутренние идентификаторы хранения.

## 6. Независимое ожидаемое значение

- оба кода и их порядок заданы Example C родительской спецификации;
- `installerCount = 0` следует из правил нормализации;
- `controlEngineerProvided = false` следует из `null`;
- одно событие следует из правила «одна отклонённая команда — один аудиторский факт»;
- время задано фиксированными серверными часами.

## 7. Не входит в срез

- security-аудит `FORBIDDEN`;
- повтор команды и идемпотентность;
- успешное формирование распоряжения;
- DB-схема, HTTP-представление и проверка внутренних вызовов.

## 8. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-27`
- Решение: `APPROVED`
- Комментарий: утверждено сообщением «оок» в рабочей сессии.

Gate 1 пройден. Следующий обязательный шаг — падающий тест по утверждённым швам.
