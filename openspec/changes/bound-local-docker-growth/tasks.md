## 1. Scope и Gate 1

- [x] 1.1 Зафиксировать owner assignment, bounded scope, authorship и non-goals в текущем delivery goal/record; проверить, что №157 и другие параллельные changes не включены в candidate.
- [x] 1.2 Создать root-authored normative contract `specs/LOCAL-DOCKER-STORAGE-BUDGET-001.md` с public seam, A–N acceptance matrix, exact thresholds, rejections, concurrency, redaction и сохранностью stand/foreign resources; проверить traceability ко всем OpenSpec scenarios.
- [x] 1.3 Создать `verification-input.json`, выполнить planner, прочитать все obligations/commands и устранить coverage gaps до Gate 2; сохранить выбранные lane и `required_reviews`.

## 2. Gate 2: executable RED

- [x] 2.1 Добавить root-authored deterministic fake-Docker contract test для stable dependency identity, source provenance и exactly-one build; запустить focused command и получить intended RED.
- [x] 2.2 Расширить RED matrix достаточным/низким/неизмеримым disk space, bounded cleanup, remeasurement, fail-closed outcome и safe machine-readable output; проверить, что тест падает именно из-за отсутствующего guard.
- [x] 2.3 Добавить RED cases для положительного owner label, dedicated-builder scope, запрета global prune/volumes/containers/networks и идемпотентного no-op; проверить полный argv и order.
- [x] 2.4 Добавить RED cases для lock/recheck/concurrent runners и disposable teardown при success/error/INT/TERM с сохранением primary status; проверить отсутствие выбора persistent stand identity.
- [x] 2.5 Зарегистрировать новые тесты в verification inventory и выполнить planner-selected focused RED commands без локального full suite; сохранить bounded RED evidence вне checkout и ссылку в review record.
- [x] 2.6 Если planner требует Gate 3, подготовить exact test candidate и получить независимый APPROVED review; при findings исправить полный test matrix и повторно подготовить package.

## 3. Gate 4: executor implementation

- [x] 3.1 Подготовить executor role package через delivery harness и передать отдельному gpt-5.6-sol/low executor только approved contract/tests и полный candidate context.
- [x] 3.2 Реализовать единый `tools/delivery` storage-guard seam: validation, measurement, lock, cleanup decision, remeasurement, structured outcome и fail-closed exit; проверить root-authored matrix.
- [x] 3.3 Подключить deterministic dedicated builder/config и stable dependency-addressed focused image labels/identity к `run-in-profile`; проверить reuse между source revisions и invalidation при изменении dependencies.
- [x] 3.4 Реализовать project-only image/cache maintenance с hard floor 50 ГиБ, target 80 ГиБ, builder maximum 30 ГБ и retention 48 часов; проверить, что никакой путь не вызывает global system/volume/container/network prune.
- [x] 3.5 Инвентаризировать repository-owned disposable Compose callers, добавить reusable exact-project lifecycle owner для будущих ephemeral callers и проверить success/error/signal cases и сохранность persistent stand paths; текущие tmpfs/`--rm` callers не переподключать без volume ownership.
- [x] 3.6 Добавить safe diagnostic/doctor output и документацию Docker Desktop GC как manual host guidance без автоматического изменения settings; проверить redaction и machine-readable fields.
- [x] 3.7 Добавить architecture rule против прямых destructive cleanup calls вне owning seam и выполнить planner-selected focused checks плюс `make architecture-check`, не запуская локальный `make test`/`make verify`.

## 4. Gate 5 и delivery

- [x] 4.1 Root проверяет полноту candidate, фиксирует actual authors и готовит exact source snapshot/package для независимого gpt-5.6-sol/low final reviewer.
- [x] 4.2 Получить независимый APPROVED final review; все findings закрыть с явным disposition и повторной проверкой изменённого delta.
- [ ] 4.3 Выполнить один planner-selected exact-source GitHub CI run, собрать полный failure inventory при сбое и не повторять same-source run без разрешённой причины.
- [ ] 4.4 Обновить OpenSpec task state и delivery record фактическими измерениями before/after, checks, review/CI status и оставшимися manual host actions; объявить PR-ready только при всех mandatory GREEN.
