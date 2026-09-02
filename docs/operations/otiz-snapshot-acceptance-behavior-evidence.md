# OTIZ snapshot acceptance — pilot behavior evidence

This note is derived from `rapid-pilot/router.php`, `rapid-pilot/LocalAuth.php`,
`rapid-pilot/Otiz.php`, its reconciliation ledgers and
`rapid-pilot/verify-otiz-workflow.php`. It records the existing acceptance
oracle only; it does not approve that oracle as target product behavior.

## Capability and classification

- Capability: accept one literal premium snapshot draft.
- Actor intent: mark a blocker-free calculation snapshot accepted so the pilot
  treats it as an immutable basis for downstream payment-closure commands.
- Inventory relation: PB-09.
- Classification: `PILOT_ONLY` characterization. PB-09 remains
  `ACCEPTED_WITH_CHANGES`; acceptance meaning, exact authority and downstream
  financial consequences remain under GRILL-001.
- Target context: Premium Decisions.
- Candidate future seam: `PremiumDecisions::acceptPremiumSnapshot`; this is a
  planning candidate, not an approved or implemented application contract.
- Snapshot calculation, formulas, evidence admission and payment closure are
  separate slices. The focused oracle can seed a literal draft.

## Current public path and validation order

The route is `POST /pilot/otiz/snapshots/{digits}/accept`. `RapidPilotLocalAuth`
runs first. An unauthenticated or deactivated session receives the auth-layer
redirect before `RapidPilotOtiz` is constructed. An active session supplies
the actor and form CSRF token; the router then constructs `RapidPilotOtiz`
before dispatching the path.

The reachable order is:

1. validate the configured table prefix and connect to MariaDB;
2. require a positive active pilot user;
3. resolve the actor's grants and require broad `otiz.manage`; failure returns
   `403`, `Раздел доступен сотрудникам ОТиЗ и администраторам.`;
4. copy the auth CSRF value, then execute all constructor-time schema checks;
5. for every POST, require an exact non-empty form `csrfToken`; failure returns
   `403`, `Недопустимый запрос.`;
6. match the acceptance path and cast its digit string to an integer;
7. begin a transaction and `FOR UPDATE` the snapshot row;
8. missing snapshot (including id `0`) rolls back and returns `404`,
   `Срез не найден.`;
9. a non-`draft` snapshot rolls back and returns `303` to
   `/pilot/otiz/snapshots/{id}?error=immutable`;
10. count issues with exactly `severity='blocker' AND state='open'`;
11. a positive count rolls back and returns `303` to
    `/pilot/otiz/snapshots/{id}?error=blockers`;
12. update the still-draft row to `accepted` with live actor/time, append one
    `snapshot_accepted` event, commit, then return `303` to
    `/pilot/otiz/snapshots/{id}?accepted=1`.

Redirects include `Cache-Control: no-store`. Plain failures include one trailing
LF and `Content-Type: text/plain; charset=UTF-8`. The form supplies no operation
id, revision, acceptance reason, evidence reference or separation-of-duties
attestation.

The pilot has no route-level `405` for this capability. An authenticated
`GET /pilot/otiz/snapshots/{id}/accept` runs constructor schema checks and then
returns generic `404` with `Not found.\n`. A valid-CSRF unknown OTIZ `POST` runs
the same checks and returns plain `404`, `Команда не найдена.\n`; bad CSRF wins
before unknown-command matching and returns the common `403`. These method and
unknown-command cases are public-router contrasts, but need not be acceptance
cases in the minimal focused oracle unless its executable spec opts into them.

## Accepted state and content unchanged by this command

For a blocker-free literal draft the snapshot changes only:

- `status`: `draft` → `accepted`;
- `accepted_at`: `NULL` → live Moscow `DATE_ATOM`;
- `accepted_by_user_id`: `NULL` → current active actor id.

All other snapshot columns, including `content_hash`, remain byte-for-byte
unchanged. Snapshot objects, allocations, issues and evidence are not updated.
One event is inserted with the same snapshot id, `object_id=NULL`, literal
`event_type=snapshot_accepted`, the current actor and payload JSON
`{"hash":"<the pre-update content_hash>"}`. `accepted_at` and event
`occurred_at` are sampled by separate calls and must not be assumed equal.

The command does not recompute or verify `content_hash`, totals, formula
version, object/allocation consistency, report date, calculation evidence or
calculated actor. A syntactically valid literal draft is therefore sufficient
to characterize this transition without importing formula semantics.

## Blockers and non-blockers

- At least one open blocker prevents acceptance and causes zero acceptance/event
  mutation.
- An open warning does not prevent acceptance.
- A resolved blocker does not prevent acceptance.
- The query is snapshot-wide; issue `object_id` is irrelevant.
- A missing or already accepted snapshot causes zero business mutation after
  constructor-time schema effects.

