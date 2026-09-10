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
