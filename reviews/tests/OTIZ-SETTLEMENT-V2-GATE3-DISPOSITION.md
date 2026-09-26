# OTIZ-SETTLEMENT-V2-001 — disposition двух возвратов Gate 3

Review sources:

- candidate `5189d8e7b05e0ccba24e5386d87a24c3d99da55a59e6347f73e2e473d0ad1388`, plan `b00b155004021b5415f3437f73d55da5519ae3a49a0f10bcc888c9e2738fbda6`, `CHANGES_REQUESTED` 2026-09-26T16:44:16Z;
- candidate `2a358d74d162d14be3d8ce13c9d7a7d2efdb4c803b94539c681938344398c6e8`, plan `ff2b6e53036a0151afbecf98f03f987a15bf985786fa20827b6e8048d2f0b253`, `CHANGES_REQUESTED` 2026-09-26T16:51:10Z;
- candidate `d3793d422a8f0bfa8286a1b7bff713cf169cf9e47ca9e58f77aebdf986d30283`, plan `e65e8073cc610d3f8cc03778003336f9ba97b09d5239e08f3b5b9a62ae182ed8`, `CHANGES_REQUESTED`: valid RED records, but M07–M15 fidelity, remaining matrix assertions and race-order determinism required correction.

`fixed` означает исполняемое доказательство и фактический bounded RED, а не описание в плане. Перед следующим Gate 3 все `open` должны стать `fixed` либо иметь проверяемое `not-applicable`.

| Finding | Место исправления | Проверка | Статус |
|---|---|---|---|
| Gate3-1 DB проверял только наличие class/file | DB, upgrade и concurrency tests | fresh/repeat/upgrade schema, public commands, rollback, claims/obligations, replay, two-process races | fixed |
| Gate3-1 workbook искал строки в source | `tests/Otiz/settlement_v2_workbook_001_test.php` | независимое открытие ZIP/XML, sheets/types/IDs/injection/modes/totals | fixed на pure exporter seam; дополнительно требуется HTTP exporter parity |
| Gate3-1 HTTP/browser искали PHP/HTML строки | canonical HTTP test и PHP Playwright caller + `.mjs` | реальный Yii server+DB; login/CSRF/routes; Chromium actions 1440/390 | fixed |
| Gate3-1 большинство M01–M24 и E/D/C/H/P/A/R/I/U/X/S не исполнялось | domain/DB/portfolio/HTTP/browser/workbook tests и `verification-input.json` | literal oracles, documentary 85/10/5, old debt, full portfolio, state races | fixed; final conformance остаётся обязанностью Gate 3 |
| Gate3-1 normative spec ссылался на Downloads | `specs/OTIZ-SETTLEMENT-V2-001.md` | self-contained public commands, matrix, literal M01–M24 | fixed |
| Gate3-1 planned paths пропускали workbook/map | `verification-input.json` | regenerated plan contains both paths | fixed |
| Gate3-2 DB RED падал до seam из-за отсутствующего `vendor/autoload.php` | worktree dependency exposure; DB test bootstrap | canonical migration reaches existing v35, затем explicit `INTENDED_RED` на отсутствующей v36 | fixed locally; evidence must be recaptured |
| Gate3-2 HTTP возвращал 500 до intended assertion | two-step real login через `Yii2AuthFixture`; dependency exposure | login 303, existing OTIZ pages 200, затем `INTENDED_RED` на unified register | fixed in test; evidence must be recaptured |
| Gate3-2 browser оставался regex test | canonical browser wrapper + Playwright scenario | реальные grouping/expand/deduction/decision/accept/export/pay actions и screenshots | fixed; RED достигает отсутствующего unified register после успешного login |
| Gate3-2 отсутствовал portfolio/economy seam E01–E06/S03/M11/M18–M20/M24 | `settlement_v2_portfolio_001_test.php` | empty/future/no-year; total-vs-page; fund once; drafts neutral; реальные 60k+15k bases; year independence | fixed |
| Gate3-2 не было настоящей concurrency/upgrade/full lifecycle | worker processes, upgrade и DB lifecycle tests | independent connections with start barrier; v35 upgrade; accept/pay/pay-vs-reverse races; cancel/replace/history | fixed |
| Gate3-2 HTTP проверял только отсутствующий calc и denial | populated owner fixture + real routes | success decision→deduction→accept→HTTP XLSX→pay; CSRF/membership/stale/#257/no partial facts | fixed |
| Gate3-3 M07–M15 были перенумерованы/подменены | normative spec | literal owner oracles M07–M16 restored without reinterpretation | fixed |
| Gate3-3 оставались M09–M15/M18 и E02–E06/D03–D04/H01–H02/A07/M24 gaps | domain/DB/portfolio/HTTP tests | exact cents/conflicts/zero payout/years/overlay/search/cancel/replace/reversal/dates/admission/6k+9k/multi-object/unlinked/refresh/remove/delete/bounds | fixed |
| Gate3-3 race зависел от worker order | concurrency test | outcomes sorted before asserting exactly one winner and one loser | fixed |

Повторяющаяся причина двух review: тестовый подход был source-shape/first-failure, а не public-seam behavior. Исправление подхода: использовать существующие canonical migration, `Yii2AuthFixture`, PHP built-in Yii server, project Playwright module, независимые DB worker processes и реальный XLSX response. Автономный handoff HTML не является доказательством.