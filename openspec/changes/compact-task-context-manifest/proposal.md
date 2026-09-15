## Why

Новые root/reviewer/correction sessions сейчас обязаны заново материализовать крупный общий набор canonical документов и исторических artifacts, хотя конкретной задаче применима только часть этого набора. Нужен deterministic индекс существующих canonical sources, уменьшающий mandatory context bytes без пропуска delivery, security, auth, persistence или domain rules и без появления второго нормативного источника.

## What Changes

- Existing delivery harness `prepare/state/package` формирует digest-bound task-context manifest для exact issue/change, base и candidate source.
- Manifest детерминированно классифицирует canonical references как `required_context`, `load_on_demand`, product/spec и verification references на основе уже известных planned/changed boundaries, lane/risk, OpenSpec binding и repository-owned document roles.
- Безопасно индексируемые именованные sections материализуются как exact bounded canonical content с source/range/digest; неделимые или неизвестные области fail-safe остаются required целиком.
- Root, executor и reviewer packages получают manifest через существующий public prepare route; stale source/section index отвергается или пересобирается, а одинаковый immutable lifecycle input даёт идентичный manifest.
- Добавляются executable cases A–L и before/after измерение mandatory bytes/chars, full-document count и load-on-demand references на UI, persistence/current-state и harness/verification replay fixtures. Фактические token savings не заявляются без telemetry.

## Capabilities

### New Capabilities

- `delivery/task-context-manifest`: Deterministic, reconstructible и fail-safe context packaging для delivery roles поверх canonical repository sources.

### Modified Capabilities

Нет.

## Impact

Затрагиваются только delivery tooling/tests/specification и public harness package schema: `tools/delivery/`, focused verification tests, OpenSpec и delivery records. Source oracle — canonical repository documents и existing verification input/plan; actor — root/executor/reviewer session; target public seam — `python3 tools/delivery/harness.py prepare` и созданный `package.json`. Product runtime, FAST classifier, verification coverage, Gates, CI composition и canonical policy semantics не меняются. Не входят NLP/LLM selection, generative summaries, RAG/vector/search service, второй planner/policy engine, новый per-issue metadata contract, mass documentation rewrite, #107, T03–T14, #95, #136 и #141.
