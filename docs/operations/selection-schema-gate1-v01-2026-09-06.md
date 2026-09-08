# Selection schema v0.1 — independent Gate1

Reviewer: `/root/selection_schema_gate1`, новый агент `gpt-5.6-sol`, low,
fork none. Author: root. Reviewed HEAD:
`c960c7382df0b3021184468183ed729b6c380c3a`.
Verdict: **CHANGES_REQUESTED**. Reviewer не изменял artifacts, tests, code или DB.

## Blocking finding

Section5 требует public registry `isBackfillComplete()===true`, но одновременно
назначает false/incomplete/incompatible результату conflict, а native query errors
— unavailable. Existing public method ловит каждый Throwable и возвращает false.
Через этот seam причины неразличимы; private registry proof дублировать нельзя.

Reviewer допускает либо separately approved non-lossy public registry result,
либо fixed conflict для любого false, включая скрытые этим bool API failures.
До устранения неоднозначности exact outcome RED не разрешён.

## Unaffected checks

Prefix maxima56/63/64/62/62; обе ветви new_order/replace_pending и independent
intent/composition/row hashes/counts согласованы. Metadata AST sensitivity,
read-only proof, leading-prefix recovery и disabled scope пригодны для RED после
исправления finding. Frontier13 не изменяется. Full selection approval не выдан.

## Exact hashes

OpenSpec paths ниже относительно
`openspec/changes/canonicalize-assignment-order-selection-schema/`.

```text
ead064e55a740795ba55d94f7d82f8cd50c107188a807e86c2476bac2ef3cf72  specs/ASSIGNMENT-ORDER-SELECTION-SCHEMA-001.md
bd25c93c80d30c8d2146c7e54aa970caf4bef0d389006a8d270f81ea9991c28c  specs/fixtures/assignment-order-selection-schema-v1.json
d7ba5056b7de298631152a181e898895f5a18bfb19de0479bd96878fc8eb209b  specs/fixtures/assignment-order-selection-example-v1.json
923cd8293934397a9cd7724e372943513c9f8efbbdf84653e9dc05d6e5fccc6f  proposal.md
1d916001980fc7e6431f3448cefd6d9d72a76f398d7fbc70180c90ee7c13e7d5  design.md
5aecd1f39d6d4bb0adf62d0567d2ced17185d41f2b92f92ef2b5a0311c5f6a73  tasks.md
d65c8f67500fc72d1eddd510317014374097fa936b6c7a3d6594987d71fbf77b  specs/pilot/assignment-order-selection-schema/spec.md
4c0b90e2a273a408ce637bdc7de8ba8c6d895604fb5258715d09703827363064  app/InstallationProcess/AssignmentOrderIdentityRegistryMigration.php
d29868b53e8240ad6296f7bba75a93952e2cb74ebb7ab5bfac515bd93ed43074  app/InstallationProcess/MariaDbAssignmentOrderIdentityRegistryHistory.php
```

## Technical disposition

Root выбирает второй вариант: public false доказывает только недоказанный
prerequisite и всегда даёт fixed conflict; ошибки собственных selection queries
остаются unavailable. Новый registry API не нужен. Поправка v0.2 требует своего
независимого verdict; этот historical CHANGES_REQUESTED сохраняется.
