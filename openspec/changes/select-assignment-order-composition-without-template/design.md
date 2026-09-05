## Context

См. proposal и independent seam inventory `16ba3e65401ab6b3acb883a96f590bc1e9eb7c3d`. `InstallationProcess::prepareAssignmentOrder` вызывает renderer до persistence. `submitAssignmentOrderOriginal` читает существующий order/composition. Fixtures с direct SQL не доказывают production direct upload.

## Goals / Non-Goals

**Goals:** один production owner выбора состава; immutable identity совместима с original command; отсутствие renderer/storage dependency в selection; atomic replay/concurrency/audit.

**Non-Goals:** менять original PDF parser/storage, применять действующий состав, открывать работы, создавать domain logic в rapid-pilot, выдавать selection права читателям документов.

## Decisions

1. Владелец selection — production application module распоряжений. Candidate seam `selectAssignmentOrderComposition(command)` выражает намерение, HTTP/CLI не пишут process tables.
2. Существующий prepare workflow разделяется на selection и optional render только после executable contract и reviewed regression. Альтернатива «вызвать prepare и скрыть PDF» не удовлетворяет no-template dependency.
3. Persistence order/member snapshots не дублируется новым независимым HTTP writer. При необходимости additive schema change получает exact manifest/version и свои Gates; runtime DDL запрещён.
4. Original command получает уже сохранённую identity. Existing derivation из physical `order_date` и member validity требует явного compatibility решения в Gate 1: дата выбора/шаблона не подменяет documentDate original.
5. Allowed dependencies: caller → selection API → authorization/catalog/repository/clock ports. Selection не получает renderer port; optional rendering — отдельная операция того же owner. Architecture ratchet проверяет отсутствие bypass и renderer calls в selection.
6. Политика pre-original correction уже утверждена exact owner record 1842Z: только новая immutable selection/version, видимая история, без изменения accepted composition. Permission mapping и точные DTO/storage outcomes остаются technical Gate1; mutable draft не вводится. Новые продуктовые вопросы имеют NEEDS_GRILL disposition и откладываются до возвращения владельца.
7. Existing projection gap доказан в `docs/operations/selection-existing-assignment-projection-gap-2026-09-05.md`: directory фильтрует registered order, но MAX(version) берёт среди всех orders; новая selection скрывает старое назначение. No-application contract охватывает public directory availability/assignments и inspection actor/installer attribution, а не только неизменные interval rows. Изолированный переход на MAX(registered) не является target original applicability. Gate 1 согласует selection visibility с owning lifecycle contract до implementation.

## Risks / Trade-offs

- [Existing prepare одновременно фиксирует даты/интервалы] → exact no-application invariant и regression до переключения HTTP.
- [Manager может upload, но existing prepare grants иные] → coherent selection authority в Gate 1 без implicit inheritance от read.
- [Ошибочный выбор до original] → explicit append-only correction/version contract; не добавлять silent in-place edit.
- [Optional renderer failure ломает direct upload] → independent selection success и preserved identity при failed render.

## Migration Plan

Подготовить executable `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001`, coherent disposition existing prepare/HTTP expectations и independent Gate 1. Затем RED → Gate 3 → minimal owner/persistence → relevant regression/architecture → Gate 5. Подключить direct HTTP flow только после approvals. Existing historical prepared/rendered orders и original revisions не переписываются при deployment или rollback к предыдущему коду.

## 2026-09-05 technical storage disposition — pending executable Gate 1

The chosen drafting direction is a separate immutable selection ledger plus
one canonical assignment-order identity registry/allocator shared by both
legacy preparation and new selection. This resolves the previously open choice
of storage owner; it is not an approved schema manifest or implementation gate.
The preceding no-template/no-application product invariants remain unchanged.

A selection does not create fm2_assignment_orders/fm2_order_installers rows and
never supplies invented order_date/valid_from/valid_to. Existing effective
readers therefore cannot see pending B through their MAX(all order versions).
Their current registered predicate is preserved only as predecessor behavior;
the target original-applicability owner remains a required separate migration.

The identity registry must own both numeric assignmentOrderId and per-case
order version, with uniqueness for ID and (caseId,version). Each entry has one
immutable source-kind discriminator, legacy_order or selection. Identity
allocation and domain persistence commit in the same transaction under the
case-scoped serialization boundary; failure cannot leave an acknowledged ID
without its source fact. Existing historical IDs and case/version pairs are
preserved exactly as registry metadata, never renumbered or reconstructed as
new domain events. New IDs come from that one allocator, not independent table
AUTO_INCREMENT sequences and not MAX()+1 in callers.

The later executable migration contract must define exact shapes, identity
backfill/validation, next-ID frontier including existing AUTO_INCREMENT gaps,
collisions, overflow, bounded locking, partial restart and preservation. It must
cover switching legacy preparation to explicit allocated IDs in the same
release; enabling selection while an old writer still allocates independently
is forbidden. Clean deployment stops application writers before migration,
verifies registry↔source completeness and starts only compatible writers.
Runtime readiness fails closed on absent/drifted registry; no lazy DDL/backfill
or duplicate-owner precedence is allowed. Rollback to an incompatible writer
must be explicitly blocked in the eventual deployment contract.

