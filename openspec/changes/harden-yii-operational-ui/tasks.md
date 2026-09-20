## 1. Gate 1 и verification plan

- [x] 1.1 Root создаёт стабильный executable contract `specs/YII-OPERATIONAL-UI-CONSISTENCY-001.md` со всей acceptance matrix и проверяет его связь с OpenSpec delta.
- [x] 1.2 Root создаёт `verification-input.json`, запускает `python3 tools/delivery/harness.py prepare`, читает все mandatory obligations и фиксирует выбранные planner lane/reviews без ручного понижения.

## 2. Gate 2: RED на реальном Yii seam

- [x] 2.1 Root добавляет focused Yii HTTP/DOM-тест календаря для трёх рядов, deterministic projections, разных публичных tones, invalid date, authorization и read-only повторов; команда должна воспроизводимо падать на исходном child base.
- [x] 2.2 Root добавляет focused Yii HTTP/DOM-тест списка объектов для `Рег. №`/`Зав. №`, отсутствующего/нулевого заводского номера, отсутствия инженера в адресе и устойчивости к длинным значениям; RED подтверждён.
- [x] 2.3 Root добавляет inventory/interaction-тест общего SHLZ Select contract на всех активных Yii views с select-контролами, включая keyboard/ARIA/hidden value и non-JS fallback; RED подтверждён.
- [x] 2.4 Root добавляет matrix-тест `MainNavigation` для полного порядка «Объекты → Стройконтроль → Календарь → ОТиЗ → Монтажники → Пользователи → Роли», RBAC-скрытия без перестановки, одинакового состава на всех Yii-экранах и отсутствия post-render mutations; RED подтверждён.
- [x] 2.5 Если planner требует Gate 3, независимый reviewer проверяет полную acceptance mapping и RED evidence; только `APPROVED` разрешает реализацию.

## 3. Gate 4: минимальная реализация отдельным executor

- [x] 3.1 Executor расширяет bounded Yii calendar read projection событиями planned start/finish/inspection и проходит focused projection tests без runtime-зависимости от rapid-pilot.
- [x] 3.2 Executor переводит Yii calendar view на три фиксированных ряда, разные публичные SHLZ tones и типизированную agenda; calendar HTTP/DOM-тест становится GREEN.
- [x] 3.3 Executor исправляет представление объекта на `Рег. №`/`Зав. №`, нормализует пустой/нулевой заводской номер, удаляет инженера из адреса и ограничивает overflow; object-list тест становится GREEN.
- [x] 3.4 Executor добавляет общий SHLZ Select renderer/progressive-enhancement behavior и переводит все обнаруженные активные Yii select-контролы без изменения form names/values; inventory и interaction tests становятся GREEN.
- [x] 3.5 Executor делает `MainNavigation` единственным ordered RBAC owner с порядком «Объекты → Стройконтроль → Календарь → ОТиЗ → Монтажники → Пользователи → Роли» и удаляет применимые post-render mutations; navigation matrix становится GREEN.

## 4. Проверка, review и стенд

- [ ] 4.1 Запустить только planner-selected bounded local checks, применимый architecture check и `impeccable detect --json` по изменённым UI-файлам; сохранить полную evidence вне checkout, не запускать локальный full suite.
- [ ] 4.2 Выполнить один batched visual pass авторизованных `/pilot/calendar` и `/pilot/objects` на desktop/mobile, исправить найденные дефекты одним пакетом и при необходимости выполнить не более одного подтверждающего прохода.
- [ ] 4.3 Независимый reviewer выполняет planner-required final review exact source и записывает полный verdict; findings исправляются и изменённый delta повторно рассматривается.
- [ ] 4.4 После `APPROVED` и GREEN focused checks пересобрать локальный стенд `8093` без удаления named volumes и проверить health, три календарных ряда, номера объекта, SHLZ selects и RBAC-навигацию.
- [ ] 4.5 Запустить один exact-source GitHub CI через выбранный existing consumer и зафиксировать полный failed-job/`REGRESSION_FAILURE` inventory либо GREEN; PR/merge/deploy не утверждать при `UNKNOWN`.

## 5. Done definition

- [ ] 5.1 Все normative scenarios наблюдаемы на Yii public seams, planner obligations закрыты, required reviews `APPROVED`, focused checks и exact-source CI GREEN, стенд проверен, а иконка календаря и доменная история остались вне изменения.
