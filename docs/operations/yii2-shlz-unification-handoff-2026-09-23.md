# Handoff — завершение визуальной унификации Yii2 / shlz-ui

Дата среза: 2026-09-23. Это продолжение change `unify-yii2-shlz-ui`, а не новый аудит или новый candidate.

## Где продолжать

- Worktree: `/Users/antropophag/code/fmonitor-2-yii2-shlz-unification`
- Branch: `codex/unify-yii2-shlz-ui`
- Production HEAD на момент подготовки handoff: `e0e46b1c7c738ba31f55b8ec9b4edad65c8a42ab`
- Base, интегрированный в candidate: `origin/main` / `f7260c0b` через merge `5bb5203e`
- Закреплённый shlz-ui: `9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b`
- Локальный стенд: `http://127.0.0.1:8093`
- Compose project: `fm2-local-timofey`
- Проверенный runtime image после `e0e46b1c`: `sha256:823f5b2bcdc424c67118a8d0db2c9989e93e65e76ca93632cd397ab3c4a7394b`
- Данные стенда сохранены. БД, volumes, users и offline queue не сбрасывались.

Исходный checkout `/Users/antropophag/code/fmonitor-2` содержит чужой WIP №157 и не должен изменяться или попадать в candidate.

## Обязательные инструкции новой сессии

Сначала прочитать:

1. `AGENTS.md`;
2. `docs/operations/current-delivery-goal.md`;
3. `PRODUCT.md`, `CONTEXT.md`;
4. `docs/development-process.md`;
5. `specs/YII2-SHLZ-VISUAL-CONTRACT-001.md`;
6. `openspec/changes/unify-yii2-shlz-ui/`;
7. этот handoff.

Затем выполнить:

```bash
python3 tools/delivery/harness.py state
git status --short --branch
git log -12 --oneline
```

Локальный полный `make test` / `make verify` запрещён owner-решением. Разрешены bounded focused checks и один exact-source GitHub CI после owner checkpoint и финального review.

## Авторство и review

- Root владеет scope/spec/tests и этим handoff.
- Production implementation выполнял отдельный executor `/root/yii2_shlz_executor` (`gpt-5.6-sol / low`).
- Исходный Gate 3 был `APPROVED` до поздних owner-directed визуальных исправлений.
- Поздний supplemental static-test review получил `CHANGES_REQUESTED`, потому что проверки были implementation-coupled и не проверяли реальную геометрию. Неподходящий static regression удалён; реальные browser assertions добавлены/обновлены.
- Для текущего exact source нужен refreshed independent Gate 3, затем отдельный independent Gate 5.
- PR не создан, CI не запускался, merge/deploy не выполнялись. Их статус `UNKNOWN`, не GREEN.

## Что реализовано в production

Общий результат:

- согласованы shell, responsive ownership и mobile bottom navigation;
- таблицы переведены на полный shlz data-list contract, плотные таблицы имеют локальный scroll;
- удалены конфликтующие серые/белые header overrides и page-owned OTIZ shell rules;
- поля и selects используют публичную pill-геометрию shlz-ui: desktop 40px / radius 20px, mobile touch variant 48px;
- устранены вложенные Field у монтажников;
- toolbars перестраиваются без переноса коротких кнопок;
- checklist, inspection, selection, object edit и payment overlays используют полные modal surface/header/body/footer композиции;
- исправлены focus entry/return, Escape/cancel, modal scroll и непрозрачные поверхности;
- справки и документарные формы получили DatePicker, file control и компактные surfaces;
- users/roles/calendar/OTIZ/feedback/secondary forms получили локальную адаптивность без глобального скрытия данных;
- generic DatePicker progressive enhancement сохраняет native no-JS fallback и передаёт серверу прежний ISO payload;
- удалён pilot-only текст обратной связи по owner request;
- исправлены owner screenshot defects, перечисленные ниже.

Ключевые production-коммиты после основной волны:

