# RECONCILE-COMPLETED-INSTALLATION-STATE-001 v0.2 — runtime corrections only

## Простыми словами

Исторические данные переноситься не будут, поэтому backfill не нужен. Исправляем только отображение новых завершённых дел и ложную ошибку ОТиЗ при нулевом прогрессе.

## R1. Завершённое дело

Persisted `process_state=completed` MUST отображаться как «Работы завершены» в shared status и weekly FKR. Dashboard MUST считать его completed и MUST не включать в active или unfinished-overdue. Повторные reads ничего не пишут; паспорт объекта продолжает читаться из `fm_maintable`.

## R2. Writers

Checklist writers остаются exact-`working`; completed mutation отклоняется без новых фактов. Document corrections продолжают действующий append-only contract. Нового state-changing seam нет.

## R3. OTIZ attribution

Если общий учитываемый progress равен нулю, `MariaDbNativePremiumInputs::forDate()` MUST вернуть empty team/allocation без `INSTALLER_ATTRIBUTION_ABSENT`. При progress>0 каждый selected installer без positive contribution MUST остаться fail-closed причиной; deterministic diagnostic содержит safe case id и стабильный список `{tab,name}` всех affected installers. Contribution conservation, cutoff, formula и settlement не меняются.

## R4. Границы и Done

Historical reconciliation, CLI, migrations, backup/restore и data mutation исключены. Done требует root-authored RED, planner-required reviews, bounded GREEN и один exact-source CI GREEN.
