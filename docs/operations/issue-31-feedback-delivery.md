# №31 — обратная связь тестового стенда

## Authorization and authors

Поручение владельца 2026-09-14: минимальная обратная связь от актуального main до PR-ready. Ветка `codex/issue-31-feedback`, база `41573bf7` (origin/main после fetch). Начало работы около 17:58 UTC.
Root: контракт FEEDBACK-001, OpenSpec, тесты и current-frontier ожидания. `seam_inventory` (gpt-5.6-sol/low): read-only инвентаризация seams и schema consumers, без авторства spec/tests. `gate3` (gpt-5.6-sol/low): независимый test review. Executor/final review ещё не выполнены.

## Scope and necessary direct consumers

См. [контракт](../../specs/FEEDBACK-001.md) и [design](../../openspec/changes/test-stand-feedback/design.md). Две таблицы требуют canonical v25 и current recovery V25: RuntimeRecovery сравнивает точный schema/AI inventory. Исторические V22/V23/V24 profiles неизменны. Existing tests, вызывающие полный canonical catalogue, получают только новые конечные версии/таблицы; бизнес-ожидания прежних slices сохраняются. `tools/verification/categories.json` и `suites.tsv` получают только две записи новых тестов — иначе явный CI inventory отвергает незарегистрированный test. `harness_otiz_canonical_compat_001_test.php` проверяет текущий `make migrate`, потому его terminal version тоже обновляется. CI workflows/planner/harness не меняются.

## Evidence and current status

