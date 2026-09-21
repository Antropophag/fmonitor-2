## 1. Контракт и Gate 1

- [x] 1.1 Зафиксировать bounded legacy evidence A–F и owner decision по NULL/hourly в executable contract `specs/ERP-EQUIPMENT-FACTS-001.md`; проверить соответствие source fields, exact key и explicit non-goals issue #12.
- [x] 1.2 Создать `verification-input.json`, вычислить обязательный Quality Graph plan штатным planner и проверить полноту acceptance mapping A–L до Gate 2.

## 2. Executable specification и RED

- [x] 2.1 Root добавляет disposable-DB integration spec через public owner seam для initial/partial/full-without-first/correction/idempotency/clear/failure/mapping/provenance/process-isolation сценариев A–L и проверяет intended RED только planner-selected командой.
- [x] 2.2 Root добавляет focused adapter contract test точных BI fields, sentinel и invalid/incomplete response, затем фиксирует intended RED без live ERP или secrets.
- [x] 2.3 Root добавляет focused hourly scheduler/worker и object-card regressions, включая same-slot idempotency, no backlog, retryable failure и минимальный read projection, затем фиксирует intended RED.

## 3. Минимальная реализация отдельным executor

- [x] 3.1 Executor реализует additive equipment projection/history/runs/metadata/diagnostics migration и transactional application owner; focused owner integration spec проходит.
- [x] 3.2 Executor реализует read-only ERP adapter и canonical sync composition с strict batch validation и безопасной конфигурацией; adapter и failure regressions проходят.
- [x] 3.3 Executor расширяет существующие scheduler/worker hourly job без cron и нового framework; focused jobs regressions проходят.
- [x] 3.4 Executor добавляет компактный equipment facts/freshness/status block в существующую карточку без изменения process semantics или redesign; focused card и isolation regressions проходят.
- [x] 3.5 Executor регистрирует только необходимые production migration/autoload/focused-test entries и проверяет planner-selected architecture/boundary checks без локального full suite.

## 4. Reviews и delivery

- [x] 4.1 Подготовить complete candidate через harness package; независимый reviewer выполняет требуемый planner-ом Gate 3 review и все findings устранены либо явно блокируют delivery.
- [x] 4.2 Выполнить planner-selected focused checks на исправленном candidate и сохранить bounded evidence вне checkout; локальный `make test`/`make verify` не запускать.
- [ ] 4.3 Запустить один exact-source GitHub CI по действующей policy, собрать полный failure inventory и довести обязательные checks до GREEN без UNKNOWN-as-approval.
- [ ] 4.4 Независимый final reviewer выполняет Gate 5 по exact reviewed source; обновить delivery record/current goal, подготовить PR с issue #12 и подтвердить PR-ready без merge, deployment или live ERP write.
