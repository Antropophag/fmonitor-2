# Inspection-planning schema ownership evidence

Evidence captured: 2026-09-01. This document records the current rapid-pilot
storage and call paths. It is not approval of inspection cadence, scheduling,
rescheduling, cancellation, authorization, or any other product semantics.

## Scope and sources

The family consists of exactly these process-prefixed tables:

1. `fm2_pilot_inspection_schedules`
2. `fm2_pilot_inspection_schedule_events`

Primary source is `RapidPilotInspectionSchedule::ensureSchema()` in
`rapid-pilot/InspectionSchedule.php`. Supporting evidence was read from:

- `rapid-pilot/InspectionSchedule.php` (DDL, command, event append and control
  read path);
- `rapid-pilot/Calendar.php` (calendar bootstrap and schedule projection);
- `rapid-pilot/docker-bootstrap.php` (environment bootstrap call);
- `rapid-pilot/router.php` (request routing and construction-control wiring);
- `rapid-pilot/verify-calendar-projections.php` (the only existing indirect
  verifier);
- `docs/operations/runtime-ddl-migration-plan.md`,
  `docs/operations/pilot-behavior-inventory.md`, and
  `docs/operations/migration-backlog-and-grill.md`.

The runtime source has two consecutive, independently committed
`CREATE TABLE IF NOT EXISTS` statements. It has no schema version, migration
ledger, compatibility inspection, transaction spanning both statements, or
rollback of the first table if the second statement fails.

## Current DDL owners and call paths

| Entry point | DDL timing | Subsequent use |
|---|---|---|
| `RapidPilotInspectionSchedule::handle()` | Calls `ensureSchema` immediately after opening the DB, before CSRF and capability validation | validates scheduling input, reads canonical case/order data, inserts a schedule and then an event |
| `RapidPilotInspectionSchedule::enhanceControl()` | Calls `ensureSchema` before reading today's schedules | annotates the construction-control projection |
| `RapidPilotCalendar::read()` | Calls `ensureSchema` before all calendar source reads | projects schedule dates into the calendar |
| `rapid-pilot/docker-bootstrap.php` | Calls `ensureSchema` during disposable pilot bootstrap | prepares the two tables before optional fixtures |

`router.php` dispatches the scheduling POST to `handle()` and passes the
construction-control response through `enhanceControl()`. Consequently an
otherwise rejected scheduling request can currently execute DDL before its
CSRF/authorization checks, and read-only calendar/control requests also own
schema creation.

No other production source creates or alters these two tables. The calendar
verifier pre-registers both names only for cleanup because the read path may
create them. The architecture baseline records the two source DDL statements.

## Exact source manifests

All columns are in the source declaration order. `NULL` below means SQL
nullability; every current column is `NOT NULL` and has no explicit default.

### `${prefix}fm2_pilot_inspection_schedules`

| # | Column | Source type | Nullable | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `id` | `BIGINT UNSIGNED` | no | none | `AUTO_INCREMENT`, primary key |
| 2 | `installation_case_id` | `BIGINT UNSIGNED` | no | none | none |
| 3 | `legacy_object_id` | `BIGINT UNSIGNED` | no | none | none |
| 4 | `control_engineer_user_id` | `BIGINT UNSIGNED` | no | none | none |
| 5 | `inspection_date` | `DATE` | no | none | none |
| 6 | `scheduled_by_user_id` | `BIGINT UNSIGNED` | no | none | none |
| 7 | `scheduled_at` | `VARCHAR(40)` | no | none | none |

Indexes, in source order:

| Name | Unique | Type / visibility | Ordered columns |
|---|---|---|---|
| `PRIMARY` | yes | BTREE / visible | `id` |
| `unique_planned_inspection` | yes | BTREE / visible | `installation_case_id`, `control_engineer_user_id`, `inspection_date` |
| `calendar_date` | no | BTREE / visible | `inspection_date`, `id` |
| `engineer_day` | no | BTREE / visible | `control_engineer_user_id`, `inspection_date`, `id` |

There are no foreign keys and no CHECK constraints.

### `${prefix}fm2_pilot_inspection_schedule_events`

| # | Column | Source type | Nullable | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `id` | `BIGINT UNSIGNED` | no | none | `AUTO_INCREMENT`, primary key |
| 2 | `schedule_id` | `BIGINT UNSIGNED` | no | none | none |
| 3 | `installation_case_id` | `BIGINT UNSIGNED` | no | none | none |
| 4 | `event_type` | `VARCHAR(80)` | no | none | none |
| 5 | `payload_json` | `JSON` | no | none | server-expanded metadata described below |
| 6 | `actor_user_id` | `BIGINT UNSIGNED` | no | none | none |
| 7 | `occurred_at` | `VARCHAR(40)` | no | none | none |

