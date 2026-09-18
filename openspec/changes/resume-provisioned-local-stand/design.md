## Context

См. `proposal.md`. Сейчас `Makefile up` после migrations вызывает production-oriented CLI, а `MariaDbInitialOwnerProvisioning::isExactReplay()` одновременно проверяет происхождение owner и первозданность всей identity-БД. Поэтому обычные login/history/additional-user facts превращают безопасный повтор local startup в `IDENTITY_NOT_EMPTY`.

Owning module остаётся `app/IdentityAccess`; CLI — транспорт, Make — local orchestration. Persistence owner и schema не меняются, `rapid-pilot` не участвует. Работа чувствительна к identity и не подходит для FAST.

## Goals / Non-Goals

**Goals:**

- сохранить один canonical initial-owner owner и advisory-lock/transaction admission;
- отделить строгий production create/exact replay от explicit read-only local continuation;
- распознавать owner по совокупности identity facts и bootstrap provenance;
- доказать zero mutation на success и rejection через изолированную MariaDB и реальный Make route.

**Non-Goals:**

- repair, role reseed, password/profile/session reset или автоматическое восстановление revoked authority;
- новый bootstrap owner, schema migration либо изменение обычных access-management seams;
- остальные части #185, #182, planner/harness algorithms, skip policy и architecture exceptions.

## Decisions

1. CLI получает явный local-resume intent, который передаёт только `make up`. Без intent существующий public production contract остаётся строгим. Альтернатива — автоматически разрешать развитую identity в `provision()` — отвергнута, потому что ослабляет production bootstrap.

2. IdentityAccess предоставляет отдельную операцию/ветвь подтверждения уже provisioned local owner под тем же per-database/prefix lock. Она читает expected normalized email, ровно одного matching user и ровно одного непротиворечивого набора обязательных `origin=bootstrap`, `assigned_by_user_id IS NULL` назначений. Дополнительные users/history/roles допустимы, но второй кандидат с bootstrap owner provenance делает состояние неоднозначным. Email без provenance недостаточен.

3. Resume проверяет active/unblocked user, существование credential и эффективные owner permissions через активные обязательные bootstrap roles. Он не использует supplied bootstrap password как oracle, потому что password мог законно измениться; secret остаётся обязательным только для clean create/strict production replay. Missing/revoked/inactive authority — rejection, не repair.

4. Resume является read-only по identity. Снимок таблиц до/после в focused DB tests доказывает сохранность users, credentials, sessions, grants, invitations и histories. Lock обеспечивает coherent read; никакие startup writes не добавляются.

5. Normative contract остаётся `INITIAL-OWNER-PROVISIONING-001`; OpenSpec delta модифицирует существующую capability. `capability_ownership` регистрируется адресно только если planner иначе не связывает изменяемый owner и focused tests. Algorithms, exceptions и classifications не меняются.

## Risks / Trade-offs

- [Forged email-only user ошибочно принят] → обязательны bootstrap grant provenance, null actor, active roles и effective owner permissions.
- [Законная смена пароля ломает startup] → local resume не сравнивает и не перезаписывает password hash.
- [Отозванное полномочие молча восстановлено] → missing/inactive grant или permission даёт fail-closed rejection без writes.
- [Дополнительный bootstrap candidate создаёт ambiguity] → проверка глобальной однозначности provenance, не только email lookup.
- [Production caller случайно получает local semantics] → opt-in существует только на Make route; default CLI и owner method остаются строгими.
- [Planner не видит узкий identity seam] → допускается только точечная ownership/test registration, после чего planner обязан выбрать STANDARD/CRITICAL и reviews Gate 3/5.

## Migration Plan

Schema/data migration отсутствует. Delivery идёт отдельным PR от `e419c2b5` (merge #188): обновить contract/tests, получить RED и Gate 3, затем executor реализует minimal change. Bounded local checks используют disposable test DB; один exact-source CI запускается после независимого Gate 5. Rollback к предыдущему коду не меняет данные, но возвращает ошибку повторного local `make up`. Merge/deploy не выполняются.
