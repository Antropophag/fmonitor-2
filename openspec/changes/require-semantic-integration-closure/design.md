## Context

См. `proposal.md` и delta spec. Текущий planner уже владеет path boundaries, lane, required categories и commands. `tools/verification/suites.tsv` после #135 — единственный canonical inventory. Gap: общий `application-code` не отличает authoritative current-state/domain/recovery semantics, а category command не выражает closure всего зарегистрированного integration set.

## Goals / Non-Goals

**Goals:**

- Добавить небольшой deterministic guard в существующий public `change-verification.py build/check`, используемый `harness.py prepare`.
- Представить high-risk surfaces как минимальный closed set в существующей policy и выдать structured evidence.
- Получать integration verifier set только через canonical inventory API.

**Non-Goals:**

- Capability-to-consumer graph, точный transitive frontier и новый planner/registry.
- Gate 3 completeness audit, CI feedback expansion, Gate 6, product/runtime changes.
- Изменение FAST safety/classifier либо blanket integration для STANDARD/CRITICAL.

## Decisions

1. **Classification живёт в существующей verification policy.** Добавляется один валидируемый список semantic surfaces с именем, path patterns, причиной и required category `integration`. Это repository-owned closed set рядом с boundaries; отдельного manifest нет. Альтернатива — hard-coded paths в Python — хуже проверяется и скрывает owner policy.

2. **Category closure строится из `suites.tsv`.** При match planner выбирает canonical inventory entries категории `integration` и добавляет каждый зарегистрированный verifier command. Пустой set — deterministic failure до plan emission. Альтернатива — один representative `category_argv` — не закрывает класс #148; второй registry запрещён.

3. **Structured output дополняет существующий plan.** Отдельный массив semantic escalations содержит surface, changed paths, reason, required category и selected checks. Lane остаётся результатом текущей classifier logic; semantic closure не означает blanket CRITICAL и не меняет FAST boundaries, кроме невозможной комбинации FAST path с protected surface.

4. **Executable regression использует disposable repository через настоящий CLI/public prepare seam.** Fixture содержит direct GREEN oracle, protected owner и downstream integration consumer в `suites.tsv`; RED доказывает, что старый plan опускает consumer. Matrix проверяет positive/negative cases, missing verifier diagnostic, tamper/check behavior и healthy FAST compatibility без Docker/DB/network.

5. **Owning module и dependencies.** Owner — `tools/delivery/change-verification.py`; разрешённые зависимости — stdlib, существующая policy и `tools/verification/inventory.py`. Persistence owner, rapid-pilot adapter и product architecture не меняются. Architecture check impact ограничен verification tooling/tests.

## Risks / Trade-offs

- [Closed-set patterns могут быть шире точного consumer frontier] → это сознательная conservative category closure Slice A; точность оставлена Slice B.
- [Новый semantic owner появится вне patterns] → policy является reviewable repository-owned closed set и может расширяться отдельным contract change; Slice A не обещает graph inference.
- [Integration category велика] → применяется только protected semantic surfaces, не любой PHP/JS/docs и не все STANDARD/CRITICAL.

## Migration Plan

Изменение атомарно доставляется вместе со spec, RED/GREEN regression, policy и canonical test registration. Rollback удаляет guard и его artifacts; product data migration отсутствует. После focused checks и независимых Gates exact-source full matrix выполняется только GitHub CI.
