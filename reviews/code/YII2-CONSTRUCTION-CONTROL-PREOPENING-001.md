# YII2-CONSTRUCTION-CONTROL-PREOPENING-001 — independent Gate 5 review

- Reviewer: independent Codex reviewer `/root/issue40_gate5`; authored neither specification/tests nor production implementation
- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T183245Z-b5c4162999/package.json`
- Exact commit: `d5e9b26ffcddac4ce3858126e62d14af5da962ff`
- Base: `41573bf75a067eafc1422a24d2761b45805274cc` (`origin/main` at package preparation)
- Candidate source: `204c934f2235c74955fee2200440e9dec3abe87ea3c365b629b0cbd2e6d70c9f`
- Executable source: `1fd03eaa9351bc824565d0a3e157491e09bdbb920a852307bd6628387b7fbbb6`
- Snapshot patch: empty committed-source patch, SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`
- Verification plan SHA-256: `3235671da418ed39971f27bb1f7396f00059faa022d8c37b9860c7c7030aef95`
- Review date: 2026-09-14
- Verdict: **CHANGES_REQUESTED**

## Complete findings

1. **HIGH — ordinary engineers still receive other engineers' already-opened work.** The normative A1 contract says an ordinary engineer's server-side “Мои” view contains only rows whose current engineer user ID equals the actor, and the design repeats that the queue returns only actor-related rows except an explicit manager-wide mode. In `app/InspectionEvidence/MariaDbYiiChecklistRead.php:62-63`, however, the assignment filter is nested entirely inside `if ($r['process_state'] !== 'working')`. Every `working` row is appended to `$eligible` for every actor with `construction_control.read`, regardless of `$manager` or `$engineer`. This is a cross-engineer data-disclosure and authorization regression in the same queue the change extends. The focused preopening test checks actor filtering only while the row is ready and contains only one row after opening; the retained active-queue test uses one actor and never assigns distinguishable working rows to another engineer, so all supplied GREEN evidence is insensitive to the defect. Apply the actor/manager predicate to both ready and working rows, and add a real HTTP test with two differently assigned working cases proving filtered rows, totals and page boundaries.

2. **HIGH — the legacy/non-local opening owner does not enforce assigned-engineer authorization.** The design authorizes the new path specifically for the assigned `construction_control_engineer` with exact `installation.open`, with the owner rechecking actor and assignment. `MariaDbConfirmedOriginalOpening` correctly passes the selected engineer to `authorizedForOpening()` before and inside the transaction, but `app/AssignmentOrderComposition/MariaDbAssignmentOrderApplicationOperation.php:50-51` discards that engineer whenever `fm2_pilot_users` is absent and delegates to the old capability-only `authorized(..., 'installation.open')` lookup. Thus any active legacy user possessing `installation.open` can reach the writer for another engineer's composition in that supported storage mode. The Yii fixture always creates `fm2_pilot_users`; neither the new test nor the retained authorization/concurrency tests exercise this fallback, so their GREEN result cannot establish authorization “at every layer.” Preserve any explicitly specified FKR/manager override, but require actor-to-composition assignment for the construction-control-engineer admission in both storage modes and add assigned/foreign tests for the non-local branch, including the in-transaction recheck and zero-fact refusal.

3. **MEDIUM — pagination was changed from bounded SQL work to an unbounded N+1 full scan.** A1 says the existing pagination rules do not change. Previously `queue()` counted eligible rows and fetched one `LIMIT/OFFSET` page. The implementation at `app/InspectionEvidence/MariaDbYiiChecklistRead.php:59-67` now loads every working/preopening candidate, performs an engineer query for every row, additionally constructs and executes the authoritative card projection for every non-working row, stores the complete result in PHP, and only then calls `array_slice()`. Request cost and memory therefore grow with the entire corpus rather than the requested page, and the number of database queries grows linearly. The 51-row regression fixture demonstrates output shape but does not bound queries, memory or candidate count. Move the complete authorization/readiness predicate into a bounded projection/query (or another batch-capable authoritative read seam), compute the filtered count from the same predicate, and retain deterministic post-filter page ordering without per-row projection calls.

## Conformance assessment

The implementation otherwise preserves the existing confirmed-original command as the single writer: checklist/controller/view additions only compose reads and a form, and the POST continues through `ExecutionController` into the existing opening owners. The rendered action carries the construction-control return hint, success returns to the requested checklist, ready GET/HEAD remain read-only in the exercised local path, the checklist stays inert until `working`, and the view uses framework HTML encoding for stored object values. No schema or `rapid-pilot` runtime change is introduced. No additional maintainability finding is raised beyond the unbounded/N+1 queue composition above.

