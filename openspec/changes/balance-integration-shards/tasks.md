## 1. Baseline и executable contract

- [x] 1.1 Зафиксировать последние сопоставимые successful FULL runs после №135: workflow wall, оба integration execution/setup и top `VERIFY_TIMING`; проверить ссылки/SHA и честно указать размер выборки.
- [x] 1.2 Root добавляет executable tests A–H для LPT, shuffled input, fallback/stale/invalid hints, union/intersection, двух job names и fail-closed aggregation; получить и сохранить ожидаемый RED.
- [x] 1.3 Создать `verification-input.json`, получить planner-selected lane/reviews через harness и проверить OpenSpec strict validation.

## 2. Minimal implementation

- [x] 2.1 Executor добавляет минимальный optional planning-weights artifact и deterministic LPT seam в существующий `ci.py`; focused tests подтверждают полный canonical membership без ручного timing для новых tests.
- [x] 2.2 Вычислить old/new estimated loads на свежем inventory и зафиксировать predicted critical-shard effect отдельно от measured CI.

## 3. Один setup candidate

- [x] 3.1 Тремя bounded повторами одного declared profile измерить prerequisites `quality_graph_ci_setup_001_test`; сохранить phase evidence вне checkout и итоговые числа в report.
- [x] 3.2 Либо executor реализует одну contract-preserving оптимизацию и минимум три after-повтора (с cold/warm/invalidation cases при cache), либо report фиксирует `NO_SAFE_SETUP_OPTIMIZATION_FOUND` без setup code changes.

## 4. Gates и PR-ready

- [x] 4.1 Выполнить planner-selected focused checks, включая invariants №135 и closure №153A, без локального full suite; сохранить evidence.
- [ ] 4.2 Получить независимые planner-required Gate 3 и final reviews на exact source, исправить подтверждённые замечания и повторить затронутые focused checks.
- [ ] 4.3 Опубликовать bounded PR, запустить один exact-source FULL Quality Graph, собрать полный failure inventory при ошибке и зафиксировать before/after job timings с runner-variance caveat.
- [ ] 4.4 Подготовить финальный report с base/head SHA, coverage invariants, единственным setup outcome и `token/cost UNKNOWN`; merge/deploy/settings не выполнять.
