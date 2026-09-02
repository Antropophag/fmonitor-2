# CHARACTERIZE-COMPLETION-PTO-RECORDING-001 v0.1

Status: `DRAFT / OWNER_APPROVAL_REQUIRED` for Gate 1. This is a strictly
`PILOT_ONLY` characterization of the current first `record_pto` mutation. It
does not approve the 85/15 progress model, declaration as the last 15%, broad
active-user authorization, absence of evidence, duplicate-as-conflict,
correction policy, request-time DDL or any target completion seam.

Technical consistency: `READY_FOR_OWNER_REVIEW` on 2026-09-01 after fresh
independent rereview `completion_pto_gate1_rereview_v35`. Technical review is
not owner approval.

## Product contrast, actor and intent

Product evidence says an Act of PTO is a fact that blocks ordinary assignment
and opening commands, and that every fact must have source, date, responsible
actor and basis. It does not approve the rapid-pilot rule that 41 hard-coded
items produce 85% and PTO plus declaration produce 100%. GRILL-001 questions
2–3 still own target threshold/documents, authorization and correction.

The characterized intent is narrower: an authenticated rapid-pilot user submits
one date for the first `pto_act` fact of a selected installation case. The fixed
fictional primary actor is active pilot user `7601`, `Анна Петрова`, login
`pto.recorder@shlz.ru`. Out-of-scope actor `7602`, `Олег Смирнов`, login
`pto.outside@shlz.ru`, is also active but has no role, capability or assignment
connected to the fixture object. Deactivated actor `7603`, `Ирина Волкова`,
login `pto.disabled@shlz.ru`, starts authenticated and is changed to status `0`
before the characterized POST.

## Public oracle seam

- Future verifier entry:
  `php tests/Verification/characterize_completion_pto_recording_001_test.php`.
- Every serial action SHALL pass through a production-composed loopback
  `rapid-pilot/router.php` server and a real local-auth session. The client logs
  in through `/pilot/login`, retains the server-issued cookie, obtains the
  server-created form CSRF from an authenticated page, and POSTs
  `/pilot/objects/{objectId}/completion` as
  `application/x-www-form-urlencoded`.
- Accepted/rejection requests use exact form member order `csrfToken`, `action`,
  `ptoActDate`, except the explicitly unknown-action probe. Redirect following
  is disabled so the initial status and `Location` remain observable.
- Test-created `FMONITOR_AUTH_*`, direct `RapidPilotCompletionFlow::handle`,
  direct completion-fact DML, rendered HTML, verifier stdout or response alone
  cannot substitute for the public HTTP path.
- The parent records method/route plus an unpredictable parent-known request
  nonce in per-worker access logs, and fingerprints raw DB state before/after
  each request.
- Same-case concurrency SHALL use the production PHP CLI server with
  `PHP_CLI_SERVER_WORKERS=4`; preflight SHALL prove at least two distinct worker
  PIDs served requests. Two independent clients use different authenticated
  sessions/CSRF tokens and release their POSTs at one parent barrier against the
  same prefixed DB. A single worker, one session/connection or sequential calls
  is not concurrency evidence.

## Private namespace, people and exact process fixtures

The verifier owns one unpredictable prefix matching
`completion_pto_<20 lowercase hex>_` and SHALL reject collision before setup.
It creates only private prefixed auth/process/checklist tables and never reads
or writes production/legacy rows. The prefix and session directory are removed
on success, assertion failure, child failure, signal and timeout; unrelated
prefixed decoy rows/tables remain byte-identical.

All cases use these literal values unless a scenario says otherwise:

- active user `7601`; second active user `7602`; deactivated user `7603`;
- registered assignment-order version `1`, control engineer `7699`, no
  assignment or capability for users `7601`/`7602`;
- checklist operations use actor `7699`, distinct client operation ids and
  `operation_type=item_completed`; only `installation_case_id`, `item_id` and
  type affect current progress, while all other required columns receive fixed
  valid literals and are fingerprinted;
- no initial completion facts;
- completion table is setup-owned except in the two explicitly absent-table
  scenarios.

| Purpose | Case | Object | Order | Process state | Initial progress |
|---|---:|---:|---:|---|---:|
| accepted/replay | 7501 | 47501 | 8501 | `working` | 85 |
| below threshold/order probe | 7502 | 47502 | 8502 | `working` | 84 |
| invalid/future dates | 7503 | 47503 | 8503 | `working` | 85 |
| active user outside scope | 7504 | 47504 | 8504 | `working` | 85 |
| non-working admission | 7505 | 47505 | 8505 | `assignment_order_prepared` | 85 |
| missing object + DDL | none | 47506 | none | none | none |
| deactivated session | 7507 | 47507 | 8507 | `working` | 85 |
| concurrent commands | 7508 | 47508 | 8508 | `working` | 85 |

Case 7505 has latest order 8505 status `registered` but `actual_start_date`,
`opened_at` and `opened_by_user_id` SQL `NULL`. This creates a coherent unopened
case rather than inventing a process state.

## Independently fixed pilot progress operands

