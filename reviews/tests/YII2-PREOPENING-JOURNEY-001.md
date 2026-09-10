# Gate 3 test review — #76 Yii2 pre-opening journey

Source reviewed: restored snapshot `/private/tmp/fmonitor-76-preopening-gate3`, base `f804f3f6fa7baa7264a51b6e503c13f48406f2d7`; snapshot patch SHA-256 `ca25d26f8b62f11e799067af960a1c7fcbc6c196056da375f3f33fdc2d28485b` (verified). Review author: independent reviewer; authored neither specification/tests nor production implementation.

Evidence checked: all ten `/tmp/76-preopening-g3-red-*.log` files are valid intended RED (nine absent Yii routes returning 404 and one absent owned logo); the supplied native fixture/proxy preflights are GREEN; inventory and CI-inventory logs are GREEN. Regenerated `.local/verification/preopening-plan.json` from this snapshot with the pinned base; `change-verification.py check` is GREEN, plan SHA-256 `a24eb4668fcd23f14f34925ede1dc574ce45940eff530448b87e1128a1739a4c`.

## Findings

1. **HIGH — the authorization matrix can regress on write routes while the suite remains green.** Locations: `tests/Yii2/yii2_preopening_authorization_001_test.php:60-81`, normative spec `specs/YII2-PREOPENING-JOURNEY-001.md:93-108`. Exact-capability revocation is tested only for selection GET, history GET, and card GET. Manager coverage is read-only. There is no exact revocation/near-match test for template POST (`assignment_order.composition.select`), initial upload (`assignment_order.original.upload`), correction (`assignment_order.original.correct`), or opening (`installation.open`), and no successful manager save/template/upload/correction/open path. An implementation could authorize writes from route visibility, a broad role, or the wrong capability and pass. **Correction:** add real HTTP positive/negative table cases for every write family, separately revoke each exact capability and install a near-match, verify FKR and manager policy, and assert zero facts/files on denial. Keep the open-only positive case.

2. **HIGH — required HTTP stale/concurrency ownership is not exercised.** Locations: `tests/Yii2/yii2_preopening_lineage_001_test.php:17-35`, `tests/Yii2/yii2_selection_input_001_test.php:19-21`, normative spec `specs/YII2-PREOPENING-JOURNEY-001.md:233-235`. The lineage test changes only the current original between GET and POST. Selection and application changes between GET and POST are absent, and no concurrent Yii HTTP submissions are issued. Native owner suites prove owner behavior but cannot detect a Yii controller that drops/rewrites expected versions, retries a command, or invokes it twice; the normative text explicitly requires the HTTP observations and says native tests do not substitute for them (`:263-265`). **Correction:** add real HTTP races/barriers (or two simultaneous requests) for selection, upload/correction, and opening as applicable; independently mutate current selection/application after rendering a form; assert the specified conflict/rejection, exact one owner invocation/fact lineage, and no duplicate facts/files.

3. **HIGH — the declared complete route/method/HEAD migration matrix has material holes.** Locations: `tests/Yii2/yii2_preopening_authorization_001_test.php:16-35`, `tests/Yii2/yii2_object_card_001_test.php:13-15`, normative route matrix `specs/YII2-PREOPENING-JOURNEY-001.md:65-87`. Only card and download have HEAD body assertions. Prepare GET/HEAD redirect, selection/search/original form/history/execution HEAD semantics, strict order/revision IDs, extra segments/encoded separators, and guest handling across POST/raw-upload families are not covered. Compatibility execution actions `apply`/`open` are required to remain callable but are never invoked. The 410 list checks only four paths and does not cover the specified physical order/appendix/signed-original artifact routes. **Correction:** make the route matrix executable: one data table covering every listed route, allowed method and `Allow`, GET/HEAD equivalence with empty HEAD bodies, strict IDs/segments, guest return, all obsolete 410 routes, and successful/denied compatibility `apply` and `open` through their existing owners.

4. **MEDIUM — template and raw-upload transport preservation is only partially sensitive.** Locations: `tests/Yii2/yii2_preopening_http_001_test.php:14-17`, `tests/Yii2/yii2_original_transport_001_test.php:12-28`, normative spec `specs/YII2-PREOPENING-JOURNEY-001.md:137-191`. Template is generated once; no second-generation/new-audit assertion or render failure proving unchanged last-success date and no partial response exists. Raw upload omits explicit Transfer-Encoding rejection, exact 20 MiB acceptance through Yii, declared-length mismatch variants, missing metadata/header cases, and the full native status/reason/Retry-After mapping. Existing old HTTP tests contain some boundary expectations but are absent from `verification-input.json`, and the new Yii seam is not exercised by them. **Correction:** add focused Yii HTTP cases for these preserved wire boundaries and template failure/repetition; add the relevant predecessor contract tests to the verification input as regression obligations where they remain applicable.

5. **MEDIUM — card/read failure and presentation requirements are under-covered.** Locations: `tests/Yii2/yii2_object_card_001_test.php:10-28`, normative spec `specs/YII2-PREOPENING-JOURNEY-001.md:114-135`. The test covers an unknown object, one partial opening tuple, and one escaped actor name. It does not distinguish missing/unimported/dangling identity (404) from corrupt linkage/source/detail tuples (503), prove absent/corrupt technical details use the unavailable notice, exercise adjusted-vs-plan finish and unknown dates, or test historical actor full-name/email/unavailable-ID fallback including an inactive author. **Correction:** add independently seeded rows for each read state and assert the exact status/text plus unchanged DB/files.

6. **MEDIUM — the browser test does not prove the stated keyboard/CSP/history/download behavior.** Locations: `tests/Yii2/preopening_browser.mjs:7-35`, normative spec `specs/YII2-PREOPENING-JOURNEY-001.md:188-191,236-237`. All controls are mouse/programmatic locator operations; history and exact download are not reached in the browser; response headers are not checked for the required script/connect CSP. In particular, a fetch-based picker/upload can be blocked because the current CSP lacks `connect-src 'self'`, while this test can fail without identifying the policy contract and does not prevent blanket CSP weakening. **Correction:** assert the exact allowed CSP directives (including `connect-src 'self'` and absence of inline/eval/worker/blob grants), operate the dialog/form with keyboard focus, visit history and download an old/current revision, and verify the received bytes and navigation on desktop/mobile.

