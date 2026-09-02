# CHARACTERIZE-COMPLETION-DECLARATION-RECORDING-001 v0.1

Status: `DRAFT / OWNER_APPROVAL_REQUIRED` for Gate 1. This is a strictly
`PILOT_ONLY` characterization of the current first `record_declaration`
mutation. It does not approve declaration as the final 15% or a terminal
product fact, free text as sufficient evidence, the threshold, broad
authorization, duplicate-as-conflict, missing date ordering, request-time DDL,
or any target completion seam.

Technical consistency: `READY_FOR_OWNER_REVIEW` on 2026-09-01 after fresh
independent rereview `completion_declaration_gate1_rereview_v41`. Technical
review is not owner approval.

## Product contrast, actor and intent

Product evidence distinguishes documentary facts from editable fields and
requires source, date, responsible actor and basis. It does not approve the
rapid-pilot rule that a declaration after PTO converts 85% to completed/100%,
nor does it define sufficient declaration evidence. GRILL-001 questions 2–3
still own terminal semantics, authorization, evidence and correction.

The characterized intent is narrower: an authenticated rapid-pilot user submits
one date plus textual details for the first `declaration` fact of an installation
case that already has PTO. Fixed fictional actors are:

- active primary user `7701`, `Анна Петрова`, login
  `declaration.recorder@shlz.ru`;
- active out-of-scope user `7702`, `Олег Смирнов`, login
  `declaration.outside@shlz.ru`, with no relevant role/capability/assignment;
- user `7703`, `Ирина Волкова`, login `declaration.disabled@shlz.ru`, who
  authenticates while active and is set to status `0` before POST;
- fixed control engineer/PTO setup actor `7799`, not a command actor.

## Public oracle seam

- Future verifier entry:
  `php tests/Verification/characterize_completion_declaration_recording_001_test.php`.
- Every serial command SHALL use a production-composed loopback server executing
  `rapid-pilot/router.php`. A client logs in through `/pilot/login`, retains the
  server-issued cookie, extracts the server-created form CSRF from an
  authenticated page and POSTs
  `/pilot/objects/{objectId}/completion` as
  `application/x-www-form-urlencoded`. Redirect following is disabled.
- Declaration forms carry decoded members in exact order `csrfToken`, `action`,
  `declarationDate`, `declarationDetails`; action is literal
  `record_declaration`. Raw encoded request bytes, `Content-Type` and exact
  `Content-Length` are captured before sending.
- Direct `RapidPilotCompletionFlow::handle`, fabricated `FMONITOR_AUTH_*`, direct
  declaration DML, seeded projection output or verifier stdout cannot substitute
  for HTTP execution. PTO is permitted only as byte-fingerprinted setup evidence.
- Parent-known unpredictable request nonces and per-worker PIDs are recorded
  outside application state so request/worker execution cannot be replaced by
  a printed transcript.
- Concurrent declaration SHALL use the production PHP CLI server with
  `PHP_CLI_SERVER_WORKERS=4`. Preflight and access evidence SHALL prove at least
  two distinct serving worker PIDs. Two independent sessions/tokens release
  their forms at one parent barrier against one prefixed DB; one worker/session
  or sequential calls do not satisfy concurrency.

## Shared session and worker-safety contract

LocalAuth hard-codes shared directory
`/home/fmonitor/.local/state/fmonitor2/sessions`; the verifier never owns,
empties, globs or removes that directory.

- Each server binds a collision-checked unique loopback port, yielding unique
  cookie name `fm2auth_<port>`.
- Server command explicitly includes
  `-d mbstring.internal_encoding=UTF-8` and
  `-d session.gc_probability=0`; a preflight using the same PHP executable and
  options must return exact effective values `UTF-8` and `0` before HTTP setup.
  All CLI server workers inherit those options.
- Parent snapshots file names, sizes and SHA-256 for unrelated pre-existing
  session files. It accepts only random session ids newly issued to its clients,
  maps those ids to exact `sess_<id>` files and tracks that allowlist.
- After server shutdown it removes only allowlisted owned files and proves every
  unrelated session file name/size/hash remains byte-equivalent. A new unrelated
  file appearing during the run is not owned and is left untouched.

## Private database namespaces and exact process fixtures

