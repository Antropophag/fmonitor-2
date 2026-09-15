## Context

Legacy oracle: `Integration.php` читает direct child folders и `disk.folder.getExternalLink`; `Tables.php` связывает имя папки с `fm_maintable.zavnumber`; `showcell.php` отображает ссылку. Legacy остаётся только read-only evidence, не runtime dependency.

## Goals / Non-Goals

**Goals:** exact `zavnumber` mapping, bounded read-only delivery, безопасная полная замена текущих ссылок, отображение в существующей карточке.

**Non-Goals:** audit/replay framework, append-only snapshots, generalized integrations, scheduler redesign, recovery redesign, object identity, `rapid-pilot`, checklist/construction-control, OTIZ/calculation.

## Decisions

### 1. Bounded adapter

Adapter вызывает только `disk.folder.getchildren` и `disk.folder.getExternalLink` для настроенного HTTPS Bitrix origin/root. Он проверяет configuration, pagination, JSON/schema, лимиты и URL origin. Любая ошибка возвращает safe failure без partial result.

### 2. Простая current projection

Один application owner принимает complete validated list и в одной transaction полностью заменяет строки owned projection. Ошибка delivery не пишет данные и сохраняет предыдущую projection. Complete empty list очищает projection. Runs, snapshots, replay IDs и content hashes не создаются.

### 3. Exact order number

Nullable `zavnumber` добавляется в managed mirror и existing import. Read owner выбирает ссылки binary-exact по этому полю. `regnumber`, object id и иные identity не используются как fallback.

### 4. Карточка и console

Секция добавляется в уже авторизованную `objects.read` object card и различает missing order, empty links и temporarily unavailable. Console command только собирает delivery и вызывает application owner; SQL ему не принадлежит.

### 5. Hourly schedule через existing Jobs

Существующий native Jobs scheduler регистрирует один idempotent slot на каждый московский час и ставит bounded sync command в существующую очередь. Existing worker вызывает тот же console/application composition; новая задача не создаёт собственные lease/retry/outbox primitives и не обращается к `rapid-pilot`. Пропущенные часы не вызывают параллельный backlog: scheduler ставит только актуальный due slot, а overlapping execution предотвращается существующей job identity.

## Risks / Trade-offs

- Partial Bitrix response мог бы удалить рабочие ссылки → публикация только после complete validation.
- Необычные дефисные имена неоднозначны → refresh отклоняется, прежние ссылки сохраняются.
- External URL может быть неверным → только HTTPS URL настроенного origin; дальнейшей авторизацией владеет Битрикс.

## Migration Plan

1. Forward migration добавляет nullable binary-exact `zavnumber` и одну owned links table.
2. Existing legacy import переносит `zavnumber` без изменения object identity.
3. Оператор настраивает Bitrix и запускает console refresh.
4. Включить существующие `jobs-worker`/`jobs-scheduler`; после этого refresh ставится раз в час по `Europe/Moscow`.
5. Rollback кода прекращает refresh/read и hourly registration; таблица и nullable column остаются без destructive rollback.
