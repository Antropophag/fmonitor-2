## 1. Контракт и чувствительность

- [x] 1.1 Добавить независимый immutable current-schema test contract и проверить PHP syntax плюс literal frontier.
- [x] 1.2 Перевести профильный frontier test на contract и добавить bounded missing-last/missing-intermediate варианты; проверить, что оба завершаются адресным frontier mismatch.

## 2. Предметные consumers

- [x] 2.1 Классифицировать изменённые в #192 PHP assertions и перевести только current setup/replay consumers; проверить отсутствие собственных current-version literals в выбранном наборе.
- [ ] 2.2 Сохранить subject assertions, пустой replay `appliedVersions`, DB/prefix isolation, cleanup и concurrency; выполнить выбранные тесты на изолированной MariaDB.
- [x] 2.3 Оставить literal catalog, historical upgrade/recovery и compatibility assertions; проверить diff на отсутствие `app/**`, DDL и Python/Compose fixture изменений.

## 3. Delivery

- [ ] 3.1 Выполнить OpenSpec validation, planner-selected architecture/policy и focused checks до первого push без локального full suite.
- [ ] 3.2 Получить независимые Gate 3 и Gate 5 reviews цельного exact-source кандидата, исправляя только delta/open findings.
- [ ] 3.3 Создать отдельный PR #193, получить exact-source CI GREEN и зафиксировать before/after count и намеренно сохранённые проверки.
