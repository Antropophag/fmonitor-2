## Purpose

Фиксирует canonical least-privilege local actors для golden journey и отделяет authorization integration от independently owned combined-PDF artifact contract.

## ADDED Requirements

### Requirement: Migrated object-list steps используют canonical actors
E2E actor `18` (ФКР) SHALL быть fictional active/activated local user с active
assigned role и exact `objects.read` для `GET /pilot/objects`. Actor `19`
(reader) SHALL оставаться legacy-only без local role/grant в negative branch.
Actor ID SHALL поступать из local authentication boundary;
legacy identity MUST NOT давать fallback authority. Другие journey routes не
получают local-RBAC requirements этим slice.

#### Scenario: FKR object-list step проходит exact grant
- **WHEN** FKR actor 18 выполняет `GET /pilot/objects`
- **THEN** list route допускается exact `objects.read`, после чего journey продолжает existing prepare/register/open contracts

#### Scenario: Legacy-only reader не проходит list
- **WHEN** reader присутствует только в legacy users/roles без local grant
- **THEN** `GET /pilot/objects` отклонён generic 403 до list handler read

### Requirement: E2E revoke и repeat воспроизводимы
Отдельная isolated revoke fixture SHALL committed-удалить exact actor-18
`role → objects.read` row перед новым invocation; этот revoke SHALL закрывать
следующий list invocation. Main journey fixture MUST NOT мутироваться;
repeat неизменившегося fixture SHALL давать тот же admission и facts. Fixture
MUST сохранять audit/history и очищать task-owned credentials, sessions, DB и
artifacts после success/failure.

#### Scenario: Revoke между object-list steps
- **WHEN** fixture admin удаляет exact role-permission committed после одного успешного list GET
- **THEN** следующий list GET отклонён, list handler не выполняется и ранее записанные journey facts неизменны
- **AND** authorization check не создаёт audit; audit fixture-admin mutation вне scope

#### Scenario: Main grant сохранён для downstream journey
- **WHEN** isolated revoke branch завершена и main journey достигает artifact boundary
- **THEN** main actor18 exact grant/roles byte-equivalent initial fixture и object-list admission остаётся 200

### Requirement: Combined PDF остаётся отдельной зависимостью
Этот fixture slice MUST NOT возвращать два legacy HTML artifact или ослаблять
combined-PDF assertions. Artifact failure SHALL быть классифицирован как
dependency `PILOT-E2E-COMBINED-PDF-001` после успешной RBAC admission.

#### Scenario: Authorization проходит до artifact contract
- **WHEN** object-list RBAC admission прошёл и journey достигает artifact проверки
- **THEN** authorization не маскирует результат, а combined-PDF outcome проверяется отдельным approved contract

### Requirement: Snapshot boundary отделяет authorization reads от prepare mutation
Каждый object-list authorization invocation SHALL иметь full byte-equivalent
DB/process/storage snapshot непосредственно до и после authorization
read, кроме exact fixture-admin revoke в isolated branch. На artifact
boundary после canonical prepare journey verifier MUST допускать
только approved assignment-order, append-only event, artifact metadata и owned
artifact bytes delta. Exact local users, roles, assignments, permissions, RBAC
schemas и authority-related counters MUST оставаться byte-equivalent.

#### Scenario: List authorization не мутирует состояние
- **WHEN** actor 18 или denied actor выполняет object-list authorization invocation
- **THEN** full DB/process/storage snapshot сразу после invocation byte-equivalent snapshot сразу до него

#### Scenario: Prepare даёт только approved delta
- **WHEN** admitted main journey выполнил prepare и достиг artifact boundary
- **THEN** с pre-prepare snapshot различаются только approved assignment-order, event, artifact metadata и owned artifact bytes
- **AND** exact RBAC facts, schemas и authority-related counters byte-equivalent
