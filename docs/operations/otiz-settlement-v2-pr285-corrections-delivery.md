# PR #285 — corrections delivery state

- Scope: existing PR `#285`, branch `codex/fmonitor-otiz-v2`, change `redesign-otiz-settlements-v2`, contract `OTIZ-SETTLEMENT-V2-001`.
- Audit baseline: Git head `518ce410051aac5c2c0e3a31fb8b86b265f52d46`.
- Reproduced: F01 lost Kss; F02 overlapping prebuilt drafts; F03 immediate/destructive replacement; F04 stale payment export after #257; F05 non-working XLSX/stable-recipient totals; F06 stale evidence binding.
- Corrected: canonical Kss cents; append-only entitlement/admission frontiers with v36→v37 restart-safe backfill; baseline/concurrency guards; separate replacement draft and atomic acceptance; durable #257 generations for accept/export/payment; zero-obligation denial; complete typed/styled XLSX and positive stable-recipient aggregation.
- Gate 3: approved, including supplemental test-delta reviews in `reviews/tests/OTIZ-SETTLEMENT-V2-CORRECTIONS-*`.
- Final review: `APPROVED` for source `a2714f687f828f717c335e7844040bf7f6fa13625e1d497f1b647e6b964da46f`; record `reviews/code/OTIZ-SETTLEMENT-V2-CORRECTIONS-FINAL-REVIEW.md`.
- Focused verification: all 11 mapped acceptance commands GREEN; HTTP qualification, architecture check, migration runner and recovery-forward checks GREEN. Full local `make test` / `make verify` was not run by owner decision.
- Remaining: commit/push the same branch, bind final committed source, exact-source CI, update PR evidence. No merge/deploy/backfill.

## Rereview R01–R08 — 2026-09-27

- Actual starting head: `cdca2ebd25a8c8e252083bf776a98e242899a3b9`; PR `#285` remains open on `codex/fmonitor-otiz-v2`. The prior exact-source CI run `36312063138` is green for that committed head only and does not cover this correction candidate.
- Reproduced on real database seams: R01 allocates a canonical 30→40% increment as `2,437,500 + 4,062,500` cents instead of only `6,500,000` to the installer who performed the new work; R05 lets two distinct authorized actors accept the same right under explicit `REPEATABLE READ`; portfolio year derives from the wrong date. The workbook regression stays GREEN.
- Root-authored correction candidate now specifies/tests replacement completeness and corrected attribution, admission-preserving edits and atomic refresh, zero/no-delta/blocked/UNKNOWN partitioning, erroneous-mark versus financial reversal, late knowledge, exact saved-revision XLSX metadata/styles/stable recipients, canonical plan-year and non-multiplied economy totals.
- Gate 3 rereview: independently `APPROVED` after reproducing all three controlled RED failures and closing test/setup findings.
- Initial implementation source `c15f9f5463df571a6f3cb925f2f065756f01e98e4474344b536bc999c706370a` received supplemental `CHANGES_REQUESTED`; all seven findings were then corrected, including HTTP replacement refresh/new rights, documentary prepared-at cutoff, canonical deduction/empty-draft guards, share scaling and public reversal kind. The supplemental candidate awaits a fresh exact-source binding below.
- A later supplemental review found two further gaps; the current candidate now rejects A→B replacement when copied personal facts would be silently lost, and derives immutable `acceptedBy`/`acceptedAt` from the append-only acceptance event rather than the draft creator.
- Focused verification: all 11 mapped acceptance commands are GREEN; the distinct-actor race is additionally stable across repeated runs. HTTP global-call qualification and `make architecture-check` are GREEN. Independent supplemental/final review is `APPROVED`; the exact source is owned by the active harness binding. Commit/push and new exact-source CI remain.
- Prohibited actions remain unchanged: no full local `make test`/`make verify`, merge, deploy, historical backfill or working-data mutation.

## Приёмка согласованного поведения — 2026-09-27

