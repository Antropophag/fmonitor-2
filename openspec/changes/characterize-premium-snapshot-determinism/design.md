## Context

См. proposal, delta spec и `docs/operations/premium-snapshot-determinism-behavior-evidence.md`. Current calculator — static pure function с одним узким verifier; HTTP wrapper, operand acquisition и persistence являются отдельными seams. Точные финансовые norms не утверждены.

## Goals / Non-Goals

**Goals:**

- focused literal matrix для arithmetic, validation, reasons и permissive contrasts;
- expected-value independence и bounded overflow probes;
- deterministic canonical characterization без external fixtures;
- явный target contrast для всех v1 financial rules.

**Non-Goals:**

- target formula/application API или изменение calculator;
- HTTP/auth/persistence/hash/allocation behavior;
- DB schema ownership или payment/acceptance semantics.

## Decisions

### 1. Public characterization seam — complete function input/output

Verifier вызывает calculator через его единственный public method и сравнивает полный typed nested result/exception class. Reflection/private helpers не используются. Альтернатива — HTTP calculation — смешивает operand acquisition, persistence и authorization.

### 2. Literals и independent arithmetic принадлежат Gate 1

Каждый numeric/date/provenance case фиксируется в executable spec; verifier не переиспользует production constants/formula strings для expected results. Отдельный небольшой reference calculation допустим только как literal hand-worked table, не mirror implementation.

### 3. Matrix группируется по mutation axes

Nominal fixture меняет один dimension за case: operand validation, dates, payments, exclusions, echo shape. Это делает failure attribution точным и не превращает test в combinatorial clone calculator.

### 4. Overflow остаётся source risk, а не resource-amplifying test

64-bit runtime preflight отделяет setup; formula maxima проверяются directly.
Payment aggregate overflow не исполняется: при `1e12` per-row limit для первого
64-bit overflow требуется минимум 9 223 373 положительных rows, что противоречит
bounded/no-amplification harness. Gate 1 фиксирует отсутствие count/aggregate
guard как source risk; отдельный hardening slice должен сначала определить
target bound/error contract.

### 5. Ownership остаётся verification-only

Future owner candidate — `PremiumDecisions`; разрешённые зависимости будущего module — approved operand/evidence value contracts, clock only outside pure calculation, and ports. Persistence owner и rapid-pilot adapter не создаются этим change. Harness может require current oracle file, но application modules не зависят от rapid-pilot.

### 6. Architecture impact — registration only

Добавляется verifier и одно canonical characterization registration. Нет SQL, runtime DDL, production mutation, dependency edge или baseline allowance; `make architecture-check` должен остаться green.

## Risks / Trade-offs

- [Oracle выглядит как approval formula v1] → `PILOT_ONLY` в capability, cases, transcript и backlog; GRILL-001 остаётся owner.
- [Test mirrors implementation] → literal worked examples и fresh independent test review до GREEN.
- [Unbounded payment aggregate остаётся непроверенным runtime risk] → exact source-risk note и отдельный target hardening decision вместо resource-amplifying characterization.
- [Permissive inputs становятся target contract] → assertions маркируются contrast и не переносятся в future seam без owner decision.
- [Full nested equality overfits irrelevant order] → Gate 1 явно разделяет observed echo/order от semantic target requirements.

## Migration Plan

1. Завершить OpenSpec planning и fresh independent review.
2. Подготовить exact Gate 1 literals/matrix, fresh review и owner approval.
3. Fresh RED author создаёт smallest nominal replay RED; другой fresh reviewer утверждает до GREEN.
4. Отдельным reviewed RED/GREEN расширить boundaries, rejection, permissive contrasts и overflow.
5. Зарегистрировать один раз, выполнить focused/regression/architecture checks и fresh code review.
6. Сохранить target formula/norms в NEEDS_GRILL; sync/archive только после полного Done.

Rollback удаляет только verifier registration/harness и lifecycle records; production calculator и данные не меняются.
