# CHARACTERIZE-INSPECTION-ATTRIBUTION-CORRECTION-001 v0.1

Status: `DRAFT / OWNER_APPROVAL_REQUIRED` for Gate 1. This is a strictly
`PILOT_ONLY` characterization of current `item_installers_changed`. It does not
approve this command as product correction, per-item actual-installer editing,
target authorization, payload-unaware replay, stale acceptance, two-success
concurrency, last-write projection, request-time DDL or read-time backfill.

Technical consistency: `READY_FOR_OWNER_REVIEW` on 2026-09-01 after fresh
independent rereview `attribution_correction_gate1_rereview_v21`. This records
technical review only and is not owner approval.

## Product contrast, actor and intent

The approved fast-pilot contract defines correction as
`completion_retracted`: reference the original fact, require a short reason,
make the item incomplete and reopen a completed section. It explicitly defers
assigning actual installers to individual items. Current
`item_installers_changed` instead appends a replacement for visible attribution,
without reason/reference and without changing completion/progress. This spec
records that contradiction; it does not resolve it.

A discovery/test agent needs deterministic evidence before any target
`InspectionRecording::changeItemAttribution` decision. The fixed fictional actor
is active user `7302`, `Мария Соколова`, authenticated as
`engineer.attribution@example.test`, with no active role granting
`checklist.edit`. Each admitted object card identifies `7302` as current control
engineer. This proves one current pilot branch only, not target authorization.

## Public oracle seam

- Stable future verifier entry point:
  `php tests/Verification/characterize_inspection_attribution_correction_001_test.php`.
- Every serial action SHALL use a production-composed loopback HTTP server.
  Client first GETs `/pilot/objects/{objectId}/checklist`, retains the real
  session cookie and parses the server-created CSRF token, then POSTs compact
  JSON to `/pilot/objects/{objectId}/checklist/operations`.
- POST SHALL carry the exact cookie, matching `Origin`,
  `Sec-Fetch-Site: same-origin`,
  `Content-Type: application/json; charset=UTF-8`, exact `Content-Length` and
  `X-FM2-CSRF`. Test-created session/token is not evidence.
- Concurrent actions SHALL use two separately started production-composed
  loopback servers, clients, sessions/tokens and DB connections against one
  private generation. One server/connection or sequential calls cannot satisfy
  concurrency evidence.
- Request logs record method, route and unpredictable parent-known nonce.
  Direct `ChecklistSync::accept`, correction DML, verifier stdout or response
  alone cannot substitute for public HTTP execution.

## Fixed time, template, people and primary process

All expected values are literals fixed before RED and SHALL NOT be derived from
future verifier output or production checklist calculations.

- Server clock: `2026-09-01T14:00:00+03:00`.
- Device installation id:
  `20202020-2020-4020-8020-202020202020`.
- Template snapshot id `9201`, version
  `characterize-attribution-correction-v1`,
  `valid_from=2026-08-01 00:00:00`, association
  `effective_at=2026-08-02 00:00:00`.
- Exact template payload bytes:
  `{"definitions":[{"id":28,"share":10},{"id":29,"share":10}]}`.
- Independently fixed payload SHA-256:
  `5b7ff8f8158ea2716964fe7766d7aabfaae52b937f70dbcedf8a1663416aff1a`.
- Installer `1042`: `Иванов Иван Иванович`; installer `2048`:
  `Петров Пётр Петрович`; installer `4096`: `Сидоров Сергей Сергеевич`.
  Each has position `Электромеханик по лифтам`, status `employed`, SQL `NULL`
  dismissal and source update `2026-08-27T18:15:00+03:00`.
- Primary case `7201`, object `47201`, state `working`, registered order `8201`
  version `1`, engineer `7302`, crew `[1042,2048]`, fixed template above and
  revision `1`.

Primary setup contains immutable prior operation P:

- client id `10101010-1010-4010-8010-101010101010`, device id above;
- `item_completed`, section `1`, item `28`, actor `7302`;
- device time `2026-09-01T09:50:00+00:00`, received time
  `2026-09-01T12:50:00+03:00`, base `0`, accepted revision `1`;
- payload `{"installerTabIds":["1042"]}`, fixed template triple;
- one installer snapshot for `1042` with the literal personnel values and
  `assignment_source=completion`.

