# CHARACTERIZE-OTIZ-SNAPSHOT-ACCEPTANCE-001 v0.1

Status: `DRAFT / OWNER_APPROVAL_REQUIRED` for Gate 1. This is a strictly
`PILOT_ONLY` characterization of the current snapshot-acceptance command. It
does not approve broad `otiz.manage`, self-acceptance, blocker/warning policy,
trusted `content_hash`, missing reason/evidence/revision/operation id,
non-idempotent replay, runtime DDL or any payment consequence as target rules.

GRILL-001 continues to own target acceptance meaning, exact authority,
separation of duties, evidence sufficiency and payment closure. Candidate future
owner `PremiumDecisions::acceptPremiumSnapshot` is planning direction only.

## Actor, intent and public seam

The fixed fictional actors are:

- `8801`, `Анна Орлова`, `otiz.acceptor@shlz.ru`: active and granted broad
  `otiz.manage` through the literal pilot access fixture;
- `8802`, `Борис Лебедев`, `otiz.denied@shlz.ru`: active with no role,
  capability or assignment granting `otiz.manage`;
- `8803`, `Вера Соколова`, `otiz.second@shlz.ru`: active with the same broad
  `otiz.manage`, used only as the second concurrency contender.

Actor intent is to accept one existing blocker-free pilot premium snapshot.
Every characterized command SHALL use production-composed loopback
`rapid-pilot/router.php`: login through `/pilot/login`, server-issued cookie and
form CSRF, followed by an `application/x-www-form-urlencoded` request
`POST /pilot/otiz/snapshots/{digits}/accept` with exact sole form member
`csrfToken=<server token>`. Redirect following is disabled.

Direct `RapidPilotOtiz` calls, direct acceptance DML, verifier stdout, rendered
HTML or response-only evidence cannot satisfy the oracle. Per-request evidence
SHALL bind method/path and a parent-known unpredictable query nonce to a serving
PID. A verification-only read-only `auto_prepend_file` logger may append the
tuple `PID, REQUEST_METHOD, parse_url(REQUEST_URI, PATH), verifierNonce` before
including the unchanged router; it MUST NOT alter globals, sessions, routing,
responses or DB state. Each request URI is the stated exact path plus sole query
member `verifierNonce=<32 lowercase hex>`; router path matching remains the
stated exact path. Raw database fingerprints SHALL independently prove effects.

For response contracts below:

- every redirect has exact status `303`, exact `Location`, exact
  `Cache-Control: no-store`, and an empty body;
- the LocalAuth redirect has exact status `303`, exact
  `Location: /pilot/login`, exact `Cache-Control: no-store`, and an empty body;
- every plain failure has no `Location`, exact
  `Content-Type: text/plain; charset=UTF-8`, the stated UTF-8 body plus one LF;
- OTIZ plain failures do not assert `Cache-Control`, because current
  `RapidPilotOtiz::fail` does not set it; the LocalAuth redirect and OTIZ
  redirects do;
- headers not named above are outside this slice and MUST NOT substitute for a
  missing named header.

## Private namespace, server and session ownership

Each run owns one unpredictable table prefix
`otiz_accept_<20 lowercase hex>_`, validated before use and rejected on any
collision. It creates only private prefixed prerequisites/fixtures. A separate
ambient DB table and row outside that prefix are recorded byte-for-byte and
MUST survive success, setup failure and regression failure.

The real LocalAuth directory is shared. Each server SHALL reserve a unique
loopback port, giving a unique LocalAuth cookie name, and use unpredictable
validated verifier-owned session ids distinct for actors `8801` and `8803`.
The PHP executable/options used by every worker SHALL be preflighted with
integer `session.gc_probability=0`. The parent records the exact owned session
filenames, creates one unrelated session decoy with unpredictable name/bytes,
stops/reaps workers before cleanup, deletes only those exact owned files and
proves the decoy unchanged. Directory removal, glob deletion and sweeping
unknown session files are forbidden.