Expected sums below are literal inputs fixed before RED; the future verifier
MUST NOT call production progress/weight code to compute expectations.

| Item ids | Corresponding literal weights | Sum |
|---|---|---:|
| `28,29,30,31,32,33,34,35,36,37,38,39,40,41,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27` | `2,2,2,2,1,1,2,1,2,3,3,3,3,2,2,2,1,2,1,1,2,5,1,1,3,2,2,1,2,4,4,3,3,3,2,3,2,1,1,1,1` | 85 |
| same list without item `32` | same weights without its `1` | 84 |

One duplicate `item_completed` row for item `28` is additionally seeded in the
85 fixture to prove distinct-item counting: expected progress remains `85`, not
`87`. These weights and the threshold are current-oracle operands only.

## Live Moscow clock contract

`recorded_at` is not controlled by `FMONITOR_NOW`; the pilot reads live
`Europe/Moscow` time and formats `DATE_ATOM` at whole-second precision.

For every date-sensitive scenario the parent samples Moscow instants
immediately before and after the request. If the samples cross a Moscow date,
that scenario discards its whole private namespace and retries in a new private
prefix with the same fixed case/object/user ids within a bounded deadline.
Otherwise:

- `today` is that shared sampled Moscow date;
- `tomorrow` is calendar `today + 1 day` in `Europe/Moscow`;
- lower bound is before-sample floored to its whole second;
- upper bound is after-sample ceiled to its whole second;
- persisted `recorded_at` must parse with offset `+03:00`, fall inclusively in
  those bounds and have date `today`;
- concrete `today`, `tomorrow` and `recorded_at` are normalized to symbolic
  tokens in the stable transcript.

No production clock seam, system-clock mutation or `libfaketime` is permitted.

## First PTO fact is accepted

- **GIVEN** case/object `7501/47501`, exact progress operands summing to 85,
  duplicate item 28, no completion fact and active authenticated actor `7601`
- **WHEN** actor POSTs exact form values
  `csrfToken=<server token>&action=record_pto&ptoActDate=<today>`
- **THEN** response is `303`, exact `Location` is
  `/pilot/objects/47501#completion`, `Cache-Control` contains `no-store` and
  response body is empty
- **AND** exactly one completion row exists with case `7501`, type `pto_act`,
  date `<today>`, `details=''`, actor `7601` and bounded Moscow `recorded_at`
- **AND** no declaration, process event, task, order, case, checklist operation
  or legacy row is inserted/updated/deleted.

Stable milestone:

`COMPLETION_PTO accepted status=303 location=/pilot/objects/47501#completion facts=1 type=pto_act date=<TODAY> actor=7601 recorded_at=<MOSCOW_NOW>`

## Sequential replay preserves the original

The parent fingerprints the complete accepted fact and all related rows.

- **WHEN** actor `7601` repeats the identical accepted form and date
- **THEN** response is `409`, exact UTF-8 body is
  `Дата акта ПТО уже зафиксирована.` plus one LF, `Cache-Control` contains
  `no-store`, and every fingerprint remains byte-identical.
- **WHEN** actor repeats with valid date `<today minus 1 day>`
- **THEN** the same `409` and body occur before changed-date validation; the
  original fact date/actor/timestamp remain byte-identical.
- **WHEN** actor repeats with future date `<tomorrow>`
- **THEN** duplicate precedence still produces the same `409` rather than date
  `422`, with zero mutation.

Stable milestone:

`COMPLETION_PTO replay exact=409 changed=409 future_changed=409 facts=1 original=preserved`

Duplicate-as-conflict and the absence of an operation id are `PILOT_ONLY`.

## Threshold, action and date rejection ordering

Each request uses its own real session/CSRF and a pre-request raw-state
fingerprint. Every rejection below adds zero completion facts and leaves all
case/order/checklist/auth/decoy rows byte-identical, except the explicitly
observed completion-table creation scenario.

1. Case/object `7502/47502`, progress 84, valid `record_pto`, `<today>` returns
   `409` and exact body `Сначала завершите монтажные работы до 85%.` plus LF.
2. Same 84 fixture with `action=not_a_completion_action` and no
   `ptoActDate` returns the same threshold `409`, proving threshold-before-action.
3. Case/object `7503/47503`, progress 85, `ptoActDate=2026-02-30` returns `422`
   and exact body `Укажите дату акта ПТО не позже сегодняшней.` plus LF.
4. Fresh reset of 7503 with progress 85 and `ptoActDate=<tomorrow>` returns the
   same `422` and body.
5. Fresh reset of 7503 with progress 85, valid date and
   `action=not_a_completion_action` returns `422` and exact body
   `Неизвестное действие.` plus LF.

Stable milestone:

`COMPLETION_PTO validation below=409 unknown_below=409 invalid_date=422 future=422 unknown_at_85=422 mutations=0`

All threshold/date/action values and Russian messages are current pilot oracle,
not target UX acceptance.

## Authentication, CSRF and broad admission

1. Active actor `7601` POSTs to object 47504 with an exact 64-character token
   consisting of `0` that differs from the server token: response `403`, body
   `Недопустимый запрос.` plus LF, zero completion facts and no completion-table
   creation when the table was absent.
