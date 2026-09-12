# YII2-OTIZ-WORKFLOW-001 v0.1

## Простыми словами

Сотрудник ОТиЗ продолжает рассчитывать, проверять, подтверждать и выгружать расчёт, затем фиксировать выплату или сторно по тем же адресам и правилам. Меняется только framework owner: весь production HTTP-путь обслуживает Yii2 и вызывает существующие application owners; `RapidPilotOtiz` остаётся только историческим oracle. Формулы, полномочия, данные и смысл принятия не меняются.

## Scope, actor and public seam

Actor — активный пользователь FMonitor с exact permission `otiz.manage`. Guest SHALL получить redirect на явный Yii2 OTIZ login `/pilot/otiz/login` с сохранённым return URL; authenticated actor без exact permission SHALL получить denial до предметной операции. Near-match permission не допускает действие. Rapid session от `/pilot/login` не является Yii2 authentication и не SHALL неявно переноситься либо дублироваться.

Public seam — реальные HTTP method/path из `openspec/changes/yii2-otiz-workflow/inventory.md`, обслуживаемые `public/yii.php` и production Yii2 configuration. State-changing adapters MUST вызывать существующие seams:

- `FMonitor2\Otiz\SnapshotPublication::buildAndPublish`, `accept`;
- `FMonitor2\Otiz\OtizSettlement` для discipline, complete и reverse;
- существующие migrated-evidence и quarantine decision ledgers для их решений.

Read routes MUST использовать существующие `app/Otiz`/migration read models. Controller/view/JS MUST NOT создавать финансовые, acceptance, settlement, reconciliation или quarantine facts и MUST NOT владеть SQL-транзакцией.

Inherited behavior SHALL соответствовать `OTIZ-SNAPSHOT-PUBLICATION-001`, `OTIZ-SETTLEMENT-001`, `OTIZ-OBJECT-REGISTER-PAGINATION-001` и существующим reconciliation/quarantine contracts. При конфликте нормативный domain contract имеет приоритет над presentation parity.

## Calculate and publication

`POST /pilot/otiz/calculate` принимает Yii2 CSRF, `reportDate` и lowercase canonical UUID `operationId`.

- Валидная дата и новая операция SHALL атомарно создать полный draft snapshot, publication receipt и одно `draft_calculated`, затем вернуть `303 /pilot/otiz/snapshots/{id}?created=1`.
- Replay actor/operation/date SHALL вернуть тот же id без новых facts; та же операция с другой датой SHALL дать observable conflict без новых facts.
- Invalid date/operation, denial, CSRF failure, input/storage failure SHALL не оставлять header, children, receipt, event или orphan.
- Два concurrent одинаковых запроса SHALL дать один результат/receipt/event; читатель до commit не видит draft.

## Inspect, acceptance and return path

GET overview/payments/history/snapshot SHALL показывать существующие report date, status, totals, objects, allocations, issues, formula trace, publication/audit history и links без пересчёта сохранённого snapshot. `created`, `accepted`, `closed`, `paid`, `reversed` и error return URLs SHALL приводить на доступную Yii2 page с явным status/alert.

Draft snapshot form SHALL отправлять `POST /pilot/otiz/snapshots/{id}/accept` с Yii2 CSRF. `SnapshotPublication::accept` SHALL под lock проверить существование, draft, complete publication receipt/manifest и отсутствие blockers; success атомарно меняет только acceptance fields и добавляет одно `snapshot_accepted`, затем возвращает `303 ...?accepted=1`.

NOT_FOUND maps to 404. FORBIDDEN/CSRF denial, IMMUTABLE, BLOCKERS и SNAPSHOT_INCOMPLETE SHALL иметь стабильный observable HTTP outcome/return page и не добавлять facts. Concurrent accept SHALL не создать два acceptance events. Этот refactor сохраняет действующий contract; будущие separation-of-duties/evidence semantics из `CHARACTERIZE-OTIZ-SNAPSHOT-ACCEPTANCE-001` остаются `NEEDS_GRILL` и не утверждаются.

