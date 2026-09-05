# ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.8 — review counter outcomes

Дата: 2026-09-05.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed commit: `6280b8a36b2a0c57d127ecdc07240241f30ca316`.  
Bounded verdict: **APPROVED для v0.8 generated-counter delta**.  
Full Gate 1: **NOT READY FOR RED** из-за ранее известных P0 dependencies.

Это независимый ограниченный rereview единственного finding v0.7: различение
proven generated-counter capacity exhaustion, malformed/protocol receipt и
unknown acknowledgement. Review сохраняет bounded approval typed flow v0.6 и
не оценивает одновременно создаваемую registry test matrix. Reviewer не автор
reviewed artifacts. Code/tests/spec/OpenSpec не изменялись.

## Exact reviewed hashes

- `specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md`:
  `5cb8a371a43356849b374356212689562a8ccba7db402bb05c46715b1df704b2`.
- OpenSpec design:
  `07801a49cacb60c266419640b28349f6318660697087d255ee843f2700877c72`.
- OpenSpec proposal:
  `8c0bb8729e464f67acc3f847a2bd726d82ddb0c56ebd9e5b3364d85b09a0c4f7`.
- OpenSpec delta spec:
  `4253e9bfb96ae2e5ab04a4b10547d6fbb0ce3a58758239114592a81e6ded3f27`.
- OpenSpec tasks:
  `8dc8124062affdcf3eedf1a55fd6f49da86ee0cf69b3e9939e6bde1b03740e21`.
- v0.7 generated-ID review:
  `3eebc54427c2ade425b1df1db3c5bd0d504bf62971ed5f6efe564982cb821db7`.
- v0.6 typed-flow review:
  `65d5d697e9d0e1936d73822cbbf779995742454cfbfd05566ac85ed2f1dc5693`.

## Finding disposition

### v0.7 P1: capacity и persistence failure для одного boundary — RESOLVED

`SelectionIdentityAllocationResult` теперь является closed result с variants
`allocated`, `capacityExhausted` и `persistenceError`. Overflowed PHP int нельзя
передать как allocation payload. Registry allocator сравнивает canonical
unsigned decimal server value с PHP_INT_MAX по длине и лексикографически до int
conversion; float и overflowed cast запрещены.

External mapping теперь однозначен:

- доказанный positive registry/event/audit counter больше PHP_INT_MAX после
  confirmed rollback → `failed/allocation_capacity_exhausted`, retryable false;
- malformed lexical value, zero/negative, wrong case/source/version, protocol
  mismatch или ambiguous query failure после confirmed rollback →
  `failed/persistence_failure`;
- commit или rollback acknowledgement нельзя подтвердить →
  `failed/persistence_outcome_unknown` и no mutation retry.

Stage и independent audit results имеют отдельный `capacityExhausted` branch.
Registry identity allocation, selected event и independent denial/conflict audit
охвачены раздельно. Это закрывает прежнюю неопределённость независимых event и
audit AUTO_INCREMENT counters.

## Boundary preservation checks

- Registry next ID `9223372036854775808` определяется как capacity до выдачи
  allocation payload; out-of-range identity не признаётся существующей.
- Event ID `9223372036854775808`, полученный как lossless native decimal, приводит
  к rollback всех staged selected facts; допустим только AUTO_INCREMENT gap.
- Independent audit counter с тем же доказанным overflow возвращает capacity
  только после confirmed rollback; первоначальный denial/conflict не выдаётся
  как будто audit состоялся.
- Last valid ID `9223372036854775807` разрешён; следующий invocation получает
  capacity result. ID не переиспользуется и не перенумеровывается.
- Native `not-a-number` не угадывается как capacity и остаётся persistence
  failure после confirmed rollback.
- Shape-invalid входные IDs остаются `invalid_command` до allocator/storage
  checks и не смешиваются с исчерпанием внутренних counters.
- Read integrity по-прежнему отвергает persisted out-of-range ID как unavailable,
  без PHP int cast до lossless range proof.

## Typed-flow consistency

Новые capacity branches используют существующий
`SelectionRollbackCause::ALLOCATION_CAPACITY_EXHAUSTED`; transaction owner и
stage/decision/UoW boundaries v0.6 не меняются. Stage adapter не выполняет
commit/rollback. Request race и observed-terminal ветви не переопределены.
Unknown acknowledgement сохраняет recovery без второго allocation или blind
write retry. В bounded delta нового противоречия не найдено.

## Bounded verdict и Gate summary

v0.8 полностью закрывает finding v0.7 и получает **APPROVED** в пределах
generated-counter correction. Это не full Gate 1 approval и не разрешение RED.

Full Gate 1 остаётся `NOT READY FOR RED` до exact migration/backfill/receipt and
prefix contract, all-writer/N-1 cutover, original-reader amendment и
same-identity optional-render contract с независимыми approvals exact hashes.
Эта запись не заменяет отдельный review registry engine/test matrix.