- `0a6c2f32` — initial shlz visual contracts;
- `eb8c2089` — shared visual surfaces;
- `cfda8e0e` — mobile financial readability/local scroll;
- `15a47086` — legacy user link forms;
- `868ebfb8` — dense surfaces containment;
- `c75763c0` — final mobile surface defects;
- `0e5df8bd` — construction filters on mobile;
- `a1a49fbd` — public DatePickers, shell/sidebar, table header cleanup;
- `d5512e73` — field geometry delegated to public shlz-ui;
- `a2975936` — stable DatePicker fallback and OTIZ local scroll;
- `5f0c4e6a` — mobile OTIZ action/FAB/nav collision fix;
- `a308facc` — feedback pilot-only copy removal;
- `d2a9213c` — checklist modal public close control;
- `b97d0897` — object edit modal header alignment;
- `e0e46b1c` — complete 280px shared DatePicker popover and white contextual actual-start surface.

Root browser-test correction:

- `f56aec4a` — public DatePicker interaction and pinned field geometry expectations in affected journeys.

## Последние owner screenshot corrections

### Feedback

Удалены:

- «Не указывайте пароли и содержимое документов. Сохраняются описание, безопасный адрес страницы, ваш ID и версия приложения.»
- слова «на тестовом стенде».

Подзаголовок теперь: «Расскажите о проблеме или пожелании».

### Checklist installer modal close

Причина: кнопка `data-installer-cancel` не имела `.shlz-modal__close` и отображалась крупным локальным rounded square.

Исправление: публичная 40×40 close composition, radius 100px, доступное имя, удалён competing local CSS.

Проверенный screenshot:

`/Users/antropophag/.local/share/fmonitor-2/checklist-installer-close-final.png`

### Object edit modal close

Причина: `.shlz-modal__title` был завёрнут в лишний `div`, поэтому flex public header не отталкивал close control вправо.

Исправление: title и close теперь прямые siblings в `.shlz-modal__header`; ARIA/data behavior сохранены.

### Object actual-start DatePicker

Причины:

- later generic `.shlz-popover` сбрасывал public calendar shell с 280px до 236px и обрезал седьмой столбец;
- gray-50 date surface выглядел инородно внутри синей next-action surface.

Исправление:

- `.fm2-date-picker .shlz-date-picker__popover` сохраняет полный 280px calendar, на 320px ограничивается `viewport - 24px`;
- actual-start control использует public semantic base/white surface, public pill/focus/error geometry не переопределяется.

Проверенные desktop metrics: popover 280px, calendar 280px, grid 210px, 7 column headers, document 1440px при viewport 1440px, field 250×40, background white.

Проверенный screenshot:

`/Users/antropophag/.local/share/fmonitor-2/object-start-datepicker-final.png`

## Визуальное evidence

Основные after sweeps вне checkout:

- `/Users/antropophag/.local/share/fmonitor-2/ui-sweep-final2-20260923.1j3EfA` — 30 initial states, HTTP 200, JS errors 0, overflow 0, open selection/object-edit states;
- `/Users/antropophag/.local/share/fmonitor-2/geometry-final3-20260923.knuOhU` — objects/OTIZ/certificate at 320/390/1440, HTTP 200, JS errors 0, overflow 0, open DatePicker;
- `/Users/antropophag/.local/share/fmonitor-2/construction-390-final-0e5df8bd.png`;
- `/Users/antropophag/.local/share/fmonitor-2/checklist-installer-close-final.png`;
- `/Users/antropophag/.local/share/fmonitor-2/object-start-datepicker-final.png`.

Owner screenshots that identified the latest defects remain on Desktop, including 08.44.47, 08.48.08, 08.50.02 and 08.50.12.

## Focused verification already observed

GREEN during the final correction wave:

- `YII2-PREOPENING-JOURNEY-001` browser;
- `YII2-OBJECT-QUEUE-001` HTTP/browser matrix;
- `OTIZ-EXCEL-PUBLICATION-001` native browser;
- `YII2-DOCUMENTARY-CLOSURE-001` browser;
- `OTIZ-SHLZ-UI-001` responsive browser after updating stale 44px expectation to pinned 40px;
- `PRODUCTION-HTTP-RUNTIME-001` protected browser journey;
- feedback `FEEDBACK-001` A1–A9;
- forms/overlays inventory;
- object-card presentation HTTP/browser/no-write;
- PHP lint for touched views;
- Impeccable detector `[]` on executor passes.

