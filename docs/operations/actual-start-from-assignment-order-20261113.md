# Фактическое начало из подписанного распоряжения — решения владельца

## Подтверждённое поведение

Поручение 13 ноября 2026: фактическая дата начала работ берётся из графы «Дата начала работ» приложения к распоряжению о закреплении монтажников. Дата выпуска документа и момент действия стройконтроля не являются этой датой. После принятия подписанного оригинала работы должны открываться автоматически; отдельное действие стройконтроля с вводом даты исключается.

Владелец сообщил: «Мы зачистим базу и загрузим объекты с нуля». Перенос прежних дат не входит в этот change. Это план следующей операции, а не выполненная очистка и не разрешение удалить данные до готовности нового сценария. В этой сессии база и исходники production не изменялись.

Режим поставки задан владельцем ранее в этой беседе: изменения на production с последующим переносом в Git, обычная последовательность гейтов не применяется. Для реализации сохраняются адресные проверки и независимый final review; отложенные gate/CI не объявляются пройденными. Root владеет спецификацией и regression tests, отдельный sol/low executor — реализацией. Пока выполнено только исследование.

## Единственная незакрытая развилка

`NEEDS_OWNER_DECISION`: если дата начала в подписанном приложении находится в будущем, требуется выбрать одно поведение:

1. Принять оригинал и автоматически открыть работы при наступлении даты.
2. Немедленно открыть работы с будущей фактической датой.
3. Отклонить будущую дату начала.

Вопрос отправлен владельцу через async input. Вариант не выбран по умолчанию. Он определяет необходимость фонового перехода, правила доступности чек-листа и проверки дат; завершённую спецификацию/реализацию до ответа не заявлять.

## Найденные границы

Исходная версия Git: `6664d5d85dc5e0812b7b4758457087a683f5644d`.

- `ProductionPdfAssignmentOrderRenderer::renderAppendixTable` печатает `installationObjectSnapshot.plannedStartDate`, полученную `MariaDbTemplateSource` из текущих данных объекта. Это не сохранённый факт даты подписанного оригинала.
- `SubmitAssignmentOrderOriginalCommand` и `OriginalMetadata` сейчас принимают `documentDate`; отдельной даты из приложения нет. Требуется её явное подтверждение при загрузке и immutable хранение на original revision. OCR не вводится; текущий план объекта не может молча заменять дату подписанного документа.
- `MariaDbConfirmedOriginalOpening` атомарно применяет состав и открывает дело, но требует переданную пользователем дату между documentDate и московским today. `ExecutionController`, карточка и чек-лист сейчас предоставляют отдельное открытие с датой.
- Original evidence имеет собственный file/DB commit/recovery protocol. При добавлении автоматического открытия нельзя объявлять общую ACID-транзакцию между уже сохранённым PDF и следующим opening commit. Новый публичный application seam должен честно отражать частичный результат и безопасно продолжать операцию при повторе; HTTP только адаптирует ввод/вывод.
- Replay/fingerprint должны учитывать дату приложения. Новый оригинал/состав после первого открытия не должен молча менять фактическое начало дела или исторические checklist/financial facts. Исправления PDF сохраняют историю и требуют явного согласованного поведения при изменении start date.
- Дата используется карточкой, общей очередью и native premium inputs; смена подписи на экране недостаточна.
- Если добавляется schema v39, охватить canonical migration catalogue, readiness, backup/restore и текущую schema contract. Не менять исторический frontier38 задним числом.

## Следующий шаг

После решения о будущей дате завершить контракт и OpenSpec artifacts, вычислить verification plan, написать root-owned RED на публичном seam и передать executor пакет. Проверки должны покрыть подтверждённую дату отдельно от documentDate/плана, автоматическое открытие, повтор после обрыва, отсутствие ручного обхода, права, stale revision, correction/new-order и согласованную future-date ветку. Локальный полный `make test`/`make verify` не запускать.

Read-only investigation выполняли root и `/root/assignment_start_executor` (gpt-5.6-sol/low). Source/tests/production implementation ещё не изменены.

