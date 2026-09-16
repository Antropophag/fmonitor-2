## Why

При CI failure root сейчас вручную извлекает job log и заново решает, является ли уже доказанный инфраструктурный случай основанием для same-source retry. T03 из #145 должен убрать это повторное LLM-расследование только для закрытого набора доказанных signatures, сохранив неизвестные случаи fail-closed.

## What Changes

- Существующий public `harness state/wait` seam возвращает закрытую CI triage classification `PRODUCT_REGRESSION | INFRA_TRANSIENT | SETUP_FAILURE | UNKNOWN` с exact run/attempt/job/check, candidate/head, matched evidence, deterministic confidence basis, bounded action/retry и diagnostic references.
- Добавляется repository-owned closed set ровно из двух initial signatures: доказанный PR #144 partial-result JSON race и существующий exact MariaDB precondition failure.
- Для PR #144 разрешается не более одного same-source retry; смена source/head и исчерпание attempt budget запрещают перенос решения, а исходный failure остаётся в GitHub/harness history.
- Неизвестные и похожие соседние failures остаются `UNKNOWN`; product regression выдаётся только при достаточном существующем verifier evidence.
- Proxy фиксирует mandatory log payloads materialized до решения, model-driven triage steps и automatic retry count; token savings без telemetry не заявляются.
- Не меняются product code, tests assertions, FAST classifier, T06/#153, publisher/workflow semantics, #97, общий #94, orchestration/evidence stores, auto-push/merge/deploy/settings.

## Capabilities

### New Capabilities

- `deterministic-known-ci-triage`: Bounded deterministic classification и retry decision для доказанных CI infrastructure/setup signatures через существующий delivery harness.

### Modified Capabilities

Нет.

## Impact

Actor — root delivery session. Source oracle — сохранённые run/attempt/job/log evidence PR #144 и существующий structured harness/category setup outcome. Target seam — существующие команды `python3 tools/delivery/harness.py state` и `wait`; ожидаются изменения только в `tools/delivery/`, их verification tests, normative spec/OpenSpec и delivery records. Новые runtime dependencies, persistence, service, dashboard или CI waiter не добавляются.
