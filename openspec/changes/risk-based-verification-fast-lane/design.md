## Context

См. `proposal.md`. На `origin/main` после PR #125 уже существуют три нужных owner seam: `change-verification.py` вычисляет effective diff, boundary/consumer closure и exact commands; `ci.py` владеет PR mode и aggregate expectations; declarative `quality-graph.yml` порождает GitHub workflow. PR #117 показал, что общий execution/provenance layer превышает задачу. PR #121/#125 оставили `run-in-profile` узким primitive и подтвердили, что category не является безопасной общей container boundary.

Изменение не владеет product persistence, не касается `rapid-pilot` и не меняет domain facts. Architecture-check impact ограничен существующими delivery/verification owners и generated Quality Graph consistency.

## Goals / Non-Goals

**Goals:**

- Механически классифицировать candidate и fail closed при неизвестном closure.
- Провести один end-to-end F01 fixture через planner, lifecycle contract и minimal exact-source CI admission.
- Измерить реальное сокращение reviews и CI work до расширения corpus.
- Уложить первый slice примерно в 420–650 infrastructure LOC, включая tests, но без generated workflow churn в LOC budget.

**Non-Goals:**

- Новый planner, registry, orchestration/state framework, provenance DB, snapshot system, dashboard/service, supervisor, Gate 6 или I3/I4.
- LLM как authority, transitive daemon analysis, Docker socket/nested Docker/blanket containerization.
- Изменение execution profiles или массовое переписывание product tests.
- F02/F05/F08/F09/F11, same-seam correction machinery и расширенный real-CI F12.

## Decisions

### 1. Lane является производным полем существующего verification plan

`change-verification.py build()` дополнит versioned result полями lane/reasons/escalations/selected checks/reviews. Он уже связывает base, HEAD, actual/untracked bytes, policy, inventory и spec; поэтому refresh автоматически обнаруживает scope growth. Альтернатива — отдельный classifier — отвергнута как второй planner и источник drift.

### 2. Минимальные risk facts живут рядом с canonical boundary metadata

У существующей boundary policy добавится только необходимая machine-readable risk/FAST-eligibility семантика. Однозначный boundary match плюс consumer obligations образуют closure; неизвестный/ambiguous match остаётся fail-closed. File size и наличие других inventory entries не входят в predicates. Альтернатива — новый registry — запрещена и дублирует owner data.

### 3. FAST v1 ограничен proven UI/view fixture

Первый PR реализует F01/F03/F04/F06/F07/F10 и один healthy/defective fixture. F02/F05/F08/F09/F11 и expanded real-CI F12 не являются requirements этого change. Classification corpus становится GREEN до начала CI wiring.

### 4. CI routing потребляет plan, а не повторно классифицирует risk

`ci.py plan` валидирует exact-source lane plan и возвращает FAST mode/конкретный selected-check contract; whole-category expansion запрещён. Aggregate различает selected outcomes и policy-unselected. Quality Graph topology остаётся текущей. Альтернатива — второй FAST workflow — отвергнута.

### 5. FAST review — существующий prepared package с другим contract

`harness_context.py prepare` получает минимальную lane-aware ветку: FAST не требует standalone Gate 3, но final reviewer package обязан содержать intended RED, GREEN, diff, classification и exact selected checks. Correction state machine не вводится.

### 6. Execution profiles не расширяются

Selected argv сохраняет существующий route. Только уже совместимый focused check может быть обёрнут существующим `run-in-profile`; category-level integration/e2e не контейнеризируются. Неясная совместимость повышает applicability/lane.

### 7. Planned files и ownership

Canonical owners: `.quality-graph/verification-policy.json` (boundary/consumer policy), `tools/delivery/change-verification.py` (candidate plan), `tools/verification/ci.py` (CI selection/aggregate), `quality-graph.yml` + renderer (graph source), `tools/delivery/harness_context.py` (role package). Planned executable contracts: `specs/DELIVERY-FAST-LANE-118-V1.md`, `tests/Verification/change_verification_001_test.py`, `tests/Verification/verification_ci_001_test.py`, возможно один узкий `delivery_harness_001_test.py` case. Generated `.github/workflows/quality-graph.yml` и manifest меняются только через renderer.

## Risks / Trade-offs

- [Существующие boundaries слишком широки для safe FAST] → v1 допускает FAST только для явно помеченного bounded fixture; unknown остаётся STANDARD/CRITICAL, затем metadata расширяется малыми slices.
- [Static Quality Graph плохо выражает per-check selection] → v1 использует текущие nodes и explicit selected roster; если нужен dynamic orchestration framework, срабатывает hard stop и scope сужается до planner+lifecycle proof без CI claim.
- [Harness change сам себя ошибочно классифицирует FAST] → delivery/CI/policy paths имеют безусловный CRITICAL override и negative acceptance F06.
- [Generated workflow увеличит diff] → источник редактируется в `quality-graph.yml`/renderer, generated bytes проверяются существующим consistency check.
- [LOC превышает budget] → hard stop при прогнозе >800 infrastructure LOC или необходимости нового general abstraction; предложить planner-only FAST v1 с CI в отдельном slice.

## Migration Plan

1. Утвердить этот pre-implementation пакет владельцем; production implementation до подтверждения запрещена.
2. Gate 1: заморозить stable spec и verification input; Gate 2: получить intended RED F01/F03/F04/F06/F07 через public seams.
3. Поскольку #118 CRITICAL, выполнить обычный independent Gate 3, затем отдельный executor.
4. На Gate 5 проверить implementation/tests и measured corpus; один полный exact-source CI для самого #118.
5. После merge включать FAST только когда exact plan присутствует; отсутствие/ошибка возвращает текущий full mode. Rollback — удалить FAST selection branch, сохранив full routing.
