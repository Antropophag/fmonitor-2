## Context

См. `proposal.md`, delta spec и owner decision. Inspection-evidence v8 уже
перевёл `ChecklistSync::ensureSchema()` в read-only readiness; planning v9
сделал то же для inspection planning. Оставшийся ObjectQueue blocker — private
completion `ensureSchema()`, который выполняет `CREATE TABLE IF NOT EXISTS` под
DML-only principal.

Predecessor содержит одну populated table с `UNIQUE(case,fact_type)` и двумя
типами. Owner утвердил сохранение исходных facts, append-only correction с
обязательной причиной, обязательную декларацию и target 85/15.

## Goals / Non-Goals

**Goals:**

- Один persistence owner в `InstallationProcess` deployment layer.
- Lossless additive upgrade текущей populated table.
- Exact two-member family: immutable root facts и immutable correction chain.
- Read-only readiness для всех rapid-pilot consumers и DML-only verification.
- Family-wide preflight, deterministic conflict/repeat/partial recovery.

**Non-Goals:**

- Реализация correction command/UI или её authorization.
- Перенос PTO/declaration commands в новый application module этим slice.
- Premium/payment semantics, изменение checklist weights или backfill state.
- Runtime repair, destructive down migration и concurrent migration runners.

## Decisions

### 1. Сохраняем root table и добавляем correction ledger

Текущая `fm2_pilot_completion_facts` остаётся immutable root store. Новая
correction table хранит root reference, ordinal, nullable previous correction и
previous ordinal, replacement date, reason, actor и time. Composite self-FK
фиксирует same-root adjacent predecessor; CHECK фиксирует NULL shape для version
1 и `previous_version_no = version_no - 1` дальше; UNIQUE фиксируют один ordinal
и один direct successor. Exact encoding фиксируется executable schema spec и
RED fingerprint matrix. `details` не исправляется этим решением.

Альтернатива — переписать roots в универсальный event ledger — требует data
copy/cutover и расширяет риск. Добавление mutable corrected columns нарушает
append-only history.

### 2. Effective projection следует единственной correction chain

Root fact — version 0, corrections — gap-free ordinal chain начиная с 1.
Composite constraints обеспечивают физическую same-root adjacency и отсутствие
ветвей независимо от порядка конкурентных INSERT. Они не утверждают, кто и при
каких состояниях может инициировать correction. Future application seam обязан
lock-ить case/root и определить admission/stale policy в отдельном approved
behavior contract; этот ownership slice command не реализует.

### 3. Family migration выполняет read-only preflight до DDL

Каждый member классифицируется absent/exact/predecessor/conflict. Разрешены
clean, exact complete, documented exact predecessor и exact-compatible partial.
Любой conflict прекращает run до mutation. Missing members создаются в
deterministic dependency order после полного preflight.

### 4. Runtime получает один read-only readiness seam

Migration class публикует pure compatibility check; CompletionFlow adapter
вызывает его вместо DDL. ObjectQueue не владеет completion schema и не знает её
DDL. Missing/drift преобразуется существующей HTTP boundary в 503 с redacted
reference; successful DML-only path остаётся 200.

### 5. Architecture ratchet только уменьшается

Удаляется exact completion CREATE fingerprint и соответствующий rapid-pilot
mutation hotspot. Новые schema SQL разрешены только migration owner; baseline
не расширяется для сокрытия debt. Новая domain logic в `rapid-pilot` запрещена.

## Risks / Trade-offs

- [Correction schema случайно разрешит branch] → dedicated constraint/concurrency
  RED и exact metadata fingerprint до implementation.
- [Старые consumers читают только roots] → ownership slice сохраняет текущую
  projection; switch на effective corrections входит в отдельный approved
  behavior slice до появления correction command.
- [Prefix ceiling изменится из-за нового basename] → вычислить полный catalogue
  после выбора exact name и утвердить boundary в executable spec.
- [MariaDB implicit DDL commit оставит partial family] → full preflight и
  restartable exact-compatible partial completion.
- [Owner semantics смешаются с authorization] → schema хранит actor/evidence,
  но permissions и public command проходят отдельные Gates 1–5.

## Migration Plan

1. Зафиксировать evidence текущей table, landed catalogue, basename/prefix,
   collation и populated rows; написать executable schema spec.
2. Получить independent Gate 1 review и explicit owner approval exact spec.
3. Fresh RED author создаёт clean/repeat/predecessor/partial/conflict/prefix,
   preservation, correction-chain и DDL-denied runtime matrix; fresh reviewer
   утверждает tests.
4. Реализовать migration/runner registration и read-only readiness; удалить
   completion runtime DDL минимальным GREEN.
5. Выполнить focused behavior, architecture, fresh lifecycle и `make verify`,
   затем fresh independent code review.

Rollback после применения — только forward fix: tables/history не удаляются.
До deployment migration можно откатить code на предыдущую версию.
