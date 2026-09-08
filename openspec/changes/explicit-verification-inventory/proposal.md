## Why

Первый срез issue #25: разработчик не может надёжно определить состав проверок без чтения их исходников. Поиск строк уже ломал Linux CI. Явный каталог и длительности дают основу для ускорения обратной связи.

## What Changes

- Публичный seam: `bash tools/verification/run.sh list unit|db|characterization|e2e` и запуск этих групп.
- Каталог TSV задаёт группу, интерпретатор и путь; неизвестный тест приводит к SETUP_FAILURE до исполнения.
- Длительность и exit code каждого top-level теста видны в выводе.
- Состав существующих групп сохраняется; E2E-дубли, CI-матрица и разделение unit/governance доставляются следующими срезами #25.

## Capabilities

### New Capabilities
- `verification/inventory`: явный состав и наблюдаемость проверок.

## Impact

Только tools/verification и проверки runner. Продукт, БД, стенд, branch protection и Quality Graph не меняются. Oracle — списки runner на d5f8f2d и действующий VERIFICATION-NATIVE-SUITES-001. Удаление дублей требует отдельной проверки композиции.
