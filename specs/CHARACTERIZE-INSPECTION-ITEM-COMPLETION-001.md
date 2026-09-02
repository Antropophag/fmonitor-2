# CHARACTERIZE-INSPECTION-ITEM-COMPLETION-001 v0.1

Status: `DRAFT / OWNER_APPROVAL_REQUIRED` for Gate 1. This is an explicitly
`PILOT_ONLY` executable characterization of the current rapid-pilot HTTP oracle.
It does not approve target authorization, payload-unaware replay, stale revision
acceptance, two-success concurrency, hard-coded items, request-time DDL or
projection-side historical backfill.

Technical consistency: `READY_FOR_OWNER_REVIEW` on 2026-09-01 after fresh
independent review `item_completion_gate1_final_review_v15`. This records
technical review only and is not owner approval.

## Actor and intent

A discovery/test agent needs deterministic evidence for the first post-open
engineer mutation before `item_completed` moves behind
`InspectionRecording::completeItem`. The characterized actor is fictional active
user `7301`, `Анна Волкова`, authenticated as
`engineer.item@example.test`, with no active role granting `checklist.edit`. The
current object card identifies `7301` as its control engineer, so the pilot admits
the actor through one observed assignment branch only.

This fixed admission is test precondition, not target policy. GRILL-003 is now
owner-resolved for the target: active user plus exact
`inspection.item.complete` grants all-object scope, while current assignment is
routing/audit context only. This characterization still observes the old pilot
assignment branch and does not assert that it implements the target decision.

## Public oracle seam

- Stable future verifier entry point:
  `php tests/Verification/characterize_inspection_item_completion_001_test.php`.
- Every serial behavioral action SHALL use a real production-composed loopback
  HTTP server. The verifier first performs GET
  `/pilot/objects/{objectId}/checklist`, retains the returned session cookie and
  parses the server-created checklist CSRF token, then submits same-origin JSON
  POST `/pilot/objects/{objectId}/checklist/operations`.
- POST SHALL send the exact cookie, `Origin` matching its loopback origin,
  `Sec-Fetch-Site: same-origin`, `Content-Type: application/json; charset=UTF-8`,
  exact `Content-Length` and `X-FM2-CSRF`. No test-created session or token is
  accepted as evidence.
- Concurrent actions SHALL use two separately started production-composed
  loopback server processes, two clients, two sessions/tokens and independent DB
  connections against the same private generation. A single built-in server,
  one connection or sequential calls cannot satisfy concurrency evidence.
- Request log records method, route and an unpredictable parent-known nonce for
  every dispatched request. Direct `ChecklistSync::accept`, direct operation DML,
  verifier stdout or response alone cannot substitute for HTTP execution.
- Target seam candidate `InspectionRecording::completeItem` is neither confirmed
  by this characterization nor implemented here.

## Fixed time, template, people and process facts

Expected values are literals fixed before RED and not copied from future verifier
output or recomputed with production checklist logic.

- Server clock: `2026-09-01T12:00:00+03:00`.
- Device installation id:
  `22222222-2222-4222-8222-222222222222`.
- Template snapshot id `9101`, version
  `characterize-item-completion-v1`, `valid_from=2026-08-01 00:00:00`,
  association `effective_at=2026-08-02 00:00:00`.
- Exact template payload bytes:
  `{"definitions":[{"id":28,"share":10},{"id":29,"share":10}]}`.
- Independently fixed payload SHA-256:
  `5b7ff8f8158ea2716964fe7766d7aabfaae52b937f70dbcedf8a1663416aff1a`.
- Installer `1042`: `Иванов Иван Иванович`, position
  `Электромеханик по лифтам`, status `employed`, dismissal date SQL `NULL`,
  workforce source update `2026-08-27T18:15:00+03:00`.
- Installer `2048`: `Петров Пётр Петрович`, the same literal position/status/
  dismissal/source-update values; initially not assigned.
- Primary case `7101`, object `47101`, state `working`, registered order `8101`
  version `1`, control engineer `7301`, crew only `1042`, exact operational-case
  template association above, initial checklist revision absent.

