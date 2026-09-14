## 1. Gate 1 и verification binding

- [x] 1.1 Root создать нормативный `YII2-CLEAN-STAND-CUTOVER-001` executable spec из delta contract, связать issue #76 и owner decision 2026-09-14; verification: public seams, exact authorization/attestation, outcomes, rejection/no-effect cases и explicit non-goals полностью наблюдаемы.
- [x] 1.2 Root создать exact `verification-input.json`, подготовить harness package от актуального `main` и прочитать весь generated Quality Graph plan; verification: active binding указывает только на clean-cutover contract, а schema, fixture, runtime, deployment, secrets, jobs, HTTP/browser и architecture obligations разрешены без UNKNOWN.
- [x] 1.3 Root зафиксировать landed capability inventory с exact main commit и CI evidence; verification: target Compose, migrations, auth/access, provisioning, jobs, web, image и optional offline backup/restore проверены по коду/main, не по старым task checkboxes.

## 2. Root-owned executable acceptance и Gate 3

- [x] 2.1 Root добавить isolated exact-candidate/target admission tests с independent negative matrix и zero-effects snapshots; verification: source/image/Compose/authorization/identity/overlap drift fail closed до mutation.
- [x] 2.2 Root добавить clean provisioning RED на real disposable MariaDB/filesystem boundaries: fresh create, prepare, canonical migrations и idempotent initial users; verification: schema/ledger/users/paths observable и partial/UNKNOWN result не становится GREEN.
- [x] 2.3 Root добавить integrated Compose RED на php/web/worker/scheduler startup, live/ready/jobs health, heartbeats и enqueue→claim→outbox/history/recovery; verification: fixtures не подменяют long-running processes или readiness.
- [x] 2.4 Root добавить production HTTP golden RED для login/access и representative FKR, construction-control, checklist, OTIZ flows на synthetic fresh facts; verification: success и unauthorized no-fact outcomes происходят из действующих contracts.
- [x] 2.5 Root добавить real process/image/include closure RED для web/console/jobs без `rapid-pilot` и `RuntimeRecovery`; verification: attributable execution evidence является основным oracle, lexical assertions только дополнительный guard.
- [x] 2.6 Root сохранить intended RED evidence вне checkout и подготовить complete exact-source Gate 3 package; independent gpt-5.6-sol/low reviewer выдаёт `APPROVED` либо полный `CHANGES_REQUESTED` verdict.

## 3. Минимальный apply и Gate 5

- [x] 3.1 После Gate 3 отдельный executor выполнить generated focused plan на current production code; verification: если contract уже GREEN, зафиксировать zero production delta и не создавать искусственную implementation.
- [x] 3.2 Только если honest RED выявил gap, executor добавить минимальный operational/production delta через существующих owners без новых domain semantics, второй provisioning owner или ослабления readiness; verification: production delta не потребовался, добавлены только approved acceptance-only support/override/adapters, focused tests GREEN.
- [x] 3.3 Root подтвердить complete candidate и exact reconstructible source; independent Gate 5 `APPROVED` under explicit owner evidence waiver, code findings отсутствуют.
- [ ] 3.4 Выполнить обязательную verification из generated plan и один exact-source full GitHub CI; verification: focused commands и Quality Graph `VERIFY_OK`, UNKNOWN/partial outcomes явно не считаются успехом.

## 4. Disposable acceptance handoff

- [x] 4.1 Сформирован exact clean disposable target/source/image authorization package; package ограничил effects project `fm2-clean-76-a9fd0c33-325e1f46` без production/neighbor overlap.
- [x] 4.2 По owner authorization exact disposable target развёрнут и substantive acceptance завершён `CLEAN_STAND_ACCEPTED`; внешний evidence SHA-256 `ce8df9cdbba9eeed458f6c96521834858db0213a8f3583cee569c87210b148a6`.
- [ ] 4.3 Сформировать отдельный production cutover authorization handoff; verification: этот change не переключает traffic, не удаляет old stand и не выполняет restore/reconciliation/rollback.

## 5. Done definition

- [ ] 5.1 Change complete после Gates 3/5 APPROVED, exact-source CI GREEN и доказанного disposable `CLEAN_STAND_ACCEPTED`; legacy data preservation, UNKNOWN reconciliation, rollback, RuntimeRecovery retirement и production cutover execution остаются вне scope.
