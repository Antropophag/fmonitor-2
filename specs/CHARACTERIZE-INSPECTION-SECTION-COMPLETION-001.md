# CHARACTERIZE-INSPECTION-SECTION-COMPLETION-001 v0.1

Status: `DRAFT / OWNER_APPROVAL_REQUIRED` for Gate 1. This is a strictly
`PILOT_ONLY` characterization of current `section_completed`. It does not
approve browser auto-enqueue, broad authorization, hard-coded readiness,
payload-blind replay, stale/repeated/two-success completion, last-write
projection, stale completion after photo revoke, runtime DDL or mutating reads.

Technical consistency: `READY_FOR_OWNER_REVIEW` on 2026-09-01 after fresh
independent review `section_completion_gate1_ready_review_v29`. This records
technical review only and is not owner approval.

## Product basis, actor and intent

The fast-pilot contract says an assigned construction-control engineer issues a
separate section-completion command only when every item in current projection
is complete, at least one non-revoked accepted photo exists and work is open.
The command adds no progress weight.

Current browser silently auto-enqueues `section_completed` after item/photo
changes and during local-state restoration. Current server counts historical
hard-coded item ids and active photo rows, accepts repeated completion and does
not invalidate completion after last-photo revoke. This spec records those
contrasts; it does not turn them into target requirements.

The fixed fictional actor is active user `7401`, `Елена Орлова`, authenticated
as `engineer.section@example.test`, with no active role granting
`checklist.edit`. Admitted object cards identify `7401` as current control
engineer. This is one current pilot admission branch, not target policy.

## Public oracle seam

- Future verifier entry:
  `php tests/Verification/characterize_inspection_section_completion_001_test.php`.
- Serial behavior SHALL run through a production-composed loopback HTTP server.
  Client first GETs `/pilot/objects/{objectId}/checklist`, retains the real
  session cookie and parses server-created CSRF, then sends same-origin POSTs to
  `/pilot/objects/{objectId}/checklist/operations` and `/checklist/photos`.
- JSON POSTs carry exact cookie, matching `Origin`,
  `Sec-Fetch-Site: same-origin`, exact `Content-Length`,
  `Content-Type: application/json; charset=UTF-8` and `X-FM2-CSRF`. Photo POST
  uses exact PNG bytes, `Content-Type: image/png`, base64 JSON metadata in
  `X-FM2-Operation`, and the same cookie/origin/CSRF boundary.
- Concurrent completion SHALL use two separately started production-composed
  servers, clients, sessions/tokens and DB connections against one private
  generation. One server/connection or sequential calls is not concurrency.
- Request logs record method, route and unpredictable parent-known nonce.
  Direct `ChecklistSync`, direct operation/photo DML, stdout or response alone
  cannot substitute for real HTTP execution.
- Browser auto-enqueue evidence is a separate source assertion; it is never
  inferred from server results or treated as target UX approval.

## Fixed time, template, people, image and primary process

Expected values are literals fixed before RED and SHALL NOT be copied from
future verifier output or recomputed with production checklist logic.

- Server clock: `2026-09-01T16:00:00+03:00`.
- Device installation id:
  `12121212-1212-4212-8212-121212121212`.
- Template snapshot id `9301`, version
  `characterize-section-completion-v1`,
  `valid_from=2026-08-01 00:00:00`, association
  `effective_at=2026-08-02 00:00:00`.
- Template payload bytes: `{"definitions":[{"id":42,"share":0}]}`;
  independently calculated SHA-256
  `1f424fbc272f96d1bbbc442aa007744f282edfb10ed3ace8176eb983b8de5e7a`.
  The current server does not parse these bytes for readiness.
- Installer `1042`, `Иванов Иван Иванович`, position
  `Электромеханик по лифтам`, status `employed`, SQL `NULL` dismissal, workforce
  source update `2026-08-27T18:15:00+03:00`.
- Exact 1×1 PNG base64:
  `iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=`;
  decoded size `68`, MIME `image/png`, SHA-256
  `431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460`,
  original name `section-8.png`.
