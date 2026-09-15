# Delivery #150 — единая основная навигация Yii2

## Scope и авторство

- Base: `25aee5524f790292d350175ba278bc47e282ed4c` (`main`).
- Контракт и tests: root agent.
- Production implementation: `/root/executor`, gpt-5.6-sol/low.
- Gate 3 review: `/root/gate3_review`, gpt-5.6-sol/low.
- Gate 5 review: `/root/final_review`, gpt-5.6-sol/low.
- Owner authorization: prompt 2026-09-15 разрешил proposal → apply → Gates без промежуточного подтверждения; merge/deploy запрещены.
- `rapid-pilot/` не читался и не изменялся как oracle/target.

## Delivered behavior

Один `app/YiiRuntime/MainNavigation.php` формирует permission-aware MAIN navigation через существующий `canonicalAccess`. Ровно пять surfaces #150 подключены к renderer; active section задаётся явным presentation context. Сохранены существующие группы, icons, feedback-final placement и отдельная внутренняя OTIZ navigation. Routes, RBAC, permission semantics и server-side authorization не менялись.

## Gates и evidence

- Gate 2: начальный RED и последующие целевые RED после review findings сохранены вне checkout в delivery harness records.
- Gate 3: `APPROVED`; два correction returns для первоначальной полной матрицы, затем одобрены setup-only delta и два bounded strengthening restart. См. `reviews/tests/YII2-MAIN-NAVIGATION-001.md`.
- Gate 4: executor реализовал renderer и пять integrations; root исправил только test setup и усилил regression по review findings.
- Gate 5: `APPROVED`; два correction returns (presentation structure/out-of-scope OTIZ routes, затем feedback placement). Exact approved package: `20260915T120938Z-cf803fee97`, candidate `6ed71c0df5779afecd9087317890f64e7fac1f2835f144391f3aef67dbb1582e`, executable `580148441d027678c5812473912872b17be78fb31c23f5c67c015102021ba198`, plan SHA-256 `60b2b0ea5c8334ec43b372d8c37d8c74f842c669304c6956ff46013eb43bcad4`.
- Final planner-selected focused phase: GREEN — navigation regression, change-verification and architecture guard. Records: `1789474106150899000-cd25b4bcdd66492c9ffbabb8d4071c34`, `1789474124324058000-a746fee01db842fb9ab130f132424411`, `1789474148552004000-d18b0adf6d694cbc953154930262a0f1`.
- Локальный полный `make test`/`make verify` не запускался.

## Publication

Exact commit, PR и GitHub CI заполняются после публикации candidate. Merge/deployment не выполняются.
