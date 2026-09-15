# Gate 1 review: BITRIX-ORDER-DOCUMENT-LINKS-001

> **Current owner checkpoint — 2026-09-15.** После owner instruction «ужми до минимального контракта» ранние sections ниже про anonymous HEAD challenge, runs/snapshots/replay/audit и recovery superseded и сохранены только как append-only history. Действующий контракт — `specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md` и current OpenSpec: HTTPS exact-origin external links, одна current projection, exact `zavnumber`, card, console и hourly existing Jobs. Возвращать HEAD/challenge или legacy/rapid-pilot compatibility в #15 запрещено. Независимые minimal Gate 1 и Gate 3 review текущего source подтвердили этот scope.

- Reviewer: independent `gpt-5.6-sol` Gate 1 agent `/root/gate1_review`
- Authors reviewed: root agent (normative specification and OpenSpec planning artifacts)
- Reviewed checkpoint: base/HEAD `b2907355b55ac9b45fd26a7fb95a4b8b4d7a1cdc` plus retained working-tree snapshot `/Users/antropophag/.local/share/fmonitor-2/issue-15/gate1-review-source`, binary patch SHA-256 `dc0d23a19af62d0b0f18af2f245d6e40ed1458460eb70ab64d9f2ff0a755d08c`
- Review scope: issue #15; `specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md`; `openspec/changes/bitrix-order-document-links/{proposal.md,design.md,tasks.md,specs/integration/bitrix-order-document-links/spec.md}`; read-only legacy oracle `../fmonitor/application/controllers/Integration.php::{create_public_link_folders,expandFolderName}`, `../fmonitor/application/controllers/Tables.php` and `../fmonitor/application/views/tables/helper/showcell.php`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — требование «не обходить авторизацию Битрикс» не сведено к проверяемому решению допуска.** Issue #15 требует сохранить ограничения доступа и не позволять ссылкам обходить авторизацию Битрикс. При этом нормативный контракт требует `disk.folder.getExternalLink`, после чего утверждает, что дальнейший доступ контролирует Битрикс (`specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md:44-46,70`), а design прямо признаёт, что такой URL может быть публичным и оставляет это operational `UNKNOWN` (`openspec/changes/bitrix-order-document-links/design.md:61-64`). Same-origin HTTPS и отсутствие credentials доказывают целостность URL, но не наличие Bitrix authentication/authorization. Поэтому acceptance допускает одновременно реализацию, которая публикует анонимно доступную ссылку, и запрет такой публикации. Зафиксировать fail-closed критерий допуска live URL/tenant (или иной подтверждаемый механизм), точный observable outcome при неподтверждённой авторизации и то, что до такой проверки sync не публикует ссылку. Если владелец сознательно принимает external public-link contract, это должно быть явным owner decision, а не `UNKNOWN`, объявляемым соответствием acceptance.

2. **HIGH — material identity снимка и exact duplicate недостаточно определены для независимого idempotency/concurrency oracle.** A4 говорит о canonicalized links, exact duplicates и material hash (`specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md:50-52`), но не определяет каноническую запись и какие из обязательных полей A3 (`source folder ID`, exact source folder name, derived order number, URL; строки 44-46) входят в identity/hash. Нельзя однозначно вывести, должен ли rename исходной папки при прежних derived keys/URL, смена URL у одного folder ID или повтор одного folder с несколькими derived keys создать новый snapshot, конфликт либо duplicate collapse. Это также не даёт вывести exact unique constraints для migration и race expectations. Перечислить canonical tuple, binary/string comparison rules для каждого поля, duplicate key, deterministic ordering и hash payload; отдельно задать ожидаемый результат source-folder ID collision с несовпадающими metadata в одной complete delivery.

3. **HIGH — append-only run не содержит полного проверяемого audit contract.** A4 требует immutable run с UUID, outcome и для failure — typed reason (`specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md:50-54`), но не определяет обязательные source/time/initiator/configuration facts. Это не покрывает Gate 1 audit requirement и продуктовый invariant, что факт имеет источник, дату, ответственного и основание (`PRODUCT.md:21-23`). Для console/integration invocation нужно явно определить сохраняемые безопасные поля (как минимум server timestamp, trigger/initiator identity или system actor, source/origin/root identity без секрета, outcome/reason, input/material identity и snapshot reference), их immutability, а также replay semantics: replay возвращает первоначальный receipt и не меняет audit timestamp/facts. Указать, какие из этих фактов переживают backup/restore.

