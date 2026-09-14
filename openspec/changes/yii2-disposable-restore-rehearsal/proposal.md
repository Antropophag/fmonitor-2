## Why

> **Owner decision / supersession — 2026-09-14.** Restore/rollback rehearsal superseded как acceptance gate для closure issue #76 решением о clean Yii2 stand cutover. Исторические implementation, evidence, failed `OUTCOME_UNKNOWN` и retained lease сохраняются; reconciliation и rollback не выполнять. Production-shaped offline backup/restore capability этим действием не удаляется. Ниже сохранён исходный исторический scope.

После merge PR #124 новый Yii2 `StandRestoreApplication` сохраняет fail-closed protocol, bundle validation, lease/ledger/replay и подтверждённый outcome, но выполняет эффекты только через test fixture и не может восстановить настоящий disposable runtime. Issue #76 требует до production cutover доказать реальный backup/restore и rollback на одноразовом stand с MariaDB, persistent artifacts/sessions, restart/readiness и golden smoke flows.

## What Changes

- Добавить к существующему `StandRestoreApplication` минимальный production-shaped driver/ports для MariaDB import, persistent artifact/session targets и post-restore runtime verification; не создавать второй restore application protocol.
- Заменить test-mode как единственный путь эффектов явной authorization-конфигурацией и cryptographically/exactly attest-нутым target manifest: project name сам по себе никогда не разрешает destructive operation.
- Перед любым эффектом подтвердить pinned image/source, compose topology, database and volume identities, credentials source, disposable marker и отсутствие production identity; после эффектов повторно подтвердить target identity.
- Интегрировать явные stop/clear-or-recreate/restore/restart/readiness boundaries, сохраняя `OUTCOME_UNKNOWN` при недоказуемом результате и append-only operation evidence.
- Выполнить только на disposable stand operational rehearsal: known state → новый stand-backup → independent bundle verify → разрешённое уничтожение target → новый stand-restore → restart/readiness → DB/schema/history/AUTO_INCREMENT, artifacts/modes, sessions, jobs/outbox/recovery и golden smoke assertions.
- Выполнить rollback rehearsal: определить failure predicate, вернуть target к известному backup и повторно доказать restart/readiness и существенные факты.
- Сохранить `RuntimeRecovery`, пока executable inventory подтверждает его неперенесённые legacy responsibilities; retirement вынести в следующий отдельный срез только после доказанного замещения.
- Не выполнять production deployment/cutover, не использовать production credentials/targets, не менять user workflows или harness policy и не дробить `StandRestoreApplication` ради размера.

## Capabilities

### New Capabilities

- `operations/yii2-disposable-restore-rehearsal`: явным образом авторизованный production-shaped restore и rollback rehearsal существующего Yii2 backup/restore contour на attest-нутом disposable stand.

### Modified Capabilities

Нет. Срез расширяет реализацию ещё не архивированного `yii2-stand-restore-control`, но не ослабляет и не заменяет его требования; совместимость с его полным контрактом является входным инвариантом новой capability.

## Impact

- Actor: уполномоченный operator, выполняющий только заранее подготовленный disposable rehearsal package.
- Source oracle: issue #76, merge PR #124 `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`, существующие stand-backup/stand-restore contracts, runtime compose/readiness и golden behavior suites.
- Target public seam: `php bin/yii stand-restore/run --interactive=0` через существующий `StandRestoreApplication`; lifecycle orchestration использует explicit disposable manifest/package и не становится вторым restore owner.
- Release value: операционное доказательство recoverability и rollback до отдельного production cutover decision.
- Affected areas: `app/RuntimeRestore`, Yii2 console composition, disposable compose/runbook tooling, focused deployment tests, verification inventory и external rehearsal evidence.
- Non-goals: production deployment/cutover, production secrets или destructive production target, implicit discovery по project name, изменение application protocol/user workflow/harness policy, cleanup/decomposition `StandRestoreApplication`, удаление `RuntimeRecovery` без полного замещения.