- Primary case `7301`, object `47301`, state `working`, registered order `8301`
  version `1`, engineer `7401`, crew `[1042]`, fixed template and no initial
  checklist revision/operation/photo.

Section 8/item 42 is chosen only because it is the smallest current hard-coded
section. Item share zero in fixture ensures that any progress change would be a
false implementation-derived claim; target template/weight semantics remain
outside this characterization.

## Literal primary operation envelopes

Compact JSON order is `clientOperationId`, `deviceInstallationId`, `type`,
`deviceTime`, `baseRevision`, `sectionId`, then type-specific members.

### I — prerequisite item completion

- id `13131313-1313-4313-8313-131313131313`;
- time `2026-09-01T12:50:00+00:00`, base `0`, section `8`, item `42`,
  installers `["1042"]`.

```json
{"clientOperationId":"13131313-1313-4313-8313-131313131313","deviceInstallationId":"12121212-1212-4212-8212-121212121212","type":"item_completed","deviceTime":"2026-09-01T12:50:00+00:00","baseRevision":0,"sectionId":8,"itemId":42,"installerTabIds":["1042"]}
```

### U — prerequisite photo upload

- id `14141414-1414-4414-8414-141414141414`;
- time `2026-09-01T12:51:00+00:00`, base `1`, section `8`;
- metadata additionally contains fixed SHA/MIME/size/name above.

Exact decoded `X-FM2-Operation` JSON:

```json
{"clientOperationId":"14141414-1414-4414-8414-141414141414","deviceInstallationId":"12121212-1212-4212-8212-121212121212","type":"photo_uploaded","deviceTime":"2026-09-01T12:51:00+00:00","baseRevision":1,"sectionId":8,"sha256":"431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460","mime":"image/png","size":68,"originalName":"section-8.png"}
```

### A — first section completion

- id `15151515-1515-4515-8515-151515151515`;
- time `2026-09-01T12:52:00+00:00`, base `2`, section `8`.

Exact body:

```json
{"clientOperationId":"15151515-1515-4515-8515-151515151515","deviceInstallationId":"12121212-1212-4212-8212-121212121212","type":"section_completed","deviceTime":"2026-09-01T12:52:00+00:00","baseRevision":2,"sectionId":8}
```

### B/C/R/D — repeat, ahead, revoke and post-revoke probe

- B `16161616-1616-4616-8616-161616161616`, time
  `2026-09-01T12:53:00+00:00`, section 8, lower-stale base `0`.
- C `17171717-1717-4717-8717-171717171717`, time
  `2026-09-01T12:54:00+00:00`, section 8, ahead base `5`.
- R `18181818-1818-4818-8818-181818181818`, `photo_revoked`, time
  `2026-09-01T12:55:00+00:00`, base `4`, section 8 and runtime photo id returned
  by U; the id is audited but excluded from normalized transcript.
- D `19191919-1919-4919-8919-191919191919`, `section_completed`, time
  `2026-09-01T12:56:00+00:00`, base `5`, section 8.

## Accepted lifecycle and no extra progress

- **GIVEN** primary fixture and real GET-created session/CSRF/revision `0`
- **WHEN** actor `7401` POSTs I, U and A in order
- **THEN** all HTTP responses are `200`/`accepted` at revisions `1`, `2`, `3`
- **AND** I has exact item/installer/template facts; U has exact photo operation,
  photo metadata and content-addressed blob; A has case `7301`, null item,
  actor/device/server times, base `2`, accepted revision `3`, literal payload
  `[]` and
  fixed template triple
- **AND** revision is exactly
  `(7301,3,2026-09-01T16:00:00+03:00)`
- **AND** projection exposes item 42, one active photo and completed section 8
  with A/server time/revision/template
- **AND** comparing immediately before/after A proves I/U/photo/blob and item/
  active-photo counts are byte-identical; only operation A appends, the existing
  revision row advances and projection is recomputed.

