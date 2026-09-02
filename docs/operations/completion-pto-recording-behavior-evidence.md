# Completion PTO recording — pilot behavior evidence

This note is derived from `rapid-pilot/CompletionFlow.php`, `rapid-pilot/router.php`,
`rapid-pilot/LocalAuth.php`, and `rapid-pilot/verify-completion-flow.php`. It records
the current pilot oracle; it does not approve the behavior as the target product
contract.

## Capability and classification

- Capability: record the first `pto_act` completion fact for one installation case.
- Actor intent: state the date of the PTO act after the pilot's installation-progress
  threshold has been reached.
- Inventory relation: the PTO half of PB-07.
- Classification: `PILOT_ONLY` characterization. The target threshold, evidence,
  authorization, correction model, and relationship to declaration remain subject
  to GRILL-001 questions 2–3.
- Candidate future seam: `InstallationCompletion::recordPtoAct`. This name is a
  planning candidate, not an approved or implemented application contract.
- Declaration recording is deliberately outside this slice.

## Current HTTP path and ordering

`POST /pilot/objects/{positive-integer}/completion` is dispatched after
`RapidPilotLocalAuth::handle()` and before the normal production request/origin
policy. Local authentication redirects an unauthenticated browser and, for an
authenticated session, provides `FMONITOR_AUTH_USER_ID` and
`FMONITOR_AUTH_CSRF`.

The handler performs these steps in order:

1. Reject a non-POST request with `405` and plain text.
2. Compare form `csrfToken` with `FMONITOR_AUTH_CSRF`; reject mismatch with `403`.
3. Connect to MariaDB and execute runtime `CREATE TABLE IF NOT EXISTS` for
   `fm2_pilot_completion_facts`.
4. Require only that `FMONITOR_AUTH_USER_ID` identifies an active row in
   `fm2_pilot_users`; otherwise reject with `403`.
5. Start a transaction and lock the unique installation case selected by legacy
   object id. A missing or non-unique case produces `404`.
6. Sum the hard-coded weights of distinct `item_completed` operations, capped at
   85. A value below 85 produces `409` before action dispatch.
7. For `action=record_pto`, reject an existing `pto_act` with `409`, then validate
   `ptoActDate` as a real `YYYY-MM-DD` date no later than the current Moscow date;
   invalid or future values produce `422`.
8. Insert the fact and commit, then return `303` with
   `Location: /pilot/objects/{id}#completion` and `Cache-Control: no-store`.

The route performs no `Origin`/`Sec-Fetch-Site` check. Inside the completion
handler, table creation occurs before actor validation and before the transaction.
That actor failure is not reachable through the ordinary router: local auth first
revalidates active status and redirects a deactivated session to login.

## Persisted fact and observable effects

The successful insert records:

- installation case id;
- `fact_type = pto_act`;
- submitted date;
- empty `details`;
- live `Europe/Moscow` timestamp from `now()`;
- active pilot user id.

The table has a unique key on `(installation_case_id, fact_type)`. The mutation
does not append a process event, operation id, task change, case-state change, or
legacy-system update. There is no foreign key, revision, source-document,
evidence, or correction/supersession field.

## Authorization and admission actually enforced

Any active pilot user can record PTO for any uniquely resolved case whose seeded
checklist operations reach 85. The handler does not check a capability, role,
current engineer assignment, object scope, assignment-order state, opening fields,
or `process_state`. Consequently, a non-working case can mutate if it has enough
completion operations, and an active out-of-scope user can mutate it.

If a previously authenticated user is deactivated, the router returns `303` to
`/pilot/login` before entering completion handling; it neither returns the
handler-internal actor `403` nor triggers completion-table DDL. These are oracle
observations, not recommended authorization rules.

## Replay and concurrency

- Exact replay is not treated as idempotent success: after the first fact it is
  rejected with `409`.
- A changed-date replay is also rejected with `409`; duplicate detection happens
  before date validation.
- There is no client operation id.
- Concurrent commands for the same case serialize on the case row lock. With no
  pre-existing PTO fact, one request can commit with `303` and the other observes
  the fact and returns `409`; exactly one fact persists.

## Existing verifier gap

`rapid-pilot/verify-completion-flow.php` creates isolated prefixed tables and
checks render/projection behavior using directly seeded completion facts. It does
not exercise the router, local-auth session, CSRF behavior, real HTTP status and
headers, actor admission, transaction, PTO insert, rejection ordering, replay,
concurrency, or request-triggered DDL.

## Characterization fixture and clock boundary

The executable characterization should use an isolated table prefix and an actual
HTTP request through the pilot router. It should seed the smallest deterministic
set of distinct completed items whose hard-coded weights total 85, a unique case,
and explicit active/inactive/out-of-scope users. Each scenario must inspect both
the response and persisted rows and clean up its prefix.

`CompletionFlow::now()` ignores `FMONITOR_NOW` and reads the live Moscow clock
with `DATE_ATOM` whole-second resolution.
The characterization must not introduce a production clock seam. For a successful
insert it should capture wall-clock bounds immediately before and after the
request, round the bounds outward to whole-second precision, assert that
`recorded_at` is a parseable Moscow timestamp within those bounds and on the
expected Moscow date, then normalize the concrete timestamp out
of any golden comparison. Date-boundary-sensitive fixtures should derive today
from the same observed Moscow clock rather than hard-code it.

## Target contrast and unresolved decisions

The pilot's `85% + PTO + declaration = 100%` model has no approved normative
source. Native OTIZ currently reads a PTO snapshot from assignment-order data,
not this completion fact, so the two sources are disconnected. The following are
not accepted by this characterization and remain blocked by GRILL-001 questions
2–3:

- durable progress/threshold semantics;
- whether declaration is mandatory for completion;
- authorized roles and object scope;
- required document/evidence reference;
- correction/supersession semantics;
- reconciliation with assignment-order and OTIZ inputs.
