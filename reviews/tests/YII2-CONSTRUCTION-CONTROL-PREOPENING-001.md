# YII2-CONSTRUCTION-CONTROL-PREOPENING-001 — Gate 3 test review

- Reviewer: independent Codex reviewer `/root/issue40_gate3`; authored neither specification nor tests
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T173931Z-524fede386/package.json`
- Exact reviewed source: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus reconstructible snapshot, harness source `4f77a9a368031d03f48b8eddc6ccaac6ade4ef92cfefb1ad5dff14a114de1ae5`, executable source `6d4fb06e2f7ac8f35aad0cdcdf220bae975f4bdf28fbf9082a9bb7c60922e811`
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T173931Z-524fede386/snapshot/source.patch`, SHA-256 `4d9da4106313f9b83c1e81e02a496445cda4980b019b781419728a2da38b8ac8`
- Verification plan SHA-256: `d519830e1266dbab0a43265d63eea608f447e285a1833d7bb9280a6ad395cfcf`
- Review date: 2026-09-14

## Complete findings

1. **HIGH — A1's readiness, ownership and filtered-pagination matrix is represented by one positive row only.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-PREOPENING-001.md:15-22`; `tests/Yii2/yii2_construction_control_preopening_001_test.php:12-27`. The new test creates one current accepted original assigned to actor 73 and checks only the default queue page. It does not construct or distinguish pending selection without an original, a stale original from the previous selection lineage, missing/corrupt lineage, ready PTO/completed rows, or another engineer's otherwise-ready row. It also never proves that `total`, pages and offsets are computed after the ready/actor predicate. The retained active-queue test covers working/PTO pagination, not any ready row or ready ownership. An implementation that admits stale/foreign ready objects, suppresses valid ready rows at a boundary, or counts them on the wrong page can pass. Add a sensitive multi-row fixture and real HTTP assertions for every exclusion, exact actor identities, duplicate suppression, and page/count transitions after the complete server predicate.

2. **HIGH — A2's new authorization and locked-checklist boundary is not behaviorally covered.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-PREOPENING-001.md:24-28`; `tests/Yii2/yii2_construction_control_preopening_001_test.php:29-39`; mapped `tests/Yii2/yii2_preopening_authorization_001_test.php:76-101`. The candidate only exercises the assigned engineer with both grants and treats one `data-enabled="false"` substring as proof of the lock. It never requests the ready queue/checklist as a foreign engineer, never requests it as the assigned engineer lacking `installation.open`, never proves the opening form is absent for either actor, and never attempts checklist operations/photos/sync-context/offline mutation before opening. The inherited authorization test checks capability revocation on the existing object-card execution flow; it does not exercise visibility or form composition on the new construction-control ready paths. Add server-side foreign/no-grant cases with exact status/resource isolation, zero-fact/file assertions, form absence, and actual preopening mutation attempts through every enabled checklist alias.

3. **HIGH — the test does not inspect the generated opening command, so the novel form-to-owner seam is not sensitive.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-PREOPENING-001.md:26,32-34`; `tests/Yii2/yii2_construction_control_preopening_001_test.php:31-40`. The test submits hard-coded `requestId`, `orderId`, `revisionId` and `sequence` values instead of extracting them from the rendered form; only action/date/button markers and CSRF are read from HTML. Consequently a form with stale/wrong IDs, no UUIDv4 request ID, wrong execution action/HTTP method, or a controller that does not carry the actual checklist return target can pass because the test constructs a valid command itself. Parse and assert the exact form action/method and hidden current IDs, validate a fresh UUIDv4, submit those rendered values, and observe the real return contract rather than supplying the expected command independently.

4. **HIGH — the new flow's rejection, replay and concurrency outcomes are not covered at the construction-control seam.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-PREOPENING-001.md:32-34`; `tests/Yii2/yii2_construction_control_preopening_001_test.php:35-51`; mapped `tests/Yii2/yii2_preopening_concurrency_001_test.php:26-29`. The candidate checks one successful open. It does not cover document-date/today boundaries, stale order/revision/sequence/date, changed-intent replay, foreign object, capability revocation between GET and POST, infrastructure rollback, uncertain response reread, or same/different-intent concurrent submissions while also checking the construction-control return path and post-state UI. The retained concurrency test invokes the generic execution path and counts owner facts, but does not exercise the newly composed ready checklist/form/return behavior. Add representative inherited rejection/failure cases through values emitted by the new form, full no-partial-facts inventories, and paired replay/concurrency observations that end in the truthful queue/checklist state.

