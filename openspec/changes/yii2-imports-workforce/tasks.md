## 1. Re-scope и Gates 1–3

- [x] 1.1 Root синхронизирует `YII2-IMPORTS-WORKFORCE-001`, verification input и tests с одним seam `case-import/run`; verification: snapshot/workforce отсутствуют в acceptance mapping и явно остаются следующими changes #76.
- [x] 1.2 Root готовит свежий harness package и intended RED для transport, полного direct/alias DB oracle, single-composition witness и package/load closure; verification: RED вызван отсутствующим Yii case-import adapter, governance checks GREEN.
- [x] 1.3 Независимый sol/low reviewer выполняет новый Gate 3 по свежему exact source; verification: append-only review содержит полный findings list и `APPROVED`, иначе Gate 4 закрыт.

## 2. Gate 4 — implementation

- [x] 2.1 Executor реализует shared case-import console adapter с закрытой validation, одним mysqli lifecycle и существующим `PilotCaseImporter`; verification: transport/counting/DB oracle GREEN.
- [x] 2.2 Executor регистрирует Yii route и превращает retained legacy script в тонкий alias, переключая только доказанные callers; verification: direct/alias и package/load tests GREEN.
- [x] 2.3 Executor выполняет bounded focused plan без локальных `make test`/`make verify`; verification: применимые команды GREEN, логи вне checkout.

## 3. Gate 5 и merge-ready

- [x] 3.1 Независимый sol/low reviewer проверяет exact production diff и evidence; verification: code review содержит полный findings list и `APPROVED` либо correction scope.
- [ ] 3.2 Root подтверждает reviewed bytes, strict OpenSpec и completeness, фиксирует merge-ready commit и PR; verification: harness связывает exact source/PR, CI остаётся UNKNOWN до фактического запуска.
