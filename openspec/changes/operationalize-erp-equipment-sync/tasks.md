## 1. Delivery binding и Gate 1

- [x] 1.1 Root обновляет `docs/operations/current-delivery-goal.md`, создаёт stable normative contract/amendment к `specs/ERP-EQUIPMENT-FACTS-001.md` и delivery record для issue #12 с owner authorization, baseline `origin/main`, авторами root/executor/reviewers, stand exception и explicit no-merge/no-external-deploy; проверить ссылки и отсутствие secrets.
- [x] 1.2 Root создаёт `verification-input.json` для всех затронутых boundaries (application/source, jobs, scheduler, env, templates/generated files, readiness, migration/recovery, card), запускает `harness.py prepare`, читает все obligations/commands и устраняет unmapped acceptance до Gate 2; planner один определяет lane и `required_reviews`.

## 2. Root-authored focused RED и Gate 2

- [x] 2.1 Root добавляет exact versioned claim regression через реальный public claim/handler/worker seam для `erp.equipment-facts.sync`: baseline MUST RED на `CONFIGURATION_INVALID/JOB_HANDLER_FAILED` до ERP, а test дополнительно различает completed receipt и retryable `SOURCE_UNAVAILABLE`; сохранить bounded intended-RED evidence вне checkout.
- [x] 2.2 Root добавляет ERP source regression из утверждённого legacy oracle: fully-qualified `[1c-erp].[...]`, реальные join/filter fields, sentinel-before-MIN и safe parameters/chunks только для уникальных nonzero local `zavnumber`; baseline MUST RED на неверном SQL/global `maxRows=10000`, без live ERP и без вывода literals/secrets.
- [x] 2.3 Root добавляет public application regressions для exact unique/zero-empty/ambiguous mapping, explicit clear/absent record, chunk failure atomicity, repeated-state history idempotency и raw-number privacy; проверить intended RED только затронутых missing behaviors.
- [x] 2.4 Root добавляет runtime/deployment regressions для direct `.env`, missing/empty fail-fast, structurally-valid bad-access retry path, `local-runtime-env` allowlist, canonical/generated Dockerfile/Compose parity, full ordinary startup, jobs-aware readiness и state-preserving recovery; проверить intended RED bounded commands.
- [x] 2.5 Root добавляет scheduler/manual/card regressions: first tick после старта исполняется, same-slot не дублирует job/history, successful run заполняет read projection/last-success, protected card отображает dates/freshness и `zavnumber=0` не сопоставляется; выполнить обязательный pilot HTTP auth check при изменении `app/PilotHttp/*.php`.

## 3. Gate 3 и executor package

- [x] 3.1 Подготовить reconstructible exact-source test candidate через harness package; независимый `gpt-5.6-sol/low` reviewer выполняет planner-required Gate 3, записывает одну полную findings list/verdict в `reviews/tests/`, а root устраняет все findings и повторно получает APPROVED при необходимости.
- [x] 3.2 Root проверяет полноту candidate и готовит отдельный bounded executor role package с утверждёнными tests/specs/plan/evidence; package MUST не содержать secrets, mutable stand facts или урезанные obligations.

## 4. Executor implementation

- [x] 4.1 Отдельный `gpt-5.6-sol/low` executor регистрирует exact ERP job type/version в canonical claim/handler runtime, проводит success/failed receipts через worker и подтверждает focused public-seam tests completed/retryable outcomes.
- [x] 4.2 Executor реализует unique nonzero local candidate selection и parameterized chunked ERP reads с fully-qualified legacy joins/filters, sentinel handling и whole-batch failure; focused source/application tests подтверждают >10 000 global independence, atomicity, ambiguity и privacy.
- [x] 4.3 Executor переводит ERP password/HMAC на direct `.env`, обновляет `.env.example`, `local-runtime-env`, worker/scheduler composition и canonical templates, регенерирует Dockerfile/Compose штатным способом и подтверждает parity/fail-fast regressions без реальных values.
- [x] 4.4 Executor делает обычный pilot startup полным jobs-contour и добавляет scheduler/worker liveness/freshness в readiness; при необходимости выполняет только additive heartbeat migration и обновляет migration catalogue, fixtures, backup/restore/recovery inventories, подтверждая state-preserving tests.
- [x] 4.5 Executor исправляет safe failed-run/retry diagnostics, canonical manual/hourly execution и object-card read projection; focused tests подтверждают completed job, same-slot idempotency, last-success/card и отсутствие DSN/SQL/credentials/source rows.
- [x] 4.6 Executor запускает только planner-selected bounded local checks и релевантные architecture/generated/auth checks, сохраняет concise evidence вне checkout и возвращает complete candidate root; локальный `make test`/`make verify` MUST NOT запускаться.

## 5. Локальная operational qualification 8093

- [x] 5.1 Root/executor перед изменением снимает безопасный inventory текущего стенда и recovery readiness, не печатая secrets; обновляет ignored `.env` значениями из read-only legacy oracle, проверяет restrictive permissions и отсутствие tracked diff/secret leakage.
- [x] 5.2 State-preserving способом обновить 8093 и поднять canonical complete contour без reset/delete volumes; доказать healthy scheduler и worker через readiness и process/heartbeat evidence.
- [x] 5.3 Дождаться completed ERP run и сохранить safe receipt (run/job identity, status, timestamps, counts без raw numbers/rows); подтвердить projections и HTTP cards 1226, 1427, 2238, 2239, отсутствие mapping для 1318 и last successful sync.
- [x] 5.4 Повторить same-slot/identical-state path и доказать отсутствие второй job либо duplicate equipment history; повторно проверить сохранность database, sessions и artifacts и записать любые UNKNOWN как blocker, не GREEN.

## 6. Publication, exact-source CI и Gate 5

- [ ] 6.1 Root сверяет весь diff с spec/plan, выполняет финальные bounded checks только при изменившемся риске, создаёт meaningful commit(s), готовит exact-source harness package и убеждается, что reviewed bytes/metadata и secret scans совпадают.
- [ ] 6.2 Push branch и открыть PR с issue #12 без merge; запустить ровно один planner-selected exact-source GitHub CI run, сначала собрать полный failed-job и `REGRESSION_FAILURE` inventory, затем исследовать каждый failure до любой correction; same-source rerun допускается только с записанной причиной.
- [ ] 6.3 Если CI требует correction, вернуть изменившиеся tests/specs к нужному Gate и получить review delta; после exact-source GREEN независимый `gpt-5.6-sol/low` reviewer выполняет Gate 5 и записывает APPROVED либо полную findings list в `reviews/code/`.
- [ ] 6.4 Обновить delivery record/current goal фактическими PR, exact commit, CI URL/status, Gate 3/5 verdicts, stand state, safe successful-run receipt и списком реально заполненных object IDs; остановиться до merge/external deployment, если нет нового явного подтверждения владельца.

## 7. Done definition

- [ ] 7.1 Change считается готовой только когда все acceptance scenarios имеют executable/evidence mapping, focused local checks GREEN, required reviews APPROVED, exact-source CI GREEN, стенд 8093 автоматически выполняет hourly ERP sync с healthy jobs readiness и подтверждёнными cards, данные стенда сохранены, а PR остаётся unmerged до owner confirmation.
