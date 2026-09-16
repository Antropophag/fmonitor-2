## Why

Slice A issue #153 консервативно выбирает всю integration category для protected semantic surface, но не умеет доказуемо связать изменённый capability/invariant с его прямыми и транзитивными verification consumers. Forensic #20 и последовательные исправления #52/#148 показали, что direct migration verifier может присутствовать, пока recovery/current-schema и indirect runtime/inventory consumers остаются вне pre-PR frontier.

## What Changes

- Расширить существующую repository-owned verification policy минимальной capability/ownership metadata, ссылающейся только на verifier identities из canonical `tools/verification/suites.tsv`.
- Научить существующий planner детерминированно проходить direct и transitive consumer chains от изменённой protected semantic surface и добавлять каждый зарегистрированный verifier.
- Для каждого расширенного verifier выдавать machine-readable причинную цепочку `changed capability -> consumer chain -> verifier`.
- Fail closed при missing/stale/ambiguous ownership protected surface и при удалённом либо незарегистрированном verifier.
- Сохранить Slice A conservative integration closure и отсутствие semantic expansion для local presentation-only изменений.
- Не реализовывать #153C/D, T07a+/#107, fixture reachability, product changes, evidence store или issue-specific policy.

## Capabilities

### New Capabilities

- `verification/consumer-ownership-frontier`: deterministic direct/transitive verification frontier для repository-owned protected capabilities и invariants.

### Modified Capabilities

Нет. Сохранение Slice A category-level closure является compatibility requirement нового capability, поскольку Slice A ещё не архивирован в main specs.

## Impact

Затрагиваются `.quality-graph/verification-policy.json`, существующий `tools/delivery/change-verification.py`, canonical planner regression и delivery artifacts. Canonical verifier registration остаётся в `tools/verification/suites.tsv`; новый planner, глобальный dependency framework, product/runtime code и full local CI не появляются.
