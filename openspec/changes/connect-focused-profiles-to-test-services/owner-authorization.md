# Owner authorization — issue #110 networking prerequisite

## 2026-09-13 — Gate 4 и delivery

После pre-implementation плана владелец явно подтвердил networking prerequisite
и поручил пройти `Gate 2 RED → independent Gate 3 → отдельный executor → focused
verification → independent Gate 5 → отдельный prerequisite PR`.

После Gate 3 владелец дополнительно поручил работать автономно до PR merge-ready
и не останавливаться без явного blocker либо требования расширить scope. Это
является authorization отдельному executor менять только
`tools/delivery/run-in-profile` в пределах подтверждённого контракта.

## 2026-09-13 — окончательное сужение после consumer verification

Владелец принял Hard stop и разрешил продолжить prerequisite отдельным bounded PR
только в уже доказанном networking scope:

- production change только `tools/delivery/run-in-profile`;
- conditional route profiles `integration`/`browser` к уже существующей
  canonical Compose test network;
- behavioral DB-backed `mysqli SELECT 1` acceptance;
- не более 100 infrastructure LOC;
- без Docker socket/daemon, Docker CLI/tooling expansion, Git metadata support,
  нового orchestrator или изменения Compose lifecycle.

Владелец отменил PR B и blanket category adoption. Quality Graph,
planner/selection/aggregation, verification inventories и `harness.py` менять не
разрешено. Issue #118 не входит в эту поставку.

## Publication boundary

Разрешены отдельный prerequisite commit/PR и ожидание authoritative exact-source
CI. Самостоятельный merge не разрешён. Поле live admission
`action_authorized=false` при `live_github_unavailable` не используется как
разрешение либо запрет Gate 4 и не считается GREEN; фактическое owner
authorization зафиксировано выше, а merge readiness определяется отдельными
Gate 5 и exact-source CI evidence.
