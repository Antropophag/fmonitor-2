## Context

См. `proposal.md` — Why. Текущий `ChecklistSync` сначала ищет active duplicate (`revoked_at IS NULL`) и сериализует mutation под case transaction, но canonical photos table дополнительно имеет более строгий unique content index. После revoke application policy разрешает новый факт, а database constraint его отклоняет. GRILL-007 требует permanent retention всех rows/blobs и разрешает identical re-upload с новой identity.

## Goals / Non-Goals

**Goals:**

- Согласовать canonical schema с уже выбранной active-duplicate policy.
- Сохранить revoked photo и upload/revoke operations неизменными.
- Доказать новый upload fact, revision 3 и reuse одного physical blob.
- Заменить устаревшее SQL-error ожидание characterization без ослабления остальных revoke scenarios.

**Non-Goals:**

- Перенос photo/revoke поведения в новый application module в этом минимальном stabilization slice.
- Изменение ролей, current-assignment, revoke confirmation/reason или completed-section correction.
- Blob deletion/garbage collection, retention expiry, UI redesign или production deployment.

## Decisions

1. **Canonical schema использует non-unique lookup index.** Удаляется только uniqueness свойства `(case, section, sha256)`; columns/order сохраняются для active duplicate lookup. Альтернатива generated active-hash unique column отклонена как более широкая schema/model migration. Существующая case transaction сериализует same-case commands, а active predicate остаётся authoritative idempotency check.

2. **Миграция распознаёт exact predecessor и final shape.** Перед DDL она проверяет всю inspection-evidence family и конкретный predecessor index fingerprint. Populated transition выполняет drop прежнего unique index + add non-unique index без row rewrite. Literal migration version выбирается на фактическом свободном frontier непосредственно перед implementation; параллельная стабилизация не должна получить collision.

   Historical `InspectionEvidenceSchemaMigration` v8 compatibility остаётся точным predecessor oracle и не переопределяется задним числом. Новый successor предоставляет final compatibility check, а runtime consumers `ChecklistSync` и `MariaDbInspectionAuthorization` переходят на него. Canonical runner repeat использует final successor check; иначе migrated v19 ошибочно классифицируется как v8 schema drift.

3. **Новый факт переиспользует blob.** Storage identity остаётся SHA-256-based. Если blob уже существует, bytes/hash/size проверяются существующим path; новая SQL photo row получает новый upload operation ID и новый photo ID, но тот же `storage_name`. Revoked row не обновляется.

4. **Authorization не расширяется.** Upload проходит прежнюю admission/role policy; revoke сохраняет exact `inspection.photo.revoke` + current engineer rule. Наличие revoke authority само по себе не разрешает upload. Presentation/HTTP не становятся владельцами факта.

5. **Characterization v0.1 сохраняется как provenance, active contract получает явную supersession.** `specs/CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001.md` получает новую версию/решение GRILL-007 и полный updated final scenario. Verifier больше не catch-ит SQL exception как expected result, сохраняет before/after audit для первой revoked row и требует accepted new fact. Approved Gate 3 test/expected transcript меняются и проходят новый независимый review до GREEN claim.

6. **Owning modules and architecture.** Canonical DDL остаётся в `app/InstallationProcess/*SchemaMigration`; photo persistence остаётся текущему `ChecklistSync` seam; `rapid-pilot/verify-checklist-photo-revoke.php` остаётся verification adapter. Runtime не выполняет DDL. Architecture baseline не меняется.

## Risks / Trade-offs

- [Index migration accidentally drops the wrong generated-name index] → сравнивать semantic ordered columns + uniqueness и требовать ровно один exact predecessor; ambiguous forms fail closed before DDL.
- [Runtime продолжает требовать literal-v8 unique index] → сохранить v8 historical oracle, добавить successor final compatibility и перевести оба runtime consumer; тест требует canonical repeat и runtime admission final schema.
- [Two simultaneous uploads create two active identical rows] → сохранить case-row transaction serialization и добавить concurrent/revision-sensitive test; не полагаться на removed unique constraint.
- [Re-upload mutates revoked evidence] → fingerprint full original row/operations before and after; разрешить только new row/new operation/new revision.
- [Blob reuse becomes silent corruption] → existing content-addressed verification MUST подтвердить existing bytes/hash/size before returning reuse.
- [Characterization history is rewritten без provenance] → записать superseded SQL-error milestone и GRILL-007 authority в spec/review; изменить только final scenario.
- [Rollback canonical index after new repeated hashes] → до первого принятого repeated fact допустим code/index rollback; после него rollback только forward-fix, без удаления facts.

## Migration Plan

1. Зафиксировать current frontier, exact predecessor/final catalogues и populated-row/blob fingerprints; выбрать свободную literal version.
2. Обновить executable target spec and characterization expectation, получить новый independent test review и RED на старом unique schema.
3. Реализовать canonical migration/index definition без runtime DDL; проверить clean, predecessor-populated, repeat, incompatible/ambiguous and prefix isolation.
4. Подтвердить public upload→revoke→identical upload acceptance, active duplicate behavior, same-case serialization, history/blob retention and unchanged authorization.
5. Выполнить focused regressions, architecture/global-call checks и independent code review.
6. Подготовить deployment отдельно. Перед применением на stand снять exact backup/catalogue/row/blob evidence; deployment требует отдельного фактического шага и не входит в этот proposal.
