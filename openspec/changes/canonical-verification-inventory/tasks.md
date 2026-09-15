## 1. Gate 1–3 contracts

- [x] 1.1 Добавить нормативный `VERIFICATION-CANONICAL-INVENTORY-001` и verification input; strict OpenSpec validation и generated planner obligations должны быть полными.
- [x] 1.2 Добавить executable characterization/RED для migration, validator, atomic registration, deterministic order, category union, early `UNREGISTERED_TEST`, fast/governance separation и fail-closed Quality Graph behavior; сохранить RED evidence.
- [x] 1.3 Получить независимый Gate 3 review полного контракта и RED tests для planner-required reviews.

## 2. Canonical inventory migration

- [x] 2.1 Реализовать один stdlib parser/validator/CLI и механически мигрировать все manifest rows в четырёхполосный deterministic format; focused inventory tests должны пройти.
- [x] 2.2 Удалить `categories.json` и перевести `run.sh`, `ci.py`, change-verification planner и policy на общий parser без active references; consumer tests должны пройти.
- [x] 2.3 Добавить атомарный `make register-test FILE=... CATEGORY=... RUNTIME=... SUITE=...`; acceptance cases success/failure/unchanged bytes должны пройти.
- [x] 2.4 Встроить candidate-aware unregistered-test validation в существующий prepare/build seam с точной диагностикой; planner tests должны пройти.
- [x] 2.5 Заменить прямой fast-запуск governance test дешёвой inventory validation, сохранив governance membership и текущую lane/aggregation semantics; CI contract tests должны пройти.

## 3. Delivery completion

- [x] 3.1 Выполнить bounded focused checks из подготовленного plan без локального полного `make test`/`make verify`; все применимые результаты должны быть GREEN.
- [x] 3.2 Синхронизировать final candidate с актуальным `main`, механически мигрировать только появившиеся inventory additions и повторить затронутые focused checks.
- [x] 3.3 Получить независимый Gate 5 review exact candidate и исправить все findings без расширения scope.
- [ ] 3.4 Запустить один exact-source GitHub CI run, получить GREEN и подготовить PR без merge/deployment/settings changes.
