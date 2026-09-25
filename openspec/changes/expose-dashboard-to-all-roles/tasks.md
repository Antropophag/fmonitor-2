## 1. Gate 1 — executable specification

- [x] 1.1 Root инвентаризирует все assertions о dashboard authorization/navigation в `tests/Yii2` и фиксирует production characterization (`403` при живых healthy services) без чтения секретов; проверка — inventory перечисляет каждый конфликтующий тест и текущую ветку отказа.
- [x] 1.2 Root добавляет focused RED HTTP-матрицу для guest, минимальной активной роли без `installers.read`, стройконтроля, multi-role и privileged actor; проверка — текущий source детерминированно падает именно на ожидаемых `GET|HEAD=200`, полном installer widget и общей области данных.
- [x] 1.3 Root расширяет navigation и no-write assertions: дашборд виден каждой активной роли, guest не получает данные, standalone permissions и mutation denials не ослаблены; проверка — RED вызван только новым universal-dashboard contract.
- [x] 1.3a Root меняет characterization справочника/карточки: все active roles видят одну global utilization projection без `installers.read`, а picker сохраняет прежний denial; проверка — текущий source RED на scoped/permission-gated различиях.
- [x] 1.4 Root добавляет production-characterization и RED-матрицу provisioning: exact `inspection.schedule` получают только `manager` и `construction_control_engineer`; их scoped команды успешны, остальные роли/guest/inactive/out-of-scope закрыты, replay/conflict/audit сохраняются; проверка — текущий source детерминированно падает из-за отсутствующих grants и несогласованной кнопки.
- [x] 1.5 Root создаёт `verification-input.json`, вычисляет обязательный Quality Graph plan по `tools/delivery/change-verification.md` и устраняет все unmapped obligations; проверка — harness принимает exact input и planner выдаёт lane/`required_reviews` без unresolved obligations.

## 2. Gate 2 — минимальная реализация executor

- [x] 2.1 Отдельный executor по подготовленному harness role package делает dashboard `index` и observation reads доступными любому активному аутентифицированному actor без `objects.read`, `installers.read` и role-scope veto; проверка — root-authored HTTP-матрица проходит, guest/inactive cases остаются закрыты.
- [x] 2.2 Executor выравнивает dashboard read owner/store с универсальной public policy без synthetic actor и без изменения bounded/fail-safe DTO validation; проверка — focused owner/dashboard tests проходят для actor без permissions и сохраняют unavailable behavior.
- [x] 2.3 Executor делает navigation item дашборда безусловным для authenticated identity, сохраняя permission-aware поведение остальных пунктов; проверка — focused navigation matrix проходит без дублей и с ровно одним `aria-current="page"`.
- [x] 2.4 Executor сохраняет dashboard installer utilization и observation details всем активным ролям, не расширяя standalone installer/admin/mutation authorization; проверка — позитивные dashboard assertions и отрицательные standalone assertions проходят вместе.
- [x] 2.4a Executor делает directory/card read routes универсальными и удаляет actor scope из `MariaDbInstallerUtilization`, сохраняя guest/inactive и picker/mutation boundaries; проверка — engineer/manager/minimal-role получают одинаковые counts/objects/history.
- [x] 2.5 Executor добавляет `inspection.schedule` ровно ролям `manager` и `construction_control_engineer` в `LocalRoleCatalog`, затем согласует UI controls с effective permission, не ослабляя application owner; owner-authorized production provisioning записывается отдельно, проверка — catalog/UI/owner/concurrency tests проходят и append-only аудит содержит фактического actor.

## 3. Gates 3–5 — bounded verification и независимое решение

- [x] 2.6 Root добавляет RED HTTP/browser matrix для 42-дневного default/previous/empty окна, строгой валидации `utilizationTo`, disabled future control, отсутствия roster и desktop/mobile geometry.
- [x] 2.7 Executor реализует bounded observation range read, серверную навигацию периода и shlz-ui bar-chart controls без новых writers или chart dependency.

- [ ] 3.1 Запустить только planner-selected bounded local checks, включая focused Yii2 dashboard/navigation/auth suites и architecture check, но не canonical full `make test`/`make verify`; проверка — каждый выбранный check записан с exact source и результатом.
- [ ] 3.2 Независимый reviewer выполняет planner-required Gate 3 (если выбран) на exact candidate и проверяет полное соответствие spec/tests без расширения иных прав; проверка — review record содержит явный GREEN или blocking findings.
- [ ] 3.3 После исправления findings запустить один exact-source GitHub CI consumer для полной матрицы и собрать полный failed-job/`REGRESSION_FAILURE` inventory при любом сбое; проверка — CI source совпадает с candidate либо статус честно остаётся UNKNOWN.
- [ ] 3.4 Независимый final reviewer проверяет exact source, focused evidence, CI и отсутствие production/data mutations; проверка — Gate 5 review record даёт итоговое решение, после чего publication/deployment выполняются только по отдельной авторизации владельца.

## 4. Done

- [ ] 4.1 Change считается завершённым, когда все активные production-роли получают `200` на `GET|HEAD /pilot/dashboard`, видят полный installer widget, роли руководителя ФКР и стройконтроля получают exact `inspection.schedule` и успешно выполняют показанное scoped-планирование, остальные роли/guest/inactive/out-of-scope actors не проходят, чтения не создают writes, остальные authorization boundaries не ослаблены, а planner-required reviews и exact-source CI имеют GREEN; проверка — acceptance mapping не содержит UNKNOWN или незакрытых обязательств.
