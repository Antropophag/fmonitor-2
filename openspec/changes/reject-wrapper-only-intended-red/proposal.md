## Why

T08 gap-check на `main` `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` показал ложный `INTENDED_RED`: canonical Yii command не дошёл до acceptance behavior из-за setup failure, но ожидаемый marker встретился в сериализованном argv wrapper diagnostic. Slice B для issue #123 должен закрыть только этот admission gap, чтобы Gate не принимал metadata как наблюдение продукта.

## What Changes

- `INTENDED_RED` допускается только по marker из разрешённого observation channel фактически запущенного acceptance test после успешного launcher/setup.
- Marker только в argv, command echo, serialized command metadata, wrapper diagnostic, environment dump или expected-value echo не допускает `INTENDED_RED`.
- Setup/launcher failure сохраняет точный non-RED outcome; raw command verdict, exit code и diagnostic evidence не маскируются.
- Добавляется executable matrix A–M и sensitivity fixture через настоящий публичный `harness.py run` route с wrapper.
- Healthy intended-RED и setup-failure lifecycle fixtures сохраняют прежний контракт.
- **Non-goals:** container vendor visibility (slice A), worktree identity guard (slice C), evidence redesign, product tests, FAST/T06/T03, semantic log parser, LLM, `rapid-pilot/`, merge/deploy/settings.

## Capabilities

### New Capabilities

- `delivery/intended-red-observation-provenance`: Строгая классификация intended RED по явному происхождению acceptance observation на публичном delivery harness seam.

### Modified Capabilities

Нет.

## Impact

- Actor: root/executor/reviewer delivery workflow; source oracle — T08 gap-check и существующий retained runner record.
- Public seam: `python3 tools/delivery/harness.py run ...`, включая существующий profile wrapper.
- Затрагиваются только `tools/delivery/`, bounded tests в `tests/Verification/`, нормативный tooling contract и delivery records.
- Новые runtime dependencies, product/domain state, audit/history facts и persistence не вводятся; evidence остаётся append-only во внешнем harness home.
