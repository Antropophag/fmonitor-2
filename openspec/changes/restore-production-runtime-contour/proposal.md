## Why

#36 требует доказать, что согласованный снимок MariaDB и закрытого state volume
восстанавливает пользователей, полномочия, append-only историю, PDF, фото и сессии
в новом изолированном production-runtime контуре и переживает обновление exact
image. Текущий runbook перечисляет ручные части backup, но не задаёт единого
публичного seam, проверяемого manifest или выполненного restore drill.

## What Changes

- Добавить операторскую CLI-команду создания согласованного backup bundle после
  остановки writers: DB dump, архив private state и versioned manifest с exact
  source/image, размерами и SHA-256 без секретов.
- Добавить отдельную CLI-команду восстановления только в пустой изолированный
  target: полный preflight bundle до mutation, импорт DB и state с владельцем
  runtime UID/GID, затем read-only readiness.
- Выполнить synthetic restore drill через production Compose: сверить точные
  строки истории и связи, PDF/фото, auth/session, обычный browser flow и обновление
  на другой exact image с отдельными migrations.
- Зафиксировать rollback: для совместимой additive schema возвращается прежний
  web/php image; при несовместимости выполняется restore-forward в новый пустой
  контур, произвольный downgrade DB не обещается.
- Срок хранения, RPO, RTO и допустимая потеря данных остаются **NEEDS_GRILL** и не
  получают выдуманных значений в этом техническом срезе.
- Worker/jobs/outbox recovery, включая lease, незавершённые задания и
  неопределённую внешнюю доставку, явно отложен до #34. Базовый drill не зависит от
  отсутствующего механизма и не заявляет его проверенным.

## Capabilities

### New Capabilities

- `operations/coordinated-runtime-restore`: согласованный backup, fail-closed
  restore в пустой контур, проверка целостности и exact-image update/rollback drill.

### Modified Capabilities

Нет.

## Impact

Публичные seams: новые deployment CLI под `bin/`, private bundle вне repository и
существующий `deploy/runtime/compose.yaml`. Затрагиваются operator tooling,
production runbook, Runtime tests и verification inventory. Рабочий стенд, его
volumes, production imports и внешние отправки не изменяются. Источник требований:
issue #36, ADR0002, production runtime/runbook #33/#27 и действующие контракты
append-only данных и закрытого artifact/session storage.
