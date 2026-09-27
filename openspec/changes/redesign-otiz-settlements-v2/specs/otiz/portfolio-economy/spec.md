# OTIZ portfolio economy

## ADDED Requirements

### Requirement: Economy SHALL start from the full authorized object catalog
Objects without a case, order, progress or calculation, future and completed objects, and objects with data problems MUST remain visible. Object identity and manual detail overlays MUST match the canonical object card.

#### Scenario: Empty and future objects remain visible
- **WHEN** the catalog contains an object without process facts and an object planned for 2045
- **THEN** both appear with unknown values preserved as unknown and neither presence implies financial eligibility

### Requirement: Portfolio filters and aggregates SHALL be server-side
Plan-finish-year values MUST be derived from actual filtered data and include all years plus `Без года`; search/sort MUST precede root pagination; KPI MUST represent the complete filtered set without multiplying funds through joins.

#### Scenario: Old debt is independent from portfolio year
- **WHEN** the plan-year filter is 2036 while a 2025 object has an unpaid accepted obligation
- **THEN** portfolio rows follow 2036 but the global payable queue and calculation register still expose the 2025 obligation

### Requirement: Positive payable totals SHALL be explainable
Every payable amount MUST trace to active accepted obligations and stable recipients. Unlinked imported totals MUST be shown as `Не найдено основание задолженности` and excluded from payment exports.

#### Scenario: Multiple obligations are not collapsed
- **WHEN** one object has active obligations of 6,000 and 9,000 rubles
- **THEN** economy shows 15,000 and `К выплатам` reveals both calculations; paying one leaves the other payable