4. **MEDIUM — typed failure contract не задаёт конечную observable taxonomy.** A1/A4 перечисляют классы ошибок и требуют typed safe failure (`specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md:30-32,52`), однако нет списка стабильных reason codes и правил приоритета, когда один delivery одновременно нарушает несколько условий; `conflict` при run-ID collision также отсутствует среди объявленных public outcomes `published|idempotent|failed` (`:22` против `:52`). Без этого RED tests будут изобретать API и причины, а executor сможет сделать несовместимый выбор. Определить публичный outcome/receipt union, стабильные non-sensitive reason codes, collision outcome и deterministic precedence либо правило агрегации. Exception/transport text должен оставаться только внутренним и не попадать в persisted/public reason.

## Подтверждённая часть

Legacy evidence описан фактически верно: `create_public_link_folders` постранично читает direct children и получает `disk.folder.getExternalLink`; ранее известный `b24id` не обновляется; `expandFolderName` приводит integer parts к `int`; таблица сопоставляется SQL-равенством `installation_drawings_folders.name = fm_maintable.zavnumber`; view превращает отображаемый `zavnumber` в ссылку; `MAX(link)` сворачивает несколько результатов. Fail-closed отличие для malformed/leading-zero ranges обозначено как осознанное усиление, а не приписано legacy.

Границы задачи соблюдены: planning не расширяет #15 на #12/#30, общий redesign интеграций, object identity, `rapid-pilot`, construction-control/checklist #40 или OTIZ/calculation #66. State mutation назначена одному application owner; complete/failed разделены; старый successful snapshot сохраняется; complete empty выделен; atomic snapshot и concurrent no-mixing заявлены; карточка сохраняет существующий authorization gate и section-level degradation. Общие verification/harness files в reviewed checkpoint не изменены.

## Required corrections

Исправить findings 1-4 согласованно в normative spec и OpenSpec artifacts, заново снять reconstructible source checkpoint и передать полный Gate 1 candidate независимому rereviewer. Gate 2 до решения этих неоднозначностей начинать нельзя.

## Gate 1 rereview — 2026-09-14

- Reviewer: fresh independent `gpt-5.6-sol` Gate 1 agent `/root/gate1_rereview`; не автор reviewed specification/OpenSpec artifacts и не первый reviewer
- Authors reviewed: root agent (исправленная normative specification и OpenSpec planning artifacts)
- Reviewed checkpoint: base/HEAD `b2907355b55ac9b45fd26a7fb95a4b8b4d7a1cdc` плюс retained working-tree snapshot `/Users/antropophag/.local/share/fmonitor-2/issue-15/gate1-rereview-source`, binary patch `source.patch` SHA-256 `3e11a06e982988f352f5bf308a07b7d90e86c8faf6d38c7a28da6bda8735b128`
- Reconstruction check: snapshot восстановлен в `/Users/antropophag/.local/share/fmonitor-2/issue-15/gate1-rereview-checkout` от указанного base; restored index содержит полный reviewed candidate
- Review scope: полный этот review record; исправленный `specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md`; все artifacts `openspec/changes/bitrix-order-document-links/`; issue #15; read-only legacy oracle `../fmonitor/application/controllers/Integration.php::{create_public_link_folders,expandFolderName}`, `../fmonitor/application/controllers/Tables.php` и `../fmonitor/application/views/tables/helper/showcell.php`
- Validation evidence: `openspec validate bitrix-order-document-links --strict` — PASS
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **HIGH — anonymous challenge admission всё ещё не задаёт однозначный body-free HTTP exchange.** `specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md:32,48` требует `bodyless probe`, говорит, что response body не читается/не хранится и запрещает любой `2xx`, но одновременно допускает same-origin redirect chain, заканчивающийся exact login path. Обычный login endpoint отвечает `200`; контракт не определяет, означает ли «заканчивающийся path» непоследованный `Location` либо фактически запрошенный финальный endpoint, и не фиксирует HEAD-only/no-response-body transfer. Поэтому одна реализация может скачать login/target response body и затем его отбросить, а другая может принять redirect на login path без проверки конечного challenge; обе смогут сослаться на текущий текст. В normative spec и согласованно в delta/design нужно зафиксировать exact probe method/redirect algorithm, запрет GET/range fallback и response-body download, а также непротиворечивый terminal status для exact login path. Acceptance должен наблюдаемо отличать запрос только заголовков от «скачал body, но не сохранил».

