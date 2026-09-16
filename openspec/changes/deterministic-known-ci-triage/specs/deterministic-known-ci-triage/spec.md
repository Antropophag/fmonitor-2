## Purpose

Снижает повторное LLM-расследование CI failures, выдавая через существующий public delivery seam детерминированное bounded решение только для заранее доказанных infrastructure/setup signatures.

## ADDED Requirements

### Requirement: Public triage result is closed and exact-source bound
Существующий CI waiter/state/triage seam SHALL возвращать `classification`, принимающий только `PRODUCT_REGRESSION`, `INFRA_TRANSIENT`, `SETUP_FAILURE` или `UNKNOWN`, exact closed `known_signature_ids`, а также exact repository/PR/run/attempt/job/check, candidate source/head, matched signature/evidence, `confidence_basis`, `recommended_action`, `retry_allowed`, `retry_budget` и diagnostic references с complete failed-job/primary inventories. Любое отсутствующее, malformed, противоречивое, unavailable или stale обязательное observation MUST давать `UNKNOWN`, `signature_id=null`, пустое evidence, `retry_allowed=false` и normal triage. Незарегистрированный третий signature id MUST игнорироваться. Diagnostic/log retrieval MUST NOT считаться GREEN test evidence.

#### Scenario: Unknown failure fails closed
- **WHEN** failed current attempt не совпадает со всей конъюнкцией зарегистрированной signature и не имеет достаточного existing verifier evidence для product regression
- **THEN** public result равен `UNKNOWN`, запрещает automatic retry и сохраняет ссылки обычного triage

#### Scenario: Source drift invalidates prior decision
- **WHEN** head или candidate source отличается от source, к которому привязаны failure и retry history
- **THEN** старое transient/setup решение не переносится, а public result остаётся `UNKNOWN`

#### Scenario: FAST and STANDARD share one seam
- **WHEN** public triage вызывается для planner-selected FAST maintenance либо обычного STANDARD/CRITICAL route
- **THEN** обе ветви получают результат одного и того же classifier через существующий waiter/state seam

### Requirement: PR 144 transient signature is exact and bounded
Signature `pr144-inspection-partial-result-json-v1` SHALL совпадать только при одновременном наличии exact Integration check/job context, failure конкретного `inspection_item_complete_001_mariadb_test.php` в `--missing-revision`, decode failure конкретного worker result JSON на доказанном read site, неизменного candidate/head и неизрасходованного budget. Совпадение SHALL возвращать `INFRA_TRANSIENT`, `confidence_basis=deterministic_signature`, `recommended_action=SAME_SOURCE_RETRY`, `retry_allowed=true`, `retry_budget=1`. Общие `JsonException`, malformed JSON, timeout, connection reset, exit 1 или flaky test MUST NOT быть достаточны.

#### Scenario: Exact historical fixture permits one retry
- **WHEN** сохранённый fixture run `34933440293`, attempt 1, check/job `Integration (2/2)` / `104266277494`, head `b964901b73d567cf2a98efd41bcb31ef9b7b2a86` содержит exact доказанный failure tuple
- **THEN** public triage возвращает `INFRA_TRANSIENT`, `SAME_SOURCE_RETRY`, `retry_allowed=true` и один оставшийся retry

#### Scenario: Same text in another context is rejected
- **WHEN** error text совпадает, но job/check, test/mode, provenance или decode site отличается
- **THEN** результат не равен `INFRA_TRANSIENT`

#### Scenario: Generic product JSON failure is rejected
- **WHEN** product-owned output malformed и содержит `JsonException` без exact PR #144 tuple
- **THEN** результат равен `UNKNOWN` либо доказанному `PRODUCT_REGRESSION`, но не `INFRA_TRANSIENT`

#### Scenario: Retry budget cannot loop
- **WHEN** та же signature уже разрешила one same-source retry в данном run context
- **THEN** следующий failure не разрешает новый automatic retry и возвращает `UNKNOWN`/normal triage, если отдельное product evidence не доказывает regression

### Requirement: Setup classification requires exact precondition evidence
Signature `verification-mariadb-precondition-v1` SHALL применяться только к exact integration/e2e category precondition signal `test MariaDB unavailable; run make test-db-reset migrate` до достижения product behavior. Совпадение SHALL возвращать `SETUP_FAILURE`, `confidence_basis=deterministic_signature`, existing bounded recovery action и `retry_allowed=false`; classifier MUST NOT изобретать dependency install, host fallback или workaround.

#### Scenario: Known setup precondition is classified
- **WHEN** exact category preflight signal наблюдается в применимом integration/e2e job/check до запуска tests
- **THEN** public result равен `SETUP_FAILURE` и рекомендует только существующий bounded database preflight recovery

#### Scenario: Similar product failure is not setup
- **WHEN** product verifier failure упоминает MariaDB или setup похожим текстом, но не совпадает exact preflight location и signal
- **THEN** public result не равен `SETUP_FAILURE`

### Requirement: Failure history and admission remain append-only
Triage SHALL сохранять первый failure и его diagnostics после retry. Successful same-source retry MAY сделать current CI GREEN только по существующей exact-source/run-attempt admission policy; он MUST NOT переписать первый failure, считать diagnostics test evidence или ослабить current attempt guarantees.

#### Scenario: Successful retry preserves failed attempt
- **WHEN** attempt 2 на том же head успешно проходит после разрешённого transient retry
- **THEN** current CI может стать GREEN по существующей policy, а attempt 1 остаётся доступным в history/diagnostic references

### Requirement: Measurement uses observable proxies
Historical fixture comparison SHALL сообщать число mandatory log payloads materialized before decision, число model-driven triage steps и automatic retry count для before/after. Token savings MUST оставаться `UNKNOWN`, если нет поддерживаемой telemetry.

#### Scenario: Deterministic after path
- **WHEN** exact PR #144 fixture классифицируется public seam
- **THEN** after path фиксирует deterministic classification и bounded action без model semantic judgment и без требования root читать полный job log