Indexes, in source order:

| Source spelling / observed name | Unique | Type / visibility | Ordered columns |
|---|---|---|---|
| `PRIMARY` | yes | BTREE / visible | `id` |
| unnamed `KEY(schedule_id,id)` / `schedule_id` | no | BTREE / visible | `schedule_id`, `id` |
| unnamed `KEY(installation_case_id,id)` / `installation_case_id` | no | BTREE / visible | `installation_case_id`, `id` |

There are no foreign keys. MariaDB expands `JSON` to `LONGTEXT CHARACTER SET
utf8mb4 COLLATE utf8mb4_bin` plus `CHECK (json_valid(payload_json))`. On the
observed server that generated CHECK is named `payload_json`. The expanded type,
binary JSON collation, validation expression, and generated constraint are
semantic schema metadata; their server-generated presentation name is not a
portable application-owned identifier.

Both source statements specify `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` and omit
an explicit table collation.

## MariaDB 11.4.7 observation

Observation environment:

- image/server: `11.4.7-MariaDB-ubu2404`;
- database and server charset: `utf8mb4`;
- database and server collation: `utf8mb4_uca1400_ai_ci`;
- isolated prefix: `ipsev1_`;
- no production or shared-prefix tables were touched.

MariaDB rendered `BIGINT UNSIGNED` as `bigint(20) unsigned`. The schedules table
used table collation `utf8mb4_uca1400_ai_ci`; its `scheduled_at` inherited that
collation. The events table used the same table collation for `event_type` and
`occurred_at`, while `payload_json` used `utf8mb4_bin`. All indexes were visible
BTREE indexes (`IGNORED=NO` in MariaDB metadata), with ascending collation and
no prefix lengths. No generated columns or generation expressions existed.

On a clean family, each table had zero rows and `AUTO_INCREMENT=1`. Exact
`SHOW CREATE TABLE` hashes, including the isolated physical table name, were:

| Table | Clean SHA-256 |
|---|---|
| `ipsev1_fm2_pilot_inspection_schedules` | `299360f442635d4e29d01fc07db4261806d7aba4c4da28cdd9192b03641ec2e7` |
| `ipsev1_fm2_pilot_inspection_schedule_events` | `1c33cefbbb88a6aa29a290104c39bd5d5250e22072202a007e9e7fe6429dcae1` |

After inserting two schedules and their two events, both tables had two rows
and `AUTO_INCREMENT=3`. The populated `SHOW CREATE TABLE` hashes changed only
because MariaDB included `AUTO_INCREMENT=3`:

| Table | Populated SHA-256 | Row-state SHA-256 |
|---|---|---|
| schedules | `e6b2b9e1a1e84d31a9739fc0ec6cee2154874f4bbb673fa094c0954b598d93ef` | `b8383516caae632339a54810d324150d215db58e70b0cacf04f505929d8a706f` |
| events | `e3ef8aef3ccebfaf6ee3c038fb8f72795079de5ddcfc4bd74e900430b6774540` | `2706559df1fb54f748d8567cdf2f661e04865e228f234a2ffcfe689fc4b59819` |

Re-running the current `ensureSchema()` against that populated family left the
two `SHOW CREATE` hashes, row hashes, row counts, and auto-increment values
byte-for-byte identical. This proves preservation for an exact present schema;
it does not prove compatibility checking because `IF NOT EXISTS` skips all
inspection.

Cardinality and `TABLE_ROWS` reported by `information_schema` are optimizer
estimates and must not be part of a canonical fingerprint. `AUTO_INCREMENT` is
preservation state, not a structural compatibility field.

## Dependencies and ordering

Physical creation order in the current function is schedules first, events
second. There are no database FKs, so MariaDB can physically create either
table first. Application behavior nevertheless has these dependencies:

1. scheduling reads canonical `fm2_installation_cases` and
   `fm2_assignment_orders` and requires an open/working case with a registered
   latest order and assigned control engineer;
2. a schedule row is inserted first;
3. only after a new schedule insert succeeds is its
   `inspection_scheduled` event appended in the same application transaction;
4. calendar and construction-control projections read schedules but do not read
   the event table.