5. **MEDIUM — A4's ready-resource read envelope and safety claims are missing from both test and plan.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-PREOPENING-001.md:36-38`; `tests/Yii2/yii2_construction_control_preopening_001_test.php:19-33`; prepared `verification-plan.json`. Queue GET/HEAD is checked, but ready-checklist HEAD, guest behavior, exact `construction_control.read` denial, unknown/foreign 404 isolation, corrupt/dependency 503, repeat-read determinism and hostile stored-string escaping are not. Existing GREEN controls exercise working checklist/queue fixtures and cannot detect a separate preopening branch leaking a ready object, emitting an unsafe label, mutating facts, or skipping integrity admission. The plan also omits the nearest `tests/Yii2/yii2_inspection_boundaries_001_test.php` boundary control. Add ready-state cases for the complete method/auth/integrity/safe-rendering envelope and map the retained boundary test.

6. **MEDIUM — verification completeness is overstated by the two broad acceptance mappings.** Locations: `openspec/changes/construction-control-preopening/verification-input.json`; prepared `verification-plan.json`. `ready-queue-opening-and-continuation` maps the new happy-path RED plus inherited authorization/concurrency GREEN tests as though they cover the complete A1-A4 matrix, while those retained tests do not observe the new ready projection, form composition, preopening mutation lock or construction-control return path. `adjacent-active-queue-and-inspection` omits the existing inspection-boundaries control despite A4 explicitly promising its 404/503/escaping/runtime boundaries. Split the mapping into observable acceptance families, add the missing tests above, and regenerate a package whose evidence is classified only for outcomes each command actually observes.

## Evidence assessment

The retained primary record `1789407528567385000-2d9d1952e25c42ea9528dfe2004df72b` is source-bound with `source_drift=false` and fails at `yii2_construction_control_preopening_001_test.php:22`: expected one ready object, actual zero. The fixture had already completed selection, accepted-original setup, authentication and the real Yii HTTP request, so this is an intended missing-production RED rather than setup failure. The four mapped inherited controls are retained GREEN, but they do not cure the sensitivity gaps above.

`openspec validate construction-control-preopening --strict` is GREEN, `git diff --check` is clean, and snapshot/plan hashes match the package. No local full `make test` or `make verify` was run. CI and deployment remain `UNKNOWN`; neither is approval or GREEN.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return the complete candidate to Gates 1/2, add the bounded negative readiness/pagination, ready-route authorization/lock, rendered-form, rejection/replay/concurrency and safety matrix, regenerate the verification package, retain fresh intended RED evidence, and request independent Gate 3 rereview.

---

## Gate 3 correction rereview — 2026-09-14

- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T174422Z-f65ac5f691/package.json`
- Exact corrected source: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus reconstructible snapshot, harness source `ccd070d678b611f756aa25ba035fe44f20fb0a1b75f978ea53a8ccf624a1a210`, executable source `bfca17d612ab9845f707adc0dd1db68961d523342fc41dff8b2cfb28130eb636`
- Snapshot patch SHA-256: `91403ee7e312d000bb1fed8bd28d46bd7809817afd6d98b6a52e8cf4168ca0ff`
- Verification plan SHA-256: `633bfade2c2c2997b673c8cac9ae11c8f75d6ba1fb6237b662dc3aa4492a51ae`

### Prior findings disposition

- Finding 2 is partially resolved: the test now attempts a real preopening checklist operation and sync-context read, requires 403, and compares the complete database inventory; it also checks form absence after grant removal. The foreign-actor and between-GET-and-POST authorization portions remain defective as finding 2 below.
- Finding 3 is partially resolved: hidden owner IDs and UUIDv4 are now parsed and validated. The rendered action is not used for submission, so the return-path composition remains defective as finding 3 below.
- Finding 4 is resolved for the deliberately narrowed contract: rejection/replay/concurrency outcomes are explicitly inherited unchanged from the existing owner and retained authorization/concurrency tests; this slice now owns only accurate rendered intent and absence of a bypass writer.
- Finding 5 is partially resolved: the nearest inspection-boundaries test is mapped and GREEN. Ready checklist HEAD remains uncovered as finding 4 below.
- Finding 6 is resolved to the extent allowed by the narrowed contract and added boundary mapping. The retained tests now claim only inherited owner and inspection behavior rather than new ready-branch outcomes.
- Finding 1 remains materially open as finding 1 below.

### Complete correction findings

1. **HIGH — the explicit ready eligibility exclusions still have no sensitive test.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-PREOPENING-001.md:15-22`; `tests/Yii2/yii2_construction_control_preopening_001_test.php:12-27`. The narrowed contract still says a selection without an accepted current original and a case with `pto_act` do not enter as ready, and that the accepted original must apply to the current selection lineage. The corrected test still creates only one eligible positive row. A reader that admits every assigned preparation case regardless of original/PTO/current lineage passes. Add at least isolated real-HTTP rows for no accepted original, accepted original made stale by a later selection, and ready-plus-PTO, alongside the eligible row; assert exact server-rendered identities and no duplicate of the eligible object. Existing working/PTO and opening-owner tests do not reach this new ready predicate.

2. **HIGH — the supposed foreign-user check is satisfied by global RBAC denial and does not prove server-side ownership filtering; capability revocation between GET and POST is still absent.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-PREOPENING-001.md:22,28`; `tests/Yii2/yii2_construction_control_preopening_001_test.php:47-55`; `tests/Yii2/PreopeningFixture.php:38-45`. Actor 95 has role 5, whose fixture grants only `objects.read`; it has neither `construction_control.read` nor checklist read. The test does not assert response statuses, so a queue/checklist 403 with an arbitrary body satisfies both substring assertions without exercising a foreign actor who is otherwise admitted to the read seam. Give a different assigned-ready fixture actor the exact read grants, require 200 and exact absence of object/form for actor 95, and assert facts remain unchanged. Separately, the test removes `installation.open`, performs only a GET, then restores the grant before POST; it never performs the contract's between-GET-and-POST revoked submission. Submit the previously rendered command while the grant is absent and assert exact denial plus no application/opening/checklist facts, then restore the grant for the successful path.

3. **HIGH — the test validates one form action but submits to a different URL, leaving the promised return-path composition untested.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-PREOPENING-001.md:26,32-34`; `tests/Yii2/yii2_construction_control_preopening_001_test.php:31,57-62`. The asserted rendered action is `/pilot/objects/4512/execution?return=construction-control`, but `$f->form()` posts to `/pilot/objects/4512/execution` without that query. Thus the test can pass only if the non-form route happens to redirect to construction control, while an implementation that correctly depends on the rendered return hint is never exercised. Parse the form action and submit to that exact URL with the parsed hidden intent. This also makes the 303 location assertion genuinely sensitive to the ready checklist form.

