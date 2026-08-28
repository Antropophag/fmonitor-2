# ORDER-PREPARE-002-E — полный набор отсутствующих реквизитов объекта монтажа

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-27`
- Актор: сотрудник ФКР с полномочием `assignment_order.prepare`
- Публичный командный шов: `InstallationProcess.prepareAssignmentOrder(installationObjectId, installerTabIds[], controlEngineerUserId, actorId)`
- Публичный шов наблюдения: результат команды и `InstallationProcess.getInstallationObjectProcess(installationObjectId)`

## 1. Цель

Не допустить подготовки распоряжения при отсутствии одного или нескольких обязательных реквизитов объекта монтажа и вернуть весь набор обнаруженных пропусков за один вызов.

Обязательный набор:

1. `address`;
2. `entrance`;
3. `objectRegistrationNumber`;
4. `plannedStartDate`;
5. `plannedFinishDate`.

Обе плановые даты уже выбраны LegacyInstallationObject adapter как актуальные. Алгоритм выбора между исходными и скорректированными legacy-полями не входит в срез.

## 2. Предусловия

- авторизация и обязательный состав прошли;
- объект монтажа существует, доступен и находится в `needs_assignment_order` без версии, открытия, завершения и даты Акта ПТО;
- любые из пяти обязательных реквизитов могут отсутствовать;
- выбран один допустимый монтажник и один допустимый инженер;
- каталоги людей и renderer при отказе не вызываются;
- конкурентного изменения нет.

## 3. Нормализация и порядок

Каждый обязательный реквизит отсутствует при `null`, пустой строке либо строке, пустой после удаления пробелов по краям. Проверка формата и предметной корректности непустых значений относится к отдельным срезам.

После загрузки снимка команда проверяет все пять полей и возвращает по одной violation на каждое отсутствующее поле в нормативном порядке из раздела 1. Только при отсутствии пропусков возможны каталоги людей, бизнес-дата и renderer.

Одиночные случаи `address`, `entrance` и `objectRegistrationNumber` сохраняют точные ответы утверждённых B–D. Этот срез добавляет полный обход и случаи дат.

## 4. Наблюдаемый отказ

Для каждого пропуска возвращается `code = INSTALLATION_OBJECT_REQUIRED_DATA_MISSING` и точная пара:

| `field` | `message` |
|---|---|
| `address` | `В объекте монтажа не заполнен адрес объекта.` |
| `entrance` | `В объекте монтажа не заполнен подъезд или секция объекта.` |
| `objectRegistrationNumber` | `В объекте монтажа не заполнен регистрационный номер объекта.` |
| `plannedStartDate` | `В объекте монтажа не заполнена плановая дата начала работ.` |
| `plannedFinishDate` | `В объекте монтажа не заполнена плановая дата завершения работ.` |

`accepted = false`; массив `violations` содержит все отсутствующие поля в порядке таблицы.

## 5. Состояние и аудит

Версии, назначения и артефакты не создаются; `processState = needs_assignment_order`; задача `prepare_assignment_order` остаётся открытой; работы и чек-лист закрыты. Добавляется одно событие `assignment_order_prepare_rejected`:

```text
reasonCodes = [INSTALLATION_OBJECT_REQUIRED_DATA_MISSING]
missingFields = [<все отсутствующие поля в нормативном порядке>]
installerCount = 1
controlEngineerProvided = true
```

Событие содержит также `installationObjectId`, `actorId`, серверный `occurredAt` и не сохраняет значения объекта монтажа или персональные данные.

## 6. Исполняемый пример A

```text
installationObjectId = 4512
installerTabIds = [1042]
controlEngineerUserId = 73
actorId = 18
now = 2026-08-27T12:35:00+03:00

address = "Москва, ул. Примерная, д. 10"
entrance = "2"
objectRegistrationNumber = "77-000123"
plannedStartDate = "   "
plannedFinishDate = null
ptoActDate = null
```

Ожидается:

```text
accepted = false
violations = [
  { code: INSTALLATION_OBJECT_REQUIRED_DATA_MISSING,
    message: "В объекте монтажа не заполнена плановая дата начала работ.",
    field: plannedStartDate },
  { code: INSTALLATION_OBJECT_REQUIRED_DATA_MISSING,
    message: "В объекте монтажа не заполнена плановая дата завершения работ.",
    field: plannedFinishDate }
]
```

Исходное состояние сохраняется, добавляется только событие раздела 5 с `occurredAt = 2026-08-27T12:35:00+03:00` и `missingFields = [plannedStartDate, plannedFinishDate]`. Fixture запрещает обращения к каталогам людей и renderer.

## 7. Приоритеты

- авторизация и обязательный состав проверяются раньше снимка;
- авторизация и нарушения обязательного состава имеют приоритет над реквизитами объекта монтажа;
- реквизиты объекта монтажа возвращаются полным набором в нормативном порядке;
- в `reasonCodes` код не дублируется, даже когда violations несколько.

## 8. Не входит

- формат, диапазон и взаимный порядок дат;
- выбор актуальной даты из legacy-колонок;
- прочие отказы состояния, людей, renderer, persistence и конкурентности;
- успешная подготовка, UI и HTTP.

## 9. Решения и доказательства

- [`order-template-required-inputs.md`](../docs/order-template-required-inputs.md): дата начала — обязательная колонка нормативного приложения.
- [`ORDER-PREPARE-002-B.md`](ORDER-PREPARE-002-B.md), [`ORDER-PREPARE-002-C.md`](ORDER-PREPARE-002-C.md) и [`ORDER-PREPARE-002-D.md`](ORDER-PREPARE-002-D.md): утверждённые одиночные ответы для первых трёх реквизитов.
- [`ORDER-PREPARE-002.md`](ORDER-PREPARE-002.md): LegacyInstallationObject adapter предоставляет актуальный снимок плановой даты.

## 10. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-27`
- Решение: `APPROVED`
- Комментарий: версия `0.2` утверждена сообщением «ок» в рабочей сессии.

Gate 1 пройден. Следующий обязательный шаг — один падающий тест по подтверждённому публичному шву.
