## Why

Rapid-pilot меняет отображаемый состав исполнителей завершённого пункта через
append-only `item_installers_changed`, но этот путь не имеет отдельного
детерминированного oracle-контракта. Перед переносом команды за публичный seam
`InspectionRecording::changeItemAttribution` нужно отделить наблюдаемое поведение
pilot от ещё не утверждённых target-правил коррекции, авторизации и конфликтов.

## What Changes

- Добавить строго `PILOT_ONLY` characterization текущей HTTP-команды
  `item_installers_changed` через production-composed session/CSRF seam.
- Зафиксировать принятые raw facts и projection после исходного
  `item_completed` и последующей коррекции, включая неизменность исходной
  операции и кадровые snapshots коррекции.
- Зафиксировать фактические replay, stale/ahead и конкурентные результаты как
  migration contrast, не превращая наблюдаемые дефекты в target requirements.
- Зафиксировать точные zero-mutation rejection boundaries и воспроизводимый
  private-fixture/cleanup контракт.
- Провести characterization через обязательные Gate 1 → RED → независимый test
  review → GREEN → независимый code review; не менять production behavior.
- `NEEDS_GRILL`: target authorization, смысл supersession/display projection,
  payload-aware idempotency, strict revision и winner policy остаются в
  `INSPECTION-ATTRIBUTION-CORRECT-001` и не блокируют этот oracle slice.

## Capabilities

### New Capabilities

- `verification/inspection-attribution-correction-characterization`:
  воспроизводимый executable oracle текущей коррекции атрибуции пункта.

### Modified Capabilities

Нет.

## Impact

- Source oracle: `app/PilotHttp/ChecklistSync.php`, production HTTP routing and
  current rapid-pilot checklist UI.
- Verification: новый private fixture/verifier и регистрация в canonical
  characterization stage только после reviewed GREEN.
- Target public seam: будущий
  `InspectionRecording::changeItemAttribution`; этот change его не реализует.
- Production code/schema/API не изменяются; runtime DDL остаётся отдельным
  `canonicalize-inspection-evidence-schema` debt.
- Release value: после owner approval target slice получит независимый contrast
  для append-only history, projection, idempotency и concurrency.
