## Context

См. `proposal.md`. На актуальном main Slice A уже классифицирует protected semantic paths и консервативно добавляет все integration entries из canonical `tools/verification/suites.tsv`. Existing policy также имеет path-based `consumers`, но только одноуровневый match owner pattern → tests и не выражает capability identity или transitive ownership. Canonical inventory остаётся единственным реестром исполняемых verifier identities.

## Goals / Non-Goals

**Goals:**

- Добавить bounded capability/consumer ownership в существующий planner и policy.
- Валидировать terminal verifier identities через существующий inventory API.
- Доказать класс #20 и synthetic current-assignment класс #148 через public planner seam с BEFORE/AFTER output.

**Non-Goals:**

- Новый planner, giant global product dependency graph или inference из исходного кода.
- #153C/D, T07a+/#107, fixture reachability, product/runtime changes либо evidence store.
- Замена Slice A closure или полный local CI.

## Decisions

1. **Ownership расширяет существующую verification policy.** Добавляется один bounded section с capability name, protected owner patterns, verifier identities и downstream capability names. Это сохраняет policy ownership рядом с уже существующими boundaries/semantic surfaces. Альтернативы: второй manifest создаёт competing source; annotations во всех specs требуют глобального framework и неоднозначного lifecycle.

2. **Verifier identity — canonical inventory path.** Policy не хранит argv/category и не регистрирует checks повторно. Planner загружает `suites.tsv` через существующий inventory API, требует registration и существование файла, затем получает runtime/category от canonical entry. Альтернатива с raw commands дублирует inventory и допускает stale coverage.

3. **Protected capability определяется path ownership поверх Slice A surface.** Каждый changed path, который matched `semantic_surfaces`, обязан иметь ровно один capability owner. Неprotected paths могут не иметь capability metadata. Это даёт fail-closed completeness на заявленной protected области без глобального graph для всего repository.

4. **Traversal — deterministic graph closure.** Planner валидирует уникальные capability names/owners, существование downstream targets и terminal verifiers до построения plan; затем перечисляет unique simple causal chains с per-path visited nodes и canonical sorting. Direct verifier не является terminal условием. Cycle прекращает только текущую chain, не скрывая sibling path; unreachable declarations всё равно schema-validated. Consumer-only nodes могут иметь пустой patterns list, тогда как root owner changed protected path обязан иметь non-empty patterns.

5. **Evidence additive и execution deduplicated.** Новый `consumer_expansions` связывает changed path/capability, ordered chain и verifier. `selected_checks` остаётся canonical execution set: существующий `add()` дедуплицирует команду, а reason provenance не теряет indirect selection. Slice A `semantic_escalations` и integration closure остаются без изменения.

6. **Owning module и dependencies.** Owner — `tools/delivery/change-verification.py`; dependencies — stdlib, `.quality-graph/verification-policy.json`, существующий `tools/verification/inventory.py` и canonical `suites.tsv`. Persistence owner и rapid-pilot adapter не меняются. Architecture-check impact ограничен planner policy/regression и inventory validation; product architecture отсутствует.

## Risks / Trade-offs

- [Первоначальный graph покрывает только registered protected capabilities] → missing ownership для любого matched protected path fail closed; расширение происходит reviewable slices рядом с policy.
- [Slice A продолжает выбирать широкую integration category] → это явное preservation requirement; precision/сокращение category closure не входит в Slice B.
- [Один verifier достижим разными chains] → execution дедуплицируется, evidence canonical-sort; regression фиксирует deterministic result.
- [Переименование verifier оставит stale edge] → canonical inventory/file validation останавливает prepare до публикации.

## Migration Plan

Доставить policy schema, planner traversal, canonical regression/spec и delivery records атомарно. До implementation зафиксировать BEFORE на unmodified main: direct verifier может выбираться существующим path mapping/Slice A closure, но consumer-specific recovery/runtime chains отсутствуют. AFTER фиксирует механический frontier для synthetic #20/#148. Rollback удаляет additive metadata/traversal/output; product data migration отсутствует. Exact-source full matrix запускается один раз в GitHub CI, локально — только planner-selected focused checks.
