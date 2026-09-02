## Context

См. `proposal.md` и delta spec. Local auth уже поддерживает собственные users, credentials и session, а `app/PilotHttp/AccessPolicy.php` читает role permissions. Однако часть route composition всё ещё исторически связана с `REMOTE_USER`/legacy auth specs, некоторые handlers проверяют только active user, а verifiers создают неполные RBAC manifests. Требуется security boundary, пересекающая route adapters и application authorization без изменения schema в этом slice.

Owning module — application-level Identity & Access contract. HTTP/UI и `rapid-pilot` могут только выбрать route permission, передать authenticated local user ID и обработать typed result. Persistence owner — отдельный MariaDB read adapter local identity/RBAC namespace; business modules и screens не владеют этим SQL.

## Goals / Non-Goals

**Goals:**

- Один typed public seam для current local authorization snapshot.
- Одна SQL/read-model ownership boundary для local user → active role → exact permission.
- Явные results, позволяющие HTTP сохранить generic `401/403/503` без знания SQL причин.
- Route-specific permissions остаются рядом с routing/composition и тестируются как явная mapping.
- Возможность заменить MariaDB adapter in-memory double в Gate 2 без обхода seam.

**Non-Goals:**

- Не объединять authentication, credential/session lifecycle и authorization в один модуль.
- Не добавлять schema, permission literals, role hierarchy, wildcard grants или direct-user exceptions.
- Не переносить административные mutations и audit в этот read-only slice.
- Не менять legacy-authenticated routes, пока они не подключены отдельными migration slices.

## Decisions

### 1. Typed application seam вместо boolean helper

Seam принимает nullable/validated local actor ID и application-owned required permission, возвращая `AUTHORIZED`, `AUTHENTICATION_REQUIRED`, `ACCESS_DENIED` или `AUTHORIZATION_UNAVAILABLE`.

Rationale: boolean смешал бы отсутствие authentication, отказ policy и инфраструктурную ошибку, провоцируя permissive fallback либо неверный HTTP status. Alternative — exceptions для всех отказов — отклонён, потому что expected policy denial не является инфраструктурной исключительной ситуацией.

### 2. Один authorization read получает согласованный grant

MariaDB adapter проверяет обязательную цепочку одним параметризованным exact query либо одной read transaction с согласованным snapshot. Permission сравнивается binary/byte-exact; user ID параметризован. Adapter не возвращает route-у roles или полный permission set.

Rationale: выдача полного набора наружу размазывает policy evaluation и позволяет adapters самостоятельно трактовать hierarchy/wildcards. Alternative — последовательные независимые lookups — отклонён из-за риска смешать user/role/permission из разных concurrent commits.

### 3. Route mapping остаётся явной и закрытой

Каждая route registration/composition точка задаёт literal permission; handler получает уже разрешённого actor. Request parsing не принимает permission parameter. Общий seam не выводит permission по URL.

Rationale: routing владеет знанием, какое прикладное действие вызывается, а authorization module владеет только проверкой exact grant. Alternative — центральная строковая URL ACL table — не вводится: она дублировала бы router и создала второй источник mapping.

### 4. Legacy auth остаётся за migration boundary

Новый seam никогда не обращается к legacy tables и `REMOTE_USER`. Existing legacy HTTP authentication может сосуществовать только на ещё не мигрированных routes. Route нельзя подключить одновременно к local seam и legacy fallback.

Rationale: staged strangler migration сохраняет обратимость, не ослабляя authority на уже мигрированном route. Alternative — composite local-then-legacy resolver — прямо противоречит owner decision.

### 5. Read-only authorization не создаёт audit event

Grant/deny checks не пишут историю. Будущие команды назначения/отзыва ролей обязаны владеть audit, но не входят сюда.

Rationale: запись на каждом GET усложняет availability, идемпотентность и privacy без утверждённого security-event retention contract. Alternative — synchronous access log в domain DB — требует отдельного product/security slice.

### 6. Architecture checks

Если новый application namespace/adapter добавляет boundary, `make architecture-check` должен подтвердить: application contract не зависит от HTTP/UI, rapid-pilot или concrete MariaDB; SQL находится только в разрешённом persistence adapter; rapid-pilot содержит wiring, не policy logic. Hotspot justification требуется, если route integration увеличивает существующий hotspot сверх ratchet.

## Risks / Trade-offs

- [Старые route specs считают legacy identity достаточной] → мигрировать routes отдельными fixture/E2E slices; не включать dual fallback.
- [Текущие fixtures имеют упрощённые таблицы или пропущенную activation] → следующие fixture slices создают canonical manifests; этот slice не чинит verifier DDL.
- [Binary exact comparison зависит от collation] → Gate 2 включает case/space near-match и adapter явно задаёт exact comparison.
- [RBAC revoke между authorization и state-changing handler] → этот slice гарантирует current snapshot только в точке допуска; атомарность revoke с domain command требует отдельного transaction/authorization design для соответствующей команды.
- [503 может скрыть schema drift] → typed unavailable result, generic external
  response с opaque 12-hex correlation ID и internal stable safe category без
  RBAC/SQL/config disclosure закреплены в Gate 1.

## Migration Plan

1. Получить independent Gate 1 review и отдельное owner approval executable spec.
2. Gate 2: написать contract tests на public seam и route-admission test, сохранить intentional RED.
3. После independent test approval добавить application contract/result types и in-memory test double.
4. Добавить production local-RBAC read adapter и composition; подключить
   `GET /pilot/objects` с exact `objects.read`. Остальные routes мигрировать
   отдельными slices через тот же seam.
5. Запустить focused tests, `make architecture-check` и relevant regression; пройти independent code review.
6. Отдельными slices синхронизировать object-read, prepare и E2E fixtures/routes.

Rollback: вернуть route wiring на предыдущую реализацию только для ещё не выпущенного test contour; не добавлять legacy fallback в новый seam. Schema rollback не требуется, поскольку slice не меняет DDL.
