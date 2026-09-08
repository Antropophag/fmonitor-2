## Why

Canonical runner обнаруживает только InstallationProcess;22 native composition/HTTP
tests и Node upload client остаются вне make verify. Release engineer должен видеть
их в общей проверке и получать failure при любом отказе.

## What Changes

- VERIFICATION-NATIVE-SUITES-001: расширить discovery и запуск через существующий
  public CLI `tools/verification/run.sh`, добавить read-only список unit/db.
- Три in-memory selection command tests и Node client — unit; остальные19 native
  composition/template/HTTP PHP tests — db. Все прежние семейства сохраняются.
- Продолжать выполнение остальных файлов при отказе одного, вернуть ненулевой итог.

## Capabilities

### New Capabilities
- `delivery/native-verification-suites`: полный состав проверок в canonical runner.

### Modified Capabilities

## Impact

Только tooling/tests и evidence. Oracle: существующие approved tests и task5
handoff2026-09-06-2350Z. Нет product/domain/schema/remote/deployment изменений;
не чинить проверки через skips/allowed failures. Browser original QA остаётся отдельно.