4. **MEDIUM — ready checklist HEAD remains untested despite being an explicit public seam and read-only promise.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-PREOPENING-001.md:11,38`; `tests/Yii2/yii2_construction_control_preopening_001_test.php:25-33`. Only queue HEAD is exercised before opening. The inherited inspection test covers HEAD for a working checklist, so a separate preopening branch that rejects HEAD, emits a body, skips ready admission, or writes can pass. Add assigned ready-checklist HEAD with exact `200`/empty body and unchanged facts; add the corresponding foreign/no-grant behavior if the branch differs by authorization.

### Evidence and correction verdict

Record `1789407806605001000-dfb74c5c734148b782f7529e6f50dac1` is a valid, source-bound intended RED at the unchanged absent-ready-row assertion with `source_drift=false`. All five mapped inherited controls are source-bound GREEN, including the newly added inspection-boundaries test. `openspec validate construction-control-preopening --strict` is GREEN, `git diff --check` is clean, and package hashes match. These results do not supply the missing sensitivity above. No local full suite was run; CI and deployment remain `UNKNOWN`.

`CHANGES_REQUESTED`

Gate 4 remains blocked. Correct findings 1-4 together, capture a fresh exact-source package and intended RED, and request another independent Gate 3 rereview.

---

## Third Gate 3 correction rereview — 2026-09-14

- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T174747Z-b8c41327f5/package.json`
- Exact corrected source: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus reconstructible snapshot, harness source `5472ce9d9a472c8be0c869241e92e68d137ebf760d8c7f511c45b7e3f5109ec2`, executable source `292539b4984208189ac1913ef547f4af610b5b69ea403ee7ba4efed7f3a28b8e`
- Snapshot patch SHA-256: `d0c0338a345e6802c07097aad6a0a808c00f190f66e06ec90e947c3b629af5ca`
- Verification plan SHA-256: `88feb13886dbaf55efb5c22b74ad30ce678db0da94e0be684e6882d77c3c25c5`

### Prior findings disposition

1. Resolved. The real queue seam now observes selection-without-original exclusion before the positive transition and PTO exclusion after authoritative readiness. Exact current/stale lineage classification is explicitly owned by the unchanged, reviewed preopening projection; the new slice is normatively forbidden from introducing a second lineage predicate. The public positive/no-original/PTO assertions are sensitive to whether the queue consumes that authoritative classification.
2. Resolved. Actor 95 receives actual `construction_control.read`, `checklist.read` and `installation.open` grants, both read seams must return 200, the foreign ready row/form must be absent, and the complete fact inventory must remain unchanged. Separately, the previously rendered command is submitted while `installation.open` is revoked and must return 403 with no facts before the grant is restored.
3. Resolved. The test parses the rendered form action and uses that exact URL for both the revoked and successful submissions; the success assertion therefore observes the form-owned construction-control return path.
4. Resolved. Ready checklist HEAD must return 200 with an empty body and preserve the complete fact inventory.

### Complete findings

None.

The corrected candidate now covers the bounded novel seam from selected-only exclusion through ready queue/checklist, inert mutations, actor-filtered visibility, current rendered owner intent, revoked submission, successful opening, durable single facts, return navigation and opened continuation. Existing source-bound controls retain the unchanged working queue, inspection boundaries, opening authorization and concurrency/replay owners. Expected IDs, form values, statuses and fact counts come from contract/fixture facts rather than planned implementation details. The test remains deterministic and isolated from production systems.

Record `1789408037207553000-b3f14337c27848ce9bf1baec2b7f201b` is source-bound with `source_drift=false` and reaches the intended missing behavior after the selected-only exclusion passes: ready object expected once, actual zero at line 27. This is a valid intended RED, not setup failure. The five mapped inherited controls are GREEN at the same candidate source. `openspec validate construction-control-preopening --strict` is GREEN, `git diff --check` is clean, and snapshot/plan hashes match. No local full suite was run; exact-source CI and deployment remain later/`UNKNOWN` and are not implied by this review.

### Verdict

`APPROVED`

Gate 3 passes for exact source `5472ce9d9a472c8be0c869241e92e68d137ebf760d8c7f511c45b7e3f5109ec2`. Gate 4 may proceed from this reviewed contract/test package plus the appended review metadata. Any later behavioral specification or test-expectation change requires independent Gate 3 delta review.

---

## Gate 3 planned-path delta review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T175224Z-81f3e9b2c3/package.json`
- Exact source: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus snapshot, harness source `20ac43d4db1d08fa862b4782d66b3492daf9262e80e402d5271b55c566508cd7`, executable source `221472a28240e7afa299c09ba83481b8a74af0c36fecb94ea54077ad8d49c055`
- Snapshot patch SHA-256: `2c300871eaf9242a5b9cbc3e7769c91d9a2e3eb41514b3822f5f17d25d3fdc2c`
- Verification plan SHA-256: `4f650ebd4788f781c7f9818b8043d5246ea6f718c2dedc94fb4f59b235a51928`

