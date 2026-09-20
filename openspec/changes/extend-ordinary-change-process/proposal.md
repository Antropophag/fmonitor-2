## Why

Текущий compact lifecycle описывает только bounded correction установленного поведения, а FAST presentation — узкий server-rendered класс. Из-за этого повторяющиеся обычные изменения представления, чтения и безопасного прикладного рефакторинга получают лишний Gate 3 либо полный CI по причинам, не связанным с фактическим риском.

## What Changes

- Расширить существующий compact lifecycle на согласованные обычные исправления, небольшие функции и behavior-preserving refactoring в трёх заявленных классах.
- Разделить решение о числе reviews и решение о ширине CI: обычная задача сохраняет один final review даже при FULL CI.
- Расширить существующий FAST presentation через текущие ownership/oracle связи на связанные изменённые regression tests и consumers; при неполном mapping выбрать FULL CI без возврата Gate 3.
- Сохранить Gate 3 + final для прав/секретов, денег, схемы, записи/истории, replay/concurrency, offline/sync, внешних эффектов и admission policy, включая чувствительные методы смешанного файла и чувствительный diff после prepare.
- Проверить реальный маршрут `prepare → focused → reviewer package → CI selection`, отрицательные исходы обязательных checks и исторические изменения #187, #194 и #209 без встроенных исключений.

## Capabilities

### New Capabilities

- `ordinary-change-process`: risk-based lifecycle и CI selection для повторяющихся обычных изменений приложения.

### Modified Capabilities

Нет.

## Impact

Изменяются существующие verification planner, delivery harness, Quality Graph policy/ownership mapping, их regression fixtures, процессная документация и delivery evidence. Product runtime, модели, merge authority, стенд, исторические approvals и сами исторические продуктовые изменения не меняются. Изменение admission policy чувствительно и для собственного delivery сохраняет Gate 3 + final и полный выбранный exact-source CI.