Thus the ownership migration depends operationally on canonical process v1 and
registered assignment data, while its current DDL itself has no enforced
cross-table dependency. Absence of FKs is evidence about the current schema,
not approval to keep or add them.

## Prefix and identifier boundary

MariaDB table identifiers are limited to 64 bytes. The accepted source prefix
alphabet is ASCII, so character and byte counts coincide.

| Basename | Bytes | Maximum prefix for a 64-byte table name |
|---|---:|---:|
| `fm2_pilot_inspection_schedules` | 30 | 34 |
| `fm2_pilot_inspection_schedule_events` | 36 | 28 |

The events basename therefore makes **28 bytes** the maximum safe common
process prefix for this family. The longest current index name is
`unique_planned_inspection` at 25 bytes and does not include the prefix.

This exposes a catalogue-level contract conflict that a future executable spec
must resolve: the currently approved runner accepts prefixes through 32 bytes,
and the proposed checklist-template prerequisite narrows that to 29 bytes, but
this family cannot form its longest table name with either 29, 30, 31, or 32
bytes. Canonical registration of this family requires a pre-connection runner
ceiling of at most 28 bytes (or an independently approved physical rename). A
29-byte prefix produces a 65-byte events table identifier and must not be sent
to MariaDB.

The private HTTP `prefix()` method currently uses
`/^[A-Za-z0-9_]+$/` (non-empty, with no length check). Other call paths pass
their configured prefix directly to public `ensureSchema()`, which performs no
prefix validation. Canonical runner validation must be authoritative and occur
before DB inspection or mutation.

## Partial, interrupted, and incompatible states

Because MariaDB DDL commits per statement and the current family has two
independent statements, these states are reachable:

| Existing family | Current `ensureSchema()` outcome | Risk / preservation fact |
|---|---|---|
| neither table | creates schedules, then events | clean happy path |
| exact schedules only | leaves schedules untouched and creates events | repairs one compatible interrupted order |
| exact events only | creates schedules and leaves events untouched | repairs reverse partial state despite source order |
| both exact, empty or populated | performs no structural or row mutation | empirically confirmed for populated state |
| incompatible schedules only | silently accepts it, then creates events | returns without proving the family usable; may leave a new second table beside an incompatible first |
| incompatible events only | creates schedules, then silently accepts incompatible events | can add the first table before discovering later behavior failure |
| either name occupied by an incompatible table | `IF NOT EXISTS` does not compare columns, indexes, engine, collation, CHECKs, or data | conflict is deferred to later reads/writes |
| first CREATE commits and second raises/crashes | schedules remains committed | family is partial; application transaction cannot roll DDL back |

There is no destructive recovery path in `ensureSchema()`. A canonical additive
migration must preserve all rows and auto-increment state in exact/approved
compatible present tables, may fill only absent members of an explicitly
compatible partial family, and must fail closed with zero mutation for an
incompatible present member. In particular it must inspect the whole existing
family before creating the missing member; otherwise a conflict in the second
member can still leave a newly created first member.

Generated index/CHECK names, `AUTO_INCREMENT`, optimizer cardinality, and
MariaDB display widths require an explicit semantic-vs-presentation comparison
policy. That policy belongs in the executable schema spec, not in this evidence
record.

## Canonical ownership target (behavior-neutral)

Moving ownership without approving PB-06 semantics requires all of the
following:

- register an additive sequential migration after its canonical prerequisites;
- reproduce or explicitly approve the two structural manifests above on a
  fresh MariaDB 11.4.7 database;
- validate the database-default `utf8mb4` collation explicitly rather than
  relying on server/environment inheritance;
- inspect the full two-table family before any mutation and distinguish absent,
  compatible-present, compatible-partial, and incompatible states;
- preserve populated compatible tables, their rows, JSON values, IDs, and
  auto-increment state;
- make scheduling POST, calendar reads, construction-control rendering, and
  docker bootstrap consume the canonical schema without calling
  `ensureSchema()` or executing any replacement runtime DDL;
- retain focused characterization for duplicate/date/event behavior separately
  from the schema ownership contract;
- leave cadence, due-date, reschedule/cancel, overdue and role semantics in
  GRILL rather than silently encoding them in the ownership migration.

After cutover, a missing or incompatible inspection-planning schema is an
environment/deployment failure. HTTP and read-model paths must fail closed and
must not repair schema on demand.

## Cleanup evidence

Both exact `ipsev1_` tables were dropped after observation. A query for
`ipsev1_%` returned no remaining tables, and the disposable Compose test
environment was subsequently torn down with volumes removed.