Auto-increment operation/photo identities are checked for uniqueness and linkage
but excluded from stable transcript.

Stable milestone:

`SECTION_COMPLETION accepted statuses=200,200,200 revisions=1,2,3 completion=1 item_delta=0 photo_delta=0`

## Exact and changed-semantics replay

Changed-replay fixture is working/open case/object `7309/47309`, registered order
`8309` version `1`, actor/engineer `7401`, crew `1042` and the fixed template
association. Its own GET supplies an object-bound session/CSRF. It need not be
ready because global duplicate lookup is the observed earlier boundary.

- **WHEN** exact A is POSTed again
- **THEN** response is `200`, `duplicate`, revision `3`, zero mutation.
- **WHEN** A's id is reused on object `47309` in this exact compact body

```json
{"clientOperationId":"15151515-1515-4515-8515-151515151515","deviceInstallationId":"12121212-1212-4212-8212-121212121212","type":"section_completed","deviceTime":"2026-09-01T12:52:30+00:00","baseRevision":0,"sectionId":7}
```

- **THEN** global duplicate precedence still returns `200`, `duplicate`,
  revision `3` before object/section/readiness semantics
- **AND** primary and other-object facts remain byte-identical.

Stable milestone:

`SECTION_COMPLETION replay exact=duplicate changed=duplicate revision=3 mutations=0`

Payload/global duplicate precedence is `PILOT_ONLY`.

## Lower-stale distinct repeat and ahead conflict

- **GIVEN** ready section remains completed at revision `3`
- **WHEN** B submits base `0`
- **THEN** response is `200`, accepted revision `4`; a second raw
  `section_completed` appends and projection section 8 exposes B while A remains
  byte-identical.
- **GIVEN** revision `4`
- **WHEN** C submits base `5`
- **THEN** response is `409`, conflict/current revision `4`, zero mutation.

Stable milestone:

`SECTION_COMPLETION repeat stale=accepted:4 completions=2 ahead=conflict:4 final=4`

Repeated completion and lower-stale acceptance are `PILOT_ONLY`.

## Revoking the last photo leaves completion visible

- **GIVEN** primary revision `4`, two completion facts and one active photo
- **WHEN** R revokes the only photo
- **THEN** response is `200`, accepted revision `5`; photo is inactive, revoke
  operation appends, I/A/B remain byte-identical and completed-section projection
  still exposes B/revision `4`
- **WHEN** D then attempts another completion at base `5`
- **THEN** response is `422`, no D row/revision advance, final revision `5`, and
  completed-section projection still exposes B despite zero active photos.

Stable milestone:

`SECTION_COMPLETION revoke accepted=5 active_photos=0 retry=rejected completion_visible=revision-4 final=5`

This inconsistency is `PILOT_ONLY`; target revoke/correction invalidation and
photo reuse remain owner decisions.

## Two real same-base completions both succeed

Independent case/object `7302/47302`, order `8302`, actor `7401`, crew `1042`,
valid template and real HTTP-created item/photo prerequisites are ready at
revision `2` with no completion.

- Concurrency device id is the fixed `12121212-1212-4212-8212-121212121212`.
- Prerequisite item I2: id `31313131-3131-4131-8131-313131313131`, type
  `item_completed`, time `2026-09-01T12:40:00+00:00`, base 0, section 8, item
  42, installers `["1042"]`; it is accepted at revision 1.
- Prerequisite upload U2: id `32323232-3232-4232-8232-323232323232`, type
  `photo_uploaded`, time `2026-09-01T12:41:00+00:00`, base 1, section 8 and the
  exact PNG SHA/MIME/size/name/bytes fixed above; it is accepted at revision 2.
- I2 and U2 use the same compact member order and public HTTP headers as I/U;
  their complete literal values above leave no generated envelope field.

- E id `21212121-2121-4121-8121-212121212121`, time
  `2026-09-01T12:57:00+00:00`, base `2`, section 8.
- F id `23232323-2323-4323-8323-232323232323`, time
  `2026-09-01T12:58:00+00:00`, base `2`, section 8.