2. **MEDIUM — replay/collision с тем же `runId` зависит от неопределённого `canonical input`.** `specs/BITRIX-ORDER-DOCUMENT-LINKS-001.md:56` требует вернуть первоначальный receipt при повторе и ephemeral `RUN_ID_CONFLICT` при «ином canonical input», но canonical input нигде не перечислен. Material tuple/hash определён только для complete delivery; остаётся неоднозначным, считаются ли одинаковыми retries с тем же material hash, но иными origin/root audit facts, и failed retries с тем же либо иным reason/input kind. Это влияет на persisted audit, collision outcome и тестируемость replay. Перечислить canonical replay identity отдельно для complete и failed input, включая отношение к non-secret source configuration; определить binary/canonical comparison и receipt при каждом повторе.

### Closure of the original four findings

- Original finding 1: **partially closed** — fail-closed `ACCESS_POLICY_UNCONFIRMED`, anonymous/no-credential check и preservation предыдущего snapshot определены, но точный no-body redirect/challenge algorithm остаётся противоречивым.
- Original finding 2: **closed** — tuple `[sourceFolderId,sourceFolderName,orderNumber,canonicalUrl]`, binary comparison/sort, JSON hash payload, exact duplicate collapse, range rows, source-ID conflict и snapshot uniqueness определены.
- Original finding 3: **partially closed** — immutable timestamps, system actor/trigger, non-secret origin/root, outcome/reason/hash/snapshot и backup/restore определены; replay mutation запрещена, но identity повторяемого input остаётся неопределённой (finding 2 выше).
- Original finding 4: **closed** — public receipt keys/outcomes, stable safe reason codes, collision outcome, category precedence и запрет raw exception/response text определены достаточно для Gate 2 после устранения replay ambiguity.

### Confirmed scope and acceptance coverage

Legacy source/API, `fm_maintable.zavnumber` exact join, отсутствие `regnumber` fallback, несколько объектов одного заказа, empty/unavailable distinction, atomic complete snapshots, failed-run preservation, UI authorization/escaping и отсутствие file proxying прослеживаются до issue #15. Scope не расширен на #12/#30, общий integration redesign, object identity refactor, `rapid-pilot`, construction-control/checklist #40 или OTIZ/calculation #66. Reviewed candidate не меняет shared verification/harness files.

Gate 2 остаётся заблокирован до согласованного исправления двух findings и нового независимого решения по обновлённому reconstructible checkpoint.

## Gate 1 final rereview — 2026-09-14

- Reviewer: third fresh independent `gpt-5.6-sol` Gate 1 agent `/root/gate1_final_review`; не автор reviewed specification/OpenSpec artifacts и не участник двух предыдущих решений
- Authors reviewed: root agent (normative specification и OpenSpec planning artifacts с исправлениями после rereview)
- Reviewed checkpoint: base/HEAD `b2907355b55ac9b45fd26a7fb95a4b8b4d7a1cdc` плюс retained exact files `/Users/antropophag/.local/share/fmonitor-2/issue-15/gate1-final-review-source`; normative spec SHA-256 `62be4dacaf1258da922bb5fb58941becc5992b95472d9f81fb82f3b8d3a2944d`, delta spec SHA-256 `f841c9401def85caa4a462ff26b5a3a11c33cf6d696aa2d5dc2e90f3a645aeeb`, design SHA-256 `d030d8cbdd9fa5a8d6175ebf6a00a735055ca8bb80738e50cac861f241de546a`
- Reconstruction: retained tree также содержит `.openspec.yaml`, `proposal.md`, `tasks.md` и полный review record до этой секции; их SHA-256 соответственно `99f4cb4cc789c6d4e665b552c7fc401530b10f538c6cf87d334842e868590303`, `eccb1aaf600edb266a19aacaf465d3490aefa280956d5c0de7f3f0d55664f83e`, `7731c96e41adf237d76bc130c5d9c325c78e0166907ebbc8f5059238ea595b4a`, `b69ae8278ca701ad44a88f2865799e2e6380e1ba452451abcec2055aa32e97ee`
- Review scope: весь предыдущий `reviews/tests/BITRIX-ORDER-DOCUMENT-LINKS-001.md`; issue #15; normative spec; все OpenSpec artifacts; read-only legacy oracle `Integration.php::{create_public_link_folders,expandFolderName}`, exact join в `Tables.php` и link rendering в `showcell.php`
- Validation evidence: `openspec validate bitrix-order-document-links --strict` — PASS
- Verdict: `READY_FOR_OWNER_REVIEW`

