# ОТИЗ: независимое итоговое code review и production

Reviewer: `/root/otiz_ui_review`, gpt-5.6-sol/low, не автор кода или тестов.
Verdict: APPROVED,2026-10-29, после исправления трёх блокирующих замечаний.
Авторы: root — контракт/тесты; `/root/otiz_reserve_executor` — owning finance,
workforce/report/controller/UI/XLSX; `/root/otiz_ui_executor` — monthly read/filter helpers.

## Исправленные замечания

1. Принятое обязательство распределения резерва не зависит от последующего выхода
   получателя из официальной бригады; payment/export используют сохранённую выплату,
   сохраняя admission/freshness проверки источника.
2. Settlement и builder требуют активного пользователя, active activation state,
   активной роли и точного разрешения. Неактивный actor не проходит public seam.
3. Точный повтор operationId повторно проверяет текущие права до возврата receipt.
   Отозванный доступ не обходит проверку через replay.

Root добавил intended RED на все три случая; после исправлений reviewer повторно
проверил reserved_shares и builder и одобрил полный candidate. Gate3 approval отдельно
в `reviews/tests/OTIZ-FEEDBACK-FINANCE-20261029.md`.

## Проверки и ограничения

Focused acceptance: reserved_shares (включая API/auth/no-write/rollback), два отдельных
процесса/актора на один резерв, builder as-of/MSK/effective date/official member,
monthly periods/multi-object/zero/reversal/reserve/incomplete, workbook values/history,
HTTP CSRF/report/full-scope/release/refresh, portfolio reserve totals. Также проверены
existing settlement DB/math/native admission/snapshot publication и governance/unit
checks. Браузер desktop1440+narrow390: изображения просмотрены, overflow/errors нет,
скачивание отчёта работает. `make architecture-check`: PASS8rules, HTTP qualification
PASS. File-size advisories сохранены, не блокируют.

Не запускались full local make test/make verify. Полный CI отложен по прямому
поручению владельца о срочном production цикле. Дополнительный planner-selected
`pilot_jobs_compose_001_test.py` для неизменённых deployment paths достиг bounded120s
и отложен без повторения. Это не overall CI GREEN и не merge/admission approval.

## Exact runtime review binding

- `ffdc40866e3fd570f8f45adc256c70d38328df802adba8d71e1c8dab20ae8f3c` app/Otiz/MariaDbOtizMonthlyTotals.php
- `9c01355d42daa9e08a5aeb1dbafa5838e9b753d4ae55cf5a228693e4e5f70b43` app/Otiz/OtizCalculationReportFilter.php
- `b4601f0d5d1aec9b296b06753d3d960872d72011a89973d98c4f5998e376f65d` app/Otiz/MariaDbOtizPortfolioEconomy.php
- `d05a9c7d332d01019a9fee8e1b14ef3acca58e6afa3d8327d9caad68483a44d1` app/Otiz/MariaDbOtizSettlementV2.php
- `7d2261651ba4dfcef29bcc3d7b64d26688cfbc1246b254894a68c27aa87614e1` app/Otiz/MariaDbOtizSettlementV2DraftBuilder.php
- `b0db1c46cfa908b401f5a71dd8314bd6c5175e909c70575ed55e681b14b37994` app/Otiz/OtizSettlementV2Workbook.php
- `995e49045acbe9db78e049dedccd0e1e9a0f31fdb8308c76639e99b3b6ca7fb5` app/YiiRuntime/Assets/pilot.css
- `c39d2d58040187abfefeea41d1b2054e24f5f4e3d76d01136abe9a2ebe0964b1` app/YiiRuntime/Controllers/OtizSettlementV2Controller.php
- `8b831fc25d22e362c8bac85adb3d464948177901b2f22ee1877240ec40d2d1b6` app/YiiRuntime/Views/_otiz-v2-group-table.php
- `b3e823d2ec7636ce3f1165e0b06752b7651dea60d93368e1ed174221dd47147a` app/YiiRuntime/Views/otiz-calculation-v2.php
- `d00c3568323878cf8f08372c14796bc6cbdffbfbd9db8e80f146412539293f46` app/YiiRuntime/Views/otiz-economy-v2.php
- `652e31e4ca786f5cdabbceb6ebd8f884299b9d23ed10985a90c5e33c98bd2cb1` config/yii/web.php

## Production

12точных runtime файлов поставлены в host checkout и PHP container в19:54МСК.
Схема БД и финансовые факты не изменялись поставкой; резерв использует существующие
versioned snapshots, obligations и audit events. Перед выкладкой проверены исходные
хеши для защиты параллельных сессий. Полная БД сохранена отдельно; файлы имеют rollback.
После выкладки прочитаны11старых расчётов, сформированы11XLSX, проверены фильтры11срезов,
портфель309объектов и новые server-built inputs: policy reserved-shares-v1,
86объектов,47строк employed/2dismissed. Полный fingerprint финансовых таблиц до/после
read probe совпал. HTTPS login/CSS200; CSS bytes совпадают с reviewed hash.

Primary evidence, screenshots, immutable reviewed hashes, deploy manifests,
SQL-backup path and checksums remain outside Git:
`/Users/antropophag/.local/share/fmonitor-2/otiz-production-20261029/`.
Точные rollback locations — finance-deploy-result.json и ui-deploy-result.json.

Всего7независимых review turns:2UI,3test/spec,2financial final;4returns
(2UIграницы, полнота financial contracts/tests,3financial auth/history blockers).
Предыдущие решения и failed probes сохранены в session/private evidence.
