# LEGACY-CONTROL-ENGINEER-MIGRATION-001 — перенос закреплений стройконтроля

Status: `ACCEPTED_FOR_GATE_2`
Owner decision: issue #20, уточнение 2026-09-16
Actors: active administrator with exact `access.administer`; offline migration operator
Public seams: Yii2 user administration legacy-link command; `php bin/fmonitor2-control-engineer-migration.php preview|apply|reconcile`

## 1. Identity link

IdentityAccess владеет append-only exact связью local `fm2_pilot_users.user_id` с positive legacy `users.id`. Одна local identity и один legacy ID участвуют максимум в одной current-связи. Команда требует lowercase UUIDv4 request ID, active actor/role и exact `access.administer`, сохраняет actor/server UTC time и bounded snapshot legacy id/name/email/status/role id/status. Имя, email, роль и совпадение numeric ID не являются authority.

Exact replay идемпотентен; иной fingerprint того же request ID конфликтует. Missing legacy user, duplicate link, inactive local engineer/role либо local user без active `construction_control_engineer` role отклоняются без фактов. Исправление — новый append-only superseding fact с обязательной причиной; старые rows не UPDATE/DELETE. Existing invite/role/activate flow создаёт local credential; legacy password/hash/session/rights никогда не читаются и не переносятся.

Yii2 `/pilot/admin/users` показывает безопасный legacy ID/status и POST link form только authorized actor. Yii identity, CSRF, method/content bounds и 303/400/403/409/422/503 semantics наследуют `YII2-USER-ACCESS-001`; controller не пишет SQL.

## 2. Preview

CLI принимает `preview --all-imported` либо 1..100 distinct canonical `--object-id=N`, обязательный `--operation-id=<lowercase UUIDv4>` и output path вне checkout. DB env/prefix contract наследует `PILOT-CASE-IMPORT-001`; web principal не имеет execute/write grants.

Preview одним snapshot читает target `fm2_installation_cases`, legacy `fm_maintable(id,responsstroicontrol)`, legacy users/roles, current identity links и standalone assignments. Он сортирует по object ID и выдаёт canonical UTF-8 JSON version/operation/sourceFingerprint/counts/rows и SHA-256 digest. Legacy и process fingerprints до/после совпадают.

Каждая строка имеет ровно один status/reason: `ready`; `already_applied`; `skipped` с `OBJECT_NOT_IMPORTED`, `LEGACY_ENGINEER_MISSING`, `LEGACY_USER_NOT_FOUND`, `LEGACY_USER_INACTIVE`, `IDENTITY_LINK_MISSING`, `LOCAL_ENGINEER_INELIGIBLE`; либо `conflict` с `IDENTITY_LINK_AMBIGUOUS`, `CURRENT_ASSIGNMENT_DIFFERS`, `SOURCE_CORRUPT`.

ФИО/email не используются как fallback. Any schema/cardinality/lineage ambiguity fail closed. Output не содержит password/hash/token/config/SQL/stack trace.

## 3. Apply и reconcile

`apply` принимает exact preview path, operation ID и expected digest. Он проверяет canonical bytes/digest, повторно читает и блокирует все authority facts в deterministic order. Любой drift/stale/conflict отклоняет весь batch без assignment facts.

Ready rows атомарно передаются batch method существующего ControlEngineerAssignment application owner. Immutable row сохраняет source `legacy_fmonitor`, operation ID, legacy object/user IDs, local engineer ID, actor identity, UTC time и request fingerprint. Ни CLI, ни controller не делают direct assignment INSERT. Signed orders/applications/originals/opening/checklist и legacy rows остаются byte-identical.

Exact successful replay создаёт ноль rows. Отличающееся current native assignment всегда conflict и не заменяется. Confirmed rollback возвращает safe failure; unknown commit outcome возвращает `IMPORT_OUTCOME_UNKNOWN` и проверяется только `reconcile`. UNKNOWN не является GREEN/success и не запускает mutation retry.

`reconcile` читает operation facts и возвращает deterministic terminal report для каждого selected object: applied/already_applied/skipped/conflict/unknown. Он не изменяет данные.

## 4. Acceptance A–L

A. Existing invite→role→link→activate создаёт local engineer и новый local credential без legacy password access.
B. Exact link сохраняется один раз; duplicate/ambiguous/missing/ineligible/unauthorized/replay matrix имеет stable outcomes и append-only audit.
C. Preview `all-imported` не включает legacy-only objects; explicit IDs не могут расширить target cases.
D. Ready row связывает `responsstroicontrol` legacy ID с exact confirmed local link; name/email collision ничего не применяет.
E. Missing/inactive/ambiguous data классифицируются exact reason и сохраняют обе БД.
F. Existing equivalent migration fact → already_applied; manual/different current assignment → conflict without replacement.
G. Preview canonical ordering/digest детерминированы и повтор не пишет данные.
H. Apply exact unchanged preview атомарно создаёт append-only assignments с полной provenance через owner.
I. Любой drift отклоняет весь batch до первой mutation.
J. Exact apply replay не создаёт users/links/assignments; reconcile совпадает с persisted operation.
K. Commit failure/UNKNOWN и concurrent commands fail closed/reconcile без ложного success.
L. Secrets, credentials, SQL/details не выходят; legacy rows и historical process documents/facts byte-identical.

## 5. Scope и verification

Gate 2 обязан покрыть A–L через real isolated MariaDB, real Yii HTTP для link flow и public CLI processes; class/private method не является единственным oracle. Required regressions: Yii user access, standalone assignment A–H, pilot case import. Runtime DDL, `rapid-pilot`, generic user migration, production apply, merge/deploy — вне scope. Full local suite запрещён; planner-selected focused checks, independent required reviews и один exact-source CI обязательны.
