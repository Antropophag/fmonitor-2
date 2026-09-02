# CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001 v0.2

Status: approved by the TEST-USER-READY pilot-behavior-inventory mission for Gate 1. This is an explicitly `PILOT_ONLY` executable characterization of the current rapid-pilot scheduling oracle. It is not product-owner approval of inspection cadence, target authorization, rescheduling, cancellation, reassignment behavior, or the current persistence mechanism.

## Actor and intent

A discovery/test agent needs a small deterministic oracle for the current creation and sequential exact-duplicate behavior before inspection planning is moved behind an application seam or its schema ownership changes.

The characterized actor is an already authenticated synthetic pilot user. The current HTTP handler admits that actor only when the submitted CSRF token matches the authenticated request context and an active local role grants the exact pilot capability `inspection.schedule`. Those are pilot observations only.

## Public oracle seam

- Stable future verifier entry point: `php rapid-pilot/verify-inspection-schedule-duplicate.php`.
- Every behavioral action SHALL be a real form-encoded HTTP `POST` to `/pilot/objects/{positive-decimal-id}/inspection-schedule` through one test-owned loopback server and router, and SHALL execute `RapidPilotInspectionSchedule::handle(...)`.
- The router MAY inject the pre-agreed synthetic authenticated actor id and CSRF value into request context before dispatch. It SHALL NOT validate, normalize, rewrite, retry, classify, or persist a scheduling command.
- Neither the verifier nor the meta-test may call a private handler method or issue schedule/event `INSERT` statements as a substitute for the HTTP action.
- Target seam candidate `InspectionPlanning::scheduleInspection(...)` is not confirmed or implemented by this characterization.

## Fixed oracle fixture

Expected values are literals fixed at Gate 1, not values copied from verifier output or recomputed with production scheduling logic.

- Moscow clock: `FMONITOR_NOW=2026-09-01T09:30:00+03:00`.
- Authenticated CSRF token: `schedule-characterization-csrf-001`.
- Accepted fixture: legacy object `451201`, installation case `6101`, state `working`, registered assignment order version `2`, control engineer `7301`, scheduler `8101`, inspection date `2026-09-03`.
- Scheduler `8101` is an active local pilot account connected only to an active role granting `inspection.schedule`.
- Denial actor `8301` is active but none of its active roles grants `inspection.schedule`.
- Each rejection uses an otherwise isolated eligible fixture unless its scenario changes that precondition.

The submitted date, accepted case state, registered assignment order, and capability spelling are current-oracle inputs, not target product policy.

## Isolation and anti-fake contract

1. The caller SHALL supply exactly one `FMONITOR_INSPECTION_SCHEDULE_VERIFY_RUN_TOKEN` of 12 lowercase hexadecimal characters and an exact repository-owned `FMONITOR_INSPECTION_SCHEDULE_VERIFY_ARTIFACT_ROOT`. Missing, malformed, symlinked, non-directory, or out-of-bound inputs are rejected before database or filesystem mutation. `/tmp`, a home root, and fallback locations are forbidden.
2. The run derives one SQL prefix from that token. The complete prefix SHALL contain only lowercase ASCII letters, digits and underscores and SHALL be no longer than 28 bytes. It owns only the exact fixture table names enumerated by the Gate 2 test and the exact artifact child `inspection-schedule-<token>` directly below the validated root; no wildcard namespace is authorized.
3. Before mutation, the meta-test SHALL reject any occupied exact owned SQL name or artifact child. It SHALL NOT reuse, repair, truncate, rename, drop, or inspect rows in an occupied namespace.
4. The meta-test owns fixture DDL and inserts for identity/role, installation-case, assignment-order, schedule and schedule-event tables. It SHALL pre-create the schedule tables and SHALL NOT invoke `RapidPilotInspectionSchedule::ensureSchema(...)` as setup. Direct SQL is permitted only for isolated setup, independent audit and exact cleanup; it is not the behavioral seam.
5. The handler's current runtime `ensureSchema(...)` remains executable oracle behavior, but this specification does not approve HTTP-owned DDL. Structural fingerprints of all exact fixture tables SHALL be unchanged by every request.
6. The meta-test SHALL create an ambient decoy sibling with fixed unpredictable bytes outside the owned artifact child and record a fingerprint of unrelated process-prefixed tables before starting the server. The decoy and unrelated fingerprint SHALL remain unchanged.
7. The test-owned router SHALL append, for every dispatched scheduling request, a record containing an unpredictable per-run nonce known independently to the meta-test, the request method and the route. After the verifier exits, and before cleanup, the meta-test SHALL independently prove exactly six such `POST` records (creation, replay and four rejections), read their routes from that log, and query the database to prove the exact persisted history. Verifier stdout, verifier-produced summaries, and verifier exit status are never sufficient evidence. An echo-only verifier therefore fails.
8. One bounded loopback server process serves all requests in a run. On success or any failure, the meta-test SHALL stop and reap that exact process, remove only the exact owned SQL tables and artifact child, and prove no owned member remains. Cleanup is bounded and idempotent; process-name scans and wildcard SQL/filesystem cleanup are forbidden.
9. The meta-test SHALL run the verifier twice with distinct unoccupied tokens. Both runs SHALL produce byte-identical normalized stdout, empty stderr, complete independent request-log and database evidence, and no surviving owned member.

