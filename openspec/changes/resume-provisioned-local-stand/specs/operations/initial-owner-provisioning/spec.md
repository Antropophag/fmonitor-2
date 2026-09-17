## MODIFIED Requirements

### Requirement: Replay безопасен, existing state отвергается
Production provisioning без явно выбранного local-resume режима SHALL сохранять clean-create/exact-replay контракт: exact единственный user с requested email, empty phone, session_version 1, active credential с `password_verify`, обеими exact bootstrap grants и пустой auxiliary identity history даёт byte-zero-mutation replay, а любое другое existing состояние отвергается без seed/repair/promotion.

Local `make up` SHALL явно выбирать отдельный read-only resume существующего стенда. Resume SHALL вернуть success только когда requested normalized email однозначно указывает на одного active/unblocked owner, его credential существует, а его действующие назначения `user` и `superadministrator` имеют bootstrap provenance с null actor и обеспечивают необходимые текущие owner permissions. Наличие других пользователей, приглашений, auth/status/role history, изменённых profile/session/credential данных либо дополнительных не-bootstrap ролей само по себе SHALL NOT блокировать resume. Resume MUST NOT сравнивать или изменять текущий password secret и MUST NOT изменять users, credentials, sessions, profile, grants, invitations или историю.

Чужой email, отсутствующий либо неоднозначный bootstrap owner, disabled/blocked owner, отсутствующий credential, отозванная/неактивная обязательная bootstrap grant или недостаточные owner permissions SHALL быть отвергнуты без seed, repair, promotion или восстановления полномочий. Произвольная непустая identity-БД без доказанного bootstrap owner SHALL NOT превращаться в success.

#### Scenario: Exact replay
- **WHEN** production provisioning с тем же email/password повторяется после `created` и identity остаётся exact первозданной
- **THEN** exit 0/status `already_provisioned`, все identity bytes unchanged

#### Scenario: Развитый локальный стенд продолжается
- **WHEN** local `make up` повторяется для однозначного active bootstrap owner после входа, изменения его password/profile/session, появления другого пользователя, приглашения и append-only identity history
- **THEN** startup получает успешный read-only resume, число и содержимое users, credentials, sessions, grants, invitations и history остаются byte-identical

#### Scenario: Непригодный локальный владелец
- **WHEN** expected owner чужой или неоднозначный, disabled/blocked, потерял обязательную bootstrap grant либо необходимые owner permissions, или identity содержит только email-совпадение без bootstrap provenance
- **THEN** local startup возвращает понятный fail-closed outcome и все identity данные остаются unchanged

#### Scenario: Existing conflict
- **WHEN** production provisioning без local-resume получает любую partial identity либо invited, blocked, different-password, changed-history или multiple-user state
- **THEN** exit 65/reason `IDENTITY_NOT_EMPTY`, users/roles/credentials/grants/invitations/events unchanged

### Requirement: Operation отделена от migration и startup
CLI SHALL проверить canonical readiness без DDL, работать под explicit provisioning authority, получить per-database/prefix lock timeout 0 и всегда release. Web/FPM, migration runner и production deployment startup SHALL NOT неявно выбирать local-resume. Только repository-owned local `make up` SHALL вызывать явный local-resume после migrations; прямой CLI без этого явного режима сохраняет production clean-create/exact-replay semantics.

#### Scenario: Schema отсутствует
- **WHEN** canonical identity schema не готова
- **THEN** exit 70/reason `SCHEMA_NOT_READY` без DDL/repair

#### Scenario: Concurrent attempts
- **WHEN** два operators одновременно provision clean database либо resume один существующий local stand
- **THEN** lock сериализует проверку: clean path создаёт не более одного user/grant set, а resume выполняет только read-only подтверждение или возвращает busy/conflict

#### Scenario: Реальный local-up маршрут
- **WHEN** оператор запускает повторный `make up` существующего local project
- **THEN** Make route достигает explicit local-resume через canonical CLI и IdentityAccess owner, не создавая второго bootstrap и не ослабляя direct production CLI
