## 1. Контракт и Gate 2

- [x] 1.1 Зафиксировать bounded ownership check, normative spec `CONTAINER-COMPOSER-VISIBILITY-123-A` и A–J mapping; проверить `openspec validate --strict`.
- [x] 1.2 Создать `verification-input.json`, подготовить root package, прочитать planner lane/obligations/commands и устранить UNKNOWN coverage до Gate 2.
- [x] 1.3 Root написать executable public-route regression и сохранить RED на dependency visibility с T08 measurement (3/3 setup failures до behavior), не выполняя local full suite.
- [x] 1.4 Получить planner-required independent Gate 3 approval exact spec/test/RED, если план требует Gate 3.

## 2. Minimal implementation

- [ ] 2.1 Отдельный executor реализует container-only read-only dependency visibility в existing profile seam и lock-bound image identity без host vendor или нового manager; A–F становятся GREEN.
- [ ] 2.2 Executor сохраняет existing governance/integration/browser behavior и tracked inputs; G–J и focused planner commands GREEN с cold-ish/warm timing/origin evidence.

## 3. Review и публикация

- [ ] 3.1 Root проверяет complete candidate, source snapshot/freshness и готовит reviewer package с полным focused evidence.
- [ ] 3.2 Independent Gate 5 reviewer выдаёт APPROVED для exact source; все findings исправлены и применимые delta повторно reviewed.
- [ ] 3.3 Выполнить один exact-source CI run через planner-selected existing consumer, собрать полный failure inventory при сбое и довести branch/PR до PR-ready без merge/deploy/settings.