### Findings

None.

Adding `app/YiiRuntime/Controllers/ExecutionController.php` to `planned_paths` is necessary and complete for the already approved behavior: the rendered checklist form submits the current owner intent to the existing execution route with a construction-control return hint, and that controller owns interpretation of the hint and the resulting redirect. The generated plan now includes it as an application-code boundary. It does not alter the normative contract, test expectations, acceptance mappings, selected CRITICAL lane, required categories, or required Gate 3/final reviews.

The accompanying task checkbox and accumulated review record are delivery metadata only. The source-bound intended RED and five inherited GREEN records were refreshed at this source with no drift. `openspec validate construction-control-preopening --strict` is GREEN, `git diff --check` is clean, and package hashes match. CI and deployment remain `UNKNOWN`.

### Verdict

`APPROVED`

The prior Gate 3 approval remains valid with this planned-source correction at exact source `20ac43d4db1d08fa862b4782d66b3492daf9262e80e402d5271b55c566508cd7`. This verdict is limited to the verification-input/source-path delta and does not approve implementation, Gate 5, CI or deployment.

---

## Gate 3 owner-admission/form-parser delta review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T180552Z-8272d12857/package.json`
- Exact clean-production-baseline candidate: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus snapshot, harness source `264ee08f87545402f276bda8d8c44445d076e115e4ce157b4f98fe5ae21f6a68`, executable source `a42331838f6a34d0dc27a0cacbf3f529147edaad0f3845e9aaedbee8bb5b988f`
- Snapshot patch SHA-256: `033067d9a31ce84ea189e695d9ed0e862cd4b02911d33a89805417d16d64c676`
- Verification plan SHA-256: `76422120ac13cf7c739ca1d40cafb842fb606665d588c39878dd67b1e718493e`

### Complete findings

None.

The corrected parser first isolates complete form blocks, selects exactly the one containing `open_confirmed`, requires one match, extracts its POST action regardless of attribute order, and asserts the exact construction-control return URL before using it for revoked and accepted submissions. This removes the prior risk of accidentally parsing another page form. Hidden current intent, UUIDv4, CSRF, no-grant rejection and final redirect checks remain intact.

The added engineer `installation.open` process capability complements the Yii role permission and makes the accepted-path assertion exercise the existing confirmed-original owner's own authorization boundary. The design now states the bounded policy change explicitly: the same owner remains the only writer, but an assigned construction-control engineer with exact opening authority must be admitted and all assignment/original/eligibility checks remain inside its transaction. This is required for the actor named by the normative spec and does not introduce a new command or writer.

Adding `app/AssignmentOrderComposition/MariaDbOriginalOpening.php` to planned paths is therefore necessary and closes the implementation/review scope. The generated plan retains the CRITICAL lane, Gate 3 plus final review, all prior acceptance mappings and applicable neighboring checks. Production bytes are absent from this package as expected because executor WIP was stashed; this review approves only the corrected spec/test/design/plan candidate.

Record `1789409123465151000-9e8334144bb849e683b28caefecd6084` is source-bound intended RED at baseline ready-row absence with `source_drift=false`; setup and the selected-only exclusion completed first. All five mapped neighboring tests are GREEN at the same source. `openspec validate construction-control-preopening --strict` is GREEN and `git diff --check` is clean. No local full suite was run; CI and deployment remain `UNKNOWN`.

### Verdict

`APPROVED`

Gate 3 remains approved for exact source `264ee08f87545402f276bda8d8c44445d076e115e4ce157b4f98fe5ae21f6a68`, including this owner-admission/form-parser/planned-path delta. Gate 4 may implement the bounded candidate. Any later behavioral spec or test-expectation change requires independent delta review.

---