Ports, process ids, nonces, tokens, prefixes, database names, paths, SQL, and secrets SHALL NOT enter normalized stdout.

## Accepted creation and exact history

- **GIVEN** the accepted fixture and no schedule or schedule-event row
- **WHEN** actor `8101` submits the exact CSRF token and `inspectionDate=2026-09-03` through the public HTTP seam
- **THEN** the response is exactly status `303`, `Location: /pilot/objects?inspectionScheduled=2026-09-03`, and `Cache-Control: no-store`
- **AND** exactly one schedule row exists with installation case `6101`, legacy object `451201`, control engineer `7301`, date `2026-09-03`, scheduling actor `8101`, and `scheduled_at=2026-09-01T09:30:00+03:00`
- **AND** exactly one event exists for that persisted schedule identity and case `6101`, with `event_type=inspection_scheduled`, actor `8101`, and `occurred_at=2026-09-01T09:30:00+03:00`
- **AND** the event's raw UTF-8 JSON is exactly `{"scheduleId":<persisted schedule id>,"inspectionDate":"2026-09-03","controlEngineerUserId":7301}`; the placeholder is the decimal identity created by the request, and the decoded payload contains exactly those three keys and values
- **AND** no other fixture row, structural fingerprint, decoy, unrelated table, or unowned artifact changes.

Stable milestone:

`INSPECTION_SCHEDULE created responses=1 schedules=1 events=1 history=exact`

The generated numeric identity itself is not pinned. Exact referential identity and payload bytes are observable history; an auto-increment allocation is persistence presentation state.

## Sequential exact duplicate is a successful no-op

- **GIVEN** the accepted history above
- **WHEN** the same actor submits the same route, CSRF token and inspection date again
- **THEN** the response is again exactly status `303`, the same `Location`, and `Cache-Control: no-store`
- **AND** exactly the original one schedule and one event remain
- **AND** every persisted column and raw payload byte of both original rows is unchanged
- **AND** no new domain row, structural change, or unowned filesystem artifact is created.

Stable milestone:

`INSPECTION_SCHEDULE sequential-duplicate responses=2 schedules=1 events=1 mutations=0`

Possible auto-increment counter consumption by the current persistence mechanism is not a domain fact and is not asserted.

## Four zero-mutation rejection boundaries

Each rejection starts from its own clean fixture and compares exact schedule/event history and all relevant fixture fingerprints before and after the HTTP request. Exact translated response text is not asserted.

1. Wrong submitted CSRF token against the authenticated token returns `403` and creates no schedule or event.
2. Active actor `8301` with no active role granting `inspection.schedule` submits the correct CSRF and valid date; the response is `403` and creates no schedule or event.
3. Authorized actor submits impossible date `2026-02-30`; the response is `422` and creates no schedule or event.
4. Authorized actor submits valid date `2026-09-03` for a case in state `needs_assignment_order`; the response is `409` and creates no schedule or event.

