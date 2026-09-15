## 1. Gate 1 и verification plan

- [x] 1.1 Обновить `PRODUCT.md`, normative executable contract `YII2-CONTROL-ENGINEER-ASSIGNMENT-001` и delivery record решением владельца #52; проверить полную трассировку сценариев A–H и явные non-goals
- [x] 1.2 Создать `verification-input.json`, вычислить planner-selected plan через delivery harness и закрыть все обязательства до Gate 2

## 2. Gate 2 — executable RED

- [x] 2.1 Root пишет focused owner-level tests standalone schema/read/command для bootstrap provenance, authorized/unauthorized mutation, replay, conflict и A→B→C history; проверить RED на отсутствии owner/schema
- [x] 2.2 Root пишет Yii2 regression через реальные card/preparation seams для read-only GET, отсутствия selector/confirmation, blocked missing assignment и server-owned engineer snapshot; проверить RED на текущем UI/transport
- [x] 2.3 Root добавляет regression исторического application/order snapshot и focused #40/#38 witnesses; проверить RED только на изменяемом current-engineer поведении и GREEN сохранённых исторических/installer semantics
- [x] 2.4 Подготовить exact Gate 3 package и получить независимый `gpt-5.6-sol/low` APPROVED review тестов, если planner требует Gate 3

## 3. Gate 4 — минимальная реализация

- [x] 3.1 Executor добавляет минимальную additive schema migration/history storage и штатную регистрацию permission; focused schema test проходит без runtime DDL
- [x] 3.2 Executor реализует один native assignment application owner/read seam с authorization, eligibility, bootstrap, append-only lineage, idempotency и optimistic concurrency; owner focused tests проходят
- [x] 3.3 Executor подключает POST карточки и справочное current assignment отображение без GET mutation; Yii2 card tests проходят
- [x] 3.4 Executor переводит preparation на server-side current assignment, удаляет engineer selector/confirmation и фиксирует current engineer в новом immutable composition/order snapshot; preparation tests проходят
- [x] 3.5 Executor точечно переводит требующие current engineer seams #40 на новый reader и сохраняет application-based installer semantics #38; focused regressions проходят либо фиксируется scope-stop blocker до redesign

## 4. Gate 5 и PR-ready

- [x] 4.1 Запустить только planner-selected focused local checks, architecture check и обязательные exact boundary regressions; сохранить полную evidence вне checkout
- [x] 4.2 Подготовить exact final-review package и получить независимый `gpt-5.6-sol/low` APPROVED Gate 5 без unresolved findings
- [ ] 4.3 Создать meaningful commits, открыть PR из exact reviewed source и запустить один существующий exact-source GitHub CI consumer; собрать полный failure inventory при любом отказе
- [ ] 4.4 Подтвердить PR-ready только при GREEN exact-source CI, APPROVED required reviews и совпадении source; merge/deployment не выполнять
