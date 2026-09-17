## 1. Gate 1 и verification plan

- [x] 1.1 Root создаёт нормативную спецификацию `specs/LOCAL-INTEGRATION-ENV-001.md` со всеми public seams, примерами valid/invalid input, security, replay/concurrency и non-goals; проверить traceability со всеми сценариями delta spec
- [x] 1.2 Root создаёт `verification-input.json`, запускает `python3 tools/delivery/harness.py prepare` и читает весь mandatory Quality Graph plan; unresolved obligations отсутствуют до Gate 2

## 2. Gate 2 — RED

- [x] 2.1 Root добавляет focused тест единого `.env` для legacy и Bitrix: шаблон, отдельные Make seams, `up-with-data`, valid/invalid matrices и доказательство отсутствия downstream effects; подтвердить intended RED bounded-командой
- [x] 2.2 Root добавляет security/staging cases: permissions, atomic replacement/failure/concurrency, changed `.env`, canary redaction из argv/stdout/stderr/Compose/build context и отсутствие reset; подтвердить intended RED bounded-командой
- [x] 2.3 Root фиксирует RED evidence и полную acceptance mapping в `reviews/tests/LOCAL-INTEGRATION-ENV-001.md`; проверить, что записи не содержат секретов

## 3. Gate 3 — независимая проверка тестов

- [x] 3.1 Если planner требует `gate3`, независимый gpt-5.6-sol/low reviewer проверяет полный Gate 1/2 candidate из prepared reviewer package и записывает APPROVED либо полный CHANGES_REQUESTED verdict
- [x] 3.2 Root устраняет все Gate 3 findings в scope/spec/tests, повторяет только затронутые bounded RED checks и получает требуемое независимое одобрение до implementation

## 4. Gate 4 — минимальная реализация

- [x] 4.1 Отдельный gpt-5.6-sol/low executor из prepared package добавляет полный безопасный integration-шаблон в `.env.example`; focused template test проходит
- [x] 4.2 Executor реализует единый parse/validate/stage owner с безопасными codes, atomic `0600` files и `0700` directory; focused invalid/effects, permission, concurrency и redaction tests проходят
- [x] 4.3 Executor переводит `make import-legacy`, `make sync-workforce` и `make up-with-data` на автоматический staging из `.env`, сохраняя read-only mounts и Yii2 owners; focused public-seam tests проходят
- [x] 4.4 Executor согласует operator docs с единым quickstart и повторным применением без reset; documentation assertions и OpenSpec strict validation проходят
- [x] 4.5 Executor запускает все planner-selected bounded local checks, сохраняет полные результаты вне checkout и отмечает выполненными только полностью реализованные задачи

## 5. Gate 5 и публикационный candidate

- [x] 5.1 Root захватывает reconstructible exact-source snapshot/commit, сверяет digest и готовит final reviewer package с полной acceptance mapping и verification evidence
- [x] 5.2 Независимый gpt-5.6-sol/low reviewer выполняет planner-required final review и записывает `reviews/code/LOCAL-INTEGRATION-ENV-001.md` с APPROVED либо полным CHANGES_REQUESTED verdict
- [ ] 5.3 После APPROVED root запускает один exact-source GitHub CI через выбранный consumer, инвентаризирует все failed jobs/`REGRESSION_FAILURE` при сбое и не повторяет same-source run без записанной причины
- [ ] 5.4 Root оформляет delivery record: authorship, source, reviews, CI, elapsed/rework, delivered result и все UNKNOWN/blockers; merge/deploy/settings не выполняются

## 6. Done definition

- [ ] 6.1 Все сценарии `LOCAL-INTEGRATION-ENV-001` реализованы на public Make/bootstrap seams, planner-required review APPROVED, selected focused checks и единственный exact-source CI GREEN; старые manual-file инструкции отсутствуют, product/schema/backup owners не изменены
