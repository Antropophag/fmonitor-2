## 1. Source и Gate 1

- [x] 1.1 Завершить или безопасно сохранить WIP №157, создать отдельный чистый worktree/branch от актуального `main` и подтвердить, что `git diff` нового candidate не содержит файлов №157.
- [ ] 1.2 Сопоставить актуальный `main` с audit SHA `bced877a` и #197, записать для V01–V09 статус reproduced/resolved-by-predecessor/UNKNOWN и проверить каждый reproduced case в разрешённом runtime.
- [x] 1.3 Создать нормативную executable specification с идентификатором, разделом «Простыми словами», матрицей 25 views, публичными seams, preservation invariants и независимо вычисленными expected outcomes; проверить полную трассировку на delta spec.
- [x] 1.4 Создать `verification-input.json`, вычислить обязательный Quality Graph plan по `tools/delivery/change-verification.md` и проверить, что все unresolved obligations закрыты до Gate 2.
- [x] 1.5 Построить минимальный deterministic authenticated GET loop для baseline 503, воспроизвести его повторно, проверить 3–5 ранжированных гипотез и записать доказанную причину без секретов.
- [x] 1.6 Добавить отдельный regression test причины baseline 503; если требуется source correction, передать её отдельному executor; проверить installers/users/preopening/ОТиЗ journeys до первого предметного assertion без ослабления permission-denied case. Диагноз оказался environment-only, production correction не потребовалась.

## 2. Baseline и Gate 2

- [ ] 2.1 Поднять разрешённый Yii test runtime на фиксированных fixtures и сохранить вне checkout before screenshots для representative table/form/modal/shell surfaces при 320, 390, 768, 1024, 1280, 1440 и 1920 CSS px; вручную проверить изображения и записать source/environment.
- [x] 2.2 Добавить focused structural tests для V02 и V09: одна field-композиция/label и единственный `main`/рабочий skip-link; запустить их и сохранить RED по отсутствующему поведению.
- [x] 2.3 Добавить browser tests для V03 и V04: фактический selector/геометрия selection modal и полный focus/Escape/cancel/return lifecycle payment confirmation; сохранить честный applicable outcome. V03 characterization GREEN на 320×568, source selector RED; V04 browser INTENDED_RED.
- [ ] 2.4 Добавить browser regression tests для V06 и shared data-list invariants: local scroll, полный текст денег/actions, отсутствие document overflow на boundary widths; запустить и сохранить RED.
- [ ] 2.5 Добавить characterization tests существующих routes, filters, roles, payloads, no-JS fallbacks, offline states, idempotency и histories на затрагиваемых flows; проверить GREEN до production edits.
- [ ] 2.6 Передать полный spec/tests/RED candidate независимому reviewer и получить planner-required Gate 3 `APPROVED` в `reviews/tests/`; при изменении expectations пересчитать plan и повторить Gate 2.

## 3. Shared foundation

- [ ] 3.1 Реализовать явные page/shell variants, единственный landmark/skip target и safe-area/layout ownership в Yii shell; проверить focused V09 tests на desktop/mobile и отсутствие page-owned `body`/sidebar rules.
- [ ] 3.2 Реализовать небольшие shared data-list compositions для wrapper/head/row/cell/numeric/status/action/empty/pagination variants; проверить contract tests на representative simple и financial tables.
- [ ] 3.3 Разделить APIs полного Field и Control, добавить единые help/error slots для text/search/date/select/textarea/file; проверить V02 и form accessibility tests.
- [ ] 3.4 Реализовать единый modal/drawer markup и focus controller поверх публичного shlz contract без изменения command forms; проверить open/trap/Escape/cancel/focus-return и отсутствие двойных handlers.
- [ ] 3.5 Реализовать shared feedback-state composition с раздельными empty/loading/denied/retry/offline pending/conflict/sent semantics; проверить текстовые и permission/offline characterization tests.

## 4. Реестры

