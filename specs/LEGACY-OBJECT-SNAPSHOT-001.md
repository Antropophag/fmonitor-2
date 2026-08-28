# LEGACY-OBJECT-SNAPSHOT-001 — прочитать production-снимок объекта монтажа из `fm_maintable`

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР с полномочием `assignment_order.prepare`
- Публичный командный шов: `InstallationProcess.prepareAssignmentOrder(installationObjectId, installerTabIds[], controlEngineerUserId, actorId)`
- Публичный шов наблюдения: результат команды и `InstallationProcess.getInstallationObjectProcess(installationObjectId)` через новый экземпляр модуля

## 1. Цель

Подключить production `LegacyInstallationObject` delegate к завершённому успешному пути подготовки первой версии распоряжения. Delegate читает утверждённые реквизиты объекта монтажа непосредственно из существующей MariaDB-таблицы `fm_maintable`, нормализует их в внутренний `installationObjectSnapshot` и передаёт модулю без раскрытия legacy-схемы в публичном интерфейсе.

Срез заменяет только источник снимка объекта. Авторизация, кадровый каталог, каталог инженеров, clock и renderer остаются детерминированными делегатами; process persistence использует production-схему `MIGRATION-PROCESS-001`.

## 2. Предусловия

- production migration `MIGRATION-PROCESS-001` применена;
- для legacy `fm_maintable.id = 4512` существует пустое монтажное дело `needs_assignment_order`, `lock_version = 1`;
- актуального подготовленного или зарегистрированного распоряжения нет;
- сотрудник `18` имеет полномочие `assignment_order.prepare`;
- монтажник `1042`, инженер `73`, время и два renderer-артефакта совпадают с утверждённым примером `PERSISTENCE-PREPARE-001`;
- process persistence и legacy delegate используют MariaDB, но `fm_maintable` остаётся read-only источником интеграции.

## 3. Вход и точный mapping

Команда не получает legacy-поля:

```php
$result = $process->prepareAssignmentOrder(4512, [1042], 73, 18);
```

Production delegate выполняет параметризованный поиск одной строки по точному равенству:

```text
fm_maintable.id = installationObjectId
```

Он возвращает внутренний снимок по следующему нормативному mapping:

| Поле снимка | Источник и нормализация |
|---|---|
| `address` | `trim(fm_maintable.ordadr_address)` |
| `entrance` | `trim(fm_maintable.entrance)` |
| `objectRegistrationNumber` | `trim(fm_maintable.regnumber)` |
| `plannedStartDate` | календарная дата `YYYY-MM-DD` из `fm_maintable.workdatestart` |
| `plannedFinishDate` | календарная дата из `workdateendadjusted`, если значение не `NULL`, не пустое, не строковый/числовой ноль и не zero-date; иначе календарная дата из `plan_finish_date` |
| `ptoActDate` | `null`, если `ptoactdate` равно `NULL`, пустой строке, строковому/числовому нулю или zero-date; иначе календарная дата `YYYY-MM-DD` из `ptoactdate` |

Для этого контракта:

- trim удаляет только окружающий whitespace; внутреннее написание адреса, подъезда и регистрационного номера объекта не исправляется;
- календарная дата — первые календарные компоненты значения MariaDB, без преобразования часовой зоны;
- zero-date включает `0000-00-00` и значения даты-времени, начинающиеся с `0000-00-00`;
- строковый ноль — значение, которое после trim состоит только из одного или нескольких символов `0`;
- `workdatefinish` никогда не читается и не используется как план: это legacy-факт фактического завершения;
- fallback применяется только между `workdateendadjusted` и `plan_finish_date`; равенство этих дат не меняет результат.

SQL выбирает только необходимые семь колонок: `id`, `ordadr_address`, `entrance`, `regnumber`, `workdatestart`, `workdateendadjusted`, `plan_finish_date`, `ptoactdate`. `id` является условием идентичности; остальные семь значений образуют снимок.

## 4. Исполняемый пример A — скорректированная дата имеет приоритет

Строка `fm_maintable` задаётся независимыми литералами:

```text
id = 4512
ordadr_address = "  Москва, ул. Примерная, д. 10  "
entrance = " 2 "
regnumber = " 77-000123 "
workdatestart = "2026-10-05 14:30:00"
workdateendadjusted = "2026-12-18 09:15:00"
plan_finish_date = "2026-12-20"
workdatefinish = "2026-11-30 18:00:00"
ptoactdate = "0000-00-00 00:00:00"
```

