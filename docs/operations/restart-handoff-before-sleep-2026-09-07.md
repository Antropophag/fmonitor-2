# Restart handoff — перед сном, 7 сентября 2026

Checkpoint: 23:26–23:30 Europe/Moscow. Владелец попросил выбрать безопасный момент
для перезапуска, сохранить работу и подготовить продолжение. Текущая локальная
проверка завершена, все три агента закончили работу. PHP/browser/test workers
остановлены, disposable diagnostic DBs очищены, test DB slot свободен.
**Рабочий стенд оставлен healthy. Глобальная цель ACTIVE, без token budget;
VERIFY_OK не получен.** Это остановка по запросу владельца, не завершение цели.

## 1. Актуальный стенд и последние замечания

URL: http://127.0.0.1:8092/pilot/objects, прежний owner-admin вход.

- Installed source: `a6d3a7fee363b14ec08a643ad74b63797e2298ed`.
- Image: `fmonitor2-manual:feedback-a6d3a7f`,
  `sha256:71294ca170fc6cac7b5fc1ebe1f4182c7184cf5a69f3e265bad2e48d53d93f18`.
- Container: `fmonitor2-manual-pilot-1`, running/healthy.
- Этот source — отдельный direct child ранее установленного6aa39aa с двумя
  reviewed production fixes. Он НЕ является main stabilization HEAD.
- **Не откатывать runtime к6aa39aa по старым handoff. Photo migration19 на стенд
  не устанавливалась.**

Владелец сообщил: объект1450 в карточке «Готов к открытию», а в очереди «Требуется
распоряжение»; случайные повторные запросы входа. Оба исправления установлены:

1. Queue read owner учитывает принятый original текущего состава без отдельного
   application. Label/filter/count используют одну основу readiness. Старые
   original/application не делают новый неподписанный состав готовым. GET ничего
   не применяет и не открывает.
2. PilotCommandSession при инициализации больше не стирает LocalAuth payload.
   Auth identity/CSRF сохраняются для того же actor; mismatch регенерирует session
   и удаляет чужую авторизацию.

На exact hotfix checkout оба focused tests PASS. После deployment headless Chrome
с новым owner login подтвердил совпадение card/queue «Готов к открытию», сохранение
входа после execution page и8 read-only переходов, errors0. Позже объект1450 уже
перешёл в «Монтажные работы»: повторное чтение подтвердило совпадение карточки и
списка, правильное включение installation filter и исключение ready/needs filters.
Открытие работ проверочный скрипт не выполнял. **1450 и966 — реальные данные,
только read-only для тестов.**

