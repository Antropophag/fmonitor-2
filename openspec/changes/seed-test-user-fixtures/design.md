## Context

См. `proposal.md`,
`docs/operations/test-user-data-reset-decision.md` и capability spec. Текущий
Compose bootstrap смешивает schema, imports, identity и optional fixtures;
обычный restart может повторять setup mutation. Canonical schema chain и
`separate-pilot-generation-metadata` ещё не landed, поэтому implementation этой
change остаётся `BLOCKED_PREDECESSORS`.

## Goals / Non-Goals

**Goals:**

- Один setup owner для versioned fictional seed и semantic receipt.
- Seed-once на пустой generation, validation-only restart и exact recreate после
  ownership-checked reset.
- Минимальный dataset для login, role-specific projections, golden journey и
  representative rejection inputs без production source.
- Machine-checkable no-personal-data/no-secret/no-runtime-seed boundaries.

**Non-Goals:**

- Встроить seed в production migrations или domain commands.
- Создать второй standalone TEST-USER contour.
- Зафиксировать production credentials/backup/scaling или legacy cutover.
- Предзаполнить completed inspection, OTIZ acceptance/payment либо исторические
  evidence facts.
- Добавить новую domain logic в `rapid-pilot`.

## Decisions

1. **Owner — отдельный fixture initializer вне `app/PilotEnvironment`.**
   `PilotEnvironment` владеет generation lock, validated identity, opaque
   prerequisite receipts и publication. Односторонняя orchestration вызывает
   initializer, который может писать non-domain identity/workforce/object-source
   setup facts через узкие ports и создавать domain facts только публичными
   `InstallationProcess` commands. Private process persistence adapters,
   HTTP views, rapid-pilot domain classes и production clients запрещены.

2. **Checked-in manifest хранит semantics, не credentials.** Literal fictional
   identities используют reserved `.invalid` email domain и выделенные ID ranges.
   Semantic SHA-256 вычисляется из canonical ordered manifest bytes. Password
   поступает через Compose secret/env boundary, hash имеет random salt и поэтому
   исключён из reproducible fingerprint; readiness проверяет credential presence
   и успешный public login, а не byte equality password hash.

3. **External/config facts seed-ятся setup ports; domain history — только public
   application seam.** Identity/RBAC, workforce catalog и fictional legacy-object
   inputs являются setup/integration facts. Если exact Gate 1 потребует заранее
   сформированный process state, он создаётся только утверждёнными
   `InstallationProcess` commands с fixed clock/actor; прямой INSERT process facts
   запрещён. Минимальный recommended manifest оставляет golden object в состоянии
   до первого распоряжения.

4. **Разделённое владение receipt.** Fixture initializer владеет seed version,
   semantic fingerprint и validation. `PilotEnvironment` не интерпретирует эти
   fields: он сохраняет opaque versioned prerequisite envelope и включает его
   hash в readiness proof. Extension point сначала должна land в
   `separate-pilot-generation-metadata`; иначе эта change объявляет explicit
   generation capability delta до Gate 1. Domain seed ledger не создаётся.

5. **Preflight-all, затем seed transaction groups.** Owner сначала проверяет
   target emptiness/compatibility и external decoys, затем применяет ordered
   groups. MariaDB implicit commits canonical DDL не участвуют: migrations уже
   завершены. Любая mid-seed failure оставляет generation incomplete; automatic
   merge/repair запрещён, recovery только доказывает exact completed receipt либо
   требует explicit reset.

6. **Reset переиспользует Compose ownership proof, но не seed owner.** `make
   reset` удаляет whole owned disposable resources через отдельный operator
   adapter. Seed не выполняет DELETE/TRUNCATE/DROP. Это сохраняет ясную границу
   restart versus recreate.

7. **Architecture ratchet.** Добавить проверки: fixture manifest не содержит
   запрещённых domains/source literals/secrets; production/runtime code не
   вызывает seed; `rapid-pilot` не получает новую fixture/domain ownership;
   canonical migrations не seed-ят rows. Existing debt baseline не расширяется.

## Risks / Trade-offs

- [Exact identity/access schema ещё меняется] → держать role/table literals вне
  planning spec и зафиксировать их в executable Gate 1 после predecessor landing.
- [Слишком большой fixture превращается в вторую demo-систему] → минимальный
  manifest и independent review каждого обязательного row/scenario.
- [Random password hash ломает reproducibility] → fingerprint semantics без hash,
  secret-only input и public login proof.
- [Restart drift оставляет contour unavailable] → fail closed без repair;
  operator выбирает diagnosis или ownership-checked reset.
- [Synthetic objects маскируют integration gaps] → runbook явно маркирует
  fictional contour; production import/cutover получает отдельную rehearsal.
- [Reset может удалить чужое] → exact project/resource ownership proof и
  adversarial decoy tests до любой destructive implementation.

## Migration Plan

1. Дождаться ordered frontier: canonical workforce runner v5 → identity/access
   → checklist-template → inspection-evidence → inspection-planning →
   classification-provenance → object-detail-snapshot → generation-metadata
   opaque receipt extension; fresh-проверкой зафиксировать exact versions и
   setup order. Installation-completion, premium, migrated-evidence, quarantine
   и legacy-active исключены.
2. Создать executable `TEST-USER-FIXTURE-SEED-001` с literal manifest,
   independent fingerprints, secret/redaction contract, public seams и reset
   target matrix; получить owner approval.
3. Свежий RED-author доказывает missing seed-once/restart/reset behavior; другой
   свежий reviewer утверждает тест до production changes.
4. Реализовать minimal setup owner/adapter, не меняя domain semantics; сделать
   reviewed RED и relevant login/golden/rejection cases GREEN.
5. Выполнить fresh create, state-changing journey, restart preservation,
   ownership-checked reset/recreate, no-source/no-secret, architecture и full
   regression; получить свежий независимый code review.

Rollback до publication — code-only. После использования fixture generation не
repair-ится downgrade-ом: сохранить её для диагностики либо выполнить явно
подтверждённый local reset; production data никогда не является rollback target.