Setup P is prerequisite evidence only. Its exact operation row, installer row
and revision row are byte-fingerprinted before the first POST. It is not passed
off as execution of the characterized command.

## Literal correction envelopes

Compact JSON member order is `clientOperationId`, `deviceInstallationId`,
`type`, `deviceTime`, `baseRevision`, `sectionId`, `itemId`,
`installerTabIds`.

### Operation A — visible attribution replacement

- client id `30303030-3030-4030-8030-303030303030`;
- `item_installers_changed`, device time `2026-09-01T10:00:00+00:00`;
- base `1`, section `1`, item `28`, installers `["2048"]`.

Exact body:

```json
{"clientOperationId":"30303030-3030-4030-8030-303030303030","deviceInstallationId":"20202020-2020-4020-8020-202020202020","type":"item_installers_changed","deviceTime":"2026-09-01T10:00:00+00:00","baseRevision":1,"sectionId":1,"itemId":28,"installerTabIds":["2048"]}
```

### Operation B — lower-stale replacement

- client id `40404040-4040-4040-8040-404040404040`;
- device time `2026-09-01T10:01:00+00:00`, base `1`, section `1`, item `28`,
  installers `["1042"]`.

### Operation C — ahead conflict

- client id `50505050-5050-4050-8050-505050505050`;
- device time `2026-09-01T10:02:00+00:00`, base `4`, section `1`, item `28`,
  installers `["2048"]`.

## Accepted replacement and immutable original

- **GIVEN** the primary fixture and real session/CSRF obtained by GET
- **WHEN** actor `7302` POSTs exact A
- **THEN** HTTP status is `200`, result `accepted`, result/projection revision
  `2`
- **AND** exactly two operation rows exist: byte-identical P plus A with case
  `7201`, fixed ids/type/section/item/actor/times, base `1`, accepted revision
  `2`, raw payload `{"installerTabIds":["2048"]}` and fixed template triple
- **AND** P's `1042` installer row remains byte-identical and A has exactly one
  `2048` literal snapshot with `assignment_source=correction`
- **AND** revision is exactly
  `(7201,2,2026-09-01T14:00:00+03:00)`
- **AND** projection item `28` exposes A/2048 while current crew remains
  `[1042,2048]`, with no reason, referenced completion or retraction fact
- **AND** raw existence of P continues to satisfy completed-item progress;
  no original row is updated/deleted and no process event is added.

Stable milestone:

`ATTRIBUTION_CORRECTION accepted status=200 result=accepted revision=2 original=preserved correction_installers=2048`

This is a visible attribution replacement only. It SHALL NOT be described as
approved `completion_retracted` behavior.

## Exact and changed-payload replay

- **WHEN** exact A is POSTed again with the same valid session/token
- **THEN** response is `200`, result `duplicate`, revision `2`, and every raw
  operation/installer/revision/projection fact is byte-equivalent.
- **WHEN** A's client id is reused in an otherwise-valid body with item `29`,
  installers `["1042"]` and base `2`
- **THEN** current pilot still returns `200`, `duplicate`, revision `2` before
  changed item/payload semantics are validated
- **AND** complete primary state remains byte-identical.

Stable milestone:

`ATTRIBUTION_CORRECTION replay exact=duplicate changed=duplicate revision=2 mutations=0`

Payload-unaware duplicate precedence is `PILOT_ONLY`.

## Lower-stale acceptance and ahead conflict

- **GIVEN** revision `2` after A
- **WHEN** valid B submits base `1`
- **THEN** response is `200`, accepted revision `3`; B and its `1042`
  correction snapshot append while P/A remain byte-identical
- **AND** projection item `28` now exposes B/1042 without retracting P.
- **GIVEN** revision `3`
- **WHEN** C submits ahead base `4`
- **THEN** response is `409`, result `conflict`, current revision `3`
- **AND** operation/installer/revision/projection facts remain byte-identical.

Stable milestone:

`ATTRIBUTION_CORRECTION revisions stale=accepted:3 ahead=conflict:3 final=3`

## Stored snapshots survive crew and workforce drift

- **GIVEN** P/A/B and revision `3`
- **WHEN** fixture registers order `8202`, version `2`, same engineer and only
  crew `4096`, then changes workforce names/status source values for `1042` and
  `2048` and performs a real checklist GET
- **THEN** every stored P/A/B operation and installer snapshot remains
  byte-identical