См. `object1450-session-feedback-2026-09-07.md`: approvals, source/image,
backup/browser hashes и separation deployment. Private pre-deploy backup:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/backup-before-a6d3a7f/`.
SQL1,409,622 bytes; state archive714,057 bytes; manifest hashes. Volumes сохранены.
Existing private `compose.override.yaml` теперь указывает image feedback-a6d3a7f;
`preview.env` и secrets не публиковать. Старый image сохранён как
`fmonitor2-manual:rollback-6aa39aa`, но это не текущий стенд.

## 2. Source и зафиксированные пакеты

Branch: `codex/remove-pilot-work-navigation-v2`.
HEAD **перед данным handoff commit**: `6d0a56017c145e11e73d6ad882ea1eadc5e368ce`.
Последующий docs-only checkpoint commit не означает deployment.

- `795ac3e7540c16468389d882b4743526cee9f240`: photo19,16 frontier consumers,
  native list/shell/card reconciliation и независимые reviews.
- `96ec4f5`: queue/session manual fixes в main lineage; те же production bytes,
  что в отдельном установленном hotfix a6d3a7f.
- `f4cb63e`: deployment/backup/read-only proof владельческих замечаний.
- `6d0a560`: protected current E2E, shared fixture prefix support, scoped bootstrap
  prerequisites/deadlines/CSS cleanup и launcher probe corrections.

Approved records:

- `reviews/tests/INSPECTION-PHOTO-CONTENT-INDEX-019-SUPPLEMENTAL-2026-09-07.md`
- `reviews/code/INSPECTION-PHOTO-CONTENT-INDEX-019-2026-09-07.md`
- `reviews/tests/CANONICAL-FRONTIER-019-CONSUMERS-2026-09-07.md`
- `reviews/tests/RECONCILE-PILOT-QUEUE-SHELL-CARD-INDEPENDENT-2026-09-07.md`
  и corresponding code review.
- `reviews/tests/ORIGINAL-READY-QUEUE-MANUAL-2026-09-07.md` и code review.
- `reviews/tests/PILOT-SESSION-SHARED-AUTH-COMMAND-001-2026-09-07.md` и code review.
- `reviews/tests/RECONCILE-PROTECTED-PILOT-E2E-CURRENT-FLOW-2026-09-07.md`
  и code review.
- `reviews/tests/PILOT-DEMO-BOOTSTRAP-PROTECTED-SUPPLEMENTAL-2026-09-07.md`
  и code review — ограниченный CSS/setup/probe slice, НЕ весь текущий launcher WIP.

## 3. Что уже GREEN и чего не повторять без причины

Photo19 плюс16 current-frontier consumers: independent18/18 PASS. Первое падение
ОТиЗ harness было SETUP_FAILURE: подготовленная тестовая DB оставаласьv18, тогда
как контракт требует current canonical DB. После prepare v19 повтор PASS; no-op
assertion не ослаблялся. Existing historical v8 schemas/helpers сохраняются.

Native list/shell были GREEN в предыдущем handoff и не изменены в продолжении;
card теперь также focused GREEN, hash
`a618cce172ed326b7ee0d2b3d1a9db6b0dec426c01f221c0ea3a931f10b26b9b`.
См. `native-ui-verifier-card-green-2026-09-07.md`. Native public/router имеет полный
unfiltered set, ignored query и500/501; rapid adapter имеет q/status/page и50/page.
Не смешивать эти seams. Native standalone trusted-email directory и configured
local-ID admission тоже различаются; это явно записано в delta/review.

Protected E2E теперь выполняет12 retained child contracts и реальный headless
journey: selection → inline template → original/correction → distinct authorized
opener без standalone apply →41 items/7 photos/85% →ПТО+declaration/100% →reload,
fresh DB facts и HTTP original GET/HEAD bytes/hash/no-write proof.

- PHP hash: `71d02b054b42ef63483ce49d4494d21f08e4b62b7362f8eb1ea71e04a2590deb`
- Browser hash: `9193e3454f758af023d8e40499139ec9b122d1f876a906a8fd03c00bd469eae7`
- SelectionHttpFixture: `b59339b637336017306aa97ac8a23957209d86c42351a4ca4034adddacb9108f`

Chrome response.body для PDF возвращает HTML viewer wrapper: verifier прозрачно
route.fetch→fulfill передаёт настоящий ответ и проверяет exact server PDF.
Для checklist дождаться default IndexedDB auto-open, затем accepted HTTP projection
items/photos/**completedSections** до следующего раздела. Optimistic DOM и banner
сами по себе недостаточны: auto section_completed повышает revision после фото.
Ни409, ни failed operation не игнорируются/не превращаются в success.

Точный final protected test прошёл внутри bootstrap caller; caller затем падал на
своих последующих startup/CSS assertions. Это не whole-bootstrap GREEN.
`protected-current-e2e-evidence-2026-09-07.md` и scoped reviews разделяют эти факты.
Один ранний409 raw log был затёрт последующим tee; его excerpt остался в tool/session
transcript. Это честно зафиксировано, восстановленный raw/hash не выдуман. Новые
terminal logs numbered. Первичный full verify6aa log сохранён.

## 4. Текущий незавершённый WIP — ближайшая работа

**Единственный tracked WIP: `bin/fmonitor2-pilot-demo.php`**, hash
`b6c91db186aef321359464d97b4b7439df8fc5a2fcdd41a8fa399f0baf6596a1`.
Не reset/checkout/clean поверх него. Он ещё НЕ reviewed и НЕ GREEN; lint и
`git diff --check` PASS не означают functional readiness.

Терминальная причина normal demo startup на committed predecessor:
`queue401, form401, card503, foreign503`, при CSS200/HEAD200/unknown404/graphOk=true.
Legacy demoProvision готовит толькоv1–4, а current routes требуют local identity и
новые schema families. demoServe не задавал trusted local actor и продолжал ждать
старый select/option UI. Это подтверждённый setup/compatibility разрыв, не timing.

Broad WIP начал canonical19 provisioning, synthetic local users/roles/grants,
object-detail eligibility, schema19 owner metadata, exact configured-email actor
lookup и selection smoke. Но он ещё не согласован с остальными lifecycle checks,
старым expected8 table catalogue и legacy manual-registration journey assertions
в bootstrap test. Planning addendum есть в protected change design; отдельного
законченного spec/test/code approval для broader reconciliation нет.

Последний private body diagnostic на broad WIP: post-spawn fixture не успела bind
в12s; log `runtime/bootstrap-current-body-development.log`, SHA256
`5712bde169f57811c1e265f07c6ccf0f8ced26becf7487a2b9f9d93bf951d260`.
**Сначала выяснить actual exit/status/stderr и provisioning result, а не слепо
увеличивать deadline.** Проверить seed INSERT без column list после расширения
canonical workforce schema и remaining generation catalogue assumptions.

Root architecture check на broad WIP **FAIL**:
-7 новых `sql_ownership` violations в bin;
-hotspot ratchet:376→387 lines.
Log `runtime/architecture-after-owner-hotfix.log`, SHA256
`fb896f8b9845ac4658ec1341959edc822d3ce74da81e6e57c5abbaa133441a0f`.
Это относится к новому unreviewed CLI WIP, не к установленным owner fixes.
Нужен явный application owner/reuse существующих bootstrap seams для нового
provisioning; не увеличивать baseline и не маскировать SQL переносом без ownership.

Использовать tight **private body diagnostic после already-GREEN child loop**,
не повторять12 contracts при каждой setup правке и не добавлять committed skip flag.
После body GREEN — полный bootstrap caller и independent review точных bytes.
Preserve marker/nonce ownership, foreign files/DB, запреты reset/cleanup while
running, failure cleanup, exact permission denial и current original/opening flow.
Не возвращать старый UI/ручную регистрацию/применение ради тестов. Legacy assertions
нужно явно mapped retained/superseded, current workflow может доказываться обязательным
protected child; молча удалять требования нельзя.

Уже исправленные scoped bootstrap проблемы (committed6d0a560):
- оба isolated checkouts link app/public/**rapid-pilot**, current bin требует
  rapid-pilot/verify-visual-contract.php;
- cleanup unlink symlink до isDir/rmdir, без traversal общего target;
- demoHttp connection failure возвращает3-element tuple;
- CSS graph drift проверяется отдельно от общего startup failure;
- measured child deadlines: migration30.95s→60s, case import44.66s→90s,
  protected→300s, fast artifact0.41s/SHLZ4.36s оставлены25s. CSS bind12s пока прежний.
Старый bootstrap25s kill оставил orphan PHP server73406: он точно идентифицирован
и остановлен. Не возобновлять старые PID/session IDs; новый запуск — с чистого slot.

## 5. Private backup и рабочие каталоги

Резерв после safe stop:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/restart-before-sleep-20260907/`
(0700/files0600). Содержит tracked-wip.patch, standalone WIP file, head.txt,
untracked-wip.tar.gz и sha256.json.

