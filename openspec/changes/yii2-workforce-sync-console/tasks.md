## 1. Scope, specification и Gate 1–3

- [x] 1.1 Root создаёт `YII2-WORKFORCE-SYNC-CONSOLE-001`, связывает issue #76 с `verification-input.json` и проверяет полноту acceptance mapping; verification: обязательный executable Quality Graph plan свежий, OpenSpec strict GREEN, OTIZ paths отсутствуют.
- [x] 1.2 Root пишет минимальные public-seam tests и сохраняет intended RED для strict transport, полного direct/alias DB oracle, job/manual single-composition witness, package/load closure и redaction; verification: failures вызваны отсутствующим Yii workforce-sync seam, существующие oracle controls GREEN.
- [x] 1.3 Независимый sol/low reviewer проверяет normative spec, mapping, tests и RED; verification: append-only `reviews/tests/YII2-WORKFORCE-SYNC-CONSOLE-001.md` содержит полный findings list и `APPROVED`, иначе Gate 4 закрыт.

## 2. Gate 4 — implementation

- [x] 2.1 Executor выделяет общую workforce synchronization composition с validation environment и одним mysqli lifecycle, переиспользуя существующие Bitrix delivery и synchronization owners; verification: delivery/history/canonical-runner и resource-close tests GREEN.
- [x] 2.2 Executor добавляет строгий `workforce-sync/run` controller, переводит retained manual script в тонкий alias и переиспользует общую composition из jobs без изменения lease/retry mapping; verification: transport, direct/alias parity и применимые jobs workforce/retry tests GREEN.
- [x] 2.3 Executor закрывает production package/load inventory и verification registration, не изменяя OTIZ или stand; verification: ownership/package/governance checks и `git diff --check` GREEN, локальные `make test`/`make verify` не запускаются.

## 3. Gate 5 и delivery

- [x] 3.1 Независимый sol/low reviewer проверяет exact production diff и bounded evidence; verification: append-only code review содержит полный findings list и `APPROVED` либо точный correction scope.
- [ ] 3.2 Root подтверждает reviewed bytes, task completeness и exact source, готовит PR через harness; verification: source/PR binding известен, один GitHub full CI запускается для кандидата, UNKNOWN не объявляется GREEN и deployment остаётся отдельным неавторизованным шагом.
