## Context

См. `proposal.md`. Existing ownership: `tools/delivery/harness_context.py` собирает exact GitHub PR/run/attempt/jobs и обслуживает `state/wait`; `tools/delivery/admission.py` остаётся единственным admission evaluator; `tools/delivery/harness.py` уже хранит append-only local command evidence. Quality Graph artifact PR #144 содержит provenance и failed node, но не test-level cause; exact cause доступна в bounded job diagnostics.

Evidence gap-check:

- PR #144 run `34933440293`, attempt 1, head `b964901b…`, job/check `Integration (2/2)` / `104266277494`: единственный primary `REGRESSION_FAILURE` — `inspection_item_complete_001_mariadb_test.php`, mode `--missing-revision`, `JsonException: Syntax error` в read `*.json`; same-head attempt 2 GREEN. Test source показывает worker publication и parent read как отдельные operations, что подтверждает diagnosed partial-publication race.
- Existing setup seam до product behavior: `ci.py` выбрасывает exact `test MariaDB unavailable; run make test-db-reset migrate` для integration/e2e preflight.
- Отдельного retry ledger нет. GitHub run attempt + exact head/candidate дают существующее bounded state; automatic decision разрешён только на attempt 1, attempt >1 исчерпывает budget.

## Goals / Non-Goals

**Goals:**

- Один public triage object в `state/wait`, одинаковый для FAST и STANDARD/CRITICAL.
- Closed signatures рядом с delivery triage policy, максимум две.
- Fetch/parse только bounded diagnostics failed applicable job после полного machine job inventory; root получает compact result.

**Non-Goals:**

- Publisher/workflow semantics, новый waiter/store/service, общий log/NLP classifier, product code, test expectations, FAST/T06/#153, #97 и #94 целиком.
- Автоматический dispatch GitHub retry, push или merge: `SAME_SOURCE_RETRY` — bounded recommended action и policy permission, не новый orchestration layer.

## Decisions

1. **Owner module:** triage policy живёт в `tools/delivery/` и вызывается из `harness_context.py`; public schema включается в существующий state/wait result. `admission.py` не дублирует signatures и не принимает diagnostics за GREEN.
   Альтернатива — helper только в unit tests — отклонена: он не достигает public route.

2. **Representation:** immutable tuple для каждой signature: stable id, applicable job/check, required structured provenance, exact bounded diagnostic observations, classification/action/retry policy. Regex допустим лишь внутри всей tuple.
   Альтернатива — общий registry exception strings — отклонена как широкий classifier.

3. **Diagnostics:** сначала exact run/attempt/jobs и failed inventory, затем для потенциально применимого job один bounded diagnostic retrieval/parsing. Triage output содержит references и counts, но не превращает diagnostic в evidence of GREEN.
   Альтернатива — парсить все полные job logs — отклонена по scope и measurement goal.

4. **Retry:** initial budget `1`; только attempt 1 exact same source может вернуть `retry_allowed=true`. Attempt 2+ и source/head drift запрещают новый retry. Existing GitHub attempt history является authoritative history, новый store не создаётся.

5. **Setup:** exact preflight marker применяется лишь до test execution в integration/e2e context; action ссылается на существующий `make test-db-reset migrate`, но T03 его не запускает.

6. **Persistence/rapid-pilot/architecture:** domain persistence owner отсутствует, product state не меняется, rapid-pilot adapter неприменим. Изменение затрагивает harness policy/tests и проходит planner-selected harness verification/architecture inventory без product migration ceremony.

## Risks / Trade-offs

- [Risk] GitHub log formatting изменится → signature перестанет совпадать и fail closed в `UNKNOWN`; negative-neighbor tests запрещают расширять regex автоматически.
- [Risk] Attempt number не всегда означает T03 retry → permission всё равно только сужается: attempt >1 не разрешает автоматический повтор.
- [Risk] Product bug имитирует точный tuple → exact test/mode/read site + known job context уменьшают риск; любая provenance ambiguity даёт `UNKNOWN`.
- [Risk] Diagnostic retrieval недоступен → public result `UNKNOWN`, а не inferred transient/setup.

## Migration Plan

Добавить контракт и RED на public CLI fixture, затем минимальную policy integration. Deployment/data migration отсутствуют. Rollback — удалить policy integration и вернуться к fail-closed `UNKNOWN`; GitHub failure history остаётся неизменной.