The approved Gate 3 record was inspected in full, including its prior correction history and final approval at clean candidate source `c03b53a96ba3e415b111614a21f2ff24fdd4d2e7ee93a61aa5f044ed3e872138`. The final package binds six source-matched GREEN records with `source_drift=false`:

- `php tests/Yii2/yii2_construction_control_preopening_001_test.php` — record `1789410647095542000-731ccd1c65d84f66bb1f50c5b22d3a70.json`
- `php tests/Yii2/yii2_construction_control_active_queue_001_test.php` — record `1789410654650169000-d02e5d3035424c268bc090b237c74768.json`
- `php tests/Yii2/yii2_inspection_journey_001_test.php` — record `1789410661473686000-151fc8053fd94d57ac79921aff966d4d.json`
- `php tests/Yii2/yii2_inspection_boundaries_001_test.php` — record `1789410674276399000-cf8c969bbfa44d27b75c3f1b05b7cdd1.json`
- `php tests/Yii2/yii2_preopening_authorization_001_test.php` — record `1789410683845011000-a66ae556cb974e11a32c7fff4b00c4e6.json`
- `php tests/Yii2/yii2_preopening_concurrency_001_test.php` — record `1789410694457887000-85ec777a313b40f980b52635d32bd128.json`

Those checks support the local happy path, neighboring behavior, read-only state, return path, replay and concurrency owner behavior, but their fixture topology does not detect findings 1-2 and they provide no performance bound for finding 3. `git diff --check origin/main...HEAD` is clean. In accordance with the owner decision, no local full `make test` or `make verify` was run. Exact-source GitHub CI and deployment are `UNKNOWN`; neither is treated as GREEN or approval.

## Verdict

**CHANGES_REQUESTED**

Gate 5 does not approve publication. Correct the working-row server authorization, enforce assigned-engineer admission in every supported owner storage path, replace the unbounded N+1 queue construction with bounded pagination, add sensitive focused regressions, obtain any Gate 3 delta approval required by changed tests/contracts, then prepare a fresh exact-source final-review package.

---

