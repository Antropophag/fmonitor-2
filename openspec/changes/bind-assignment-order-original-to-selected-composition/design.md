## Context

См. proposal.md. Native selection и standalone registry reader уже Gate5 APPROVED.
Original preflight и locked check пока physical-only. Новый constructor связывает
оба чтения; canonical schema activation и портал не переключаются этим пакетом.

## Goals / Non-Goals

**Goals:** один original owner, один case serialization boundary с selection,
явный stale-selection outcome с использованием existing target_not_current.
**Non-Goals:** перенос старых writers, новая storage/schema family, HTTP/PDF-template,
применение состава, открытие и изменение protected E2E.

## Decisions

- Новый явно выбранный `createForSelections` в production factory. Existing create/
  createRecoveryReady остаются physical-only; автоматического fallback по schema
  presence нет. Это изолирует fresh flow и не требует historical writer conversion.
- Shared registered source query/validators переиспользуются в read-only snapshot
  и внутри owned original transaction. Исторический reader продолжает читать все
  immutable selections; write-target adapter дополнительно проверяет currentness.
- Locked validation сначала блокирует installation case, затем читает источник и
  root. Selection уже блокирует эту же строку, поэтому replace/upload сериализуются.
- Новый внутренний lookup NOT_CURRENT и commit COMPOSITION_NOT_CURRENT переводятся
  в existing conflict/target_not_current, без нового persisted enum/schema.
- Verification constructor использует тот же binding с supplied clock и existing
  persistence observer; production не принимает fault hooks. Owner SQL остаётся в
  MariaDb adapters, DDL — только existing migrations. rapid-pilot не изменяется.

## Risks / Trade-offs

- [Preflight устарел до записи] → повторная проверка после общего case lock.
- [Новый pending скрывает старый original correction] → accepted root разрешает
  историческую composition; прежний original owner проверяет leaf и correction.
- [Отказ после private finalize] → существующий resource/lease/orphan protocol;
  ручное удаление files или обход approved audit не вводятся.

## Migration Plan

Gate1 → direct-selection/native original RED → Gate3 → minimal binding → relevant
original/reader/selection regressions + architecture → Gate5. Затем отдельная
активация canonical engines на актуальном frontier и portal integration.