### Findings

Новых blocking findings нет.

### Closure of the two rereview findings

1. **HEAD-only/no-body/manual redirect challenge — closed.** Normative A1 задаёт только anonymous `HEAD`, protocol no-body mode, отсутствие credentials/cookies/`Authorization`, запрет GET/range fallback и automatic redirects. Каждый redirect проверяется вручную; terminal `401/403` допускается, а same-origin redirect принимается без следующего запроса только когда resolved path byte-equal configured login path. Login body поэтому не запрашивается. Любой `2xx`, `405`, иной status, cross-origin/неоднозначный `Location` или превышение трёх hops даёт `ACCESS_POLICY_UNCONFIRMED`; response body не запрашивается и не передаётся callback. Delta requirement и anonymous-link scenario сохраняют те же наблюдаемые запреты.
2. **Canonical replay input — closed.** A4 отдельно определяет canonical source identity `[originScheme,originHost,effectivePort,rootFolderId]`, complete input `["complete",sourceIdentity,materialHash]` и failed input `["failed",sourceIdentity,reasonCode]`, единые canonical JSON/SHA-256 rules и исключение timestamps/attempts/raw errors. Byte-equal `inputHash` replay возвращает первоначальный receipt для любого исходного outcome без изменения audit; изменение kind/source/material/reason при том же UUID даёт ephemeral `conflict/RUN_ID_CONFLICT` без второй run row. Delta spec согласован с этим контрактом.

### Integrity of prior corrections, acceptance and scope

Предыдущие corrections остаются целостными: material tuple, binary ordering/hash, duplicate collapse, source-ID conflict, immutable run audit, конечные public outcomes/reason taxonomy и precedence определены; complete empty отличим от failure; failed run не скрывает последний successful snapshot; concurrent publication не смешивает snapshots. Legacy evidence совпадает с read-only oracle: direct children + `disk.folder.getExternalLink`, отсутствие refresh известного `b24id`, integer expansion, exact `installation_drawings_folders.name = fm_maintable.zavnumber`, отображение ссылки на `zavnumber` и произвольный legacy `MAX(link)`.

Acceptance issue #15 покрыт без подмены identity: exact byte-string `zavnumber`, отсутствие fallback на `regnumber`, несколько объектов одного заказа, несколько distinct folders, missing/empty/unavailable состояния, безопасная карточка под существующим `objects.read`, atomic refresh/idempotency и сохранение прежнего снимка при failure. Planning не расширяет scope на #12/#30, общий integration redesign, object identity refactor, `rapid-pilot`, construction-control/checklist #40 либо OTIZ/calculation #66; shared verification/harness files в reviewed candidate не изменены.

Gate 1 candidate готов к owner scope review. Live Bitrix access/challenge остаётся будущим runtime evidence и не объявляется GREEN этим design-time review.

## Verification plan completeness review — 2026-09-14

