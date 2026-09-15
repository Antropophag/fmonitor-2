## Why

Физическое число строк стало блокировать delivery и стимулировать сжатие или искусственную декомпозицию вместо проверки ответственности модуля. В ограниченное окно техдолга размер нужно оставить заметным review-сигналом, сохранив fail-closed содержательные architecture rules.

## What Changes

- Hotspot/file-size observations становятся non-blocking advisories в human-readable и JSON output публичного checker.
- Exit status и `ok` определяются только blocking architecture errors.
- Обновление size metadata отделяется от принятия SQL/DDL/dependency/public-seam и иных meaningful exceptions.
- Существующие ownership detectors и threshold `150` сохраняются.
- Добавляется bounded executable contract и обновляется применимая guardrail documentation.

## Capabilities

### New Capabilities

- `architecture-file-size-advisory`: Контракт публичного architecture checker для разделённых blocking errors и non-blocking size advisories.

### Modified Capabilities

Нет.

## Impact

Затронуты только `tools/architecture/check.py`, его public CLI fixtures, size metadata contract, применимая architecture documentation и штатная verification registration. Production code, meaningful ownership rules, rapid-pilot runtime и unrelated baseline debt не меняются.
