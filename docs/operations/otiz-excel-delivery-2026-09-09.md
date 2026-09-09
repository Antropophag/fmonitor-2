# #66 — delivery plan: просрочка Excel и справки о переносе

Дата: 2026-09-09. Checkout: `/Users/antropophag/code/fmonitor-2-otiz-excel-20260909`, branch `codex/otiz-excel-66`, baseline `41e39498`. Этот документ фиксирует только планирование; source, tests, schema, stand и private workbook не изменялись.

## Решения владельца

- Источник формулы — исследованная рабочая книга SHA-256 `6e2d17246ebfc136ec7004331babf515d19fc2dfa98076c288c5c32da4f65b77`; private bytes остаются вне репозитория.
- Effective deadline — исходный плановый срок с override из текущей известной принятой PDF-справки независимо от её документной даты. Excel использует `DPn`, а `DOn` не является gate. ФКР обязательно вводит дату справки и новый срок; без сохранённых PDF reference/hash срок не активируется.
- Comparison date — effective дата ПТО даже после report date; без ПТО — report date.
- Kss уменьшается на 1 процентный пункт за календарный день и имеет floor0.
- Штраф применяется к остатку после paid-before. Все деньги — целые копейки; денежные границы округляются обычным математическим HALF-UP по owner decision33.
- Весь allocation remainder распределяется по наибольшим дробным остаткам; постоянный tie-break — canonical tab identity.
- A02/owner decision34: новая version применяется к новым/пересозданным расчётам; прежний accepted-unpaid результат получает append-only superseded event и блок выплаты без изменения сумм, paid history не supersede-ится и не пересчитывается.

## Delivery boundary

Один PR доставляет законченную цепочку: certificate application/history/PDF → as-of deadline и native completion provenance → versioned calculator/allocation → atomic snapshot → карточка/HTTP. Это не общий rewrite: новый domain owner ограничен справками, существующие AssignmentOrderOriginal и completion lifecycle не поглощаются и не копируются целиком. `rapid-pilot` остаётся presentation adapter.

Новая canonical schema v24 необходима, потому что deadline override является новым append-only фактом с concurrency/idempotency и private-file recovery, а object entitlement требует явной истории активаций/supersession. План предусматривает certificate roots/revisions/attempts и OTIZ object-entitlement events, отдельный private namespace, exact capabilities `deadline_certificate.write|read`, canonical migration/readiness и v23→v24 forward/restore доказательства. Default grants: write только `fkr_operator|manager`; read `fkr_operator|manager|otiz_specialist`; engineers/admin не наследуют доступ автоматически. Точные physical manifests и counts выводятся из final DDL и утверждаются независимым review.

## Missing native seams resolved by the plan

- Excel `T` соответствует исходному raw `plan_finish_date`; существующий `plannedFinishDate` adapter предпочитает `workdateendadjusted` и потому для этого расчёта не является корректным owner.
- `NEEDS_GRILL`: owner/timing raw T выбирается между current object-card capture каждого snapshot, immutable selection/application capture и отдельным подтверждением ФКР. До ответа original-deadline schema/seam не заявлены готовыми; отсутствие evidence блокирует расчёт.
- `MariaDbNativePremiumInputs` больше не должен брать ПТО из `assignment_orders.pto_act_date_snapshot`: operand строится из effective completion root/correction chain.
- Certificate reader использует current accepted leaf на момент новой publication без `certificate_date<=report_date`; future-dated document применяется буквально как заполненный Excel `DPn`. Recorded-at не подменяет дату документа, а snapshot фиксирует exact revision.
- Future-date exception ограничено certificate/PTO operands новой version. Checklist progress, payments и прочие facts сохраняют прежние report-date cutoffs; version-1 replay читает сохранённый snapshot без vNext recalculation.

## A02 и единая выплата