- **AND** current crew contains only `4096`; latest item attribution remains
  B/1042 with `currentlyAssigned=false` and its original snapshot values.

Stable milestone:

`ATTRIBUTION_CORRECTION drift stored=preserved latest=1042 current=4096 currently_assigned=no`

Current projection overlay rules are audited separately from immutable stored
snapshots and remain `PILOT_ONLY`.

## Two real same-base replacements both succeed

Independent case `7202`, object `47202`, order `8203`, actor `7302`, crew
`[1042,2048]`, valid template and revision `1` contain a byte-fingerprinted
prior completion for section 1/item 28 with installer 1042.

- Operation D: id `60606060-6060-4060-8060-606060606060`, device time
  `2026-09-01T10:03:00+00:00`, base `1`, select `1042`.
- Operation E: id `70707070-7070-4070-8070-707070707070`, device time
  `2026-09-01T10:04:00+00:00`, base `1`, select `2048`.

- **WHEN** two loopback servers/sessions release D/E POST clients at one parent
  barrier
- **THEN** both responses are `200`/`accepted`, unordered revisions `{2,3}`
- **AND** both complete correction snapshots exist, prior completion remains
  byte-identical, final revision is `3`, and projection exposes whichever
  operation owns accepted revision `3` with no partial state.

Stable milestone:

`ATTRIBUTION_CORRECTION concurrent statuses=200,200 results=accepted,accepted revisions=2,3 final=3 latest=revision-3`

Two-success/last-write behavior is `PILOT_ONLY`; target one-winner and
supersession policy remain undecided.

## Eight isolated rejection boundaries

Each scenario owns a dedicated object/case/order and GET session. Except where
the violated condition requires otherwise, it has working/open card, actor
`7302`, valid template, crew `[1042,2048]`, revision `1` and a fully attributed
prior completion for section 1/item 28. Parent snapshots all facts immediately
after GET and before POST.

1. Object/case `47203/7203`: no earlier completion for item 28 (revision 1 is an
   unrelated completion for item 29); correction id
   `80808080-8080-4080-8080-808080808080` returns `422`.
2. `47204/7204`: submits section 1/item 1, id
   `90909090-9090-4090-8090-909090909090`; returns `422`.
3. `47205/7205`: installer list `[]`, id
   `a0a0a0a0-a0a0-40a0-80a0-a0a0a0a0a0a0`; returns `422`.
4. `47206/7206`: installer list `["1042",1042]`, id
   `b0b0b0b0-b0b0-40b0-80b0-b0b0b0b0b0b0`; returns `422`.
5. `47207/7207`: current crew excludes submitted `4096`, id
   `c0c0c0c0-c0c0-40c0-80c0-c0c0c0c0c0c0`; returns `422`.
6. `47208/7208`: case/card is non-working/not opened, id
   `d0d0d0d0-d0d0-40d0-80d0-d0d0d0d0d0d0`; POST admission returns `403`.
7. `47209/7209`: missing template association, id
   `e0e0e0e0-e0e0-40e0-80e0-e0e0e0e0e0e0`; returns `422`.
8. `47210/7210`: active actor `7399` has neither broad editor capability nor
   current-engineer assignment; it obtains its own page session/CSRF, then id
   `f0f0f0f0-f0f0-40f0-80f0-f0f0f0f0f0f0` returns `403`.

For every rejection, POST adds zero operation/installer rows and does not
advance revision. Pre-existing completion, case/order/template/workforce,
schema fingerprints, decoy and unrelated facts remain byte-identical. Required
GET may initialize an absent revision row or backfill legacy attribution; these
are captured in the pre-POST baseline, never counted as rejection mutation.
Exact Russian messages and validation ordering are not promoted.

Stable milestone:

`ATTRIBUTION_CORRECTION rejections statuses=422,422,422,422,422,403,422,403 corrections=0 revision_advances=0`

## Legacy projection manufactures attribution on read

Independent case/object `7211/47211`, order `8211`, current crew `2048` contains
legacy operation `abababab-abab-4bab-8bab-abababababab`: `item_completed`,
section 1/item 28, actor 7302, device time
`2026-08-20T09:00:00+03:00`, received time
`2026-08-20T09:01:00+03:00`, base 0/revision 1, payload `{}`, SQL `NULL`
template fields, revision 1 and no installer rows.

