## Context

Существующий harness уже имеет необходимые durable primitives: `prepare` materialize'ит immutable package и `verification-plan.json`, а external `state/active-binding-<worktree>.json` связывает worktree, input, base, source, plan и package. `_refresh_active_binding` заново строит plan, но live admission observation сейчас жёстко задаёт `reviews: []`; hook event `review_return` фиксирует лишь `CHANGES_REQUESTED` telemetry и не является authoritative verdict record. Markdown в `reviews/` включается в role package как материал, но не должен парситься как policy/evidence.

Владелец persistence — существующий delivery harness и его external evidence home. Product modules и `rapid-pilot` не участвуют; architecture-check impact ограничен delivery tooling/tests.

### Bounded data-flow gap-check

| Datum | PRODUCER | DURABLE LOCATION | FRESHNESS / BINDING | CONSUMER |
|---|---|---|---|---|
| Exact verification plan | existing verification planner через `prepare` / refresh | immutable package `verification-plan.json` и active plan path в external `active-binding` | canonical plan bytes/digest; input, base, task/worktree и prepared source | role packages, canonical live context |
| Selected obligations | тот же planner | plan commands/acceptances и active-binding projection | exact plan identity + task/source | role package и live context |
| Candidate/source binding | source identity + `prepare` | external active-binding и package.json | repository/worktree, base, exact source/candidate | `state`, role preparation, admission observation |
| Policy binding | existing policy digest + plan identity | canonical live context/review record в existing external harness state | exact policy digest и applicable plan digest | review freshness и existing admission consumer |
| Gate 3/5 verdict | independent reviewer через минимальный harness result command/contract | structured review result рядом с existing external task state/package; не Git candidate | task/change + role + gate + exact source/candidate + plan/policy | canonical live context |
| Review status projection | harness context builder | вычисляется из durable plan/binding/results; live state snapshot допустим как cache | CURRENT/MISSING/STALE, fail-closed | одинаково `state`, `wait`, `prepare-merge`; затем existing observation/admission |

Вывод gap-check: canonical structured APPROVED record сейчас отсутствует. Existing package/state архитектура пригодна для минимального расширения; новый evidence subsystem не требуется, поэтому `STOP NEEDS_OWNER` не срабатывает.

## Goals / Non-Goals

**Goals:**

- Один pure builder canonical context поверх existing active-binding, exact plan и external structured review results.
- Durable recovery fresh process и explicit fail-closed freshness.
- Тот же object для трёх live commands и существующего admission observation.

**Non-Goals:**

- Любое изменение `admission.evaluate()` или определения APPROVED/merge-ready.
- Второй planner/review system/evidence store, Markdown parsing, Quality Graph publisher, T07a/T07b/#153B/C или product code.

## Decisions

1. **Расширить existing external active task state, не вводить новый store.** Structured review results сохраняются в task/worktree-scoped external harness location, на которую ссылается active-binding/package contract. Альтернатива — repository review Markdown — отклонена из-за отсутствия machine binding и candidate-hash self-reference.
2. **Review result — explicit structured writer input.** Reviewer передаёт verdict и identity через существующий harness CLI/package flow; harness добавляет exact task/source/plan/policy bindings из active package и валидирует required role/Gate. Произвольный hook transcript и prose не являются authority. Альтернатива — извлекать verdict из agent message — отклонена как parsing prose.
3. **Canonical context строится один раз общей функцией.** `state`, `wait`, `prepare-merge` и native observation получают одну projection. Commands могут добавлять свои GitHub/CI поля, но plan/reviews object не дублируется.
4. **Freshness вычисляется, а не перезаписывает историю.** Старый record остаётся append-only/durable, projection маркирует его STALE при несовпадении task/gate/source/plan/policy. Missing остаётся MISSING. Foreign records игнорируются для current approval, но не удаляются.
5. **Review requirements следуют plan.** Gate keys нормализуются к planner vocabulary (`gate3`, `final`) и только затем отображаются в формат existing admission observation (`3`, `5`) там, где это уже требуется consumer contract. FAST никогда не получает synthetic gate3.

## Risks / Trade-offs

- [Одновременная запись results] → atomic replace/append-only uniquely named records и deterministic selection только exact-bound result.
- [Plan refresh незаметно меняет applicable policy] → bind plan digest и policy digest; несовпадение делает review stale.
- [Legacy admission evaluator ожидает оба Gate] → slice не меняет semantics; context честно представляет FAST и тестирует передачу, а любое semantic несоответствие остаётся T07a boundary, не маскируется synthetic approval.
- [Несколько historical results] → current выбирается только по exact composite binding; конфликтующие exact records fail closed, не выбираются по времени молча.

## Migration Plan

1. Root добавляет executable contract tests A–J и получает RED.
2. Отдельный executor минимально расширяет existing harness package/state contract и общий context builder.
3. Выполняются bounded focused checks, independent Gate 3/5 по planner-selected route и один exact-source CI run.
4. Candidate публикуется отдельным PR-ready без merge; blocked T07a остаётся неизменённым.
5. Rollback удаляет projection/writer, не затрагивая исторические external records или admission semantics.
