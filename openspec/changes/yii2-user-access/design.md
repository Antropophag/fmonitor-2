## Context

#76 после PR77/79/83/84. Root автор полного контракта и тестов; отдельные sol/low
исполнитель и reviewer. Autonomy — текущее поручение владельца 2026-09-10.

## Decisions

Normative matrix: [YII2-USER-ACCESS-001](../../../specs/YII2-USER-ACCESS-001.md).
YiiUserAccess — публичный owner; одна Yii DB connection и transaction для mutation,
DB-side current clock, canonical roles/permissions. Административные операции
редки: допустима общая serialization через существующую canonical role row, с
одинаковым lock order для всех mutations, включая activation. Контроллеры вызывают
owner и работают только с Yii Request/User/Session/Response. Yii session CSRF
(`enableCsrfCookie=false`) и regeneration после mutation сохраняют form replay
защиту; login/logout/Otiz regression обязательна из-за общей настройки Request.
Views используют текущие shlz public assets и presentation contract users/roles.
Raw invitation URL существует только в ответе/session flash; DB хранит hash.

## Dependency impact before Gate 2

- Schema frontier v24: migrations/readiness/rapid verifiers/table inventories не
  меняются. Используются existing IdentityAccess tables; HTTP DDL запрещён.
- Fixture: existing Yii2AuthFixture создаёт isolated DB current catalogue; новый
  helper добавляет explicit users/roles, faults и concurrency workers. DB root
  используется только fixture; mutation contract проверяется также DML-only user.
- Runtime dependencies: установленный composer.lock Yii2.0.55; новых packages нет.
  public yii entrypoint и runtime image уже копируют app/config, новые view/assets
  войдут автоматически. Исходный production runtime остаётся rapid до cutover.
- Deployment/readiness/backup/restore: storage layout/schema/entrypoints не меняются;
  существующие runtime checks входят в полный CI. Отдельный новый Docker build для
  каждого assertion не нужен; UI проверяется через реальный Yii server и browser.
- Verification inventory: новые acceptance/HTTP/concurrency/browser tests в
  categories.json/suites.tsv, явные additions в historical inventory baseline.
  Existing Yii auth/session/Otiz HTTP suites — relevant neighboring regressions.
- Architecture: ratchet включает новые Yii routes, запрещает rapid includes и
  direct controller SQL; legacy stand adapters остаются явно неприменимыми к Yii.

## Risks / Trade-offs

Полный #76 остаётся большим: процессные маршруты, OTIZ calculation/reconciliation,
console/jobs и cutover ещё впереди. Этот срез закрывает administrative family.
Mutable projections сохраняют append-only audit и invitation lineage. Login
redirect на objects остаётся прежним; перенести queue — следующий process slice.

## Final internal structure

Public YiiUserAccess interface remains unchanged. It composes a shared
MariaDbUserAccessTransaction (connection, serial transaction, clock and exact
permission/privileged-count reads) and internal MariaDB collaborators for
Directory, Invitations, Activation, RoleChanges and StatusChanges. SQL lives only
in the permitted MariaDb-prefixed adapters. This is one deep module with a stable
external interface; controllers and tests do not know its internal collaborators.
Each file stays below the existing hotspot threshold through coherent responsibility
separation, not baseline expansion. Yii Html dropdown helpers and explicit data
hooks replace raw select tokens without changing the rendered control semantics.
