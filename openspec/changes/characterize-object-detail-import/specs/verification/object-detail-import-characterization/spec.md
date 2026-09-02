## Purpose

Даёт воспроизводимый PILOT_ONLY oracle текущего serial object-detail import,
чтобы последующий перенос schema ownership сохранял наблюдаемую data semantics,
не повышая UNKNOWN pilot behavior до product requirement.

## ADDED Requirements

### Requirement: Characterization exercises the real operator seam

Characterization SHALL запускать реальный object-detail import CLI как migration
operator против private synthetic source и isolated target generation. Expected
facts MUST независимо вычисляться из literal fixtures, а не копироваться из
importer implementation.

#### Scenario: Clean run projects one detail and one quarantine fact
- **WHEN** active target set содержит один object с полным source row и один
  object без source row, а operator применяет import с canonical capture time
- **THEN** CLI сообщает один created detail и один created quarantine
- **AND** target содержит только ожидаемые `technical-object-detail-v1` facts
  с independently calculated hashes, payload/provenance и supplied capture time

#### Scenario: Static transcript cannot impersonate execution
- **WHEN** CLI не был реально выполнен или target facts не соответствуют его
  reported result
- **THEN** characterization завершается regression failure
- **AND** заранее напечатанный ожидаемый transcript не считается evidence

### Requirement: Six-field projection remains exactly observable

Accepted detail characterization SHALL проверять ровно `floors`, `weight`,
`speed`, `pittype`, `pitmaterial`, `paired`: trimmed raw values, scalar display,
type-4 dictionary display и field provenance. Она MUST NOT утверждать import
`lift_type` либо бизнес-валидность скалярных значений.

#### Scenario: Dictionary and scalar fields are projected
- **WHEN** fixture содержит nonblank type-4 id и обычные scalar values с
  surrounding whitespace
- **THEN** stored payload содержит trimmed raw, independently expected display
  и source metadata каждого из шести fields
- **AND** content SHA-256 соответствует canonical material без capture time

### Requirement: Serial exact repeat preserves first accepted evidence

Characterization SHALL зафиксировать current serial hash-repeat outcome как
PILOT_ONLY observation. Она MUST сравнивать полные before/after rows, а не только
aggregate counts.

#### Scenario: Repeat uses a different capture time
- **WHEN** тот же source material импортируется последовательно второй раз с
  другим canonical capture time
- **THEN** CLI сообщает detail и quarantine как already present без creates
- **AND** original payload JSON, hashes и captured times остаются byte-identical

### Requirement: Run-level rejections preserve target DML state

Characterization SHALL различать quarantine missing-source outcome и run-level
rejections. Metadata/dictionary rejection и existing-hash conflict MUST не
создавать новый quarantine code и MUST оставлять полный target family DML state
неизменным.

#### Scenario: Changed detail conflicts atomically
- **WHEN** accepted detail уже существует, source material изменён и тот же batch
  содержит другой pending target write
- **THEN** real CLI завершается с stable `DETAIL_PROJECTION_CONFLICT` category
- **AND** полный target family остаётся byte-identical, включая pending write

#### Scenario: Required metadata is incomplete
- **WHEN** source metadata не содержит один из шести required fields
- **THEN** CLI завершается с `SOURCE_METADATA_INCOMPLETE` до target DML
- **AND** detail и quarantine facts остаются byte-identical

#### Scenario: Type-4 dictionary value is unknown
- **WHEN** nonblank source id отсутствует в dictionary fixture
- **THEN** CLI завершается с `SOURCE_DICTIONARY_VALUE_UNKNOWN` до target DML
- **AND** detail и quarantine facts остаются byte-identical

### Requirement: Harness is isolated, deterministic, and self-cleaning

Verifier SHALL использовать collision-resistant private source/target
namespaces, SHALL refuse ownership collision, SHALL preserve ambient decoys и
SHALL bounded-clean только доказанно принадлежащие ему artifacts при success и
failure. Setup/environment failure MUST быть отличим от RED assertion и
regression failure; secrets и production identifiers MUST NOT попадать в output.

#### Scenario: Two clean executions are deterministic
- **WHEN** verifier дважды запускается на clean private fixtures
- **THEN** normalized observable transcript и facts совпадают
- **AND** после каждого run отсутствуют verifier-owned artifacts, а decoys целы

#### Scenario: Fixture setup cannot masquerade as behavior
- **WHEN** source/target MariaDB, privileges, manifest или fixture prerequisites
  недоступны
- **THEN** verifier сообщает setup failure вместо behavioral pass/fail
- **AND** выполняет bounded cleanup уже созданных owned artifacts

### Requirement: Observation does not approve unknown product semantics

Characterization MUST обозначать результат как PILOT_ONLY и MUST NOT утверждать
target semantics для concurrent runs, missing/present transitions, coexistence
precedence, refresh, quarantine resolution/retention, authorization/audit,
consumer integrity, production-data cutover или premium meaning.

#### Scenario: Characterization is used by schema ownership work
- **WHEN** следующий migration slice использует oracle как regression evidence
- **THEN** он может сохранять покрытые serial data outcomes
- **AND** любое excluded behavior требует отдельной approved executable spec
