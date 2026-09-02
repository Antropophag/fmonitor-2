# FMonitor 2.0 — модель данных и процессный интерфейс пилота

## 1. Решение

Первый инкремент добавляет процессные таблицы рядом с legacy `fm_maintable`. Старые поля объекта монтажа остаются источником идентификационных данных, но перестают быть источником истины для распоряжений, состава и открытия чек-листа.

Все изменения проходят через один глубокий модуль **InstallationProcess**. Контроллер, cron, импорт или будущая интеграция не обновляют его таблицы напрямую.

## 2. Внешний интерфейс модуля

Интерфейс выражает намерения пользователя, а не CRUD:

```text
prepareAssignmentOrder(installationObjectId, installerTabIds[], controlEngineerUserId, actorId)
submitAssignmentOrderOriginal(command)
openInstallation(installationObjectId, actualStartDate, actorId)
prepareAssignmentChange(installationObjectId, installerTabIds[], controlEngineerUserId, effectiveDate, actorId)
getInstallationObjectProcess(installationObjectId)
getWorkQueue(actorId, filters)
```

`submitAssignmentOrderOriginal` — единый pilot application seam для initial upload и append-only correction подписанного PDF. Он сохраняет original evidence, подтверждённую дату документа, отдельное системное время загрузки, состав, hash и audit, но не применяет состав и не открывает работы. Ручного command регистрации/номера в pilot interface нет. Возможная будущая интеграция с 1С ДО находится вне пилота и не меняет этот факт.

Каждая команда:

1. блокирует процессную запись объекта монтажа;
2. загружает актуальные факты через внутренние адаптеры;
3. проверяет все инварианты;
4. одной транзакцией записывает новую версию и аудит;
5. обновляет legacy-проекцию при необходимости;
6. возвращает новое состояние и следующую задачу.

Ошибки являются предметными: `ORDER_ALREADY_OPEN`, `ORDER_HAS_PTO_ACT`, `INSTALLER_NOT_EMPLOYED`, `CONTROL_ENGINEER_REQUIRED`, `ACTUAL_START_BEFORE_ORDER_DATE`. UI переводит их в понятные пользователю причины и способы исправления.

## 3. Таблицы первого инкремента

Названия предварительные; важен контракт данных.

### `fm2_installation_cases`

Одна запись на legacy-объект монтажа.

| Поле | Назначение |
|---|---|
| `id` | внутренний идентификатор дела |
| `legacy_installation_object_id` | уникальная ссылка на `fm_maintable.id` |
| `process_state` | вычисляемое/кэшированное состояние процесса |
| `actual_start_date` | фактическая дата начала, указанная ФКР |
| `opened_at` | точное системное время открытия |
| `opened_by_user_id` | кто открыл работы |
| `created_at`, `updated_at` | технический аудит |
| `lock_version` | защита от одновременного изменения |

Инвариант: `actual_start_date` заполняется только командой открытия и не раньше даты актуального распоряжения.

### `fm2_assignment_orders`

Версии распоряжений по одному объекту монтажа.

| Поле | Назначение |
|---|---|
| `id` | идентификатор версии документа |
| `installation_case_id` | монтажное дело |
| `version_no` | последовательная версия внутри дела |
| `kind` | `initial` или `change` |
| `status` | pilot lifecycle выбранного состава/template; не содержит manual `registered` gate |
| `template_date` | предложенная дата, напечатанная в необязательном шаблоне; не является окончательной датой документа |
| `control_engineer_user_id` | выбранный инженер строительного контроля |
| `control_engineer_fio_snapshot`, `control_engineer_position_snapshot` | снимок данных инженера на момент формирования |
| `organization_form` | сохранённая производная `individual`/`brigade` для воспроизводимости документа |
| `previous_assignment_order_id` | предыдущая версия распоряжения при изменении |
| `object_address_snapshot`, `entrance_snapshot`, `object_registration_number_snapshot` | неизменяемые реквизиты объекта, использованные документом |
| `planned_start_date_snapshot`, `planned_finish_date_snapshot`, `pto_act_date_snapshot` | неизменяемый снимок дат объекта на момент формирования |
| `prepared_at`, `prepared_by_user_id` | аудит формирования |

Одна версия относится к одному монтажному делу. Legacy registration columns/facts могут временно сохраняться для read-only compatibility, но не являются pilot source of truth и не участвуют в новом opening contract.

### `fm2_order_installers`

Снимок монтажников конкретной версии распоряжения.

