## Purpose

Определяет воспроизводимый fictional dataset для первого TEST-USER Compose
contour, который позволяет войти под утверждёнными ролями и пройти основной
pilot journey без production sources, реальных персональных данных и скрытого
перезаписывания состояния при restart.

## ADDED Requirements

### Requirement: Seed имеет точную версию и fictional manifest

Setup SHALL применять один утверждённый `TEST-USER-FIXTURE-SEED-001` manifest с
literal version, ordered identities, roles/capabilities, workforce candidates,
installation-object inputs и independently fixed semantic fingerprint. Все
человеко-подобные данные MUST быть явно fictional; email MUST использовать
reserved non-deliverable domain, а IDs MUST принадлежать выделенному fixture
range. Manifest MUST NOT содержать password, password hash, invitation token,
production identifier, document или source credential.

#### Scenario: Exact fictional manifest
- **WHEN** Gate 1 verifier читает versioned seed input
- **THEN** каждый обязательный literal и semantic fingerprint совпадает с
  independently approved manifest
- **AND** no-personal-data verifier не находит production/legacy identifiers,
  deliverable email domains, secrets или external-source payloads

#### Scenario: Unknown seed version
- **WHEN** setup получает absent, malformed или неутверждённую fixture version
- **THEN** `make up` завершается стабильной setup failure до fixture mutation и
  до readiness publication

### Requirement: Минимальный dataset покрывает роли и journey

Manifest SHALL содержать минимальный набор fictional actors для сотрудника ФКР,
инженера строительного контроля, руководителя и системного администратора;
точные local RBAC roles и capabilities SHALL следовать фактически landed
identity/access contract. Workforce inputs SHALL включать не менее двух
трудоустроенных кандидатов и одного явно уволенного rejection candidate.
Installation-object inputs SHALL включать несколько fictional объектов,
достаточных для одного clean prepare → register → open journey и отдельных
observable rejection states, без заранее созданного распоряжения, открытия,
inspection, completion, premium decision или payment fact.

#### Scenario: Первый вход сотрудника ФКР
- **WHEN** Compose generation опубликована ready и operator передаёт bootstrap
  secret через утверждённый secret input
- **THEN** fictional ФКР actor может пройти штатный login seam
- **AND** получает только утверждённые process capabilities без superuser bypass

#### Scenario: Golden journey начинается с основания
- **WHEN** ФКР открывает seeded eligible installation object
- **THEN** public projection показывает состояние «Требуется распоряжение» и
  действие подготовки
- **AND** последующие process facts могут появиться только через публичные
  application commands, а не как прямые seed rows

#### Scenario: Rejection fixtures различимы
- **WHEN** verifier читает seeded workforce/object inputs через публичные
  directories/projections
- **THEN** employed candidates доступны для выбора, dismissed candidate даёт
  утверждённую кадровую причину отказа, а blocked object даёт отдельно
  утверждённую object-precondition причину

### Requirement: Seed применяется только к пустой owned generation

`make up` SHALL применять seed после успешных canonical migrations и
identity/config prerequisites, но до generation readiness publication. Seed
MUST сначала доказать exact Compose generation ownership, пустое seed state и
отсутствие несовместимых target rows. Он MUST fail closed на partial, foreign или
ambiguous state и MUST NOT repair, merge, truncate или overwrite его.

#### Scenario: Fresh owned generation
- **WHEN** `make up` создаёт новую пустую ownership-proved generation
- **THEN** отдельный fixture initializer создаёт exact fictional inputs один раз,
  валидирует semantic receipt и передаёт generation owner opaque versioned
  prerequisite envelope до readiness proof

#### Scenario: Partial или conflicting fixture state
- **WHEN** хотя бы один expected target отсутствует после partial seed либо
  существующий target отличается от exact semantic manifest
- **THEN** setup завершается deterministic conflict до новых fixture/domain writes
- **AND** сохраняет existing rows, counters, artifacts и ambient objects для
  явной recovery/reset диагностики

