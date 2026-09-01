## Why

Compose `make up` выбран единственным TEST-USER contour, но fresh environment
пока не имеет утверждённого минимального fictional dataset, который позволяет
войти под тестовыми ролями и воспроизводимо пройти основной prepare → register →
open journey без production source и персональных данных. Owner утвердил
synthetic/native policy и state-preserving restart с отдельным явным reset.

## What Changes

- Добавить versioned setup-only seed contract для детерминированных fictional
  users/roles/capabilities, workforce candidates и нескольких installation cases,
  достаточных для golden journey и явных rejection states.
- Применять seed только при создании пустой owned Compose generation; обычный
  `make up`/restart валидирует exact seed identity и сохраняет последующие
  пользовательские действия без reseed или overwrite.
- Оставить `make reset` единственным destructive operator seam: после
  ownership-checked удаления новая generation получает тот же exact named seed
  version, но reset не становится production migration или HTTP behavior.
- Запретить production/legacy source reads, реальные персональные данные,
  production documents/secrets и вывод fixture literals из соседнего legacy
  checkout.
- Отделить fixture seed от canonical schema migrations и generation metadata:
  migrations создают пустую schema, отдельный fixture initializer создаёт
  утверждённые fictional rows, а generation owner публикует лишь opaque
  versioned prerequisite-receipt envelope.
- Не предзаполнять финансовые решения, выплаты, завершение или исторические
  доказательства; точный минимальный scenario catalogue и независимые expected
  fingerprints фиксируются в executable Gate 1 до RED.

Behavior slice: `TEST-USER-FIXTURE-SEED-001`. Actor — локальный Compose setup
operator. Source oracle — approved pilot golden journey, role/capability
contracts и literal fictional Gate 1 manifest; production/legacy systems не
являются источниками. Target public seam — `make up`, делегирующий versioned
setup seed после canonical migrations и до readiness publication. Release value
— воспроизводимый первый вход и основной TEST-USER сценарий без реальных данных,
с сохранением действий на restart и точным возвратом после явного reset.

Non-goals: production data migration, sanitisation, legacy-active cutover,
runtime/domain self-seeding, изменение process semantics, автоматический reset,
пароли/секреты в repository, массовый persistence redesign и новая domain logic
в `rapid-pilot`.

## Capabilities

### New Capabilities

- `operations/test-user-fixture-seed`: versioned fictional seed, его безопасное
  применение на пустой Compose generation, restart preservation, readiness
  fingerprint и ownership-checked reset/recreate contract.

### Modified Capabilities

Нет.

## Impact

- Compose setup orchestration, future `app/PilotEnvironment` opaque prerequisite
  receipt registry и отдельный fixture initializer.
- Versioned private fixture manifest/fingerprint без credentials и персональных
  данных.
- Identity/access, workforce, legacy-object adapter fixtures и canonical process
  seams только как уже утверждённые schema/application consumers.
- TEST-USER runbook, fresh lifecycle verifier и architecture/no-production-source
  ratchet.
- Implementation остаётся `BLOCKED_PREDECESSORS`: canonical runner v5 →
  identity/access → checklist-template → inspection-evidence →
  inspection-planning → classification-provenance → object-detail-snapshot →
  generation-metadata opaque prerequisite-receipt extension должны фактически
  land. Installation-completion, premium, migrated-evidence,
  migration-quarantine и legacy-active provenance исключены, пока literal
  Gate 1 не начнёт их читать.