- [ ] 4.1 Перенести `objects.php` и `construction-control.php` на shared shell/data-list/toolbar/status/action/empty/pagination contracts, удалить заменённые rules и проверить object/active-queue/offline focused suites.
- [ ] 4.2 Перенести `installers.php`, `users.php` и `roles.php`, включая служебные admin forms, permissions и empty states; удалить заменённые rules и проверить installer/user-access focused suites.
- [ ] 4.3 Перенести `otiz.php`, `otiz-snapshot.php`, `_otiz-snapshot-list.php` и `_otiz-nav.php`, сохранив полные суммы, ledger/actions и настоящие links; удалить page-owned shell/конфликтующие responsive layers и проверить OTIZ focused/browser suites.
- [ ] 4.4 Перенести history table `deadline-certificates.php` на shared data-list wrapper с длинными значениями и локальным scroll; проверить history/form focused cases.

## 5. Overlays, forms и operational flows

- [ ] 5.1 Исправить `selection.php` и связанный JS/CSS так, чтобы responsive rules достигали фактического dialog при длинном составе и малой высоте; проверить V03 browser test.
- [ ] 5.2 Перевести payment confirmation и общие overlays `otiz-snapshot.php` на единый focus lifecycle, сохранив payload, repeat protection и no-JS fallback; проверить V04 и OTIZ preservation tests.
- [ ] 5.3 Согласовать overlays/states в `object-card.php`, `objects.php` и `checklist.php`, сохранив object-card layout, edit boundaries, gallery и все offline/replay states; проверить object-card/checklist focused suites.
- [ ] 5.4 Перенести forms/states в `completion.php`, `original.php`, `original-history.php`, `execution.php` и `deadline-certificates.php`; проверить long values, upload/retry, histories и отсутствие изменений state-changing seams.
- [ ] 5.5 Перенести `feedback.php`, `feedback-confirmation.php`, `feedback-admin.php` и `preopening-error.php`, сохранив исходный контекст, retryability, pagination и различие окончательных ошибок; проверить focused HTTP/browser cases.

## 6. Остальные поверхности

- [ ] 6.1 Согласовать `calendar.php` и `dashboard.php` с shell/toolbar/surface/typography contracts без замены публичного Calendar Grid и с читаемыми chart labels; проверить соответствующие focused/browser suites.
- [ ] 6.2 Согласовать `login.php` и `activate.php` как общий auth layout с едиными fields/error/success/expired states; проверить auth-focused tests с длинным email и denied/expired cases.
- [ ] 6.3 Заполнить page inventory для всех 25 views: shared compositions, сохранённые исключения с обоснованием и evidence; проверить отсутствие непокрытой строки.

## 7. CSS/JS cleanup и bounded visual acceptance

- [ ] 7.1 Удалить заменённые CSS generations, orphaned selectors и местные common-primitive implementations; проверить поиском consumer references и запустить focused CSS/asset contract tests.
- [ ] 7.2 Проверить production assets на JS exceptions, missing assets и двойные event handlers; запустить bounded browser sweep на representative flows.
- [ ] 7.3 Выполнить один batched visual sweep desktop+mobile с sidebar states, boundary widths, short height, touch, overlays, long Russian data и 200% browser zoom/reflow; вручную составить единый список дефектов.
- [ ] 7.4 Исправить весь список visual sweep одним пакетом и выполнить не более одного подтверждающего sweep; сохранить просмотренные after screenshots на той же среде/fixtures.
- [ ] 7.5 Один раз запустить Impeccable detector по изменённым UI targets, устранить applicable findings либо записать обоснованные исключения и проверить отсутствие material findings.

## 8. Exact-source verification и Done

- [ ] 8.1 Запустить только planner-selected bounded local checks и relevant architecture check, включая обязательную PilotHttp qualification лишь если такие файлы неожиданно изменились; записать команды, source digest и все failures без локального full `make test`/`make verify`.
- [ ] 8.2 Подготовить exact-source role package через delivery harness и получить независимый final Gate 5 review с проверкой V01–V09, всех 25 views, preservation invariants, before/after evidence и отсутствия чужого WIP.
- [ ] 8.3 После `APPROVED` Gate 5 запустить один exact-source GitHub CI matrix, собрать полный failed-job/`REGRESSION_FAILURE` inventory при ошибке и не считать UNKNOWN/GitHub недоступность GREEN.
- [ ] 8.4 Отметить Done только когда exact source имеет GREEN selected CI, APPROVED final review, просмотренные before/after, полностью заполненный inventory, удалённые заменённые layers и сохранённые behavior/history/security contracts; deployment и merge оставить владельцу.
