# PERSISTENCE-REGISTRATION-001 — долговечно сохранить ручную регистрацию точной версии

> **SUPERSEDED FOR TARGET PILOT — 2026-09-02.** Ниже сохранён legacy
> persistence contract manual registration. Historical rows остаются read-only;
> replacement начинается с `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`.

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-28`
- Актор: сотрудник ФКР, уполномоченный подтверждать регистрацию распоряжения
- Публичный командный шов: `InstallationProcess.confirmOrderRegistration(installationObjectId, assignmentOrderVersion, registrationNumber, source, actorId)`
- Публичный шов наблюдения: результаты подготовки/регистрации и `InstallationProcess.getInstallationObjectProcess(installationObjectId)` через новый экземпляр и новое MariaDB-соединение

## 1. Цель и граница

Подключить уже утверждённый успешный `REGISTRATION-CONFIRM-001` к production MariaDB persistence. Adapter должен обновить существующую точную подготовленную версию, добавить одно событие и после `COMMIT` восстановить полную зарегистрированную проекцию без in-memory состояния и внешних источников.

Это durability tracer одного успешного ручного подтверждения. Новых бизнес-правил, production authorization/integration adapters и failure semantics срез не вводит.

## 2. Предусловия и production assembly

- production schema migrations v1 `MIGRATION-PROCESS-001`, v2 `WORKFORCE-CATALOG-001` и v3 `PROCESS-USER-DIRECTORY-001` применены с одним уникальным допустимым test-prefix;
- в `fm2_installation_cases` существует только дело legacy-объекта `4512` в `needs_assignment_order`, revision `1`;
- `MariaDbInstallationProcessEnvironment` является единственным process persistence adapter;
- authorization, снимки объекта/монтажника/инженера, clock и renderer предоставлены детерминированным составным внешним delegate утверждённого `PERSISTENCE-PREPARE-001`;
- actor `18` разрешён и для подготовки, и для ручного подтверждения регистрации;
- `fm2_workforce_catalog` и `fm2_process_user_capabilities` содержат независимые sentinel rows; они не используются детерминированным delegate и нужны только для публично допустимой проверки отсутствия внешних writes;
- reload delegate запрещает любой внешний вызов исключением, поэтому историческая проекция может прийти только из `fm2_*`.

Production LegacyInstallationObject, Workforce, ProcessUserDirectory и renderer не требуются для этого узкого persistence tracer: они уже имеют отдельные срезы и не должны скрывать дефект записи регистрации.

## 3. Подготовка предусловия через публичный seam

Fixture не вставляет распоряжение SQL. Исходная версия создаётся production persistence через:

```php
$prepareResult = $process->prepareAssignmentOrder(4512, [1042], 73, 18);
```

Точный результат наследуется из `PERSISTENCE-PREPARE-001`:

```text
accepted = true
assignmentOrderVersion = 1
status = prepared
assignmentOrderDate = 2026-08-27
organizationType = individual
```

После подготовки сохранены версия `1`, полный состав/снимки/артефакты, одно событие `assignment_order_prepared`, `processState = assignment_order_prepared`; registration fields равны `null`.

Перед второй командой детерминированный clock переключается с момента подготовки `2026-08-26T21:30:00+00:00` на момент регистрации `2026-08-28T12:15:30+03:00`. Никакие другие внешние факты не меняются.

## 4. Действие регистрации и результат

```php
$registrationResult = $process->confirmOrderRegistration(
    4512,
    1,
    ' 12-Р ',
    'manual',
    18,
);
```

Точный результат совпадает с `REGISTRATION-CONFIRM-001`:

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

## 5. Атомарное логическое изменение MariaDB

Production environment под row lock дела и при ожидаемой revision:

1. находит ровно существующую `fm2_assignment_orders` по делу и `version_no = 1`;
2. подтверждает, что это текущая версия со `status = prepared`;
3. выполняет `UPDATE` этой строки: `status = registered`, нормализованный `registration_number = 12-Р`, `registered_at`, `registration_actor_type = user`, `registration_actor_id = 18`, `registration_source = manual`, `external_registration_id = NULL`;
4. вставляет ровно одно `fm2_process_events` типа `assignment_order_registered` с утверждёнными occurredAt/actor/payload;
5. оставляет `fm2_installation_cases.process_state = assignment_order_prepared`, меняет только технические `updated_at` и revision как часть того же сохранения;
6. подтверждает всё одним `COMMIT`.

В наблюдаемой предметной истории остаётся одна и та же версия `1`; версия `2`, дубли монтажника/артефактов и второе событие подготовки не появляются.

Физическая реализация обязана изменять существующую строку распоряжения через `UPDATE` и не удалять/пересоздавать её либо строки состава/артефактов. Это implementation invariant, следующий из append-only истории, foreign keys и неизменяемости подготовленных фактов. Он проверяется diff/code review на Gate 5, а не объявляется непосредственно наблюдаемым через публичный Gate 2 seam: внутренний DB row id в публичную проекцию намеренно не входит.

## 6. Наблюдение новым соединением

После успешного результата исходные module, environment, external delegate и connection уничтожаются. Новый `MariaDbInstallationProcessEnvironment` получает новое MariaDB-соединение и delegate, запрещающий все внешние методы. Новый модуль вызывает:

```php
$projection = $reloadedProcess->getInstallationObjectProcess(4512);
```

Проекция строго равна утверждённой проекции `REGISTRATION-CONFIRM-001`:

- `installationObjectId = 4512`, `processState = assignment_order_prepared`;
- ровно одна версия `1`, теперь `registered`;
- exact registration metadata из раздела 4;
- неизменные `assignmentOrderDate = 2026-08-27`, `organizationType = individual`, installation-object snapshot, installer `1042`, engineer `73`;
- те же два artifacts с filenames, media types, byte sizes и SHA-256 `PERSISTENCE-PREPARE-001`;
- те же два assignment links, без дубликатов;
- `openTasks = []`, `installationOpened = false`, `checklistAvailable = false`;
- ровно два события в порядке `assignment_order_prepared`, `assignment_order_registered`.

Событие регистрации имеет точную форму:

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

Reload не вызывает authorization, clock, renderer, LegacyInstallationObject, Workforce или UserDirectory. Любая попытка восстановить historical metadata из внешнего delegate делает тест красным.

## 7. Неизменность внешних и исторических фактов

До и после регистрации детерминированные внешние fixtures не получают write-вызовов. `fm_maintable`, `fm2_workforce_catalog`, `users`, `users_roles` и `fm2_process_user_capabilities` не обновляются.

В публичной process-проекции прежние значения состава/артефактов и payload события подготовки строго неизменны. Источником истины номера становится зарегистрированная точная версия распоряжения; legacy-поле номера не создаётся. Физическая побайтовая идентичность внутренних process rows не проверяется SQL assertions в Gate 2.

## 8. Авторизация, аудит, повтор и ошибки

Авторизация подтверждения предоставлена детерминированным delegate и проверяется до изменения версии. Production mapping capability не входит в persistence tracer.

Срез выполняет ровно одну успешную регистрацию. Сквозной default безопасного повтора и конкурентности наследуется: дубли и перезапись сохранённого результата запрещены. Но точные результаты повторного/одновременного вызова, confirmed rollback, unknown `COMMIT` и reconciliation требуют отдельных MariaDB tests/specs и этим tracer не заявляются.

При любом неподтверждённом атомарном сохранении частичная registration projection не является допустимым успехом. Технические ошибки MariaDB, SQL и connection details не возвращаются как бизнес-данные; их типизированная обработка остаётся отдельным срезом.

## 9. Публичный seam и независимость теста

Интеграционный тест:

1. применяет production migrations; SQL fixture создаёт пустое дело revision `1` и независимые sentinel rows внешних текущих каталогов, но не создаёт распоряжение;
2. через публичную `prepareAssignmentOrder` создаёт production persisted предусловие;
3. через публичную `confirmOrderRegistration` выполняет один registration transition;
4. уничтожает исходные объекты и соединение;
5. через публичную `getInstallationObjectProcess` новым соединением сравнивает полную литеральную проекцию;
6. не использует SQL assertions к process-таблицам: прямой SQL там ограничен schema/initial-case fixture и безопасной очисткой уникального prefix;
7. допускает SQL equality только для поставленных в test-scope внешних текущих таблиц `fm2_workforce_catalog` и `fm2_process_user_capabilities`, чтобы доказать отсутствие внешних writes; installation case/orders/installers/artifacts/events наблюдаются исключительно через публичную полную проекцию.

Expected registration fields/event заданы `REGISTRATION-CONFIRM-001`; неизменные fields/artifacts заданы `PERSISTENCE-PREPARE-001`. Они не вычисляются из production adapter, DB rows или command output. Строгая публичная equality чувствительна к missing logical transition/event, duplicate domain facts, partial hydration и внешнему rehydration. Она намеренно не различает физический `UPDATE` и delete/reinsert с тем же скрытым row identity; последнее запрещено implementation invariant раздела 5 и проверяется Gate 5.

## 10. Не входит в срез

- production authorization для регистрации;
- production external source delegates и renderer;
- transport/UI;
- `source = one_c_do`, integration principal и `externalRegistrationId`;
- пустой/невалидный номер, version/status/authorization rejections;
- уникальность и исправление номера;
- безопасный повтор, concurrency и stale revision outcome на MariaDB;
- rollback, unknown `COMMIT`, operation ID и reconciliation;
- создание задачи открытия;
- `openInstallation` и checklist gate;
- legacy-проекция.

## 11. Решения и доказательства

- `specs/REGISTRATION-CONFIRM-001.md`: точный доменный result/projection/event и отсутствие отдельного этапа/задачи.
- `specs/PERSISTENCE-PREPARE-001.md`: production adapter, durable prepared facts и reload без внешних reads.
- `specs/MIGRATION-PROCESS-001.md`, `WORKFORCE-CATALOG-001.md`, `PROCESS-USER-DIRECTORY-001.md`: production schema v1–v3 и изолированный test-prefix.
- `docs/fmonitor-2-pilot-data-model.md`: registration fields обновляются на той же версии атомарно; подготовленный документ не пересобирается.

## 12. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать работу и выбрал real MariaDB durability tracer утверждённого manual registration следующим единичным SSD + TDD-срезом; версия 0.2 повторно утверждена после разделения публично наблюдаемого Gate 2 контракта и физического append-only инварианта Gate 5.

Gate 2 разрешён для версии `0.2`.
