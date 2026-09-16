## Purpose

Не допускать intended-RED test candidate к независимому Gate 3, пока bounded exact-source evidence не докажет исполнимость применимой fixture/infrastructure части за ранней RED-границей.

## ADDED Requirements

### Requirement: Applicable test declares a bounded reachability control
Для acceptance test, где настоящий `INTENDED_RED` останавливает исполнение до материальной fixture/infrastructure части, verification input SHALL явно объявлять bounded reachability control, связанный с тем же acceptance и mapped test command. Tests без недостигнутого remainder SHALL сохранять текущий контракт без обязательного probe; неизвестная применимость MUST fail closed при подготовке Gate 3, если declaration требует reachability.

#### Scenario: Early RED makes remainder control applicable
- **WHEN** mapped acceptance test корректно падает на отсутствующем product behavior до выполнения fixture, helper, provider, CSRF/setup либо post-fork DB remainder
- **THEN** Gate-3 contract требует явный bounded reachability control для этого acceptance

#### Scenario: No unreachable remainder
- **WHEN** intended-RED assertion находится после всей материальной fixture/infrastructure части или acceptance declaration не включает reachability requirement
- **THEN** существующий intended-RED evidence contract остаётся достаточным и safeguard не требует массовой instrumentation всех tests

### Requirement: Control proves the declared boundary without product GREEN
Control SHALL детерминированно обойти только заявленную intended-RED product assertion, продолжить тот же test path до именованной reachability boundary и завершиться отдельным успешным runner outcome. Control MUST NOT считать GREEN product behavior prerequisite, выполнять destructive/product mutations, обращаться к production systems или использовать arbitrary crash/absence of crash как oracle.

#### Scenario: Healthy intended RED with reachable remainder
- **WHEN** обычный запуск того же exact-source test возвращает `INTENDED_RED` по отсутствующему behavior, а bounded control достигает заявленной remainder boundary и возвращает явный healthy outcome
- **THEN** оба evidence record сохраняют разные outcomes, общий acceptance/command identity и допускают test candidate к Gate-3 preparation

#### Scenario: Product behavior remains absent
- **WHEN** control выполняется при всё ещё отсутствующем product behavior
- **THEN** control может доказать fixture/remainder reachability без подмены обычного `INTENDED_RED` на GREEN

#### Scenario: Destructive control is rejected
- **WHEN** declaration или control требует product mutation, production endpoint/system либо снятие иных acceptance assertions для доказательства reachability
- **THEN** safeguard MUST fail closed и сообщить, что bounded control невалиден

### Requirement: Fixture defects block before Gate 3
Gate-3 preparation SHALL отклонять applicable candidate, если control обнаруживает fixture/infrastructure defect или не предоставляет явное healthy reachability evidence. `SETUP_FAILURE` MUST NOT классифицироваться как `INTENDED_RED`; exception, signal, timeout, malformed control marker и arbitrary nonzero после RED MUST NOT доказывать reachability.

#### Scenario: Missing fixture table or column
- **WHEN** control за intended-RED boundary обращается к отсутствующей fixture table или column
- **THEN** runner сохраняет non-reachability failure и Gate-3 preparation отклоняет candidate

#### Scenario: Wrong helper argument
- **WHEN** control достигает helper call с неверным argument type, count или value shape
- **THEN** failure блокирует Gate 3 независимо от корректного обычного `INTENDED_RED`

#### Scenario: Malformed data provider or index
- **WHEN** control достигает malformed provider row, отсутствующего index/key или неверной variant shape
- **THEN** failure блокирует Gate 3 и не считается reachability evidence

#### Scenario: Invalid CSRF or setup source
- **WHEN** control получает CSRF/setup value не из требуемого public/fixture source либо не может получить валидный source
- **THEN** failure блокирует Gate 3 до product implementation

#### Scenario: Broken post-fork database fixture
- **WHEN** child/post-fork path не может открыть, восстановить или прочитать изолированную DB fixture
- **THEN** failure блокирует Gate 3 и сохраняется как infrastructure outcome

### Requirement: Evidence is exact-source and identity bound
Reachability evidence SHALL использовать существующий retained runner record и MUST быть связан с текущими source/executable-source, environment, acceptance id, command id и declared boundary. Gate-3 preparation MUST отклонять отсутствующее, stale, чужое, повторно использованное или не соответствующее declaration evidence и MUST сохранять raw command verdict/diagnostic.

#### Scenario: Evidence belongs to another source or command
- **WHEN** healthy control record создан для другого source, environment, acceptance, command или boundary
- **THEN** Gate-3 preparation отклоняет record без reinterpretation

#### Scenario: Arbitrary marker after RED
- **WHEN** raw output содержит reachability marker, но structured runner outcome не подтверждает успешное достижение declared boundary
- **THEN** marker не допускает candidate к Gate 3

### Requirement: Independent Gate 3 remains authoritative
Safeguard SHALL быть prerequisite подготовки reviewer package, а не новым Gate или approval. Успешная reachability проверка MUST NOT выставлять `APPROVED`, ослаблять sensitivity/traceability review или заменять независимое решение Gate 3.

#### Scenario: Prepared healthy candidate
- **WHEN** intended-RED и reachability evidence полностью валидны
- **THEN** reviewer package имеет `NOT_REVIEWED`, содержит оба evidence и требует независимый Gate-3 verdict

#### Scenario: Repeated or concurrent controls
- **WHEN** один control повторяется или несколько controls выполняются параллельно
- **THEN** каждый retained record классифицируется по собственному source/identity/boundary без заимствования marker или outcome другого запуска и без product mutations