## Решение о будущей дате — принято

Владелец ответил «Открыть сразу». Работа открывается немедленно, future appendix date сохраняется как actual_start_date. Планировщик будущего перехода не нужен. Прежний раздел NEEDS_OWNER_DECISION выше сохранён как история и закрыт этим решением. Контракт: `specs/ACTUAL-START-FROM-ORDER-001.md`.

## Реализация и адресные проверки

Root автор контракта, lifecycle, regression tests и current-frontier fixture adaptations. `/root/assignment_start_executor` (sol/low) реализовал database owner/migration/recovery; `/root/assignment_start_ui` (sol/low) — active Yii UI/HTTP. Все реализации завершены; независимое final review и production delivery ещё предстоят.

RED domain прошёл исходные fixture DB/schema/auth/PDF prerequisites и остановился на отсутствии migration39; metadata RED — неизвестное поле appendixStartDate; real browser RED — отсутствие поля в существующей форме. Исходные raw logs и snapshots сохранены вне Git.

GREEN: expanded domain acceptance, concurrent duplicate upload in two processes, migration/replay/incompatible CHECK rejection, canonical fresh migration, legacy opening, strict metadata, native HTTP partial/retry/denial + legacy history, real desktop/mobile browser response-loss replay, readiness/frontier, архитектура8 rules + HTTP global-call qualification. Browser screenshots проверены root: существующая форма и карточка сохранены, новая дата читаема, поля доступны на mobile.

Реальный текущий backup/restore выполнен `runtime_jobs_recovery_001_test.php` на isolated MariaDB с текущими source bytes и существующим runtime dependency image `8d6e3b67b662`; проверка сохраняет также непустой immutable start intent. Source/tests bind read-only, отдельные DB/network удалены после проверки. Пройден `PRODUCTION-JOBS-RECOVERY-001` (его исторический output label v24 не означает schema24: test/current manifest ожидают39).

Незавершённые широкие проверки не скрываются: старый `runtime_recovery_001_test.php` по умолчанию пытается historical Docker/Git wrapper, недоступный из focused image; direct run в focused image также не имел mariadb clients. При запуске с native clients его старый fault-injection ждёт внешнюю `mv`, тогда как нынешний backup использует PHP rename, поэтому collision assertion не срабатывает. Этот historical wrapper не объявляется GREEN. Текущий public backup/restore с новыми фактами отдельно прошёл указанную проверку; полный CI и обычный Gate3 owner-deferred. Локальный полный suite не запускался.

Primary evidence: `~/.local/share/fmonitor-2/actual-start-from-order-20261113/` (`executor-evidence/`, `root-ui-green.log`, `root-readiness-and-historical-recovery.log`, `recovery-current-native-client.log`, `tmp/yii-preopening-2ff8b59b00a9/`). Первое architecture failure показало10 SQL-ownership нарушений из-за database-specific owner без MariaDb имени; executor переименовал конкретный SQL adapter, не меняя whitelist/baseline, и canonical architecture-check прошёл.


A8 verification mapping уточнён на текущий `runtime_jobs_recovery_001_test.php` с literal непустым start intent, а не historical wrapper `runtime_recovery_001_test.php`; последний остаётся в существующей suite/CI consumer inventory с описанным failure, его проверки не удалены. Строгий reviewer-packager требует harness-native records для каждого mapped command; ранние focused runs сохраняли RUN_IN_PROFILE_RESULT/raw logs. Автоматизированный Gate5 package пока не получен. Независимый reviewer получает свежий harness root package + reconstructible source snapshot и полный manual evidence index; это independent final review owner emergency route, а не автоматический admission/GREEN.


## Независимое review — первый возврат

