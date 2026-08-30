# InstallationProcess — техническая привязка публичного шва

- Статус: `APPROVED для ORDER-PREPARE-001`
- Дата: `2026-08-27`

## Модуль и seam

`InstallationProcess` — глубокий PHP-модуль, через интерфейс которого UI, интеграции, cron и тесты выполняют процессные команды и читают их результат.

Первый исполняемый seam:

```php
$process->prepareAssignmentOrder(
    int $installationObjectId,
    array $installerTabIds,
    ?int $controlEngineerUserId,
    int $actorId,
): CommandResult;
```

Следующий утверждённый публичный seam:

```php
$process->confirmOrderRegistration(
    int $installationObjectId,
    int $assignmentOrderVersion,
    string $registrationNumber,
    string $source,
    int $actorId,
): CommandResult;
```

`REGISTRATION-CONFIRM-001` фиксирует первый in-memory success tracer для `source = manual`: точная текущая версия переходит `prepared → registered`, а этап монтажного дела, задачи, состав и документы не изменяются.

`PERSISTENCE-REGISTRATION-001` сохраняет тот же transition production MariaDB adapter-ом: существующая строка версии обновляется атомарно вместе с событием и технической revision дела, затем полная проекция читается новым соединением без внешних источников.

Следующий утверждённый публичный seam:

```php
$process->openInstallation(
    int $installationObjectId,
    string $actualStartDate,
    int $actorId,
): CommandResult;
```

`OPEN-INSTALLATION-001` фиксирует первый in-memory success tracer: registered status актуальной версии, допустимая actual-start date и повторная проверка current Workforce создают отдельный opening fact, состояние `working` и доступность checklist без пользовательского status и без пока не утверждённой задачи инспекции.

`PERSISTENCE-OPEN-001` сохраняет opening fact production MariaDB adapter-ом и восстанавливает полные root `actualStartDate`, `openedAt`, `openedByUserId`, gates, registered history и событие новым соединением без внешних reads.

`DOCUMENT-RENDER-HTML-001` зафиксировал первый HTML `DocumentRenderer`; текущая production composition использует `ProductionPdfAssignmentOrderRenderer`, который создаёт один combined PDF (`order`) со страницами распоряжения и приложения. HTML metadata сохраняется как legacy-compatible формат чтения.

`PROCESS-COMMAND-AUTHORIZATION-001` закрепляет отдельные production capabilities `assignment_order.prepare`, `assignment_order.confirm_registration` и `installation.open`. Composite `MariaDbProcessUserDirectory` проверяет active user/role и exact capability каждого command seam без fallback к другим capabilities или `users_rights2roles`.

`PRODUCTION-COMPOSITION-001` добавляет единый composition root `ProductionInstallationProcessFactory`: одно `mysqli`, явные `processTablePrefix`/`legacyTablePrefix` и optional test `Clock` дают готовый `InstallationProcess` со всеми production adapters. Production default — `SystemClock`; delegates наружу не выдаются.

`ARTIFACT-STORE-001` расширяет composition обязательным `artifactStorageRoot`, оборачивает текущий PDF renderer в content-addressed storing adapter и добавляет авторизованный `AssignmentOrderArtifactService` для integrity-checked download по process metadata/hash. Filename никогда не является filesystem path.

Наблюдаемый `CommandResult` для отказа:

```php
[
    'accepted' => false,
    'violations' => [
        ['code' => '...', 'message' => '...', 'field' => '...'],
    ],
]
```

Внешний интерфейс модуля не раскрывает репозитории, таблицы, renderer или последовательность внутренних проверок.

## Внутренний seam окружения

Конструктор модуля принимает один адаптер окружения. Он объединяет доступ к состоянию объекта монтажа, авторизации, внешним каталогам, времени, документам и транзакции, но не становится частью процессного интерфейса для UI.

Для тестов используется in-memory адаптер; для приложения будет создан DB/legacy-адаптер. Тесты вызывают только `InstallationProcess.prepareAssignmentOrder(...)` и читаемые команды самого модуля, не методы адаптера.

Точный состав внутреннего адаптера растёт только вместе с утверждёнными срезами. `ORDER-PREPARE-001` требует ответа на проверку полномочия и сохранения аудита отклонения; `REGISTRATION-CONFIRM-001` добавляет проверку полномочия подтверждения, clock и атомарное сохранение регистрации точной версии; `OPEN-INSTALLATION-001` добавляет open authorization, current Workforce lookup и атомарное сохранение opening fact.

## Размещение

- production namespace: `FMonitor2\InstallationProcess`;
- production class: `FMonitor2\InstallationProcess\InstallationProcess`;
- production path: `app/InstallationProcess/InstallationProcess.php`;
- тесты: `tests/InstallationProcess/`;
- тестовые адаптеры: `tests/Support/`.

Пространство имён и путь являются технической привязкой уже утверждённого предметного seam, а не новым бизнес-поведением.