## Gate 5 correction rereview — 2026-09-14

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T185447Z-d6d30e29b8/package.json`
- Exact correction source: base commit `d5e9b26ffcddac4ce3858126e62d14af5da962ff` plus reconstructible snapshot, candidate source `554460a3d968fa975c9aabfc67e136bd6b1a13e8b30d9ec454fea1f079c14515`, executable source `112617e93ac9fdf223aa344873209c26e3ccc6c32e0ae28f78852b303c590744`
- Snapshot patch SHA-256: `cfddb2745e331b0a700cf04c7a9fdb23c510b643bf83a7e150e2a521a1a219cd`
- Verification plan SHA-256: `ae37a537e639bc65837c029a001ac84ce8dc6f65835a1e7789af264c995ebff9`
- Correction verdict: **CHANGES_REQUESTED**

### Prior findings disposition

1. **Resolved.** `MariaDbYiiChecklistRead::queue()` now composes one current-engineer expression with the documented application → latest selection → latest change event → legacy fallback order, adds `engineer = actor` to the SQL `WHERE` for every non-manager, and therefore applies the same server-side ownership predicate to both `working` and ready rows. The corrected real-HTTP active-queue test gives actor 73 exactly 51 owned working rows plus two independently sourced foreign rows, and requires exact membership, 50/1 pagination, totals and transition behavior. Its source-matched GREEN record demonstrates that the prior cross-engineer leak is no longer accepted.

2. **Resolved for the originally reported authorization gap.** Both opening-owner authorization implementations now distinguish a legacy actor carrying `construction_control_engineer` from the preserved operator/manager class: a construction-control engineer must equal the selected engineer, while an authorized non-engineer operator retains the pre-existing override. The confirmed-original owner invokes this before and again inside its transaction; `MariaDbOriginalOpening` invokes the equivalent predicate before and under its case lock. The new non-local public-factory test gives actors 73 and 95 equivalent active role/capability facts and proves only assigned actor 73 can open, while actor 95 receives `authorization_denied` with the full business snapshot unchanged. Existing local Yii authorization/revocation and the historical operator opening case remain GREEN. Static inspection confirms the downstream owner uses the same two-branch predicate at both check sites.

3. **Resolved.** The PHP full-corpus materialization, per-row engineer lookup and per-row `InstallationProcessFactory::card()->read()` call are gone. Queue execution is now one filtered `COUNT(*)` and one filtered, deterministically ordered `LIMIT/OFFSET` page query; row mapping performs no database reads. The actor predicate and readiness predicate are shared byte-for-byte between count and page SQL, and the corrected 51-row test proves exact filtered totals and boundaries. This closes the prior application-level N+1 and unbounded result-materialization finding.

### New complete finding

1. **HIGH — the performance correction creates a second, weaker readiness authority contrary to A1 and can admit integrity-invalid rows.** The normative contract explicitly requires this slice to consume the existing authoritative preopening readiness projection and says it MUST NOT implement a second simplified lineage predicate. The design likewise chooses the same readiness meaning and identifies divergence as a principal risk. `app/InspectionEvidence/MariaDbYiiChecklistRead.php:61-63` now declares readiness independently as an `EXISTS` join over latest selection, original root and current revision. That is not the authoritative `MariaDbYiiObjectCardProjection` path used by checklist access, and it omits that owner's semantic/integrity checks: non-empty selection members, positive and coherent order identity, complete engineer snapshots, application snapshot/identity/hash validation, original metadata validation, and malformed-state refusal. A persisted row can therefore be shown as “Готов к открытию” in the queue while the authoritative checklist projection rejects it (HTTP 503) or classifies it differently. The seven GREEN tests cover valid fixtures and stale lineage/PTO cases, so they do not distinguish this duplicated predicate from the required authority. Replace the duplicated readiness SQL with a batch-capable authoritative projection/seam that exposes a SQL-safe eligibility relation or materialized authoritative readiness, and add parity/integrity cases proving malformed or incomplete lineage never appears as ready while preserving bounded count/page execution.

### Correction evidence

The final correction package contains seven source-bound GREEN test records, all for candidate source `554460a3d968fa975c9aabfc67e136bd6b1a13e8b30d9ec454fea1f079c14515`, executable source `112617e93ac9fdf223aa344873209c26e3ccc6c32e0ae28f78852b303c590744`, with no reported drift:

- construction-control preopening: `1789411940899771000-6a9fbe98949a49efb325258cc0602bcc.json`
- confirmed-original opening, including non-local assignment: `1789411949549100000-edc32a57c51f40d4aa248da8de93dba3.json`
- active construction-control queue: `1789411965545161000-b6ad7a74cd8948899f026349fff48baf.json`
- inspection journey: `1789411973528668000-294628ee7d7a4f9884449d29523c9494.json`
- inspection boundaries: `1789411987578766000-fb731dad1fe142cd8f1879d9c734ae2f.json`
- preopening authorization: `1789411998839778000-725217a611894f369c89ea233e7a07e0.json`
- preopening concurrency: `1789412012217376000-2889d226912b4b60b65a996a1da64713.json`

The separately supplied exact-source architecture record `1789412019919616000-ca9644ad35f244e7963d87831aa74f7a.json` is GREEN and reports `PASS: PILOT-HTTP-AUTH-001 complete global-call qualification` plus `ARCHITECTURE CHECK PASSED (7 rules)`. The post-Gate-5 and authoritative-fixture Gate 3 delta reviews are `APPROVED`; their new assertions are independent and sensitive to the first two prior findings. `git diff --check` is clean. No prohibited local full suite was run. Exact-source GitHub CI and deployment remain `UNKNOWN` and are not approval.

### Correction verdict

**CHANGES_REQUESTED**

The three prior findings are closed, but publication remains blocked by the newly introduced violation of the explicit authoritative-readiness requirement. Correct it without restoring the full-scan/N+1 behavior, obtain Gate 3 approval for the new parity/integrity tests, and prepare another exact-source Gate 5 package.

---

## Final authoritative-helper correction rereview — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T191839Z-ed7c816b4f/package.json`
- Exact source: base commit `d5e9b26ffcddac4ce3858126e62d14af5da962ff` plus snapshot, candidate source `54299b9efeb388f58494d7883b93a8670bc1911f3a875c13f084de5fefcd19e3`, executable source `fc1eea1a82cdbe0f809ad5b550e6017d1d1360080a357bc69f431dbe98dbe8c0`
- Snapshot patch SHA-256: `a372d112cb14320f26ff0dadf3dfa58674bcfab40fae19a9cc878fc5e2aec10a`
- Verification plan SHA-256: `56baee9d50417065be5250e0077e61d045dcf1c2c5c44d76d7e14263ee3f84a2`
- Verdict: **CHANGES_REQUESTED**