Продолжение в том же worktree/ветке/PR. Входной HEAD `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, worktree был чистым. GitHub непосредственно подтверждает успешный Quality Graph `36323191154` на этом HEAD; это исходное evidence, а не проверка новых исправлений. Утверждение о конкурентной отмене/утверждении в run `36321685676` отозвано автором аудита и не используется как основание правок конкурентного теста.

Прочитаны исходные поручения из Downloads; они не копируются в Git. `fmonitor-otiz-pr285-review3.md` пока отсутствовал при повторном поиске, доступный `rereview.md` относится к `cdca2ebd`. Его замечания проверяются как гипотезы. Текущее поручение владельца по #285 имеет приоритет над записью общей очереди #276.

Фактическая цепочка: canonical checklist operations + immutable attribution и documentary facts → `MariaDbNativePremiumInputs` → `OtizSettlementV2DraftBuilder` → сохранённый draft input/projection → `OtizSettlementV2::accept` (claims/frontiers/recipient obligations в транзакции) → отдельные payment/reversal facts. Portfolio/register/workbook читают сохранённые обязательства. Найденное расхождение находится до allocator: builder выбирает разные основания recipients для обычной дельты и replacement. Новый ledger/framework не требуется.

| Проверяемое замечание | Статус на входном HEAD и доказательство |
|---|---|
| Ксс теряется между builder и owner | Уже исправлено; сохраняется существующая native Kss/documentary регрессия |
| Обычный прирост платит старой бригаде | Простой монотонный сценарий уже исправлен; новая real-source проверка подтверждает B-only increment; произвольные corrections требуют отдельных work identities |
| Смена даты меняет получателей замены | Подтверждено real HTTP checklist → canonical builder → owner: A/B 65000/65000 превращаются в B=130000 |
| Замена при новом объёме/соседнем утверждении | Подтверждено: текущие delta recipients подставляются для исходных денег |
| Исправление одного выполнения меняет всю бригаду | Подтверждено: 2% A→B ошибочно переносят весь исходный пул к B |
| Неполная замена после refresh | Подтверждено: admission partition после проверки полноты удаляет второй объект, acceptance отменяет весь исходник |
| Экономика двух объектов 15000/25000 | Подтверждено: обе строки показывают 40000, используя calculationRemaining вместо objectContribution |
| stale/admission и гонки разных пользователей | Существующие исправления и тесты сохранены; адресная перепроверка обязательна, отозванный пересказ CI не используется |
| Удаление удержания/preview/сохранённое решение | Подтверждено статическим чтением UI: нет remove route/preview, решение после reload показывает pay, формы дублируются по объектам |
| XLSX payment/history и основания | Подтверждены отсутствующий payment export action, основание приложения, actor/time решения и ложный zero для неизвестной просрочки |

Авторы этой коррекции: root — контракт, числовые ожидания и тесты; отдельные `gpt-5.6-sol/low` executors — production после Gate 3; независимый reviewer — Gate 3/final. Planner сохраняет `CRITICAL`, `required_reviews=[gate3,final]`. Старые approvals не распространяются на новый source. Новый monetary test использует настоящий нормативный фонд fixture 650000 ₽, масштаб 6,5 относительно контрольного фонда 100000 ₽; нормативы ради теста не изменяются.

В прежней integration-регрессии найдено ошибочное ожидание: повторное выполнение пункта соседнего 10%-го расчёта ожидало перевод всех исходных 30% к B. Оно заменено независимым ожиданием сохранения исходных A/B и личного удержания. Число строк claims также заменено проверкой признанной суммы, поскольку количество технических строк не является денежным инвариантом. Эти изменения входят в обязательный новый Gate 3.

### Correction lifecycle и цельный candidate

Независимый reviewer `/root/acceptance_review` дважды вернул Gate 3 за неполноту проверок. После второго возврата root пересобрал всю матрицу: права/даты/полнота replacement, документарные веса, история/очередь, server preview и отсутствие записей, удаление по identity, сохранённые решения, реальные table sort/page/crosslinks, полный scope выплаты и явная отмена ошибочной отметки. Все семь findings закрыты в том же review record; `APPROVED` для source `5a4f88377e6922b507569ed6744cbb22e28315f660e607eab4385a21c8611e3d`, package `20260927T144039Z-33fb22609d`, 14 mapped checks: 7 GREEN, 7 INTENDED_RED. Полные журналы находятся в private delivery-harness, не в Git.

Реализация поручена `/root/money_seam_analysis` (canonical inputs, builder, денежный owner, экономика/реестр, workbook) и `/root/ui_coverage` (Yii routes/forms/views/assets). Оба `gpt-5.6-sol/low`, тестов не авторствуют. Gate 3 не является финальным approval реализации. Executor package `20260927T144139Z-f6010c87ad` отличается от reviewed candidate только добавленной записью независимого review; дальнейшие изменения потребуют новой точной привязки.

Дополнительные проверенные причины: старый History predicate выбирал наличие payment fact вместо итогового состояния; UI предлагал неподдержанный `financial` возврат; документарный builder подставлял равные веса вопреки `OTIZ-EXCEL-INPUTS-001` §§7–8. Контроль документарного распределения изменён на доказанный вклад 1500/2500: 1828125/3046875 коп. при общем 4875000. В caller конкурентного теста root явно передаёт `erroneous_mark`; сами гонки, actors, isolation и assertions не меняются. Это следствие согласованной семантики отмены, не отозванного пересказа CI.

| Матрица | Основная проверка цельного candidate |
|---|---|
| E01–E06 | portfolio + rights_flow (15000/25000 против 40000), HTTP acceptance economy link |
| D01–D04, C01–C05 | rights_flow, corrections/integration, editing; реальные checklist HTTP и native builder |
| H01–H03, P01–P05 | editing, HTTP acceptance, browser; native dismissal в rights_flow |
| A01–A07, R01–R04 | concurrency, corrections, rights_flow, HTTP acceptance, browser payment/reversal |
| I01–I06 | corrections/integration, HTTP acceptance/export, durable admission; producer #257 отдельно |
| U01–U06 | browser с exact sums, sort/page/crosslinks/preview/removal/reversal, HTTP history/queue |
| X01–X06 | workbook + real HTTP integration/export; проверенный legacy source/hash и saved basis |
| S01–S04 | DB/HTTP authorization/rollback/concurrency, upgrade, architecture; final review/CI после GREEN |

### Получен review3 — 2026-09-27 17:45 MSK

Файл `~/Downloads/fmonitor-otiz-pr285-review3.md` появился после начала работы и полностью прочитан. Первоначальная запись об отсутствии файла остаётся историей наблюдения, не текущим ограничением.

| Review3 | Disposition |
|---|---|
| T01 последняя отметка/дата вместо прав замены | Подтверждено canonical HTTP regression, исправляется единым per-work builder |
| T02 partition после completeness | Подтверждено настоящим owner/DB refresh→accept; исходный пакет исчезал частично |
| T03 одинаковые aggregate claim keys при замене с приростом | Подтверждено чтением старого builder/schema; добавлено реальное acceptance старых+новых прав и следующий пустой builder |
| T04 object/payable против package total | Подтверждено MariaDB oracle 15000/25000/40000 |
| T05 equal documentary/min Kss/копирование старых коэффициентов | Подтверждено; equal и historical minimum не имеют нормативного основания. Ксс берётся из текущего canonical расчёта с доказанными датами/справкой; явная замена использует исправленные основания, сохраняя прежнюю версию неизменной |
| T06 личное удержание, удаление, ошибочная отметка, saved decision | Подтверждено; покрыты real UI/owner действия и возврат в очередь |
| T07 EMPTY_CALCULATION без предметного HTTP результата | Подтверждено исходным actionCreate; добавлен real HTTP no-work и доступность старого unpaid |
| T08 ошибочный пересказ cancel/accept CI | Опровергнуто/отозвано автором по прямому сообщению владельца; не используется для изменения гонки. Актуальный начальный run36323191154 непосредственно проверен SUCCESS |

Нормативное уточнение T05: независимый oracle при **заданном** Ксс 0,5 остаётся 1828125/3046875 коп. Но реальная integration fixture затем исправляет план на 04.09 и имеет ПТО 04.09; её **текущий** канонический Ксс равен 1. Поэтому documentary obligations в этой цепочке должны быть 3656250/6093750, а replacement исходных 30% при исправленном Ксс — 19500000 до личного удержания, 19490000 после него. Прежнее утверждение 9750000 сохраняется в истории. Искусственный минимум предыдущих Ксс и слепое копирование старых коэффициентов удаляются, новый норматив не вводится. Тестовый delta направлен на независимый supplemental Gate 3.

### Кандидат реализации к финальной приёмке

Оба исполнителя завершили и заморозили production-изменения. Обычный расчёт, refresh и replacement используют идентичность конкретной работы; исправление атрибуции/нормы обнаруживается при acceptance/current payment export/payment. Независимые новые работы не останавливают старую выплату. Подтверждённая замена допускает корректную новую стоимость в обе стороны, не пересчитывая соседнее утверждение. Округление выровнено с каноническим half-up; пример 971750 коп. и Ксс0,99 даёт уменьшение9718 и выплату962032. Current legacy prefix явно передаётся owner для одинаковых источников обычного построения и проверки свежести.

Сохранённая projection содержит выбранные права и отдельные accepted/confirmed/new объёмы. Реальный HTTP XLSX проверен независимым ZIP/XML-reader: только 11 новых работ B, ровно13000000 коп., тип numeric, сохранённые sourceId/revision/date; historical export сохраняет те же строки после исправлений источников/нормы. Первоначальная ошибка namespace-атрибута в новом test reader исправлена как setup/test defect и не считается RED production-дефекта.

UI включает два раздела, semantic tables обеих группировок, server search/sort/root pages, cross-links, неизменный полный scope, personal/common preview и удаление по identity, сохранённые решения, entered-reason confirmations и ошибочную отметку без финансового возврата. Экономика использует backend approved/paid/object remaining, отдельно показывает package remaining. Последние browser snapshots находятся вне Git в `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fm2-otiz-v2-browser-190a61a91aaa`; итоговая точная привязка определяется финальным reviewer package и CI. Impeccable mechanical detector по изменённым UI-файлам вернул `[]`; root просмотрел desktop/expanded/narrow preview evidence.

Все дополнительные тестовые deltas получили независимые blob-bound Gate 3 dispositions в том же `reviews/tests/OTIZ-SETTLEMENT-V2-CORRECTIONS-GATE3-REVIEW.md`; они не подменяют финальный review production. Полный локальный suite не запускался. Финальные current head/source/checks/reviews читаются через `harness.py state`; CI расположен в [checks PR #285](https://github.com/Antropophag/fmonitor-2/pull/285/checks). Producer UI #257 и live preflight/enforcement #107 не объявляются реализованными этим PR; отсутствие реального evidence остаётся UNKNOWN. Merge/deploy/backfill/working-data mutation не выполнялись.

### Цельная коррекция семи findings финального review

Final review source `d7295c4e5ba8d30c99f1e5ba61d3c55ed77a9ac03f5d1e2ff521b643c73c7670` вернул семь замечаний, хотя 18 точных focused checks были GREEN. Root добавил их одним тестовым пакетом; независимый Gate 3 одобрил source `fc8227db0aa05ef1182d27195def2ed6516b83fa2cf8d3b2921ead4745f9f05d` (10 GREEN/4 INTENDED_RED), package `20260927T160415Z-2fdb1a21ac`.

Все семь исправлены в существующих owners: included-object issues передаются в XLSX «Контроль» с identity; lifecycle-формы уже в исходном серверном HTML находятся внутри закрытых подтверждений без выдуманных hidden reasons; нулевые preview/save удержания отклоняются; реестр отделяет предварительные деньги draft и локализует состояния; XLSX использует numeric date serials/Normal style/full-body filter; неверные даты выплаты дают `INVALID_PAYMENT_DATE` без записи; checklist использует канонические вклады пунктов, а не равное деление денег. Реальный пример трёх получателей: 200bp →67/67/66, G1300000 →435500/435500/429000 коп. через original/open/checklist/builder/DB/HTTP XLSX.

Независимое открытие новой книги через openpyxl3.1.5 подтверждает main A2 `datetime(2026,9,26)`/тип `d`, даты оснований как datetime, default style `Normal`, filter `A1:U5`; прежнее style warning отсутствует. Исполнители заморозили исправления; последний author browser artifact — `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fm2-otiz-v2-browser-c48a94e6bf8e`. Итоговый Gate 5 и CI фиксируются после новой точной привязки. Две неблокирующие рекомендации по дальнейшему упрощению read-model/preview не превращены в новый framework или отдельный реворк.

### Последние workbook findings и независимо воспроизведённая гонка

Следующий независимый final review проверил исправления семи findings, но обнаружил дублирование saved issues и отсутствие печатного mode banner в XLSX. Одобренный Gate 3 source `1ecf9145f46f0cd75a45267810a5a5eb433fa3c0c4fd6699e6e0fd4d4edd7465` покрывает оба замечания, а также current-export paid/zero state guard. Исполнители исправили их; banner теперь строка1, заголовки2, данные3+, filter начинается A2, freeze/print titles1:2. Это supersedes предыдущие координаты workbook evidence; окончательные проверки ещё требуются.

Root отдельно воспроизвёл реальную paid/cancel и paid/replacement гонку в изолированной MariaDB: разные actors и реальные соединения, REPEATABLE READ, выплата коммитится непосредственно перед object lock второй команды. Обе команды ошибочно отменили оплаченный исходник и освободили claims. Это не отозванный T08 и не вывод из старого CI. Регрессия в `settlement_v2_concurrency_001_test.php` требует `PAID_CALCULATION`, сохранения accepted/claims/payment и draft замены, отсутствия receipt отклонённой команды. Настоящий INTENDED_RED: private harness record `1790527235103126000-5bbb2b09e1c64aa38cb47a8c119c7d5c`. Тест написан root; production fix ожидает независимого supplemental Gate 3.

Supplemental Gate 3 одобрил test blob `f2b10451955320e4168789101bce8e99b1f7b474`. Отдельный executor исправил общий payment-dependency guard на текущие блокирующие чтения payment/reversal/event facts под существующей транзакцией; глобальный isolation level не менялся. Те же проверки применяются к подготовке и утверждению замены. Author concurrency/corrections/db/integration проверки GREEN. Все production-файлы заморожены для окончательного root-run и независимого Gate 5; результаты и точный CI для итогового commit публикуются также в том же PR #285, без нового delivery record.

### Итоговая независимая приёмка коррекций — 2026-09-27

Последний Gate 5 `APPROVED`: source `0f2c91038ea83778bb6ca5073eade48bc621bac3ef238b0d712a53027b6647f4`, package `20260927T165520Z-450619d8c4`, reviewer `/root/acceptance_review`. Все блокирующие findings закрыты, включая последнее UI-условие: нулевой accepted сохраняет cancel/replace с подтверждениями, но не предлагает payment/current-payment export. Root тесты и отдельные production authors сохранены. После последней проверки изменены только записи final review и этот итоговый абзац, production/test bytes неизменны.

14 mapped checks плюс native Excel inputs, navigation, runtime boundary и architecture/HTTP qualification: **18/18 GREEN** на указанном source. Exact browser record `1790528034156543000-0e396c888b9c4ee2bfeeb399b4fb8409`, artifacts `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fm2-otiz-v2-browser-e9e9ed641282`. Повторные runs выполнялись после связанных source/test corrections ради актуальной привязки; полный локальный suite не запускался. Финальный review возвращался три раза: семь conformance findings, затем два workbook findings, затем zero-lifecycle UI; все исправлены в том же candidate. Gate 3 supplements и RED остаются в существующем test-review record.

Публикуется тот же PR #285. Его актуальные HEAD, exact-source Quality Graph run и окончательный CI verdict фиксируются в описании и checks PR после push; этот pre-CI checkpoint не объявляет CI заранее успешным. `tasks.md` 4.2 остаётся незакрытой в pre-CI commit до фактического результата. Producer #257 и live enforcement #107 остаются внешними ограничениями, не GREEN. Merge/deploy/backfill/working-data mutation не выполнялись.

### Полный CI inventory и связанная коррекция

Run `36335197270`, exact HEAD `719c00cca1b3505ef60d8253c71776febbebadcc`, завершился FAILURE. До исправлений собран полный inventory: failed `e2e`, `Integration (1/2)`; `verify` и итоговый `Quality Graph` отказали как агрегаторы этих результатов. `plan`, `fast`, `unit`, `Integration (2/2)`, `governance`, `quality-results` SUCCESS; `harness` ожидаемо SKIPPED. Все failed-job причины просмотрены; новые денежные/concurrency/canonical/HTTP/XLSX проверки прошли.

Ровно три `REGRESSION_FAILURE`: (1) recovery browser не находит прежний `data-recovery-deduction`, потерянный при переписывании формы; неизменённый тест воспроизводит локальный INTENDED_RED `1790529282365341000-f4bad229581d4bee9ae4bee63b03ee74`; (2) production web cutover содержит прежние pinned hashes `pilot.css`/`otiz.js`; текущие обслуживаемые bytes совпадают с проверенными исходниками, остальные headers/routes/security/no-facts остаются без изменений; (3) historical snapshot test требует третью вкладку `/pilot/otiz/history`, хотя согласованы два раздела и совместимый redirect. Исторический read/export, запрещённые legacy writers и no-facts сохраняются; тест теперь проверяет две вкладки и настоящий 303 old-history→unified-history redirect.

Root актуализирует два орacles и добавляет все три CI consumers в mapped focused checks. Production correction ограничена восстановлением двух прежних form recovery attributes, без изменения бизнес-поведения или ожиданий recovery browser. Независимый reviewer подтвердил применимость неизменённого recovery test; complete correction и новый exact-source CI обязательны. Журналы CI сохраняются в GitHub/private harness, не в checkout. Это новый source после доказанных падений, не бессодержательный retry того же GREEN.

Коррекция завершена и независимо одобрена: Gate 5 `APPROVED` для source `c5d69ad1b3a54a55276e558a1ce7a1fc679c99b6a83b6f59b662e669da786838`, package `20260927T172641Z-9c4157cd13`, snapshot base `719c00cc`. **21/21 GREEN**: 17 mapped и прежние четыре boundary checks. Все три CI failure устранены, ожидания recovery browser не менялись. Exact browser artifacts: `fm2-otiz-v2-browser-ee1fbf8d8ceb`, `fm2-otiz-v2-recovery-704b6d7e7803`; recovery screenshot визуально проверен root. После review меняются только его MD/JSON и эта запись. Новый полный CI выполняется на новом commit; окончательный run/result закрепляется в том же PR, предыдущий FAILURE не скрывается.
