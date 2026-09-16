# CONSUMER-OWNERSHIP-FRONTIER-153-B — #153 Slice B

## Простыми словами

Когда меняется защищённая схема, миграционный frontier или authoritative current state, planner обязан механически найти не только ближайший verifier, но и recovery/runtime consumers за ним. Причина выбора каждого check видна машине; неизвестное ownership останавливает prepare. Локальные presentation changes и широкая Slice A safety closure сохраняют прежнее поведение.

## Scope and public seam

Actor — автор change, запускающий существующий `harness.py prepare` / `change-verification.py plan/check`. Oracle — owner scope issue #153 Slice B, forensic класса #20 и системный failure class #52/#148, существующие semantic surfaces/policy и canonical `tools/verification/suites.tsv`. Public seam остаётся существующим planner; новый planner, evidence store или global product dependency graph запрещены.

## Normative contract

1. Repository policy MUST объявлять bounded capabilities с unique name, owner patterns, terminal verifier identities и downstream capability identities. Capability, который может быть root изменённого protected path, MUST иметь non-empty unique owner patterns; consumer-only capability MAY иметь пустой patterns list. Terminal identity MUST разрешаться только через canonical inventory entry; raw argv и issue-specific names MUST NOT быть ownership policy.
2. Каждый changed path, matched существующим protected `semantic_surfaces`, MUST принадлежать ровно одному capability. Ноль либо более одного owner MUST fail closed до plan emission. Непротектированный path не обязан иметь capability owner.
3. Planner MUST детерминированно обойти весь reachable graph. Наличие direct verifier MUST NOT останавливать traversal; cycles и multiple paths MUST завершаться bounded visited traversal. Для terminal verifier, достижимого несколькими путями, plan MUST содержать по одному evidence item на каждую unique simple causal chain в canonical sort order, но выполнить verifier ровно один раз. Declaration order MUST NOT менять canonical result.
4. Каждый reachable terminal verifier MUST быть зарегистрирован в canonical inventory и существовать в candidate source. Missing/stale capability target, removed file или unregistered verifier MUST fail closed и MUST NOT считаться coverage.
5. Plan MUST содержать `consumer_expansions`: для каждого expanded verifier — changed path, root capability, ordered capability chain и terminal verifier identity/argv. Execution command MUST быть дедуплицирован, даже если verifier уже выбран direct boundary/acceptance/Slice A closure; causal evidence при этом MUST сохраняться.
6. Slice A `semantic_escalations`, integration category и полный canonical integration closure MUST сохраняться как additive conservative closure. Slice B не сокращает Slice A и не меняет lane/reviews.
7. Bounded presentation-only change без protected semantic surface MUST NOT получать `consumer_expansions` или новую semantic obligation.

## Executable matrix

| Case | Input | Required observable result |
|---|---|---|
| A | Synthetic #20 canonical migration/schema owner | Direct migration verifier selected |
| B | A plus downstream current-schema/recovery capability | Recovery/current-schema verifier selected |
| C | B plus indirect runtime/inventory capability | Indirect verifier selected; direct presence does not hide it |
| D | Synthetic #148 authoritative current-assignment owner | All direct/transitive registered consumers selected without full-suite inference |
| E | Protected path without capability owner | Fail closed with stable diagnostic |
| F | Protected path with two owners | Fail closed as ambiguous |
| G | Edge to undeclared capability | Policy validation fail closed |
| H | Terminal verifier absent from inventory | Fail closed; no coverage credit |
| I | Registered verifier file removed | Fail closed; no coverage credit |
| J | Graph declarations reordered / contains cycle | Canonical-equivalent finite result |
| K | Direct verifier already selected by another reason | One execution, preserved consumer causal evidence, indirect still selected |
| L | Local presentation-only change | No consumer expansion; existing FAST/presentation behavior preserved |
| M | Complete protected ownership | Existing Slice A semantic escalation and full integration closure unchanged |

## BEFORE / AFTER measurement

The same disposable synthetic inputs MUST be executed against unmodified Slice A and Slice B candidate. BEFORE records exact consumer-specific selections absent from the plan (even if Slice A broadly executes the same check); AFTER records exact mechanically reachable direct/recovery/indirect identities and causal chains for #20 and #148. Broad category selection MUST NOT be misreported as ownership proof.

## Done and stop

Gate 1, intended RED, independent Gate 3, separate minimal implementation, focused GREEN, independent Gate 5 and one exact-source GitHub CI run are recorded. No local full suite, product code, #153C/D, T07a+/#107, fixture reachability, merge/deploy/settings. Final record states `#153 Slice B delivered`; `#153 remains open`; C/D untouched; then stop.
