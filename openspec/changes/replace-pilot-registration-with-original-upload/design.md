## Context

См. `proposal.md` и delta-spec. Текущий workflow связывает opening с manual `prepared → registered`; owner truth уже перешла на подписанный PDF. Этот change намеренно заканчивается на secure original evidence seam. Применение состава и opening будут отдельными changes, чтобы каждый Gate 2 доказывал одну acceptance statement.

## Goals / Non-Goals

**Goals:** один application owner для initial/correction, exact process authorization, deterministic PDF security boundary, immutable lineage, semantic idempotency и zero-public-orphan failures.

**Non-Goals:** HTTP/upload route, metadata-read/download, менять composition/opening, моделировать sequential-order ties, OCR/signature/malware inspection, интегрировать 1С ДО, удалять historical registration facts или добавлять domain logic в `rapid-pilot/`.

## Decisions

### 1. Contract supersession — prerequisite Gate 1, не implementation tail

Canonical `CONTEXT.md`, pilot spec и pilot data model уже синхронизированы с owner-approved original-PDF truth. До executable spec оставшиеся активные interface/inventory/dependent changes/tests получают explicit disposition: target requirements amended; tests текущей реализации сохраняются как legacy characterization либо блокируются predecessor-ом и не считаются target acceptance; historical review/evidence records не редактируются. Найденные families: `docs/installation-process-interface.md`, `docs/operations/pilot-behavior-inventory.md`, active `pilot-e2e-rbac-fixtures`, `pilot-e2e-combined-pdf`, registration/opening application specs/tests и downstream characterization, использующая historical registered crew. Opening implementation остаётся predecessor для `open-installation-from-assignment-order-original`; этот slice не может объявить end-to-end pilot journey GREEN.

### 2. Один command с двумя modes и optimistic revision

Assignment Orders владеет `submitAssignmentOrderOriginal(Command): Result`. `INITIAL` создаёт root identity и отдельную revision identity; `CORRECTION` передаёт root, target revision и expected current revision. DTO/result/reason codes нормативно закрыты executable spec. Composition берётся production query по order identity, не доверяется HTTP payload.

Отдельные update-file/update-date endpoints отвергнуты: они допускают partial/mutable history. Sequential composition intentionally отвергается `SEMANTIC_COLLISION` до отдельного slice.

### 3. Exact capability grants

Новые exact strings `assignment_order.original.upload` и `.correct` добавляются в process capabilities. Bootstrap/administration явно выдаёт обе пользователям builtin technical role codes `fkr_operator` и `manager`; `manager` сохраняет code и получает display «Руководитель ФКР». Runtime seam проверяет explicit user capability row плюс active user/role. Ни `assignment_order.prepare`, ни legacy role/name не являются fallback. Future HTTP slice вводит отдельный exact local/read permission `assignment_order.original.read`; он не выводится из upload/correct.

### 4. Deterministic PDF policy

Caller adapter передаёт stream; application/storage boundary считает received bytes и прекращает чтение после `20,971,520 + 1`. MIME/magic — быстрый prefilter. Production использует owned `FMonitorPassivePdfInspector` algorithm `fmonitor-passive-pdf-v1` с literal grammar/bounds/active-key set executable spec; TCPDF 6.11.4 остаётся renderer и не валидирует input. Algorithm version меняется только новым Gate 1/2.

### 5. Storage/persistence publication protocol

Document storage finalizes private content и возвращает typed lease из общего с maintenance digest exclusion domain. Lease held через commit/rollback, fresh unknown-outcome lookup и, для CAS `CONFLICT`, через обязательные fingerprint/current-lineage rereads. После выбранного conflict outcome release вызывается exactly once; failure не заменяет outcome, safe-log-ится и оставляет token storage recovery. Non-replay conflict attempt-audit выполняется даже при release failure. Private blob без DB row не public/applicable; maintenance/retry работают только через тот же exclusion domain.

### 6. Idempotency и correction ties

После shape/authorization request-ID hit возвращает stored result до stream read и владеет retry identity. При miss stream hash позволяет accepted-operation fingerprint lookup; затем идут expected-current, target-current и no-change checks. Раздельные root/current/target revision IDs делают `STALE_REVISION` и `TARGET_NOT_CURRENT` наблюдаемыми. CAS даёт одного winner; upload time не разрешает tie.

