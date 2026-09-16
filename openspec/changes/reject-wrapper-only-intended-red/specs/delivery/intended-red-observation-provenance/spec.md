## Purpose

Гарантировать, что delivery harness признаёт intended RED только по фактически наблюдённому acceptance behavior, а не по тексту ожидаемого marker в metadata или wrapper diagnostics.

## ADDED Requirements

### Requirement: INTENDED_RED требует разрешённого observation provenance
Публичный runner SHALL классифицировать nonzero acceptance execution как `INTENDED_RED` только когда launcher/setup успешно достиг теста, ожидаемый marker наблюдён в разрешённом oracle channel этого теста и marker относится к заявленному acceptance behavior. Marker, присутствующий только в argv, command echo, serialized command metadata, wrapper diagnostic, environment dump или expected-value echo, MUST NOT допускать `INTENDED_RED`.

#### Scenario: Marker только в argv или command echo
- **WHEN** acceptance command завершается nonzero, а marker присутствует только в argv либо echo команды
- **THEN** outcome равен `REGRESSION_FAILURE`, а не `INTENDED_RED`

#### Scenario: Marker только в wrapper metadata
- **WHEN** wrapper сериализует marker в diagnostic metadata, но child oracle его не наблюдал
- **THEN** outcome не равен `INTENDED_RED`

#### Scenario: Legitimate oracle marker
- **WHEN** launcher/setup успешно запускает acceptance test, а test oracle выдаёт canonical expected marker и завершается nonzero
- **THEN** outcome равен `INTENDED_RED`

#### Scenario: Metadata и legitimate oracle одновременно
- **WHEN** marker присутствует и в metadata, и в разрешённом child oracle channel
- **THEN** legitimate oracle provenance допускает `INTENDED_RED`

### Requirement: Setup и regression verdict имеют точный приоритет
Runner MUST сохранять `SETUP_FAILURE`, `REGRESSION_FAILURE`, `INTENDED_RED` и `GREEN` как различимые outcomes. Setup/launcher failure до test behavior SHALL давать `SETUP_FAILURE` либо существующий точный non-RED outcome; unrelated assertion SHALL оставаться `REGRESSION_FAILURE`; exit zero SHALL давать `GREEN`. Arbitrary nonzero exit MUST NOT считаться intended RED.

#### Scenario: Launcher не достиг behavior
- **WHEN** launcher или setup завершается до запуска test behavior
- **THEN** outcome равен `SETUP_FAILURE` или существующему точному non-RED outcome и не равен `INTENDED_RED`

#### Scenario: Unrelated assertion
- **WHEN** test execution достигнут, но nonzero вызван assertion без expected acceptance marker
- **THEN** outcome равен `REGRESSION_FAILURE`

#### Scenario: Green behavior
- **WHEN** acceptance command завершается zero без control failure marker
- **THEN** outcome равен `GREEN`

### Requirement: Evidence сохраняет provenance и raw verdict
Публичный runner SHALL сохранять raw child exit, command verdict, stdout/stderr и diagnostic metadata после classification. Machine-readable result публичного prepare/run lifecycle MUST точно сообщать outcome и не маскировать raw command verdict.

#### Scenario: Failure evidence retained
- **WHEN** setup, regression или intended RED классифицирован
- **THEN** retained record содержит исходный exit code, raw command verdict и пути к полным stdout/stderr

#### Scenario: Lifecycle compatibility
- **WHEN** выполняются существующие healthy intended-RED и setup-failure fixtures
- **THEN** healthy intended RED сохраняет lifecycle contract, а setup failure не становится regression или intended RED

### Requirement: Классификация не изменяет domain state
Runner SHALL оставаться read/execute tooling seam без новых product authorization, audit или persistence facts. Повторный или конкурентный запуск MUST классифицировать каждый retained record независимо по его собственным structured observations.

#### Scenario: Повторные независимые записи
- **WHEN** одинаковая команда выполняется повторно или параллельно
- **THEN** каждый запуск получает отдельный retained record и classification без переиспользования marker provenance другого запуска