The server SHALL use `PHP_CLI_SERVER_WORKERS=4`. Preflight SHALL prove at least
two distinct serving PIDs. Ports, cookie names, session ids, SQL prefixes,
request nonces and child processes MUST be unique across concurrent verifier
runs. Every wait, barrier, request and reap has a bounded timeout.

## Exact LocalAuth and RBAC prerequisites

The fixture creates the exact current LocalAuth/RBAC table manifests required by
the public exchange, without invoking production bootstrap or DDL helpers:
`fm2_pilot_users`, `fm2_pilot_auth_credentials`,
`fm2_pilot_auth_attempts`, `fm2_pilot_roles`, `fm2_pilot_user_roles` and
`fm2_pilot_role_permissions`. Their schema SHALL match the independently
fingerprinted current manifests in `IDENTITY-ACCESS-SCHEMA-001 v0.1`; the
verifier copies no expected columns from runtime `CREATE TABLE` strings. No
invitations, user-status events or unrelated access rows are needed.

Each user row has its id/name/email from the actor section plus exact
`phone=''`, `status=1`, `activation_state=active`, `session_version=1`, and
`source_updated_at=2026-08-31T08:00:00+03:00`. There are no other user columns.
Each credential has `email_normalized` equal to the lowercase actor email,
`password_hash` equal to a setup-created Argon2id hash of the shared literal
password `Otiz-Gate1-Fixture-2026!`, `password_set_at` and `updated_at` equal to
`2026-08-31T08:00:00+03:00`. Hash salt/bytes are setup data and normalized; the
known plaintext and `password_verify` success are fixed expectations. Auth
attempts starts empty.

Role `18801` has exact `code=otiz_gate1`, `name=OTIZ Gate 1`,
`description=Gate 1 literal broad OTIZ role`, `status=1`,
`source_updated_at=2026-08-31T08:00:00+03:00`; its sole role-permission row is
`(18801, otiz.manage)`. Its only user-role rows are
`(8801,18801,gate1_fixture,2026-08-31T08:00:00+03:00,NULL)` and
`(8803,18801,gate1_fixture,2026-08-31T08:00:00+03:00,NULL)` in exact column order
`user_id, role_id, origin, assigned_at, assigned_by_user_id`. User `8802` has no
user-role row. Full ordered prerequisite rows are
fingerprinted before/after every admission case; only login-attempt rows and
session files created by the described login exchange may differ before the
acceptance-request baseline.

For every authenticated client, login is exact:

1. `GET /pilot/login?verifierNonce=<nonce>` obtains initial cookie and parses
   the server-issued form `csrfToken`; status `200` and the form are setup
   evidence, not the business oracle.
2. `POST /pilot/login?verifierNonce=<nonce>` sends ordered form members
   `csrfToken`, `email`. Response `200` exposes the password-stage form and a
   fresh/current server token.
3. A second `POST /pilot/login?verifierNonce=<nonce>` sends ordered members
   `csrfToken`, `email`, `password` with the literal password. It returns `303`
   to `/pilot/objects`; the client retains the regenerated server cookie.
4. `GET /pilot/otiz?verifierNonce=<nonce>` with that cookie returns `200`; the
   acceptance form supplies the exact CSRF used by the command.

Actors `8801` and `8803` execute this flow with independent cookie jars and
distinct initial/regenerated session ids. Actor `8802` executes the same flow,
so its subsequent OTIZ `403` proves RBAC denial rather than authentication
failure. Any missing stage/token/session regeneration is `SETUP_FAILURE`.

## Constructor admission and exact DDL order

Before every business case the public path order is:

1. `RapidPilotLocalAuth` resolves a live session and active user;
2. router constructs `RapidPilotOtiz` only for the matching OTIZ path;
3. constructor resolves active actor and requires broad `otiz.manage`;
4. constructor checks/creates these seven tables in order:
   `fm2_pilot_otiz_snapshots`, `fm2_pilot_otiz_snapshot_objects`,
   `fm2_pilot_otiz_snapshot_allocations`, `fm2_pilot_otiz_snapshot_issues`,
   `fm2_pilot_otiz_snapshot_evidence`, `fm2_pilot_otiz_payment_closures`,
   `fm2_pilot_otiz_events`;