Planner CRITICAL: Gate 3 + final. Gate 3 package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T181504Z-a4161d6c3f/package.json`.
RED owner: record `1789409693897514000-0092b5d12ba54d3baff995103c161768`; отсутствует FeedbackApplication. RED browser: `1789409693897518000-73f4dd1418eb43a38f8a3e562debdfec`; отсутствующий маршрут даёт 404. Records и полные logs находятся вне checkout в delivery-harness evidence home.

Первоначальные два harness RED повторены один раз из-за неверного command-id metadata; planner ожидал null ID для legacy command shape. Это исправление привязки evidence, не повтор GREEN. OpenSpec strict validation и diff whitespace check пройдены.

Реализация/Green/PR/CI ещё не выполнены; UNKNOWN не является approval. Полный локальный make test/verify не запускался. Merge/deploy не выполняются.

## Gate 3 resolution

Первый Gate 3 вернул шесть замечаний к полноте matrix; root одним пакетом добавил populated backup/restore + replay, полный HTTP error/method matrix, browser submission/admin/return и named shells, контролируемое пересечение конкурентных команд, literal schema/privacy oracles и недостающие terminal expectations. Повторный Gate 3 APPROVED: [review](../../reviews/tests/FEEDBACK-001.md), package `20260914T182524Z-c07d6801fc`, candidate `83af759099a6b5220bf35f04e9df0771dc2873bd48561361a4e0ec6dce4dca59`. RED повторён вследствие изменённых тестов.

Checkpoint approved artifacts: `907737a2`. Executor `/root/executor` (gpt-5.6-sol/low) работает по package `20260914T182707Z-00a32b8de9`; spec/test authorship остаётся у root. Полная local suite не запускалась.

## Focused implementation verification

Application/concurrency/HTTP checks exposed fixture defects after the intended RED boundary: empty PHP arrays disappear in http_build_query (use nonempty nested arrays for HTTP 400); PreopeningFixture has no stop method (terminate its disposable server before open); recovery metadata uses binary table ordering; displayed result text shares a paragraph with actor/time (substring locator). Root corrected these test mechanics without changing contractual expected behavior. Independent delta review is required and pending.

Bounded canonical frontier passed (`1789411137566462000-cb4d66d82d264c768b64504e002228f0`). Populated backup/restore and replay command passed exit 0 (`1789411138731429000-a83c2830d2624655bb58fcf988cce3f6`), but harness applicability is STALE/UNKNOWN because source changed during the run; this is supporting evidence, not current-source GREEN. Final source will be rebound after implementation stops changing.

Frozen core evidence before UI correction: owner `1789411332486426000-5263f90fc56244f79ffcbb80c284e33e`, browser `1789411333656944000-7b1d7a038a6244a0b69d862a6693e47e`, architecture `1789411334813012000-1e9fd433ea7c4f79a94eff50ea3063c8`, populated restore `1789411347967383000-f354781b2db54d15b3478b8265f5a714`: GREEN. Root inspected all four desktop/mobile form/review screenshots. Mechanical UI detector ran once, zero findings; actual screenshot inspection found tiny multiline fields and a mobile navigation link with hidden text and no icon. Added A6 regression, real intended RED `1789411450753249000-b668049e28c347babdd811ca0fd53818`; retained owner GREEN `1789411426800362000-368a25a1e496479e9e55963b6e0dcd1c`. Proper Gate3 delta package `20260914T184435Z-50547ada31` prepared with explicit retained owner GREEN and browser RED. The initial UI RED was rebound once after verification-input changed; no test outcome relabeling or harness changes.

Gate3 delta APPROVED by independent sol/low `gate3` on `262d0104…`; no new findings. Executor resumed bounded UI correction through package `20260914T184554Z-f7ab1f1cb2`. Formatting new production code is included for readable review; no semantic expansion authorized. Any final architecture limits remain enforced.

UI batch current candidate `6315a93a…`: owner/browser/architecture GREEN records `1789411663063777000-b4d6139607d74546a39054a663d8f055`, `1789411673882484000-f34c0100ea974f5889a401a285aa6327`, `1789411688856722000-60054f65a4244a91adaf8c10474ba6ce`. Gate5 package `20260914T184941Z-61d5e7f71e` sent to fresh independent sol/low `final_review`. Root second screenshot inspection found apparent clipping despite DOM height; passed to independent reviewer rather than treating browser GREEN as visual approval.

Additional bounded checks: runtime readiness GREEN `1789411818219592000-1bc9e6c2be934b839fbe7199faa82154`; migration runner GREEN `1789411830114850000-3c170a3dc7b543758f4f00e59405b6e0`. Legacy runner/demo defaults initially failed isolated DB authentication (`1789411818219656000-3f7271cb24d544b8bbf09c21f1f15406`, `1789411818223259000-15098c976ac14b19bbdc54d9d3ad4112`); corrected test environment, no code change. Demo then exposed actual regression `1789411831286291000-0ea2988063b647b5af7b57884b674626`: ready marker25 expected,24 actual. `bin/fmonitor2-pilot-demo.php` still writes/checks24 at126/140; this direct canonical consumer correction is proven necessary and pending final review inventory.

First final review CHANGES_REQUESTED: two HIGH (clipped multiline controls, stale demo frontier) and one MEDIUM (compressed new PHP). Full findings preserved in reviews/code/FEEDBACK-001.md. Root strengthened A6 oracle to intersect the textarea with clipping/scrolling ancestor rectangles for every ordinary/admin textarea; actual browser intended RED `1789411951862951000-dbf008098dde492982c143d36776c91e`, retained owner GREEN `1789411953027931000-202a4e4e3c4a486b8b362597ae9f55b3`. Gate3 restart package `20260914T185301Z-80c0e40d37` sent independently before implementation correction. The initial own-box height assertion was insufficient; screenshot review caught that gap. No expected behavior relaxed.

Final correction batch candidate `2a9723dd…`: owner GREEN `1789412215771737000-cd9fad2c8b7f4bdebbeda532105730b2`, stronger browser GREEN `1789412228545448000-36b317d00d54425296253c5a56e2bfc6`, architecture GREEN `1789412267448520000-d0a96612e4fc4b8fbdaf59fe1db69b67`, demo full walkthrough/reset/cleanup GREEN `1789412340800218000-b4171c0d029943ffaadc4ccb3f4417a9`. Executor's demo retry `1789412251587010000-42de658f4f2a41adb43192c1d8d70e10` failed setup; root supplied correct isolated test environment and reached GREEN. Gate5 verdict-pass package `20260914T185902Z-0dfc23c1c1`; authoritative screenshots `yii-preopening-1605e64c0ab6`. OpenSpec strict valid; whitespace check clean. New PHP formatting preserved behavior and architecture ceilings; a small controller support trait owns only HTTP scalar/cursor parsing.

Final Gate5 APPROVED / UI SHIP on `2a9723dd…`; all three findings resolved, no new findings. Root restored the reviewed snapshot and compared every candidate artifact byte: only this delivery record and the appended independent code review differed; the completion checkbox for task3.1 is the additional lifecycle-only update before commit. Snapshot reconstruction base is checkpoint `907737a21e7748236ca610d84abb761fbe648322` (branch review base remains `41573bf7`).

Prepublication checkpoint after about 64 minutes: one initial Gate3 return for matrix completeness, two later approved browser-oracle deltas, one Gate5 return and approved rereview. Repeated checks above are tied to actual corrections, fixture/source binding or test-environment setup failures. No local full suite was run. PR publication and exact-head CI are the remaining delivery step at this frozen record; live PR description/checks own their eventual result, avoiding a documentation-only source change after authoritative CI. Task3.2 remains a prepublication snapshot until lifecycle reconciliation. Merge/deploy and live enforcement are not approved by this record.
