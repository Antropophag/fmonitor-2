## Context

См. `proposal.md` и delta spec. Current declaration mutation разделяет endpoint, table и helpers с PTO, но имеет отдельные prerequisite/payload/terminal effects. Existing verifier directly seeds both facts and therefore cannot protect the command boundary. Target semantics are blocked by GRILL-001, so design must characterize without deepening rapid-pilot or selecting a durable schema.

Future ownership candidate is bounded application module `InstallationCompletion` with seam candidate `recordDeclaration`. The candidate module may depend on completion contracts/ports and approved process facts; it may not depend on HTTP/UI, rapid-pilot or concrete MariaDB. Persistence would belong to a future completion adapter and DDL to canonical migrations, neither created here.

## Goals / Non-Goals

**Goals:**

- exercise local-auth/session/form-CSRF through the real rapid-pilot router;
- distinguish declaration-specific PTO prerequisite, trimmed Unicode payload, persisted fact and terminal projection input;
- prove sequential replay and real multi-worker same-case serialization;
- make broad admission, missing date ordering, live clock and runtime DDL visible as migration risks;
- integrate the verifier into canonical characterization without new pilot domain logic.

**Non-Goals:**

- recording PTO or exercising concurrent PTO/declaration ordering;
- approving declaration as mandatory/terminal/15%;
- designing structured evidence, target authorization or correction;
- production application seam/schema/migration changes.

## Decisions

### 1. Real HTTP is the only accepted command seam

Harness logs in through LocalAuth, retains server cookie/form token, disables redirect following and posts URL-encoded forms through `router.php`. Response assertions pair with raw DB fingerprints and request evidence. Direct handler calls or fact DML cannot establish mutation behavior.

Alternative direct helper invocation is rejected because it bypasses local-auth ordering, CSRF, router dispatch and response headers.

### 2. PTO is setup evidence, never execution evidence

Each accepted declaration case receives one exact byte-fingerprinted PTO prerequisite. The harness never claims to have exercised `record_pto`, and declaration assertions require that row to remain unchanged. PTO/declaration race is a future separate behavior because it adds cross-command scheduling scope.

### 3. Unicode boundaries use independent literal operands

Accepted 500- and rejected 501-character strings are fixture literals constructed by the test layer and verified by an independent UTF-8 character counter, not production validation code. Server launch explicitly sets `-d mbstring.internal_encoding=UTF-8`; preflight invokes the same PHP executable/options to require `mb_internal_encoding() === 'UTF-8'`, and all worker processes inherit those options. Trim scenarios fix exact before/after bytes. This makes `mb_strlen`/trim regressions observable without promoting free-text evidence as target design.

### 4. Clock and date fixtures remain live but bounded

Harness samples `Europe/Moscow` immediately before/after, rounds bounds outward to whole seconds, derives today/tomorrow/earlier dates from the same calendar and normalizes concrete values. A date crossing discards the entire private namespace and retries with the same literal ids within a deadline. No production clock seam is added.

### 5. Concurrency requires observed multiple workers

The production PHP CLI server runs with `PHP_CLI_SERVER_WORKERS=4`. Preflight proves requests were served by at least two PIDs. Two independent authenticated sessions release requests at one parent barrier against one prefixed DB; result is winner-neutral `{303,409}` plus one fact. A single worker or sequential client cannot satisfy the scenario.

### 6. Architecture debt is observed, not grandfathered

Missing-object HTTP proves reachable request-time completion DDL without enumerating schema details from code under test. Architecture guard remains unchanged and forbids new runtime DDL/rapid-pilot mutation growth. Verifier belongs only to characterization/test layers.

### 7. Shared LocalAuth session directory is preserved

LocalAuth hard-codes `/home/fmonitor/.local/state/fmonitor2/sessions`, so the harness does not claim directory ownership and adds no production test seam. Each server owns a unique validated port, which produces a unique cookie name, and clients accept only newly issued random session ids. Server launch also fixes `-d session.gc_probability=0`, and same-options preflight requires effective integer zero before starting HTTP assertions; `session_start()` therefore cannot garbage-collect unrelated files. The parent snapshots unrelated session files, tracks exact files for owned ids, removes only those files after worker shutdown and proves unrelated file names/bytes unchanged.

## Risks / Trade-offs

- [Characterization could look like terminal-rule approval] → every terminal/progress/admission observation is marked `PILOT_ONLY`; target slices remain `NEEDS_GRILL`.
- [Live midnight race] → discard/recreate namespace with identical ids and bounded retry.
- [PHP server silently falls back to one worker] → verify distinct serving PIDs before concurrency assertion or classify setup failure.
- [Long Unicode/form encoding obscures actual bytes] → set/preflight UTF-8 on the same PHP executable/options; retain exact encoded request bytes and decoded persisted bytes in fingerprints, normalize neither.
- [Shared session handling could delete another run/user state] → explicit/preflighted `session.gc_probability=0`, unique ports/cookies, exact owned-id allowlist and unrelated session file byte snapshots; never remove the directory or glob files.
- [Failure cleanup hides primary result] → bounded teardown reports separately while preserving initial failure classification.

## Migration Plan

1. Prepare exact executable Gate 1 and obtain explicit owner approval for the `PILOT_ONLY` oracle only.
2. Capture minimal accepted declaration RED and obtain independent test review before GREEN.
3. Add expanded prerequisite/payload/admission/replay/concurrency/DDL RED and obtain a new independent test review before GREEN.
4. Register canonical characterization and run focused/regression/architecture/isolation checks.
5. Update inventory/backlog with target contrast, retaining GRILL blockers.
6. Obtain separate independent code review; then sync/archive through OpenSpec lifecycle.

Rollback removes only verifier registration/harness and lifecycle records; production rapid-pilot and schema are unchanged.
