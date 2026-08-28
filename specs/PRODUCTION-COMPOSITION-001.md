# PRODUCTION-COMPOSITION-001 — собрать готовый production `InstallationProcess`

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-28`
- Актор: production application bootstrap; публичные process-команды выполняет сотрудник ФКР
- Production composition seam: `ProductionInstallationProcessFactory.create(connection, config, clock = null)`
- Публичные domain seams: `prepareAssignmentOrder(...)`, `confirmOrderRegistration(...)`, `openInstallation(...)`, `getInstallationObjectProcess(...)`

## 1. Цель

Дать application caller одну production assembly вместо ручного связывания persistence и внешних adapters. Factory принимает одно `mysqli` connection и явную конфигурацию table namespaces, собирает `MariaDbInstallationProcessEnvironment` со всеми утверждёнными production delegates и возвращает только готовый `InstallationProcess`.

Состав:

```text
MariaDbInstallationProcessEnvironment
├── MariaDbLegacyInstallationObject
├── MariaDbWorkforceCatalog
├── MariaDbProcessUserDirectory
├── ProductionHtmlAssignmentOrderRenderer
└── Clock (production default: SystemClock)
```

Factory не создаёт HTTP/controller/storage/download seams и не скрывает неприменённые migrations.

## 2. Production API и config

```php
$process = ProductionInstallationProcessFactory::create(
    mysqli $connection,
    ProductionInstallationProcessConfig $config,
    ?Clock $clock = null,
): InstallationProcess;
```

Production config имеет три обязательных string-поля; `ARTIFACT-STORE-001 v0.2` supersedes прежнюю возможность composition без storage root:

```text
processTablePrefix
legacyTablePrefix
artifactStorageRoot
```

Routing prefixes:

- `processTablePrefix` применяется ко всем production-owned таблицам `fm2_installation_cases`, `fm2_assignment_orders`, `fm2_order_installers`, `fm2_order_artifacts`, `fm2_process_tasks`, `fm2_process_events`, `fm2_workforce_catalog`, `fm2_process_user_capabilities`;
- `legacyTablePrefix` применяется только к `fm_maintable`, `users`, `users_roles`;
- `users_rights2roles` не маршрутизируется и не читается;
- production config обычно передаёт две пустые строки, потому что обе группы находятся в одной FMonitor DB;
- интеграционный тест передаёт два разных уникальных prefix в одной test DB, исключая collisions и доказывая правильный routing.

Prefix не является SQL schema/database name. Каждый prefix обязан быть длиной `0..32` ASCII chars и соответствовать `/^[A-Za-z0-9_]*$/`. Factory передаёт его adapters как identifier prefix; ни один adapter не принимает table name из process command. Exact persistent-root validation определяет `ARTIFACT-STORE-001 v0.2`; `create` без valid nonempty root fail closed и non-storing production renderer fallback отсутствует.

## 3. Clock contract

```php
interface Clock
{
    public function now(): string;
}
```

Если clock не передан, factory создаёт `SystemClock`. Каждый `SystemClock::now()` создаёт `new DateTimeImmutable('now')` и возвращает RFC3339 с точностью до секунд и явным offset:

```text
Y-m-d\TH:i:sP
```

SystemClock не меняет PHP/default timezone и не преобразует instant в business date. `InstallationProcess` самостоятельно выводит assignment-order/current business date в `Europe/Moscow`, как утверждено domain contract.

Тест может передать только объект `Clock`, а не callback/string. Его sequence clock возвращает последовательно exact instants prepare/confirm/open. Clock injection не заменяет ни один другой production adapter.

## 4. Exact method routing без расширения прав

Composite environment маршрутизирует:

| Environment intent | Единственный delegate |
|---|---|
| process load/atomic replace/public hydration/events | `MariaDbInstallationProcessEnvironment` с `processTablePrefix` |
| `getInstallationObjectSnapshot` | `MariaDbLegacyInstallationObject` с `legacyTablePrefix` |
| prepare Workforce lookup и `findCurrentInstallerSnapshot` | один `MariaDbWorkforceCatalog` с `processTablePrefix` |
| `actorCanPrepareAssignmentOrder` | `MariaDbProcessUserDirectory`: exact `assignment_order.prepare` |
| `actorCanConfirmOrderRegistration` | тот же directory: exact `assignment_order.confirm_registration` |
| `actorCanOpenInstallation` | тот же directory: exact `installation.open` |
| `findEngineerSnapshot` | тот же directory: exact `construction_control_engineer` |
| render | один `ProductionHtmlAssignmentOrderRenderer` |
| `now` | injected `Clock` либо `SystemClock` |

Fallback к in-memory facts, permissive `true`, legacy role name/right, prepare capability для других команд или synthetic engineer запрещён. Factory не выдаёт caller-у delegates для обхода `InstallationProcess`.

## 5. Constructor validation и fail closed

PHP type boundary принимает только `mysqli`, `ProductionInstallationProcessConfig` и `Clock|null`; значения иных типов отклоняются стандартным `TypeError` до тела factory и SQL. Внутри factory до первого SQL query валидируются:

- оба prefixes соответствуют разделу 2;
- config не содержит недоступных/uninitialized полей.

Invalid prefix/config state отклоняется `InvalidArgumentException` без SQL и без частично возвращённого process object. Connection credentials/prefix input не включаются в message.

Factory вызывает `mysqli::set_charset('utf8mb4')`. Если connection закрыт или charset setup не подтверждён, factory fail closed:

```text
RuntimeException
message = "Production installation process initialization failed."
```

Exception не содержит host/user/password, driver SQL, table names или underlying message. Factory не применяет migrations автоматически: missing/incompatible schema проявляется fail closed при production adapter operation; deployment выполняет v1–v4 заранее.

## 6. Real MariaDB fixture и production assembly

Test DB получает:

```text
processTablePrefix = pc001_<random>_
legacyTablePrefix = pc001_legacy_<random>_
```

Оба соответствуют длине/regex. Migrations v1–v4 применяются с `processTablePrefix`. Под `legacyTablePrefix` создаются минимальные реальные `fm_maintable`, `users`, `users_roles`; `users_rights2roles` отсутствует.

Fixtures строго наследуют:

- object `4512` example `LEGACY-OBJECT-SNAPSHOT-001 A`;
- workforce installer `1042` example `WORKFORCE-CATALOG-001`;
- actor `18` с тремя distinct command capabilities и engineer `73` example `PROCESS-COMMAND-AUTHORIZATION-001`;
- initial case `needs_assignment_order`, revision `1`;
- renderer input values `DOCUMENT-RENDER-HTML-001`.

Factory получает одно connection, оба prefixes и test `SequenceClock`:

```text
1. 2026-08-26T21:30:00+00:00
2. 2026-08-28T12:15:30+03:00
3. 2026-08-28T12:45:00+03:00
```

Factory возвращает один `InstallationProcess`; test не получает composite/delegate references.

## 7. Exact public chain

```php
$prepare = $process->prepareAssignmentOrder(4512, [1042], 73, 18);
$confirm = $process->confirmOrderRegistration(4512, 1, ' 12-Р ', 'manual', 18);
$open = $process->openInstallation(4512, '2026-08-28', 18);
```

Точные command results наследуются из approved prepare/registration/open slices. Важные renderer metadata prepare-result/projection:

```text
order:
  filename = assignment-order-v1.html
  mediaType = text/html
  size = 1093
  sha256 = 682749a063958eb102f5b184c4dfe6c21a009f77932b3b68b3b92e340adf4928
