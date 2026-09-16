## 1. Gate 1–3: contract и RED

- [ ] 1.1 Root создаёт executable spec и `verification-input.json`, отображающие cases A–J на один focused Delivery test; verification: `tools/delivery/change-verification.py` строит mandatory plan без unresolved obligations.
- [ ] 1.2 Root добавляет RED cases: prepare→fresh state, structured APPROVED, stale after source change, missing, FAST final-only, STANDARD required reviews, identical `state|wait|prepare-merge`, restart recovery, foreign binding rejection и отсутствие hash self-reference; verification: focused test падает только на отсутствующем canonical context/result contract.
- [ ] 1.3 Root запускает harness `prepare` для reviewer Gate 3 и отдельный independent reviewer проверяет полноту mapping, чувствительность RED и отсутствие T07a semantics; verification: durable structured Gate 3 result `APPROVED` для exact source либо correction loop.

## 2. Minimal implementation

- [ ] 2.1 Отдельный executor добавляет minimal structured review-result writer в existing harness external state/package contract без Markdown parsing или нового store; verification: current/missing/foreign/self-reference focused cases GREEN.
- [ ] 2.2 Executor добавляет один canonical live admission context builder с exact plan, obligations, source/candidate и policy bindings и CURRENT/MISSING/STALE review projection; verification: prepare/fresh-process/source-stale cases GREEN.
- [ ] 2.3 Executor подключает тот же context к `state`, `wait`, `prepare-merge` и existing native admission observation без изменения `admission.evaluate()`; verification: command-parity, FAST и STANDARD cases GREEN и diff `tools/delivery/admission.py` пуст.

## 3. Verification и PR-ready

- [ ] 3.1 Root выполняет только planner-selected bounded focused checks и strict OpenSpec validation; verification: все A–J GREEN, `git diff --check` GREEN, full local `make test`/`make verify` не запускались.
- [ ] 3.2 Отдельный independent final reviewer проверяет exact complete candidate, external-only verdict storage, stale/foreign fail-closed behavior и жёсткие non-goals; verification: durable structured final `APPROVED` для exact source либо correction loop.
- [ ] 3.3 Root открывает отдельный PR и запускает один existing exact-source GitHub CI consumer; verification: полный failed-job/`REGRESSION_FAILURE` inventory обработан, exact-head CI GREEN, PR-ready зафиксирован без merge.
- [ ] 3.4 Root останавливается после PR-ready и не продолжает blocked T07a; verification: ветка T07a, Quality Graph publisher, T07b, #153B/C и product code не изменены.
