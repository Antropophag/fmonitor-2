# YII2-CLEAN-STAND-CUTOVER-001 — clean stand provisioning и acceptance

## Простыми словами

Issue #76 закрывается не переносом старого stand, а доказанным запуском нового пустого Yii2 stand. Внешний acceptance harness должен связать точный source/image с явно разрешённым disposable target, создать fresh schema штатными командами, запустить настоящий web/worker/scheduler и пройти реальные пользовательские и jobs flows. Он не восстанавливает старые данные, не переключает production и не удаляет legacy stand.

## Нормативный контракт

- Actor: владелец либо явно уполномоченный deployment operator.
- Public acceptance seam: `php tests/Support/yii2_clean_stand_acceptance.php preflight|run --manifest=<absolute-path> --authorization=<absolute-path> --evidence-root=<absolute-path>`.
- Source oracle: issue #76 owner decision 2026-09-14, `PRODUCT.md`, `CONTEXT.md` и landed Yii2 executable contracts.
- Target: только отдельный exact attest-нутый clean/disposable Docker Compose project; production/neighbor overlap запрещён.
- Credentials: только private absolute file references; secret contents запрещены в arguments, stdout/stderr, canonical reports и evidence.

### A1. Exact admission до effects

`preflight` MUST canonicalize и проверить exact source commit, immutable `image@sha256:<64 hex>`, Compose absolute path и SHA-256, operation UUID, expiry, authorization digest, disposable marker, exact будущие project/database/container/network/volume names, ожидаемое отсутствие этих resources и отсутствие production/neighbor overlap. До первого effect container/network/volume IDs ещё не существуют и не входят в authorization.

После создания isolated project и до создания database/bootstrap effects harness MUST получить actual IDs, labels, image digest, network attachments и volume mounts каждого созданного resource. Post-create attestation MUST доказать их принадлежность exact authorized namespace/image/topology и отсутствие overlap. Любой unexpected pre-existing resource либо post-create drift останавливает дальнейшие effects; созданное частичное состояние остаётся явно recorded и не принимается.

Каждый malformed/missing/conflicting binding MUST вернуть stable non-zero result с отдельной machine-readable причиной до первого effect. Это включает malformed/non-canonical documents, version/intent/effect mismatch, expired/stale digest, authorization/operation replay conflict, mutable image, Compose path/symlink/content drift, unsafe credential reference и unexpected resource. Project/database name сами по себе ничего не авторизуют. Same exact preflight MUST быть deterministic и не менять target/evidence, кроме явно заданного нового immutable preflight record.

### A2. Разрешённые clean-provision effects

После отдельной exact authorization `run` MAY выполнить только перечисленные package effects для exact disposable target:

1. создать новый isolated Compose project/network/named volumes и fresh MariaDB database;
2. создать DML runtime principal из private credential references;
3. выполнить canonical runtime prepare;
4. выполнить `php bin/yii schema-migrate/run --interactive=0` canonical migration principal;
5. выполнить существующий initial-admin provisioning seam и создать только перечисленных required synthetic users/configuration;
6. запустить `php`, `web`, `jobs-worker`, `jobs-scheduler` того же exact image.

Harness MUST NOT импортировать legacy database/files/sessions/jobs/outbox/artifacts, читать restore bundle, вызывать restore/reconcile/rollback либо воздействовать на иной project/database/container/network/volume. Повтор prepare/migrate/provision MUST доказать idempotency по exact schema ledger и user/role facts.

Любой partial, interrupted или ambiguous step MUST блокировать общий success; известные step facts сохраняются append-only во внешнем evidence.

### A3. Runtime readiness

После startup acceptance MUST наблюдать реальные long-running containers/processes и потребовать одновременно:

- healthy `php`, `web`, `jobs-worker`, `jobs-scheduler`;
- canonical `GET /health/live` success;
- canonical `GET /health/ready` success;
- `php bin/yii jobs/health --interactive=0` success;
- свежие distinct worker/scheduler heartbeat facts через configured canonical table prefix;
- отсутствие blocking dead jobs, expired leases и overdue-ready recovery counters.

Synthetic JSON/readiness fixtures, ручная вставка heartbeat либо ослабление healthchecks не удовлетворяют контракту.

### A4. Login/access и representative golden flows

На fresh schema harness MUST создать минимальные synthetic seed facts через acceptance-only setup adapter с отдельным private setup principal. Adapter MAY использовать internal DB/application boundaries только внутри isolated disposable topology и MUST отсутствовать в production image, Compose, runtime configuration, console commands и HTTP routes. После setup harness через неизменённый production Yii2 HTTP entrypoint MUST доказать:

