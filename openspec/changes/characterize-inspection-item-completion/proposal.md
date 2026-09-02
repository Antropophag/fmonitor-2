## Why

Golden journey сейчас заканчивается после открытия работ и не доказывает ни
одного engineer mutation. Existing item-completion verifiers не покрывают real
acceptance, а pilot materially расходится с draft target по replay, stale
revision и concurrency; без executable oracle calibration RED может закрепить
неверные ожидания.

## What Changes

- Добавить compact PILOT_ONLY characterization реального production HTTP POST
  `/pilot/objects/{id}/checklist/operations` с живой session/CSRF и private
  MariaDB fixtures; raw facts проверять независимо от HTTP response.
- Доказать accepted `item_completed`, exact raw operation/installer/revision/
  template facts, projection и historical crew snapshot preservation.
- Зафиксировать как PILOT_ONLY payload-unaware same-id duplicate, принятие lower
  stale base, ahead-base conflict и current two-command same-base result через
  реальные independent connections/processes.
- Проверить компактный набор case/template/item/crew rejections с exact mutation
  boundary: required session GET PILOT_ONLY инициализирует revision row zero,
  rejected POST не создаёт operation/installer и не меняет revision; также
  deterministic transcript, namespace
  collision refusal, decoy preservation и bounded cleanup.
- Зафиксировать projection-side legacy installer backfill как отдельный
  PILOT_ONLY read mutation и обязать target planning явно сделать projection
  read-only до calibration Done.
- Не переносить current behavior в target seam и не менять production code,
  runtime DDL, HTTP authorization policy или существующий golden E2E в этом
  characterization.
- **NEEDS_GRILL:** GRILL-003 блокирует только target authorization/object scope.
  Oracle использует только fixed admitted current-engineer fixture и не выбирает
  target policy между assignment/capability alternatives.

## Capabilities

### New Capabilities

- `verification/inspection-item-completion-characterization`: deterministic
  PILOT_ONLY oracle текущих item-completion facts, replay, revision, concurrency
  и rejection boundaries.

### Modified Capabilities

Нет.

## Impact

- Stable spec `CHARACTERIZE-INSPECTION-ITEM-COMPLETION-001`, focused verifier и
  registration в canonical characterization stage.
- Actor: authenticated synthetic current engineer admitted by current pilot;
  source oracle: `PilotE2ECoordinator` HTTP exchange backed by
  `app/PilotHttp/ChecklistSync.php`; target seam candidate remains
  `InspectionRecording::completeItem` and is not implemented here.
- Tables are private fixtures matching current checklist revision/operation/
  installer/template/process/workforce/order contracts; verifier-owned DDL is
  test setup and does not approve runtime schema ownership.
- Release value: calibration target spec can explicitly replace payload-unaware
  replay and stale/concurrent acceptance rather than accidentally preserve them.
- Explicit non-goals: choosing target capability/current-assignment policy,
  exhaustive HTTP auth matrix, photos, corrections, section completion,
  template redesign, completion percentages, premium/payment and production
  data.