Reviewer `/root/assignment_start_final_review` (sol/low, не автор кода/tests) проверил source0616fba2bda4a6b59fbd26345118135aad517a168ff7f5b9c3eb65005a0b8242: CHANGES_REQUESTED. Полная инвентаризация: P1 неверный Yii return type после переименования concrete owner; P2 original rawBody читался до проверки installation.open. UI executor исправил оба в одном batch; root добавил реальный HTTP body-acquisition probe с denied/allowed controls. Ранний UI GREEN относится к версии до переименования и не квалифицирует исправленный candidate. Выполняется новый frozen-source focused qualification перед повторным review. Production ещё не менялся.


## Поставка — 13 ноября 2026

Независимый reviewer повторно **APPROVED** corrected source73675f16152a4ac63a70bac003d5ec08853b37179cc527c3ced7e650da12350e / executable7a84a1b39842078fbccfad9924e7201c35ad97d660283cb68c9cf050db14ed91. [Review](../../reviews/code/ACTUAL-START-FROM-ORDER-001.md). Все10 focused checks повторены на reconstructible snapshot (`final-focused-2.log`), current backup/restore — `final-recovery.log`, canonical architecture8 rules + HTTP qualification — `final-architecture.log`. Первая попытка frozen qualification с bind-mounted TMPDIR не прошла private fresh-reader prerequisite; повтор использовал штатный `/tmp`, исходники не менялись. Все неуспешные записи сохранены.

Production deployment выполнен через SSH/WSL по private reviewed-byte manifest.22 runtime-файла на host и в PHP-container совпали с baseline до записи и с reviewed hashes после записи. На время backup/migration остановлены только web и два jobs процесса, затем исходно работающие сервисы запущены обратно; PHP-FPM graceful USR2 reload. База не очищалась, старые receipts не подделывались. Existing worker image drift не исправлялся этим срезом; background jobs получают новый actual_start_date через общую БД, их код не менялся.

Private backup на сервере: `/home/antropophag/.local/share/fmonitor-2/actual-start-from-order-20261113/deploy-20261113T102341Z/`: SQL79,703,359bytes, state7,895,040bytes, secrets10,240bytes; файлы0600. Additive canonical-owner migration39 выполнена под существующим migration lock и повторена как no-op. Row counts и SHOW CREATE fingerprints всех105 прежних таблиц остались прежними; новая intent table пуста. Известный historical v14 full-run receipt gap не затрагивался; full canonical migration на старой production базе не повторяли.

Проверка запуска публикует readiness для новой схемы. Первичный внутренний probe с публичным Host вернул `ready:null` в deployment result; повтор с настроенным backend Host успешен, внешний `https://fmonitor.antropophag.ru/health/ready` и login — HTTP200. PHP/web/db/worker/scheduler healthy. `deployment-result.json` и `live-verification.json` сохраняют оба наблюдения. Новые пользовательские upload/opening действия на production ради проверки не создавались; полный сценарий доказан isolated real HTTP/browser.

Formal Gate3, exact-source full GitHub CI и automated admission остаются owner-deferred/UNKNOWN. Это выполненная scoped production delivery с независимым final review и адресными проверками, не full-CI GREEN и не merge. Суммарно final review: один возврат с двумя findings, затем исправленный candidate APPROVED. Сверх изменения даты не выполнялись ни reset данных, ни перенос в кластер.


В финальный Git checkpoint входят byte-identical reviewed implementation/tests/contract. Дополнения после frozen qualification — только эта delivery-запись, current-delivery-goal, отметки tasks и independent review record; полный список сверён сравнением с `final-source`. Новых runtime/test изменений после APPROVED и deployment не было. Ветка `codex/actual-start-from-assignment-order` сохраняет поставку отдельно от main; merge и новый CI не выполнялись в emergency route.

## PR293 integration correction — 13 November 2026

Owner explicitly requested PRs through merge; no further production deployment or data reset is implied. Initial head7835373b01066f11ed327b0fd751bb8dea4dc1f7, fullCI43439040280: fast/unit/governance passed, both integration shards and e2e failed. Complete60 REGRESSION_FAILURE entries and all failed category logs retained at ~/.local/share/fmonitor-2/pr292-293-20261113/. Aggregate verify/Quality Graph fail due to these category results; quality-results succeeded. All failures inspected before corrections.

