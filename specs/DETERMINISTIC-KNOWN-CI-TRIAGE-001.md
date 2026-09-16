# DETERMINISTIC-KNOWN-CI-TRIAGE-001 — bounded triage доказанных CI failures

## Простыми словами

Если CI снова показывает уже доказанную инфраструктурную поломку, существующий delivery harness сам выдаёт компактную детерминированную классификацию и разрешает максимум один повтор того же source. Он не угадывает причины неизвестных failures, не использует LLM и не считает диагностический лог доказательством успешного теста.

## 1. Actor, seam и scope

Actor — root delivery session. Public seam — существующие команды `python3 tools/delivery/harness.py state [--observation <fixture>]` и `python3 tools/delivery/harness.py wait [--once] [--observation <fixture>]`.

Обе команды MUST возвращать один `ci.triage` object для одного observation независимо от FAST или STANDARD/CRITICAL route. T03 не добавляет command, waiter, evidence store или retry dispatcher. Initial registry MUST содержать ровно `pr144-inspection-partial-result-json-v1` и `verification-mariadb-precondition-v1`.

## 2. Public result

`ci.triage` MUST содержать:

- `classification`: ровно `PRODUCT_REGRESSION`, `INFRA_TRANSIENT`, `SETUP_FAILURE` или `UNKNOWN`;
- `known_signature_ids`: ровно два stable id из section 1, в deterministic order;
- exact `repository`, `pr`, `run_id`, `attempt`, `job_id`, `job`, `check`, `candidate_source`, `head` либо explicit `UNKNOWN`;
- `signature_id` (`null` без match), bounded `matched_evidence` и `confidence_basis` (`deterministic_signature` только при exact match, иначе `insufficient_evidence`);
- `recommended_action`: `SAME_SOURCE_RETRY`, `RUN_EXISTING_DB_PREFLIGHT`, `NORMAL_TRIAGE` или `NEEDS_OWNER`;
- boolean `retry_allowed`, integer `retry_budget` (maximum `1`) и `retry_remaining`;
- exact `diagnostic_references`, включая complete failed-job и primary `REGRESSION_FAILURE` inventories, и measurement `mandatory_log_payloads_materialized`, `model_triage_steps`, `automatic_retry_count`, `token_usage`.

Missing, malformed, contradictory, stale или unavailable обязательное observation MUST давать `UNKNOWN`, `signature_id=null`, `matched_evidence=[]`, `confidence_basis=insufficient_evidence`, `recommended_action=NORMAL_TRIAGE`, `retry_allowed=false`, `retry_remaining=0`. `UNKNOWN` никогда не повышается до transient по похожему exception text, а незарегистрированный третий signature id не может повлиять на classification.

## 3. PR №144 signature

`pr144-inspection-partial-result-json-v1` совпадает только при полной конъюнкции:

1. Exact current failed check/job `Integration (2/2)` с positive job id, exact run/attempt/head/candidate binding и failure conclusion.
2. Failed inventory содержит applicable primary test `tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php` в mode `--missing-revision`; downstream aggregate failures не заменяют primary evidence.
3. Bounded diagnostic содержит `JsonException: Syntax error` именно в этом test на worker-result JSON decode site и parent failure `Mode --missing-revision exit`.
4. Candidate source/head не изменились.
5. Current attempt равен `1`.

Exact match MUST вернуть `INFRA_TRANSIENT`, `confidence_basis=deterministic_signature`, `recommended_action=SAME_SOURCE_RETRY`, `retry_allowed=true`, `retry_budget=1`, `retry_remaining=1`.

Attempt `2+` MUST запретить новый retry и вернуть `UNKNOWN`/`NEEDS_OWNER`, если отдельное existing product evidence не доказывает `PRODUCT_REGRESSION`. Общие `JsonException`, malformed product JSON, timeout, connection reset, exit 1 и flaky result MUST NOT совпадать.

## 4. Setup signature

`verification-mariadb-precondition-v1` совпадает только когда applicable integration/e2e category job до первого `VERIFY <test>` наблюдает exact signal `test MariaDB unavailable; run make test-db-reset migrate`.

Match MUST вернуть `SETUP_FAILURE`, `confidence_basis=deterministic_signature`, `recommended_action=RUN_EXISTING_DB_PREFLIGHT`, `retry_allowed=false`. Action обозначает существующий recovery `make test-db-reset migrate`; T03 MUST NOT запускать его автоматически или изобретать workaround. Похожее сообщение после product verifier, общий `SETUP_FAILURE`, MariaDB error внутри product test или иной context MUST NOT совпадать.

## 5. Product regression и UNKNOWN

`PRODUCT_REGRESSION` допустим только если existing structured CI evidence однозначно показывает failure product verifier и ни одна signature не совпала. Иначе result MUST быть `UNKNOWN`. Diagnostic/log retrieval является `DIAGNOSTIC`, MUST NOT становиться GREEN test evidence и MUST NOT менять admission semantics.

## 6. History, replay и concurrency

Triage read-only, deterministic и idempotent. Он MUST NOT изменять GitHub results, checkout или product facts. Attempt 1 failure/references MUST оставаться после attempt 2. Successful same-source attempt 2 MAY сделать current CI GREEN только по существующей admission policy и MUST NOT переписать attempt 1. Run/attempt/head mismatch, source drift и другой worktree fail closed.

## 7. Executable cases

| Case | Input | Expected public result |
|---|---|---|
| A | Exact historical PR #144 fixture | `INFRA_TRANSIENT`, retry true |
| B | Тот же text, другой job/context/provenance | `UNKNOWN`, не transient |
| C | Generic malformed product JSON | не transient |
| D | Exact MariaDB precondition до tests | `SETUP_FAILURE` |
| E | Похожий product test failure | не setup |
| F | Unknown failure | `UNKNOWN`, retry false |
| G | First known transient, attempt 1 | один retry разрешён |
| H | Та же signature, attempt 2 | retry запрещён |
| I | Head/candidate изменился | старое решение не переносится |
| J | Attempt 1 затем successful retry | первый failure остаётся в history |
| K | Successful same-source retry | current CI MAY стать GREEN только existing admission policy |
| L | Diagnostic retrieval | не GREEN evidence |
| M | Binding mismatch | exact-source/run-attempt guarantees fail closed |
| N | FAST и STANDARD observations | один classifier/schema |

## 8. Measurement

Historical fixture MUST показывать BEFORE: один full job-log payload, минимум один model-driven triage step, один owner-triggered retry; AFTER: не более одного bounded applicable diagnostic payload, `model_triage_steps=0`, `automatic_retry_count=0`. `token_usage` MUST быть `UNKNOWN` без telemetry.

## 9. Authorization и audit

T03 не расширяет GitHub permissions. Retry permission не является authorization на dispatch, push, merge или deployment. Audit — append-only existing GitHub attempts плюс diagnostic references; новый persistence owner отсутствует.
