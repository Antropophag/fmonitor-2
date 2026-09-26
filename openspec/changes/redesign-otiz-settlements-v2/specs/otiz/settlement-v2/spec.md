# OTIZ settlement v2

## ADDED Requirements

### Requirement: Draft SHALL be financially neutral
Creating, refreshing, viewing, exporting or deleting a draft MUST NOT create claims, obligations, payments, deductions in recognized economics, or change the baseline for another draft.

#### Scenario: Abandoned draft does not reduce new volume
- **WHEN** an unaccepted 30% draft exists and confirmed progress grows from an accepted 20% to 50%
- **THEN** a new draft contains 30% new volume and the abandoned draft changes no recognized aggregate

### Requirement: Acceptance SHALL claim exact entitlements atomically
Acceptance MUST lock the revision, re-authorize, validate admission and money invariants, create unique entitlement claims and recipient obligations, and publish audit in one transaction.

#### Scenario: Competing drafts claim the same work
- **WHEN** two revisions with different operation ids concurrently accept the same 10,000-ruble entitlement
- **THEN** exactly one succeeds, one returns a domain conflict linked to the accepted calculation, and exactly one claim/obligation exists

### Requirement: Payments SHALL execute saved obligations independently of new progress
The payable queue MUST select active accepted obligations with positive remaining balance and MUST NOT require new work, a current-year object, an unfinished case or a fresh draft.

#### Scenario: Old obligation remains payable
- **WHEN** object 014903 has a paid accepted 60,000-ruble calculation and a separate unpaid accepted 15,000-ruble calculation
- **THEN** the queue and object economy expose exactly 15,000 rubles linked to the second calculation and its recipients, and paying it creates no new claim

### Requirement: Reversals and replacement SHALL preserve append-only history
Only drafts MAY be deleted. An unpaid acceptance MAY be cancelled with reason or atomically replaced; a payment mark MAY be reversed with reason. Paid or dependent accepted history MUST NOT be rewritten by ordinary cancellation.

#### Scenario: Payment reversal restores only the obligation
- **WHEN** an erroneous payment mark for 15,000 rubles is reversed
- **THEN** the same obligation returns to payable, while accepted amount, claims, fund and recipient snapshot remain unchanged

### Requirement: Deductions SHALL not redistribute
Object-pool and personal disciplinary deductions MUST be positive, reasoned, auditable draft facts. A personal deduction reduces only that recipient; no disciplinary deduction returns in a future calculation.

#### Scenario: Personal deduction
- **WHEN** a 12,000-ruble pool with weights 60/40 has a 1,000-ruble deduction for the first recipient
- **THEN** allocations are 6,200 and 4,800 rubles, total payable is 11,000 and the second recipient receives no redistribution

### Requirement: Former employees SHALL default to payment
A historically eligible recipient who is currently dismissed MUST remain included by default. An explicit reasoned `do_not_pay` decision MAY redistribute that recipient's amount only within each affected object's original eligible crew.

#### Scenario: Decision is reversible without drift
- **WHEN** a 30,000-ruble pool has original weights 60/40 and the dismissed 40-weight recipient is switched `do_not_pay` then back to `pay`
- **THEN** allocations move 18,000/12,000 → 30,000/0 → 18,000/12,000 without changing original contribution or losing the zero recipient from audit

### Requirement: Monetary allocation SHALL be deterministic
Money MUST use integer cents and fixed-point coefficients. Largest-remainder allocation MUST use a stable identity tie-break and MUST NOT depend on current UI order.

#### Scenario: Repeated recalculation is exact
- **WHEN** grouping, searching, paging or reversing a draft decision is repeated
- **THEN** stored totals and residual cents are identical and never exceed the object pool

### Requirement: Composition mismatch admission SHALL fail closed
Every acceptance, current payment export and payment command MUST ask the shared #257 admission contract for every object. Open, stale or UNKNOWN mismatch state MUST block the whole object/package. Historical read remains available, and resolving a mismatch MUST require a new replacement rather than revive an old unpaid snapshot.

#### Scenario: Direct payment cannot bypass mismatch
- **WHEN** an old accepted unpaid calculation includes an object with an open composition mismatch and a user submits the payment URL directly
- **THEN** no payment fact is written, the obligation remains visible with a reason, and resolution alone does not make that snapshot payable

### Requirement: Calculation UI SHALL project one immutable revision in two groupings
The object and employee groupings MUST use the same revision and totals; multiple groups MAY remain expanded; accessible toggles, server search/sort/pagination and cross-links MUST preserve full mutation scope.

#### Scenario: Filtered multi-object payment remains whole-scope
- **WHEN** a user enters a multi-object calculation from one object's payable link
- **THEN** the page shows that object's contribution and the full calculation amount/scope before the single payment command, and filtering cannot create a partial payment

### Requirement: One calculation register SHALL include queue and history
The top-level OTIZ navigation MUST expose only `Расчёты` and `Экономика объектов`. The calculation register MUST filter the same identities by `Ожидают выплаты`, `Черновики`, `История`, and `Все`; historical URLs MUST resolve to the same register without duplicating data.

#### Scenario: Zero payable is not falsely paid
- **WHEN** an accepted calculation is reduced to zero without a payment fact
- **THEN** it appears in history as zero obligation and is not labelled as money paid