The template payload intentionally contains items 28 and 29. The current pilot
does not validate item membership from those bytes; this fixture is honest input,
not evidence that the hard-coded item map is target-approved.

## Literal operation envelopes

JSON bodies are compact and retain the following member order:
`clientOperationId`, `deviceInstallationId`, `type`, `deviceTime`,
`baseRevision`, `sectionId`, `itemId`, `installerTabIds`.

### Operation A — initial completion

- client id `11111111-1111-4111-8111-111111111111`;
- device time `2026-09-01T08:55:00+00:00`;
- type `item_completed`, base `0`, section `1`, item `28`, installers `['1042']`.

Exact body:

```json
{"clientOperationId":"11111111-1111-4111-8111-111111111111","deviceInstallationId":"22222222-2222-4222-8222-222222222222","type":"item_completed","deviceTime":"2026-09-01T08:55:00+00:00","baseRevision":0,"sectionId":1,"itemId":28,"installerTabIds":["1042"]}
```

### Operation B — lower-stale completion

- client id `33333333-3333-4333-8333-333333333333`;
- device time `2026-09-01T08:56:00+00:00`;
- type `item_completed`, base `0`, section `1`, item `29`, installers `['1042']`.

### Operation C — ahead conflict

- client id `44444444-4444-4444-8444-444444444444`;
- device time `2026-09-01T08:57:00+00:00`;
- type `item_completed`, base `3`, section `1`, item `28`, installers `['1042']`.

## Accepted completion and exact persisted facts

- **GIVEN** the primary fixture and a real session/CSRF obtained from its GET
- **WHEN** actor `7301` POSTs exact operation A
- **THEN** response status is `200`, decoded result is `accepted`, revision `1`,
  and returned projection revision is `1`
- **AND** exactly one operation row exists with case `7101`, operation/device ids,
  type/section/item/actor/device time above,
  `server_received_at=2026-09-01T12:00:00+03:00`, base `0`, accepted revision
  `1`, raw payload `{"installerTabIds":["1042"]}`, and template
  `9101`/`characterize-item-completion-v1`/the fixed hash
- **AND** exactly one installer row exists with operation A, tab `1042`, the
  literal personnel snapshot, SQL `NULL` dismissal and
  `assignment_source=completion`
- **AND** revision row is exactly `(7101,1,2026-09-01T12:00:00+03:00)`
- **AND** projection item `28` exposes A, actor/times/revision/template above,
  installer `1042` with snapshot/current status `employed`,
  `currentlyAssigned=true`, and separate current crew contains only `1042`
- **AND** photos/completed sections are empty and no unrelated fact changes.

The auto-increment operation identity is checked for referential uniqueness but
is not a stable transcript value.

Stable milestone:

`ITEM_COMPLETION accepted status=200 result=accepted revision=1 operations=1 installers=1`

## Exact and changed-payload replay

### Exact serial replay

- **WHEN** exact A is POSTed again with the same valid session/token
- **THEN** response is `200`, result `duplicate`, revision `1`
- **AND** every raw operation/installer/revision row and projection byte-equivalent
  value remains unchanged.

### Same id with changed semantic payload

- **WHEN** the same client id A is POSTed for the same object with valid envelope
  but `itemId=29` and `installerTabIds=['2048']`
- **THEN** current pilot still responds `200`, `duplicate`, revision `1` before
  validating changed item/crew semantics
- **AND** complete primary facts/projection remain unchanged.

Stable milestone:

`ITEM_COMPLETION replay exact=duplicate changed=duplicate revision=1 mutations=0`

Payload-unaware duplicate precedence is PILOT_ONLY. Target same-id/different-
payload conflict remains an approved-spec decision and future RED, not a
characterization regression.

## Lower-stale acceptance and ahead conflict

- **GIVEN** revision `1` after A
- **WHEN** valid operation B submits base `0`
- **THEN** response is `200`, accepted revision `2`; a second exact operation and
  installer row append, while A remains byte-identical
- **AND** revision becomes `(7101,2,2026-09-01T12:00:00+03:00)` and projection
  contains items 28/A and 29/B.

