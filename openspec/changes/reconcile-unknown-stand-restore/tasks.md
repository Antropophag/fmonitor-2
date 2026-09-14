## 1. Root contract и intended RED

- [x] 1.1 Root создать `YII2-STAND-RESTORE-UNKNOWN-RECONCILE-001` normative spec и exact verification input, связанные с issue #76, failed operation `e82320ce-a1de-4728-971a-f472167717b8`, bundle `ed8d7613…` и base `9d0ca509a99a39cc765c8fad5afd0ef1b2087721`; validate strict GREEN.
- [x] 1.2 Root добавить public Yii/application RED valid UNKNOWN reconciliation: original ledger byte-identical, canonical recovery fact, no restored pointer, rollback-only state; сохранить harness evidence вне checkout.
- [x] 1.3 Root добавить rejection RED для missing/non-UNKNOWN operation, missing/mismatched/malformed lease, target/bundle/rollback-bundle conflict, existing restored pointer и identity overlap/drift; zero effects доказаны snapshots.
- [x] 1.4 Root добавить replay/interruption/durability RED: exact replay byte-stable, conflicting authorization fail-closed, interruption matrix и append/fsync-before-lease-transition ordering.
- [x] 1.5 Root вычислить Quality Graph plan и подготовить exact Gate 3 package; unresolved/missing acceptance blocks implementation.

## 2. Independent review и minimal implementation

- [x] 2.1 Независимый gpt-5.6-sol/low reviewer вынести Gate 3 `APPROVED` либо `CHANGES_REQUESTED` по spec/tests/RED/plan.
- [x] 2.2 Отдельный executor добавить exact reconciliation authorization value и public `stand-restore/reconcile-unknown` adapter под существующим RuntimeRestore owner; malformed argv/authorization fail before evidence calls.
- [x] 2.3 Executor реализовать immutable ledger/lease/pointer/bundle/identity admission и canonical append-only reconciliation ledger без изменения restore ledger.
- [x] 2.4 Executor реализовать durable fsync-ordered rollback-only lease transition/pointer и exact replay repair; interruption/conflict tests GREEN.
- [x] 2.5 Executor зарегистрировать tests в verification inventory и выполнить только generated focused plan; existing backup/restore regressions GREEN.

## 3. Gate 5 и authorization handoff

- [ ] 3.1 Root подготовить complete-candidate exact-source Gate 5 package; отдельный independent reviewer проверить no-success, history immutability, authorization, attestation, crash ordering и absence generic unlock.
- [ ] 3.2 После Gate 5 выполнить только read-only preflight current operation/ledger/lease/no-pointer/target identities/verified rollback bundle и создать canonical reconciliation authorization package с exact SHA-256.
- [ ] 3.3 Остановиться до отдельной owner authorization; retained lease и UNKNOWN record остаются неизменными.
- [ ] 3.4 После отдельно авторизованной successful reconciliation сформировать новый rollback package на fresh exact source/current identities и остановиться; rollback не запускать.

## 4. Done definition

- [ ] 4.1 Slice complete только после Gates 3/5 APPROVED, focused GREEN, authorized reconciliation evidence и exact pending rollback package; production/neighbor untouched, rollback/deployment не выполнены.