#### Scenario: Два creator одновременно
- **WHEN** два реальных `make up` creator конкурируют за одну generation
- **THEN** generation lock допускает ровно одного seed owner
- **AND** второй получает active/duplicate либо stable conflict без duplicate rows

### Requirement: Обычный restart никогда не reseed-ит состояние

При already-ready generation `make up`/restart SHALL через fixture initializer
валидировать seed version, semantic receipt и required fixture identities, а
generation owner SHALL проверять только opaque envelope hash. Restart MUST NOT повторять
INSERT/UPDATE, восстанавливать удалённые rows, вращать credentials, сбрасывать
counters или изменять domain facts и artifacts, созданные тестовыми пользователями.

#### Scenario: State-preserving restart
- **WHEN** пользователь завершил часть golden journey и operator выполняет
  stop/start или повторный `make up`
- **THEN** exact user-created process history, current state, counters и artifacts
  сохраняются byte-equivalent
- **AND** fixture seed transcript сообщает validation-only без mutation

#### Scenario: Fixture drift после readiness
- **WHEN** required fixture identity/receipt отсутствует или несовместим при restart
- **THEN** startup fail-closed до HTTP/worker readiness
- **AND** не восстанавливает fixture автоматически и не удаляет user-created state

### Requirement: Fixture initializer имеет одностороннюю setup boundary

`PilotEnvironment` SHALL владеть generation lock, validated identity, opaque
prerequisite envelopes и publication, но SHALL NOT интерпретировать fixture
semantics или вызывать domain persistence. Отдельный fixture initializer SHALL
владеть manifest/version/fingerprint и MAY писать non-domain identity,
workforce и fictional object-source setup facts через узкие setup ports; domain
facts SHALL проходить только через public `InstallationProcess` commands.

#### Scenario: Prerequisite publication
- **WHEN** fixture initializer завершил full semantic validation
- **THEN** он возвращает opaque versioned receipt envelope generation owner, который публикует его hash без доступа к fixture fields

#### Scenario: Domain fact требуется fixture
- **WHEN** literal Gate 1 требует создать process history
- **THEN** initializer вызывает approved public application command и не использует private process persistence adapter

### Requirement: Reset является отдельным ownership-checked operator действием

Fixture capability SHALL NOT предоставлять HTTP reset и SHALL NOT выполнять
destructive action из ordinary startup или production migration. Явный
`make reset` MUST до удаления перечислить exact Compose-owned DB/state/artifact
resources, доказать ownership target environment и fail closed при ambiguity.
После успешного reset следующий `make up` SHALL создать новую generation с той
же approved fixture version, но новой generation identity.

#### Scenario: Явный reset и recreate
- **WHEN** local operator вызывает `make reset` для exact ownership-proved
  TEST-USER Compose project
- **THEN** удаляются только объявленные owned resources
- **AND** следующий `make up` воспроизводит исходный fixture semantic fingerprint
  без истории прежнего test run

#### Scenario: Неоднозначная destructive цель
- **WHEN** ownership, project identity или точный resource set не доказан
- **THEN** reset завершается до deletion и перечисляет безопасную причину отказа
- **AND** foreign volumes, databases, files и ambient objects неизменны

### Requirement: Источники и секреты остаются за границей seed

Seed MUST NOT обращаться к production/legacy DB, `../fmonitor`, Bitrix, 1С,
network source или пользовательскому home-data вне exact owned state root.
Bootstrap credential MUST поступать через runtime secret configuration, не
печататься и не участвовать в reproducible semantic fingerprint. Seed SHALL
записывать только минимальный redacted setup audit: fixture version,
generation identity, semantic fingerprint, result и timestamp.

#### Scenario: External sources недоступны
- **WHEN** все production/legacy/network sources отсутствуют или содержат decoy
  credentials
- **THEN** fresh seed и readiness успешно завершаются только из checked-in
  fictional manifest и approved secret channel
- **AND** captured stdout/stderr/manifest/audit не содержат ни одного secret

#### Scenario: Runtime user пытается вызвать seed
- **WHEN** HTTP actor, worker или process command пытается применить fixture seed
- **THEN** публичного mutation seam не существует
- **AND** fixture receipt, config и domain state не меняются
