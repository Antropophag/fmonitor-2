# Maintenance RED checkpoint / пересмотр затрат

MAINTENANCEv0.2 Gate1 APPROVED: `original-maintenance-owner-gate1-v02-2026-09-06.md`.
Три новых scripts: maintenance_owner (34 cases), maintenance_storage (11),
maintenance_repository (17). Completed RED archive:
`/Users/antropophag/.local/state/fmonitor2-verification/original-maintenance-owner-red-ztd88nf4`.
Три existing controls PASS, остальные59 cases intended RED: missing typed owner/
repository/storage ports, hardcoded principal, ignored invalid config, unchecked
maintenance backing. Production не изменена. Первый Python-runner syntax error
исправлен до сохранённого RED, не является behavioral evidence.

Пакет планировался90min/120000 token delta. На12:16UTC goal counter1784623 против
1131642 на старте: +652981. Лимит существенно превышен; пакет не расширяется.
Следующий ход: independent Gate3 подготовленных артефактов, затем отдельные
bounded implementation units (typed application; native repository; filesystem
ownership/bindings), без новой матрицы ради полноты. Общая цель/requirements не
сокращаются. Нельзя отмечать maintenance/full original/portal complete по RED.

До этого DATA-INTEGRITY Gate5 и ATTEMPT-AUDITv0.4 Gate5 завершены. Последний
полный affected run52/52 PASS на3d4e852; это не make verify/VERIFY_OK. Canonical
frontier13. No push/deploy/PR mutation. Следующие launch blockers прежние:
maintenance/declarations, selection/read/render/cutover, HTTP/application/opening,
full exact-SHA VERIFY_OK, CI, clean deploy/restart/golden path. Deadline9Sept09МСК.