One invocation owns collision-checked main prefix `cdec_<16 lowercase hex>_`
plus three separately collision-checked prefixes
`cdec_c_<16 lowercase hex>_` (CSRF),
`cdec_x_<16 lowercase hex>_` (deactivated) and
`cdec_d_<16 lowercase hex>_` (DDL). Each full prefixed table identifier remains
below MariaDB's 64-byte limit. No table is shared across those four
namespaces. The main namespace contains all PTO-bearing behavior fixtures. Each
probe namespace contains only the auth/process support explicitly needed for its
one request and begins without `fm2_pilot_completion_facts`; it is never used to
fingerprint a main-namespace PTO. All four create only prefixed test tables/rows
and preserve separately named decoy tables/rows byte-for-byte.

Main cases have registered order version `1`, control engineer `7799`, no
declaration initially and exact checklist operands summing to 85 unless noted.

| Purpose | Case | Object | Order | State | Progress | PTO |
|---|---:|---:|---:|---|---:|---|
| accepted/replay | 7601 | 47601 | 8601 | `working` | 85 | present |
| threshold ordering | 7602 | 47602 | 8602 | `working` | 84 | absent |
| missing PTO | 7603 | 47603 | 8603 | `working` | 85 | absent |
| trim/500/501/blank/date fixtures | 7604–7609 | 47604–47609 | 8604–8609 | `working` | 85 | present |
| earlier-than-PTO | 7610 | 47610 | 8610 | `working` | 85 | present |
| out-of-scope actor | 7611 | 47611 | 8611 | `working` | 85 | present |
| unopened/non-working | 7612 | 47612 | 8612 | `assignment_order_prepared` | 85 | present |
| concurrency | 7614 | 47614 | 8614 | `working` | 85 | present |
| unknown action | 7616 | 47616 | 8616 | `working` | 85 | present |

Probe namespaces are exact:

- `cdec_c_…`: active authenticated actor 7701, no case required, object `47617`,
  completion table absent;
- `cdec_x_…`: actor 7703 authenticates while active, then becomes status 0;
  no case required, object `47613`, completion table absent;
- `cdec_d_…`: active authenticated actor 7701, no case for object `47615`,
  completion table absent.

Case 7612 has latest order 8612 status `registered`, but
`actual_start_date`, `opened_at` and `opened_by_user_id` are SQL `NULL`, making a
coherent unopened case rather than an invented state.

Every PTO prerequisite is an exact row:

- matching `installation_case_id`;
- `fact_type=pto_act`;
- `fact_date=<today>`;
- `details=''`;
- `recorded_at=<today>T09:00:00+03:00`;
- `recorded_by_user_id=7799`.

The parent fingerprints every PTO row before POST; declaration behavior may not
modify it. PTO is never created through the characterized server.

The 85 fixture uses distinct ids
`28,29,30,31,32,33,34,35,36,37,38,39,40,41,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27`
with literal weights
`2,2,2,2,1,1,2,1,2,3,3,3,3,2,2,2,1,2,1,1,2,5,1,1,3,2,2,1,2,4,4,3,3,3,2,3,2,1,1,1,1`, independently summed to `85`.
The 84 fixture omits item `32`/weight `1`. Future tests MUST NOT call production
weight logic to produce expected values. Threshold/weights are `PILOT_ONLY`.

## Live Moscow date and timestamp operands

Before and after each date-sensitive POST, the parent samples
`Europe/Moscow`. If Moscow dates differ, it stops workers, discards the entire
private DB namespace and its exact owned session files, then retries under a new
prefix/port with the same literal ids within a bounded deadline.

Within a stable sample date:

- `<today>` is the shared Moscow date;
- `<tomorrow>` is `<today> + 1 calendar day`;
- `<earlier>` is `<today> - 30 calendar days`;
- stored `recorded_at` must parse as whole-second `DATE_ATOM` with `+03:00`,
  fall inclusively between before floored and after ceiled whole-second bounds,
  and have date `<today>`;
- concrete calendar/timestamp values normalize only to `<TODAY>`, `<TOMORROW>`,
  `<EARLIER>` and `<MOSCOW_NOW>` in the transcript.

The verifier may not set system time, use `FMONITOR_NOW` as an oracle or add a
production clock seam.

## Exact declaration request matrix

Every POST uses ordered decoded members `csrfToken`, `action`,
`declarationDate`, `declarationDetails`. `<TOKEN>` means that client's exact
server-issued token; `<BAD64>` is exactly 64 ASCII zeroes and is verified unequal
to `<TOKEN>`. `<J500>`/`<J501>` are the exact `Ж` operands and hashes fixed
below. There are no omitted form members unless this table says `absent`.