После команды из раздела 3 успешный результат остаётся утверждённым результатом `PERSISTENCE-PREPARE-001`:

```text
accepted = true
assignmentOrderVersion = 1
status = prepared
assignmentOrderDate = 2026-08-27
organizationType = individual
```

Новый экземпляр модуля и новое MariaDB-соединение возвращают в версии `1` точный снимок:

```text
address = "Москва, ул. Примерная, д. 10"
entrance = "2"
objectRegistrationNumber = "77-000123"
plannedStartDate = "2026-10-05"
plannedFinishDate = "2026-12-18"
ptoActDate = null
```

Значение `workdatefinish = 2026-11-30` не попадает в результат. Остальная полная проекция, назначения, артефакты, событие успеха и закрытые work/checklist gates строго наследуются из `PERSISTENCE-PREPARE-001`.

## 5. Исполняемый пример B — fallback плановой даты и повторные нули

Пример B выполняется в независимом чистом setup с тем же объектом монтажа `4512`, исходным process-state и командой из раздела 3. Все не перечисленные legacy-поля, авторизация, монтажник, инженер, clock и renderer совпадают с примером A и утверждённым `PERSISTENCE-PREPARE-001`. Отличаются только:

```text
workdateendadjusted = "0000-00-00 00:00:00"
plan_finish_date = "2027-01-09 23:59:59"
workdatefinish = "2026-12-01 12:00:00"
ptoactdate = " 000000 "
```

Команда успешно подготавливает первую версию. Её точный результат:

```text
accepted = true
assignmentOrderVersion = 1
status = prepared
assignmentOrderDate = 2026-08-27
organizationType = individual
```

Новый экземпляр модуля возвращает сохранённый снимок:

```text
address = "Москва, ул. Примерная, д. 10"
entrance = "2"
objectRegistrationNumber = "77-000123"
plannedStartDate = "2026-10-05"
plannedFinishDate = "2027-01-09"
ptoActDate = null
```

Полная остальная проекция строго наследуется из `PERSISTENCE-PREPARE-001`: ровно одна версия и предварительные назначения монтажника `1042` и инженера `73`; ровно два renderer-артефакта `assignment-order-4512-v1.pdf` и `assignment-order-4512-v1-appendix.pdf` с утверждёнными размерами и SHA-256; ровно одно событие `assignment_order_prepared`; открытых задач нет, работы и чек-лист закрыты. Значение `workdatefinish = 2026-12-01` не попадает в результат.

Правило раздела 3 для ненулевого `ptoactdate` остаётся нормативным mapping delegate. Ненулевая дата Акта ПТО намеренно не является executable example этого среза: успешная первая подготовка при таком legacy-факте противоречила бы границе импорта/quality gate. Такое сочетание не проверяется прямым вызовом delegate и не получает здесь нового command-result.

## 6. Наблюдаемый результат и долговечность

Примеры A и B вызываются только через публичный `InstallationProcess.prepareAssignmentOrder(...)`. Тест не вызывает delegate напрямую и не передаёт снимок объекта вручную.

После успешного `COMMIT` исходные module, delegate и DB-соединение уничтожаются. Новый `InstallationProcess` с новым соединением читает снимок исключительно из `fm2_assignment_orders`; последующее изменение или удаление legacy-строки не меняет уже подготовленную версию.

`fm_maintable` после команды побайтно сохраняет исходные значения задействованных колонок. Process adapter не пишет в `fm_maintable.installator*` и не восстанавливает снимок из legacy после сохранения.

## 7. Отказы и приоритет

Этот срез не вводит новый публичный код отказа.

После чтения снимка уже утверждённые пустые обязательные значения проходят существующий путь `INSTALLATION_OBJECT_REQUIRED_DATA_MISSING` с полями в порядке `address`, `entrance`, `objectRegistrationNumber`, `plannedStartDate`, `plannedFinishDate`. В частности, пустой/zero-date `workdateendadjusted` при пустом/zero-date `plan_finish_date` даёт отсутствующий `plannedFinishDate`, а не подменяется `workdatefinish`.

Отсутствующая строка `fm_maintable`, ошибка соединения и нераспознаваемое непустое значение даты не преобразуются этим срезом в придуманный предметный отказ. Они fail closed как инфраструктурная ошибка до renderer и до сохранения версии; точный типизированный caller-result требует отдельной спецификации.

