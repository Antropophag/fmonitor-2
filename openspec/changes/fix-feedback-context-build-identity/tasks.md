## 1. Контракт и Gate 1–2

- [x] 1.1 Root обновляет `specs/FEEDBACK-001.md` полной acceptance-матрицей issue #172 и проверяет соответствие delta spec через `openspec validate fix-feedback-context-build-identity --strict`
- [x] 1.2 Root создаёт `verification-input.json`, запускает `python3 tools/delivery/harness.py prepare` для точного worktree/source и читает все planner obligations; отсутствие unmapped obligations подтверждает готовность к Gate 2
- [x] 1.3 Root дополняет адресные application/HTTP тесты таблицей текущих положительных/отрицательных маршрутов, A/B/unknown/client spoof/replay/conflict/concurrency и фиксирует ожидаемый RED focused-командой без полного suite
- [x] 1.4 Root дополняет один связный HTTP/browser-сценарий «исходный экран → ссылка → форма → сохранение → подтверждение → возврат → оператор» для календаря, дашборда, нескольких ОТиЗ экранов и snapshot; focused browser test демонстрирует RED по отсутствующему поведению
- [x] 1.5 Независимый `gpt-5.6-sol/low` reviewer проверяет полный spec/tests/RED snapshot и записывает Gate 3 verdict, если planner требует Gate 3; `APPROVED` обязателен до production implementation

## 2. Минимальная реализация

- [x] 2.1 Отдельный `gpt-5.6-sol/low` executor расширяет закрытую нормализацию только перечисленными user-facing GET routes, сохраняет object ID только для объектных путей и подтверждает адресной таблицей GREEN
- [x] 2.2 Executor добавляет fail-soft чтение заранее сформированного immutable build-файла и Yii composition с полным digest либо `unknown`, не вызывая source fallback; focused A/B/unknown/spoof проверки GREEN
- [x] 2.3 Executor сохраняет существующий fingerprint/replay/store/schema, делает operator labels однозначными при необходимости и подтверждает старую запись A, новую B, replay и conflict без UPDATE/дубликата
- [x] 2.4 Root выполняет обязательные planner-selected focused проверки, архитектурную проверку и связный HTTP/browser-сценарий; полный локальный `make test`/`make verify` не запускается

## 3. Review и PR-ready

- [x] 3.1 Root фиксирует reconstructible exact-source snapshot/commit и проверяет, что diff не затрагивает ОТиЗ бизнес-правила, status/dashboard consistency, readiness semantics, migration, downloads/exports или чужой PR #235
- [x] 3.2 Независимый `gpt-5.6-sol/low` reviewer выполняет planner-required Gate 5 по exact source, spec, tests и evidence; все findings исправляются и изменённый delta повторно рассматривается
- [ ] 3.3 Root создаёт отдельный commit/PR для issue #172 без merge/deploy/закрытия issue и запускает один обязательный exact-source GitHub CI consumer; при сбое сначала собирает полный failed-job и `REGRESSION_FAILURE` inventory
- [ ] 3.4 Root записывает delivery/handoff с PR и HEAD, поддержанными контекстами, источником build identity, доказательствами A/B/replay/unknown, CI/review verdicts и оставшимися ограничениями; статус задач отражает только фактически завершённое