- Reviewer: independent `gpt-5.6-sol` Gate 2 mapping reviewer `/root/plan_review`; не автор specification, OpenSpec artifacts, verification input, тестов или реализации
- Reviewed checkpoint: base/HEAD `b2907355b55ac9b45fd26a7fb95a4b8b4d7a1cdc`; normative spec SHA-256 `62be4dacaf1258da922bb5fb58941becc5992b95472d9f81fb82f3b8d3a2944d`; delta spec SHA-256 `f841c9401def85caa4a462ff26b5a3a11c33cf6d696aa2d5dc2e90f3a645aeeb`; verification input SHA-256 `fd9883faec04b281db04fc1beb239f60cc78c09a103172bb379eebc73627de97`
- Generated plan: `.local/verification/bitrix-order-document-links-plan.json`, SHA-256 `fef41239a4cf2182ce98f3e4b08e3a85151bcb21def368747bb1750a68e0c167`; bound source `d87f6ba5ec451d5a69cc9d61d4dcd5d9db1b2b42a2e98af26d0f7672fdf4ac62`; graph `cfd9eaa38878f0446c7261d47ac686026a65ee77bd54610af1d310c7a42ac2f2`
- Validation evidence: `python3 tools/delivery/change-verification.py check --plan .local/verification/bitrix-order-document-links-plan.json` — `CHANGE_VERIFICATION_OK`
- Planner decision: lane `CRITICAL`, escalation `delivery-policy`, required categories `governance` and `unit`, required reviews `gate3` and `final`; these values were read from the generated plan and not assigned by the reviewer
- Verdict: `APPROVED`

### Completeness decision

Все нормативные A1–A7 и все 15 delta scenarios имеют public-seam coverage в семи неповторяющихся acceptance mappings. A1 покрывает server-owned configuration, permitted methods, pagination/budgets, typed complete/failed delivery и точный anonymous HEAD/manual-redirect challenge; A2 — byte-exact ordinary/range normalization, leading zeros, ambiguity and no-`regnumber` fallback; A3 — URL/origin/credential validation, title/escaping and no content proxy; A4 — canonical material/replay identity, immutable audit, complete-empty, atomic publication, idempotency, replacement, failure preservation and concurrency; A5 — forward migration, compatible recovery, nullable `zavnumber` import and backup/restore inventory; A6 — latest-complete exact read, multiple folders/objects and missing/empty/unavailable states; A7 — existing GET/HEAD authorization semantics, no read mutation, console composition, safe link attributes and neighboring-behavior isolation. Planned tests observe those contracts through delivery, application, migration/import/recovery, read projection, console and HTTP seams rather than private persistence output alone.

The 22 planned implementation paths cover the declared native adapter/types, sole application and persistence owners, mirror schema/import/read projection, canonical migration catalogue, v26 recovery inventory, Yii composition/controller/view and console registration. No duplicate planned paths, duplicate test consumers, unknown boundaries or unmapped normative requirement groups were found. The overlap between A3 and A7 is intentional boundary coverage (delivery URL admission versus rendered/access-controlled HTTP behavior), not duplicate acceptance identity.

Scope remains confined to issue #15: the mapping does not introduce #12/#30, a general integration redesign, object identity changes, `rapid-pilot`, construction-control/checklist #40 or OTIZ/calculation #66. Shared verification/harness implementation paths are absent; only the existing governance consumer is planner-selected. Live Bitrix access/authentication remains runtime `UNKNOWN` and is not represented as GREEN by this design-time approval.

## Gate 3 independent test review — 2026-09-14

