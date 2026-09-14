## Purpose

Capability безопасно и append-only переводит один exact retained UNKNOWN restore lease в состояние, где допустим только отдельно авторизованный rollback, не меняя исторический UNKNOWN outcome.

## ADDED Requirements

### Requirement: Reconciliation выражает exact recovery intent
Система SHALL предоставлять explicit non-generic intent `reconcile unknown restore`, адресующий exact prior restore operation UUID. Intent MUST принадлежать существующему restore application owner и MUST NOT принимать path-only unlock, wildcard, project-name-only либо manual lease deletion inputs.

#### Scenario: Exact UNKNOWN operation выбран
- **WHEN** operator передаёт exact failed operation UUID, target manifest и separate reconciliation authorization
- **THEN** application рассматривает только matching UNKNOWN ledger/lease и не выполняет backup, restore, rollback, restart или payload mutation

### Requirement: Admission доказывает UNKNOWN и exact retained lease
До первого reconciliation effect application SHALL независимо проверить: один canonical restore ledger record exact operation имеет outcome `OUTCOME_UNKNOWN`; исходный record byte-identical; `restored.json` отсутствует; canonical retained lease связывает тот же operation/target/bundle; rollback bundle independently verified; authorization unexpired и exact связывает operation, target, bundle, lease digest, rollback bundle digest, pinned source/image/runtime tuple и observed disposable identities; production/neighbor overlap отсутствует.

#### Scenario: Полный preflight проходит
- **WHEN** ledger, pointer absence, lease, verified bundle, authorization и observed identities exact совпадают
- **THEN** application допускает append-only reconciliation record, не объявляя прежний restore успешным

#### Scenario: Non-UNKNOWN operation отклонён
- **WHEN** operation отсутствует либо имеет `RESTORE_VERIFIED`, `RESTORE_FAILED` или unknown/unrecognized outcome
- **THEN** reconciliation fail-closed завершается без record/lease/target changes

#### Scenario: Lease отсутствует или не совпадает
- **WHEN** lease missing, malformed, symlinked либо его operation/target/bundle/digest отличается
- **THEN** reconciliation возвращает non-success и сохраняет evidence byte-identical

#### Scenario: Confirmed pointer уже существует
- **WHEN** `restored.json` существует независимо от его содержимого
- **THEN** reconciliation запрещён и не освобождает lease

#### Scenario: Identity или rollback bundle mismatch
- **WHEN** target identity drift, production/neighbor overlap, target/bundle conflict либо rollback bundle verify failure обнаружены
- **THEN** application fail-closed останавливается до append/release effects

### Requirement: Recovery fact append-only сохраняет прошлый UNKNOWN
Successful reconciliation SHALL добавить один canonical append-only fact, связывающий reconciliation id/authorization digest, prior operation/target/bundle/lease digests, timestamp и terminal state `ROLLBACK_ONLY`. Fact SHALL явно фиксировать: previous restore remains UNKNOWN; success unconfirmed; forward completion abandoned; lease released/transferred only for rollback; next permitted state-changing action is exact rollback. Existing restore ledger bytes MUST NOT изменяться, удаляться или дополняться задним числом; `restored.json` MUST NOT публиковаться.

#### Scenario: UNKNOWN abandoned for forward completion
- **WHEN** admitted reconciliation успешно записывает recovery fact
- **THEN** прежний UNKNOWN record остаётся byte-identical, новый fact append-only содержит `ROLLBACK_ONLY`, confirmed pointer отсутствует

### Requirement: Durable ordering предшествует lease release
Application SHALL durable append/fsync reconciliation fact и parent directory до lease release/transfer. Затем SHALL durable удалить exact matching retained lease либо atomically заменить его exact rollback-only lease и fsync directory. Если outcome release после durable fact нельзя доказать, replay SHALL repair только matching lease state; conflicting state SHALL оставаться fail-closed. До durable fact interruption MUST сохранять original lease.

#### Scenario: Interruption до durable fact
- **WHEN** process прерывается до fsync reconciliation record
- **THEN** original UNKNOWN ledger и retained lease остаются, rollback не допускается

#### Scenario: Interruption после durable fact до release confirmation
- **WHEN** recovery fact durable, но lease release/transfer outcome ambiguous
- **THEN** result не утверждает готовность rollback; replay exact authorization сверяет fact и repair-ит только exact matching lease

#### Scenario: Correct durable order
- **WHEN** reconciliation подтверждена
- **THEN** observed order: append fact → fsync fact → fsync evidence directory → exact lease release/transfer → fsync evidence directory → publish rollback-ready pointer/fact при необходимости

### Requirement: Reconciliation replay и conflict deterministic
Same reconciliation operation plus byte-identical authorization/target/bundle/lease SHALL возвращать тот же terminal result без второго fact или unrelated effects. Same reconciliation id либо prior operation с conflicting authorization, target, bundle или rollback bundle SHALL возвращать conflict и не менять evidence.

#### Scenario: Exact replay
- **WHEN** confirmed reconciliation повторена с теми же digest-bound arguments
- **THEN** возвращается исходный `UNKNOWN_RECONCILED_FOR_ROLLBACK` result, recovery ledger и lease state byte-identical

#### Scenario: Conflicting replay
- **WHEN** повтор меняет любой bound digest/identity
- **THEN** возвращается `OPERATION_CONFLICT` до effects

### Requirement: Authorization и последующий rollback разделены
Reconciliation SHALL требовать отдельный owner authorization package и отдельный reconciliation UUID. Эта authorization разрешает только recovery fact и exact lease transition; она MUST NOT разрешать rollback/restore/restart. После successful reconciliation система SHALL подготовить новый rollback package на current exact source, verified known-good bundle и current identities, но MUST NOT выполнять rollback без следующей owner authorization.

#### Scenario: Reconciliation authorized, rollback не authorized
- **WHEN** owner разрешает exact reconciliation package
- **THEN** допускаются только append-only recovery/lease effects, а rollback остаётся отдельным pending package

#### Scenario: Package подготовлен без execution
- **WHEN** Gate 5 завершён до owner reconciliation authorization
- **THEN** repository/external evidence содержит exact reconciliation package digest, но retained lease и UNKNOWN history не изменены
