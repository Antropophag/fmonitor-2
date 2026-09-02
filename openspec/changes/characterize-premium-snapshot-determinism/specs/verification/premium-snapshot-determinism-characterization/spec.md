## Purpose

Определяет воспроизводимый `PILOT_ONLY` oracle текущего pure premium calculator, чтобы arithmetic и validation drift были наблюдаемы без утверждения pilot-формулы целевой финансовой политикой.

## ADDED Requirements

### Requirement: Oracle изолирует pure versioned calculation

Focused characterization SHALL вызывать current versioned calculator только с literal in-memory arrays и SHALL доказывать отсутствие зависимости результата от DB, HTTP, actor, clock, filesystem и snapshot persistence. Каждый coefficient и reason MUST быть маркирован `PILOT_ONLY`.

#### Scenario: Exact replay
- **WHEN** один и тот же полный набор sourced operands, payments и exclusions рассчитывается дважды
- **THEN** оба nested result byte-normalized эквивалентны и содержат одну version literal

### Requirement: Oracle фиксирует integer formula boundaries

Characterization SHALL покрыть independently calculated nominal, zero, maximum, deadline/completion и Kss-floor examples с exact integer results. Expected values MUST происходить из Gate 1 worked examples, а не из production calculator.

#### Scenario: Nominal late calculation
- **WHEN** literal operands задают premium, shaft, progress, deadline, report date и sourced payment rows
- **THEN** result содержит exact five-step trace, amounts, Kss и ordered reasons из executable spec

#### Scenario: Completion bounds comparison date
- **WHEN** completion отсутствует, до/on/после report date или deadline
- **THEN** observed days-late/Kss outcomes соответствуют текущей `PILOT_ONLY` comparison policy

#### Scenario: Zero and maximum boundaries
- **WHEN** accepted integer operands находятся на exact allowed boundaries
- **THEN** result не использует floating-point rounding и совпадает с independently computed integer examples

### Requirement: Payments не backsolve entitlement

Characterization SHALL различать closures и actual payouts, signed rows, zero/empty evidence, net-negative rejection и current pool/remaining floors. Она MUST NOT утверждать signed closure как target reversal semantics.

#### Scenario: Payout changes only reconciliation fields
- **WHEN** меняется только actual-payout evidence
- **THEN** progress, fund и accrued не меняются, а payout totals/discrepancy отражают новый input

#### Scenario: Closure floors and rejection
- **WHEN** closure net находится below/at/above accrued or becomes negative
- **THEN** pool/remaining floors либо exact rejection наблюдаемы независимо от payout evidence

### Requirement: Exclusions и reasons наблюдаемы

Characterization SHALL покрыть list и associative exclusion inputs, normalization, validation, distributable zero и stable reason order/cardinality. Эти outcomes MUST оставаться `PILOT_ONLY`.

#### Scenario: Valid exclusion
- **WHEN** sourced valid exclusion передан вместе с otherwise distributable result
- **THEN** accrued сохраняется, distributable становится zero и ordered reasons включают `CALCULATION_EXCLUDED`

#### Scenario: Invalid exclusion
- **WHEN** code, date, provenance или element shape invalid
- **THEN** calculation отвергается с нулевым external effect

### Requirement: Validation matrix фиксирует permissive contrasts

Characterization SHALL различать missing members, scalar/list/associative shapes, numeric types/ranges, exact dates, provenance whitespace/hash case, future evidence dates и echoed extra fields/key order. Oracle MUST записывать permissive observations как target contrasts, не requirements финансового продукта. Unbounded payment-row aggregate overflow SHALL оставаться documented source risk вне executable matrix: достижение 64-bit boundary при per-row limit требует resource-amplifying input.

#### Scenario: Invalid operand or payment
- **WHEN** ровно одно required value/type/range/date/provenance/list constraint нарушено
- **THEN** calculation отвергается на соответствующей input family без partial result

#### Scenario: Noncanonical accepted input
- **WHEN** current calculator получает accepted future-dated evidence, extra fields, associative exclusions или signed rows
- **THEN** exact echo/normalization result наблюдаем и явно маркирован `PILOT_ONLY`

### Requirement: Oracle детерминированно интегрирован

Focused characterization SHALL выдавать repeatable normalized transcript, отличать setup failure от regression и запускаться ровно один раз в canonical characterization stage. Regression MUST сделать stage и aggregate verification красными.

#### Scenario: Repeatable canonical run
- **WHEN** oracle выполняется дважды отдельно и затем canonical stage
- **THEN** normalized transcripts совпадают, focused result green и canonical registration встречается один раз

#### Scenario: Sensitive regression
- **WHEN** arithmetic, validation, reason order или echo assertion нарушена
- **THEN** focused oracle сообщает `REGRESSION`, а canonical stage/aggregate становятся red
