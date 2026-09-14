# YII2-STAND-RESTORE-UNKNOWN-RECONCILE-001 — reconciliation UNKNOWN restore

## Простыми словами

Владелец отдельным разрешением признаёт конкретное неизвестно завершившееся
восстановление непригодным для продолжения. История UNKNOWN остаётся неизменной;
система добавляет durable факт `ROLLBACK_ONLY` и только затем переводит exact
lease в состояние, допускающее отдельно авторизованный rollback.

## 1. Public intent и authority

Actor — owner-authorized deployment operator. Source — issue #76, operation
`e82320ce-a1de-4728-971a-f472167717b8`, bundle
`ed8d7613673b1bff987daa03547604f999aeab596baa517159b5fa27c0305108`,
base `9d0ca509a99a39cc765c8fad5afd0ef1b2087721`.

Public seam: `php bin/yii stand-restore/reconcile-unknown` с exact manifest,
prior operation UUID, reconciliation UUID, authorization и `--interactive=0`.
Controller только адаптирует argv; existing RuntimeRestore application owner
владеет transition. Path-only/manual/generic unlock запрещён.

## 2. Admission

До effects MUST быть доказаны: exact canonical prior ledger record существует и
имеет только `OUTCOME_UNKNOWN`; его bytes сохранены; `restored.json` отсутствует;
lease exact связывает prior operation/target/bundle; rollback bundle независимо
verified; authorization unexpired и связывает reconciliation/prior UUID,
authorization/ledger/lease/target/bundle/rollback-bundle digests, source/image,
runtime tuple и observed disposable identities; overlap с production/neighbor
отсутствует. Missing/non-UNKNOWN/malformed/mismatch/pointer/drift отклоняются без
изменений.

## 3. Append-only result

Success: `UNKNOWN_RECONCILED_FOR_ROLLBACK`. Existing restore ledger MUST остаться
byte-identical; `restored.json` MUST отсутствовать. Новый canonical
`restore-reconciliations.jsonl` fact фиксирует prior remains UNKNOWN, success
unconfirmed, forward completion abandoned, state `ROLLBACK_ONLY`, next intent
exact rollback и все bound digests/ids.

## 4. Durability, replay и interruption

Порядок MUST быть: append fact → fsync fact → fsync evidence directory → exact
lease transition → fsync directory → rollback-ready publication при необходимости.
До durable fact interruption сохраняет original lease. После durable fact replay
repair допускается только для matching fact/lease. Same authorization replay
возвращает тот же result без второго fact; conflicting operation/authorization/
target/bundle даёт `OPERATION_CONFLICT`. Никакого `RESTORE_VERIFIED`.

## 5. Rollback boundary

Reconciliation authorization разрешает только append-only recovery и exact lease
transition. Она не разрешает restore/restart/rollback. После separately authorized
successful reconciliation готовится новый rollback package на fresh source/current
identities; rollback автоматически не запускается.