Earlier bounded GREEN evidence also covered visual source, installers, inspection, calendar, users/roles, feedback and architecture guard 59/59.

Known unrelated or unresolved evidence:

- `tests/Yii2/deadline_transfer_certificate_http_001_test.php` remains RED on actor name/ID in the history row. Production markup already contains actor name/id; current evidence points to fixture data after main integration. Triage and record it honestly before final readiness. Do not hide or weaken it.
- No local full suite was run or may be run without explicit owner override.

## Stand rebuild recipe

Use the existing private env without printing secrets and without resetting state:

```bash
docker build --file deploy/runtime/Dockerfile --tag fmonitor2-runtime:local .
docker compose \
  --env-file /Users/antropophag/code/fmonitor-2-erp-operational/.env \
  --project-name fm2-local-timofey \
  --file deploy/runtime/compose.yaml \
  up --detach --wait php web jobs-worker jobs-scheduler
```

Do not run down with volumes, database reset, import, user reset or offline queue cleanup.

## Что осталось сделать в новой сессии

1. Проверить clean status и exact HEAD. Если этот handoff закоммичен поверх `e0e46b1c`, production source стенда остаётся `e0e46b1c`, а единственная разница HEAD — документация.
2. При необходимости пересобрать 8093 exact current HEAD state-preservingly.
3. Показать владельцу:
   - `/pilot/objects`;
   - object 39058 / actual-start DatePicker;
   - checklist installer modal;
   - object edit modal;
   - OTIZ at 320/390.
4. Получить явное owner manual checkpoint. До него не публиковать PR и не запускать CI.
5. Триажить known certificate actor fixture RED без изменения product semantics.
6. Актуализировать `openspec/changes/unify-yii2-shlz-ui/tasks.md` только по реально выполненным/evidenced пунктам; не объявлять всё Done по числу классов или тестов.
7. Подготовить refreshed exact-source role package через delivery harness и получить independent Gate 3.
8. После owner checkpoint выполнить independent Gate 5.
9. Только затем создать PR и запустить один exact-source GitHub CI matrix. При failure сначала собрать полный failed-job и `REGRESSION_FAILURE` inventory.
10. Не merge и не deploy production; это остаётся владельцу.

## Критические запреты и invariants

- Не менять routes, roles, request methods/payloads, CSRF, idempotency, histories, finance formulas, dates/status meaning или offline/storage/sync protocol.
- Не обновлять и не форкать shlz-ui.
- Не создавать новый candidate, новый proposal, новый browser framework или дополнительный слой CSS overrides.
- Удалять заменённые rules, а не скрывать overflow глобальным clip/hidden.
- Не считать healthy containers, наличие CSS-классов или GREEN static assertions визуальной приёмкой.
- Не использовать production или пользовательские записи стенда для изменяющих проверок.

## Точный критерий завершения

Работа считается PR-ready только когда одновременно есть:

- явный owner checkpoint стенда;
- просмотренные актуальные after screenshots;
- bounded local checks с честным failure inventory;
- refreshed independent Gate 3 и independent Gate 5 `APPROVED`;
- один exact-source GitHub CI GREEN;
- точные HEAD/source/image identities;
- перечисленные оставшиеся дефекты/UNKNOWN без выдачи их за GREEN.

## Продолжение 2026-09-23 после handoff

- Separate executor `/root/certificate_triage` (`gpt-5.6-sol / low`) установил,
  что RED `deadline_transfer_certificate_http_001_test.php` был product markup
  regression, а не fixture/environment failure: после `strip_tags()` ID автора
  склеивался со временем следующей ячейки. Коммит `7769cb52` восстановил только
  текстовый разделитель после actor ID; actor name/ID, persistence и тест не
  изменялись.
- Root сверил и записал candidate outcomes V01–V09 в
  `yii2-shlz-visual-audit-inventory-2026-09-22.md`, просмотрел финальные
  checklist/date-picker/construction screenshots и актуализировал только
  подтверждённые OpenSpec tasks. Коммит root: `63f46371`.
