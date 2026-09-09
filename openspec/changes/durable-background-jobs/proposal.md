## Why

Issue #34 требует общий backend-механизм для фоновой работы #11/#12/#13/#15:
сейчас hourly workforce shell сам определяет расписание, вызывает sync напрямую и
оставляет вечный ready-file, а durable queue/outbox/lease/operator recovery нет.

## What Changes

- Срез `DURABLE-BACKGROUND-JOBS-001`: application owners ставят versioned jobs через
  один `Jobs\Queue` seam; worker получает bounded lease и вызывает зарегистрированный
  application handler из того же production image под DML-only principal.
- Добавить MariaDB queue с payload type/version, `available_at`, attempt history,
  lease token/deadline, terminal outcome и идемпотентностью предметных эффектов.
- Добавить transactional outbox: domain application operation сохраняет intent в
  своей транзакции, transport вызывается dispatcher-ом только после commit.
- Перенести существующий hourly workforce sync на scheduler → job → текущий
  `MariaDbWorkforceSynchronization::run`; schedule `:07` Europe/Moscow, один job на
  час, после downtime ставится только текущий slot без backfill всех пропусков.
- Зафиксировать технические retries: максимум 5 attempts с delays
  `1m, 5m, 15m, 1h`; lease 5 минут с heartbeat и reclaim после expiry. Workforce
  job использует stable run/idempotency identity на всех attempts.
- Добавить operator CLI: список failed/dead/stale jobs, безопасный retry terminal
  job с новой operation identity и причина/attempt history, пригодные будущему #30.
- Health читает свежий worker heartbeat, scheduler slot и overdue-ready/expired
  lease counters из DB; старый ready-file удаляется из production contract.

Не входят: email/уведомительные product triggers и templates, новый пользовательский
UI, broker, real Bitrix/email calls, изменение workforce normalization/publication,
автоматический deploy и exactly-once обещание внешнего transport.

## Capabilities

### New Capabilities

- `operations/durable-background-jobs`: durable queue, worker leasing, retries,
  recovery, operator inspection/retry и динамический health.
- `operations/transactional-outbox`: атомарная запись notification intent и
  post-commit delivery с явной at-least-once неоднозначностью.
- `workforce/hourly-sync-schedule`: сохранение hourly workforce behavior через
  уникальный Europe/Moscow schedule slot и общий queue/worker.

### Modified Capabilities

Нет. `workforce/bitrix-delivery` остаётся readonly transport contract без изменения.

## Impact

Actor-ы: application owner, scheduler, DML-only worker и deployment operator.
Source oracle: issue #34, ADR0002, `bin/fmonitor2-sync-workforce.php`,
`MariaDbWorkforceSynchronization`, `rapid-pilot/workforce-worker.sh` и approved
Bitrix delivery contract. Публичные seams: enqueue в составе application unit of
work, worker claim/execute/complete, scheduler tick и operator CLI list/retry/health.
Новые владельцы — `app/Jobs` для queue/outbox lifecycle и `app/Workforce` для sync
handler; scheduler/CLI/transport не владеют domain facts.
