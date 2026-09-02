# Completion declaration recording — pilot behavior evidence

This note is derived from `rapid-pilot/CompletionFlow.php`,
`rapid-pilot/router.php`, `rapid-pilot/LocalAuth.php` and
`rapid-pilot/verify-completion-flow.php`. It records the existing declaration
oracle only; it does not approve that oracle as target product behavior.

## Capability and classification

- Capability: record the first `declaration` completion fact after a PTO fact.
- Actor intent: supply the declaration date and textual details so the current
  pilot projection presents the installation case as complete.
- Inventory relation: the declaration half of PB-07.
- Classification: `PILOT_ONLY` characterization. PB-07 remains
  `ACCEPTED_WITH_CHANGES` and target semantics remain under GRILL-001.
- Target context: Installation Completion.
- Candidate future seam: `InstallationCompletion::recordDeclaration`; this is a
  planning candidate, not an approved or implemented application contract.
- Recording PTO is a prerequisite contrast and remains a separate slice.

## Current public path and validation order

The route is `POST /pilot/objects/{positive-integer}/completion` with
`action=record_declaration`. `RapidPilotLocalAuth` runs first: an unauthenticated
or deactivated session receives `303 /pilot/login`; an active authenticated
session supplies `FMONITOR_AUTH_USER_ID` and a form CSRF token. The completion
route runs before the normal production entrypoint/request-origin policy.

The reachable handler order is:

1. non-POST → `405`, `Метод не поддерживается.`;
2. missing/mismatched form `csrfToken` → `403`, `Недопустимый запрос.`;
3. connect to MariaDB and run request-time `CREATE TABLE IF NOT EXISTS` for
   `fm2_pilot_completion_facts`;
4. require a positive active pilot user id; handler-internal failure is `403`,
   though ordinary local auth already rejects deactivated sessions;
5. capture live `Europe/Moscow` time;
6. begin a transaction and `FOR UPDATE` the unique case selected by legacy
   object id; missing/non-unique case → `404`, `Объект не найден.`;
7. sum hard-coded weights for distinct `item_completed` ids, capped at 85;
   progress below 85 → `409`, `Сначала завершите монтажные работы до 85%.`;
8. require an existing `pto_act` → otherwise `409`,
   `Сначала зафиксируйте дату акта ПТО.`;
9. require no existing `declaration` → otherwise `409`,
   `Декларация уже зафиксирована.`;
10. validate declaration date/details → otherwise `422`,
    `Укажите дату и реквизиты декларации.`;
11. insert, commit and return `303` to
    `/pilot/objects/{id}#completion` with `Cache-Control: no-store`.

Plain errors contain one trailing LF and use
`Content-Type: text/plain; charset=UTF-8`. The handler does not validate
`Origin` or `Sec-Fetch-Site`.

## Input and stored fact

`declarationDate` must be an actual Gregorian date in exact `YYYY-MM-DD` form
and no later than the live Moscow date. There is no lower bound and no ordering
constraint relative to order, opening or PTO dates.

`declarationDetails` is cast to string, outer whitespace is trimmed, must remain
non-empty and may contain at most 500 characters according to the worker's
`mb_internal_encoding()` because `mb_strlen` receives no explicit encoding.
The planned oracle must start and preflight workers with UTF-8 before describing
the boundary as Unicode characters.
Internal content/whitespace is otherwise preserved. There is no structured
number, issuer, registry status, artifact, hash, source or evidence reference.

The insert records case id, literal `fact_type=declaration`, submitted date,
trimmed details, live `DATE_ATOM` Moscow timestamp and active actor id. It does
not append a process event, advance a task, update case/order/legacy state or
record an operation id/revision. The visible 100%/completed state is only a read
projection derived from the presence of both PTO and declaration rows.

## Authorization and process admission

Any active pilot user can mutate any uniquely resolved case with seeded
progress 85 and PTO. There is no declaration capability, role, current engineer
assignment, object scope, separation of duties, registered-order, opened-state
or `process_state` check. A coherent unopened/non-working case can therefore be
mutated. These are security observations, not recommended target rules.

## Replay and concurrency

- Exact replay returns duplicate `409`, not idempotent success.
- Changed date/details replay also returns duplicate `409`; duplicate detection
  precedes payload validation and preserves the original row.
- Same-case concurrent declarations serialize on the case lock: with PTO
  present, one request commits `303`, the follower returns `409`, and exactly
  one declaration remains.
- Concurrent first PTO and declaration are order-dependent. Declaration succeeds
  only when it locks after PTO commits; otherwise it returns missing-PTO `409`.
- There is no client operation/idempotency key or revision precondition.

The PTO/declaration race is a separate cross-command behavior and should not be
silently folded into the minimal declaration characterization.

## Runtime DDL and clock

Request-time table creation occurs before case, progress, PTO, duplicate, action
and payload checks. A reachable active-session request for a missing object can
therefore create the table and still return `404`; non-POST, failed-CSRF and
unauthenticated/deactivated-session paths do not reach completion DDL.

`CompletionFlow::now()` ignores `FMONITOR_NOW` and writes whole-second
`DATE_ATOM` from live `Europe/Moscow`. A deterministic verifier must sample
Moscow bounds immediately before/after the request, round bounds outward to
whole seconds, assert parseability/`+03:00`/inclusive containment and normalize
the concrete timestamp. A Moscow-midnight crossing should discard the private
fixture namespace and retry with the same literal ids under a bounded deadline;
no production clock seam is justified for characterization.

## Existing verifier gap

`rapid-pilot/verify-completion-flow.php` directly seeds PTO/declaration facts and
checks render/projection changes. It does not invoke the mutation endpoint or
cover router/local-auth/session/CSRF, HTTP responses, actor/case/progress/PTO
ordering, input trimming/Unicode limit, insert values, replay, concurrency,
runtime DDL or the live clock.

## Tables touched

- Creates/writes: `fm2_pilot_completion_facts`.
- Reads/locks: `fm2_installation_cases`.
- Reads: `fm2_checklist_operations`, `fm2_pilot_completion_facts`,
  `fm2_pilot_users`.
- Local auth also reads its user/credential/session support before dispatch.

## Target contrast and exactly blocked slices

The characterization must not approve:

- declaration as mandatory for completion or worth the final 15%;
- the 85 threshold;
- free text as sufficient declaration evidence;
- broad authorization or the same actor recording both documents;
- dates earlier than PTO/opening/order;
- duplicate rejection as target idempotency;
- direct correction or absence of append-only supersession;
- declaration-driven OTIZ/payment readiness.

GRILL-001 questions 2–3 block target `COMPLETION-DECLARATION-001`, durable
`canonicalize-installation-completion-schema`, and downstream OTIZ behavior that
uses declaration as terminal evidence. They do not block a strictly
`PILOT_ONLY` declaration characterization.
