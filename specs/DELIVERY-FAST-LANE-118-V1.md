# DELIVERY-FAST-LANE-118-V1 — bounded verification fast path

## Простыми словами

Небольшая локальная UI-правка получает короткий delivery route только когда существующие repository-owned данные однозначно доказывают её owner closure и называют конкретный проверяющий oracle. Неизвестная область повышается, а auth и delivery policy всегда остаются CRITICAL. Этот slice не реализует F02/F05/F08/F09/F11 и расширенный real-CI F12.

## Public seams

- Actor: delivery root/executor/reviewer и GitHub Quality Graph.
- Classification: `python3 tools/delivery/change-verification.py plan|check|run`.
- Lifecycle package: `python3 tools/delivery/harness.py prepare`.
- Admission: `python3 tools/verification/ci.py plan|aggregate` и существующий Quality Graph.
- Authority: effective Git source, verification policy, registered inventory и consumer ownership facts. LLM output не является входом.

## Acceptance

### F01 — bounded UI is FAST

При единственном bounded UI/view/JS/CSS owner, однозначном closure и registered acceptance oracle plan возвращает `verification_lane=FAST`, `required_reviews=["final"]` и `selected_checks` с exact canonical argv этого oracle. Category argv и unrelated integration/e2e checks отсутствуют.

### F03 — persistence is not FAST

Добавление persistence owner повышает candidate из FAST. Result содержит deterministic reason/escalation и не содержит FAST admission contract.

### F04 — auth/RBAC is CRITICAL

Любой auth/RBAC/identity/session owner возвращает `CRITICAL` независимо от остальных paths.

### F06 — delivery policy is CRITICAL

Изменения `tools/delivery`, verification policy/inventory/router, Quality Graph или workflows возвращают `CRITICAL`. Реализация этого spec поставляется обычными Gates 1–5.

### F07 — unknown closure fails closed

Unknown или ambiguous boundary/consumer closure не возвращает FAST. CLI либо возвращает machine-readable escalation минимум до STANDARD, либо ненулевой `SETUP_FAILURE` до создания plan.

### F10 — selected oracle is sensitive

Healthy bounded fixture проходит exact selected oracle. Defective variant того же public UI behavior выполняет тот же oracle и возвращает intended RED по assertion, не setup failure.

### Minimal F12 — exact-source admission

Для exact current SHA каждый selected check обязан присутствовать. Selected success допускается; selected failure, absence или unexpected skip блокируют admission. Check, явно не выбранный policy, нейтрален. Success другого SHA не принимается.

### Conventional CI input and reconstruction

FAST candidate имеет ровно один applicable committed `openspec/changes/<change>/verification-input.json`. Ноль, более одного, malformed либо не соответствующий current candidate input не выбирают FAST. Plan, executing и admission jobs независимо строят plan из current HEAD, input и canonical policy/inventory; serialized plan между jobs не передаётся. Две reconstruction одного HEAD byte-equivalent по lane, selected checks, reviews, reasons/escalations и admission expectations; несовпадение fail closed.

## FAST review contract

FAST consumer проходит classify → intended RED → implementation → selected affected checks → один independent final review → exact-source admission. Final package одновременно содержит acceptance/test sensitivity, diff, lane/reasons, отсутствие critical boundaries, RED→GREEN и exact selected checks. Отдельного Gate 3 для FAST consumer нет.

## Exclusions

Нет нового planner/registry/router/state machine, Gate 6, I3/I4, supervisor, provenance/snapshot framework, dashboard, execution classifier, Docker expansion или изменения `run-in-profile`. F02/F05/F08/F09/F11 и expanded real-CI F12 поставляются отдельно.