These are observations of the pilot query, not approval that warning/resolution
semantics or evidence sufficiency are correct for the target product.

## Authorization and process admission

Any active actor granted broad `otiz.manage` can accept any matching draft.
There is no exact `premium_snapshot.accept` capability, object scope, relation
to the calculator, four-eyes/separation-of-duties rule or snapshot-specific
assignment. Authorization happens before constructor DDL. Conversely, an
authorized actor with bad CSRF, an unknown command or a missing snapshot still
causes constructor schema checks before command rejection.

## Replay and concurrency

- Sequential exact replay is not idempotent success: it returns the immutable
  redirect and does not append another event or rewrite actor/time.
- A changed-CSRF replay is rejected before the snapshot lock but after schema
  checks.
- Two concurrent authorized accepts serialize on the snapshot row lock. The
  winner updates and appends exactly one event; after the winner commits, the
  follower observes `accepted`, returns the immutable redirect and makes no
  business mutation.
- There is no client operation/idempotency key or expected revision.

The focused verifier must use two genuinely concurrent workers/connections and
a start barrier if it claims concurrency; the PHP development server requires
multiple workers for two simultaneous HTTP requests.

A real LocalAuth/router verifier also shares the pilot's fixed session directory
unless carefully isolated. Planning must allocate a unique loopback port (and
therefore cookie name), unique verifier-owned session ids, disable session GC,
delete only the exact session files it created, and prove unrelated session
files survive success and failure. It must not sweep the shared directory.

## Runtime DDL and clock

After active-user and `otiz.manage` admission, every request constructs all
three schema-owning collaborators before CSRF/path/business validation. In
order, the request checks or creates:

1. seven `fm2_pilot_otiz_*` tables: snapshots, snapshot objects, snapshot
   allocations, snapshot issues, snapshot evidence, payment closures and
   events;
2. the `unique_reversal` index on payment closures through an
   `information_schema.statistics` probe and conditional `ALTER TABLE`;
3. `fm2_migrated_evidence_decisions`;
4. `fm2_migrated_evidence_projection`,
   `fm2_migrated_evidence_conflicts` and
   `fm2_migrated_evidence_decision_state`;
5. `fm2_migration_quarantine_decisions`.

Thus an authorized bad-CSRF request in a namespace that already contains the
minimum LocalAuth/RBAC prerequisite tables, but none of these twelve
constructor-owned tables, can create all twelve; an unauthorized actor cannot.
The broad workflow verifier calls OTIZ bootstrap before requests, masking the
first seven creates and the conditional alter; it also dispatches the class
directly instead of exercising LocalAuth/router.

`RapidPilotOtiz::now()` ignores `FMONITOR_NOW` and writes whole-second
`DATE_ATOM` from live `Europe/Moscow`. A deterministic characterization must
bound both stored timestamps with independently sampled Moscow times, normalize
their concrete values and avoid asserting equality. A bounded retry may discard
only its private fixture namespace if a Moscow-midnight boundary invalidates a
date-sensitive assertion.

## Existing verifier gap

`rapid-pilot/verify-otiz-workflow.php` proves a broad calculated workflow,
including blocked acceptance and an unchanged row after sequential replay. It
does not prove the real LocalAuth/router/session exchange, exact HTTP status and
headers, authorization-before-DDL ordering, bad-CSRF DDL, all twelve schema
effects, literal-fixture isolation, exact accepted actor/time, exact event
payload/cardinality, child-table immutability, warning/resolved-blocker behavior
or concurrent acceptance. Its explicit bootstrap also couples acceptance to
calculation fixtures that the focused oracle does not need.

## Tables touched by the acceptance command

- Business lock/update: `fm2_pilot_otiz_snapshots`.
- Business read: `fm2_pilot_otiz_snapshot_issues`.
- Business append: `fm2_pilot_otiz_events`.
- Constructor DDL/checks: the twelve tables listed above.
- Authentication/authorization reads: pilot user and access-policy support
  tables before dispatch.
- Snapshot objects, allocations and evidence are witnesses that this acceptance
  command leaves content unchanged; this slice proves no database-wide write
  protection against other writers.

## Target contrast and exactly blocked slices

The characterization must not approve:

- broad `otiz.manage` as the target acceptance authority;
- self-acceptance by the calculator or absence of four-eyes control;
- pilot blocker/warning/resolution semantics as sufficient financial evidence;
- acceptance without a reason, operation id or expected revision;
- non-idempotent replay as target semantics;
- acceptance as automatic permission to create or complete payments;
- pilot table shapes, runtime DDL or `content_hash` trust as canonical design.

GRILL-001 OTIZ-scope, payment-closure and separation-of-duties decisions block
target `PREMIUM-SNAPSHOT-ACCEPTANCE-001`, the canonical premium schema and
downstream closure/payment slices. They do not block a strictly `PILOT_ONLY`
snapshot-acceptance characterization.