- **WHEN** two server/session/client processes release E/F at one parent barrier
- **THEN** both return `200`/`accepted`, unordered revisions `{3,4}`
- **AND** two complete raw facts exist, final revision is `4`, prerequisites are
  unchanged and projection exposes whichever operation owns revision `4`.

Stable milestone:

`SECTION_COMPLETION concurrent statuses=200,200 results=accepted,accepted revisions=3,4 final=4 latest=revision-4`

## Six isolated rejection boundaries

Each scenario owns object/case/order and gets its own real page session. Parent
snapshots all raw state immediately after GET and before completion POST.
Current production ordering computes mutation `$allowed`, but the `isPage` GET
branch creates session/CSRF and returns before POST-only `if (!$allowed)` in
`PilotE2ECoordinator::checklist`. Therefore cases 7306 and actor 7499 in 7307
can obtain real object-bound tokens while their subsequent POSTs honestly return
403. This permissive read/session behavior is observed setup, not target access
policy.

All six commands use device id `12121212-1212-4212-8212-121212121212`, type
`section_completed`, section `8`, compact member order identical to A and the
fixed server clock/template `9301` triple. The following table fixes every
remaining envelope/process field; there are no omitted request members:

| Case/object/order | Actor | Client operation id | Device time | Base | Single violated boundary | HTTP |
|---|---:|---|---|---:|---|---:|
| `7303/47303/8303` | 7401 | `24242424-2424-4424-8424-242424242424` | `2026-09-01T13:10:00+00:00` | 1 | no item 42 | 422 |
| `7304/47304/8304` | 7401 | `25252525-2525-4525-8525-252525252525` | `2026-09-01T13:11:00+00:00` | 1 | no photo | 422 |
| `7305/47305/8305` | 7401 | `26262626-2626-4626-8626-262626262626` | `2026-09-01T13:12:00+00:00` | 3 | revoked-only photo | 422 |
| `7306/47306/8306` | 7401 | `27272727-2727-4727-8727-272727272727` | `2026-09-01T13:13:00+00:00` | 2 | non-working/not opened | 403 |
| `7307/47307/8307` | 7499 | `28282828-2828-4828-8828-282828282828` | `2026-09-01T13:14:00+00:00` | 2 | no editor/assignment | 403 |
| `7308/47308/8308` | 7401 | `29292929-2929-4929-8929-292929292929` | `2026-09-01T13:15:00+00:00` | 2 | mismatched template triple | 422 |

Cases use registered order version `1`, crew `1042`, engineer `7401`, working/
opened state and coherent template `9301` unless that row's single boundary says
otherwise. Case 7306 process state is `assignment_order_prepared`, its latest
order status is `registered`, and all opening fields are SQL `NULL`, so the card
is valid `Готов к открытию` but not opened.
Case 7307 is working/opened but card engineer is 7401; active actor 7499 has no
editor capability. Case 7308 association version is literal
`characterize-section-completion-mismatch` while snapshot remains the fixed v1.

1. `47303/7303`: one active photo but no item 42; id
   `24242424-2424-4424-8424-242424242424`; returns `422`.
2. `47304/7304`: item 42 but no photo; id
   `25252525-2525-4525-8525-252525252525`; returns `422`.
3. `47305/7305`: item 42 and one revoked-only photo; id
   `26262626-2626-4626-8626-262626262626`; returns `422`.
4. `47306/7306`: prerequisites exist but case/card is non-working/not opened;
   id `27272727-2727-4727-8727-272727272727`; HTTP admission returns `403`.
5. `47307/7307`: active actor `7499` gets its own page session but has neither
   broad editor capability nor current assignment; id
   `28282828-2828-4828-8828-282828282828`; returns `403`.
6. `47308/7308`: prerequisites exist but template association/snapshot triple is
   mismatched; id `29292929-2929-4929-8929-292929292929`; returns `422`.