5. constructor probes and conditionally adds unique index `unique_reversal` on
   `fm2_pilot_otiz_payment_closures(reverses_payment_closure_id)`;
6. collaborators check/create `fm2_migrated_evidence_decisions`, then
   `fm2_migrated_evidence_projection`, `fm2_migrated_evidence_conflicts`,
   `fm2_migrated_evidence_decision_state`, then
   `fm2_migration_quarantine_decisions`;
7. `command()` requires exact non-empty form CSRF;
8. acceptance route parses digits, starts a transaction and performs
   `SELECT ... FOR UPDATE` on the snapshot.

Three isolated DDL fixtures SHALL prove this order:

1. Actor `8802` uses a live valid session against prerequisites with none of
   the twelve tables. Response is plain `403`, exact body
   `Раздел доступен сотрудникам ОТиЗ и администраторам.\n`; all twelve tables
   and `unique_reversal` remain absent and business state is unchanged.
2. Actor `8801` uses an exact 64-character all-zero CSRF different from its
   server token in a fresh namespace. Response is plain `403`, exact body
   `Недопустимый запрос.\n`; exactly all twelve listed tables and exact unique
   index now exist, with zero acceptance rows/events.
3. In another namespace all twelve exact tables exist but payment closures lacks
   only `unique_reversal`. The same bad-CSRF request returns the same `403` and
   body; exact `unique_reversal` is added, while table catalogue and every row
   fingerprint are otherwise unchanged.

An unauthenticated request in a fourth prerequisite-only namespace returns the
LocalAuth redirect before constructor effects; all twelve tables remain absent.
These DDL effects are architecture debt, not target persistence semantics.

## Literal snapshot fixtures

No calculation/bootstrap/formula helper may create expected data. Each case
seeds a syntactically valid literal snapshot with these values unless overridden:

| Column | Literal |
|---|---|
| `report_date` | `2026-11-30` |
| `status` | `draft` |
| `previous_snapshot_id` | SQL `NULL` |
| `rules_version` | `pilot-literal-no-formula-v1` |
| `calculated_at` | `2026-08-31T09:15:00+03:00` |
| `calculated_by_user_id` | `8899` |
| `accepted_at`, `accepted_by_user_id` | SQL `NULL`, SQL `NULL` |
| totals | `total_pool_cents=123456`, `total_closed_cents=23456`, `total_available_cents=100000` |
| `content_hash` | `0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef` |

The primary snapshot id is `88101`, object id `98101`. It has exactly one object
row: `regnumber=OTIZ-LITERAL-1`, `address=Тестовая улица, 1`,
`previous_progress_bp=1000`, `current_progress_bp=2500`,
`progress_fact_date=2026-11-20`, `premium_cents=10100`, `shaft_bp=102`,
`kss_bp=103`, `accrued_cents=10400`, `fund_cents=10500`,
`closed_before_cents=10600`, `remaining_cents=10700`, `pool_cents=10800`,
`distributed_cents=10900`, `undistributed_cents=11000`,
`calculation_state=ready`, and exact decoded JSON
`{"fixture":"literal","formulaUsed":false}`.

It has exactly one allocation: auto id `88111`, snapshot/object `88101/98101`,
`tab_id=TAB-8801`, `full_name=Иван Тестов`, `position_name=Монтажник`,
`contribution_bp=100`, `base_ktu_bp=10000`, `adjustment_ktu_bp=0`,
`effective_ktu_bp=10000`, `share_bp=10000`, `amount_cents=100000`,
`employment_status=active`, `participation_basis=literal fixture`.

It has exactly one evidence row: snapshot/legacy object `88101/78101`,
`admission_state=confirmed_not_mapped`, `source_label=Gate 1 literal evidence`,
`source_locator=fixture://otiz/88101/78101`, `snapshot_hash=<64 lowercase a>`,
`projection_hash=<64 lowercase b>`, `evidence_grade=A`, exact decoded JSON
`{"fixture":"literal-evidence"}`. Primary accepted fixture has no issue row.