- На candidate source `93c8140a54e7ba1a75e7d41dbf2bff9bda9bea4e4f0169213ad0644858c82511`
  и executable source `d85faea018b1ef81da2f90be6bf706bf7079c3587c1bd33d9900c1140a77224c`
  выполнены все 15 planner-selected bounded local commands. Все GREEN; также
  GREEN исправленный `deadline_transfer_certificate_http_001_test.php`.
  `architecture_guard_001_test.py`: 59/59. Локальный full suite не запускался.
- Refreshed Gate 3 package пока не создан: `harness.py prepare --role reviewer
  --gate 3` fail-closed требует exact-source evidence по каждому mapped test.
  Existing Gate 3 RED records привязаны к pre-implementation source, а текущие
  исправленные tests закономерно GREEN. Старое approval нельзя переносить на
  поздний root-authored browser test delta молча; нужен допустимый test-delta
  package либо явное process решение. Это `BLOCKED`, не approval.
- Owner manual checkpoint, Gate 5, PR и CI по-прежнему не выполнены.

## Финальный continuation handoff — 2026-09-23 11:50 Europe/Moscow

### Точная точка продолжения

- Worktree: `/Users/antropophag/code/fmonitor-2-yii2-shlz-unification`.
- Branch: `codex/unify-yii2-shlz-ui`.
- Production/test HEAD до этого handoff: `fda366801e77d20b70f0457bec042288c8804b60`.
- Candidate source: `5d180059a584372a638a43659c4bc40ecd3c5d67585accb7f8e46c44d90c06c4`.
- Executable source: `9ecfdb320e95d59ecab2544fdbfa9dea05079bdc045fe199beb2fb85e30dd47e`.
- Runtime image на стенде: `sha256:fae5cc54363c9123d2f868d1f04a3b3af5af031822fb7bc408d94e07c06a1f46`.
- `fm2-local-timofey` db/php/web/jobs-worker/jobs-scheduler healthy; данные и
  volumes не сбрасывались.
- Worktree был clean до записи этого handoff. После handoff commit единственное
  отличие HEAD — этот lifecycle document; production image остаётся exact по
  executable source.

Active harness binding `20260923T081029Z-ae144c2a35` устарел относительно
текущего HEAD и всё ещё содержит промежуточный acceptance mapping на retired
`installer_search_http_manual_pilot_test.php`. Текущий
`verification-input.json` уже исправлен на active Yii
`yii2_preopening_routes_001_test.php`. Следующая сессия MUST первым delivery
действием выполнить fresh `harness.py prepare --role root` и использовать только
новый package/source; старый binding не годится для review/admission.

### Последние owner-approved визуальные решения

- Target matrix: laptop 1366×768/1536×864, Redmi Pad 2 Pro
  1280×800/800×1280, mobile 360×800/390×844.
- Wide lists objects/users/installers/roles и OTIZ владеют локальным scroll;
  viewport/sidebar больше не сжимают headings/values до пересечения.
- Shared pagination: page controls слева, summary справа; balanced 16px inset
  (12px mobile), summary 14px/400 (13px mobile).
- Selection picker показывает только ФИО, табельный номер и непустые текущие
  закрепления. Position/source/updatedAt скрыты. Объект с `pto_act + declaration`
  исключается из текущих закреплений без изменения истории.
- Для таб. 015199 отсутствие закрепления корректно: единственный application —
  object 1318, закрытый обоими completion facts 2026-09-20. Действующие examples:
  таб. 016238 → object 180; таб. 015130 → object 2232.
- Modal close и chip remove используют public `close.svg`; chip remove target 32px.
- Feedback FAB получил 2px surface edge и offset soft elevation, сохранив
  desktop/mobile inset и bottom-navigation clearance.
- `preopening.js` теперь обязательно content-versioned через
  `AssetVersion::file('preopening.js')`; это исправляет наблюдавшуюся смесь
  нового JSON со старым Safari module (`undefined`, старые Источник/Актуально).

