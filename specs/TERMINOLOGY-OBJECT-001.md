# TERMINOLOGY-OBJECT-001 — объект монтажа вместо процессного «заказа»

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-28`
- Тип изменения: согласованное переименование публичного seam и предметного языка без изменения поведения

## 1. Решение

Процессная сущность называется **объект монтажа**. Слово **заказ** не является термином FMonitor: оно не используется ни для объекта, ни для его реквизитов. Если оно встречается в первичном или legacy-источнике, это только внешняя терминология источника, а не язык продукта.

Инженер строительного контроля входит в **состав распоряжения** вместе с одним или несколькими монтажниками. Формулировки «состав или инженер», «изменение состава/инженера» и аналогичные запрещены; используется «состав».

## 2. Канонические технические имена

| Было | Станет | Смысл |
|---|---|---|
| `prepareOrder(...)` | `prepareAssignmentOrder(...)` | подготовить распоряжение, а не объект/заказ |
| `orderId` | `installationObjectId` | идентификатор объекта монтажа |
| `getOrderProcess(...)` | `getInstallationObjectProcess(...)` | прочитать процесс объекта монтажа |
| `orderSnapshot` | `installationObjectSnapshot` | неизменяемый снимок данных объекта |
| `orderVersion` | `assignmentOrderVersion` | версия распоряжения |
| `orderDate` | `assignmentOrderDate` | дата распоряжения |
| `processState = needs_order` | `processState = needs_assignment_order` | монтажному делу требуется распоряжение |
| `processState = order_prepared` | `processState = assignment_order_prepared` | распоряжение подготовлено |
| задача `prepare_order` | задача `prepare_assignment_order` | подготовить распоряжение |
| `ORDER_NOT_FOUND` | `INSTALLATION_OBJECT_NOT_FOUND` | объект не найден/недоступен |
| `ORDER_REQUIRED_DATA_MISSING` | `INSTALLATION_OBJECT_REQUIRED_DATA_MISSING` | отсутствуют обязательные данные объекта |
| `order_id` в предметных событиях | `installation_object_id` | ссылка события на объект монтажа |

Имена, действительно относящиеся к распоряжению, сохраняются: `AssignmentOrder`, `assignmentOrders`, `assignment_order_prepared`, `ASSIGNMENT_ORDER_ALREADY_PREPARED`, `registrationNumber`.

Legacy-имена колонок и внешних контрактов не переписываются: `fm_maintable`, `zavnumber`, `regnumber` и иные имена источника остаются трассируемыми. В пользовательском и предметном тексте `zavnumber` подписывается «Заводской номер лифта».

## 3. Публичный seam после миграции

```text
InstallationProcess.prepareAssignmentOrder(
    installationObjectId,
    installerTabIds[],
    controlEngineerUserId,
    actorId
)

InstallationProcess.getInstallationObjectProcess(installationObjectId)
InstallationProcess.getSecurityAudit(installationObjectId, actorId)
```

`getSecurityAudit` сохраняет имя операции, потому что оно не называет объект заказом; переименовывается только параметр.

Старые методы `prepareOrder` и `getOrderProcess` удаляются без compatibility alias: приложение ещё не подключено к production callers, а сохранение двух языков закрепило бы двусмысленность.

## 4. Наблюдаемое поведение

Все ранее утверждённые сценарии `ORDER-PREPARE-001`—`ORDER-PREPARE-004` сохраняют результаты, порядок проверок, аудит и состояние, кроме нормативно перечисленных имён:

- вызов выполняется через новый публичный seam;
- `orderId` в читаемой проекции становится `installationObjectId`;
- `orderSnapshot` становится `installationObjectSnapshot`;
- `orderVersion`/`orderDate` становятся `assignmentOrderVersion`/`assignmentOrderDate`;
- violations обязательных данных используют `INSTALLATION_OBJECT_REQUIRED_DATA_MISSING`;
- русские сообщения говорят об объекте монтажа, а не о заказе.

Контрольные суммы артефактов примера остаются прежними: тестовые байты не являются пользовательским шаблоном и не пересобираются в этом срезе.

## 5. Исполняемый пример

Пример `ORDER-PREPARE-002` вызывается новым методом:

```text
prepareAssignmentOrder(
  installationObjectId = 4512,
  installerTabIds = [1042],
  controlEngineerUserId = 73,
  actorId = 18
)
```

Результат:

```text
accepted = true
assignmentOrderVersion = 1
status = prepared
assignmentOrderDate = 2026-08-27
organizationType = individual
```

`getInstallationObjectProcess(4512)` возвращает `installationObjectId = 4512`, `installationObjectSnapshot`, `assignmentOrderVersion` и `assignmentOrderDate`; остальные утверждённые факты примера неизменны.

Отдельный пример обязательных данных вызывает тот же seam и получает `INSTALLATION_OBJECT_REQUIRED_DATA_MISSING` с прежними полями `address`/`entrance`/`objectRegistrationNumber`/датами и сообщениями, в которых «заказ» заменён на «объект монтажа».

## 6. Область репозитория

Миграция охватывает:

- `CONTEXT.md`, `PRODUCT.md`, нормативные и discovery-документы;
- все `specs/`, review-ссылки и handoff;
- `InstallationProcess`, in-memory environment и все тесты;
- throwaway demo: пользовательские подписи и локальные имена, если они называют объект заказом;
- HTML-прототипы и UX-copy.

Review-записи завершённых Gate не переписывают исторические вердикты и captured output задним числом. В них добавляется короткая пометка о терминологической миграции либо они читаются вместе с этой нормативной спецификацией. Имена файлов `ORDER-PREPARE-*` сохраняются как стабильные исторические идентификаторы спецификаций; `ORDER` в них означает распоряжение (`AssignmentOrder`), не объект.

## 7. Проверка завершения

После миграции поиск по процессному «заказу» должен находить только:

- дословные legacy-названия/исторические цитаты, явно помеченные как внешняя терминология;
- стабильные идентификаторы `ORDER-PREPARE-*` и имена `AssignmentOrder` в значении распоряжения;
- исторические review evidence, которое нельзя изменять задним числом.

Все существующие поведенческие тесты проходят через новый seam. Старые публичные методы отсутствуют.

## 8. Не входит

- изменение идентификаторов файлов `ORDER-PREPARE-*`;
- переименование таблиц/колонок внешней legacy-системы;
- изменение бизнес-поведения подготовки распоряжения;
- production DB adapter, HTTP и UI wiring.

## 9. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: утверждено сообщением «ок» после уточнения, что слово «заказ» не является термином FMonitor.

Gate 1 пройден. Следующий обязательный шаг — RED на новом публичном seam.