## Export and settlement adjacency

GET `/pilot/otiz/snapshots/{id}/export.xlsx` для accepted snapshot SHALL вернуть существующий XLSX с листами и metadata из сохранённых rows; draft/unknown/denied request SHALL не выдавать принимаемый реестр. Content type, disposition filename и workbook values SHALL оставаться проверяемыми через HTTP и parser.

Уже поставленные discipline, payment complete и reverse routes SHALL оставаться Yii2 adapters к `OtizSettlement`. Их authorization, CSRF, operation replay/conflict, global financial-object serialization, append-only closure/reversal facts, audit receipts, arbitrary-input rejection и redirects не изменяются. Полный golden journey SHALL доказать calculate → inspect → accept → export → payment → reverse на одном public runtime.

## Reconciliation, quarantine and historical reads

GET reconciliation, quarantine, active-baselines и historical-replay SHALL сохранить существующие filters/pagination, provenance, statuses, empty/error states и navigation. POST decision routes SHALL передать exact actor, operation UUID, source identities/digests, outcome, reason and occurred-at существующему application owner.

Invalid, stale, conflicting, replayed or forbidden decision SHALL сохранить установленный owner result mapping и не создавать лишний append-only факт. Повтор идентичной операции SHALL отображаться как duplicate/replay согласно существующему contract; несовместимый fingerprint SHALL быть conflict. UI не выводит секреты, raw exception/SQL или непроверенные primary evidence.

## Framework and runtime boundary

Yii2 MUST владеть Request/Response, routing, authentication/session, CSRF, error mapping, views and assets для всех inventory routes. Реальный production request к любому `/pilot/otiz...` MUST NOT require, instantiate, dispatch or call `RapidPilotOtiz`; production router MUST NOT использовать его для navigation permission probing.

Production preparation MUST создать закрытый durable Yii2 session directory в общем state volume до первого OTIZ request; обязательные cookie validation/identity keys MUST поступать через runtime environment. Известный Yii HTTP rejection SHALL сохранить свой HTTP status, а неожиданный infrastructure failure SHALL остаться безопасным `503 SERVICE_UNAVAILABLE` без раскрытия деталей.

`rapid-pilot/Otiz.php`, `verify-otiz-*` и pilot characterization MAY оставаться как non-production behavioral evidence. Их существование не считается runtime dependency. Нельзя ослаблять route admission/CSP/architecture checks ради GREEN.

## Verification and Done

### CI regression boundary — PR #103

Изолированные Compose fixtures MUST явно задавать тестовые cookie-validation и identity keys, не наследуя секреты локального стенда. DDL inventory MUST проверять реальные перенесённые `app/Otiz` implementations вместе с compatibility wrappers.

Recovery rehearsal SHALL сохранять прежний протокол: historical bundle восстанавливается exact historical image; после additive database migrations явный текущий `bin/fmonitor2-runtime-prepare.php` добавляет закрытый `yii-sessions` до текущих readiness/backup. Подготовка MUST сохранять все существующие файлы, права и исторические DB/AUTO_INCREMENT facts; повтор MUST быть no-op. Текущий backup/restore SHALL сохранять содержимое и права Yii2 session files наряду с остальным private state. Backup/readiness MUST NOT неявно готовить отсутствующие каталоги или ослаблять проверки безопасности.

Complete intended RED/GREEN SHALL проверять через настоящий Yii2 HTTP/browser: guest return, exact permission, CSRF, calculate success/replay/conflict/concurrency/rollback, snapshot content, accept blocker/incomplete/immutable/concurrency, XLSX, reconciliation/quarantine decisions, historical reads, adjacent settlement and runtime dependency absence. DB fingerprints служат дополнительным доказательством facts/no-facts.

Focused checks, independent Gates 3/5 and exact-source Quality Graph CI MUST быть GREEN. Deployment остаётся `UNKNOWN` без отдельной owner authorization. Общий rapid-pilot cutover, console/import/workforce и acceptance redesign этим spec не закрываются.