## Verdict

**RETURN — Gate 3 does not pass.** RED validity, fixture realism, exact snapshot identity, inventory registration, package ownership assertion, atomic failure proxy, and basic journey coverage are strong. The six gaps above leave core authorization, concurrency, route/HEAD, preserved raw transport, card failure, and browser security clauses unenforced, so an incomplete or unsafe implementation could satisfy all ten tests.

## Root correction candidate — review pending

Root retained the first RETURN above and corrected all six groups together:
write capability denial/near-match and manager full journey; two independent Yii
servers for concurrent commands; stale selection/application/original forms;
read/HEAD/order/revision/guest/obsolete-route table and native compatibility
apply/open; repeated template and publication failure; raw metadata/framing,
exact20MiB and canonical native503; card identity/dates/details/historical actor;
keyboard, exact CSP, history and byte-verified downloads at mobile viewport.
No production implementation or expectation waiver was introduced.

The real native persistence-failure probe is GREEN:
/tmp/76-preopening-g3-corrected-fixture-probe-2.log. It also confirms legal setup
mutations for missing identity/corrupt detail and historical-name fallbacks.
An initial probe returned no_changes for identical correction bytes/date; root
changed the intended correction date in the HTTP and concurrent examples before
resubmission. This was fixture validation, not a production defect or valid RED.

Updated RED outputs: /tmp/76-preopening-g3-corrected-red-<test-stem>.log.
Updated inventory outputs: /tmp/76-preopening-g3-corrected-{inventory,ci-inventory}.log.
The next independent verdict and exact snapshot identity will be appended here.
# Gate 3 correction review — #76 Yii2 pre-opening journey

Source reviewed: restored snapshot `/private/tmp/fmonitor-76-preopening-gate3-corrected`, base `f804f3f6fa7baa7264a51b6e503c13f48406f2d7`; permanent snapshot patch SHA-256 `cb6c12601403a61806a2382f68d15635761837aac0d636c7c9e174e3aeaaacc2` (verified). Review author is independent and authored neither specification/tests nor production implementation. Scope was the six findings in the retained first review plus the two added test seams and any risk created by that correction.

Evidence checked: twelve `/tmp/76-preopening-g3-corrected-red-*.log` files are valid intended RED with no `SETUP_FAILURE` (eleven absent Yii route failures and the absent owned-logo assertion). `/tmp/76-preopening-g3-corrected-fixture-probe-2.log` is GREEN and proves the corrected fixture mutations plus the real native `persistence_failure` taxonomy. Both inventory logs are GREEN (`Ran 15 tests`, `OK`). I regenerated `.local/verification/preopening-plan.json` from this exact snapshot and pinned base; `change-verification.py check` is GREEN, plan SHA-256 `816cc4eedf53b08d2cd53a25f5a448c2f085f4fae6c68a64507465867d87ec67`.

## Correction assessment

1. **Authorization — resolved.** `yii2_preopening_authorization_001_test.php` now revokes and replaces with near-match capabilities for selection/template, initial upload, correction, and opening, checks raw upload under revoked identity, rejects a custom role across writes, preserves zero facts/files on denial, and retains the open-only positive case. `yii2_preopening_http_001_test.php` runs the complete write journey for both FKR 18 and manager 97, including a genuine date-changing correction and durable actor attribution.

2. **Stale state and concurrency — resolved.** `yii2_preopening_lineage_001_test.php` independently changes original, selection, and application after GET and proves stale commands preserve facts/files. New `yii2_preopening_concurrency_001_test.php` and `PreopeningConcurrentRequests.php` submit selection, initial original, correction, and opening to two independent Yii server processes with distinct authenticated sessions, then verify bounded native outcomes, public replay, and exactly one durable fact per intent. The helper sends both complete requests before reading either response and closes every socket/server resource.

3. **Routes, methods, HEAD, and compatibility — resolved.** New `yii2_preopening_routes_001_test.php` covers every read family with GET/HEAD, empty HEAD bodies, representation type, no-store and no writes; prepare redirect; canonical order/revision and encoded/extra-segment rejection; guest admission before all write/raw families; retired physical artifact routes; and successful plus denied compatibility `apply`/`open` through their existing owners. Existing method/Allow checks remain complementary.

4. **Template/raw transport — resolved.** The journey now proves every template request appends a new audit and that publication failure emits no PDF and preserves last-success facts. Yii raw tests cover missing/mismatched/overlong metadata, Transfer-Encoding and length framing, exact 20 MiB acceptance and digest, native 11-field accepted/replayed/rejected/failed envelopes, retry guidance, and persistence failure without an accepted revision. The Quality Graph input now also includes the four predecessor raw HTTP contract suites.

5. **Card degradation and presentation — resolved.** `yii2_object_card_001_test.php` distinguishes unimported/dangling/missing identity, exercises adjusted and unknown dates, absent and structurally damaged technical detail, historical full-name/email/ID fallback with an inactive author, escaping, and corrupt opening state while asserting no repair.

6. **Browser/CSP/history/download — resolved.** `preopening_browser.mjs` checks exact self-only script/connect directives and prohibited CSP expansions, uses keyboard activation in the picker and confirmation, exercises mobile correction/history, downloads and byte-compares both historical revisions, and retains whole-path, response-loss, desktop/mobile overflow, asset, logout, and independent persistence assertions. `app/YiiRuntime/WebResponse.php` is now an explicit planned path, so the required CSP change is inside the reviewed implementation boundary.

## Findings

None in the agreed correction scope or added test/helper scope.

## Verdict

**PASS — Gate 3 correction review passes.** The corrected twelve-test candidate is complete and sufficiently sensitive for the bounded Yii2 pre-opening journey. It preserves the native owners' whole mysqli boundaries, tests the new Yii transport rather than replacing it with direct SQL command assertions, and records the required adjacent regression obligations. Production implementation may proceed against this exact reviewed source.

## Дополнение root после перезапуска — pending после application

При проверке полноты Gate4 root обнаружил дефект существующего normative
требования: новое pending распоряжение наследовало готовность прежнего применённого
оригинала. В начало `yii2_preopening_lineage_001_test.php` добавлен независимый
пример через public `YiiObjectCard::read`: реальные native selection → original →
application → новое распоряжение с другим монтажником; карточка требует
распоряжение, не отдаёт confirmedOriginal и не меняет facts/private bytes.
Спецификация и прежние HTTP expectations не изменены.

