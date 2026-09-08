# Selection schema v0.2 — independent Gate1

Reviewer: `/root/selection_schema_gate1`, `gpt-5.6-sol`, low, fork none.
Author: root. Base HEAD `c960c7382df0b3021184468183ed729b6c380c3a`,
reviewed worktree bytes pinned below. Verdict: **APPROVED**.
Reviewer не изменял artifacts, tests, code или DB.

## Verdict and scope

Единственный blocker v0.1 устранён: public registry completion false всегда
означает недоказанный prerequisite и SCHEMA_MIGRATION_CONFLICT, включая скрытые
bool API native failures. Непосредственно наблюдаемые selection SQL failures
дают DatabaseUnavailable. Причина hidden false не выводится, private registry
proof не дублируется, новый registry API не вводится. Три OpenSpec artifacts
согласованы с этим exact outcome. Публичная проверка конструктивна и fail-closed.

Без повторного review переиспользованы unaffected v0.1 checks: metadata/AST
fingerprints, independent fixtures/row/intent/composition hashes и counts,
prefix maxima56/63/64/62/62, new_order/replace_pending, read-only proof,
recovery/locks и disabled boundary. Blocking findings отсутствуют.

Это разрешение RED только standalone schema engine. Full selection contract,
canonical version/wiring, registry enablement и release readiness не одобрены.
Frontier остаётся13. Historical v0.1 CHANGES_REQUESTED сохранён отдельно.

## Exact approved hashes

OpenSpec paths ниже относительно
`openspec/changes/canonicalize-assignment-order-selection-schema/`.

```text
63759ba4e889c0a3940ea15f60e4f747f48ce26c39165fe9dc67984682fe5bb1  specs/ASSIGNMENT-ORDER-SELECTION-SCHEMA-001.md
bd25c93c80d30c8d2146c7e54aa970caf4bef0d389006a8d270f81ea9991c28c  specs/fixtures/assignment-order-selection-schema-v1.json
d7ba5056b7de298631152a181e898895f5a18bfb19de0479bd96878fc8eb209b  specs/fixtures/assignment-order-selection-example-v1.json
aac1aaec08adfdcf143419a8fc20f9590483264403a424598a3e22ba711c45ca  proposal.md
b5a9985b083f63aee44829aa28838f52837a4299c2231c198fd0446d4b1e42d8  design.md
5aecd1f39d6d4bb0adf62d0567d2ced17185d41f2b92f92ef2b5a0311c5f6a73  tasks.md before completion bookkeeping
a67df5de1578cc840ed6c8a1e5c71fac349caeaf5b23e93762a161bf43636168  specs/pilot/assignment-order-selection-schema/spec.md
```

## Verification and cost

Root пересчитал пять row hashes/counts, три intent fingerprints, два composition
hashes и identifier bounds независимо от production; PASS. Неизменённые public
PHP declarations lint и manifest fingerprints используют архив
`selection-schema-draft-checkpoint-j65_4d4w` из handoff1433Z. После поправки
`openspec validate canonicalize-assignment-order-selection-schema --strict` и
`git diff --check` PASS. Это не SQL RED и не implementation evidence.

Первоначальный лимит20мин/80k оказался занижен: уже при первом boundary counter
135194 tokens включал обязательное восстановление контекста. Root пересмотрел
пакет до Gate1 closure без RED, сохранил один reviewer и ограничил повторное review
единственным corrective diff. Финальный расход фиксируется после commit отдельно;
135194 не выдаётся за final cost. Persistent goal без token budget.
