## Why

Поставка №194/№195 подтвердила два конкретных источника повторной работы: один браузерный сценарий зарегистрирован напрямую и через PHP-обёртку, а ручной запуск Quality Graph может стартовать поверх уже появившегося штатного PR-run. Ограниченный срез №198 устраняет эти повторы и уточняет повторную передачу на review, не ослабляя проверки и не создавая новый admission engine.

## What Changes

- Оставить `yii2_preopening_browser_001_test.php` единственным исполняемым browser-сценарием, перенести на него acceptance mappings и удалить ненужную обёртку и её непосредственные consumers.
- Добавить в существующий путь публикации/ожидания CI ограниченный поиск применимого штатного PR-triggered Quality Graph run до ручного dispatch: применимый pending/completed run переиспользуется, отсутствие допускает один fallback dispatch, а stale/mismatched/failed/unknown не дают ложный GREEN или слепой retry.
- Обновить используемые process/review handoff инструкции: повторная передача содержит delta и полный статус открытых findings; косметическая правка ledger сама по себе не требует нового code review, но изменения нормативных/исполняемых байтов и незакрытые дефекты продолжают блокировать.
- Сохранить отдельность локального focused запуска и одного exact-source CI; не менять workflow capabilities, branch settings, FAST classifier, planner/evidence schemas или общую модель admission.

## Capabilities

### New Capabilities

- `verification-delivery-deduplication`: Правила однократного выбора канонического browser-сценария, безопасного reuse/dispatch Quality Graph run и краткой повторной передачи на review для текущего кандидата.

### Modified Capabilities

Нет.

## Impact

- Затрагиваются inventory/mapping браузерных тестов, `tests/Yii2/yii2_shlz_operational_ui_001_test.php` и его consumers.
- Затрагивается существующий launcher/observer или минимальный тонкий launcher для GitHub Actions, его изолированные тесты и реально используемые инструкции публикации.
- Адресно меняются `docs/development-process.md` и существующие шаблоны role/review package; исторические review records остаются неизменными.
- Публичный actor — delivery agent; source oracle — GitHub Actions API и repository-owned verification inventory; публичные швы — существующий verification runner и CI publication command. Product/runtime API, БД, production UI и доменная история не затрагиваются.
- Release value: меньше повторных browser/CI/review циклов при сохранении fail-closed применимости и полного обязательного контроля.
- Non-goals: новый аудит харнесса, общий граф вложенных тестов, глобальная exactly-once семантика, объединение результатов разных runs, telemetry токенов, автоматический пропуск Gate, изменение release/manual/scheduled возможностей workflow.