### Verified corrections retained

The previous working/ready actor filter, local and non-local assigned-engineer admission, pre-lock/in-lock rechecks, and FKR/manager override behavior remain corrected. Queue construction remains bounded to one filtered count query and one `LIMIT/OFFSET` page query with no PHP full scan or per-row query. The invalid-readiness count is actor-scoped for ordinary engineers, so a malformed row owned only by another actor does not signal its existence; managers retain the explicitly broad view.

`MariaDbYiiObjectReadiness` now gives queue code one named readiness relation, and the missing-selection-member parity test correctly proves an owned malformed candidate fails closed in both queue and checklist without mutation. This is a useful correction, but it does not completely resolve the prior authoritative-parity finding.

### Remaining complete finding

1. **HIGH — card and queue still do not consume one complete readiness/integrity definition.** `MariaDbYiiObjectReadiness` delegates to new static SQL methods on `MariaDbYiiObjectCardProjection`, but `MariaDbYiiObjectCardProjection::decorate()` never consumes those methods. It continues to establish readiness procedurally through `applied()`, `YiiObjectCardApplicationIntegrity::validateComposition()`, `confirmed()` and `order()` (`app/InstallationProcess/MariaDbYiiObjectCardProjection.php:14-46,56-107`). The shared SQL relation at lines 49-52 validates only current selection/root/revision linkage plus existence of at least one selection member. It does not express the card's current-application semantic checks: JSON shape, object/case linkage, engineer identity, non-empty and ordered installer snapshots, composition identity/hash, and applied-original metadata coherence. Consequently a non-opened case with a valid current selection/original/member and a semantically corrupt latest application is `ready` to the bounded queue while the authoritative checklist card throws and returns 503. Merely locating the weaker relation as static methods on the card class does not make the card a consumer of that definition or make their integrity outcomes equivalent. The newly approved test deletes the sole selection member and is sensitive only to the one integrity condition added to the SQL; all seven GREEN fixtures retain a valid or absent application, so none detect this remaining split. Define one shared batch-capable authority that covers the card's complete ready integrity state (or make both consumers use the same validated materialized projection), and add an owned malformed-application parity case plus a foreign equivalent proving no cross-actor signaling, while retaining bounded count/page execution.

### Evidence assessment

The package supplies seven exact-source GREEN records for the preopening journey, confirmed-original owner, active queue, inspection journey/boundaries, authorization and concurrency:

- `1789413356038459000-f57e8584c49e4fec9b92a1947cd62f1e.json`
- `1789413365496198000-be6a83ee641d4cd39547d9c663f2f965.json`
- `1789413381428509000-1a7d1068a4b741f19d7d5aabebcbb9e1.json`
- `1789413391344717000-43db95a5a9654666b14faea1a995a547.json`
- `1789413406066665000-7f2328f0ddec43b58fe853936104a4b3.json`
- `1789413419632276000-0dd0a1c05f684919b4d996e82b9297aa.json`
- `1789413433079383000-99ff49083ce14d3fa388115e70097b56.json`

All are bound to candidate source `54299b9efeb388f58494d7883b93a8670bc1911f3a875c13f084de5fefcd19e3`, executable source `fc1eea1a82cdbe0f809ad5b550e6017d1d1360080a357bc69f431dbe98dbe8c0`, with `source_drift=false`. Architecture record `1789413442039524000-3a34b1b3aea94c61bf3330269e0e489b.json` is exact-source GREEN and reports the PilotHttp global-call qualification plus all seven architecture rules passing. Gate 3 approved both the malformed-member parity test and helper path delta. Those results establish their stated cases but cannot approve the untested malformed-application divergence above. `git diff --check` is clean. The prohibited local full suite was not run; GitHub CI and deployment remain `UNKNOWN`.

### Final verdict

**CHANGES_REQUESTED**

Publication remains blocked until the queue and card share the complete readiness/integrity authority rather than only selection-member eligibility, with a sensitive malformed-application parity regression and fresh exact-source Gate 5 package.

---

## Owner-corrected narrow Gate 5 review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T194828Z-2618125727/package.json`
- Exact source: base commit `d5e9b26ffcddac4ce3858126e62d14af5da962ff` plus reconstructible snapshot, candidate source `a752f9012851a882e681fa6c533ffc2e5fc94261aca278379258fe7f98d7cbc8`, executable source `163e14617cb9c44b5687cb52841c0df8572b39819bc94b3fd7e64efba272ef5a`
- Snapshot patch SHA-256: `1f317e005148a379a53390ea2a06be5fdc73463cf7d3a61c9eda30624c8c766c`
- Verification plan SHA-256: `ca0f2beee7835eb995c574b4447d868c2e258d7b2a2ca80123f22142e6e41b6a`
- Verdict: **APPROVED**

