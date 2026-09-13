## Why

Небольшие bounded product changes сейчас платят стоимость полного Gate 3/Gate 5 lifecycle и полного Quality Graph независимо от доказуемого blast radius. Issue #118 требует сократить lead time без субъективной оценки риска: FAST допускается только при machine-provable closure и при любой неопределённости автоматически повышается.

## What Changes

- Существующий change-verification planner будет выдавать `FAST`, `STANDARD` или `CRITICAL`, причины, escalation и точный набор конкретных registered checks/canonical commands на основе canonical boundary/consumer ownership metadata.
- FAST lifecycle сохранит intended RED, но заменит отдельные Gate 3 и Gate 5 одним независимым final review; локальная correction того же seam потребует affected rerun и reviewer delta без повторного planning.
- Существующий Quality Graph routing получит bounded exact-source FAST admission: запускаются только mechanically selected checks, а policy-unselected job отличается от failed, interrupted и unexpected skipped selected job.
- Unknown/ambiguous closure и critical owners fail closed.
- Этот вертикальный slice докажет F01/F03/F04/F06/F07/F10 и minimal F12; F02/F05/F08/F09/F11 и расширенный real-CI F12 остаются отдельными changes.
- Само изменение #118 классифицируется `CRITICAL` и проходит существующий полный процесс со specialized controls, без нового Gate 6.

## Capabilities

### New Capabilities

- `delivery/risk-based-verification-lanes`: machine-provable v1 classification, concrete selected-check roster и one-review FAST lifecycle contract.

### Modified Capabilities

- `delivery/current-ci-quality-graph`: full/docs/harness-only routing расширяется exact-source FAST mode с fail-closed selected-check admission.

## Impact

- Canonical planner: `tools/delivery/change-verification.py` и `.quality-graph/verification-policy.json`.
- Canonical CI router/aggregate: `tools/verification/ci.py`, `quality-graph.yml` и generated `.github/workflows/quality-graph.yml`.
- Lifecycle packages: `tools/delivery/harness_context.py` только в минимальной части required review routing.
- Executable contracts: один новый stable spec и focused verification tests; существующие inventories расширяются только если нужен реальный fixture/oracle.
- `run-in-profile` остаётся неизменяемым execution primitive для совместимых selected checks; Docker socket, nested Docker и blanket category containerization исключены.
- Source oracle: issue #118 и подтверждённый context #110 / closed PR #117 / merged PR #121 / merged exact-source GREEN PR #125. Target public seams: `change-verification.py plan/check/refresh`, `ci.py plan/aggregate` и текущий Quality Graph admission.
- Release value: healthy F01 требует один independent review и ноль unrelated full integration/e2e categories. Non-goals: F02/F05/F08/F09/F11/expanded F12, новый planner/registry/orchestrator/provenance DB/Gate 6/I3/I4/dashboard/LLM authority/execution classifier.