## Final upstream-owner Gate 3 delta review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T181104Z-20f968013e/package.json`
- Exact clean-production-baseline candidate: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus snapshot, harness source `e7668acac1719acbc60b19b9a3eeecf8cbc683ae27e08184d0d9c92f4ac2ac8b`, executable source `e350c240c3c07abfe2037ae933b113b997d23876fdf14e812d2ee59dcd779ce0`
- Snapshot patch SHA-256: `a55043ff85cdb030161651fe36a5b46e0c6935025b765f6ecc516e092d6f56f0`
- Verification plan SHA-256: `4373c601eb48c88b8b37b4b52f9229f4a22db1bdb8fce70ed65661c60153bba1`

### Complete findings

None.

The authorization matrix is now sensitive to both required outcomes. Actor 73 is the selected engineer and has the Yii `installation.open` grant plus the process capability needed by the upstream owner; the final submission through the exact rendered action must succeed and record actor 73. Actor 95 receives all local read/open grants and an active `construction_control_engineer` role, reaches both read seams with HTTP 200, then submits a distinct request through the same rendered owner command. It must receive the owner's domain rejection (`422`) and preserve the complete fact inventory solely because it is not the assigned engineer. An implementation that admits engineers globally, or one that retains FKR/manager-only admission and blocks the assigned engineer, cannot satisfy both halves.

The form-block parser remains scoped to the sole form containing `open_confirmed`, verifies its exact action, and uses that action for foreign, revoked and successful requests. The design preserves one writer and explicitly limits the authorization expansion to assigned construction-control engineers with exact opening permission while retaining transactional assignment/original/eligibility checks.

Adding `MariaDbAssignmentOrderApplicationOperation.php` and `MariaDbConfirmedOriginalOpening.php` alongside `MariaDbOriginalOpening.php` completes the upstream implementation frontier: the execution controller delegates through the confirmed-original orchestration/application operation into the opening owner, so admission changes in any of those owners are now visible to the plan and final review. Acceptance mappings, CRITICAL lane, required categories and independent final review remain unchanged.

Record `1789409432180241000-52751e714401442084ffe23fa01545e3` is source-bound intended RED at the baseline ready-row absence with no drift; the five neighboring controls are GREEN at the same source. `openspec validate construction-control-preopening --strict` is GREEN, `git diff --check` is clean, and package hashes match. The later assertions are statically coherent with the already reviewed real HTTP fixture; Gate 4 GREEN must execute the complete file. No local full suite was run. CI and deployment remain `UNKNOWN`.

### Verdict

`APPROVED`

Gate 3 remains approved for exact source `e7668acac1719acbc60b19b9a3eeecf8cbc683ae27e08184d0d9c92f4ac2ac8b`, including the assigned-versus-foreign owner-admission test and complete upstream planned paths. Gate 4 may proceed. Any behavioral spec/test change requires a new independent Gate 3 delta review.

---

## Fixture-only Gate 3 delta review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T181437Z-c0676260d1/package.json`
- Declared candidate source: `a772be7e1653e50d9a416b73826ac07ae9a681c50bfd1ded8bcdda4daedfdbb3`, executable source `dd526bac33d0ad2bfb9190f70f2a382563a750f3cb5643d520595daaddb88b64`
- Snapshot patch SHA-256: `018f48b17a366581dadc2d920e6ed4a454c4cb55ac7f4744335765c28e0a41e5`
- Verification plan SHA-256: `32167d0e0e2b74e186d010b3c8ba2d0afdfaef0121884a775367f3b6eb09e259`

### Complete findings

1. **HIGH — assignment is not the sole authorization difference between actors 73 and 95.** Locations: `tests/Yii2/yii2_construction_control_preopening_001_test.php:10-11,68-82`. Both actors now share active role 2 and its exact Yii read/open grants, which correctly avoids the unique role-code collision. However only actor 73 has a row in `fm2_process_user_capabilities` for `installation.open`; actor 95 does not. The design and preceding delta explicitly treat that process capability as part of upstream-owner admission. A production owner that requires this capability for all engineers can return the expected 422 for actor 95 before evaluating assignment, while still accepting actor 73, so the test would pass without enforcing the intended assigned-engineer predicate. Give actor 95 the same `installation.open` process capability (and any other owner-admission fact actor 73 has), then retain the 422/no-facts assertion so assignment identity is genuinely the only material difference.

