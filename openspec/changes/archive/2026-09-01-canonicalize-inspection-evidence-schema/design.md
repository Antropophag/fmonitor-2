## Context

См. `proposal.md`. `ChecklistSync::ensureSchema()` сейчас request-reachable и владеет четырьмя таблицами, включая два исторических `ALTER` upgrade. Таблицы потребляют process cases, registered-order crew, workforce snapshots и checklist-template binding; downstream их читают completion и premium projections.

## Goals / Non-Goals

**Goals:**

- один persistence owner в `app/InstallationProcess` и одна ordered canonical runner version;
- exact clean/final fingerprints и два явно разрешённых legacy upgrade fingerprints;
- family-wide preflight, zero mutation on conflict и runtime consumers без DDL;
- сохранить checklist evidence и текущее observable behavior.

**Non-Goals:**

- foreign keys/check constraints или storage redesign;
- retraction/correction model, photo revoke reason/history, dimensions/caption;
- изменение authorization или перенос checklist commands в этом slice.

## Decisions

1. **Одна migration владеет четырьмя связанными таблицами.** Она preflight-ит family целиком до create/alter. Альтернатива — по migration на таблицу — допускает half-upgrade и усложняет rollback/conflict reporting.
2. **Разрешены только три формы:** absent, exact final и два точно охарактеризованных additive predecessors. Произвольный `IF NOT EXISTS` repair отклонён, потому что скрывает drift.
3. **Upgrade выполняется после полного preflight.** Operations получает три nullable template columns; installers получает `assignment_source` с literal `pilot_backfill_current_order` для старых rows. Это переносит существующий runtime contract, не изобретая новые facts.
4. **Migration имеет literal v8 после exact landed catalogue v1–v7.** Workforce v5, identity/access v6 и checklist-template v7 уже landed; runner регистрирует evidence v8 ровно один раз непосредственно после v7. Любое последующее изменение predecessor catalogue возвращает artifact в Gate 1 без renumber reviewed migrations.
5. **Collation validation наследует approved v6/v7 UCA-alias normalization.** Exact reported database default проверяется через metadata и safe trial application к `utf8mb4` до target DDL; это сохраняет production compatibility без принятия unknown alias.
6. **Runtime DDL удаляется, behavior остаётся за существующим HTTP adapter.** Persistence owner — canonical migration; `ChecklistSync` временно остаётся rapid-pilot-facing consumer до calibration seam migration. Application modules не зависят от HTTP, rapid-pilot или MariaDB adapter.
7. **Architecture ratchet должен уменьшиться.** Соответствующие DDL findings удаляются из current collection; baseline меняется только через отдельно проверенное debt-removal действие, не для маскировки новых violations.

## Risks / Trade-offs

- [Неизвестная реальная schema отличается от двух predecessors] → fail closed с exact conflict и сохранить данные; отдельная migration только после evidence.
- [Bootstrap вызывает checklist до runner] → deployment ordering test и явная schema precondition вместо self-healing.
- [Nullable template identity остаётся у legacy rows] → это уже наблюдаемая совместимость; semantic backfill не входит в ownership slice.
- [Новые constraints могли бы усилить integrity] → отложить: они могут отвергнуть pilot data и смешивают redesign с ownership transfer.
- [Downstream completion/premium зависят от columns] → прогнать current-crew, template binding, offline/prefetch, completion и native OTIZ characterization.

## Migration Plan

1. Утвердить executable schema spec и frozen exact fingerprints/predecessors.
2. Получить RED runner/schema test и independent test review.
3. Реализовать family migration v8, зарегистрировать непосредственно после landed v7.
4. Удалить `ensureSchema` DDL/calls, оставить fail-closed precondition.
5. Прогнать focused DB, checklist/downstream characterization, architecture и full verification; затем independent code review.
6. Deployment: canonical migration до bootstrap/request traffic. Rollback приложения не удаляет таблицы или columns; forward-fix новой migration вместо destructive rollback.