Prerequisites MAY be setup-owned literal rows where the violated boundary makes
their public creation impossible; they are never evidence for accepted I/U/A.
Every row uses actor `7401`, fixed device/server/template facts, section 8, item
42 for item operations and the exact fixed PNG for uploads. `base` and `accepted`
below are literal `base_revision`/`accepted_revision`; `payload_json` bytes are
exactly the shown compact strings.

| Case | Operation id | Type | Device time | Base | Accepted | Exact `payload_json` |
|---:|---|---|---|---:|---:|---|
| 7303 | `33333333-3333-4333-8333-333333333333` | `photo_uploaded` | `2026-09-01T13:00:00+00:00` | 0 | 1 | `{"sha256":"431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460","mime":"image/png","size":68,"originalName":"section-8.png"}` |
| 7304 | `34343434-3434-4434-8434-343434343434` | `item_completed` | `2026-09-01T13:00:10+00:00` | 0 | 1 | `{"installerTabIds":["1042"]}` |
| 7305 | `35353535-3535-4535-8535-353535353535` | `item_completed` | `2026-09-01T13:00:20+00:00` | 0 | 1 | `{"installerTabIds":["1042"]}` |
| 7305 | `36363636-3636-4636-8636-363636363636` | `photo_uploaded` | `2026-09-01T13:00:21+00:00` | 1 | 2 | `{"sha256":"431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460","mime":"image/png","size":68,"originalName":"section-8.png"}` |
| 7305 | `37373737-3737-4737-8737-373737373737` | `photo_revoked` | `2026-09-01T13:00:22+00:00` | 2 | 3 | `{"photoId":<case-7305-photo-id>}` |
| 7306 | `38383838-3838-4838-8838-383838383838` | `item_completed` | `2026-09-01T13:00:30+00:00` | 0 | 1 | `{"installerTabIds":["1042"]}` |
| 7306 | `39393939-3939-4939-8939-393939393939` | `photo_uploaded` | `2026-09-01T13:00:31+00:00` | 1 | 2 | `{"sha256":"431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460","mime":"image/png","size":68,"originalName":"section-8.png"}` |
| 7307 | `3a3a3a3a-3a3a-4a3a-8a3a-3a3a3a3a3a3a` | `item_completed` | `2026-09-01T13:00:40+00:00` | 0 | 1 | `{"installerTabIds":["1042"]}` |
| 7307 | `3b3b3b3b-3b3b-4b3b-8b3b-3b3b3b3b3b3b` | `photo_uploaded` | `2026-09-01T13:00:41+00:00` | 1 | 2 | `{"sha256":"431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460","mime":"image/png","size":68,"originalName":"section-8.png"}` |
| 7308 | `3c3c3c3c-3c3c-4c3c-8c3c-3c3c3c3c3c3c` | `item_completed` | `2026-09-01T13:00:50+00:00` | 0 | 1 | `{"installerTabIds":["1042"]}` |
| 7308 | `3d3d3d3d-3d3d-4d3d-8d3d-3d3d3d3d3d3d` | `photo_uploaded` | `2026-09-01T13:00:51+00:00` | 1 | 2 | `{"sha256":"431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460","mime":"image/png","size":68,"originalName":"section-8.png"}` |

`<case-7305-photo-id>` is the one positive integer id from the unique photo row
whose upload operation is `36363636-3636-4636-8636-363636363636`. Setup SHALL
read it only after proving exactly one such row, substitute its canonical decimal
form into both revoke target and raw payload, and exclude only that integer from
normalized transcript. No other setup value is dynamic.
For all six, POST creates no section-completion row and does not advance the
pre-POST revision. Existing item/photo/revoke/case/order/template/workforce,
schema fingerprints, decoy and unrelated facts remain byte-identical. GET
revision initialization/backfill, exact messages and validation order are not
promoted.

Stable milestone:

`SECTION_COMPLETION rejections statuses=422,422,422,403,403,422 completions=0 revision_advances=0`

## Browser automatic enqueue source evidence

