## Context

См. `proposal.md` и owner decision. Текущий `app/PilotHttp` shared navigation
renderer создаёт disabled item «Моя работа» для всех callers. Предыдущий
`restore-pilot-work-navigation` планировал превратить его в `/pilot/` link, но
новое решение владельца меняет intent: item должен отсутствовать, тогда как
самостоятельный route `/pilot/` и очередь сохраняются.

## Goals / Non-Goals

**Goals:**

- Удалить один item в общей configured navigation composition.
- Доказать отсутствие во всех exact configured shared-shell route families.
- Сохранить route, authorization, соседние navigation items и zero-write
  presentation boundary.

**Non-Goals:**

- Удалять, перенаправлять или менять очередь `/pilot/`.
- Перепроектировать navigation/mobile shell или добавлять replacement home item.
- Менять RBAC, route table, queue filtering, domain/application seams или
  persistence.
- Переписывать/архивировать superseded `restore-pilot-work-navigation` evidence.

## Decisions

1. **Owner — `app/PilotHttp` presentation boundary.** Minimal GREEN удаляет
   item из единой shared navigation composition. Application/domain modules не
   получают новых dependencies; persistence owner отсутствует. Альтернатива —
   скрывать item на уровне каждого route — создаёт расходящиеся shells.
2. **Полное удаление, а не косметическая замена.** Public verifier проверяет в
   navigation landmark exact visible/accessibility label и root destination/
   current marker. Это ловит hidden duplicate, переименование и icon-only
   замену. Другие links/content route `/pilot/` вне navigation не запрещаются.
3. **Deep renderer seam плюс HTTP sentinels.** Exhaustive renderer oracle
   проверяет все десять current-state representations, exact siblings,
   accessibility и icon bytes. Root и object-list дают canonical HTTP/GET/HEAD/
   RBAC/zero-write sentinels; existing route-specific HTTP tests доказывают
   wiring остальных callers. Восемь дополнительных DB/server fixture stacks
   отклонены как дублирующий setup без новой наблюдаемой гарантии.
4. **Transport поведение наследуется и остаётся отдельным oracle.** Existing
   suites сохраняют exact `/pilot` redirect и `401/403/404/405/503` status,
   body и application-controlled headers. Navigation RED не создаёт собственную
   error composition и не ослабляет RBAC assertions.
5. **Rapid-pilot adapter не меняется.** Новая domain logic отсутствует;
   architecture-check baseline и hotspot exceptions не расширяются.

## Risks / Trade-offs

- [Queue остаётся без global navigation destination] → это явный product-owner
  trade-off; route `/pilot/` сохраняется для direct/deep entry и не получает
  replacement item в этом slice.
- [Удалится соседний item или изменится order] → exact sibling navigation
  snapshot/assertions до и после bounded removal.
- [Root route ошибочно удалят вместе с item] → отдельные public root success и
  `/pilot` redirect regressions.
- [Downstream RBAC test ослабит собственный contract] → predecessor assertion
  меняется только после approved removal tests; RBAC matrix/facts остаются
  отдельными exact assertions.

## Migration Plan

1. Получить fresh independent Gate 1 review этого exact planning/executable
   contract и explicit owner approval reviewed hashes.
2. Написать exhaustive shared-renderer DOM RED, подтвердить root/object-list
   HTTP sentinels и existing route-specific wiring tests, затем получить fresh
   independent test approval до production edit.
3. Удалить item только в shared presentation composition и получить focused
   GREEN; затем обновить downstream object-list predecessor assertion без
   ослабления RBAC matrix.
4. Прогнать focused route/shell/RBAC regressions, architecture-check и полный
   verify, затем получить independent code review.
5. Rollback возвращает bounded presentation diff; schema/data migration и
   destructive cleanup отсутствуют.
