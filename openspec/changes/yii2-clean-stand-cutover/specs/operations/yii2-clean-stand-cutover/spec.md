## Purpose

Определяет проверяемую подготовку и acceptance нового чистого Yii2 stand candidate без переноса legacy-состояния и без выполнения production cutover.

## ADDED Requirements

### Requirement: Exact candidate и target допускаются явно
Clean-stand operation MUST принимать exact source commit, immutable image reference с digest, canonical Compose identity и explicit target manifest. Target MUST быть помечен clean/disposable, attest-нут по exact project, database, containers, network и volumes и не пересекаться с production или соседними resources. Implicit discovery по имени проекта запрещён.

#### Scenario: Exact disposable target принят
- **WHEN** оператор передаёт matching exact candidate, authorization и свежую target attestation
- **THEN** preflight публикует machine-readable перечень всех bindings и допускает только перечисленные clean-provision effects

#### Scenario: Candidate или target не совпадает
- **WHEN** source, image digest, Compose, project, database, container, network, volume, disposable marker либо authorization отличается или overlap нельзя исключить
- **THEN** operation завершается fail closed до первого state-changing effect и не затрагивает никакой target

### Requirement: Fresh provisioning использует canonical production seams
После отдельной exact owner authorization operation MUST создать только разрешённую fresh MariaDB database, подготовить canonical persistent paths, выполнить canonical migrations/bootstrap и idempotent initial-admin/required-user provisioning через существующие public operational seams. Legacy database, backup bundle, sessions, jobs/outbox и artifacts MUST NOT быть входом provisioning.

#### Scenario: Новый пустой stand подготовлен
- **WHEN** attest-нутый clean target не содержит application schema и оператор запускает authorized provisioning
- **THEN** database создаётся, canonical migration catalogue применяется до exact current version, runtime paths готовы, initial identities/configuration созданы и итоговые schema/user facts зафиксированы во внешнем evidence

#### Scenario: Provisioning повторён
- **WHEN** та же exact operation повторяет prepare, migrations и initial-user provisioning после доказанного успеха
- **THEN** schema version, migration ledger, users/roles и persistent paths остаются эквивалентны первому результату без дублирования фактов или секретов в evidence

#### Scenario: Provisioning не завершён
- **WHEN** любой обязательный prepare, migration или provisioning step завершается ошибкой либо ambiguous outcome
- **THEN** candidate не получает acceptance, последующие runtime/golden-flow assertions не могут опубликовать общий GREEN и фактическое частичное состояние сохраняется в evidence

### Requirement: Полный Yii2 runtime достигает readiness
Operation MUST запустить из одного immutable image services `php`, `web`, `jobs-worker` и `jobs-scheduler`. Acceptance MUST требовать healthy containers, успешные `/health/live`, `/health/ready`, canonical jobs health и свежие heartbeat facts worker/scheduler; ослабление health contract запрещено.

#### Scenario: Runtime готов
- **WHEN** fresh provisioning завершён и все четыре runtime services запущены
- **THEN** liveness и readiness отвечают canonical success, jobs health не содержит blocking reasons, а database содержит свежие distinct worker и scheduler heartbeats

#### Scenario: Runtime частично готов
- **WHEN** хотя бы один process exited/unhealthy, endpoint недоступен, readiness неуспешна, jobs health неуспешен или heartbeat отсутствует/устарел
- **THEN** общий acceptance завершается неуспешно с exact observed process/health evidence

### Requirement: Golden user flows работают на fresh state
Acceptance MUST через production Yii2 HTTP entrypoint создать только необходимые synthetic seed facts и выполнить representative authenticated flows FKR, construction-control, checklist и OTIZ, включая login/access authorization. Expected outcomes MUST происходить из действующих product/executable contracts, а не из legacy database snapshot.

#### Scenario: Representative fresh-state journey успешен
- **WHEN** initial users входят с назначенными ролями и выполняют утверждённые FKR, construction-control, checklist и OTIZ действия над synthetic fresh object
- **THEN** каждый HTTP result, redirect, authorization decision и resulting append-only domain fact соответствует своему действующему Yii2 contract