| Поле | Назначение |
|---|---|
| `assignment_order_id` | версия распоряжения |
| `installer_tab_id` | устойчивый табельный идентификатор |
| `fio_snapshot` | ФИО на момент формирования |
| `position_snapshot` | должность на момент формирования |
| `employment_status_snapshot`, `employed_from_snapshot`, `employed_to_snapshot` | кадровые факты, на которых основана проверка |
| `workforce_source_snapshot`, `workforce_source_updated_at_snapshot` | источник и свежесть кадрового снимка |
| `valid_from`, `valid_to` | интервал назначения |
| `change_action` | `assign`, `retain`, `release` для изменяющего документа |

Снимки нужны для воспроизводимости подписанного документа. Актуальный справочник не переписывает старое ФИО или должность в документе.

### `fm2_order_artifacts`

Метаданные сформированных файлов конкретной версии. Байты и файловая стратегия остаются за `DocumentRenderer`/хранилищем документов.

| Поле | Назначение |
|---|---|
| `assignment_order_id` | версия распоряжения |
| `artifact_type` | `order` или `appendix` |
| `filename`, `media_type`, `byte_size` | воспроизводимые метаданные файла |
| `sha256` | контроль неизменности содержимого |

Generated template artifact отличается от подписанного original evidence и никогда им не перезаписывается. Точная additive schema original lineage назначается change `replace-pilot-registration-with-original-upload`; literal migration version выбирается только на актуальном frontier.

### Original evidence lineage

Initial upload создаёт immutable original identity/revision 1. Correction ссылается на current leaf и exact expected revision и добавляет revision `n+1`; прежние bytes/metadata не обновляются. Каждая revision хранит assignment-order identity, immutable composition identity/hash, document date, upload time, actor, SHA-256, exact received byte size, private storage identity и correction target/reason. Stored request identity и accepted-operation fingerprint обеспечивают replay/response-loss recovery. Original evidence само по себе не меняет assignment intervals или opening state.

### `fm2_process_tasks`

Персональная очередь, а не набор вычисленных красных ячеек.

| Поле | Назначение |
|---|---|
| `id` | задача |
| `installation_case_id` | связанное дело |
| `task_type` | `prepare_assignment_order`, `open_order`, `prepare_change` |
| `assignee_user_id` / `assignee_role` | конкретный исполнитель или роль |
| `due_date` | срок |
| `status` | `open`, `completed`, `cancelled` |
| `completed_at`, `completed_by_user_id` | аудит завершения |
| `created_at` | аудит постановки |

Команда завершает старую задачу и создаёт следующую в той же транзакции.

### `fm2_process_events`

Человекочитаемый append-only аудит.

| Поле | Назначение |
|---|---|
| `installation_case_id` | дело |
| `event_type` | тип совершившегося факта |
| `occurred_at` | системное время |
| `actor_user_id` | инициатор |
| `payload_json` | минимальный снимок предметных значений |

Это аудит, а не попытка сделать полное event sourcing. Текущее состояние хранится в нормализованных таблицах.

### `fm2_workforce_catalog`

Production-owned текущая проекция интеграционного кадрового каталога `1С ЗУП → Битрикс → FMonitor`. Она добавляется additive migration v2 и не является историей распоряжений.

| Поле | Назначение |
|---|---|
| `installer_tab_id` | числовая pilot-идентичность монтажника, primary key |
| `fio`, `position` | актуальные кадровые реквизиты |
| `employment_status` | `employed` или `dismissed` |
| `employed_from`, `employed_to` | известный включительный период трудовых отношений |
| `workforce_source`, `workforce_source_updated_at` | provenance и свежесть текущего снимка |

Process-команды читают каталог, но не изменяют его. Будущая интеграция заменяет/upsert-ит текущие строки; уже сохранённые снимки `fm2_order_installers` от этого не меняются. Legacy `fm_installators` не является источником отсутствующих кадровых периодов и provenance.

### `fm2_process_user_capabilities`

Production-owned явное соответствие пользователя process capability, добавляемое additive migration v3.

| Поле | Назначение |
|---|---|
| `user_id` | ссылка по значению на legacy `users.id` без cross-schema FK |
| `capability` | включая `assignment_order.prepare`, `assignment_order.original.upload`, `assignment_order.original.correct`, `installation.open` и `construction_control_engineer`; вместе с `user_id` образует primary key |
| `position_snapshot` | обязательная настроенная должность инженера; nullable для capability подготовки |

Активность пользователя и его legacy-роли проверяется по `users.status = 1` и `users_roles.status = 1`. Каждая process-команда требует свою exact capability; prepare/upload/correct/open не наследуют права друг друга. `users_rights2roles` и отображаемое имя роли не являются источником process capabilities. Historical `assignment_order.confirm_registration` rows могут сохраняться read-only для совместимости, но новая pilot command их не требует. Расширение enum выполняется additive migration на актуальном frontier.

## 4. Читаемые проекции

