## 1. Gate 1 и verification plan

- [ ] 1.1 Root обновляет current delivery goal, product/data-model contract и создаёт normative executable specs для identity link и migration CLI; проверить трассировку всех OpenSpec scenarios и explicit non-goals #20
- [ ] 1.2 Root создаёт `verification-input.json`, вычисляет planner-selected plan через delivery harness и закрывает все Quality Graph obligations до Gate 2

## 2. Gate 2 — executable RED

- [ ] 2.1 Root пишет focused IdentityAccess tests для exact-ID link, duplicate/ambiguous/missing/inactive cases, authorization, replay, correction history и отсутствия legacy credential inheritance; подтвердить intended RED на отсутствующей capability
- [ ] 2.2 Root пишет public Yii2 admin tests для invite → local role/permission → legacy link → activation/first login, CSRF/admission/escaping и отсутствия token/password disclosure; подтвердить intended RED только на link UI/seam
- [ ] 2.3 Root пишет isolated MariaDB CLI tests preview/apply/reconcile для ready/skipped/conflict, explicit allowlist/all-imported, drift, all-or-nothing, repeat, commit-unknown и read-only legacy fingerprint; подтвердить intended RED без production importer
- [ ] 2.4 Подготовить exact Gate 3 package и получить независимый `gpt-5.6-sol/low` review полноты и чувствительности executable RED, если planner требует Gate 3

## 3. Gate 4 — минимальная реализация

- [ ] 3.1 Executor добавляет additive production schema для append-only legacy identity links/audit и migration operation provenance; focused schema/migration tests проходят без runtime DDL и скрытых grants
- [ ] 3.2 Executor реализует один IdentityAccess link/correction owner и bounded Yii2 admin controls, переиспользуя invitation/role/activation flow; focused owner и HTTP tests проходят
- [ ] 3.3 Executor реализует deterministic read-only preview CLI с canonical JSON/digest, stable classifications и secret-safe failures; focused preview tests проходят и legacy fingerprint неизменен
- [ ] 3.4 Executor расширяет public ControlEngineerAssignment owner атомарным batch migration seam с provenance, replay, conflict и stale checks; owner/transaction tests проходят без direct CLI SQL writes
- [ ] 3.5 Executor реализует digest-bound apply/reconcile CLI и exact commit-outcome handling; focused apply, repeat, drift, race и reconciliation tests проходят

## 4. Gate 5 и PR-ready

- [ ] 4.1 Запустить только planner-selected bounded checks, architecture check и affected IdentityAccess/assignment/object-import regressions; полные evidence сохранить вне checkout, не запускать local full suite
- [ ] 4.2 Подготовить exact final-review package и получить независимый `gpt-5.6-sol/low` APPROVED Gate 5 без unresolved findings
- [ ] 4.3 Создать meaningful commits, открыть PR из exact reviewed source и запустить один parallel exact-source GitHub CI consumer; при failure сначала собрать полный failed-job и `REGRESSION_FAILURE` inventory
- [ ] 4.4 Подтвердить PR-ready только при GREEN exact-source CI, APPROVED required reviews и совпадении source; фактический production apply, merge и deploy не выполнять