All snapshot columns, full child rows and pre-existing non-acceptance events are
fingerprinted as ordered typed values immediately before POST. Expected values
come only from this specification, never production premium/formula/hash code.

## Successful acceptance and persisted facts

For snapshot `88101` with no issue, actor `8801` sends valid CSRF:

- response is `303`, exact `Location` is
  `/pilot/otiz/snapshots/88101?accepted=1`, `Cache-Control: no-store`, body empty;
- only snapshot fields change: `status draft→accepted`, `accepted_by_user_id
  NULL→8801`, and `accepted_at NULL→<MOSCOW_ACCEPTED_AT>`;
- every other snapshot field including `content_hash`, every object,
  allocation, issue and evidence row, and every pre-existing event is
  byte-for-byte unchanged;
- exactly one new event has snapshot `88101`, SQL `NULL` object, literal type
  `snapshot_accepted`, actor `8801`, exact decoded JSON
  `{"hash":"0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef"}`
  and `occurred_at=<MOSCOW_EVENT_AT>`.

This command does not update child rows and the oracle does not claim global
write protection against other commands.

## Live Moscow timestamps

`accepted_at` and event `occurred_at` are separate live calls ignoring
`FMONITOR_NOW`. The parent samples `Europe/Moscow` immediately before and after
the HTTP request, floors the lower bound and ceils the upper bound to whole
seconds. Each stored value independently MUST parse as `DATE_ATOM`, carry
`+03:00`, contain whole-second precision and fall inclusively within the bounds.
They MUST NOT be required equal or ordered relative to each other beyond those
bounds. Concrete values normalize separately to `<MOSCOW_ACCEPTED_AT>` and
`<MOSCOW_EVENT_AT>`.

If a Moscow calendar boundary invalidates a date-sensitive fixture assertion,
one bounded retry first tears down the entire private namespace/process/session
ownership and recreates it under a new prefix; ambient decoys remain untouched.

## Rejected and non-blocking issue cases

Each case has isolated ids and a complete pre-request fingerprint. Constructor
schema is already present; every result below leaves snapshot/children/events
byte-identical except the two explicitly successful non-blocking cases.

- Missing id `88100`: valid actor/CSRF returns plain `404`, no `Location`, exact
  body `Срез не найден.\n`.
- Already accepted id `88102`: preseeded accepted actor `8877` and time
  `2026-08-30T10:20:30+03:00`; response `303`, exact `Location`
  `/pilot/otiz/snapshots/88102?error=immutable`; original actor/time/history
  remain unchanged.
- Open blocker id `88103`: one issue with object `98103`, severity `blocker`,
  code `LITERAL_BLOCKER`, message `Литеральный блокер`, owner `ОТиЗ`, state
  `open`, with `resolution`, `resolved_by_user_id`, `resolved_at` all SQL `NULL`;
  response `303`, exact `Location`
  `/pilot/otiz/snapshots/88103?error=blockers`, zero acceptance mutation.
- Open warning id `88104`: identical issue dimensions except severity `warning`,
  code `LITERAL_WARNING`, message `Литеральное предупреждение`, state `open`,
  and all three resolution fields SQL `NULL`; response success redirect with
  `accepted=1`, three
  acceptance fields and exactly one acceptance event change as above.
- Resolved blocker id `88105`: severity `blocker`, state `resolved`, resolution
  `Проверено`, resolver `8877`, time `2026-08-30T11:00:00+03:00`; response
  success redirect with `accepted=1`, while issue row remains byte-identical.

All redirects use the common redirect header/body contract. Warning and
resolved-blocker acceptance are explicitly `PILOT_ONLY` observations.

## Sequential replay

Immediately after successful `88101` commit, actor `8801` repeats the identical
valid request. Response is `303`, exact `Location`
`/pilot/otiz/snapshots/88101?error=immutable`, exact `Cache-Control: no-store`,
empty body. Accepted actor/time, all content/children and the single
`snapshot_accepted` event remain byte-identical. Replay is not idempotent
success and this is not a target requirement.

