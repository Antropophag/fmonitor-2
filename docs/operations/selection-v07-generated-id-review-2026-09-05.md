# ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.7 — review generated-ID correction

Дата: 2026-09-05.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed HEAD: `7c9303dee000920ec48ec51de932013825fadd94`.  
Bounded verdict: **CHANGES_REQUESTED**.  
Full Gate 1: **NOT READY FOR RED** по ранее известным P0 dependencies.

Это независимый ограниченный review только удаления неподдерживаемых MariaDB
CHECK expressions с AUTO_INCREMENT IDs и сохранения logical ID bounds. Review не
дублирует отдельный full Gate 1 review registry engine, не переоткрывает
утверждённый typed flow v0.6 и не меняет code/tests/spec/OpenSpec.

## Exact reviewed hashes

- `specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md`:
  `0e0af5c3439495087a4654e54ead6a735bfd113506dbcfda607c922750b72a31`.
- Correction evidence
  `docs/operations/selection-auto-increment-check-correction-2026-09-05.md`:
  `55b0230f4dc518ac501ca332de75f577c0736bd6ee38972270c4f29510371b16`.
- OpenSpec design:
  `e1cb6fb39f31063964817614307d793deb63dc1f4a42a6851f086149f520cc34`.
- OpenSpec proposal:
  `7b8df50cd6f0552fdf59d3c0e8581836c9801d5a18b028ba13d7f66489263d97`.
- OpenSpec delta spec:
  `dd0576630bc7b0c26593d74ff1109910519f7866c74407fee21d782b62fe6eda`.
- OpenSpec tasks:
  `8a934c6e05649a1fa55be0f4e56a9f96005f094ba0a4039e7d842183257e5311`.
- Approved bounded typed-flow review v0.6:
  `65d5d697e9d0e1936d73822cbbf779995742454cfbfd05566ac85ed2f1dc5693`.

## Correctness and preservation

Удаление CHECK с `assignment_order_id`, `event_id` и `audit_id` необходимо:
reviewed official MariaDB constraint reference и local MariaDB 11.4.7 probe
показывают errno 1901 для AUTO_INCREMENT column в CHECK и успешное создание той
же таблицы без CHECK. Изменение не ослабляет physical FK compatibility: все три
колонки остаются `BIGINT UNSIGNED AUTO_INCREMENT`, а referencing identity types
не меняются.

Logical invariant `1..PHP_INT_MAX` может корректно обеспечиваться вне DDL при
условии, что lossless decimal value проверяется до acknowledgement/commit и при
каждом integrity read. Существующие v0.6 receipts и UoW ownership позволяют
откатить invalid staged event/audit/identity и не выдать success. Invalid
persisted value должен давать unavailable, а не быть приведён к PHP int.

Таким образом physical constructibility correction верна. Bounded verdict
остаётся CHANGES_REQUESTED из-за несогласованной external reason mapping ниже,
а не из-за удаления CHECK.

## Finding

### P1 — v0.7 смешивает capacity exhaustion и persistence failure для одного доказанного boundary

Новая строка section 9.1 и v0.7 disposition задают:

- `invalid generated ID` → confirmed rollback →
  `failed/persistence_failure` (`spec` строки 629 и 852);
- OpenSpec scenario также требует persistence failure для generated ID вне
  `1..PHP_INT_MAX`.

Но неизменённые section 10 и acceptance matrix задают:

- global ID bound доказывается lossless decimal arithmetic до writes;
- proven overflow → nonretryable `allocation_capacity_exhausted` (строки
  695-696);
- **any capacity boundary exceeded** →
  `failed/allocation_capacity_exhausted`, retryable false (строка 792).

Для registry `assignment_order_id` это один и тот же boundary. Если следующий
AUTO_INCREMENT/frontier уже больше PHP_INT_MAX, это доказанное исчерпание общей
identity capacity, а не неопределённая persistence fault. Нельзя одновременно
требовать два external outcomes для одной наблюдаемой границы.

Event/audit counters требуют отдельной явной классификации. Они могут достичь
PHP_INT_MAX независимо от registry frontier. Текущий текст не отвечает, является
ли доказанная event/audit counter boundary тем же `allocation_capacity_exhausted`
из acceptance row или `persistence_failure` из stage row. Для independent denial
audit это также определяет, сохраняется ли первоначальный denial outcome либо
возвращается технический failure.

Required disposition:

1. Согласовать selection spec с registry-engine counter contract: проверяемый до
   write registry frontier/order-ID overflow должен иметь один exact outcome,
   согласованный с `SelectionRollbackCause` и nonretryable capacity rule.
2. Отдельно закрепить exact mapping для event и audit AUTO_INCREMENT counters,
   включая pre-insert known boundary, unexpected out-of-range generated receipt,
   confirmed rollback и unknown acknowledgement.
3. Сузить `invalid generated ID → persistence_failure`, если это intended, до
   malformed/unexpected receipt после успешно пройденной capacity precheck; тогда
   явно сохранить `proven capacity boundary → allocation_capacity_exhausted`.
4. Уточнить, что lossless decimal counter/frontier validation не выполняет PHP
   int cast до range proof и координируется одним registry allocator owner; новый
   allocator не вводится.

Это technical coordination с registry-engine contract, а не новый product
decision.

## Gate summary

Typed flow v0.6 остаётся принятым в пределах своего review: transaction owner,
rollback causes и receipt validation не нарушены. v0.7 правильно устраняет
невозможный MariaDB DDL, но reason mapping для исчерпания generated-ID counters
нужно сделать однозначным до технического approval этой correction.

Full Gate 1 независимо остаётся `NOT READY FOR RED` из-за известных exact
migration/backfill/receipt and prefix contract, all-writer/N-1 cutover,
original-reader amendment и same-identity optional-render dependencies. Эта
запись не оценивает отдельный registry-engine candidate и не заменяет его review.
