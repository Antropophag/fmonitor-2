## 1. Gate 1 — executable contract

- [x] 1.1 Передать `specs/LOCAL-RBAC-AUTH-CONTRACT-001.md` отдельно tasked reviewer-у, устранить findings и проверить, что review record фиксирует наблюдаемый seam, exact grants/rejections, route mapping, read-only/current-snapshot contract и verdict.
- [x] 1.2 Получить отдельное утверждение владельца конкретной reviewed версии executable spec и проверить, что approval record называет spec ID/version; решение `GRILL-002` само по себе эту задачу не закрывает.

## 2. Gate 2 — characterization и intentional RED

- [x] 2.1 Зафиксировать минимальную characterization текущих local auth/RBAC route mappings без превращения legacy/authenticated-only fallback в requirement; проверить focused characterization command и сохранить evidence.
- [x] 2.2 Написать smallest public-seam contract test для полного exact grant и обязательных user/activation/active-role/exact-permission denial cases; запустить через canonical RED harness и сохранить intended missing-seam/behavior failure отдельно от setup failure.
- [x] 2.2a Добавить explicit multi-role union case: первая active assigned role
  не имеет required permission, вторая имеет exact grant; после deactivation
  второй роли тот же request denied.
- [x] 2.3 Добавить route-admission RED, доказывающий static per-route permission, отсутствие client-selected permission и запрет handler/business reads для `401/403/503`; запустить focused RED и сохранить relevant output.
- [x] 2.4 Добавить determinism/current-snapshot RED на read-only repeat и committed revoke перед новым invocation; проверить, что ожидания выводятся из executable spec, а не из будущей реализации.

## 3. Gate 3 — independent test review

- [x] 3.1 Передать approved executable spec, tests и RED evidence reviewer-у, который не писал tests; проверить review record в `reviews/tests/LOCAL-RBAC-AUTH-CONTRACT-001.md` с явным `APPROVED` прежде чем менять production code.

## 4. Gate 4 — minimal GREEN

- [x] 4.1 Добавить application-owned authorization contract и typed results без HTTP, rapid-pilot или MariaDB dependency; проверить focused contract tests.
- [x] 4.2 Реализовать production local-RBAC read adapter с согласованным current snapshot и exact comparison только в разрешённом persistence layer; проверить exact/near-match, inactive-link и unavailable tests.
- [x] 4.3 Подключить exact first vertical route `GET /pilot/objects` через
  `objects.read` и minimal rapid-pilot wiring; проверить, что все denial/
  unavailable results останавливают handler, а safe category/correlation и
  generic HTTP mapping сохраняются. Другие routes остаются follow-up slices.
- [x] 4.4 Запустить focused GREEN, relevant local-auth/PilotHttp regression и сохранить команды/results без изменения approved expectations.

## 5. Verification и Gate 5

- [x] 5.1 Запустить `make architecture-check` и проверить dependency direction, SQL ownership, public seam, rapid-pilot boundary и hotspot ratchet.
- [x] 5.2 Запустить relevant DB/characterization suites и затем `make verify`; классифицировать environment failure отдельно от regression и сохранить evidence.
- [x] 5.3 Передать spec, approved tests, production diff и verification evidence независимому code reviewer-у; проверить `APPROVED` record в `reviews/code/LOCAL-RBAC-AUTH-CONTRACT-001.md`, причём изменение tests возвращает работу к Gate 2.

## 6. Integration и Done

- [x] 6.1 Обновить operations backlog/status и разблокировать fixture slices только после approved code review; проверить, что `PILOT-OBJECT-READ-RBAC-FIXTURES-001`, `PILOT-PREPARE-RBAC-FIXTURES-001` и `PILOT-E2E-RBAC-FIXTURES-001` ссылаются на стабильный contract.
- [ ] 6.2 Подтвердить Done: approved Gate 1, demonstrated RED, independent approved test review, minimal GREEN, green regression/architecture/full verification и independent approved code review; только после этого пометить OpenSpec tasks завершёнными/готовить archive.