Current authoritative `app/PilotHttp/checklist.js` is `23195` bytes with
SHA-256 `c8f9065e5d184e66dbda1f74ecf07c03dba294a9b409e934c97384ff2c441385`.
Static evidence SHALL require all of these independent source tokens/edges:

- `completeReadySection` enqueues `section_completed` when items+photo are ready;
- it is called after item completion, photo upload and installer-dialog save;
- `load()` applies local operations and startup flow calls readiness completion;
- sync priority orders `section_completed` after item/photo operations.

Stable milestone:

`SECTION_COMPLETION browser auto_enqueue=yes explicit_user_action=no source_sha256=c8f9065e5d184e66dbda1f74ecf07c03dba294a9b409e934c97384ff2c441385`

This static milestone proves current wiring only. It neither executes browser UX
nor approves automatic completion.

## Isolation and anti-fake contract

1. Caller supplies `FMONITOR_SECTION_COMPLETION_VERIFY_RUN_TOKEN` as exactly 12
   lowercase hex and repository-owned
   `FMONITOR_SECTION_COMPLETION_VERIFY_ARTIFACT_ROOT`. Missing/malformed,
   symlinked/non-directory/out-of-bound values are `SETUP_FAILURE`; `/tmp`, home
   root and fallback locations are forbidden.
2. Run owns process SQL prefix `cisc_<token>_` (18 ASCII bytes), legacy prefix
   `cisc_<token>_legacy_`, and exactly 28 basenames:
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
   `fm2_checklist_photos`, and legacy-only `fm_maintable`. Process prefix applies
   to first 27, legacy prefix only to last. Longest process basename is 35 bytes;
   total 53 is below MariaDB 64 and future 28-byte prefix cap.
3. Filesystem ownership is exact child `section-completion-<token>`; directories
   `sessions`, `barrier`, `storage`, `storage/checklist`; direct files
   `active-manifest.json`, `router.php`, `requests-serial.jsonl`,
   `requests-concurrency-a.jsonl`, `requests-concurrency-b.jsonl`,
   `serial.stdout`, `serial.stderr`, `concurrency-a.stdout`,
   `concurrency-a.stderr`, `concurrency-b.stdout`, `concurrency-b.stderr`;
   barrier files `ready-e`, `ready-f`, `go`, `result-e.json`, `result-f.json`;
   and exact blob
   `storage/checklist/431ced6916a2a21a156e38701afe55bbd7f88969fbbfc56d7fe099d47f265460.bin`.
   PHP session files are allowed only as direct regular files inside `sessions`;
   no other descendant or symlink is owned.
4. Process ownership is limited to handles returned for serial server,
   concurrency servers A/B and POST clients E/F. Parent records and stops/reaps
   only those five; name discovery is forbidden.
5. Before mutation parent refuses every occupied exact table/artifact child and
   never inspects, reuses, repairs, truncates, renames or drops occupied state.
   Wildcard SQL/filesystem discovery/cleanup is forbidden.
6. Setup precreates exact relevant final schema and never calls `ensureSchema`.
   HTTP still executes runtime DDL debt; fingerprints remain unchanged.
7. Parent creates fixed ambient artifact decoy and fingerprints unrelated
   process-prefixed tables. Both stay byte-identical.
8. Parent independently audits request logs, response status/body, raw rows,
   blob, projection and schema after each action. Summaries/exit zero cannot
   substitute; echo-only behavior fails.
9. Servers/clients have bounded connect/readiness/execution/reaping timeouts.
   Limits: 48 HTTP requests, 20 process cases, 32 tables, 1 MiB/response.
10. Cleanup removes only proven exact owned names in reverse dependency order,
    is idempotent on all exits and proves absence. Recursive removal is allowed
    only within validated child after every descendant is in the closed set;
    artifact root/siblings are never targets.
11. Meta-test runs verifier twice with distinct clean tokens. Normalized stdout
    is byte-identical, stderr empty, evidence complete, decoy/unrelated state
    preserved and owned state absent.

Ports, nonces, pids, tokens, prefixes, DB/table/path names, cookie/CSRF, runtime
photo id, SQL, credentials and stack traces SHALL NOT enter normalized stdout.