#### Scenario: Недостаточные полномочия
- **WHEN** initial либо synthetic user вызывает representative action без требуемой роли
- **THEN** Yii2 route отклоняет действие и соответствующие domain facts не появляются

### Requirement: Jobs и outbox выполняют normal цикл
Acceptance MUST инициировать безопасную synthetic workload через canonical application/job seam и доказать enqueue, worker claim, terminal job history, outbox attempt/history, recovery counters и worker/scheduler health. Проверка MUST использовать canonical configured table identities и не подменять процессы fixture JSON.

#### Scenario: Job и outbox обработаны
- **WHEN** scheduler/application публикует разрешённую synthetic workload и worker её обрабатывает
- **THEN** наблюдаются связанные enqueue, lease/claim, completion, outbox attempt/history facts, отсутствуют unexpected dead/expired jobs и jobs health остаётся успешным

#### Scenario: Normal recovery state подтверждён
- **WHEN** workload завершена и health читается после bounded stabilization period
- **THEN** recovery projection не содержит blocking backlog, heartbeats свежие, а повторное чтение не изменяет jobs/outbox facts

### Requirement: Normal production closure не зависит от legacy runtime
Acceptance MUST инспектировать реально запущенные production web, console, worker и scheduler paths. Runtime image MUST не содержать `rapid-pilot`; loaded-file/process/command evidence MUST доказывать отсутствие `rapid-pilot` и `RuntimeRecovery` в normal paths. Historical/offline recovery CLI и compatibility code MAY оставаться в repository, но MUST NOT быть запущены acceptance operation.

Jobs worker/scheduler/health MUST получать canonical table prefix непосредственно из production runtime configuration и MUST NOT зависеть от `pilot-demo/*/active.json` discovery.

#### Scenario: Production closure чистая
- **WHEN** acceptance выполняет health, login, golden HTTP, migrations и jobs operations в exact image
- **THEN** image inventory, process commands и attributable include traces не содержат `rapid-pilot` или `RuntimeRecovery` на normal path

#### Scenario: Legacy dependency обнаружена
- **WHEN** image, process command либо attributable runtime include normal path содержит legacy runtime/recovery dependency
- **THEN** acceptance завершается неуспешно даже при успешных HTTP и database assertions

### Requirement: Acceptance evidence не является cutover
Operation MUST публиковать внешний canonical acceptance report, связанный с exact candidate, target и отдельными step outcomes. GREEN разрешён только когда все обязательные assertions доказаны; UNKNOWN не является GREEN. Acceptance MUST NOT переключать production traffic, удалять старый stand, выполнять restore/reconciliation/rollback или публиковать recovery success.

#### Scenario: Candidate принят
- **WHEN** все provisioning, readiness, golden-flow, jobs и closure assertions успешны
- **THEN** публикуется exact `CLEAN_STAND_ACCEPTED` evidence, после которого production cutover остаётся отдельным owner-authorized действием

#### Scenario: Acceptance прервана
- **WHEN** выполнение прервано или outcome любого обязательного шага неизвестен
- **THEN** `CLEAN_STAND_ACCEPTED` отсутствует, known evidence сохраняется и никакой legacy recovery operation автоматически не запускается

### Requirement: Acceptance-only topology изолирована от production
Synthetic setup principal/adapters, bounded enqueue workload и include/process probe MUST подключаться только отдельным explicit Compose override к exact disposable acceptance target. Production Compose, image, runtime configuration, public web routes и console commands MUST не содержать эти seams или credential references. Recording/test output MUST NOT удовлетворять real `CLEAN_STAND_ACCEPTED`.

#### Scenario: Isolated adapters доступны acceptance operation
- **WHEN** exact authorized disposable acceptance запускается с отдельным override
- **THEN** private setup/enqueue/probe доступны только внутренним acceptance services, а normal production services остаются на неизменённых commands/routes/image

#### Scenario: Adapter вызван вне acceptance context
- **WHEN** setup, enqueue либо probe запускается без exact disposable acceptance bindings
- **THEN** adapter завершается fail closed без DB/Docker/HTTP effects и production runtime не получает новый reachable seam
