# Issue #29 — сохранённое распределение по монтажникам

## Scope и авторство

- Bounded stage: понятная расшифровка allocation выбранного snapshot в существующем drawer объекта ОТиЗ; `Refs #29`, issue не закрывается.
- Base: `origin/main@99bd0974150617a01e195cec28f7d886f1ede761`.
- Root authored OpenSpec, canonical spec, verification mapping и tests.
- Production implementation: separate `gpt-5.6-sol / low` executor `/root/issue29_executor`.
- Gate 3: independent `gpt-5.6-sol / low` reviewer `/root/issue29_gate3`, APPROVED после двух correction cycles.
- Owner explicitly authorized autonomous delivery for this assignment. Merge/deploy/stand/real financial actions не разрешены.

## Реальный user before/after

Изолированный browser fixture: snapshot `821`, объект `ALLOC-1`, три сохранённых allocation rows, отдельный текущий состав, последующий snapshot `822`, объектное warning и XSS/long-text значения.

- Before: строка работника показывала только `ФИО · КТУ · сумма`; не было сохранённой даты, объектной суммы к распределению, вклада, доли, основания и честного missing-data состояния. RED: `INTENDED_RED: explanation contains Сумма к распределению`.
- After: из строки работника раскрывается нативный `<details>` с табельным номером, датой `31.08.2026`, суммой `123,45 ₽`, собственными сохранёнными вкладом `23,00 %`, КТУ `1,17`, долей `31,00 %`, основанием и итогом `33,33 ₽`. Второй работник показывает независимые `77,00 % / 0,93 / 69,00 % / 90,12 ₽`; пустое основание обозначено явно.
- Historical proof: старый snapshot не показывает участника текущего состава или значения snapshot `822`; новый snapshot не смешивает значения `821`.
- Safety proof: guest/403 не раскрывают identity/basis/money; repeated GET/Chromium сохраняют inventories; object issue остаётся вне worker details; long text и HTML-significant значения читаемы и escaped на desktop/narrow.

After screenshots сохранены вне checkout вместе с focused evidence:

- `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-29-allocation-577073b574/allocation-desktop.png`
- `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-29-allocation-577073b574/allocation-narrow.png`

## Verification

Planner lane: `CRITICAL`; required reviews: `gate3`, `final`; local full `make test` / `make verify` запрещены. Executor GREEN source `dff0ebbebf3ea631ca6845325b607f22443b12cd6be380093c7bcaed79400f6c`:

- `php tests/Yii2/yii2_otiz_saved_installer_allocation_001_test.php` — GREEN, 51.67 s.
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php` — GREEN, 17.06 s.
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_browser_001_test.php` — GREEN, 18.01 s.
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py` — GREEN, 223.15 s.
- `python3 tests/Verification/change_verification_001_test.py` — GREEN, 25.33 s.
- `php tests/Runtime/runtime_storage_001_test.php` — GREEN, 2.03 s.
- `python3 tests/Verification/architecture_guard_001_test.py` — GREEN, 17.33 s.

Independent Gate 5: APPROVED, findings none, review record `reviews/code/OTIZ-SAVED-INSTALLER-ALLOCATION-EXPLANATION-001.md`; reviewed source `0d55b084db458e740203f6588f7ac2abf08904b7c2aabeffae5e15f9811872f7`, snapshot patch SHA-256 `a1fb1d6580019ac998092653886364c817f31f654b3be4d8dffde5436f1449ce`. PR и exact-source CI дополняются внешней GitHub evidence без переписывания candidate.

### CI attempt 1 — полный inventory

GitHub run `36076186569` для PR head `2e83d5e82d860edb7f59a2777788c67c99cde94f`: plan/governance/unit/Integration 2/2/fast GREEN; Integration 1/2 и e2e FAILED; verify FAILED только как агрегатор; harness ожидаемо skipped. Полный `REGRESSION_FAILURE` inventory до correction:

- `tests/Yii2/yii2_otiz_settlement_001_test.php` — исчез retained historical token `КТУ 1,00`;
- `tests/Yii2/yii2_otiz_settlement_browser_001_test.php` — тот же retained token отсутствовал в drawer;
- `tests/Yii2/yii2_otiz_shlz_ui_001_test.php` — drawer потерял компактный видимый KTU context.

Correction сохраняет компактный summary из собственного `effective_ktu_bp`; legacy row с нулевым числовым КТУ и точным сохранённым basis `коэффициент 1,00` показывает совместимость как «из сохранённого основания; числовой КТУ не сохранён», тогда как canonical details остаётся буквальным `0,00`. Три failed checks и основной acceptance после correction локально GREEN; требуется refreshed final review и новый exact-source CI для corrected head. Предыдущая неуспешная попытка не считается GREEN и не скрывается.

## Остаток полной #29

- объяснение конкретных учтённых работ и прогресса;
- доказанный персональный provenance исключений и причин, когда он сохраняется owner;
- полная пользовательская приёмка первой и последующей выплаты как единой расшифровки;
- решения для исторических записей, где нужный provenance действительно отсутствует.

Поставленный ledger slice #248 не повторяется; распределение не считается доказательством выполненной выплаты.
