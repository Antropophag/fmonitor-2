## Context

См. `proposal.md` и delta-spec. Действующий `MariaDbOtizSettlementView::snapshot()` уже читает объект выбранного snapshot и его allocation rows. `MariaDbSnapshotBuilderPersistence::allocations()` является owner сохранения: вклад приходит из атрибуции прогресса/weight, КТУ хранится отдельно, доля определяется от сохранённых весов, итог создаёт canonical largest-remainder allocator, основание сохраняется вместе с allocation. Workbook подтверждает пользовательский смысл КТУ, доли и основания, но его compatibility presentation не должен переноситься как новая формула в HTML.

## Goals / Non-Goals

**Goals:**

- Представить уже сохранённые поля allocation как отдельные подписанные факты выбранного расчёта.
- Добавить локальный reusable partial/details внутри существующего object drawer и сохранить server-rendered/no-script доступность.
- Покрыть HTTP и browser seam изолированными fixtures, включая историческую стабильность и отсутствие записей.

**Non-Goals:**

- Любая новая арифметика, normalization или вывод одного финансового поля из другого.
- Изменение persistence owner, schema, writers, XLSX, settlement forms, router, общих assets или JavaScript.
- Персональная причинность issues без нового сохранённого provenance; это остаётся остатком широкой #29.

## Decisions

1. **Owning module и data boundary.** Read-only композиция остаётся в `app/Otiz/MariaDbOtizSettlementView.php`; persistence owner остаётся `MariaDbSnapshotBuilderPersistence`, calculation owner — `PremiumCalculationV2`. Projection возвращает только строки выбранного snapshot. Альтернатива — читать текущий workforce/composition — отвергнута как исторически неверная.

2. **Presentation seam.** В object drawer `otiz-snapshot.php` подключается локальный partial с нативным `<details>` для каждого allocation. Он получает snapshot date, object `distributed_cents`, allocation и объектные issues как уже подготовленные данные. Альтернатива — новый route/modal/client-side fetch — увеличивает поверхность, ломает no-script и не нужна.

3. **Formatting without calculation.** View только форматирует money, basis points и коэффициент: вклад/доля — проценты из собственных bp; КТУ — коэффициент из `effective_ktu_bp`; money — сохранённые cents. Итог никогда не вычисляется как сумма × доля. Нулевые сохранённые числа показываются нулями; только пустой текст/отсутствующая row получают честный empty state. Compatibility hack workbook не копируется.

4. **Issues separation.** Существующие snapshot issues остаются в объектном блоке. Partial не принимает issue как свойство работника и не строит причинную связь.

5. **Allowed dependencies and architecture.** Только существующие Yii helpers/view rendering и текущие OTIZ projection types; новых dependencies и shared CSS/JS нет. При необходимости локальные стили размещаются рядом с OTIZ view в уже допустимом scoped presentation seam, без изменения общего asset. `rapid-pilot/` не меняется и остаётся только историческим oracle. Ожидается отсутствие новых architecture ownership violations; planner определит обязательные checks.

6. **Authorization/read-only.** Новый endpoint не создаётся: наследуется существующий controller authorization до вызова projection. GET/render не вызывает writers. HTTP fixture сравнивает чувствительные tokens в denied response и DB inventories до/после.

## Risks / Trade-offs

- [Старые строки содержат семантически слабые нули или пустой текст] → показывать сохранённый ноль буквально и явно маркировать только действительно отсутствующее значение; не применять workbook compatibility heuristic.
- [Длинные основания перегрузят drawer] → один `<details>` на работника, label/value layout с переносами и без raw payload.
- [Параллельные #265/#258 меняют смежные файлы] → минимальный file footprint и rebase на свежий `origin/main` перед exact-source review; конфликт не разрешать переносом чужого поведения.
- [Изменение `otiz-snapshot.php` может задеть формы #263] → focused regression на восстановление формы и `operationId`, без изменения form markup/actions.

## Migration Plan

DDL и data migration отсутствуют. Выпуск — обычный deploy read-only presentation change; rollback удаляет partial/подключение и не требует отката данных. Backup/restore contracts не меняются, потому что persistence не меняется.