### 7. Persistence owner и schema direction

Additive canonical migration вводит immutable root/revision identities, terminal request results, semantic fingerprint, one-current-leaf CAS, composition hash, dates, digest/size/storage identity, actor и reason. Rejected/conflict result и safe audit сохраняются атомарно; retryable failures не становятся terminal request hits. Existing registration facts не переписываются. Literal migration version назначается по актуальному frontier.

### 8. HTTP/read/download boundary отложен

Отдельный future change `expose-assignment-order-original-http` обязан определить exact methods/routes/status/body, multipart limits, CSRF/session, local permissions, metadata DTO, actor/reason/filename visibility, not-found/forbidden indistinguishability, download authorization/digest/headers. Текущий command slice не создаёт query или HTTP surface.

### 9. Architecture impact

Разрешённые зависимости текущего slice: verifier/caller → public application seam; application → authorization/composition/repository/storage/clock ports; MariaDB/filesystem/parser adapters → ports. Future HTTP/rapid-pilot adapter сможет зависеть только от того же seam. Запрещены direct HTTP writes, runtime DDL, public web storage и opening/composition mutation. `make architecture-check` должен закрепить границы.

### 10. Gate 2 constructibility API

Executable spec v3 фиксирует namespace `FMonitor2\AssignmentOrderOriginal`, typed application/DTO/result/stream/auth/composition/clock/ID/PDF/staged-storage/repository/observer/evidence/maintenance contracts. Upload и maintenance имеют отдельные production/verification factories и exhaustive dependency bundles; maintenance authorizer принимает string system principal. Production связывает real inspector/private storage/no-op observers и не выбирает verifier composition по environment/request/CLI/global.

Request replay после authorization предшествует order/clock/stream. New-request semantic replay требует completed staged bytes/hash. Current composition drift относительно root snapshot делает collision наблюдаемым без caller composition. Repository принимает typed accepted/attempt commit DTOs, использует `READ COMMITTED`, unique request/fingerprint и CAS current revision. Worker bootstrap получает serializable config path и пять dedicated FDs; barrier имеет READY/RELEASE protocol после fingerprint miss до CAS. Private orphan maintenance получает typed candidate pages/cursor/digest locks/reference recheck/delete и atomic terminal result+audit; upload получает typed finalized-content lease и не releases его до terminal DB/unknown resolution.

## Risks / Trade-offs

- [DB/filesystem не имеют общей транзакции] → private finalize до DB commit; private orphan не observable, bounded reconciliation/reuse; stored accepted result разрешает response-loss retry.
- [Parser bugs] → pinned production parser, adversarial fixtures и fail-closed unsupported/active/encrypted behavior.
- [Active truth amended раньше code] → documents явно маркируют original upload как planned and opening replacement as future, чтобы не выдавать незавершённый journey за GREEN.
- [Role `manager` получает mutation] → только два exact new capabilities, no wildcard/inheritance, independent RBAC RED.
- [Correction document date позже/раньше иных orders] → этот slice хранит evidence lineage, но не применяет её к composition/opening; applicability решает следующий change.

## Migration Plan

1. Coherent supersede active manual-number/registration truth, сохранить historical records; strict validate и fresh independent planning rereview.
2. После constructibility amendment получить fresh independent Gate 1 review и новый owner exact-hash approval; прежний v1 approval сохраняется исторически и не разрешает Gate 2 по v3.
3. Продемонстрировать minimal RED, получить fresh independent test review.
4. Добавить exact capability migrations/grants, canonical original schema, storage/parser adapters и один command минимальным GREEN.
5. Выполнить focused tests, architecture-check, `make verify`, fresh independent code review.
6. Только после Done создать через отдельные propose workflows `expose-assignment-order-original-http`, `apply-assignment-order-original-to-composition`, затем `open-installation-from-assignment-order-original` по их зависимостям.

Rollback до production facts отключает route/composition. После появления facts rollback только forward-compatible: bytes/revisions/audit не удаляются.

## Open Questions

Нет. Applicability к составу и opening не являются вопросами этого change, а явно отложены в отдельные lifecycle slices.
