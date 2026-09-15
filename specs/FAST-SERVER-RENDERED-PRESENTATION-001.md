# FAST-SERVER-RENDERED-PRESENTATION-001

## Простыми словами

Существующий FAST route расширяется ровно на один закрытый класс исправлений уже
существующего server-rendered отображения. Planner допускает его только по
repository-owned boundary и зарегистрированному public oracle; название файла,
размер diff и заявление агента доказательством не являются. Любое смешение с
семантикой данных, полномочий, состояния, offline или delivery policy сохраняет
более строгий lane.

## Public seam

`python3 tools/delivery/change-verification.py plan|check|run` и public
`python3 tools/delivery/harness.py prepare`. Actor — delivery root/executor и
Quality Graph. Authority — exact Git source, `.quality-graph/verification-policy.json`
и canonical verification inventory.

## Acceptance contract

1. Closed policy boundary MAY declare `fast_class` equal to
   `bounded-server-rendered-presentation`, exact existing owner patterns, and at
   least one registered public oracle. The planner MUST select the class only
   when every effective boundary is that class or the existing `bounded-ui`
   presentation companion and exactly one repository-owned public oracle is
   selected.
2. A qualifying plan returns `FAST`, `required_reviews=["final"]`,
   `fast_class="bounded-server-rendered-presentation"`, a stable reason,
   `selected_public_oracle`, and machine-readable
   `negative_boundaries_checked`. Preflight, focused selected verification and
   exact-source CI remain the existing FAST contract.
3. The negative set MUST cover schema/migration, persistence/write,
   authoritative current state, domain/application mutation, authorization/RBAC/
   session/admission, CSRF/security, offline/cache/service-worker/synchronization,
   integrations, jobs/outbox/scheduler, verification/admission policy,
   runtime/deployment/configuration and product/spec semantics. Unknown or mixed
   ownership fails closed.
4. A label/heading/aria/current-state presentation change, unchanged-route
   navigation rendering, and registered view plus CSS presentation MAY be FAST.
   A permission owner, controller/action, SQL/read-model owner, schema/migration,
   persistence/current-state owner, offline/service-worker owner, product/OpenSpec,
   verification policy, new route/action, missing public oracle or unknown owner
   MUST NOT be FAST. #153A semantic and #132 sensitive escalation take precedence.
5. At least one executable replay MUST use public prepare -> selected verification
   and prove the selected oracle rejects a deliberately defective presentation
   variant for its observable behavior, rather than checking only the lane string.
6. STANDARD/CRITICAL Gate 3 and final semantics, the independent FAST final
   review, existing FAST v1 class, test coverage and product code remain unchanged.
   No LLM classifier, AST analyzer, dependency graph, second planner/registry/Gate,
   diff-size heuristic or later T05.x class is introduced.

## Historical replay and proxy

The delivery record MUST preserve current-main BEFORE and candidate AFTER lane,
boundaries, selected checks and policy-required independent review dispatch count
for three bounded presentation replay candidates. Suitable candidates change
from two reviews to one; token usage remains `UNKNOWN` without telemetry.