2. Actor `7603` first authenticates while active, retains the issued cookie,
   then its user status changes to `0`. With completion table absent it POSTs
   object 47507 using its prior form token: LocalAuth returns `303`, exact
   `Location: /pilot/login`, `Cache-Control: no-store`; completion table remains
   absent, no fact appears and all completion-owned state is unchanged.
3. Active out-of-scope actor `7602`, with no relevant role/capability/assignment,
   POSTs valid PTO `<today>` to `7504/47504`: response `303`, exactly one fact
   persists with actor `7602`.
4. Active actor `7601` POSTs valid PTO `<today>` to coherent unopened case
   `7505/47505`: response `303`, exactly one fact persists despite non-working
   state and null opening fields.

Stable milestone:

`COMPLETION_PTO admission csrf=403 deactivated=303:/pilot/login outside=303 nonworking=303 facts=2`

The two accepted admission paths are security findings, not target policy.

## Missing object triggers runtime DDL

- **GIVEN** active actor `7601` has a real session/CSRF, object id `47506` has no
  installation case and the private completion table is absent
- **WHEN** the actor POSTs valid `record_pto` and `<today>`
- **THEN** response is `404`, exact body `Объект не найден.` plus LF
- **AND** `fm2_pilot_completion_facts` now exists and contains zero rows
- **AND** no other raw state changes.

Stable milestone:

`COMPLETION_PTO runtime_ddl status=404 table_before=absent table_after=present facts=0`

This proves reachable creation-before-case-resolution only. The source's
internal DDL-before-actor order is not claimed as a reachable router scenario.
Runtime DDL remains forbidden target architecture debt.

## Two real concurrent PTO commands have one winner

Case/object `7508/47508` has exact progress 85 and no PTO fact. Actor `7601` and
out-of-scope active actor `7602` each use a distinct authenticated session and
valid token. Both submit `<today>` from verified distinct PHP worker PIDs after
one parent barrier.

- **WHEN** both POSTs are released concurrently
- **THEN** unordered response statuses are exactly `{303,409}`
- **AND** the 303 response redirects to `/pilot/objects/47508#completion`
- **AND** the 409 response body is exact duplicate text plus LF
- **AND** exactly one `pto_act` persists, with actor in `{7601,7602}`, submitted
  date `<today>`, empty details and a bounded timestamp
- **AND** loser identity is not predetermined, no partial/duplicate fact exists,
  and all non-completion rows remain byte-identical.

Stable milestone:

`COMPLETION_PTO concurrent statuses=303,409 facts=1 winner=<7601|7602> loser=409`

Serialization by case lock and one-winner outcome are current oracle evidence;
target idempotency/correction policy remains unresolved.

## Isolation, anti-fake and failure classification

- Fixture setup validates DB connectivity, required PHP extensions, router
  startup, multi-worker availability, writable private session storage and
  collision-free namespace before behavior assertions. Failure prints
  `SETUP_FAILURE completion_pto <reason>` and exits distinct nonzero status.
- A behavior mismatch prints
  `ASSERTION_FAILURE CHARACTERIZE-COMPLETION-PTO-RECORDING-001 <scenario>`.
  Failure of this previously green canonical verifier is reported by the
  aggregate as regression, not setup.
- Parent-enforced deadlines terminate/reap every client and server worker;
  teardown uses a separate bounded connection and reports cleanup failure
  without masking the primary result.
- Every milestone is emitted only after raw HTTP access evidence and DB
  fingerprints pass. A verifier that prints expected text without the requests,
  sessions, workers and facts fails sensitivity controls.
- The stable transcript normalizes only unpredictable prefix, ports, session
  ids, request nonces, worker PIDs, auto-increment ids and live date/time tokens.
  Statuses, routes, actors, case/object ids, fact bytes and counts are not
  normalized.
- The verifier SHALL run twice sequentially and once concurrently with another
  invocation; all runs produce the same normalized transcript and leave no
  owned table, session directory or process.

## Authorization, audit and target contrast

Current audit is only the completion row's actor and live timestamp. There is no
operation id, source document/evidence reference, reason, process event,
revision or correction link. Any active pilot user can mutate any qualifying
case, while a deactivated session is stopped by local auth.

The future owner candidate is `InstallationCompletion` and the candidate public
seam is `recordPtoAct`; neither is approved or implemented here. Declaration,
canonical completion schema, legacy/assignment-order/OTIZ reconciliation,
target authorization and correction/supersession stay in `NEEDS_GRILL`.

## Done definition

This slice is done only when:

1. this v0.1 receives explicit owner Gate 1 approval;
2. accepted HTTP RED is captured and independently test-reviewed `APPROVED`;
3. minimal harness makes that reviewed test GREEN without production behavior
   changes;
4. the expanded matrix receives a new RED and fresh independent test approval
   before GREEN;
5. focused/canonical characterization, architecture-check, regression and
   isolation runs pass;
6. inventory/backlog preserve every target ambiguity as `NEEDS_GRILL`;
7. a separately tasked fresh code reviewer records `APPROVED` under
   `reviews/code/`.