| Scenario | Namespace/object | Token | Action | Date | Exact details |
|---|---|---|---|---|---|
| accepted + exact replay | main/47601 | `<TOKEN>` | `record_declaration` | `<today>` | `  ЕАЭС N RU Д-RU.РА01.А.12345/26  ` |
| changed replay | main/47601 | `<TOKEN>` | `record_declaration` | `<tomorrow>` | ` \t\r\n ` |
| below threshold | main/47602 | `<TOKEN>` | `not_a_completion_action` | `2026-02-30` | ` \t\r\n ` |
| missing PTO | main/47603 | `<TOKEN>` | `record_declaration` | `2026-02-30` | ` \t\r\n ` |
| outer trim | main/47604 | `<TOKEN>` | `record_declaration` | `<today>` | ` \t\n  Номер  42 / строка\nвторая  \r\n ` |
| Unicode 500 | main/47605 | `<TOKEN>` | `record_declaration` | `<today>` | `<J500>` |
| Unicode 501 | main/47606 | `<TOKEN>` | `record_declaration` | `<today>` | `<J501>` |
| blank | main/47607 | `<TOKEN>` | `record_declaration` | `<today>` | ` \t\r\n ` |
| invalid date | main/47608 | `<TOKEN>` | `record_declaration` | `2026-02-30` | `Д-INVALID-DATE` |
| future | main/47609 | `<TOKEN>` | `record_declaration` | `<tomorrow>` | `Д-FUTURE` |
| earlier than PTO | main/47610 | `<TOKEN>` | `record_declaration` | `<earlier>` | `Д-EARLIER-THAN-PTO` |
| out of scope | main/47611 | `<TOKEN>` | `record_declaration` | `<today>` | `Д-OUTSIDE` |
| non-working | main/47612 | `<TOKEN>` | `record_declaration` | `<today>` | `Д-NONWORKING` |
| deactivated | cdec_x_…/47613 | prior `<TOKEN>` | `record_declaration` | `<today>` | `Д-DEACTIVATED` |
| concurrent A | main/47614 | actor-7701 `<TOKEN>` | `record_declaration` | `<today>` | `Д-CONCURRENT-A` |
| concurrent B | main/47614 | actor-7702 `<TOKEN>` | `record_declaration` | `<today>` | `Д-CONCURRENT-B` |
| runtime DDL | cdec_d_…/47615 | `<TOKEN>` | `record_declaration` | `<today>` | `Д-MISSING-OBJECT` |
| unknown action | main/47616 | `<TOKEN>` | `not_a_completion_action` | `2026-02-30` | ` \t\r\n ` |
| bad CSRF | cdec_c_…/47617 | `<BAD64>` | `record_declaration` | `<today>` | `Д-BAD-CSRF` |

Raw bodies are produced from these values with RFC 3986 form percent-encoding;
the parent independently decodes captured bytes back to this ordered matrix and
checks exact `Content-Length` before sending. No verifier result is used to
derive an operand.

## First declaration fact is accepted

- **GIVEN** case/object `7601/47601`, exact progress 85, exact PTO prerequisite,
  no declaration and authenticated active actor `7701`
- **WHEN** actor POSTs decoded form values
  `csrfToken=<server token>`, `action=record_declaration`,
  `declarationDate=<today>`,
  `declarationDetails="  ЕАЭС N RU Д-RU.РА01.А.12345/26  "`
- **THEN** response is `303`, exact `Location` is
  `/pilot/objects/47601#completion`, `Cache-Control` contains `no-store` and body
  is empty
- **AND** exactly one declaration exists with case `7601`, type `declaration`,
  date `<today>`, exact trimmed details
  `ЕАЭС N RU Д-RU.РА01.А.12345/26`, bounded `recorded_at` and actor `7701`
- **AND** PTO, case/order/checklist/auth/decoy facts are byte-equivalent and no
  process event/task/legacy mutation appears
- **AND** the pre-POST object card lacks `Документы приняты` and
  `Работы завершены · 100%`, while a subsequent authenticated object-card GET
  renders
  `Документы приняты`, `Работы завершены` and `100%`; those projection strings
  are observed `PILOT_ONLY`, not approved terminal semantics.

Stable milestone:

`COMPLETION_DECLARATION accepted status=303 location=/pilot/objects/47601#completion fact=1 date=<TODAY> details_sha256=77623964ef589bde99724d155e316f98af4abe5faac400edc33635cefdfa4220 actor=7701 pto=preserved projection=100`

## Sequential replay and validation precedence

