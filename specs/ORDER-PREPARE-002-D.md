# ORDER-PREPARE-002-D — отказ при отсутствии регистрационного номера объекта

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-27`
- Актор: сотрудник ФКР с полномочием `assignment_order.prepare`
- Публичный командный шов: `InstallationProcess.prepareAssignmentOrder(installationObjectId, installerTabIds[], controlEngineerUserId, actorId)`
- Публичный шов наблюдения: результат команды и `InstallationProcess.getInstallationObjectProcess(installationObjectId)`

## 1. Цель среза

Не допустить подготовки распоряжения без регистрационного номера объекта/лифта, обязательного колонкой `Рег. номер` нормативного приложения.

Проверяется `installationObjectSnapshot.objectRegistrationNumber`, источником которого в пилоте является `fm_maintable.regnumber`. Это не регистрационный номер распоряжения в 1С ДО: последний у подготовленной версии ещё отсутствует и добавляется отдельной командой после внешней регистрации.

## 2. Предусловия

- авторизация и обязательный состав успешно проверены;
- объект монтажа существует, доступен и имеет состояние `needs_assignment_order` без версии, открытия, завершения и даты Акта ПТО;
- адрес, подъезд/секция и обе актуальные плановые даты заполнены;
- выбран один допустимый монтажник и один допустимый инженер;
- каталоги людей и renderer при этом отказе не вызываются;
- конкурентного изменения нет.

## 3. Нормализация и порядок

`objectRegistrationNumber` отсутствует при `null`, пустой строке либо строке, пустой после удаления пробелов по краям. Непустое значение команда не исправляет.

Порядок после загрузки снимка: `address` → `entrance` → `objectRegistrationNumber`. Только затем допускаются последующие проверки, каталоги людей, бизнес-дата и renderer. Полный ответ при нескольких пропусках определяет `ORDER-PREPARE-002-E` после его утверждения; этот срез сохраняет одиночный ответ номера объекта.

## 4. Наблюдаемый отказ

```text
accepted = false
violations = [
  {
    code: INSTALLATION_OBJECT_REQUIRED_DATA_MISSING,
    message: "В объекте монтажа не заполнен регистрационный номер объекта.",
    field: objectRegistrationNumber
  }
]
```

## 5. Состояние и аудит

Версии, назначения и артефакты не создаются; задача `prepare_assignment_order` остаётся открытой; `processState = needs_assignment_order`; работы и чек-лист закрыты. Добавляется одно событие `assignment_order_prepare_rejected`:

```text
reasonCodes = [INSTALLATION_OBJECT_REQUIRED_DATA_MISSING]
missingFields = [objectRegistrationNumber]
installerCount = 1
controlEngineerProvided = true
```

Событие содержит также `installationObjectId`, `actorId`, серверный `occurredAt` и не содержит значений объекта монтажа либо персональных данных выбранных людей.

## 6. Исполняемый пример A

```text
installationObjectId = 4512
installerTabIds = [1042]
controlEngineerUserId = 73
actorId = 18
now = 2026-08-27T12:20:00+03:00

address = "Москва, ул. Примерная, д. 10"
entrance = "2"
objectRegistrationNumber = "   "
plannedStartDate = 2026-10-05
plannedFinishDate = 2026-12-20
ptoActDate = null
```

Ожидается точный отказ из раздела 4. Исходное состояние `needs_assignment_order`, версии, назначения, открытая задача подготовки и закрытые шлюзы работ/чек-листа не меняются; добавляется только событие из раздела 5 с `occurredAt = 2026-08-27T12:20:00+03:00`. Fixture запрещает каталоги людей и renderer.

## 7. Приоритеты

- `FORBIDDEN` и нарушения обязательного состава проверяются раньше снимка объекта монтажа.
- отсутствие адреса и затем подъезда/секции имеет приоритет над этим отказом;
- срез добавляет только `INSTALLATION_OBJECT_REQUIRED_DATA_MISSING` для `field = objectRegistrationNumber`.

## 8. Не входит в срез

- отсутствие плановых дат и объединение нескольких полей;
- форматная проверка непустого номера;
- регистрационный номер распоряжения 1С ДО;
- прочие отказы состояния, людей, renderer, persistence и конкурентности;
- успешная подготовка, UI и HTTP.

## 9. Решения и доказательства

- [`order-template-required-inputs.md`](../docs/order-template-required-inputs.md): `Рег. номер` — обязательная колонка приложения; источник — `fm_maintable.regnumber`.
- [`ORDER-PREPARE-002-C.md`](ORDER-PREPARE-002-C.md) и [`ORDER-PREPARE-002-B.md`](ORDER-PREPARE-002-B.md): порядок ранних проверок обязательного снимка и контракт отказа без частичных изменений.
- [`fmonitor-2-primary-spec.md`](../docs/fmonitor-2-primary-spec.md): различие регистрационного номера объекта и номера распоряжения 1С ДО.

## 10. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-27`
- Решение: `APPROVED`
- Комментарий: утверждено сообщением «ок» в рабочей сессии.

Gate 1 пройден. Следующий обязательный шаг — один падающий тест по подтверждённому публичному шву.