- **GIVEN** revision `2`
- **WHEN** operation C submits ahead base `3`
- **THEN** response is `409`, result `conflict`, current revision `2`
- **AND** operation/installer/revision/projection facts remain byte-identical.

Stable milestone:

`ITEM_COMPLETION revisions stale=accepted:2 ahead=conflict:2 final=2`

Exact translated conflict text is not asserted. Lower-stale acceptance is
PILOT_ONLY; target strict expected revision remains a future RED.

## Historical crew snapshot survives a new registered order

- **GIVEN** A and B accepted with installer `1042`
- **WHEN** fixture adds registered order `8102`, version `2`, same engineer `7301`
  and current crew only `2048`, then performs a real checklist GET
- **THEN** every stored A/B operation and `1042` installer snapshot remains
  byte-identical
- **AND** projection current crew contains only `2048`, while items 28 and 29
  retain `1042`, snapshot status `employed` and `currentlyAssigned=false`.

Stable milestone:

`ITEM_COMPLETION attribution historical=1042 current=2048 preserved=yes`

## Two real same-base commands both succeed

Concurrency uses independent case `7102`, object `47102`, registered order
`8103`, actor `7301`, crew `1042`, the same valid template association and
GET-initialized revision `0`.

- Operation D: id `55555555-5555-4555-8555-555555555555`, device time
  `2026-09-01T08:58:00+00:00`, base `0`, section `1`, item `28`, installer 1042.
- Operation E: id `66666666-6666-4666-8666-666666666666`, device time
  `2026-09-01T08:59:00+00:00`, base `0`, section `1`, item `29`, installer 1042.

- **WHEN** two loopback servers/sessions release D and E POST clients at the same
  parent barrier
- **THEN** both responses are `200`/`accepted`; their revisions as an unordered
  set are exactly `{1,2}`
- **AND** raw operation accepted revisions are `{1,2}`, both complete installer
  snapshots exist, final revision is `2`, and no partial fact exists.

Stable milestone:

`ITEM_COMPLETION concurrent statuses=200,200 results=accepted,accepted revisions=1,2 final=2`

Which operation receives which revision is deliberately not pinned. Two-success
serialization is PILOT_ONLY; target one-winner optimistic concurrency remains a
future RED.

## Four isolated rejection boundaries

Each scenario has a dedicated object/case/order, obtains a real page session GET,
then snapshots all relevant raw facts immediately before POST. That GET currently
creates revision row `0` through projection; this read mutation is PILOT_ONLY.
The rejected POST must not advance it or create operation/installer facts.

1. Object `47103`, case `7103` is `assignment_order_registered` with otherwise
   valid order/template/crew. POST uses valid envelope/id
   `77777777-7777-4777-8777-777777777777`; current HTTP admission returns `403`.
2. Object `47104`, case `7104` is working with valid order/crew but no template
   association. POST id `88888888-8888-4888-8888-888888888888` returns `422`.
3. Object `47105`, case `7105` is otherwise valid but submits section `1`, item
   `1`, id `99999999-9999-4999-8999-999999999999`; POST returns `422`.
4. Object `47106`, case `7106` has current crew only `1042` but submits installer
   `2048`, id `aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa`; POST returns `422`.

For all four, operations/installers remain empty; revision remains exactly zero;
case/order/template/workforce rows, schema fingerprints, decoy and unowned facts
remain byte-identical. Exact Russian messages and validation ordering are not
promoted.

Stable milestone:

`ITEM_COMPLETION rejections statuses=403,422,422,422 operations=0 installers=0 revisions=0,0,0,0`

## Legacy projection manufactures installer attribution

Independent case `7107`, object `47107`, order `8107` and current crew `1042`
contain one setup-owned legacy operation
`bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb`: item_completed, section 1/item 28,
actor 7301, device time `2026-08-20T09:00:00+03:00`, received time
`2026-08-20T09:01:00+03:00`, base 0/accepted 1, raw payload `{}`, nullable
template fields SQL `NULL`, revision 1, and no installer rows.

- **WHEN** admitted actor performs the first real checklist GET
- **THEN** current projection inserts exactly one current-crew snapshot for 1042
  with `assignment_source=pilot_backfill_current_order` and returns it
