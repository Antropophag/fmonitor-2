## Why

ObjectQueue и completion HTTP paths сейчас вызывают request-time DDL для
`fm2_pilot_completion_facts`, поэтому DML-only TEST-USER runtime получает 503 и
блокирует полную проверку canonical planning v9. Owner decision об append-only
correction, обязательной декларации и целевой модели 85/15 теперь позволяет
спроектировать durable canonical family без замораживания прежней overwrite
семантики.

## What Changes

- Добавить последовательную canonical migration, единолично владеющую
  installation-completion fact family после planning v9.
- Представить ПТО и декларацию append-only facts; исправление ошибочной даты
  является новым correction fact с обязательной bounded reason и ссылкой на
  предыдущую версию, исходный факт и его `details` сохраняются навсегда.
- Сохранить обязательность декларации для terminal completion и целевое правило
  progress: checklist 85%, документы 15%.
- Мигрировать совместимую populated pilot table без потери исходных rows;
  incompatible/ambiguous state отклонять до schema mutation.
- Удалить completion-family runtime DDL из ObjectQueue/card/checklist/command
  paths и заменить его read-only fail-closed readiness.
- Не переносить новую domain logic в `rapid-pilot`; behavior-команды и exact
  authorization остаются отдельными slices и public application seams.

## Capabilities

### New Capabilities

- `deployment/canonical-installation-completion-schema`: canonical ownership,
  additive upgrade, append-only correction storage, preservation и
  runtime-no-DDL contract для installation-completion facts.

### Modified Capabilities

Нет.

## Impact

- Canonical migration runner получает следующую literal version после landed
  planning v9; exact number и full-catalogue prefix ceiling фиксируются в Gate 1
  evidence, а не предполагаются этим proposal.
- Затронуты `rapid-pilot/CompletionFlow.php`, ObjectQueue и bootstrap/runtime
  consumers только в части schema readiness; существующие command semantics не
  получают молчаливого расширения.
- Source oracle: current completion table/characterizations и owner decision
  `docs/operations/installation-completion-owner-decision.md`.
- Actor schema slice: deployment operator; target public seam: canonical runner
  плюс read-only runtime readiness. Release value: DML-only queue verification и
  снятие prerequisite с planning v9 integration.
- Non-goals: исправление `details`/произвольного payload, authorization
  implementation, UI correction flow, изменение
  progress weights, premium/payment effects, destructive cleanup и новая
  rapid-pilot domain logic.
