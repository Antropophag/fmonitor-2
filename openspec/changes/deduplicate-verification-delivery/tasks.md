## 1. Gate 1 — контракт и полная матрица

- [x] 1.1 Создать стабильный `specs/VERIFICATION-DELIVERY-DEDUPLICATION-001.md` из capability contract с actor/seams, decision tables, exact rejections, authority/history и примерами #194/#195; проверить ручной trace каждого acceptance к issue №198.
- [x] 1.2 Создать `verification-input.json`, включив planned paths и отдельные acceptance mappings для browser selection, CI reuse/dispatch и review handoff; выполнить planner/`harness.py prepare` и подтвердить, что все mandatory obligations разрешены до Gate 2.
- [x] 1.3 Обновить текущий delivery goal/record для №198 с base `origin/main`, запретом локального full suite, авторством root и требуемыми reviewer ролями; проверить, что чужой WIP №157 и параллельные changes не включены в diff.

## 2. Gates 2–3 — root-authored RED и независимое review

- [x] 2.1 Добавить RED test, который доказывает транзитивный дубль до изменения и требует прямого единственного выбора `yii2_preopening_browser_001_test.php` в focused/full inventories; сохранить до/после список вызовов и intended failure.
- [x] 2.2 Добавить изолированные RED tests GitHub transport для applicable pending/completed, confirmed absent, stale/mismatched/base-changed, failed/cancelled и API/UNKNOWN, проверяя число dispatch и отсутствие ложного success/retry.
- [x] 2.3 Добавить RED characterization/examples для correction package: delta+dispositions сохраняют scope/findings, cosmetic ledger typo не требует нового code review, material/open finding продолжает блокировать; проверить intended failure без изменения исторических records.
- [x] 2.4 Подготовить exact-source Gate 3 package и получить независимый gpt-5.6-sol/low verdict по полной acceptance mapping; при `CHANGES_REQUESTED` исправить контракт/tests и повторно подготовить package, не начиная implementation до `APPROVED`.

## 3. Gate 4 — минимальная реализация executor

- [x] 3.1 Отдельному gpt-5.6-sol/low executor удалить `yii2_shlz_operational_ui_001_test.php`, перенести все актуальные acceptance mappings/consumers на канонический browser test и показать единственный selection на focused/full уровнях.
- [x] 3.2 В существующем delivery launcher/observer пути реализовать bounded discovery и reuse применимого PR-run, один fallback dispatch при confirmed absence и отслеживание captured run identity; прогнать изолированные CI-launch tests.
- [x] 3.3 Адресно обновить `docs/development-process.md`, существующий handoff template и реально используемый role/review package prompt для delta, полного списка findings и узкого cosmetic-ledger правила; прогнать соответствующие contract tests/examples.
- [x] 3.4 Выполнить один фактический focused запуск канонического browser-сценария и остальные planner-selected bounded local checks; не запускать локально `make test`/`make verify`, сохранить полную классификацию любых failures.

## 4. Gate 5 и PR-ready

- [x] 4.1 Захватить reconstructible exact-source snapshot, подготовить final package со spec/tests/Gate 3/evidence и получить независимый gpt-5.6-sol/low Gate 5 verdict; исправления кода/tests повторно проверять согласно recomputed plan.
- [ ] 4.2 Создать/push PR-ready candidate и использовать реализованный route: переиспользовать штатный PR-triggered Quality Graph либо выполнить ровно один supported fallback dispatch; дождаться одного exact-source результата без ручного параллельного дубля.
- [ ] 4.3 Зафиксировать в PR удалённый повторный вызов, точку CI guard и изменённые instructions, complete failed-job/`REGRESSION_FAILURE` inventory при сбое и финальные PR/CI links во внешнем delivery record; не создавать commit только ради будущего CI status и не выполнять merge/deploy/settings.
- [ ] 4.4 Подтвердить Done: browser witness выполняется один раз на каждом уровне, CI decision table и review examples GREEN, final review `APPROVED`, exact-source CI GREEN; любые UNKNOWN или незакрытые findings оставить явным blocker.
