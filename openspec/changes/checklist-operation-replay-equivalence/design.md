## Context

См. `proposal.md` — Why и delta spec. Native Yii2 owner сохраняет для каждой checklist operation case, client ID, device, type, section/item, actor, revisions и canonical JSON payload. Для всех типов кроме `item_completed` обычная и exception duplicate branches сейчас читают только accepted revision по ID. `item_completed` уже делегирован canonical `InspectionEvidence` owner и не входит в production correction.

## Goals / Non-Goals

**Goals:**

- Одна canonical equivalence decision внутри существующего checklist mutation owner.
- Одинаковое решение в early duplicate и integrity-exception recovery.
- Сравнение только семантически используемых и уже сохраняемых полей.
- Сохранение authorization/read ordering и zero-write conflict.

**Non-Goals:**

- Новый replay/idempotency framework, command bus, event store или второй owner.
- Изменение схемы, HTTP/client/offline/UI или native `item_completed` owner.
- Рефакторинг `InspectionEvidence` либо остальных checklist semantics.

## Decisions

1. **Owner и public seam остаются существующими.** `MariaDbYiiChecklistMutation` остаётся mutation/persistence owner для пяти legacy-native types; HTTP endpoints продолжают вызывать `ChecklistSync::accept(...)`. Альтернатива нового общего idempotency abstraction отклонена как redesign вне #131.

2. **Duplicate lookup возвращает сохранённый контекст, затем один comparator классифицирует replay/conflict.** Lookup читает case, type, actor, device, section/item, accepted revision и payload JSON. Comparator строит ожидаемый normalized fingerprint из входной команды и сопоставляет typed values. Он вызывается и до transaction, и после integrity exception. Альтернатива raw JSON equality отклонена из-за незначимого key ordering и type-specific normalization.

3. **Fingerprint использует только применимые данные.** Installer IDs нормализуются в unique sorted strings/integers consistent с persisted canonical selection; reasons trim-ятся; photo fingerprint включает declared content identity and metadata; section completion не получает выдуманный payload. Object сопоставляется через существующий case mapping. Actor и device обязательны; `baseRevision` и `deviceTime` не сравниваются, поскольку accepted retry сохраняет первоначальную audit запись и клиент может повторить delivery metadata без нового намерения.

4. **Authorization precedes replay disclosure.** Существующий `commandAccess`/role/read guard остаётся раньше duplicate lookup. Это сохраняет #130 и не возвращает foreign revision/projection. Для authorized actor collision comparator возвращает обычный `conflict`; HTTP mapping остаётся существующим.

5. **Photo retry не пишет файл до equivalence decision.** Early replay/conflict завершается до transaction и `storePhoto`. В race loser transaction rollback происходит до повторного lookup; comparator не вызывает storage mutation. Existing content-addressed deduplication для другого operation ID не меняется.

6. **Race тест использует disposable MariaDB и public HTTP seam.** Test синхронизирует два отдельных соединения/HTTP workers на одной unique operation ID, затем независимо проверяет status и counts. Если текущая fixture не позволяет детерминированно удержать insert boundary, разрешён bounded test seam вокруг database scheduling, но production public path и реальная unique collision обязательны.

7. **Boundaries.** `rapid-pilot` не читается и не меняется; adapter отсутствует. Architecture baseline не меняется. Единственный production file — existing owner trait; test inventory меняется только при обязательном требовании verification planner.

## Risks / Trade-offs

- [Comparator расходится между normal и race paths] → оба пути вызывают один метод с одной входной командой и одним persisted row.
- [JSON decoding принимает malformed historical payload] → fail closed как conflict, не как duplicate и не как exception с утечкой.
- [Installer ordering создаёт false conflict] → сравнивать normalized unique sorted identities, не array order.
- [Read authorization регрессирует] → сохранить guard ordering и запустить #130 focused regression.
- [Race test недетерминирован] → использовать explicit transaction/barrier в disposable DB и отдельно утверждать persisted counts.
- [Scope растёт до generic subsystem] → остановить delivery с `NEEDS_OWNER`; не создавать abstraction или schema change.

## Migration Plan

Schema/data migration отсутствует. Delivery: Gate 1 contract → planner → public-seam RED → independent Gate 3 if selected → executor minimal owner change → focused regressions → independent final review → exact-source CI and PR. Rollback — revert production comparator commit; persisted facts не преобразуются. Merge/deployment не входят.
