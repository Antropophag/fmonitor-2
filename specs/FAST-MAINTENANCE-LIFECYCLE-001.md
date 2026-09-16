# FAST-MAINTENANCE-LIFECYCLE-001

## Простыми словами

Если planner уже доказал FAST, а маленькое исправление только возвращает давно
описанное поведение, новый OpenSpec не должен появляться механически. Harness
связывает задачу с существующим требованием и его digest, но сохраняет RED,
независимый final review и exact-source CI. Любая новая или чувствительная
семантика возвращает обычный полный lifecycle.

## Actor и public seam

Actor — delivery root/executor/reviewer. Public seam —
`python3 tools/delivery/harness.py prepare|state` и созданные ими plan/package/
active binding. Authority — актуальный verification planner plan; T06 не выбирает
FAST самостоятельно.

Input `lifecycle` для запроса shortcut:

- `intent="FAST_MAINTENANCE"`;
- `issue` — непустая issue/task reference;
- `semantic_change=false`;
- `requirement_status="CURRENT"`;
- непустой `canonical_requirements`, каждый элемент содержит repository-relative
  `path`; полный acceptance text не копируется;
- `executable_regression` — test path, уже mapped в acceptance plan.

Legacy/OpenSpec input без этого declaration остаётся normal lifecycle.

## Acceptance contract

1. Harness MUST вернуть machine-readable `lifecycle.route` ровно
   `FAST_MAINTENANCE` или `OPENSPEC_REQUIRED` и stable `reason`. Shortcut допустим
   только если planner выбрал `FAST`, declaration имеет форму выше, каждый
   requirement существует как regular repository file, а executable regression
   принадлежит mapped acceptance. Harness MUST переиспользовать planner
   `fast_class`, `fast_reason`, boundaries и selected plan; path size/name или
   заявление агента сами по себе не выбирают FAST.
2. `FAST_MAINTENANCE` package/binding MUST содержать issue, exact base/source,
   planner FAST class/reason, canonical requirement path + SHA-256, literal
   `semantic_change=false`, executable regression, verification plan path/digest,
   `final_review`, `ci` и `disposition`. До соответствующего evidence последние
   поля равны `PENDING`/`UNKNOWN`/`IN_PROGRESS`, а не APPROVED/GREEN/PR_READY.
   Отдельный FAST registry или копия acceptance prose запрещены.
3. Prepare/state MUST пересчитывать requirement digests. Изменившийся/исчезнувший
   requirement делает binding `STALE`; prepared executor-role routing MUST
   запретить продолжение старого package и потребовать root rebuild/re-evaluation.
4. `OPENSPEC_REQUIRED` обязателен при non-FAST planner lane, missing requirement,
   `semantic_change` не равном false, новом behavior/acceptance/domain invariant/
   architecture decision, schema/persistence, auth/security, external integration,
   offline/state/service worker, verification/admission policy или owner decision.
   `requirement_status="CONFLICTING"` даёт normal route с `NEEDS_OWNER` reason.
   T06 не пишет OpenSpec автоматически.
5. FAST maintenance executor-role preparation MUST иметь executable intended RED.
   PR-ready disposition MUST иметь один independent final review APPROVED и
   exact-source CI GREEN. `UNKNOWN` не является approval/GREEN. Gate 3 не
   добавляется для planner FAST; STANDARD/CRITICAL сохраняют Gate 3 + final.
6. Explicit OpenSpec proposal/status/validate и inputs текущих OpenSpec changes
   MUST продолжать normal route без regression. Исторические changes не
   удаляются и не мигрируют.

## Executable examples A–N

- **A** Existing requirement + planner FAST + presentation bugfix →
  `FAST_MAINTENANCE`, без новых proposal/design/tasks/delta-spec.
- **B** A без intended RED → implementation admission blocked.
- **C** A без independent final approval → PR-ready blocked.
- **D** Requirement digest изменён после prepare → `STALE`, rebuild required.
- **E** Small UI + new product behavior/spec path → `OPENSPEC_REQUIRED`.
- **F** Permission/auth/security semantics → normal sensitive lifecycle.
- **G** Schema/persistence → normal lifecycle.
- **H** Offline/service-worker/state → normal lifecycle.
- **I** Verification/admission policy → normal lifecycle.
- **J** Planner STANDARD → normal lifecycle независимо от diff size.
- **K** Missing canonical requirement → fail-closed `OPENSPEC_REQUIRED`.
- **L** Conflicting references → normal discovery + `NEEDS_OWNER`.
- **M** Historical T05.1 #160 `feedback-confirmation.php` replay, который
  authoritative current planner классифицирует FAST: до shortcut mandatory repository
  evidence set — executable spec, proposal, delta spec, design, tasks,
  verification input, regression, Gate 3 record, final record, delivery record;
  после — verification input/compact record, regression, final record, delivery
  closeout, при переиспользовании canonical requirement. Replay MUST вычислить
  counts и bytes/chars, reviews `2 → 1`, proposal confirmation stops `1 → 0`.
  Regression, final review, exact-source CI и traceability остаются отдельно
  подтверждёнными. Это proxy, не фактическая token telemetry.
- **N** Existing explicit `openspec-propose`/validate route продолжает работать.

## Boundaries

Нет product state, persistence, authorization, audit/history, concurrency,
runtime/deployment, backup/restore или rapid-pilot изменений: это tooling policy.
Любой такой impact является отрицательным routing witness, а не частью shortcut.