RED: `php tests/Yii2/yii2_preopening_lineage_001_test.php`, exit255,
`/tmp/76-preopening-pending-lineage-red.log`: expected «Требуется распоряжение»,
actual «Готов к открытию». Все предварительные native команды и прежняя ready
карточка прошли. Это intended behavioral RED после исправления отдельной причины503
(optional legacy provenance table); отсутствие таблицы не выдаётся за этот RED.
Дополнение ожидает независимого delta Gate3; прежний PASS сохранён выше.

# Gate 3 DELTA review — #76 pending selection lineage

Источник review: восстановленный snapshot
`/private/tmp/fmonitor-76-preopening-pending-delta-gate3`, base
`036b095bce71c23188f8e62d5f2b871641fdf19f`; постоянный snapshot
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-preopening-pending-delta-gate3`,
SHA-256 patch
`105a8701f842b7dd6999abca9431938f2b4d3b430e0b19adba7519a43d287406`
(manifest и bytes проверены). Автор review независим: не писал спецификацию,
рассматриваемый тест или production implementation.

Согласованный DELTA scope — только 36 добавленных root строк
`tests/Yii2/yii2_preopening_lineage_001_test.php:8-43`. Остальные изменения
restored source являются WIP Gate 4 context и не оценивались как Gate 5.
Предыдущие исправленные Gate 3 artifacts сохранены; спецификация и прежние
expectations не менялись.

## Проверка delta

- Traceability точная. Нормативная спецификация
  `specs/YII2-PREOPENING-JOURNEY-001.md:120-123` прямо требует, чтобы новый
  pending selection не наследовал readiness старого original/application.
  Пример воспроизводит именно эту последовательность через настоящие public
  owners: accepted selection 81 → accepted original → applied application →
  `new_order` 82 с другим монтажником → `YiiObjectCard::read`.
- Public seam выбран верно. Предметные setup-переходы выполняются неизменёнными
  native application owners, а наблюдаемое ожидание проверяется через новый
  публичный read owner `YiiObjectCard::read(actorId, objectId)`, заявленный в
  спецификации. Test не подменяет пользовательскую команду прямым SQL.
- Ожидания независимы и чувствительны. До смены выбора карточка обязана быть
  «Готов к открытию»; после смены — «Требуется распоряжение» без
  `confirmedOriginal`. Это поймает как ошибочное наследование status, так и
  утечку старого opening basis при внешне правильном status.
- История и read-only поведение покрыты: снимки всех facts и private files после
  успешного нового выбора сравниваются после read. Старое application/original
  сохраняются, а read не получает возможности чинить или переписывать их.
- Изоляция и очистка достаточны: новый `PreopeningFixture`, отдельное Yii DB
  connection с DML runtime identity, закрытие обоих ресурсов в `finally`.
  Используются фиксированные UUID, actor/object/order/revision и состав.
- RED валиден. `/tmp/76-preopening-pending-lineage-red.log` завершился exit 255
  ровно на `tests/Yii2/yii2_preopening_lineage_001_test.php:36`: expected
  «Требуется распоряжение», actual «Готов к открытию». Предшествующие assertions
  selection/original/application, первоначальной ready-card и нового pending
  selection прошли; setup failure в этом результате отсутствует.
- Snapshot delta подтверждён: `git diff --numstat` показывает `36 0` только для
  рассматриваемого test-добавления, `git diff --check` чист.
- Quality Graph plan повторно сгенерирован с pinned base
  `f804f3f6fa7baa7264a51b6e503c13f48406f2d7` и текущим
  `verification-input.json`; `check` вернул `CHANGE_VERIFICATION_OK`, SHA-256
  нового plan
  `797fb4afe9cfa86672e84399069e3683a799361fb028c84260b5dd81afc96845`.
  Для контроля также восстановлен предыдущий corrected Gate 3 snapshot: его plan
  повторил сохранённый SHA-256
  `816cc4eedf53b08d2cd53a25f5a448c2f085f4fae6c68a64507465867d87ec67`.
  Между прежним и новым plans списки acceptance mappings, required categories и
  commands идентичны; verification obligations не потеряны.

## Findings

Нет findings в согласованном DELTA scope.

## Verdict

**PASS — Gate 3 DELTA проходит.** Добавленный пример точно фиксирует уже
нормативное правило pending lineage, использует public read seam, имеет
самостоятельно определённые ожидания и доказанный intended RED. Реализация может
исправлять это поведение без изменения утверждённых ожиданий; production WIP
по-прежнему требует отдельного Gate 5.

## Исправление fixture ordering — root, delta review pending

Executor обнаружил ошибку оснастки в original transport: `rows()` сортировал
`fm2_assignment_order_original_revisions` по первому столбцу (opaque revision_id),
а transport/http/lineage/browser assertions сравнивали редакции последовательно.
Лексикографический порядок случайных identities не является порядком редакций.
Исходный failure был выведен только в transcript executor; отдельного полного
log нет, поэтому он не заявляется retained intended RED.

Root изменил только выбор `ORDER BY` в `PreopeningFixture::rows()` для этой
таблицы на `root_original_id,revision_number,revision_id`. Проверки receipt IDs,
полных неизменяемых строк, дат и PDF hashes не удалены и не ослаблены.
Остальные таблицы и общий `facts()` snapshot не менялись.

Независимый от HTTP native probe selection → original → correction проверяет
полную исходную строку, номера1/2 и обе полученные identities: GREEN.
Постоянные script/output:
`/Users/antropophag/.local/state/fmonitor2/deliveries/76-preopening-20260910/76-original-lineage-order-probe.{php,log}`.
Это исправление setup nondeterminism, не новое предметное поведение и не новый
production RED. Independent delta Gate3 ещё обязателен до финального approval.

## Сохранение original UUID contract — root, delta review pending

Predecessor `OriginalUploadInput` допускает UUID versions1–5, в отличие от
selection/opening(v4). Новая OriginalMetadata ошибочно принимала толькоv4.
Root изменил requestId существующего initial-success примера на валидныйv1;
все прежние receipt/history/replay/bytes assertions сохранены.

Intended HTTP RED: `php tests/Yii2/yii2_original_transport_001_test.php`, exit255,
`/tmp/76-original-uuid-v1-red-stable.log`, line41: accepted original expected201,
actual400. Все предыдущие admission/metadata/framing cases прошли. Более ранний
запуск во время переключения controllers остановился на временном404 формы и
не считается UUID RED. Native unchanged contract разрешаетv1; production parser
не должен сужать его доv4. Вместе с fixture ordering направляется на delta Gate3.

# Независимый Gate 3 DELTA review — #76 fixture ordering и original UUID

Источник review: восстановленный exact-source checkout
`/private/tmp/fmonitor-76-preopening-fixture-uuid-gate3`, base
`036b095bce71c23188f8e62d5f2b871641fdf19f`, постоянный snapshot
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-preopening-fixture-uuid-gate3`,
SHA-256 patch `91940893cdd4f434ba17cc827f9068939e72fb0d73ba791e2e57b1c641e97093`.
Автор review не писал спецификацию, рассматриваемые тестовые изменения или
production implementation.