Every rejection also leaves existing case/order/authorization facts, table structures, decoy, unrelated tables, and unowned artifacts unchanged.

Stable milestone:

`INSPECTION_SCHEDULE rejections csrf=403 capability=403 invalid-date=422 ineligible-case=409 mutations=0`

These status codes, validation order, accepted case-state set, capability spelling, and current role lookup are pilot observations only.

## Stable transcript

Normalized stdout SHALL contain the three milestone lines above in specification order, followed by exactly:

`CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001`

No generated id, port, pid, request nonce, token, prefix, table, database, path, translated message, SQL text, or timestamp other than fixed contractual values may appear. Exact spec/test/expected-transcript hashes are pinned at Gate 3. The verifier hash is not a Gate 3 input and is pinned only at Gate 5.

## Classification and explicit deferrals

- `PILOT_ONLY`, characterized and not promoted: manually supplied date; current date validation; exact capability spelling and local-role lookup; current CSRF/status ordering; accepted `working` state; registered-order precondition; tuple identity `(case, engineer, date)`; identical `303` response for creation and exact replay; current schedule/event columns and JSON; current no-op mechanism; fixed timestamp representation.
- `PRODUCT_ACCEPTED` context only: inspection planning is assigned work distinct from inspection evidence; state change belongs behind one public application seam; authorization is explicit; audit/history is append-only; calendars, queues and screens are read models rather than domain owners.
- Explicitly deferred and not exercised: concurrent requests or winner selection; stale assignment and reassignment races; first or recurring cadence; seven-day/business-day/holiday calculations; automatic next dates and date bounds; who may schedule for whom; maximum/pending visit multiplicity; overdue behavior; reschedule, cancel, supersede and their reasons; target authorization/status/messages; visibility/queue/calendar policy; completion and inspection evidence; schema redesign, canonical DDL and removal of runtime DDL.

This contract SHALL NOT infer target semantics from any deferred behavior.

## Failure classification and Gate 2 evidence

- `SETUP_FAILURE`, exit `2`: unavailable MariaDB; invalid token/root; occupied namespace; fixture construction failure; loopback bind/readiness failure; incomplete request-log proof; process/timeout failure; or inability to audit or clean the exact private namespace.
- Qualifying Gate 2 `RED`: the focused verifier is absent, or a healthy isolated HTTP/MariaDB meta-test demonstrates that the current oracle violates one exact statement above.
- `REGRESSION_FAILURE`, exit `1`: unexpected HTTP response; wrong history/cardinality; original-history rewrite; rejection mutation; structural drift; nondeterministic transcript; decoy/unrelated-state damage; cleanup leak; or failure of an implemented assertion.

Environment/setup failure is never RED. The Gate 2 author SHALL retain the exact command and intended failure in the independent test-review record. Expected initial focused command:

`php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php`

## Done definition

The slice is done only after every mandatory gate in `docs/development-process.md` completes:

1. this v0.2 Gate 1 contract remains approved under the pilot-behavior-inventory mission;
2. focused intended RED for this exact public HTTP contract is demonstrated;
3. a fresh separately tasked test reviewer records `APPROVED`, pinning only the spec, test and expected-transcript hashes;
4. a minimal verifier reaches GREEN while the test-owned loopback request log and independent database audit prove real execution, creation, replay, the four rejection boundaries, deterministic two-run output, isolation and bounded cleanup;
5. existing inspection/checklist characterizations remain green;
6. `make architecture-check`, relevant regression, `make verify` and diff checks introduce no new regression;
7. a fresh separately tasked code reviewer records `APPROVED` and pins the verifier hash at Gate 5.

Done does not create or approve `InspectionPlanning`, target scheduling semantics, target authorization, concurrency, stale-assignment policy, cadence, reschedule/cancel, production schema, or removal of runtime DDL. This v0.2 file completes Gate 1 only.