- **AND** the legacy operation/revision remain byte-identical
- **WHEN** the same page is read again
- **THEN** no second installer row is added.

Stable milestone:

`ITEM_COMPLETION backfill first=1 second=0 source=pilot_backfill_current_order`

This is unsafe PILOT_ONLY evidence. Before target Gate 1/RED, the migration
planning contract must normatively require read-only projection and prohibit
manufacturing historical attribution.

## Isolation and anti-fake contract

1. Caller SHALL supply
   `FMONITOR_ITEM_COMPLETION_VERIFY_RUN_TOKEN` as exactly 12 lowercase hex
   characters and exact repository-owned
   `FMONITOR_ITEM_COMPLETION_VERIFY_ARTIFACT_ROOT`. Missing, malformed, symlinked,
   non-directory or out-of-bound values are `SETUP_FAILURE`; `/tmp`, home root
   and fallback locations are forbidden.
2. Run owns process SQL prefix `ciic_<token>_` (18 ASCII bytes), legacy prefix
   `ciic_<token>_legacy_`, and only these 28 exact table basenames:
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
   `fm2_checklist_photos`, and legacy-prefix-only `fm_maintable`. The process
   prefix applies to the first 27 names and legacy prefix only to the last. The
   35-byte longest process basename yields a 53-byte table name, below MariaDB's
   64-byte limit and the repository's future 28-byte prefix cap.
3. Filesystem ownership is exactly artifact child `item-completion-<token>` and
   these direct members: directories `sessions` and `barrier`; files
   `active-manifest.json`, `router.php`, `requests-serial.jsonl`,
   `requests-concurrency-a.jsonl`, `requests-concurrency-b.jsonl`,
   `serial.stdout`, `serial.stderr`, `concurrency-a.stdout`,
   `concurrency-a.stderr`, `concurrency-b.stdout`, `concurrency-b.stderr`,
   and barrier members `ready-d`, `ready-e`, `go`, `result-d.json`,
   `result-e.json`. PHP-generated session files are allowed only as direct regular
   files inside exact owned `sessions`; no other descendant or symlink is owned.
   The manifest/router/log/barrier contents are test mechanics, never behavioral
   expected-value sources.
4. Process ownership is limited to handles returned when starting exact serial
   loopback server, concurrency servers A/B and POST clients D/E. Parent SHALL
   store each handle before continuing, SHALL never discover processes by name,
   and SHALL stop/reap only those five handles.
5. Before mutation it SHALL refuse every occupied exact owned table/child and
   SHALL NOT inspect, reuse, repair, truncate, rename or drop occupied state.
   Wildcard SQL/filesystem discovery and cleanup are forbidden.
6. Test setup owns exact fixture DDL and rows. It precreates the relevant final
   checklist family and SHALL NOT use `ensureSchema` as setup. HTTP still invokes
   current runtime DDL debt; all structural fingerprints SHALL remain unchanged.
7. Parent creates a fixed ambient artifact decoy and snapshots unrelated
   process-prefixed tables. Both remain byte-identical across every action.
8. Parent independently audits HTTP request log, response status/body,
   raw database rows, projection and schema after each action. Verifier-authored
   summaries or exit zero alone never pass; echo-only behavior fails.
9. Every server/client has bounded connect/readiness/execution timeout. Parent
   stops and reaps exact processes on success/failure. At most 24 requests and
   20 process cases and 32 owned tables are permitted per run; response bodies
   are capped at 1 MiB.
10. Cleanup removes only explicit owned names in reverse dependency order after
   ownership proof, then proves none remains. It is idempotent and runs on every
   failure path. Recursive removal is permitted only inside the exact validated
   owned artifact child after proving every descendant belongs to the closed set
   above; artifact root and siblings are never removal targets.
11. Meta-test runs the complete verifier twice with distinct unoccupied tokens.
   Normalized stdout is byte-identical, stderr empty, independent evidence
   complete, decoy/unrelated state preserved and owned state absent afterward.