- tracked patch SHA256: `bd2ef226589ba7904b6ada22bc8df7a3edfff81189803983ea8bf1f1373f5e6e`.
- untracked archive SHA256: `a1fb2336e18db8c5e3e0388a795fc34ebca01d277c3feb91f87df9cf65b1d3b3`.
- Основной источник — существующий worktree. **Не применять backup patch поверх него.**

Untracked `docs/architecture/audit-and-target-2026-09-07.md` появился из внешней
работы, этот поток его не создавал/не редактировал/не коммитил. Он включён в private
backup для сохранности. `.DS_Store` оставлены нетронутыми, в backup не включены.

Worktrees сохраняются:
- main `/Users/antropophag/code/fmonitor-2`;
- `/Users/antropophag/code/fmonitor-2-manual-feedback-1450` at installeda6d3a7f;
- `/Users/antropophag/code/fmonitor-2-verify-6aa39aa` — historical full FAIL;
- `/Users/antropophag/code/fmonitor-2-verify-stabilization` at795ac3e — prepared,
  но full verify НЕ запускался; перед ним перевести clean tracked checkout на
  финальный reviewed candidate SHA. Не путать его current HEAD с candidate.

TCPDF6.11.4 проверен по upstream commit
`fbbaf14cfae8fe646f154f7c530d15ec25764040`; private clone
`runtime/tcpdf-6.11.4-verified`. Prepared verify checkout уже имеет vendor из него
и autoload из rapid-pilot/tcpdf-autoload.php; git status clean. PATH включает
/opt/homebrew/bin. Для DB tests выставить explicit test env как tools/verification/run.sh;
некоторые standalone helpers имеют старый demo-default password, это не ротация
credentials. Test MariaDB localhost23306; owner DB/volumes не использовать.

## 6. Следующая последовательность и ограничения

1. Прочитать current-delivery-goal и этот handoff, сверить HEAD/status/WIP hashes и
   persistent goal. Существующую ACTIVE цель не заменять/не завершать ради checkpoint.
2. Новые owner findings — первыми. Установленныйa6d3a7f сохранять.
3. Закончить bounded demoCLI reconciliation и architecture violations; private
   feedback loop, honest mapping, independent reviews. После этого full bootstrap.
4. Commit reviewed candidate; exact clean checkout + pinned vendor; полный
   `make verify` только после устранения известных причин. Последний полный6aa39aa
   остался FAIL4 stages; новый full прогон ещё не стартовал.
5. После literal VERIFY_OK — exact image, populated backups/migration19/restart/
   golden proof с сохранением owner data; затем подготовленная CI/Quality Graph
   интеграция по исходным approvals и ограничениям. Не объявлять production ready
   по focused GREEN или одному protected journey.

Агенты: gpt-5.6-sol / low / fork none; владелец также разрешил более слабую модель
по сложности. Авторы не review свою работу. Headless Playwright, без окон владельца.
После смены календарного дня учитывать fixed synthetic2026-09-07 clock в E2E;
не исправлять тестовую дату изменением реальных данных/дат владельца.

CI publication до первого VERIFY_OK, PR10 merge, реальные Bitrix/import ограничения
сохраняются. Read-only inventory QG: local ref f07548135fe930e7a8fb9bb97271c9f05a8ebfc1;
receipt/parity/phase-B publisher/fresh Gate5 остаются. Запреты, выданные агенту только
на его read-only inventory, не являются новыми глобальными approval requirements;
перед внешними шагами сверить исходные owner approvals. Новые features/address
 enrichment — backlog. ../fmonitor read-only, shlz-ui только public exports,
primary evidence/secrets вне репозитория. Глобальная цель остаётся ACTIVE.
