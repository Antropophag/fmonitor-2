# VERIFICATION-CANONICAL-INVENTORY-001

## Простыми словами

Каждый тест регистрируется один раз в `suites.tsv`: там одновременно указаны старый suite, runtime, путь и CI category. Регистрационная команда меняет только этот файл, а незарегистрированный новый тест останавливает подготовку до Gate 3. Fast проверяет дешёвую целостность inventory; содержательная CI governance-проверка исполняется только в governance. Срез не меняет тестовое покрытие, FAST classifier, CI sharding или product code.

## Actor, oracle, authorization and public seams

Actor — разработчик, добавляющий или изменяющий repository test. Oracle — owner contract issue #135 и сохранённые на `main` 427 соответствий `suites.tsv` ↔ `categories.json`. Root владеет спецификацией и tests; отдельный executor реализует; независимые reviewers решают planner-required Gate 3 и Gate 5. Публичные seams:

- `python3 tools/verification/inventory.py validate|list|register`;
- `make register-test FILE=... CATEGORY=... RUNTIME=... SUITE=...`;
- существующий `tools/delivery/change-verification.py build/check`, вызываемый `harness.py prepare`;
- `python3 tools/verification/ci.py list|run|run-fast-node|aggregate` и существующий Quality Graph workflow.

Никаких domain facts, authorization/audit semantics, production state или deployment действий этот tooling slice не создаёт. Repeated validation идемпотентна. Межпроцессная координация нескольких writers не вводится этим bounded slice; параллельные product additions разрешаются штатной синхронизацией с актуальным `main` перед final candidate.

## R1 — Единственный canonical manifest

`tools/verification/suites.tsv` MUST быть единственным вручную редактируемым inventory. Каждая непустая некомментарная строка MUST иметь четыре TAB-separated поля:

`legacy-suite<TAB>runtime<TAB>path<TAB>ci-category`

Допустимы legacy suite `unit|db|characterization|e2e`, runtime `php|node|python3`, CI category `unit|integration|e2e|governance`. Path MUST быть безопасным repository-relative существующим файлом под `tests/` либо сохранённым ACTIVE `rapid-pilot/` inventory reference. Каждый path MUST встречаться ровно один раз независимо от suite/category. Canonical порядок — лексикографический по `(legacy suite, path, runtime, CI category)`; parser MUST выдавать один и тот же canonical output для одинакового логического набора при любой перестановке исходных filesystem results.

Миграция MUST сохранить для всех 427 записей `main@25aee552` точные прежние path, legacy suite, runtime и CI category. `categories.json` MUST быть удалён; active consumers MUST NOT ссылаться на него. Historical reviews и archived lifecycle records не переписываются.

Invalid row, duplicate path, missing file, unknown enum или unknown discovered canonical test MUST завершить validation ненулево до исполнения test runtime. Диагностика незарегистрированного теста точно начинается `UNREGISTERED_TEST: <path>`.

## R2 — Одна реализация parser/validator

Один stdlib-only Python module SHALL владеть parse, normalization, validation, discovery и category/suite projections. `ci.py` и change-verification planner MUST переиспользовать его; `run.sh` MUST получать machine-readable list через его CLI и не реализовывать второй manifest parser. Workflow YAML и Make MUST только вызывать публичные seams, не парсить manifest.

Validator MUST обеспечивать:

1. manifest path существует и row имеет ровно четыре поля;
2. suite/runtime/category входят в допустимые enums;
3. path безопасен и уникален на всём inventory level;
4. canonical ordering deterministic;
5. discovery policy не содержит незарегистрированный canonical test;
6. union четырёх CI categories равен manifest и содержит каждый path один раз.

## R3 — Атомарная регистрация

`make register-test` MUST требовать четыре явных значения `FILE`, `CATEGORY`, `RUNTIME`, `SUITE`; suite никогда не угадывается по category, path или имени. До записи команда MUST валидировать исходный manifest, существование FILE, enums, path safety и отсутствие duplicate. Затем она MUST построить и полностью провалидировать canonical candidate bytes, записать temporary sibling и выполнить atomic replace. После replace тот же validator MUST вернуть GREEN.

При любом отказе до atomic replace, включая missing file, duplicate, unknown enum, malformed current manifest или failed candidate validation, исходные manifest bytes MUST сохраниться. Успех изменяет только `tools/verification/suites.tsv`, не test code и не второй registry.

## R4 — Discovery до Gate 3

Существующий change-verification build/check seam MUST проверить inventory до выпуска planner package. Любой новый added canonical executable под `tests/**`, отсутствующий в manifest, MUST дать `UNREGISTERED_TEST: <path>` и остановить prepare до Gate 3.

Canonical candidate test определяется поддерживаемым runtime и действующим executable naming contract. Существующие tracked historical helpers/retired tests вне inventory не становятся новым cleanup scope. Generated, ignored, fixtures/support modules и agent-only harness artifacts, уже исключённые текущей repository policy, MUST NOT создавать false positive. Удалённый manifest member остаётся missing-file error. Изменение inventory/policy остаётся в существующей non-FAST boundary; FAST classifier и allowed boundaries не меняются.

## R5 — Однократная CI composition

`ci.py list CATEGORY` MUST строить `runtime<TAB>path` только из canonical manifest. Union `unit|integration|e2e|governance` MUST полностью и без пересечений покрывать manifest; неизвестные, отсутствующие или duplicate entries являются SETUP_FAILURE до runtime execution. Существующие category assignment всех migrated rows, integration sharding и Quality Graph aggregation expectations сохраняются.

Fast job MUST запускать дешёвую canonical inventory/schema/discovery validation и MUST NOT напрямую запускать `tests/Verification/verification_ci_001_test.py`. Этот test остаётся зарегистрированным в governance и full Quality Graph MUST исполнить его там ровно один раз. Governance checks как класс и их обязательность не сокращаются; меняется только место inventory validation.

## Executable examples

| Case | Input/action | Expected observable result |
|---|---|---|
| A | Миграция 427 current rows | Validator GREEN; прежние четыре значения каждой записи сохранены |
| B | `categories.json` или active reference остаётся | Contract RED |
| C | register существующего нового FILE с четырьмя valid enums | Только `suites.tsv` изменён атомарно; test ровно в одном suite/category |
| D | added canonical `tests/**` без row | prepare RED: `UNREGISTERED_TEST: <path>` до Gate 3 |
| E | duplicate/missing/invalid enum | Ненулевой exit; manifest bytes неизменны |
| F | Перестановка logical rows/filesystem results | Canonical bytes/list одинаковы |
| G | List всех categories | Полный disjoint union manifest |
| H | Full workflow | fast не запускает `verification_ci_001_test.py`; governance запускает один раз |
| I | Повреждён manifest/discovery | fast fail-closed до categories |
| J | Existing planner mapping | Прежняя category semantics сохранена без JSON |
| K | Inventory/policy diff | Не получает FAST bypass |
| L | Missing/failed Quality Graph result | Existing aggregate остаётся RED |

Expected values определены owner contract и pre-migration manifests, а не новой реализацией. Focused tests используют disposable repositories/files, не изменяют product tests и не требуют Docker, live DB или сети.