- login и role/access enforcement;
- FKR: открыть object queue/card и выполнить один разрешённый preparation/opening action;
- construction-control: открыть назначенную queue/card и записать один разрешённый inspection/progress fact;
- checklist: выполнить один разрешённый checklist item transition;
- OTIZ: открыть OTIZ queue и выполнить representative calculate/inspect path, не подменяя существующие формулы.

Для каждого family MUST наблюдаться expected HTTP outcome и связанный canonical append-only fact. Отдельный user без permission MUST получить действующий отказ, а whole relevant fact projection MUST остаться неизменной.

Acceptance-only HTTP driver MUST поддерживать mutually exclusive form fields либо digest-bound raw JSON body, response-derived cookie/CSRF capture и bounded substitution в headers/body без записи captured credentials/tokens в argv, stdout, logs или persisted evidence.

### A5. Jobs/outbox normal operation

Harness MUST инициировать bounded synthetic workload через acceptance-only enqueue/workload adapter, доступный только внутри isolated disposable topology, и доказать настоящими production worker/outbox owners на canonical configured tables одну связанную цепочку:

`enqueue → worker claim/lease → terminal job history → outbox attempt/history`.

Adapter MAY создать только initial ready workload; terminal job/outbox/heartbeat facts MUST создавать реальные production worker/scheduler processes. После bounded stabilization jobs health MUST оставаться успешным, worker/scheduler heartbeat — свежими, recovery projection — без blocking backlog. Повторные read-only validations MUST не менять jobs/outbox facts. Direct insertion terminal/heartbeat facts вместо запуска процессов запрещена.

### A6. Normal production runtime closure

Для exact запущенного image acceptance MUST собрать три независимых вида evidence:

1. image filesystem inventory не содержит `rapid-pilot`;
2. Compose/container process commands являются canonical Yii2/nginx/PHP-FPM/jobs commands и не запускают `RuntimeRecovery`;
3. attributable loaded-file traces реальных health, login, golden HTTP, migration и jobs invocations не содержат `/rapid-pilot/` или `RuntimeRecovery`.

Jobs worker/scheduler/health MUST использовать canonical `FMONITOR_PROCESS_TABLE_PREFIX` из production configuration и MUST NOT обнаруживать либо требовать `pilot-demo/*/active.json`; missing/hostile/ambiguous legacy manifest не может менять normal jobs composition.

Attributable include traces MAY собираться acceptance-only mounted probe/Compose override, если probe отсутствует в production image/topology и не меняет production routes/commands. Lexical source checks MAY быть дополнительным guard, но не заменяют runtime observations. Historical/offline `bin/fmonitor2-runtime-recovery.php`, schema compatibility и backup/restore code MAY оставаться в repository и MUST NOT запускаться этим acceptance.

### A7. Result и evidence

Evidence root MUST находиться вне checkout, быть private и содержать canonical append-only step records, redacted command outcomes, target snapshot и final report, связанные с operation/source/image/authorization digests. Credential contents и reusable tokens MUST отсутствовать.

`CLEAN_STAND_ACCEPTED` разрешён только если A1–A6 доказаны одним exact candidate/target. UNKNOWN, missing evidence, interrupted process или failed assertion MUST оставить final result non-success. Acceptance success не разрешает и не выполняет production traffic switch либо удаление старого stand; это отдельная последующая owner authorization.

### A8. Real-disposable execution остаётся authorization-gated

Один и тот же public acceptance seam MUST иметь non-default real-boundary mode, который без внешнего exact owner authorization package возвращает `AUTHORIZATION_REQUIRED` без Docker/DB/filesystem effects. Canonical outer package MUST содержать ровно version, authorization decision digest, operation UUID, source/image/production-Compose/acceptance-override identities and digests, exact future target names, expected absence/non-overlap, private credential-file references, allowed effects, expiry и external evidence root. Recording/fault driver разрешён только при explicit test mode и MUST NOT публиковать real-disposable evidence либо удовлетворять task 4.2/Done definition. После authorization real mode MUST самостоятельно наблюдать Docker/MariaDB/HTTP/process/include boundaries и не принимать caller-provided high-level success booleans.

### A9. Acceptance-only topology физически отделена

Acceptance override, setup principal, seed/workload adapters и include probe MUST находиться только под test/deployment acceptance support, подключаться отдельным explicit Compose override и fail closed вне exact disposable operation. Production `deploy/runtime/compose.yaml`, production image recipes/configuration и public Yii2 web/console route inventory MUST оставаться byte-equivalent по normal paths и не содержать setup principal, synthetic seed, probe, enqueue endpoint либо acceptance credential reference.

## Explicit non-goals

- legacy data migration или сохранение старых sessions/jobs/outbox/artifacts;
- backup/restore rehearsal, rollback или UNKNOWN reconciliation;
- `RuntimeRecovery` retirement;
- изменение пользовательских workflows, formula/schema semantics либо harness policy;
- production cutover execution или удаление старого stand.