## Real two-worker concurrency

Fresh draft `88106` uses the same literals and no blocker. Before releasing the
clients, the parent opens an independent MariaDB transaction and holds
`SELECT ... FOR UPDATE` on snapshot `88106`. Actors `8801` and
`8803` login independently, retain distinct cookies/session ids/CSRF tokens and
release their POSTs from one parent start barrier. The read-only prepend log
MUST map both distinct request nonces and exact acceptance path to two distinct
serving PIDs. MariaDB lock-wait metadata SHALL then show both corresponding
request transactions simultaneously blocked on the externally held `88106`
record lock before the parent commits/releases its lock. Failure to observe both
within a bounded deadline is `SETUP_FAILURE`, not concurrency evidence. Only
after this observation does the parent release the lock; production router,
LocalAuth, SQL and transaction boundaries remain unchanged.

The unordered response multiset is exactly:

- one `303`, `Location: /pilot/otiz/snapshots/88106?accepted=1`;
- one `303`, `Location: /pilot/otiz/snapshots/88106?error=immutable`.

Both have `Cache-Control: no-store` and empty bodies. Final snapshot is accepted
by whichever actor won (`8801` or `8803`), with one independently bounded Moscow
time and exactly one acceptance event whose actor equals that winner. All other
content/children remain unchanged. Transcript MUST normalize actor outcome as
`winner=<ACTOR_A|ACTOR_B>` and compare the unordered response set; it MUST NOT
pin scheduling order.

## Failure classification, transcript and cleanup

Missing PHP/extension/DB access, unusable session directory, failure to reserve
unique port/prefix, server readiness, fewer than two workers/PIDs, barrier or
fixture setup failure is `SETUP_FAILURE`. A reachable response/fact mismatch is
`REGRESSION`. Cleanup failure is reported separately and never rewrites the
primary classification.

Intentional setup-failure and assertion-regression probes SHALL both prove:

- all verifier-owned child processes are terminated/reaped;
- all and only private prefixed tables are removed;
- all and only exact verifier-owned session files are removed;
- ambient DB row/table and session decoy names/bytes remain unchanged.

A third deterministic cleanup-fault probe starts from an intentional
`REGRESSION`, then directs a verification-only cleanup fault injector to refuse
the first drop of one exact owned table (before issuing SQL, without touching an
ambient target). The transcript MUST retain primary `REGRESSION`, add distinct
`CLEANUP_FAILURE resource=<OWNED_TABLE>`, continue best-effort cleanup, retry the
refused owned action through the normal fallback, finish with zero owned
DB/session/process artifacts and preserve both ambient decoys. The injector is
part of the harness only and cannot intercept production request/transaction
behavior. A fake that replaces the primary classification, stops cleanup after
the first fault, or reports success without removing the owned table MUST fail.

Two sequential successful runs use different raw ownership tokens yet emit an
identical normalized transcript containing milestones for admission/DDL repair,
accepted fields/event/children, five rejection/non-blocking cases, replay,
concurrency, cleanup and final `OTIZ_SNAPSHOT_ACCEPTANCE_OK`. Concrete prefix,
port, cookie, session ids, PIDs, nonces, auto-increment ids and live timestamps
are normalized; business ids, statuses, paths, messages and cardinalities are
not. The second run MUST NOT depend on state from the first.

## Canonical integration and Done

After explicit owner approval only, this slice follows: demonstrated RED → fresh
independent test review → minimal GREEN → focused/regression plus
`git diff --check` and `make architecture-check` → fresh independent code review.
The oracle runs exactly once in canonical characterization and inherits its
setup-blocking contract. A regression makes characterization and aggregate
verification red without hiding other completed stage results.

Done requires durable approved Gate 2/3/5 records, repeatable GREEN evidence,
no production behavior/schema/domain-logic change, no architecture-debt growth,
and PB-09 status retaining target acceptance/payment questions under
`NEEDS_GRILL`.