## Stable transcript

Normalized stdout SHALL contain these seven milestones followed by exact
terminal line in this order:

```text
SECTION_COMPLETION accepted statuses=200,200,200 revisions=1,2,3 completion=1 item_delta=0 photo_delta=0
SECTION_COMPLETION replay exact=duplicate changed=duplicate revision=3 mutations=0
SECTION_COMPLETION repeat stale=accepted:4 completions=2 ahead=conflict:4 final=4
SECTION_COMPLETION revoke accepted=5 active_photos=0 retry=rejected completion_visible=revision-4 final=5
SECTION_COMPLETION concurrent statuses=200,200 results=accepted,accepted revisions=3,4 final=4 latest=revision-4
SECTION_COMPLETION rejections statuses=422,422,422,403,403,422 completions=0 revision_advances=0
SECTION_COMPLETION browser auto_enqueue=yes explicit_user_action=no source_sha256=c8f9065e5d184e66dbda1f74ecf07c03dba294a9b409e934c97384ff2c441385
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SECTION-COMPLETION-001
```

Spec/test/transcript hashes are pinned at applicable test reviews; verifier
implementation hash only at Gate 5.

## Classification and exclusions

- `PRODUCT_ACCEPTED` context only: separate append-only section-completion fact;
  all current items plus active accepted photo; open work; current assigned
  engineer; exact-delivery idempotency; no added progress weight.
- `PILOT_ONLY`, characterized: section-8 hard-coded readiness; auto-enqueue
  source wiring; payload/global duplicate; lower stale; repeated and two
  same-base completions; limited last-write projection; completion
  surviving last-photo revoke; response categories; GET revision initialization;
  runtime DDL/mutating projection.
- Explicitly excluded/UNKNOWN: target deliberate UX; exact capability plus
  assignment and queued-after-reassignment; current broad-editor admission
  branch; template-native current projection;
  `completion_retracted`; correction/photo revoke invalidation and photo reuse;
  same-id payload conflict; distinct repeat/concurrency outcome; all-section
  catalogue/weights; completion/PTO/premium/payment; production schema/data.

## Failure classification and Gate evidence

- `SETUP_FAILURE`, exit `2`: invalid root/token/occupied namespace; unavailable
  MariaDB; fixture/asset/identity/manifest failure; loopback/session/CSRF failure
  before intended behavior; timeout/reaping/audit/cleanup failure.
- Qualifying RED: healthy private fixture/server proves focused verifier or
  reviewed intended assertion is absent/fails.
- `REGRESSION_FAILURE`, exit `1`: wrong HTTP/facts/projection/no-weight boundary;
  changed prerequisites; fake concurrency; wrong rejection/revoke behavior;
  browser-source drift; schema/decoy damage; nondeterminism/secret/state leak.

Expected domain rejection and setup failure are never RED. First command:

`php tests/Verification/characterize_inspection_section_completion_001_test.php`

## Done definition

1. Owner approves exact v0.1 solely as PILOT_ONLY characterization.
2. Fresh RED author proves smallest accepted HTTP lifecycle assertion fails.
3. Different fresh reviewer approves that test before minimal GREEN.
4. Only accepted item/photo/section lifecycle turns unchanged test GREEN twice.
5. Expanded replay/repeat/concurrency/rejection/revoke/source assertions first
   fail intentionally and another fresh reviewer approves them.
6. Minimal expansion turns unchanged tests GREEN twice and registers once.
7. Relevant regressions, lint, architecture and `make verify` add no regression.
8. Target backlog keeps automatic/hard-coded/repeated/stale-photo behaviors out
   and every unresolved target policy in NEEDS_GRILL.
9. Fresh code reviewer who authored neither GREEN increment records `APPROVED`
   and pins verifier hash.

Done changes no production behavior/schema and approves no target seam or policy.
Task 1.1 completes only after fresh technical consistency review; task 1.2 and
RED remain blocked on owner approval.
