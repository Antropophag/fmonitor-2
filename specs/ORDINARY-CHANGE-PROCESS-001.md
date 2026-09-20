# ORDINARY-CHANGE-PROCESS-001

## Простыми словами

Обычные согласованные изменения представления, чтения и безопасного прикладного рефакторинга проходят с одним автором и одним независимым final review. Ширина CI выбирается отдельно: неполный mapping означает FULL CI, но не добавляет Gate 3. Защищённые и неопределённые чувствительные изменения остаются на Gate 3 + final.

## Actor и public seam

Actor — delivery author, root и независимый reviewer. Public seam — `python3 tools/delivery/harness.py prepare|state`, verification plan и role packages, focused evidence и существующий exact-source CI consumer. Authority — фактическая Git delta, canonical requirement, repository-owned Quality Graph ownership/oracle/inventory и planner; prose declaration не является самостоятельным допуском.

## 1. Ordinary classification

1. Поддерживаются ровно три общих класса: `PRESENTATION` — templates, styles, formatting, field display и локальное UI behavior без protected operations; `READ` — search, filters, sorting, pagination и read projections без изменения access scope, money/readiness/completion rules, writes или external effects; `APPLICATION_TEST_OR_REFACTOR` — связанные regressions, test helpers и behavior preservation без изменения test execution, admission или validity.
2. Author MUST указать class, current requirement, regression и конкретное risk rationale по фактической delta. Planner MUST сверить доступные ownership/inventory/boundary facts. Один `ORDINARY`, имя/каталог/размер файла либо наличие теста не являются достаточными.
3. Qualifying ordinary fix, small feature или refactoring возвращает одного test+implementation author и `required_reviews=["final"]`. Новый или изменённый application regression сам по себе не запрещает этот lifecycle.
4. Аналогичная задача из нового модуля MUST классифицироваться общим правилом без добавления её issue/path в policy.

## 2. Independent ceremony and CI breadth

1. Planner MUST вычислять lifecycle ceremony отдельно от CI breadth.
2. Ordinary delta сохраняет один final review при FULL CI, если FAST completeness не доказана.
3. Presentation delta вместе со связанным changed regression MAY получить FAST только при полном current ownership/oracle mapping. Selected checks MUST включать все changed tests, known affected consumers и required environment checks. Иначе выбирается FULL CI без автоматического Gate 3.
4. Missing, failed, cancelled, incomplete, stale либо UNKNOWN mandatory check MUST блокировать общий GREEN и PR-ready.

## 3. Sensitive exclusions

1. Rights/secrets, money, schema, writes/history durability, replay/concurrency, offline/sync, external effects и review/CI admission сохраняют Gate 3 + final.
2. Sensitive changed method внутри смешанного файла имеет приоритет над обычной presentation/read частью. Unknown sensitive risk требует соответствующего review и не смешивается с ordinary incomplete-mapping → FULL CI.
3. Новый sensitive diff после prepare MUST быть обнаружен через exact-source freshness/reprepare до reviewer или CI admission.
4. Изменение test runner, inventory/admission, evidence validity либо test credibility является sensitive policy change, а не ordinary test/refactor.

## 4. Requirements и corrections

1. Для ordinary task достаточно одного краткого canonical requirement source в поддержанном формате, regression, final review и delivery record. Несколько пересказов одного acceptance contract не обязательны.
2. Finding correction с regression внутри неизменного contract проходит final delta-review без нового Gate 3. Новый существенный риск или изменение исходного contract требует recompute процесса.
3. Historical approval неизменяем: новые bytes получают новый delta verdict.

## 5. Executable acceptance matrix

| Case | Input | Required result |
|---|---|---|
| A | Ordinary presentation/read/test-refactor + regression | one author, final only |
| B | Та же ordinary delta, FAST mapping incomplete | final only + FULL CI |
| C | Presentation + changed mapped regression + complete consumers/env | FAST + final only; all checks selected |
| D | Permission/write/schema/sensitive method в смешанном файле | Gate 3 + final |
| E | Sensitive delta добавлена после prepare | stale/reprepare; sensitive process |
| F | Mandatory check missing/failed/unknown | no aggregate GREEN/PR-ready |
| G | New/changed application test, execution policy unchanged | compact lifecycle remains eligible |
| H | Test execution/admission/validity change | Gate 3 + final |
| I | Ordinary unseen module/path | eligible without whitelist edit |
| J | Correction regression, unchanged contract | final delta-review only |
| K | Correction changes contract/new sensitive risk | process recomputed |
| L | Unknown sensitive risk | sensitive review; not CI-only fallback |

## 6. Historical and end-to-end proof

1. Regression MUST exercise actual `prepare → focused evidence → reviewer package → CI selection`, not only classifier helpers.
2. Representatives of all three ordinary classes MUST come from different modules. Historical deltas #187, #194 and #209 are assessed as factual examples of changed paths, applicable/inapplicable class, risks and preserved checks; they are not re-executed lifecycle claims, policy identifiers or pre-approved FAST cases.
3. At least one qualifying analogous example not used to author the rule MUST pass without adding its path or issue to policy.
4. Delivery report records before/after task classes, mandatory review and artifact counts, selected checks, negative cases and remaining limits. Token usage remains `UNKNOWN` unless measured; no inferred saving is reported.

## Boundaries

Это delivery-policy изменение. Product runtime/state, database schema, append-only history, permissions, merge authority, deployment, settings, stand и historical product implementations не меняются. Для самого этого change required reviews — Gate 3 + final; будущий ordinary shortcut к нему не применяется.