Ключевые новые commits после исходного handoff:

- `42355256` — data-list containment на owner device matrix;
- `cf399c29`, `f0cee41d`, `00ee13e3` — device matrix tests/spec/evidence;
- `c335d295`, `081a0b64` — shared pagination alignment + browser geometry;
- `79f69f00` — installer picker context, public close controls, feedback FAB;
- `c54c7d2d`, `084bb02a`, `1d909796`, `a20141be` — active Yii picker spec/tests/plan lineage;
- `ac20c835`, `fda36680` — versioned preopening interaction asset + regression.

### Проверенное evidence

GREEN на последней волне:

- `yii2_preopening_routes_001_test.php` — exact picker JSON, current applied
  assignment, closed assignment exclusion, read-only preservation;
- `yii2_preopening_browser_001_test.php` — picker/modal/focus/public icons and
  full owner viewport matrix;
- `yii2_object_queue_browser_001_test.php` — feedback FAB inset/edge/elevation;
- `yii2_shlz_select_001_test.php` — content-versioned preopening asset;
- `yii2_otiz_shlz_ui_001_test.php` — headers, contained scroll, pagination geometry;
- device-matrix confirmation evidence:
  `/Users/antropophag/.local/share/fmonitor-2/device-matrix-after-20260923.WbzbIJ`;
- users residual confirmation:
  `/Users/antropophag/.local/share/fmonitor-2/device-matrix-users-confirm-20260923.Qd8HWk`;
- Impeccable detector `[]` before the final picker/cache wave; rerun once on all
  final changed UI targets before final review.

Known unrelated focused RED: `yii2_main_navigation_001_test.php` rejects an
existing `navigation.js` `insertBefore(` token. It was observed while checking
the one-line asset-version fix, predates that diff and was not changed or hidden.
Triage it in the complete local failure inventory before publication; do not
weaken the assertion casually.

Full local `make test`/`make verify` remains forbidden. PR does not exist; CI,
merge and deployment remain `UNKNOWN`.

### Explicitly excluded next PR

Owner wants to remove the separate «Документальное закрытие» block and put PTO
act/declaration date controls directly in the object-card header/primary layout.
That product-flow change was explicitly deferred to another PR. This candidate
MUST NOT absorb it. Preserve existing completion writers, permissions, append-only
corrections and progress semantics here.

### Route to PR merge-ready

1. Read AGENTS/current goal/PRODUCT/CONTEXT/development process, this handoff,
   current OpenSpec artifacts and both normative specs.
2. Run `harness.py state`, confirm clean status, then fresh root `prepare` from
   current `verification-input.json`; read every obligation and exact package.
3. Re-run only planner-selected bounded local commands. Include the known
   navigation RED in the complete inventory and classify it before any correction.
4. Run Impeccable detector once on final changed UI targets.
5. Prepare a supplemental exact-source Gate 3 test-delta handoff: prior Gate 3
   approval plus later CHANGES_REQUESTED, current device-matrix/pagination/picker/
   FAB/cache-busting test deltas and retained RED/after evidence. An independent
   `gpt-5.6-sol / low` reviewer records APPROVED or CHANGES_REQUESTED in
   `reviews/tests/YII2-SHLZ-VISUAL-CONTRACT-001.md` and harness review state.
6. After Gate 3 APPROVED, prepare current Gate 5 reviewer package with complete
   focused evidence and before/after directories. A different independent
   `gpt-5.6-sol / low` reviewer decides final verdict.
7. Only after both approvals and clean exact source: push branch, create PR to
   `main`, then use `tools/delivery/ci-launch.py` reuse/discover route and launch
   exactly one exact-source GitHub CI matrix. On failure first collect full
   failed-job and `REGRESSION_FAILURE` inventory.
8. Fix/re-review/re-run CI only when evidence requires it. Stop at PR merge-ready;
   merge and production deployment remain owner actions.

Owner has explicitly requested that the current visual candidate be finished and
published as a PR. This authorizes PR creation and the one required exact-source
CI after Gates 3/5; it does not authorize merge or deployment.