Original composition lookup resolves registry ownership at the exact numeric
ID and case inside its read-only snapshot. legacy_order retains the existing
strict date/member rules; selection reads exact immutable dateless membership.
Both yield the already approved composition-ID/JSON/hash contract. Missing both
identity and source is NOT_FOUND; contradictory ownership, orphan source,
missing registered source, duplicate case/version or unavailable inspection is
UNAVAILABLE, not fallback. Source validation failures retain a separately
specified invalid-composition result. This requires an explicit original-reader
contract amendment before implementation; safe-log work is not changed.

Optional rendering must address the same selection identity and record its own
immutable template/date/artifact facts without creating effective intervals or
converting selectionDate into templateDate. Application/opening later consumes
accepted original + selection identity through the one applicability owner.

Next deliverable is one coherent executable Gate1 batch covering this registry,
selection family, original-reader branch, existing-writer handoff, typed command
ports/replay/audit and preservation. No migration version is reserved by this
note: choose the actual next version only after reading the then-current
catalogue. No task checkbox advances and no RED/code is authorized here.
REPLACE_PENDING user-visible correction/history теперь APPROVED exact owner
record 1842Z; это не blanket technical approval остального batch.

## Combined release compatibility obligations — 2026-09-05

The new ledger is not permission to disable the owner's optional-template path.
For a selection-owned case, target HTTP must call the new selection owner and
an optional-render operation that uses that exact immutable identity. It must
not call legacy prepare to manufacture another order or satisfy its physical
N-1 predecessor requirement. The legacy guard is a mixed-writer safety condition,
not successful feature delivery. The same-identity template/artifact persistence
and public read path must have executable contracts and reviewed implementation
before direct/optional parity or the parent change can be called complete.

Existing all-legacy prepare behavior needs an explicit regression amendment for
registry allocation while preserving existing domain result/date/artifact/event
semantics. The registry writer, original reader source branch and target selection
writer form one compatible release. Legacy HTTP routing is separately migrated;
no protected E2E or manual-registration target authority follows from this plan.

State lookup must reconcile the newest registry source with ledger history.
In particular, latest ledger selection revision0 cannot imply absence of an
unsigned legacy prepared order. The consolidated Gate1 candidate must state
pending legacy/no-original, accepted-original legacy and preserved registered
predecessor outcomes explicitly. No auto-adoption, new original fact, date
fabrication, hidden render, deletion or silent replacement is allowed. Registered
legacy is a preservation predecessor only, never the target applicability rule.

Release evidence must include fresh-case direct upload without renderer, optional
render for the same selection identity, existing historical legacy read/prepare
preservation, pending legacy rejection without mutations, accepted original plus
new pending selection preserving applicable crew, and restart with the complete
registry/source facts. Full make verify and real original-first public golden
remain mandatory; the independently green selector cannot replace them.

## Typed construction correction v0.5

Результат создаётся только `SelectionResult` factories; reason/retryable/success
комбинации и fixed programming-error исключения заданы executable spec.
Lookup constructors private; отсутствие и unavailable различаются явно.
Snapshot carriers пассивны, application owner валидирует malformed values и
возвращает dependency_unavailable до persistence. Dismissed/out-of-period worker
даёт отдельный installer_not_employed, не masquerading infrastructure absence.
Event/audit pre-insert payload не содержит generated ID: storage возвращает
stage receipt, который не является commit acknowledgement. Observer читает
persisted envelopes с IDs. Legacy state использует closed enum и truth table;
transaction state lookup failure остаётся dependency failure, без allocation.

Actual source inventory `docs/operations/selection-writer-reader-cutover-inventory-2026-09-05.md`
фиксирует одного физического creator, direct HTTP status/artifact writers и
legacy-only original reader. N-1 не знает registry: простое создание таблиц не
останавливает старый writer. Нужен отдельно утверждённый concrete cutover;
режим rolling mixed N-1/N пока не разрешён. No runtime DDL, same-identity
optional render и public preservation obligations не ослаблены. Это planning,
не Gate1 APPROVED и не разрешение implementation.

## Technical flow correction v0.6

По independent v0.5 rereview один invocation-owned clock читается lazily перед
первым необходимым audit/terminal fact; full matching replay clock не читает.
Clock failure до persistence даёт dependency_unavailable. Callback/UoW передают
closed rollback cause; stage не владеет commit/rollback. Полная таблица
stage→decision→UoW→public outcome закреплена в executable v0.6, включая
request race, invalid generated receipt и unknown acknowledgement. Это technical
уточнение прежних outcomes; Gate1 и P0 release dependencies остаются открыты.

## Schema constructibility correction v0.7

MariaDB AUTO_INCREMENT IDs не могут иметь CHECK на сам ID. В executable v0.7
registry/event/audit IDs сохраняют UNSIGNED physical type; bounds обеспечивает
public allocator и storage pre-commit/read validation с прежними failure
outcomes. Новый registry engine change готовит отдельный exact contract; его
planning не закрывает writer cutover, original reader или optional renderer.