- **WHEN** admitted actor performs first real checklist GET
- **THEN** projection inserts exactly one current-crew `2048` snapshot with
  `assignment_source=pilot_backfill_current_order`
- **AND** legacy operation/revision remain byte-identical
- **WHEN** page is read again
- **THEN** no second installer row is added.

Stable milestone:

`ATTRIBUTION_CORRECTION backfill first=1 second=0 source=pilot_backfill_current_order`

This unsafe read mutation cannot satisfy target append-only/read-only rules.

## Isolation and anti-fake contract

1. Caller SHALL supply
   `FMONITOR_ATTRIBUTION_CORRECTION_VERIFY_RUN_TOKEN` as exactly 12 lowercase
   hex characters and repository-owned
   `FMONITOR_ATTRIBUTION_CORRECTION_VERIFY_ARTIFACT_ROOT`. Missing, malformed,
   symlinked, non-directory or out-of-bound values are `SETUP_FAILURE`; `/tmp`,
   home root and fallback locations are forbidden.
2. Run owns process SQL prefix `ciac_<token>_` (18 ASCII bytes), legacy prefix
   `ciac_<token>_legacy_`, and only these 28 exact basenames:
   `fm2_pilot_generation_sentinel`, `fm2_installation_cases`,
   `fm2_assignment_orders`, `fm2_order_installers`, `fm2_order_artifacts`,
   `fm2_process_events`, `fm2_process_tasks`,
   `fm2_process_user_capabilities`, `fm2_workforce_catalog`,
   `fm2_workforce_observations`, `fm2_workforce_sync_runs`,
   `fm2_workforce_sync_metadata`, `fm2_pilot_users`, `fm2_pilot_roles`,
   `fm2_pilot_role_permissions`, `fm2_pilot_user_roles`,
   `fm2_pilot_auth_credentials`, `fm2_pilot_invitations`,
   `fm2_pilot_user_role_events`, `fm2_pilot_auth_attempts`,
   `fm2_pilot_user_status_events`, `fm2_checklist_template_snapshots`,
   `fm2_checklist_template_associations`, `fm2_checklist_revisions`,
   `fm2_checklist_operations`, `fm2_checklist_operation_installers`,
   `fm2_checklist_photos`, and legacy-prefix-only `fm_maintable`. Process prefix
   applies to first 27 and legacy prefix only to last. Longest process basename
   is 35 bytes, yielding a 53-byte table name below MariaDB 64 and compatible
   with the future 28-byte full-catalogue prefix cap.
3. Filesystem ownership is exact artifact child
   `attribution-correction-<token>` and direct directories `sessions`,
   `barrier`; direct files `active-manifest.json`, `router.php`,
   `requests-serial.jsonl`, `requests-concurrency-a.jsonl`,
   `requests-concurrency-b.jsonl`, `serial.stdout`, `serial.stderr`,
   `concurrency-a.stdout`, `concurrency-a.stderr`, `concurrency-b.stdout`,
   `concurrency-b.stderr`; and barrier members `ready-d`, `ready-e`, `go`,
   `result-d.json`, `result-e.json`. PHP session files are allowed only as direct
   regular files in exact `sessions`; no other descendant or symlink is owned.
4. Process ownership is limited to handles returned for serial loopback server,
   concurrency servers A/B and POST clients D/E. Parent records each handle and
   stops/reaps only those five; name-based discovery is forbidden.
5. Before mutation parent refuses every occupied exact table/artifact child and
   never inspects, repairs, truncates, renames, reuses or drops occupied state.
   Wildcard discovery/cleanup is forbidden.
6. Setup owns exact fixture DDL/rows, precreates final checklist shape and never
   invokes `ensureSchema` as setup. HTTP still executes current runtime DDL;
   structural fingerprints SHALL remain unchanged.
7. Parent creates a fixed ambient artifact decoy and fingerprints unrelated
   process-prefixed tables. Both remain byte-identical.
8. Parent independently audits request logs, responses, raw rows, projection and
   schema after each action. Summaries/exit zero cannot replace evidence.
9. Every server/client has bounded readiness/connect/execution/reaping timeout.
   Per run limits are 40 HTTP requests, 20 process cases, 32 owned tables and
   1 MiB per response.
