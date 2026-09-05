## 1. Evidence и Gate 1

- [ ] 1.1 Зафиксировать characterization текущего HTTP wiring, capabilities и direct SQL; verification: append-only evidence с exact SHA и disposition каждого legacy route без изменения защищённого E2E.
- [ ] 1.2 Включить owner-approved read mapping от 2026-09-05 в executable contract; verification: ФКР/Руководитель ФКР — доступные объекты, инженер — закреплённые, ОТиЗ — все распоряжения и revisions, no admin inheritance; exact scope source и additive grant policy independently reviewed до RED.
- [ ] 1.3 Написать executable `ASSIGNMENT-ORDER-ORIGINAL-HTTP-001` с routes, DTO, admission, CSRF, safe audit, multipart bounds, response mapping, metadata/download и примерами; verification: все expected outcomes выводятся из approved pilot truth и имеют публичное наблюдение.
- [ ] 1.4 Получить independent Gate 1 и необходимые owner decisions; verification: новый APPROVED record перечисляет exact hashes, unresolved acceptance отсутствуют, predecessor command имеет полный Gate 5.

## 2. RED и независимый test review

- [ ] 2.1 Написать public HTTP initial/correction/replay/rejection tests на fictional fixtures; verification: доказанный intended RED для каждого нового behavior, а не setup failure, с exact command/output/hash.
- [ ] 2.2 Добавить read/download authorization, history, hash/size, отсутствующий evidence и storage-failure tests; verification: RED через публичный маршрут покрывает глобальный scope ОТиЗ, assigned/unassigned engineer, ФКР/manager, inactive/revoked/no-grant/admin-only и multi-role cases; verification reader не используется как product API.
- [ ] 2.3 Получить fresh independent Gate 3; verification: reviewer не автор тестов, explicit APPROVED включает sensitivity, determinism, cleanup и независимость expected values.

## 3. Minimal GREEN

- [ ] 3.1 Реализовать тонкий HTTP command adapter и read-only application seam; verification: approved tests GREEN, domain writes только через original command, no runtime DDL.
- [ ] 3.2 Подключить UI upload/history/download и local RBAC к approved routes; verification: browser scenario после шаблона и напрямую, correction/retry, exact read grants и неизменные opening/composition facts.

## 4. Review и Done

- [ ] 4.1 Выполнить relevant regression, architecture-check, diff-check; verification: exact SHA, отсутствие verifier runtime dependencies и domain SQL в новом adapter.
- [ ] 4.2 Получить fresh independent Gate 5; verification: reviewer не RED author/implementer, explicit APPROVED с проверкой всех entry points и append-only history.
- [ ] 4.3 Выполнить полный make verify и HTTP golden path с restart; verification: literal VERIFY_OK и exact-SHA evidence, byte-identical immutable downloads после restart.
- [ ] 4.4 Сверить Done requirement-by-requirement и архивировать только при полном доказательстве; verification: все gates/reviews/evidence существуют, read grants решены, upload/read/download готовы; opening/composition остаются отдельными changes.
