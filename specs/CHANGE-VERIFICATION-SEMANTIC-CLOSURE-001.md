# CHANGE-VERIFICATION-SEMANTIC-CLOSURE-001 — #153 Slice A

## Scope and oracle

Actor — автор change, запускающий существующий public `harness.py prepare` / `change-verification.py build`. Oracle — owner decision для issue #153 Slice A, подтверждённый failure class PR #148, актуальные repository boundaries и canonical verification inventory `tools/verification/suites.tsv` из #135. Target seam — существующий change-verification planner; новый planner, registry или Gate не создаётся.

Release value: до PR protected semantic change не может получить publication-ready plan только на основании GREEN direct/focused checks без integration closure. Issue #153 после Slice A остаётся открытой; capability→consumer graph (B), Gate 3 completeness audit (C) и CI feedback expansion (D) не входят.

## Contract

1. Planner MUST детерминированно сопоставлять changed repository paths с минимальным repository-owned closed set protected semantic surfaces: `current-state-authority`, `persistence-semantics`, `schema-migration-frontier`, `domain-application-contract`, `recovery-representation`.
2. Classification MUST использовать существующие boundaries/spec/verification metadata и при необходимости metadata в текущей verification policy. Она MUST NOT использовать runtime LLM judgement, PHP extension как самостоятельный signal, capability graph или issue-specific #148 names/paths.
3. Любой matched protected surface MUST требовать category-level `integration` closure. Closure MUST состоять из canonical registered integration verifier set, прочитанного через inventory #135 из `suites.tsv`; второй manifest/category registry запрещён.
4. Если canonical inventory не содержит ни одного применимого registered integration verifier, build/prepare MUST fail closed до plan emission с `SEMANTIC_INTEGRATION_CLOSURE_UNAVAILABLE: <surface>: integration`.
5. Plan JSON MUST содержать для каждого semantic escalation: stable surface name, sorted changed paths, stable reason, required category `integration` и sorted commands/checks, добавленные из canonical inventory.
6. Checked/tampered plan без required semantic integration closure MUST быть rejected; direct GREEN не заменяет closure и не делает candidate publication-ready.
7. Healthy bounded FAST, presentation-only PHP/view и docs/OpenSpec lifecycle-only changes MUST NOT получать semantic escalation. Slice A MUST NOT расширять FAST и MUST NOT делать integration обязательной для всех STANDARD/CRITICAL changes.

## Executable matrix

| Case | Change | Required result through public planner/prepare seam |
|---|---|---|
| A | Authoritative current-state owner | Integration closure required |
| B | Persistence semantics | Integration closure required |
| C | Schema/migration/frontier | Integration closure required |
| D | Recovery representation | Integration closure required |
| E | Protected surface, no registered integration verifier | Fail closed with exact diagnostic |
| F | Direct tests GREEN, closure absent/tampered | Plan rejected; not publication-ready |
| G | Same semantic change with canonical closure | Plan valid |
| H | Bounded server-rendered presentation-only PHP/view | No semantic integration escalation |
| I | Docs-only lifecycle metadata | No semantic integration escalation |
| J | Existing healthy FAST fixture | Lane/reviews/selected-check semantics unchanged |
| K | UI plus protected persistence semantic change | Integration closure required |
| L | Protected change | Machine-readable surface/reason/category/checks present |

Synthetic fixture MUST model the PR #148 class: direct/unit check exists and is GREEN, protected current-state/persistence owner changes, a downstream integration consumer exists and is registered, while the pre-Slice-A planner selects only the direct focused set and omits that consumer. RED is this incomplete plan; GREEN is automatic category closure including the registered integration consumer.

## Done and stop

- OpenSpec strict validation, executable RED, independent Gate 3, minimal implementation, focused GREEN, independent Gate 5 and one exact-source GitHub CI matrix are recorded.
- No local `make test` / `make verify`, product code, CI performance work, merge, deployment or settings change.
- Final record says exactly: `#153 Slice A delivered`; `#153 remains open`; B/C/D untouched. Then work stops.