10. Cleanup removes only proven exact owned names in reverse dependency order,
    is idempotent on every exit, and proves absence. Recursive removal is
    allowed only inside the validated owned child after every descendant is
    proven in the closed set; root/siblings are never targets.
11. Meta-test runs complete verifier twice with distinct clean tokens. Normalized
    stdout is byte-identical, stderr empty, evidence complete, decoys/unrelated
    state preserved and owned state absent.

Ports, host nonces, pids, tokens, prefixes, DB/table/path names, cookie, CSRF,
SQL, credentials and stack traces SHALL NOT enter normalized stdout.

## Stable transcript

Normalized stdout SHALL contain these seven milestones followed by exact
terminal line in this order:

```text
ATTRIBUTION_CORRECTION accepted status=200 result=accepted revision=2 original=preserved correction_installers=2048
ATTRIBUTION_CORRECTION replay exact=duplicate changed=duplicate revision=2 mutations=0
ATTRIBUTION_CORRECTION revisions stale=accepted:3 ahead=conflict:3 final=3
ATTRIBUTION_CORRECTION drift stored=preserved latest=1042 current=4096 currently_assigned=no
ATTRIBUTION_CORRECTION concurrent statuses=200,200 results=accepted,accepted revisions=2,3 final=3 latest=revision-3
ATTRIBUTION_CORRECTION rejections statuses=422,422,422,422,422,403,422,403 corrections=0 revision_advances=0
ATTRIBUTION_CORRECTION backfill first=1 second=0 source=pilot_backfill_current_order
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-ATTRIBUTION-CORRECTION-001
```

Spec/test/expected-transcript hashes are pinned at applicable test reviews.
Verifier implementation hash is pinned only at Gate 5.

## Classification and exclusions

- `PRODUCT_ACCEPTED` context only: history is append-only; original facts remain;
  accepted attribution snapshots are immutable; server rechecks authorization.
- `PILOT_ONLY`, characterized: `item_installers_changed` meaning; hard-coded
  map; broad editor OR engineer; no reason/reference/retraction; continued
  completion/progress; payload-unaware replay; lower-stale acceptance; two
  same-base successes; last-write projection; GET revision initialization;
  legacy backfill; response categories and request-time DDL.
- Explicitly excluded/UNKNOWN: whether target supports separate attribution
  adjustment at all; `completion_retracted`; exact capability/current assignment;
  queued commands after reassignment; reason/reference; payload conflict;
  supersession/current projection; one-winner concurrency; correction of
  completed sections/photos; current-workforce overlay for still-assigned
  historical participants; dismissed assigned-crew acceptance; production
  schema/data; premium/payment.

## Failure classification and Gate evidence

- `SETUP_FAILURE`, exit `2`: invalid root/token/namespace; unavailable MariaDB;
  fixture/identity/assets/manifest failure; loopback/session/CSRF failure before
  intended behavior; timeout/reaping/audit/cleanup failure.
- Qualifying RED: healthy private fixture and HTTP server prove the focused
  verifier or reviewed assertion is absent/fails for intended behavior.
- `REGRESSION_FAILURE`, exit `1`: wrong result/facts/projection/mutation boundary;
  mutable original; fake/single-connection race; decoy/schema damage;
  nondeterminism, secret leak or owned-state leak.

Expected domain rejection and setup failure are never RED. First focused command:

`php tests/Verification/characterize_inspection_attribution_correction_001_test.php`

## Done definition

1. Owner explicitly approves this exact v0.1 as PILOT_ONLY characterization.
2. Fresh RED author proves the smallest accepted HTTP assertion fails.
3. Different fresh reviewer approves that test before minimal GREEN.
4. Only accepted exchange turns unchanged focused test GREEN twice.
5. Expanded replay/revision/concurrency/drift/rejection/backfill assertions first
   fail intentionally and another fresh reviewer approves them.
6. Minimal expansion turns unchanged tests GREEN twice and registers once in
   canonical characterization.
7. Relevant regressions, lint, architecture and `make verify` add no regression.
8. Target backlog/spec cannot mistake this replacement for approved
   `completion_retracted` and retains every target decision in NEEDS_GRILL.
9. Fresh code reviewer who authored neither GREEN increment records `APPROVED`
   and pins verifier hash.

Done changes no production behavior/schema and approves no target semantics.
This draft completes task 1.1 only after fresh technical consistency review;
task 1.2 and RED remain blocked on owner approval.
