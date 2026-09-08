## Why

Будущий application writer должен ссылаться на принятый original, не читать
чужие persistence internals и не переносить original ownership в HTTP/Composition.
Нужны immutable metadata reference и проверка её актуальности под общим case lock.

## What Changes

- ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001: public read-only factory,
  snapshot lookup выбранного original и borrowed-transaction current guard.
- Reuse original StoredReader и registered composition proof; только selected source.
- Не выдавать private storage identity/path и не читать PDF bytes для metadata reference.

## Capabilities

### New Capabilities
- `pilot/original-application-reference`: проверенная ссылка для application consumer.

### Modified Capabilities

## Impact

Только AssignmentOrderOriginal и native tests. Source oracle — approved selected
original binding, original data integrity и caller-owned transaction contract.
Нет новых migrations, state-changing seams, grants, HTTP routes, file/audit writes,
применения состава или opening. Runtime consumers обязаны сами проверить actor.
Решение о correction после application не влияет на этот prerequisite.