2. **HIGH — the package is not the declared clean production baseline or a fixture-only delta.** Locations: package `delta.patch`; worktree `app/AssignmentOrderComposition/MariaDbAssignmentOrderApplicationOperation.php` and `app/AssignmentOrderComposition/MariaDbConfirmedOriginalOpening.php`. The reconstructible delta includes executor production changes adding `authorizedForOpening()` and wiring it into the confirmed-original owner, and the live worktree also contains the remaining implementation files. This violates the requested bounded review premise and mixes Gate 4 authorship into the exact source being presented for a Gate 3 test delta. Stash all executor production WIP, prepare a fresh source-bound package containing only the fixture/spec/design/plan/review metadata delta over the previously approved clean production baseline, and rerun intended RED/neighbors. Production code belongs in the later Gate 5 package and is not approved here.

### Evidence and verdict

The package's primary test is recorded as intended RED at ready-row absence and five neighbors are GREEN with no reported source drift. Those outcomes do not resolve either sensitivity or source-purity finding. The snapshot and plan hashes match and `git diff --check` is clean. No local full suite was run; CI/deployment remain `UNKNOWN`.

`CHANGES_REQUESTED`

The prior clean-baseline Gate 3 approval remains intact, but this fixture delta is not approved. Equalize actor 95's process admission facts, remove production WIP from the Gate 3 source, prepare fresh evidence, and request a bounded rereview.

---

## Fixture-only Gate 3 correction rereview — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T181704Z-e3e106a2e2/package.json`
- Exact clean-production-baseline candidate: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus snapshot, harness source `c03b53a96ba3e415b111614a21f2ff24fdd4d2e7ee93a61aa5f044ed3e872138`, executable source `4c11f13c0a17603c0d0a12e50555de020ee9e6d1508f75719a1f083a5210026b`
- Snapshot patch SHA-256: `0fa5c27577c4ca6c286916e19930adf96c60f4e7078a6e1a544191fbec53558c`
- Verification plan SHA-256: `140653cf370fddd4ef34a71c0ad0b072286088f74ad1c8337f036cf9f8bcb136`

### Prior findings disposition

1. Resolved. Actors 73 and 95 now both have the same active local role 2, the same `construction_control.read`, `checklist.read` and `installation.open` grants, and matching `fm2_process_user_capabilities.installation.open` facts. Both reach the real read/command transport with equivalent admission inputs. Only actor 73 is the engineer in the selected composition, so actor 95's required 422 with unchanged facts is sensitive to the upstream owner's assignment check while actor 73's later 303/durable opening proves the positive half.
2. Resolved. `git status --short` at review shows no tracked production modifications; only the untracked OpenSpec/spec/test/review candidate remains. The package delta contains the test fixture correction and accumulated review record, not executor production bytes. The reconstructible source is therefore a valid clean production baseline for Gate 3.

### Complete findings

None.

The change from a newly renamed role to the existing role 2 also removes the fixture's unique-role-code collision risk and makes the comparison direct: same role row, same grants, same process opening capability, same rendered command and current original, different actor-to-composition assignment. The exact form parser, no-facts inventory, revoked grant case, assigned success case and all previously approved coverage remain unchanged.

Record `1789409793078800000-be08dd4893414cb3bc396ac8687af84c` is source-bound intended RED at the baseline ready-row absence with no drift; all five neighboring tests are GREEN at the same source. Package/snapshot/plan hashes match and `git diff --check` is clean. No local full suite was run; CI and deployment remain `UNKNOWN`.

### Verdict

`APPROVED`

Gate 3 is approved for exact source `c03b53a96ba3e415b111614a21f2ff24fdd4d2e7ee93a61aa5f044ed3e872138`, including the corrected assigned-versus-foreign fixture. Gate 4 may proceed from this clean-baseline package. Later behavioral spec/test changes require independent delta review.
