# YII2-JOBS-CONSOLE-001 — фоновые процессы через Yii2 console

Status: `ACCEPTED_FOR_GATE_2`
Actor: production process supervisor и оператор readiness
Public seam: Yii2 console `jobs/worker`, `jobs/scheduler`, `jobs/health` и production compose entrypoints

## Простыми словами

Worker и scheduler продолжают выполнять те же надёжные фоновые задания, но запускаются через общий Yii2 console runtime, а не через файл во временном `rapid-pilot`. Этот срез не меняет очередь, расписание, кадровые правила, схему БД и не переключает рабочий стенд.

## A1. Команды и закрытый transport

Общий non-interactive Yii2 console launcher принимает ровно `jobs/worker`, `jobs/scheduler`, `jobs/health`. Успех и отказ печатают ровно один JSON object и newline в stdout; секреты и stack traces отсутствуют. Invalid route/options/config возвращают действующий exit `64` и `{"ok":false,"error":"CONFIGURATION_INVALID"}`; unexpected runtime failure — exit `70` и `JOBS_UNAVAILABLE`.

## A2. Runtime configuration

Каждая команда требует абсолютный `FMONITOR_SESSION_STATE_ROOT`, ровно один readable ready pilot manifest и canonical nonempty process prefix. Prefix применяется до подключения Jobs storage. Worker дополнительно читает `FMONITOR_BITRIX_CONFIG` через существующий `WorkerConfiguration`, передаёт origin/user/departments и staged token file прикладному handler и удаляет staged file при любом завершении. Scheduler и health не читают Bitrix secret.

Значение token, secret path/content, DB credentials, manifest path/content, prefix, exception и stack не попадают в stdout/stderr. Неверная конфигурация не создаёт и не изменяет job, heartbeat, scheduler или workforce facts.

## A3. Делегирование и устойчивость

После transport validation команды делегируют существующим `JobsRuntimeCommand`, `JobWorkerProcess` и `JobsSchedulerProcess`; алгоритмы claim/lease/retry/deduplication/outbox и workforce delivery не дублируются в Yii controller. SIGTERM, stop grace period, lease-loss и bounded shutdown сохраняют действующие contracts. Перезапуск worker/scheduler сохраняет все durable facts и не создаёт дубликат `schedule_key`.

## A4. Health

`jobs/health` проверяет те же worker/scheduler heartbeat и queue counters, что текущий Jobs owner. Healthy state возвращает exit 0 и прежний closed JSON; stale/missing heartbeat или storage failure возвращает exit 70 и прежний sanitized unhealthy JSON. Compose healthchecks вызывают только Yii2 console entrypoint.

## A5. Runtime closure и deployment boundary

Production compose entrypoints для `workforce-sync` и `workforce-scheduler`, а также их healthchecks вызывают общий Yii2 console launcher. Yii startup composition не включает и не читает PHP-файлы из `rapid-pilot`; declarative compose и все process-spawn call sites, достижимые из Jobs composition, не вызывают PHP/shell entrypoints из `rapid-pilot`. Старый файл остаётся историческим oracle до общего cutover, но новый runtime его не вызывает. Проверяемая граница намеренно не заявляет наблюдение произвольных child processes операционной системы вне repository-owned command construction.

Schema, existing rows, volumes и service identities не меняются. Рабочий stand не переключается. Rollback этого среза возвращает только entrypoint composition и не требует data rollback.

## A6. Verification

Gate 2 проверяет real Yii console subprocess: exact success/failure JSON and exits, unknown route/options, relative root, missing/unreadable/not-ready/ambiguous manifest, invalid prefix, worker secret staging cleanup, scheduler/health no-secret read, no facts on rejection и фактический loaded-file closure. Existing focused Jobs tests, явно связанные в verification input, независимо фиксируют healthy/stale health JSON, runtime CLI success, lease loss, retry, outbox deduplication и bounded SIGTERM. Isolated production compose test проверяет delivery, heartbeat health, restart, durable rows и schedule deduplication с новыми entrypoints. Обязательны architecture check, независимые Gates 3/5 и один full exact-source CI. Срез не закрывает imports/migrations, web runtime, общий #76, upgrade/rollback rehearsal или deployment.