Ports, host nonces, process ids, tokens, prefixes, DB/table/path names, cookies,
CSRF, SQL, credentials and stack traces SHALL NOT enter normalized stdout.

## Stable transcript

Normalized stdout SHALL contain these seven milestones followed by the exact
terminal line in this order:

```text
ITEM_COMPLETION accepted status=200 result=accepted revision=1 operations=1 installers=1
ITEM_COMPLETION replay exact=duplicate changed=duplicate revision=1 mutations=0
ITEM_COMPLETION revisions stale=accepted:2 ahead=conflict:2 final=2
ITEM_COMPLETION attribution historical=1042 current=2048 preserved=yes
ITEM_COMPLETION concurrent statuses=200,200 results=accepted,accepted revisions=1,2 final=2
ITEM_COMPLETION rejections statuses=403,422,422,422 operations=0 installers=0 revisions=0,0,0,0
ITEM_COMPLETION backfill first=1 second=0 source=pilot_backfill_current_order
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-ITEM-COMPLETION-001
```

Exact spec/test/expected-transcript hashes are pinned at the applicable test
reviews. Verifier implementation hash is pinned only at Gate 5.

## Classification and explicit exclusions

- `PRODUCT_ACCEPTED` context only: a completion fact is attributed to actor,
  template and installer snapshots; historical facts are append-only; state
  change belongs behind one application seam.
- `PILOT_ONLY`, characterized: current engineer admission branch; hard-coded
  section/item map; payload-unaware duplicate precedence; lower-stale acceptance;
  two successful same-base commands; current response statuses; GET-created
  revision zero; later-operation projection overwrite; current workforce values;
  legacy attribution backfill and request-time DDL execution.
- Explicitly excluded/UNKNOWN for this PILOT_ONLY oracle: target exact-capability
  authorization matrix; queued-operation reauthorization after active/capability
  change; dismissed-current-crew eligibility; payload-conflict UX; corrections;
  photos/section completion; template-native item validation; completion
  percentages; premium/payment; production data and schema redesign.

This characterization SHALL NOT infer target semantics from any excluded or
PILOT_ONLY outcome.

## Failure classification and Gate evidence

- `SETUP_FAILURE`, exit `2`: unavailable MariaDB; invalid root/token; occupied
  namespace; fixture/assets/identity/manifest construction failure; loopback
  bind/readiness/session/CSRF failure before intended action; timeout/reaping or
  inability to audit/clean exact owned state.
- Qualifying RED: healthy private fixture and HTTP server prove the focused
  verifier/one reviewed assertion is absent or fails for the intended behavior.
- `REGRESSION_FAILURE`, exit `1`: wrong HTTP/result/facts/projection; wrong
  mutation boundary; fake/single-connection concurrency; structural/decoy/
  unrelated damage; nondeterminism; secret leak or owned-state leak.

Expected domain rejections and setup failure are never RED. First focused
command:

`php tests/Verification/characterize_inspection_item_completion_001_test.php`

## Done definition

The slice is done only after every mandatory gate and incremental review in
`docs/development-process.md` completes:

1. this exact v0.1 Gate 1 draft receives explicit owner `APPROVED` as PILOT_ONLY;
2. fresh RED author demonstrates the smallest accepted HTTP assertion failure;
3. a different fresh reviewer approves that test before minimal GREEN;
4. only the accepted HTTP exchange turns GREEN without changed expectations;
5. expanded replay/revision/concurrency/history/backfill/rejection assertions
   demonstrate intended RED and another fresh reviewer approves them;
6. minimal expanded verifier turns the unchanged tests GREEN twice;
7. canonical characterization, checklist regressions, lint, architecture and
   `make verify` introduce no new regression;
8. target planning normatively requires read-only projection/no manufactured
   attribution before its own Gate 1/RED;
9. a fresh code reviewer who authored neither GREEN increment records
   `APPROVED` and pins verifier hash.

Done does not change production code/schema, implement target authorization or
promote pilot defects. Target authorization is separately owner-approved in
`docs/operations/inspection-item-completion-authorization-decision.md`. This draft can complete task 1.1 only after a fresh
technical consistency review; task 1.2 and RED remain blocked on owner approval.