### Superseding authority and scope

The owner's 2026-09-14 product correction supersedes the earlier assigned-only, server-side queue isolation, bounded-performance/helper, and legacy-assignment review premises. This review therefore does not carry those earlier findings forward. The controlling behavior is now the shared server queue with existing client-side “Мои/Все,” a ready row labelled by its current engineer, and local substitution by another authorized construction-control engineer. Legacy/non-local authorization is explicitly unchanged and outside this slice.

### Complete findings

None.

The production delta matches the corrected narrow contract:

- `MariaDbYiiChecklistRead::queue()` retains the shared queue and existing pagination/composition approach, adds only authoritative per-row ready admission, preserves PTO exclusion, and supplies `controlEngineer` so the existing client-side “Мои” projection continues to use the assigned engineer. Selection without a current accepted original is excluded; a valid ready object is emitted once with `completed=false` and the ready status.
- Checklist access derives ready state and the current opening intent from the existing authoritative object-card projection. The form is offered only when the actor has the existing checklist admission (`roleAccess` or the inherited item-completion admission) and exact current `installation.open`; assignment is deliberately not an authorization condition.
- Both local opening owners widen only their local role allow-list from FKR/manager to also include `construction_control_engineer`, before and again inside the owning transaction. The existing `MariaDbAssignmentOrderApplicationOperation::authorized()` keeps `assignment_order.composition.apply` restricted to FKR/manager because the widened role set is selected only for `installation.open`. The non-local `users`/`users_roles`/`fm2_process_user_capabilities` SQL branch is byte-equivalent to `origin/main`; no assigned-engineer condition or other legacy behavior is introduced.
- The checklist controller/view remain read composition only. Submission uses the existing `open_confirmed` execution seam, and the confirmed-original application owner remains the sole writer. The return hint changes only the successful 303 destination to the construction-control checklist.

The root-authored focused test is sensitive to the corrected substitution policy: actor 95 is an active substitute with the same local construction-control role and exact checklist/open permissions as the assigned actor, receives the ready form, is denied after live permission revocation with no new facts, then opens successfully after restoration and is recorded as `opened_by_user_id=95`. It also exercises selection-only exclusion, ready visibility and engineer marker, queue/checklist HEAD with empty bodies, read-only fact inventories, ready-plus-PTO exclusion, locked preopening operation and sync-context seams, exact rendered owner intent/action, successful return, one application, enabled continuation and disappearance of the repeat form.

### Evidence

Six package records are GREEN, source-bound to candidate source `a752f9012851a882e681fa6c533ffc2e5fc94261aca278379258fe7f98d7cbc8` and executable source `163e14617cb9c44b5687cb52841c0df8572b39819bc94b3fd7e64efba272ef5a`, with `source_drift=false`:

- preopening substitute flow: `1789415166778544000-e1a480faa9f54ad1a219dfaab1091e51.json`
- existing preopening authorization: `1789415177012416000-a0281a87929b4c6f98158d0035e48833.json`
- existing replay/concurrency owner: `1789415190823759000-ee6e2b8db0c841be91b28be2c8bb14b3.json`
- active shared construction-control queue: `1789415199803430000-323830cee9aa4ae19b4d7dc36ca9cd01.json`
- inspection journey and pagination: `1789415208708162000-e156447550a94d0db3f3d8030da78e53.json`
- inspection boundaries: `1789415225087731000-6ce594d89ea64e2a93dbcb5f8e929df5.json`

Architecture record `1789415237904493000-1e662c6c620249e3879b6e441f25cc2a.json` is exact-source GREEN and reports the PilotHttp global-call qualification and all seven architecture rules passing. The final owner-corrected Gate 3 rereview is `APPROVED`. `git diff --check` is clean. No prohibited local full suite was run. GitHub CI and deployment remain `UNKNOWN`; this review does not represent either as GREEN.

### Final verdict

**APPROVED**

Gate 5 approves the owner-corrected narrow local Yii2 implementation at exact candidate source `a752f9012851a882e681fa6c533ffc2e5fc94261aca278379258fe7f98d7cbc8`. Publication still requires the separately mandated exact-source Quality Graph CI GREEN; deployment remains outside this change.
