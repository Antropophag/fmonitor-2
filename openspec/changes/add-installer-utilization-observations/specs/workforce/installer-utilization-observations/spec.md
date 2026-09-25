# Installer utilization observations

## ADDED Requirements

### Requirement: Daily immutable observations
The system SHALL atomically capture at most one real utilization observation per Europe/Moscow date from the shared utilization owner and SHALL preserve its denominator, classification and drill-down evidence unchanged.

#### Scenario: Retry, race and failure
- **WHEN** a slot repeats, concurrent workers capture it, or persistence fails
- **THEN** retry/race produce one result and failure publishes no partial point

### Requirement: Current and historical dashboard
The dashboard SHALL show the three exclusive current groups and stock grouped historical bars for without-current and its without-next subset, with honest unavailable/missing/one-point states and first/last date-denominator comparison.

#### Scenario: Historical drill-down
- **WHEN** an authorized user activates a saved bar
- **THEN** the route shows exactly the saved people and reasons for that observation and bucket

### Requirement: Explicit access and scheduling
Capture SHALL be available only to the explicit jobs worker path; reads SHALL require existing dashboard access plus full `installers.read`, and GET/HEAD SHALL not capture or synchronize.

#### Scenario: Read does not capture
- **WHEN** an authorized or denied actor performs GET or HEAD
- **THEN** no job, synchronization or observation row is created

### Requirement: User-facing workforce presentation
Directory and card SHALL render workforce status with the stock status label, SHALL omit integration provenance/timestamp from user copy, and SHALL preserve readable local spacing at narrow width.

#### Scenario: Narrow installer card
- **WHEN** an authorized user opens the installer card on a narrow viewport
- **THEN** the status is a stock label, technical integration copy is absent, and panels remain separated from each other and their text
