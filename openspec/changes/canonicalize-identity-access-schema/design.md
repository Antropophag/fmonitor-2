## Context

См. `proposal.md` — Why. Сейчас `IdentityBootstrap::rebuild()` одновременно владеет destructive reset, table creation и seed, а `UserAccessView` лениво создаёт status-event table на request path. Landed canonical runner заканчивается workforce v5, поэтому этот slice занимает literal v6; вставка иного predecessor требует fresh reconciliation и нового Gate 1. Девять таблиц содержат security-sensitive и append-only audit данные, поэтому непроверенный repair или rebuild populated namespace неприемлемы. Owner разрешил только restartable recovery отсутствующих members после full-family preflight, когда каждый existing member exact-compatible.

Owning module — production migration layer в `app/InstallationProcess`, вызываемый только canonical seam `bin/fmonitor2-migrate.php`. Persistence owner после изменения — canonical migration contract. `rapid-pilot` остаётся adapter/oracle: auth/access consumers читают и изменяют факты через существующие seams, а explicit bootstrap может seed/rebuild disposable среду, но не определяет production schema ownership.

## Goals / Non-Goals

**Goals:**

- До любой mutation классифицировать каждый member и всю target namespace как clean, complete-compatible, exact-compatible partial либо conflict.
- Для clean создать всю family; complete-compatible принять без mutation; exact-compatible partial дополнить только missing members; при любом incompatible existing member выполнить zero DDL/DML.
- Зарегистрировать literal migration v6 после landed workforce v5 и вернуть runner-совместимый deterministic result.
- Сохранить runtime auth/RBAC characterization и append-only audit строки при удалении request/runtime DDL.
- Уменьшить production DDL architecture debt; `make architecture-check` должен видеть удаление ownership из runtime, а не перенос baseline.

**Non-Goals:**

- Утверждать local RBAC authority, legacy fallback, permission catalogue или authorization/audit policy: это `NEEDS_GRILL (GRILL-002)`.
- Выводить exact fingerprints только из текущих CREATE strings без Gate 1 review.
- Выполнять seed, reset, data backfill, collation conversion, schema repair или redesign constraints.

## Decisions

1. **Одна migration владеет всей family и делает preflight до первого DDL.** Она классифицирует все девять prefixed names по Gate 1 literal fingerprints. Clean namespace создаётся полностью; complete-compatible — no-op; exact-compatible partial восстанавливается созданием только missing members в dependency order. Если хотя бы один existing member incompatible, вся family возвращает conflict с zero mutation. Owner выбрал эту restartable policy, потому что MariaDB DDL commits per statement и прерванный clean run иначе потребовал бы ручного repair даже при exact-compatible остатке.

2. **Fingerprint охватывает columns и relationships, но утверждается Gate 1.** Проверка должна включать имена/порядок/типы/nullability/default/extra, primary/unique/secondary indexes, foreign keys и delete rules, engine, charset/collation и релевантные enum definitions для каждой таблицы. OpenSpec задаёт области сравнения, а executable schema spec фиксирует literal expected values после отдельного evidence capture. Альтернатива `CREATE TABLE IF NOT EXISTS` не обнаруживает drift.

3. **Populated compatible family принимается read-only.** Preflight не обновляет rows, AUTO_INCREMENT, timestamps, passwords, tokens или audit history; migration result лишь сообщает compatibility/version state. Альтернатива rebuild-and-reseed разрушила бы identity и append-only evidence.

4. **Version — literal v6 после landed workforce v5.** Одно значение `6`
   используется в migration result, runner map, conflicts и final output.
   Если новый predecessor должен войти между v5 и identity до implementation,
   artifacts/spec проходят fresh reconciliation и новый Gate 1 review; молчаливое
   динамическое перенумерование запрещено.

5. **Runtime consumers становятся schema consumers.** Request/login/invitation/access paths не вызывают ensure/create/alter/drop. При missing/incompatible schema они используют существующий fail-closed error boundary. Destructive `rebuild` остаётся явно вызываемым bootstrap operation и должен использовать canonical schema contract либо собственный disposable reset orchestration, не вызываться migration runner. Альтернатива оставить status-event lazy create сохраняет production DDL и допускает mutation до authorization outcome.

6. **Behavior characterization отделяется от behavior approval.** Focused tests доказывают, что populated compatible migration и удаление DDL не изменили текущие login/invitation/role/block outcomes. Они не становятся нормативным одобрением authority semantics. Любая необходимость менять queries, role meanings или result authorization останавливает behavior часть до GRILL-002/Gate 1. Это позволяет перенести ownership, не замораживая спорную security модель.

7. **Prefix — часть public migration input.** Один валидированный prefix передаётся во все table/fingerprint operations; metadata queries фильтруют exact prefixed table names. Prefix decoys другого namespace входят в verifier и не влияют на target result.

## Risks / Trade-offs

- **[Текущие CREATE strings могут расходиться с реально populated pilot schema]** → Gate 1 отдельно снимает и утверждает exact fingerprints; RED включает complete-compatible и каждую конфликтную category до production edit.
- **[Partial namespace может быть неизвестного происхождения]** → recovery разрешена только после exact fingerprint каждого existing member и relationship preflight всей достижимой family; любое расхождение даёт deterministic zero-mutation conflict. Missing members создаются в FK-safe dependency order, existing schema/data не изменяются.
- **[Удаление lazy status-event creation вскроет неверный deployment order]** → clean runner и built-image/fresh lifecycle verification выполняются до HTTP checks; runtime остаётся fail-closed.
- **[Characterization может выглядеть как одобрение local RBAC]** → specs/tasks маркируют GRILL-002, review проверяет отсутствие behavior assertions и code changes вне schema ownership.
- **[Большой nine-table fingerprint сложнее сопровождать]** → хранить literal expectations рядом с одним migration owner и проверять family-level fixtures; не дублировать DDL в runtime/verifiers.
- **[Новый predecessor может потребовать место до identity]** → остановить Gate 2/implementation, согласованно пересчитать literal versions и повторить Gate 1 review/owner approval.

## Migration Plan

1. Gate 1: утвердить executable schema specification с evidence-derived fingerprints всех девяти таблиц, family states и deterministic errors; отдельно записать, что GRILL-002 behavior не утверждён.
2. Продемонстрировать focused RED через public migration seam для clean, repeat, populated, partial, fingerprint/family conflict, prefix и no-runtime-DDL сценариев; получить independent Gate 3 review.
3. Реализовать один strict migration owner как literal v6 после landed v5 и обновить runner final version до 6.
4. Перевести explicit bootstrap на использование canonical schema readiness и удалить table creation из request/runtime owners, сохранив intentional destructive seed/rebuild отдельным вызовом.
5. Выполнить focused DB/behavior characterization, `make architecture-check`, canonical/fresh lifecycle и `make verify`; получить independent Gate 5 review.
6. Rollback к предыдущему application image допустим только если он совместим с неизменённой family. Canonical migration не удаляет таблицы/данные; rollback схемы не автоматизируется, а повторное включение runtime DDL не является допустимой rollback strategy.