- Reviewer: independent `gpt-5.6-sol` Gate 3 agent `/root/gate3_review`; не автор specification, OpenSpec artifacts или reviewed tests
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T201936Z-1ff6ae3c8f/package.json`
- Reviewed reconstructible source: base `b2907355b55ac9b45fd26a7fb95a4b8b4d7a1cdc` plus package snapshot patch SHA-256 `027d20f9390df3af6c03d08e5355ddb55480957d409c6dbea3115555985b1417`
- Candidate source: `647606d52ceb6d1601680b0a12c784926269945cbc820de914c160c285d91490`; executable source: `470c20c74ad01dfb2e786ce99195f5feee8a7c29c4e31f65778e90879f668433`
- RED evidence IDs: `1789417140291913000-aa11086f4c7d40ff92919fbc9c33c78c`, `1789417144923640000-e1ac395154364998ac49a5e4ccdba164`, `1789417147815422000-1f6cabb49a0743a5b16f44de4b9e2503`, `1789417150067107000-faac0419af384997a0e83dfc0f8b22a2`, `1789417153972345000-268158a5ae1548aea0cb604a28082be7`, `1789417157667167000-b7cf5f18535d44428f13be80c91b003c`, `1789417159838976000-05224af4da2d4e9db430c5370192a572`
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **HIGH — A1/A3 delivery failure contract and precedence are largely untested.** `bitrix_order_document_links_delivery_001_test.php:8-20` covers only one successful two-page response, while `bitrix_order_document_links_url_security_001_test.php:8-24` covers a small URL/challenge subset. There is no public-seam test for configuration rejection, TLS/transport/auth/API/JSON/schema errors, pagination cycles/gaps/incomplete totals, response/time/count budgets, non-folder or malformed ID/NAME, ambiguous folder/source-ID collision propagated as whole-delivery failure, or deterministic precedence when more than one validation fails. Redirect coverage also omits accepted terminal 401, three-hop boundary, extra hop, missing/duplicate `Location`, fragment/token-like query values and non-default-port mismatch. Add a table-driven delivery failure matrix asserting exact typed reason, no complete/partial rows, no secret/raw response leakage and the specified precedence.

2. **HIGH — A4 has no real concurrency or collision/material sensitivity test.** `bitrix_order_document_links_application_001_test.php:9-19` invokes one owner sequentially. It cannot catch duplicate snapshots or mixed links under concurrent same-content/different-content runs. It also does not exercise `SOURCE_ID_CONFLICT`, duplicate tuple collapse, one range folder producing multiple rows, invalid UUID/source identity, source-identity replay collision, failed-reason replay collision, canonical URL/hash worked values, or exact persisted audit/timestamp/input-hash facts. Add isolated competing connections/processes with a start barrier and independently computed expected canonical hashes/rows/receipts; assert immutable history and one whole latest snapshot.

3. **HIGH — A5 import and recovery preservation are absent.** `bitrix_order_document_links_schema_001_test.php:12-18` checks table presence, one column shape, catalogue names and one damaged index. It never runs the legacy snapshot/import seam with byte-exact nullable `zavnumber`, and never performs backup/restore of run timestamps/audit, snapshots, links, active selection, auto-increment frontiers and mirror `zavnumber`. Clean apply and compatible partial recovery, exact FKs/constraints/indexes, incompatible same-name column/table cases, existing-row preservation and runtime DML-without-DDL are likewise not demonstrated. Add executable import and restore round trips plus the schema conflict/recovery matrix.

4. **HIGH — four RED records do not reach the behavior they claim to specify.** Application, read, schema and Yii tests all stop in `BitrixOrderDocumentLinksFixture.php:12` at the same missing schema class. Thus A4, A6 and A7 have not demonstrated intended RED at their own public seams, and later fixture implementation can reveal setup errors without the reviewed evidence having established sensitivity. Capture fresh RED after the prerequisite fixture/schema is available so each test reaches and fails on its mapped missing behavior; the schema test itself may retain the schema-owner RED.

5. **MEDIUM — A7 access/no-mutation/degradation and adjacent-flow claims exceed its assertions.** `yii2_bitrix_order_document_links_001_test.php:15-21` checks guest denial, one authorized reader and HEAD body, but not inactive or authenticated no-permission actors, nonexistent/ambiguous object behavior, section-level `unavailable` with the rest of the card usable, or before/after state proving GET and HEAD do not sync/mutate. It does not exercise assignment/opening/checklist/completion/OTIZ/feedback isolation; no scoped regression command is present as Gate 2 evidence. Add the missing HTTP roles/states and mutation snapshots, and map existing bounded neighboring regression consumers without editing their owners.

6. **MEDIUM — A6 exact/binary and corrupt-read edges are incomplete.** `bitrix_order_document_links_read_001_test.php:11-18` covers the primary examples and failed-after-success, but not empty-string `zavnumber`, binary/case-sensitive near values under the declared database collation, duplicate tuple collapse/distinct ordering by folder ID then URL, successful empty snapshot after prior non-empty, or corrupt/unreadable owned projection returning `unavailable` without leaking SQL details. Add public read-seam cases for these states.

### Scope and evidence decision

The reviewed tests themselves stay within issue #15 and do not modify #12/#30, object identity, `rapid-pilot`, construction-control/checklist #40, OTIZ/calculation #66 or shared harness implementation. All seven retained commands are deterministic missing-class REDs, but that evidence does not close the behavioral coverage and intended-RED gaps above. Gate 4 is blocked pending root-authored corrections, refreshed exact-source package/evidence and a new independent Gate 3 decision.
