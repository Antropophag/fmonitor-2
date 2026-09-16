## 1. Gate 1 и verification planning

- [x] 1.1 Root публикует `specs/DETERMINISTIC-KNOWN-CI-TRIAGE-001.md` с public `harness state/wait` contract и executable cases A–N; verification: acceptance matrix полностью трассируется к delta spec и issue #164.
- [x] 1.2 Root создаёт `verification-input.json`, запускает `harness.py prepare` от base `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` и читает все planner obligations; verification: plan fresh, exact-source и не содержит unresolved coverage.

## 2. Gate 2 и Gate 3

- [x] 2.1 Root добавляет public CLI RED fixtures для historical transient, exact negatives, setup neighbor, unknown, retry exhaustion, source drift, preserved history, admission/diagnostic separation и FAST/STANDARD parity; verification: focused command fails только из-за отсутствующего triage behavior.
- [x] 2.2 Независимый gpt-5.6-sol/low reviewer проверяет полную spec/RED mapping и записывает Gate 3 verdict; verification: planner-required review `APPROVED` либо все findings исправлены и повторно одобрены до implementation.

## 3. Gate 4

- [x] 3.1 Отдельный gpt-5.6-sol/low executor добавляет closed two-signature policy и интегрирует её в existing `harness state/wait`, не меняя publisher/workflow/admission semantics; verification: public fixture возвращает exact machine-readable triage result.
- [x] 3.2 Executor реализует bounded diagnostic retrieval и one-retry decision на existing run/attempt/head state без нового store или retry dispatch; verification: attempt 1 same-source разрешён, attempt 2/source drift запрещены, failure history сохранена.
- [x] 3.3 Executor запускает planner-selected bounded focused checks, включая negative neighbors и architecture/inventory obligations; verification: все selected local commands GREEN, canonical local full suite не запускался.

## 4. Gate 5, measurement и публикация

- [x] 4.1 Root фиксирует historical before/after proxy: mandatory log payloads materialized до decision, model-driven triage steps, automatic retry count и `token_usage=UNKNOWN`; verification: fixture report воспроизводим и не заявляет недоступную telemetry.
- [x] 4.2 Независимый gpt-5.6-sol/low reviewer проверяет exact candidate, tests, boundaries и focused evidence; verification: Gate 5 `APPROVED`, findings/returns сохранены.
- [ ] 4.3 Root создаёт PR-ready commit/PR и запускает ровно один selected exact-source GitHub CI consumer; verification: exact head/source CI GREEN, previous failures retained, merge/deploy/settings не выполнены.

## 5. Done

- [x] 5.1 Public triage классифицирует минимум PR #144 fixture без LLM semantic judgment, setup fixture остаётся exact, unknown neighbors fail closed и retry не может зациклиться; verification: executable cases A–N GREEN через один public classifier для FAST и STANDARD routes.