Наследуется утверждённый порядок: `FORBIDDEN`, отсутствие обязательного состава и существующая актуальная версия проверяются до legacy delegate. Поэтому эти отказы не читают и не раскрывают legacy-реквизиты.

## 8. Безопасный повтор, атомарность и аудит

Успешная команда наследует `ORDER-PREPARE-005`, `ORDER-PREPARE-006` и `PERSISTENCE-PREPARE-001`: повтор не создаёт вторую первую версию и не переписывает сохранённый снимок; конкурентная команда не создаёт дубль.

Delegate выполняет только чтение. Успешный audit остаётся одним событием `assignment_order_prepared` с актором `18`, временем `2026-08-26T21:30:00+00:00` и payload утверждённого примера. В audit не добавляются имя legacy-таблицы, SQL или исходные ненормализованные значения.

Авторизацию выполняет `InstallationProcess` до вызова delegate. Сам delegate не является публичной пользовательской командой и не расширяет полномочия DB-соединения на запись в legacy.

## 9. Публичный seam теста и независимость ожидаемых значений

Интеграционные сценарии A и B каждый используют независимый чистый setup. Каждый сценарий:

1. применяет production `MIGRATION-PROCESS-001` с уникальным допустимым test-prefix к шести process-таблицам;
2. создаёт только process-предусловие и минимальную реальную `fm_maintable` fixture со значениями соответствующего примера;
3. собирает production process persistence и production `LegacyInstallationObject` delegate; остальные внешние факты предоставляет детерминированный составной delegate;
4. вызывает публичную команду и наблюдает полную проекцию новым соединением;
5. проверяет неизменность fixture-строки и отсутствие повторных legacy-чтений при reload.

Ожидаемые `2026-10-05`, `2026-12-18`, `2027-01-09` и `null` заданы примерами A и B до реализации. Они не вычисляются тестом через production delegate, SQL `DATE()`, `trim()` или прочитанный command-result. Fixture A намеренно содержит конкурирующие `plan_finish_date = 2026-12-20` и `workdatefinish = 2026-11-30`; fixture B — zero-date скорректированного плана, допустимый fallback и отличающийся фактический `workdatefinish = 2026-12-01`. Неверные приоритет, обработка повторных нулей или использование фактического завершения делают публичный тест красным.

## 10. Не входит в срез

- production Workforce, UserDirectory, authorization, clock и DocumentRenderer delegates;
- типизированный публичный результат отсутствующей legacy-строки или инфраструктурной ошибки;
- импорт/создание монтажных дел и отбор пилотных объектов;
- решение legacy-конфликта с уже существующим Актом ПТО;
- запись совместимой legacy-проекции и запрет прямого редактирования `installator*`;
- использование `workdatestartadjusted`, `factworkstartdate` или `workdatefinish`;
- форматная валидация непустых адреса, подъезда и регистрационного номера объекта;
- часовая нормализация legacy timestamps;
- controller/HTTP/UI.

## 11. Решения и доказательства

- `docs/fmonitor-2-maintable-field-census.md`: `id` — primary key, адресные реквизиты являются canonical legacy input; `workdatefinish` — фактическое завершение, `plan_finish_date` — базовый план, `workdateendadjusted` — скорректированный план, `ptoactdate` — факт Акта ПТО.
- `docs/order-template-required-inputs.md`: документ требует адрес, подъезд, регистрационный номер объекта и две плановые даты.
- `specs/ORDER-PREPARE-002-E.md`: обязательные поля снимка и точный существующий отказ для пустых значений.
- `specs/PERSISTENCE-PREPARE-001.md`: утверждённые success-result, process persistence и reload без повторного обращения к внешним источникам.
- `specs/MIGRATION-PROCESS-001.md`: production-схема process persistence.
- `../fmonitor/application/controllers/Integration.php`: read-only evidence различает базовую, скорректированную и фактическую даты завершения и показывает legacy zero-date для `ptoactdate`.

## 12. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать проект по handoff, принимать лучшие решения и соблюдать обязательный SSD + TDD workflow; версия 0.2 повторно утверждена после устранения противоречия Gate 3 — оба executable example теперь достижимы исключительно через публичную команду.

Gate 2 разрешён для версии `0.2`.
