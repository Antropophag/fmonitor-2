## Why

Перед переносом расчёта premium snapshot нужен узкий воспроизводимый oracle
текущей versioned pure-функции. Существующий verifier покрывает один nominal
пример и ошибочно называет pilot Kss coefficient утверждённым, оставляя
validation, boundary arithmetic, reason ordering и noncanonical input behavior
незащищёнными от drift.

## What Changes

- Добавить строго `PILOT_ONLY` characterization
  `PremiumCalculation::calculate` версии `premium-calculation-v1` на literal
  sourced operands без HTTP, DB, clock и snapshot persistence.
- Зафиксировать exact replay, integer arithmetic/rounding, completion/report-date
  comparison, Kss floor, closure/payout separation, exclusions и reason order.
- Покрыть missing/type/range/date/provenance rejection, zero/maximum boundaries,
  signed rows, aggregate-overflow risk и permissive/noncanonical input contrasts.
- Явно отделить принятое product-требование воспроизводимости от неутверждённых
  v1 coefficients, reason vocabulary и evidence-date policy.
- Подключить focused oracle к canonical characterization без изменения formula,
  production seam, snapshot schema или rapid-pilot domain logic.

Actor context: сотрудник ОТиЗ хочет воспроизводимо рассчитать draft из
датированных sourced operands. Source oracle:
`rapid-pilot/legacy-migration/PremiumCalculation.php` и текущий
`rapid-pilot/verify-premium-calculation.php`. Target public seam candidate:
`PremiumDecisions::calculatePremiumSnapshot`; этот change его не утверждает и
не реализует.

Release value: будущая замена calculator сможет доказать intentional parity или
явное отличие, не смешивая pure arithmetic с authorization, persistence,
acceptance и выплатами.

NEEDS_GRILL: exact premium/Kss/KTU norms, admissible evidence dates, target
reason vocabulary и financial release scope остаются в GRILL-001. Это блокирует
target `PREMIUM-OPERANDS-001`, `PREMIUM-SNAPSHOT-DETERMINISM-001` как product
formula, snapshot acceptance и payment slices, но не блокирует строго
`PILOT_ONLY` characterization текущей pure-функции.

Явные non-goals:

- утверждение v1 coefficients, Kss penalty или payout/reversal semantics;
- characterization HTTP `/pilot/otiz/calculate`, LocalAuth/RBAC/CSRF или actor;
- создание/хеширование snapshot, objects, allocations, issues либо events;
- canonical premium schema, runtime-DDL removal или persistence redesign;
- acceptance, payment closure/reversal, workforce attribution или operand
  acquisition;
- исправление permissive array/provenance/overflow behavior в этом change.

## Capabilities

### New Capabilities

- `verification/premium-snapshot-determinism-characterization`: воспроизводимый
  `PILOT_ONLY` oracle текущей pure versioned premium calculation и её validation
  boundaries.

### Modified Capabilities

Нет.

## Impact

- Новый focused verifier/test вокруг существующей pure calculator function.
- Canonical characterization registration и детерминированный transcript.
- Только test-owned in-memory literal arrays; без DB/session/filesystem fixtures.
- Никаких production API/schema/dependency или formula changes.