### Очередь объектов ФКР

Проекция возвращает только нужное экрану:

- объект монтажа, адрес, регномер;
- плановую дату и количество дней до неё;
- процессное состояние;
- следующее действие и срок;
- подтверждённую дату и наличие актуального оригинала;
- выбранных монтажников и инженера в компактном виде;
- причины блокировки.

### Справочник монтажников

Соединяет текущий интеграционный каталог `fm2_workforce_catalog` с действующими интервалами `fm2_order_installers` и возвращает:

- кадровый статус и свежесть синхронизации;
- текущие и будущие назначения;
- объекты монтажа и даты;
- конфликт доступности;
- ссылку на документ-основание.

### Legacy-проекция

Пока старые отчёты зависят от `fm_maintable.installator..installator4`, адаптер может отражать туда текущий состав. Эта проекция:

- не является источником истины;
- недоступна для прямого редактирования штатным пользователем;
- не переписывает исполнителей исторических пунктов чек-листа;
- проверяется на расхождение с процессными таблицами.

## 5. Адаптеры на внутренних швах

### LegacyInstallationObject adapter

Читает адрес, подъезд, регномер, плановые даты, завершение и дату Акта ПТО (`ptoactdate`) из существующей схемы. Наличие даты означает наличие факта Акта ПТО и блокирует обычную подготовку, изменение назначения и открытие работ. Файл акта является необязательным вложением и не участвует в этой проверке.

### Workforce adapter

Читает актуальных монтажников из существующего результата цепочки `ЗУП → Битрикс → FMonitor`. В тестах заменяется in-memory каталогом.

### UserDirectory adapter

Composite ProcessUserDirectory читает активность и ФИО из legacy `users`/`users_roles`, а явные полномочия и настроенную должность — из `fm2_process_user_capabilities`. Один adapter обслуживает authorization подготовки и поиск инженера, не связываясь с `users_rights2roles`.

### DocumentRenderer adapter

Формирует документ и приложение по шаблону. Отдельный адаптер оправдан двумя реализациями: реальный renderer и детерминированный тестовый renderer.

### LegacyProjection adapter

Временно обновляет совместимые поля старой схемы после успешной процессной команды.

## 6. Транзакционные инварианты

1. Одновременно по объекту монтажа формируется только одна новая версия.
2. Необязательный шаблон имеет предложенную дату и состав, но не является подписанным оригиналом или gate открытия.
3. Принятый original имеет подтверждённую document date, immutable состав/hash/bytes metadata и отдельный upload time.
4. Correction только добавляет новую revision с причиной; исходные bytes/facts не обновляются.
5. Upload/correction не меняет composition intervals, case state, actual start или checklist availability.
6. Целевое открытие возможно только отдельной командой при наличии применимого original, минимум одного монтажника и одного инженера; exact переключение legacy gate принадлежит `open-installation-from-assignment-order-original`.
7. Монтажник должен существовать в актуальном кадровом каталоге на дату распоряжения.
8. Инженер должен быть активным пользователем допустимой роли.
9. После Акта ПТО обычные команды формирования и открытия запрещены.
10. Изменение состава создаёт новую версию; предыдущая остаётся неизменной.
11. Исторические пункты чек-листа никогда не меняют исполнителя из-за нового распоряжения.

## 7. Минимальный набор миграций

1. Создать пять процессных таблиц и индексы.
2. Создать `fm2_installation_cases` для объектов пилота без изменения `fm_maintable`.
3. Не переносить текущие четыре слота монтажников как доказанные назначения; считать их неподтверждёнными legacy-данными до первого сформированного распоряжения.
4. После формирования первого распоряжения процессная модель становится владельцем состава этого объекта монтажа.
5. Заблокировать прямую запись `installator*` для объектов, перешедших под управление FMonitor 2.0.
6. Оставить чтение старых отчётов через совместимую проекцию на период пилота.

## 8. Тестовая поверхность

Тесты вызывают тот же интерфейс InstallationProcess, что и контроллеры:

- нельзя сформировать без монтажника;
- нельзя сформировать без инженера;
- нельзя выбрать уволенного монтажника;
- дата шаблона предлагается системой, а окончательная дата берётся из original и хранится отдельно от upload time;
- нельзя открыть с датой раньше распоряжения;
- original можно принять после шаблона или напрямую, без номера и registration status;
- нельзя принять original без composition confirmation или с будущей document date;
- byte-identical semantic replay не создаёт дубль, correction сохраняет прежнюю revision;
- upload не открывает дело и не применяет состав;
- нельзя открыть второй раз;
- изменение состава не меняет предыдущую версию и исторический чек-лист;
- параллельные команды не создают две актуальные версии.
