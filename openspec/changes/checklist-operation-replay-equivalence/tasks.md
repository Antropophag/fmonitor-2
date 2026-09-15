## 1. Gate 1 и verification plan

- [x] 1.1 Создать стабильный executable spec `CHECKLIST-OPERATION-REPLAY-001` с public seam, typed fingerprints, A–I matrix и no-write counts; verification: каждое delta requirement имеет observable acceptance mapping, а #130 и `item_completed` явно regression-only.
- [x] 1.2 Создать `verification-input.json`, запустить `harness.py prepare`, прочитать exact role package и все planner obligations; verification: plan fresh для актуального source, unresolved obligations отсутствуют, lane и `required_reviews` записаны без ручного выбора.

## 2. Gate 2 RED

- [x] 2.1 Добавить один focused disposable-real-DB test через существующие Yii2 checklist operation/photo HTTP endpoints; verification: healthy setup достигает normal replay branch и отдельно доказывает exact retry, different object/type/device/actor и meaningful payload conflicts для всех пяти затронутых types с independent operations/revisions/photos/files/facts counts.
- [x] 2.2 Добавить deterministic concurrent exact/conflicting contenders через public seam; verification: real unique-key race даёт один accepted fact, equivalent loser replay и conflicting loser non-success без partial writes.
- [x] 2.3 Включить существующие `item_completed` replay и #130 read-authorization regression assertions/commands без изменения их semantics; verification: H/I GREEN на base и новый focused test RED только на ID-only duplicate flaw.
- [x] 2.4 Сохранить RED evidence и получить planner-required independent Gate 3 review; verification: reviewer pin-ит exact spec/test/source, проверяет полную A–I mapping и даёт `APPROVED` до production implementation, если Gate 3 выбран.

## 3. Gate 4 minimal implementation

- [x] 3.1 Передать approved role package отдельному gpt-5.6-sol/low executor и реализовать typed canonical replay comparison только в существующем owner; verification: один production file изменён, schema/client/UI/offline/`rapid-pilot`/`item_completed` owner не затронуты.
- [x] 3.2 Использовать один comparator в normal duplicate и exception/race recovery; verification: focused RED становится GREEN и оба пути возвращают одинаковую классификацию.
- [x] 3.3 Запустить только planner-selected focused checks, affected Yii2 journey/#130/item regression, PHP lint, diff check и требуемую HTTP qualification при изменении `app/PilotHttp`; verification: все фактические команды/результаты записаны, полный local suite не запускался.

## 4. Gate 5 и PR-ready

- [x] 4.1 Подготовить reconstructible exact-source snapshot/package и получить независимый final review от неавтора implementation; verification: verdict `APPROVED`, findings resolved against exact bytes, никакой UNKNOWN не назван GREEN.
- [x] 4.2 Зафиксировать coherent commits, обновить delivery record/OpenSpec task state и запустить один exact-source GitHub CI через выбранного consumer; verification: complete failure inventory собран при любом failure, final source имеет GREEN required CI.
- [ ] 4.3 Открыть PR в `main` со scope, contract, test/review/CI evidence и остановиться; verification: harness показывает exact PR/source publication-ready, merge/deployment не выполнены.