After accepted state above, parent fingerprints both completion rows.

- Exact replay by 7701 returns `409`, exact body
  `Декларация уже зафиксирована.` plus LF and zero mutation.
- Changed replay uses `<tomorrow>` and whitespace-only details; duplicate
  precedence still returns the same `409`, not payload `422`, preserving both
  facts byte-for-byte.

Isolated ordering fixtures then prove:

1. `7602/47602`: progress 84, no PTO, exact matrix action/date/details → `409`, exact body
   `Сначала завершите монтажные работы до 85%.` plus LF.
2. `7603/47603`: progress 85, no PTO, exact matrix invalid date/blank details → `409`, exact
   body `Сначала зафиксируйте дату акта ПТО.` plus LF.
3. Exact case/object `7616/47616` with progress 85/PTO and
   `action=not_a_completion_action` → `422`, exact body
   `Неизвестное действие.` plus LF.

Each error has `Content-Type: text/plain; charset=UTF-8`,
`Cache-Control: no-store`, zero declaration mutation and unchanged prerequisite
fingerprints.

Stable milestone:

`COMPLETION_DECLARATION ordering replay=409 changed=409 below=409 missing_pto=409 unknown=422 mutations=0`

## Exact trim, Unicode and date boundaries

Workers have preflighted UTF-8 internal encoding. Each accepted scenario uses a
fresh case/PTO and each rejected scenario fingerprints zero declaration before
POST.

### Outer trim preserves internal bytes

Case 7604 raw details are exact bytes represented by JSON string
`" \t\n  Номер  42 / строка\nвторая  \r\n "`: 52 UTF-8 bytes, SHA-256
`750e44aaabce94517a38fa3d6f706937768674601d8c9bf522018a0167385a97`.
Accepted persisted details are exact
`"Номер  42 / строка\nвторая"`: 42 bytes, SHA-256
`67289a252f62f2cd73498b045dc6e78b8fa217cda6c41c1c9ce2a77dfeca5f80`.
Date is `<today>`; response is `303`.

### 500/501 Unicode-character boundary

- Case 7605 details are exactly `Ж` repeated 500: UTF-8 bytes `1000`, SHA-256
  `0f7ae7e11d5c9a762427ba0d99da58c0f67320bda33f57689a09b0637402d441`;
  response `303`, stored bytes/hash identical.
- Case 7606 details are exactly `Ж` repeated 501: UTF-8 bytes `1002`, SHA-256
  `efd2485bd6e4f7606f30142acd6586aaf2f3a46a4e97642b3190fbde0e559020`;
  response `422`, body `Укажите дату и реквизиты декларации.` plus LF, zero
  declaration.

### Empty and date rejection

- Case 7607 details exact `" \t\r\n "` trim to empty; valid `<today>` returns the
  same `422` and zero declaration.
- Case 7608 uses `declarationDate=2026-02-30`, details `Д-INVALID-DATE`; returns
  the same `422` and zero declaration.
- Case 7609 uses `<tomorrow>`, details `Д-FUTURE`; returns the same `422` and zero
  declaration.

### Missing lower/relative bound

Case 7610 has PTO date `<today>` but posts declaration date `<earlier>` and
details `Д-EARLIER-THAN-PTO`. Pilot returns `303` and persists `<earlier>`.
This missing ordering is a `PILOT_ONLY` risk, not desired behavior.

Stable milestone:

`COMPLETION_DECLARATION payload trim=303 unicode500=303 unicode501=422 blank=422 invalid=422 future=422 earlier_than_pto=303`

## Authentication and broad process admission

1. In isolated `cdec_c_…`, with completion table absent, active 7701 sends the
   exact matrix form with `<BAD64>` different from the server token:
   `403`, body `Недопустимый запрос.` plus LF; table remains absent.
2. In isolated `cdec_x_…`, user 7703 authenticates while active, retains
   cookie/token, then status is changed to `0`. Completion table has never
   existed in this probe; its exact matrix POST to object 47613 returns
   `303`, exact `Location: /pilot/login`, `Cache-Control: no-store`; table stays
   absent and no completion-owned state changes.
3. Active out-of-scope 7702 POSTs valid declaration to 7611/47611: `303`, one
   declaration with actor 7702 despite no capability/role/assignment.
4. Active 7701 POSTs valid declaration to unopened 7612/47612: `303`, one
   declaration despite state `assignment_order_prepared` and SQL NULL opening
   fields.

Stable milestone:

