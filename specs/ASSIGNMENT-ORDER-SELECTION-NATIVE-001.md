# ASSIGNMENT-ORDER-SELECTION-NATIVE-001 — native binding selection

Версия 0.1, 2026-09-06. Candidate Gate1; production/tests ещё не написаны.

## Простыми словами

Подключаем уже утверждённый выбор состава к MariaDB. Обе команды сохраняют
реальные неизменяемые факты; повтор возвращает прежний результат. PDF, HTTP,
открытие и перенос старых writers остаются следующими самостоятельными пакетами.

## Authority и public seam

Наследуется ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.11 целиком с controlling
section17; core Gate5 `reviews/code/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001-core-v1.md`.
Этот документ уточняет native construction и verification, не меняет outcomes.
Schema/registry и registered reader approvals из handoff1733Z переиспользуются.
Canonical migration frontier13 не резервируется и не изменяется этим binding.

Namespace `FMonitor2\AssignmentOrderComposition`:

```php
final class ProductionAssignmentOrderCompositionFactory
{
    /** @param \Closure():\mysqli $openFreshConnection */
    public static function create(\mysqli $connection, \Closure $openFreshConnection,
        string $tablePrefix = ''): AssignmentOrderCompositionApplication;
}
final class AssignmentOrderCompositionNativeVerificationFactory
{
    /** @param \Closure():\mysqli $openFreshConnection */
    public static function dependencies(\mysqli $connection, \Closure $openFreshConnection,
        string $tablePrefix = ''): SelectionDependencies;
}
```

Production factory собирает прежний `AssignmentOrderCompositionFactory::create`
из восьми native ports. Verification factory возвращает те же ports; тест может
заменить только clock на deterministic SelectionClock и собрать прежний factory.
Нет renderer, файлового storage, mutation callback/fault hook или SQL в caller.
Connection factory — infrastructure opening только; возвращает новое независимое
соединение с той же БД/пользователем/charset, без активной transaction. Recovery
закрывает его во всех исходах. Повторное использование primary connection
запрещено. Primary принадлежит caller, factory его не закрывает. Native operations
не допускают ambient transaction и не commit/rollback чужую работу.
Prefix exact ASCII `[A-Za-z0-9_]{0,25}`; неверный prefix вызывает
`InvalidArgumentException('Invalid selection table prefix.')` до I/O.
Factories не выполняют migration/DDL и не регистрируют disabled engines.

## Восемь bindings

1. Authorizer читает active local user, active builtin role и exact permission
   `assignment_order.composition.select`; только builtin fkr_operator/manager.
   Нет wildcard/custom-role/display-name grants. Local mode определяется наличием
   canonical local identity schema, повреждение schema не переключает в legacy.
   Alternative legacy mode следует section5 core contract, без local grant.
2. Facts читает existing case по legacy object ID и данные `fm_maintable`;
   нет создания case. Отсутствующий объект/case даёт NOT_FOUND, query/drift/неполная
   связность existing case даёт UNAVAILABLE. `workdatefinish` означает completed,
   `ptoactdate` означает PTO; null/пустая/zero legacy date означает отсутствие,
   malformed nonempty date — unavailable. Native completion facts declaration/PTO
   также блокируют selection; correction не удаляет факт наличия основания.
   Каталог — `fm2_workforce_catalog`, engineer — existing process user directory
   с точной ролью construction_control_engineer. Batch snapshots sorted и полны.
3. Clock возвращает системный UTC instant seconds; Moscow date вычисляет core.
4. Terminal reader в read-only consistent snapshot декодирует request, проверяет
   canonical tuple/digest/result и связность selected result с registry/selection.
   Ошибки не становятся NOT_FOUND; rollback/release read snapshot проверяется.
5. Case UoW владеет одной transaction и exact case row lock. Повторно читает
   request и актуальное состояние после lock, serializes same-case writers.
   Registry allocation принадлежит одному native session allocator: server decimal
   до int conversion, один reservation, версия от locked registry history.
   Legacy-owned history в fresh contour даёт dependency_unavailable, как core.
   Accepted root проверяется для exact case/order и composition; отсутствие root
   отличается от unavailable/противоречивой lineage. Pending selection не задаёт
   effective order. UoW проверяет echoes allocation/result/event/audit/intent и
   один stage перед commit; missing/multiple/wrong stage — confirmed rollback.
6. Fresh reader открывает независимое соединение только для recovery и закрывает
   его, не повторяет mutation. Primary transaction outcome не угадывается.
7. Independent audit writer пишет только eight safe fields плюс generated auditId,
   owns own transaction и проверяет native ID до commit. Нет confidential lookup.
8. No-case terminal UoW принимает только coherent object_not_found request+audit,
   без fake case, lock или allocation; wrong echoes до mutation отклоняются.

Native schema readiness проверяется через approved registry/selection metadata,
без lazy repair. Недоступная/противоречивая family закрывает операции. Bool false
native API столь же ошибочен, как exception. Exact request unique collision —
единственный REQUEST_RACE; остальные constraint faults — persistence failure.
Commit/rollback acknowledgement и counters следуют sections9–10 core contract.
SQL разрешён только `app/AssignmentOrderComposition/MariaDb*.php`; narrow scanner
ownership extension проверяется independently, baseline не растёт.

## Native executable evidence

Actions вызывают public selectAssignmentOrderComposition. Synthetic setup через
existing SelectionSchemaTestDatabase/approved migrations; setup SQL не является
action. Postconditions используют approved schema verification snapshot и
registered composition public reader; read-only fixture observer может читать
полные строки для exact comparison, но не менять action outcome. Primary evidence
и manifests хранятся вне repo. Every native run выполняет bounded owned cleanup.

Первый RED/Gate3 tranche (не approval всей native матрицы):

- Empty fixture case4512, actor18, installer7001, engineer73, next registry81,
  fixed UTC2026-09-05T09:00:00Z: new_order rev0 selected81/version1/revision1;
  identity/hash — exact section4 core literals. Real request/event/audit по одному;
  registered reader возвращает composition81. Physical orders/originals/case
  opening/assignments/artifacts unchanged.
- Same request replay сохраняет все domain rows и returns exact stored payload.
- Replace_pending rev1 с installer7002 создаёт82/version2/revision2, previous и
  replaces81. Старые header/member/event/audit bytes сохраняются. Reader81 и82
  возвращают соответствующие составы, interval/opening facts не меняются.
- Object9999 missing: rejected/object_not_found; ровно request+audit, null success,
  без case9999/identity/event. Same request replay silent.

Следующие native tranches обязательны до Gate5 binding: authorization deny/revoke/
restore и conflict audit; eligibility/locked state; request decoding corruption;
no_changes/stale/pending/accepted roots; same/different-case concurrent requests;
known rollback/native interruption и unknown acknowledgement recovery;
registry/event/audit capacity; invalid session echoes и ambient transaction;
bool API failures, prefix25 и readiness. Core73 не переписываются и не считаются
native proof. Каждому tranche — demonstrated RED и independent Gate3 до GREEN.

## Done

Восемь real bindings, все inherited native obligations доказаны, relevant native
и core regression GREEN, architecture-check, exact clean SHA manifest и independent
Gate5 APPROVED. Parent OpenSpec tasks не закрываются по одному tracer. Full launch
требует последующих integration slices и полного make verify/VERIFY_OK.
