## 1. Gate 1–2: контракт и RED

- [x] 1.1 Зарегистрировать #150 как текущий delivery goal, создать `verification-input.json`, выполнить `harness.py prepare` и проверить, что planner obligations полностью сопоставлены с `YII2-MAIN-NAVIGATION-001` до Gate 2
- [x] 1.2 Написать bounded regression через реальный Yii HTTP/browser seam для матриц A–F; проверить semantic main navigation DOM, неизменную route authorization и сохранённую OTIZ internal navigation
- [x] 1.3 Выполнить planner-selected focused test на актуальном `main` и записать RED строго из-за разного/отсутствующего MAIN navigation, без setup failure
- [x] 1.4 Если planner требует Gate 3, получить независимый review полного spec/test/RED candidate и продолжать только при `APPROVED`

## 2. Gate 4: минимальная реализация

- [x] 2.1 Реализовать маленький shared Yii2 main-navigation renderer, который использует существующий `canonicalAccess`, фиксированные canonical routes и явный current section; проверить syntax/focused renderer behavior
- [x] 2.2 Подключить renderer к `/pilot/objects`, `/pilot/construction-control`, `/pilot/admin/users` и `/pilot/admin/roles`, не меняя route/controller authorization; проверить focused regression
- [x] 2.3 Подключить тот же MAIN navigation к `/pilot/otiz`, сохранив внутренние OTIZ links и behavior; проверить focused OTIZ regression
- [x] 2.4 Выполнить только все planner-selected focused local checks и зарегистрированный test; записать команды, elapsed time и GREEN evidence

## 3. Gate 5 и PR-ready

- [x] 3.1 Подготовить точный reconstructible source package и получить независимый planner-required final review без findings; при коррекциях повторно проверить изменённый delta
- [x] 3.2 Создать meaningful commits, push branch и открыть PR для #150 без merge/deploy; проверить exact source binding
- [ ] 3.3 Запустить один existing exact-source GitHub CI consumer, собрать полный failed-job/`REGRESSION_FAILURE` inventory при сбое и довести CI до GREEN без локального full suite
- [ ] 3.4 Обновить delivery record и handoff фактическими авторами, review/return counts, focused evidence, exact commit/PR/CI и подтвердить состояние PR-ready
