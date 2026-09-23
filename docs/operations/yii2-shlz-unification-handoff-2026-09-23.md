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