Formula-version check не решает A02: два v2 snapshots одного объекта иначе остаются независимо оплачиваемыми текущим snapshot-local SQL. Успешная publication под stable case lock создаёт fresh candidate identity и немедленно supersede-ит прежнее accepted-unpaid основание across periods; новая version всё ещё требует отдельной acceptance. Единственный PaymentCompletion повторно блокирует объекты, проверяет latest active identity и считает signed closures across snapshots по object. Старый snapshot получает `OBSOLETE_ENTITLEMENT`; replay возвращает тот же receipt. `rapid-pilot` больше не пишет closures/payment events напрямую.

Ledger сохраняет значения раздельно: Excel paid-before — только net paid, discipline hold уменьшает payable после pool. Nonzero legacy deadline hold блокирует v2 до explicit reversal, чтобы не выполнить ни double penalty, ни silent refund.

## Gate order

Gate1 → Gate2 → executable RED → Gate3 → schema/recovery → certificate application/storage → operands → calculator/allocation/publication → focused browser/HTTP/DB/recovery → independent reviews/Gate5 → один full CI exact candidate → отдельный PR merge. Production import, backfill 659 Excel cells и stand mutation не разрешены этим планом.

## Открытые product decisions

`NEEDS_GRILL`: владелец ещё не ответил, нужен ли управляемый аналог глобального Excel `$EU$2`. #66 не предполагает waiver и не реализует скрытый boolean/env toggle. Если waiver будет одобрен, его роль, причина, срок, scope и append-only аудит оформляются отдельным change; ответ не блокирует справки и расчёт просрочки без waiver.

`NEEDS_GRILL`: кто владеет raw base T и когда он фиксируется. Field mapping известен (`plan_finish_date=T`, `workdateendadjusted=V`), но выбор current-card vs immutable upstream capture vs отдельный FKR confirmation блокирует Gate1 original-deadline slice.

`NEEDS_GRILL`: recurring snapshot без нового progress. Literal Excel при fund100/Kss0.9/paid90 даёт pool9; approved interval rule может требовать no-new-amount. Certificate sub-contract от ответа не зависит.

## Artifacts

- `specs/OTIZ-EXCEL-OVERDUE-CERTIFICATES-001.md`
- `openspec/changes/reproduce-excel-overdue-with-deadline-certificates/`
- Formula evidence: `docs/operations/otiz-overdue-workbook-analysis-2026-09-09.md`
- Implementation reconnaissance: `/tmp/fm2-otiz-cert-recon.md` (temporary read-only evidence)

## Checkpoint после завершения #17

PR68 MERGED, main c25c1f1111444221adfcaa0998cb758349ec94ba, full Actions34332358012 SUCCESS/VERIFY_OK на69d2edd1. Source #66 не реализован. OpenSpec/spec готовы для уточнения, Gate1 CHANGES_REQUESTED; механическиеcapability/frontier замечания исправлены, финальный reviewer addendum ожидается.

В чат выведены три нерешённых продуктовых вопроса: брать исходныйT из карточки в момент расчёта или отдельно подтверждать ФКР; нужен ли audited globalwaiver; сохранять ли literalExcel новую сумму без прироста прогресса. Примерпоследнего: fund100000,completed100%,kss0.9,paid90000→ещё9000 в новом расчёте. Первичный анализ CT/EE/AN: /tmp/fm2-otiz-progress-paid-recon.md; workbook не изменялся.

Уже утверждены: календарные дни/1%вдень; penalty после ранее выплаченного; coefficientfloor0; FKR вводитmandatoryPDF+дату+новыйсрок; PTO дажепослерепорта; largest-remainder allocation. Предложенное обращение с legacydeadlineholds ещё требует точногоreview, не объявлять новую бизнеснормуутверждённой по одному текстуагента. Никаких backfill, stand/volume или реальных финансовых/внешних действий не выполнялось.

## Saved continuation

Main c25c1f11 integrated in planning branch via812f960e; archive17 cherry-pick276e8fff. Final Gate1 addendum resolves capability/frontier/atomic-supersession technical findings but retains three product choices. No66 source or executable tests authored. Recurrence research: otiz-excel-recurring-payment-analysis-2026-09-09.md. All bounded agents finished; no active tests or user-contour changes.