appendix:
  filename = assignment-order-v1-appendix.html
  mediaType = text/html
  size = 1262
  sha256 = da33d58efd35c6211d850446ee9f159526c9ba779fbdd9355b68ac35806ee3ac
```

Это доказывает, что composition использовал production HTML renderer, а не прежний deterministic/PDF-like fallback.

## 8. Новое соединение и полная проекция

После chain исходные process/factory result/connection уничтожаются. Новое MariaDB connection и тот же config передаются factory с Clock, который бросает при любом `now()`; public observation не должна читать clock или внешние sources.

```php
$projection = $reloadedProcess->getInstallationObjectProcess(4512);
```

Полная projection строго равна `PERSISTENCE-OPEN-001`, кроме утверждённых HTML artifact metadata раздела 7:

- root `working`, exact actual/opened fields и gates true;
- одна registered version `1`, номер `12-Р`, exact registration metadata;
- exact object/installer/engineer snapshots, assignments и HTML artifacts;
- `openTasks = []`;
- три exact events prepare/register/open в нормативном порядке.

Reload читает process tables. Legacy/Workforce/UserDirectory/renderer/clock не вызываются; test может после chain удалить external fixture rows до reload, не меняя projection.

## 9. External read-only и observability

Перед chain test фиксирует literals legacy object, workforce, users/roles/capabilities. После chain их SQL equality неизменна. Direct SQL assertions к case/order/installers/artifacts/tasks/events отсутствуют: все process facts наблюдаются public projection.

Test также доказывает namespace routing различными prefixes: размещает conflicting decoy rows с теми же IDs под противоположным prefix. Успешные exact snapshots/authorization показывают, что legacy adapters не прочитали process namespace, а process/workforce/capability adapters — legacy namespace.

## 10. Не входит в срез

- HTTP/controller/session bootstrap;
- migrations auto-run;
- connection pool/reconnect policy;
- artifact byte storage/download/regeneration;
- PDF/1С ДО integration;
- tasks/inspection SLA;
- rejected domain commands, concurrency/rollback/unknown commit;
- production logging/metrics/DI container framework.

## 11. Решения и доказательства

- `docs/installation-process-interface.md`: один глубокий public module seam и составное внутреннее окружение.
- `specs/LEGACY-OBJECT-SNAPSHOT-001.md`, `WORKFORCE-CATALOG-001.md`, `PROCESS-COMMAND-AUTHORIZATION-001.md`: production external adapters и prefix-isolated fixtures.
- `specs/DOCUMENT-RENDER-HTML-001.md` v0.2: production renderer exact metadata.
- `specs/PERSISTENCE-OPEN-001.md`: real MariaDB full public chain/reload.

## 12. Утверждение Gate 1

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Решение: `APPROVED`
- Комментарий: пользователь поручил самостоятельно продолжать работу и выбрал единый production composition root полного prepare/confirm/open пути; версия 0.2 повторно утверждена вместе с `ARTIFACT-STORE-001 v0.2`: storing renderer и secure `artifactStorageRoot` теперь обязательны, non-storing fallback удалён.

Gate 2 разрешён для версии `0.2`; существующий composition test обязан использовать secure root согласно `ARTIFACT-STORE-001 v0.2`.
