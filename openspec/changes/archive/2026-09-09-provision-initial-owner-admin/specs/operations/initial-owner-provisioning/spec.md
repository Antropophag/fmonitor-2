## Purpose

Задаёт безопасное явное создание первого owner-admin на чистой migrated production
database без demo bootstrap и повышения существующих пользователей.

## ADDED Requirements

### Requirement: CLI принимает один owner email и внешний password secret
Command SHALL принимать exact `--email <value>`, обязательные direct DB/prefix values
и непустой `FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD`. Email SHALL использовать текущую
lowercase/trim и exact `@shlz.ru` policy. Secret/email/config SHALL NOT появляться в
stdout/stderr. Alias/default/demo manifest SHALL NOT использоваться.

#### Scenario: Некорректный input
- **WHEN** argument/config отсутствует, лишний или invalid
- **THEN** CLI возвращает exit 64 и `{"ok":false,"reason":"CONFIGURATION_INVALID"}` без DB mutation

### Requirement: Clean database получает exact existing roles
После canonical schema readiness application owner SHALL одной transaction seed-ить
  текущий `LocalRoleCatalog`, создать одного active user с `full_name`, равным
  normalized email, и Argon2id credential, затем
назначить только `user` и `superadministrator` с `origin=bootstrap` и null actor.

#### Scenario: Clean provisioning
- **WHEN** identity users отсутствуют и command получает valid inputs
- **THEN** создаётся один active user, exact две grants и CLI возвращает exit 0 `{"ok":true,"status":"created"}`

#### Scenario: Failure внутри transaction
- **WHEN** ошибка возникает после role/user/credential/first-grant write до commit
- **THEN** все изменения rollback и clean preimage сохраняется

### Requirement: Replay безопасен, existing state отвергается
Exact единственный user с requested email, empty phone, session_version1, active
credential, password_verify, обеими exact bootstrap grants и пустой auxiliary
identity history SHALL дать byte-zero-mutation replay. Любое другое
existing состояние SHALL быть отвергнуто без seed/repair/promotion.

#### Scenario: Exact replay
- **WHEN** тот же email/password повторяется после created
- **THEN** exit 0/status `already_provisioned`, все identity bytes unchanged

#### Scenario: Existing conflict
- **WHEN** любая identity table содержит partial state либо существует иной, invited, blocked, different-password или multiple-user state
- **THEN** exit 65/reason `IDENTITY_NOT_EMPTY`, users/roles/credentials/grants/invitations/events unchanged

### Requirement: Operation отделена от migration и startup
CLI SHALL проверить canonical readiness без DDL, работать под explicit provisioning
authority, получить per-database/prefix lock timeout 0 и всегда release. Web/FPM,
Compose startup и migration runner SHALL NOT вызывать provisioning.

#### Scenario: Schema отсутствует
- **WHEN** canonical identity schema не готова
- **THEN** exit 70/reason `SCHEMA_NOT_READY` без DDL/repair

#### Scenario: Concurrent attempts
- **WHEN** два operators одновременно provision clean database
- **THEN** один создаёт state; второй получает busy либо после release exact replay, никогда второй user/grants