Root authored fixture/test corrections: strict appendix metadata, immediate upload opening, retired manual actions, same-date correction/replay, retained original on opening failure, lost actual COMMIT acknowledgement, authorization/no writes, history and exact bytes, date-picker geometry moved to appendix input, independent uploader attribution and full browser lifecycle. Historical raw original library setup remains explicit only where read-model upcoming/unopened historical states are the subject. Normal HTTP fixture upload never emulates retired open. Complete schema catalogues add exactly the one new table; current recovery manifest106; exact changed preopening asset hash updated. One representative shared inspection consumer plus separately changed consumer flows are focused locally; full CI owns the complete matrix.

Real compatibility defect exposed by original transport regression: UUID1–5 admitted by original owner were rejected by the composite opening's UUID4 validator. Root added positive persisted-start HTTP loops and negative standalone loops for1/2/3/5. Independent pr_test_closure review returned one P1 missing standalone-negative control; fixed, bounded preimplementation review APPROVED. Separate sol/low pr293_transport_executor changed only MariaDbConfirmedOriginalOpening, MariaDbOriginalOpening, MariaDbOriginalStartOwner: widened UUID versions only for the persisted signed path and restored original-owner PDF classification. Existing standalone UUID4 contract remains. Root did not author product implementation. No final whole-candidate approval or fullCI GREEN is inferred from bounded review.

Focused evidence is retained in the same private directory. Initial failed attempts are preserved: metadata transitions/no-change correction fixtures and envelope expectations were corrected without bypassing assertions. Integration-profile cutover lacked public icon assets; browser-profile cutover passed with exact expected hashes. Runtime browser's new uploader tuple audit uses a fresh DB connection, matching its existing post-browser snapshot helper; its old setup connection has a short lifetime. First isolated recovery launcher observed MariaDB's temporary initialization socket too early; retry waits for TCP readiness. These are test-environment corrections, not extra production fixes. Full local suites were never run.

Post-review root boundary check added one-byte non-PDF and changed-one-byte accepted-request replay. Initial short-input fixture exposed503 instead of not_pdf422; first executor correction then exposed200 instead of409 on changed replay. Both RED logs retained as short-pdf-red.log and short-replay-red.log. Final executor correction sends every complete payload through the semantic fingerprint and common canonical guard, skipping new intent insertion only for <5 bytes prohibited by schema. Complete transport GREEN short-pdf-final.log source9da95ea58db442198a44f714723a30ced1202cef5c67ad576c435af3f6b83a34 exit0 supersedes intermediate short-pdf-green.log. Root authored tests; executor authored code; independent delta review required before push.

Integration stack includes reviewed PR292 head0c5c9147 (30-day Lax session plus exact auth and clipboard fixture corrections) before the new PR293 full run. No product/test merge conflict; only current-delivery-goal history was combined. Verification inventory retains both new test families; combined planner input includes both contracts. This allows one exact-source full run to check the final combined behavior. PR292 must merge first; no production deployment is implied.

PR292 merged as e7919de46ff1e495346ffdcf4ee37929b1b051b3 after all jobs in43447863228 succeeded. Merged main into PR293 as e944af12 with byte-identical source tree to5dc2c885, so the next full run binds current main. Run43448622563 completed with exactly3 REGRESSION_FAILURE entries: governance historical browser witness bytes, ExcelInputsAdversarial redundant manual application, and original_start non-PDF rejection expecting no storage housekeeping. Complete failed jobs and regressions retained in pr293-second-regression-inventory.json and job logs. Root test-only correction advances strict witness pin to reviewed5dc2c885 (no mapping/admission relaxation), proves latest automatic application and honest pending-third-original financial isolation through a SQL failure trigger, and permits only exact empty storage state/locks while retaining no accepted/staged/finalized PDF or business facts. No production source changes. Independent plan assessment approved witness/Excel adaptation; final delta review follows focused checks.
