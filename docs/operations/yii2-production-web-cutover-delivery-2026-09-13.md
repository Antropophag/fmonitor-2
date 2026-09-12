# №76 — production web runtime cutover через Yii2

Владелец 2026-09-12 разрешил автономно продолжать №76, создавать отдельный PR
на каждый законченный срез и merge после gates/CI. Этот срез переводит стабильный
production front controller `public/runtime.php` на единый Yii2 runtime и удаляет
production `public/router.php`/`rapid-pilot/router.php` reachability. Deployment
рабочего стенда этим разрешением не выполняется.

## Scope и evidence

- OpenSpec: `yii2-production-web-cutover`.
- Normative contract: `YII2-PRODUCTION-WEB-CUTOVER-001`.
- Gate 3: `reviews/tests/YII2-PRODUCTION-WEB-CUTOVER-001.md`, итоговый verdict
  `APPROVED`; все oracle corrections отдельно независимо утверждены.
- Gate 5: `reviews/code/YII2-PRODUCTION-WEB-CUTOVER-001.md`, correction verdict
  `APPROVED` на executable source `a3701968eca6b1f161a2d1f991cf4ad9daba2691aaa5c496e2de58a81951ea6b`.
- Sequential generated focused plan: 12/12 GREEN. Включены полный production
  browser journey до 100%, Yii session restart/private PDF, OTIZ settlement,
  readiness/storage, полный route/assets/include frontier, architecture и
  verification inventory.

## Реализовано

- production HTTP routes, assets, errors и session обслуживаются Yii2; legacy
  production router удалён, rapid-pilot остаётся только behavioral oracle;
- сохранены no-config liveness, trusted Host, safe failures, HEAD/security/cache,
  persistent Yii session и exact RBAC/CSRF boundaries;
- сохранены FKR/checklist/offline/documentary/OTIZ flows и append-only факты;
- восстановлены exact `objects.read` и POST-only inspection scheduling после
  независимого Gate 5 finding.

## Незавершённое

PR, exact-source full CI и merge заполняются после публикации кандидата. Общий
№76, удаление остальных legacy console/oracle файлов, общий upgrade/rollback
rehearsal и deployment рабочего стенда остаются отдельными шагами.