`COMPLETION_DECLARATION admission csrf=403 deactivated=303:/pilot/login outside=303 nonworking=303 accepted_facts=2`

Accepted gaps are security findings only.

## Missing object triggers request-time DDL

- **GIVEN** in isolated `cdec_d_…`, active 7701 has real cookie/token, object 47615
  has no case and completion table is absent
- **WHEN** it POSTs the exact matrix declaration fields
- **THEN** response is `404`, body `Объект не найден.` plus LF
- **AND** completion table changes absent→present and contains zero rows
- **AND** no other fixture/decoy state changes.

Stable milestone:

`COMPLETION_DECLARATION runtime_ddl status=404 table_before=absent table_after=present rows=0`

Only table existence/row count is asserted; schema details are not derived from
the implementation under test. Runtime DDL remains forbidden target debt.

## Two concurrent declarations have one winner

Case/object `7614/47614` has progress 85, exact PTO and no declaration. Actors
7701 and 7702 use separately authenticated sessions/tokens. Both forms use date
`<today>`; details are respectively `Д-CONCURRENT-A` and `Д-CONCURRENT-B`.

- **WHEN** clients served by verified different worker PIDs release at one
  parent barrier
- **THEN** unordered statuses are exactly `{303,409}`
- **AND** 303 has exact completion redirect, 409 has duplicate body plus LF
- **AND** exactly one declaration persists with submitted details/actor pair
  either `{Д-CONCURRENT-A,7701}` or `{Д-CONCURRENT-B,7702}`, bounded timestamp
  and `<today>`
- **AND** PTO and all non-declaration state remain byte-equivalent; no loser
  partial row exists.

Stable milestone:

`COMPLETION_DECLARATION concurrent statuses=303,409 facts=1 winner=<7701|7702> loser=409 pto=preserved`

One-winner locking is current oracle evidence. Target idempotency, correction and
separation of duties remain unresolved. Concurrent first PTO/declaration is not
part of this slice.

## Isolation, anti-fake and failure classification

- Setup validates DB, PHP extensions/options, unique prefix/port, router
  readiness, at least two workers and shared-session safety before assertions.
  Failure prints `SETUP_FAILURE completion_declaration <reason>` and exits a
  distinct nonzero status.
- Behavior mismatch prints
  `ASSERTION_FAILURE CHARACTERIZE-COMPLETION-DECLARATION-RECORDING-001 <scenario>`;
  aggregate failure of a previously green verifier is a regression.
- Parent deadlines terminate/reap all clients/workers. Cleanup uses a bounded
  separate DB connection, removes only owned prefixed tables and exact owned
  session files after server shutdown, and reports cleanup failure without
  hiding the primary result.
- Milestones emit only after access/PID evidence, response assertions and raw DB
  fingerprints pass. Echo-only or preseeded declaration implementations fail.
- Normalization is limited to DB prefix, port/cookie/session ids, request nonces,
  PIDs, auto-increment ids and live date/time tokens. Statuses, routes, case/
  object/actor ids, details bytes/hashes and counts are never normalized.
- Verifier runs twice sequentially and once concurrently with another invocation;
  normalized transcripts match, owned tables/session files/processes are absent,
  and unrelated DB/session decoys remain byte-equivalent.

## Authorization, audit and target contrast

Current audit is only actor and whole-second live timestamp stored in the fact.
No operation id, structured declaration number/issuer/status, source document,
file/hash, reason, revision, process event or correction link exists. Any active
pilot user can mutate any qualifying case; same-person PTO/declaration is not
prevented. These are migration risks.

Future owner candidate `InstallationCompletion` and seam candidate
`recordDeclaration` are not approved/implemented here. Target
`COMPLETION-DECLARATION-001`, canonical completion schema and declaration-driven
OTIZ remain `NEEDS_GRILL`. PTO recording and concurrent PTO/declaration ordering
remain separate behaviors.

## Done definition

This slice is done only when:

1. owner explicitly approves this v0.1 as a `PILOT_ONLY` Gate 1 oracle;
2. minimal accepted HTTP RED is captured and independently test-reviewed
   `APPROVED`;
3. minimal harness makes only that reviewed test GREEN without production pilot
   changes;
4. expanded matrix receives its own RED and a different fresh independent test
   approval before GREEN;
5. focused twice/concurrent isolation, canonical characterization, regression,
   architecture-check and diff-check pass;
6. inventory/backlog retain all target semantics under GRILL;
7. a separately tasked fresh code reviewer records `APPROVED` under
   `reviews/code/`.
