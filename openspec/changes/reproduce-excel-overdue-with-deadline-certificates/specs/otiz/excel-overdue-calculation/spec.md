## ADDED Requirements

### Requirement: Source-bound Excel operands

The system SHALL capture raw plan_finish_date when calculating, use the current accepted certificate deadline when present, and use current corrected PTO as comparison date even after report date. Without PTO the comparison date SHALL be the report date. Missing original deadline or invalid certificate evidence SHALL block calculation. Exact evidence grammar is OTIZ-EXCEL-INPUTS-001.

#### Scenario: Raw plan and certificate

- **WHEN** an accepted transfer certificate exists at execution
- **THEN** its deadline replaces raw plan for arithmetic and both sources remain recorded

#### Scenario: Future PTO

- **WHEN** plan is10August, report15August and current PTO20August
- **THEN** daysLate is10

### Requirement: Penalty applies after confirmed payments

The system SHALL use OTIZ-EXCEL-CALCULATION-001: calendar days, kss=max(0,10000-100*days), integer HALF-UP money, remaining=max(progressAmount-paidBefore,0), penalty=remaining*(1-kss), pool=max(remaining-penalty,0). Only signed confirmed paid closures within report cutoff SHALL enter paidBefore. Largest remainder with binary tab tie-break SHALL conserve distributed cents.

#### Scenario: Workbook arithmetic

- **WHEN** progress amount49725000cents, paid9360000cents and9days late
- **THEN** remaining40365000, penalty3632850 and pool36732150

#### Scenario: Repeated confirmed payouts

- **WHEN** fund100 and Kss0.9 with confirmed paid90
- **THEN** next pool is9; after confirmed total99 next pool is0.90

#### Scenario: Unpaid drafts

- **WHEN** multiple drafts exist without confirmed payment
- **THEN** they do not change paidBefore or one another's validity merely by their creation

### Requirement: Existing publication and history

New snapshots SHALL use premium-calculation-v2-excel and preserve exact operand/source/payment evidence, trace, version and allocation policy through OTIZ-EXCEL-PUBLICATION-001. Existing atomic publication, acceptance, payment receipts, global ledger and reversal lifecycle SHALL remain. Historical results SHALL NOT be rewritten. Old formula versions SHALL require explicit recalculation before new payment. Yii2/XLSX SHALL display the calculation and used deadline evidence. No additional entitlement/supersession mechanism is introduced.

#### Scenario: Historical snapshot

- **WHEN** a certificate is corrected and a new calculation published
- **THEN** the new result references the new revision and the old snapshot remains byte-identical

#### Scenario: Repeated payment

- **WHEN** the same confirmed payment operation is retried
- **THEN** the existing receipt returns its saved result without another payout
