## 1. Закрепить контракт и frontier

- [x] 1.1 Снять read-only exact frontier/schema evidence и выбрать свободную literal migration version; verification: запись содержит current runner version, predecessor photos index fingerprint, final expected fingerprint и byte-identical hash `tools/architecture/baseline.json`, без DDL или DB reset.
- [x] 1.2 Обновить `specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md` новой версией с явным superseded SQL-1062 provenance и GRILL-007 authority; verification: первые три scenario неизменны, final scenario полностью требует accepted revision 3 / active 1 / rows 2 / revoked 1 / operations 3 / blobs 1.
- [x] 1.3 Получить независимый review изменённого executable spec и точных expected values; verification: review фиксирует `APPROVED` либо implementation не начинается.

## 2. Создать RED и независимое test approval

- [x] 2.1 Изменить focused verifier/meta-test только под новый final outcome: public `ChecklistSync::accept` возвращает accepted revision 3, audit содержит две distinct photo identities и три operations, первая revoked row byte-identical, SQL exception отсутствует, physical blob один; verification: transcript/hash пересчитаны независимо и остальные milestones/cleanup/decoys не ослаблены.
- [x] 2.2 Добавить active-identical idempotency и same-case concurrent/revision scenario; verification: active duplicate создаёт zero facts, а два contenders после revoke дают не более одного accepted active photo и loser duplicate/conflict без partial mutation.
- [x] 2.3 Добавить authorization preservation assertions; verification: обычный upload использует прежний admission, revoke-only/read-only actor не получает upload, revoke сохраняет exact `inspection.photo.revoke` + current engineer + reason/confirmation, все denial snapshots неизменны.
- [x] 2.4 Запустить новый focused test на predecessor unique schema и сохранить intended RED; verification: failure вызван exact SQL uniqueness obstacle в identical-reupload scenario после healthy fixture setup, а не environment/setup/cleanup failure.
- [x] 2.5 Получить независимый Gate 3 review обновлённых tests и RED evidence; verification: review pins spec/test/evidence hashes и даёт `APPROVED` до schema implementation.

## 3. Реализовать canonical schema reconciliation

- [x] 3.1 Добавить canonical successor migration на выбранном frontier, которая preflight-ит exact predecessor/final inspection family и заменяет только unique `(installation_case_id,section_id,sha256)` на non-unique ordered lookup; verification: никакой runtime DDL и architecture baseline не меняются.
- [x] 3.2 Обновить canonical inspection schema definition/catalogue expectations; verification: clean runner создаёт unique `upload_operation_id`, non-unique three-column content lookup и существующий non-unique case/section lookup с exact engine/collation/columns.
- [x] 3.3 Проверить populated predecessor migration и repeat; verification: rows включая `revoked_at`, operation facts, auto-increment and external blob fingerprints byte/value-identical, exact final repeat no-op, incompatible/ambiguous index forms fail closed before DDL, other prefixes untouched.
- [x] 3.4 Сохранить literal-v8 compatibility как historical predecessor и перевести `ChecklistSync`/`MariaDbInspectionAuthorization` на successor final compatibility; verification: migrated v19 проходит оба runtime admission path и canonical runner repeat, а malformed index остаётся schema unavailable без runtime repair.

## 4. Подтвердить public behavior

- [x] 4.1 Запустить approved upload→revoke→identical-upload verifier на canonical schema; verification: accepted revision 3, active projection 1, total rows 2, revoked rows 1, operations 3, blobs 1, distinct operation/photo identities и immutable first row.
- [x] 4.2 Запустить existing photo upload/rejection/limit/concurrency, revoke replay/already-revoked, checklist and HTTP authorization regressions; verification: все GREEN с прежними public results кроме явно superseded identical-reupload SQL failure.
- [x] 4.3 Проверить permanent retention and storage reuse; verification: revoke/re-upload не вызывает unlink/delete, existing blob bytes/hash/size совпадают, cleanup удаляет только test-owned fixture namespace и не моделирует product lifecycle deletion.

## 5. Review, stabilization и deployment separation

- [x] 5.1 Выполнить lint, focused `git diff --check`, global-call и `tools/architecture/check`; verification: GREEN, baseline byte-identical, stand/production DB не использовались.
- [x] 5.2 Передать exact schema/runtime/test diff независимому code reviewer; verification: review проверяет authorization, case serialization, append-only history, populated migration, blob retention and characterization supersession и фиксирует verdict.
- [ ] 5.3 Отметить implementation complete только после approved tests, focused regressions и independent code review; verification: full `make verify` status записан фактически и отсутствие `VERIFY_OK` не называется production readiness.
- [ ] 5.4 Подготовить отдельный deployment handoff без выполнения migration; verification: exact source/image, backup/catalogue/row/blob preflight, rollback-before-first-repeated-fact и forward-only recovery-after-new-facts описаны, но user-data migration/stand restart остаются невыполненными до отдельного authorized deployment шага.