Scope review ограничен изменениями после уже одобренного snapshot
`76-preopening-pending-delta-gate3`: ordering в `PreopeningFixture::rows()` и
UUID существующего initial-success примера в
`yii2_original_transport_001_test.php`. Прежний 36-строчный pending-lineage тест
не изменён. Остальной production WIP не рассматривался как Gate 5.

## Проверка delta

- Traceability точная: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001` нормативно
  допускает canonical lowercase UUID versions 1–5, а
  `AssignmentOrderOriginalDataScalar::uuid()` реализует ту же унаследованную
  грамматику. Значение `22222222-2222-1222-8222-000000000001` является валидным
  UUIDv1 с допустимым variant. Отдельные v4-контракты selection/opening не
  затронуты.
- Public seam и чувствительность сохранены: изменён requestId уже существующего
  успешного real Yii raw-upload сценария. Все admission/framing проверки до него,
  canonical 11-field receipt, exact response bytes, replay, correction, history,
  download, authorization, immutable rows, dates и PDF hashes остаются на месте.
  Тест поймает именно ошибочное сужение original parser до UUIDv4.
- RED валиден: `/tmp/76-original-uuid-v1-red-stable.log`, exit 255, строка 41:
  expected HTTP 201, actual 400 на `accepted original`; предшествующие случаи
  прошли. Это behavioral RED, не setup failure.
- Fixture deterministic: для `fm2_assignment_order_original_revisions` порядок
  теперь следует предметной lineage tuple
  `root_original_id, revision_number, revision_id`; opaque случайный
  `revision_id` используется только как стабильный tie-breaker. Для всех других
  таблиц прежний `ORDER BY 1` сохранён. SQL использует только внутренний
  allowlisted suffix branch, поэтому новая строка order не принимает внешний
  ввод.
- Изменение helper не ослабляет expectations: callers по-прежнему сравнивают
  полные immutable rows/facts, identities, revision numbers, document dates,
  hashes и bytes. Native probe
  `76-original-lineage-order-probe.{php,log}` подтверждает initial/correction,
  полную исходную строку и revisions 1/2; GREEN. Transcript-only прежний
  nondeterministic failure корректно не заявлен retained RED.
- `git diff --cached --check` чист; оба PHP-файла проходят `php -l`.
- Quality Graph перегенерирован с pinned base
  `f804f3f6fa7baa7264a51b6e503c13f48406f2d7` и текущим расширенным
  `verification-input.json`; `check` вернул `CHANGE_VERIFICATION_OK`. Plan digest:
  `1614fda505dddc9eecef80df1ea032a1fbcf1e9202e50d329b14087581d0de0c`.
  Все staged production paths покрыты `planned_paths`; дополнительные paths —
  явно ожидаемые existing-capability dependencies/role DAO. В сравнении с ранее
  одобренным delta-plan digest
  `797fb4afe9cfa86672e84399069e3683a799361fb028c84260b5dd81afc96845`
  acceptance mappings идентичны (2), required categories идентичны
  (`e2e`, `governance`, `integration`, `unit`), commands идентичны (39, включая
  focused Yii/native neighbors, четыре category obligations и integration
  `make test`). Обязательства не потеряны.

## Findings

Нет findings в согласованном DELTA scope.

## Verdict

**PASS — Gate 3 DELTA проходит.** Fixture ordering является узким исправлением
детерминизма без ослабления assertions; UUIDv1-пример точно фиксирует
унаследованный original UUID v1–5 contract и имеет доказанный intended RED.
Production implementation может исправлять parser против этих ожиданий.
Production WIP остаётся Gate 4 и требует отдельного Gate 5.

## Исправление stale correction example — root, review pending

После достижения downstream original assertions найден дефект входных данных
теста: новый requestId при том же correction fingerprint обязан вернуть200
REPLAYED до проверки устаревшего target. Это прямо задано inherited
`ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:258–269`, а не новое production решение.
Прежний пример ошибочно ожидал409, изменив только requestId.

Root сохранил conflict409 assertion, изменив documentDate на2026-09-03, чтобы
намерение действительно отличалось от уже принятого correction. Дополнительно
проверяется distinct-request fingerprint replay200, вся accepted receipt с
новым echoed requestId и отсутствие новых facts/audit/private bytes.
Ни один production owner или status mapper ради этого не изменялся.

Run `php tests/Yii2/yii2_original_transport_001_test.php` после correction проходит
оба replay/stale примера и достигает следующего production failure: download
Content-Length expected327, actualNULL на line55. Log
`/tmp/76-original-semantic-replay-correction.log`; это подтверждение исправленного
setup, не новый intended production RED. Дополнение требует independent delta
Gate3 вместе с оставшимися выявленными test corrections.

## Полный grouped harness delta — root, review pending

К stale/semantic correction выше добавлены три исправления оснастки, выявленные
полным downstream HTTP/browser inventory:

- Fault proxy: на macOS SIGCHLD от завершённого Yii DB соединения прерывал
  `stream_socket_accept`, а прежний while завершал listener. Private diagnostic
  подтвердил framework503, applications0 и `Accept failed: Interrupted system call`;
  это не потерянный COMMIT. Proxy теперь повторяет accept только после отмеченного
  SIGCHLD; обычный60s timeout/ошибка завершают loop, child reap и actual ACK-loss
  protocol сохранены. Canonical test после fix достиг реального acknowledged
  COMMIT и обнаружил production422 вместо503; executor исправил mapping native
  `persistence_outcome_unknown`, после чего canonical test GREEN.
- Browser: после click/navigation Playwright терял response body correction.
  Existing interception теперь читает настоящий `route.fetch()` response до
  `route.fulfill({response: delivered})` без изменения status/headers/bytes.
  Receipt выбирается по actual posted requestId; initial lost-response branch
  сохранён. Нет production sleep, подставленного success JSON или повторной
  команды. Browser и независимый DB/bytes audit GREEN.
- Empty selection: `installerTabIds[]` появляется после выбора, пустой submitted
  input неверен. Initial HTML проверяет настоящий picker container; обязательный
  реальный input/value7001 проверяется на GET сохранённого состава вместе с
  read-only facts. Browser проверяет фактическую отправку. Dummy markup ради
  проверки не добавляется; submission contract не ослаблен.

Evidence: `/tmp/76-uncertain-diagnostic.{php,log}`,
`/tmp/76-uncertain-proxy-lifecycle-fixed.log`,
`/tmp/76-uncertain-mapping-fix.log`, `/tmp/76-browser-harness-fixed.log`.
Эти изменения root сгруппированы для одного следующего independent delta Gate3.

## Завершённый root preflight matrix — grouped delta Gate3 pending

Весь последний набор дополнений review-ится вместе, до новых production fixes.
Нормативный контракт не меняется. Дополнительно закреплены следующие уже
обязательные гарантии:

- Opening actor/time не зависят от окна8events. После настоящего opening/replay
  fixture добавляет9 более поздних по ID событий другого actor с тем же валидным
  instant, делает автора открытия inactive и меняет его имя на строку с markup.
  Другой viewer обязан видеть escaped автора и exact stored opening timestamp.
  Intended RED `76-opening-attribution-final-red.log`: GET200, actor отсутствует.
- POST selection неизвестного объекта —404 без новых case/selection/private facts.
  Intended RED `76-final-mapping-red-yii2_preopening_failures_001.log`:422 вместо404.
- Уже закреплённый инженер при недоступном application read получает503, не403.
  Fixture временно переименовывает только private application table, проверяет
  GET/HEAD, retry guidance и отсутствие repair, затем восстанавливает её.
  Intended RED `76-final-mapping-red-yii2_original_transport_001.log`:403 вместо503.
- Compatibility apply/open сохраняют rollback и503 при native persistence failure.
  Синтетические private DB triggers встроены перед прежними успешными действиями;
  flags/reasons не подменяются. Intended RED apply
  `76-final-mapping-red-yii2_preopening_routes_001.log`:422 вместо503.
- Execution body IDs не приводятся из malformed strings к допустимым identities;
  old ExecutionHttpHandler positive/revision/sequence grammar сохраняется. Added
  leading-zero/suffix/overflow cases и zero-facts assertions. Intended RED
  `76-final-shape-red-yii2_preopening_routes_001.log`: malformed apply order081
  получил303 вместо400, показывая реальное лишнее применение в private fixture.
- Selection IDs ограничены PHP integer range, пустые trailing fields отклоняются;
  form media и closed template CSRF-only body сохраняют old FreshOrderFormInput.
  Search допускает только client q/page, не client id. Intended RED
  `76-final-shape-red-yii2_selection_input_001.log`: overflow engineer422 вместо400.
- Browser дополнительно проверяет requestfailed assets и реальную загрузку всех
  объявленных stylesheet links. Это закрывает пропуск старого response-only
  observer: CSS route404 был доказан HTTP probe, несмотря на старый browser PASS.
  После production URL rule fix stylesheet-aware browser GREEN.

Все listed logs сохранены в private delivery directory. Более поздние assertions
за первым failure проверяются review полного candidate; отдельный GREEN/RED
каждого ещё не достигнутого assertion не заявляется. Три предыдущих Gate3 PASS
сохраняются историей; этот весь grouped delta ожидает независимого verdict.

# Независимый Gate 3 review — test delta #76

## Source identity

Проверен восстановленный exact source:

- checkout: `/private/tmp/fmonitor-76-preopening-final-group-gate3`;
- base/HEAD: `401a2345535a1e3c991373c7afd85acf6a2997d3`;
- snapshot: `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-preopening-final-group-gate3`;
- manifest base совпадает с checkout;
- заявленный SHA-256 patch: `31f32b4f87f412aa8154fe92869d52bc07f53dde26204a6d6e394a99e30a4dab`;
- фактический SHA-256 `source.patch`: тот же;
- заново сформированный `git diff --binary --full-index` имеет тот же SHA-256;
- `cmp` текущего полного diff и snapshot patch: полное побайтовое совпадение.

Normative spec `specs/YII2-PREOPENING-JOURNEY-001.md` относительно base не изменена.

В `tests/Yii2` относительно base изменены ровно заявленные восемь файлов:

- `preopening_browser.mjs`;
- `preopening_commit_proxy.php`;
- `yii2_original_transport_001_test.php`;
- `yii2_preopening_http_001_test.php`;
- `yii2_preopening_lineage_001_test.php`;
- `yii2_preopening_failures_001_test.php`;
- `yii2_preopening_routes_001_test.php`;
- `yii2_selection_input_001_test.php`.

Размер delta: 73 добавления, 9 удалений. Production WIP не рассматривался как Gate 5.

`vendor/` является обычным скопированным каталогом, не symlink; `vendor/yiisoft/yii2/Yii.php` присутствует. Public sibling `/private/tmp/shlz-ui` доступен.

## Проверка полного test delta

- `yii2_original_transport_001_test.php:48–53` корректно фиксирует унаследованный порядок original processing: distinct-request fingerprint replay возвращает `200 replayed` до stale-target validation, повторяет принятые evidence fields с новым request ID и не создаёт facts/audit/files. Последующий пример меняет `documentDate` на `2026-09-03`, поэтому это уже другой fingerprint и прежнее нормативное ожидание `409 stale_revision` действительно достижимо. Это соответствует `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`, а не вводит новое правило.

- `preopening_commit_proxy.php:7–20` ограниченно обрабатывает macOS `SIGCHLD`/`EINTR`: accept повторяется только когда прерывание сопровождается отмеченным `SIGCHLD`; обычный timeout или иной accept failure завершает listener. Существующий 60-секундный bound, child reap и протокол перехвата только подтверждённого opening COMMIT сохранены.

- `preopening_browser.mjs:22–38` получает настоящий ответ через `route.fetch()`, читает фактический JSON до уничтожения response body навигацией и публикует неизменённый ответ через `route.fulfill({response: delivered})`. Receipt связывается с request ID из реально отправленного `X-FMonitor-Original`. Подставленного success JSON, изменения status/headers/body или дополнительной команды нет.

- `yii2_preopening_http_001_test.php:9–12` больше не требует фиктивный пустой `installerTabIds`; первоначальная форма проверяется по реальному picker container. После сохранения проверяется настоящий submitted input `installerTabIds[]=7001`, а также отсутствие read-side mutations.

- `preopening_browser.mjs:8–14,47` теперь учитывает как HTTP failures assets, так и `requestfailed`, требует хотя бы один same-origin stylesheet и проверяет, что каждый объявленный stylesheet реально загрузился (`link.sheet !== null`).

- `yii2_preopening_lineage_001_test.php:72–86` выводит opening event за прежнее окно восьми событий девятью более поздними event IDs, деактивирует исторического автора, задаёт ему markup-containing имя и читает карточку другим viewer. Проверяются durable actor, HTML escaping, точный сохранённый `opened_at` и read-only поведение. Ожидание следует нормативному opening tuple и не восстанавливает автора из recent-event projection.

- `yii2_preopening_failures_001_test.php:11–14` проверяет POST selection для отсутствующего object как non-disclosing `404`, с неизменными selection rows, installation cases и private evidence.

- `yii2_original_transport_001_test.php:80–84` временно делает application evidence недоступным и требует для назначенного инженера `503`, а не ложный `403`, для GET и HEAD. Проверены `Retry-After: 60`, отсутствие repair/mutation и сохранность PDF; таблица гарантированно восстанавливается в `finally`.

- `yii2_preopening_routes_001_test.php:24–33` проверяет оба compatibility writer: synthetic persistence fault для apply и open должен дать `503` с retry guidance и полным rollback; успешные прежние compatibility сценарии после удаления triggers сохранены.

- `yii2_preopening_routes_001_test.php:18–20,28–29` не допускает PHP integer coercion malformed execution identities: среди примеров есть `orderId=081`, overflow, suffix, malformed revision, а для compatibility opening — leading-zero, suffix и overflow `applicationId`. Для каждого проверено отсутствие command facts.

- `yii2_selection_input_001_test.php:10–19` добавляет PHP-int overflow для engineer/installer, trailing empty form field, wrong media `415` и запрещённый search parameter `id`. Сохранены старые границы revision/list/form size и неизменность facts.

- `yii2_preopening_routes_001_test.php:21–22` закрепляет closed template body: дополнительное поле отклоняется `400`, неверный media type — `415`, в обоих случаях без template audit. Существующий успешный CSRF-only template POST остаётся в полном кандидате.

Все ожидания сопоставлены с неизменной нормативной spec и унаследованными `FreshOrderFormInput`, `ExecutionHttpHandler` и native original contracts. Новых предметных правил в test delta не обнаружено.

## QualityGraph

План перегенерирован командой с pinned base:

`f804f3f6fa7baa7264a51b6e503c13f48406f2d7`

и input:

`openspec/changes/yii2-preopening-journey/verification-input.json`.

Результат:

- `CHANGE_VERIFICATION_OK`;
- generated plan: `.local/verification/plan.json`;
- SHA-256: `55dd035c38cbf7237a650aae9c8e290f0b2d9b6f78316dc073191974953deb56`;
- 2 acceptance groups;
- 4 категории: `e2e`, `governance`, `integration`, `unit`;
- 39 команд.

Семантическое сравнение с сохранённым последним preimplementation plan `/Users/antropophag/.local/state/fmonitor2/restarts/2026-09-10-preopening/last-preimplementation-plan.json` показало полное равенство:

- acceptance mappings;
- required categories;
- command `argv`, phase и rationale.

Ни одна из 39 команд, четырёх категорий или двух acceptance groups не потеряна.

## Checked evidence

Проверены retained intended RED:

- `76-opening-attribution-final-red.log`: durable opening actor ожидался, отсутствовал;
- `76-final-mapping-red-yii2_original_transport_001.log`: ожидался `503`, получен `403`;
- `76-final-mapping-red-yii2_preopening_failures_001.log`: ожидался `404`, получен `422`;
- `76-final-mapping-red-yii2_preopening_routes_001.log`: compatibility apply ожидал `503`, получил `422`;
- `76-final-shape-red-yii2_preopening_routes_001.log`: malformed `orderId=081` ожидал `400`, получил `303`;
- `76-final-shape-red-yii2_selection_input_001.log`: overflow engineer ожидал `400`, получил `422`.

Копии в delivery directory и `/tmp`, где присутствуют обе, совпадают по наблюдаемому failure. Это intended behavioral RED, не setup failure.

Также прочитаны:

- native original lineage probe — PASS;
- первоначальный proxy diagnostic и сохранённый `EINTR`;
- lifecycle-fixed proxy run, дошедший до ожидаемого следующего production mapping failure;
- acknowledged-COMMIT-loss/exact-replay confirmation — PASS;
- browser harness correction — PASS;
- CSS route probe, доказавший прежний `404`;
- baseline/final browser confirmations, включая stylesheet-aware последний run — PASS.

Ограничение evidence сохранено явно: каждый RED log доказывает только первый достигнутый failure. Более поздние assertions в том же файле не считаются отдельно исполненными; они оценены статически в полном current candidate. DB reproduction не выполнялась. Full `make test` и CI не запускались. Выполнены только `php -l` для семи PHP-файлов, `node --check` для browser script и `git diff --check`; все прошли.

## Findings

Findings отсутствуют. Для согласованного полного восьмифайлового test delta нет замечаний с severity/location/correction.

## Verdict

**PASS — полный последний test delta #76 проходит независимый Gate 3.**

Тесты трассируются к неизменной нормативной spec, используют реальные Yii HTTP/browser и унаследованные native public seams, сохраняют независимые ожидаемые значения, чувствительны к заявленным регрессиям и имеют достаточное retained RED/harness evidence с честно обозначенными ограничениями.

Этот PASS одобряет только Gate 3 test delta. Он не означает готовность production: Gate 4 corrections, независимый Gate 5, обязательный exact-source full CI и последующая интеграция всё ещё необходимы.
## Gate5 return: contextual document affordances — root delta pending

Независимый Gate5 обнаружил одинMEDIUM: selected engineer ещё не имеет current
application и не должен видеть history/download links; form links требуют
original.read вместе с соответствующим writegrant. Сервер уже отказывает правильно.

Root добавил в original_transport один связный regression: FKR/manager без read
не видят ссылку initial/correction формы наcard/selection; после возврата grant
ссылка снова есть; selected engineer не видит doclinks до application и видит
после действительного public application. Все прежние byte/history/mutation
assertions сохранены. RED `/tmp/76-admission-links-red.log`: expected hiddenlink,
actual visiblelink приотозванном original.read. Поздние случаи статически входят
вполный candidate; отдельные выполнения не заявлены.

Плановый owner admission: AssignmentOrderOriginalAccessQuery. Raw original POST
не получает дополнительного original.read gate. Этот delta ждёт independentGate3.

## Независимый Gate 3 delta review — YII2-PREOPENING-JOURNEY-001

### Source identity

Проверен exact restored source:

- source: `/private/tmp/fmonitor-76-preopening-admission-gate3`;
- base/HEAD: `401a2345535a1e3c991373c7afd85acf6a2997d3`;
- snapshot: `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-preopening-admission-gate3`;
- manifest base совпадает;
- заявленный и фактический SHA-256 `source.patch`: `a27387d6a6c997f643b9c2481295ea5e9f1e7d11c641fe497ec29075773633e4`;
- заново сформированный `git diff --binary --full-index` имеет тот же SHA-256;
- `cmp` regenerated diff и snapshot patch: побайтовое совпадение.

Относительно `/private/tmp/fmonitor-76-preopening-gate5` изменён только `tests/Yii2/yii2_original_transport_001_test.php`: 18 добавлений, удалений и изменений существующих ожиданий нет.

### Findings

1. **MEDIUM — delta не защищает raw POST от ошибочного добавления `original.read` gate.**

   Location: `tests/Yii2/yii2_original_transport_001_test.php:6–18`, вызовы на строках 45 и 87.

   Новый `$formLinkAdmission` при отозванном `assignment_order.original.read` проверяет:

   - отсутствие form link на card/selection;
   - фактический `403` для GET формы;
   - восстановление ссылки после возврата grant.

   Это корректно покрывает UI/form admission. Однако в том же состоянии ни initial, ни correction raw POST не выполняется. Поэтому production-исправление, которое ошибочно потребует `original.read` также от raw upload POST, сможет пройти этот тест.

   Нормативный контракт различает эти поверхности: initial/correction POST требуют соответствующий `original.upload`/`original.correct`, а `original.read` дополнительно требуется именно форме (`specs/YII2-PREOPENING-JOURNEY-001.md:95–98`). Указанное ограничение «не вводить read gate на raw POST» сейчас не закреплено чувствительным ожиданием.

   **Correction:** добавить реальный положительный raw POST пример при отозванном `original.read`, но сохранённом соответствующем write grant и builtin FKR/manager policy. Он должен доказать успешный initial/correction command и ожидаемый append-only fact, отдельно от GET form/UI denial. Желательно покрыть обе write-capabilities либо обосновать, почему один параметризованный путь чувствителен к обеим.

Остальные новые проверки корректны:

- FKR 18 и manager 97 теряют form links на card/selection вместе с `original.read`;
- actual GET формы возвращает `403`;
- возвращённый grant восстанавливает ссылку;
- engineer 73 до current application не видит history/current-download links;
- после настоящего native application эти ссылки появляются;
- проверки остаются read-only;
- engineer admission основан на current application, а не только current selection;
- более поздние assertions честно рассматриваются как статически полный candidate, без заявления об отдельном выполнении.

### RED evidence

`/tmp/76-admission-links-red.log` является подходящим intended RED: выполнение достигает нового assertion, ожидает скрытую ссылку и получает `actual: true`. Это behavioral failure, не setup failure. Лог доказывает только первый достигнутый failure; последующие случаи оценены статически.

### Quality Graph

План перегенерирован планировщиком, идентичным pinned commit `f804f3f6fa7baa7264a51b6e503c13f48406f2d7`, с текущим `openspec/changes/yii2-preopening-journey/verification-input.json` и base `401a2345535a1e3c991373c7afd85acf6a2997d3`.

Результат:

- `CHANGE_VERIFICATION_OK`;
- plan: `.local/verification/plan.json`;
- SHA-256: `b2a802fca83358b9d55fc1e2f8ce351c69999ff9a3c2ae5b1a179fe92dc49105`;
- 39 commands;
- 4 категории: `e2e`, `governance`, `integration`, `unit`;
- 2 acceptance groups;
- planned/effective path содержит `app/AssignmentOrderOriginal/AssignmentOrderOriginalAccessQuery.php`.

Выполнены только regeneration/check Quality Graph, `php -l` изменённого теста и `git diff --check`; full CI/local full и production verification не запускались.

## Verdict

**RETURN — требуется одно дополнение Gate 2/3 test delta:** положительный raw POST без `original.read`, подтверждающий, что новое ограничение относится только к UI/form admission.

Это verdict только независимого Gate 3 для нового test delta. Он не является Gate 5 production approval, CI approval или подтверждением готовности к merge.
## Admission Gate3 correction — root

К возвращённому UI/form delta добавлен положительный raw-only блок для FKR18 и
manager97: после настоящего HTTP selection отзывается original.read, initial и
correction raw POST остаются201 по writegrants; GETform остаётся403. Проверяются
две immutable revisions, actor/date, сохранность первой строки и отсутствие
application/opening. Отдельный exact-body probe GREEN:
`/tmp/76-raw-without-read-probe.{php,log}`. Productionпока неизменён.

## Независимый Gate 3 correction review

### Source

- Restored source: `/private/tmp/fmonitor-76-preopening-admission-gate3-corrected`
- HEAD/base: `401a2345535a1e3c991373c7afd85acf6a2997d3`
- Snapshot manifest base совпадает.
- `source.patch` SHA-256: `d1b73b7c7b1e6c921d8e758c715fdb5be65216de34e677aef173420d7f958cc0`
- Пересозданный `git diff --binary --full-index HEAD` имеет тот же SHA-256 и побайтово совпадает со snapshot.
- Относительно предыдущего RETURN-source изменён только `tests/Yii2/yii2_original_transport_001_test.php`: 18 добавлений, без удалений или изменения прежних ожиданий.

### Evidence

Добавленный параметризованный блок для FKR 18 и manager 97 закрывает единственный RETURN finding:

- selection выполняется через настоящий Yii HTTP;
- `assignment_order.original.read` отзывается после selection;
- initial raw POST остаётся `201`;
- GET формы остаётся `403`;
- correction raw POST остаётся `201`;
- получены ровно две append-only revision;
- первая revision побайтово как DB-row не изменяется;
- actor attribution проверена для обеих revision;
- correction date проверена как `2026-09-02`;
- application не создаётся;
- opening не возникает.

Это соответствует нормативному разделению: raw POST требует соответствующего write grant, а `original.read` дополнительно требуется форме.

Проверен standalone probe `/tmp/76-raw-without-read-probe.php`; сохранённый лог сообщает:

`PASS: raw initial/correction retain write-only admission for FKR and manager`

Предыдущий `/tmp/76-admission-links-red.log` остаётся валидным behavioral RED и доказывает только первый достигнутый UI failure. Более поздние assertions отдельно исполненными не считаются.

Дополнительно:

- `php -l tests/Yii2/yii2_original_transport_001_test.php` — GREEN;
- `git diff --check HEAD` — GREEN;
- production-код не изменялся в correction delta;
- full CI и полный test-файл не запускались.

### Quality Graph

План пересчитан с:

`--base f804f3f6fa7baa7264a51b6e503c13f48406f2d7`

и текущим `verification-input.json`.

- `CHANGE_VERIFICATION_OK`
- plan SHA-256: `81a05af8ee329fe346bf4e2a763773402aca69f1001e86fb42c9d1e63e54e1e0`
- 39 команд
- 4 категории: `e2e`, `governance`, `integration`, `unit`
- 2 acceptance groups

### Findings

Findings отсутствуют. RETURN finding устранён; созданный correction-риск — нарушение append-only истории либо неявное application/opening — покрыт чувствительными проверками.

## Verdict

**PASS — исправленный admission test delta проходит независимый Gate 3.**

Это только Gate 3 approval. Не Gate 5, не full-CI approval и не подтверждение production integration.
## Admission unavailable full caller matrix — root delta pending

После второгоGate5return root пересобрал весь bounded caller matrix (design),
проверив card/selection/form/history и отдельнуюrawwrite-only поверхность.
Новый failurecase в original_transport временно переименовывает private
roles.code, затем выполняетGET/HEAD четырёхread surfaces; currentidentity/cardfacts
остаютсячитаемыми. Ожидается503везде, HEADempty, nofact/file/schema repair;
column гарантированно восстанавливается. Existingallowed/denied/rawpositives
неизменны. RED /tmp/76-admission-unavailable-matrix-red.log фиксирует все8status:
card200/200,selection503/503,form422/422,history503/503 вместо503длявсех.
Это completefailureinventory, не только первыйscalarassertion. ТребуетсяdeltaGate3.

## Независимый Gate 3 review

**Вердикт: PASS**

Findings отсутствуют.

### Source

- Restored source: `/private/tmp/fmonitor-76-preopening-admission-matrix-gate3`
- HEAD/base: `401a2345535a1e3c991373c7afd85acf6a2997d3`
- Snapshot manifest base совпадает.
- `source.patch` SHA-256: `bc34b91377d6e6daae5eb84e4eea3a8f0f4278389aa6c7cb11923f7a3d38bf1d`
- Пересозданный `git diff --binary --full-index` побайтово совпадает со snapshot.
- Относительно Gate5-return source `929eb0…` изменены только:
  - 9 строк в `tests/Yii2/yii2_original_transport_001_test.php`;
  - rebuilt caller matrix в `openspec/changes/yii2-preopening-journey/design.md`.

### Проверка теста

Новый fault-case корректно и чувствительно покрывает весь bounded read-caller matrix:

- временно делает недоступным только `roles.code`;
- сохраняет доступность активной identity и card facts;
- собирает результаты всех восьми запросов до общего assertion:
  - card GET/HEAD;
  - selection GET/HEAD;
  - form GET/HEAD;
  - history GET/HEAD;
- независимо требует `503` для каждого consumer;
- требует пустое тело HEAD;
- не допускает частичного document UI;
- проверяет отсутствие новых facts и изменений private files;
- восстанавливает колонку в `finally`.

Cleanup достаточен: восстановление защищено `finally`, а последующие role-dependent проверки дополнительно чувствительны к неуспешному возврату `roles.code`. Existing allowed, denied и raw-write-only ожидания не изменены.

RED `/tmp/76-admission-unavailable-matrix-red.log` валиден и демонстрирует именно отсутствующее поведение, а не setup failure:

- card: `200/200`;
- selection: `503/503`;
- form: `422/422`;
- history: `503/503`;
- ожидается `503` для всех восьми запросов.

Матрица соответствует `YII2-PREOPENING-JOURNEY-001`: unavailable/parity распространяется на все read consumers, тогда как raw initial/correction сохраняет отдельный write-only admission. Новых бизнес-правил design/test delta не вводит.

### Quality Graph

План заново сформирован с base `f804f3f6fa7baa7264a51b6e503c13f48406f2d7` и текущим `verification-input.json`.

- `CHANGE_VERIFICATION_OK`
- SHA-256 плана: `a22ddc02dbafa7b71fa9aa769091f4d21c158e98092fdead4d0190c347b97495`
- 39 команд
- 4 категории: `e2e`, `governance`, `integration`, `unit`
- 2 acceptance groups

Дополнительно: PHP syntax и diff check прошли. Тесты, full suite и CI не запускались; код не изменялся.

**PASS относится только к указанному Gate 3 test/design delta. Это не Gate 5, не CI approval и не подтверждение production integration.**