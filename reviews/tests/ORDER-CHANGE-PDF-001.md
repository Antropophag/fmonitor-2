# Test review: ORDER-CHANGE-PDF-001

- Reviewer: `Codex agent /root/test_review` (independent; did not author the specification, test, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-30`
- Reviewed commit: working tree at HEAD `513363b02570ee25a5d5c699630803a544f7cced`
- Specification: [`specs/ORDER-CHANGE-PDF-001.md`](../../specs/ORDER-CHANGE-PDF-001.md)
- Public seam: `InstallationProcess::prepareAssignmentOrder(...)`; observable new PDF artifact and persisted installer composition for the new version
- Red command and intended failure: `php tests/InstallationProcess/production_pdf_assignment_order_renderer_test.php` — `TestFailure: Combined PDF must contain semantic text marker: Перемещён` at helper line 42, invoked at line 64
- Initial verdict: `CHANGES_REQUESTED`
- Current verdict: `APPROVED`

## Re-review after public-seam correction (2026-08-30)

- Re-review verdict: `CHANGES_REQUESTED`
- The new `order_change_pdf_001_test.php` resolves two central blockers. It invokes `InstallationProcess::prepareAssignmentOrder(...)` against a registered version-1 fixture and independently requires the renderer input to contain Sidorov (`1043`) as `Работа` and removed Ivanov (`1042`) as `Перемещён`. It also observes the resulting process and requires version 2's persisted active composition to be exactly `[1043]`.
- The support environment is used only as a recording boundary for the input sent through the public renderer seam; the expected rows and statuses are literal test values rather than values copied from production output.
- The focused RED is deterministic and now reaches the missing orchestration behavior through the named public seam. Exact reproduction:

```text
php tests/InstallationProcess/order_change_pdf_001_test.php
PHP Fatal error:  Uncaught TestFailure: PDF input must correlate retained and removed installers with independent statuses
Expected: array (
  0 => array (... 'tabId' => 1043, ... 'workStatus' => 'Работа'),
  1 => array (... 'tabId' => 1042, ... 'workStatus' => 'Перемещён'),
)
Actual: array (
)
```

  Exit code: `255`; helper line 36, assertion line 16.
- **Remaining blocker — rendered-row correlation is still not observed.** The public-seam test proves the correct correlated data reaches the renderer boundary, while `production_pdf_assignment_order_renderer_test.php` only searches the resulting PDF globally for the two status words. Together they would still pass if the PDF swapped the two statuses, printed the status words as a legend, or otherwise failed to associate Иванов with `Перемещён` and Сидоров with `Работа`. The PDF assertion must observe row-level association (or an equivalent independently decoded structure), not only input correlation plus global markers.
- **Remaining blocker — the specified no-removal branch is still absent.** No test prepares a later version with the same selected composition and proves there is no `Перемещён` document row/status. The unchanged baseline renderer test does not assert absence of that value.
- **Remaining blocker — historical artifact immutability is still absent.** Version 1 is seeded with `artifacts => []`, so the test cannot detect rebuilding, replacement, or mutation of an already formed artifact when version 2 is prepared. Capture a non-empty version-1 artifact identity/bytes and require it to remain unchanged after the public command.

The orchestration RED is materially improved, but Gate 3 remains unapproved until these remaining specification outcomes are observable.

## Third review after correlation and history additions (2026-08-30)

- Third-review verdict: `CHANGES_REQUESTED`
- The renderer test now requires the decoded-stream order `Сидоров Сергей Сергеевич < Работа < Иванов Иван Иванович < Перемещён`. With each fixture name appearing once in the appendix input, this is sufficiently sensitive to swapped person/status rows and closes the rendered-correlation blocker.
- The public-seam fixture now seeds non-empty immutable version-1 artifact metadata and requires that exact metadata to remain on version 1 after preparing version 2. The independent SHA identity is sufficient evidence that the earlier artifact was not replaced or rebuilt through this command path.
- The public-seam no-removal branch correctly requires both selected installers to reach the renderer input with `Работа`; it proves the comparison layer does not invent an excluded installer.
- Both REDs are deterministic and occur before implementation:

```text
php tests/InstallationProcess/order_change_pdf_001_test.php
TestFailure: Change without removal must mark the complete selected composition as working
Expected: array ('Работа', 'Работа')
Actual: array ()
```

```text
php tests/InstallationProcess/production_pdf_assignment_order_renderer_test.php
TestFailure: Combined PDF must contain semantic text marker: Перемещён
```

- **One remaining blocker — PDF absence in the no-removal case is not tested.** The specification says that when nobody is excluded, the PDF contains only the selected composition. The new public-seam assertion stops at the recorded renderer input, and the renderer test exercises `documentInstallers` only when it includes a moved installer. A renderer that always appends a stale or hard-coded `Перемещён` row would satisfy every current assertion. Render a `documentInstallers` list containing only the two `Работа` rows and require the decoded PDF to contain both selected names/status rows and no `Перемещён` marker (preferably also no extra installer row).

All earlier public-seam, saved-composition, row-correlation, independent-value, deterministic-RED, isolation, and historical-identity blockers are resolved. Gate 3 requires only the focused no-removal PDF absence assertion and a fresh review.

## Final no-removal PDF re-review (2026-08-30)

- Final verdict: `APPROVED`
- The final blocker is resolved. The renderer test now supplies an explicit two-installer `documentInstallers` composition in which both independent rows have `Работа`, renders a real PDF, requires both installer names and the working status to be present, and independently requires the decoded PDF to contain no `Перемещён` marker.
- This negative assertion is sensitive to a stale, hard-coded, or accidentally retained moved-installer row. Together with the change-order row-order assertion, it covers both branches of the specification at the production renderer boundary.
- The public process test independently covers comparison with the previous registered version, exact status-correlated renderer input, exact persisted version-2 active composition `[1043]`, and preservation of version-1 artifact identity. Expected values remain fixture literals and are not derived from implementation output.
- Setup is deterministic and isolated in the in-memory environment. The PDF assertions use fixed document data and decoded semantic streams; no clock, network, mutable external source, or implementation-owned expected-value generator is involved.

Exact renderer RED reproduced before implementation:

```text
php tests/InstallationProcess/production_pdf_assignment_order_renderer_test.php
PHP Fatal error:  Uncaught TestFailure: Combined PDF must contain semantic text marker: Перемещён
Stack trace:
#0 tests/InstallationProcess/production_pdf_assignment_order_renderer_test.php(74): assertPdfTextMarker()
```

Exit code: `255`. The failure occurs on the required change-order status before the new no-removal assertions; the complementary public-process RED was recorded in the preceding review. The reviewed tests are approved for Gate 4 implementation.

## Working-state amendment review (2026-08-30)

- Amendment verdict: `CHANGES_REQUESTED`
- The new assertion is directly sensitive to the reported status regression. Its fixture begins as an opened installation in `working`, invokes the public `InstallationProcess::prepareAssignmentOrder(...)` seam for version 2, and independently requires the resulting `processState` to remain the literal `working`.
- The exact RED was reproduced:

```text
php tests/InstallationProcess/order_change_pdf_001_test.php
TestFailure: Preparing a change must keep an opened installation in construction control
Expected: 'working'
Actual: 'needs_assignment_change'
```

  Exit code: `255`; assertion line 20. The failure is deterministic and caused by the current change-order state transition, not by PDF rendering, fixture setup, or an external dependency.
- The expected state is not derived from implementation output. It is a fixed value from the amended specification, and the initial fixture independently establishes the opened case with `installationOpened => true` and `checklistAvailable => true`.
- **Blocking traceability gap — post-registration behavior is not exercised.** The amendment explicitly requires both preparation **and registration** to retain `working`, but the test stops after preparation and never invokes `confirmOrderRegistration(...)`. An implementation could satisfy the new assertion and still transition the opened object during registration without failing this test.
- **Blocking observability gap — queue/checklist availability is not asserted.** The amended specification says the object remains in the construction-control queue and the checklist stays available during preparation and after registration. The test asserts only `processState`; it does not require `installationOpened`/`checklistAvailable` after prepare and after registration, nor observe the relevant queue projection if that is a separate public seam.
- Existing PDF/status correlation, active-composition replacement, no-removal, and historical-artifact assertions remain independently valid and approved. Only the new working-state amendment remains unapproved.

Required amendment changes:

1. After the prepare assertion, authorize and invoke `confirmOrderRegistration(...)` for version 2 and require its public result plus the resulting process projection to retain `processState === 'working'`.
2. Require `installationOpened === true` and `checklistAvailable === true` both after preparation and after registration. If construction-control queue membership is not completely defined by those public process fields, assert it through the actual queue seam as well.
3. Preserve the exact preparation RED and request fresh independent review before implementing the amended behavior.

## Working-state amendment final re-review (2026-08-30)

- Final amendment verdict: `APPROVED`
- The revised public-seam test authorizes both commands, prepares change-order version 2, and then confirms registration of that exact version. It observes the process after each command and independently requires the exact tuple `['working', true, true]` for `processState`, `installationOpened`, and `checklistAvailable`.
- These fixed expectations come directly from the amended specification and initial opened-case fixture; they are not derived from command results or production state-transition logic. Registration success is separately required before its postcondition is inspected.
- `working` plus the preserved opened/checklist flags are the process projection that defines continued construction-control handling in this module; no separate queue adapter is involved in the reviewed behavior seam.
- The exact focused RED was reproduced after the amendment:

```text
php tests/InstallationProcess/order_change_pdf_001_test.php
TestFailure: Preparing a change must keep an opened installation in construction control with checklist available
Expected: array ('working', true, true)
Actual: array ('needs_assignment_change', true, true)
```

  Exit code: `255`; assertion line 20. All preceding change-order comparison, correlated document rows, active-composition replacement, and acceptance assertions pass before this intended state-transition failure.
- The same fixture will proceed to registration only after preparation is corrected, where it is sensitive to a second state regression and loss of either availability flag. Fixed time, IDs, version, registration number, and in-memory state keep setup deterministic and isolated.

The working-state amendment now covers preparation, registration, construction-control state, and checklist availability. Gate 3 is approved for implementation.

## Object-card reader policy review (2026-08-30)

- Reader-policy verdict: `APPROVED`
- The extracted policy test directly covers the reader condition needed while an opened object remains `working`: a prepared version-2 `change` order is accepted only when its `previous_assignment_order_id` identifies a registered earlier order present in the supplied order history.
- The two negative cases reject the important near misses independently: a prepared initial version cannot masquerade as an opened working change, and the existence of some unrelated registered earlier order is insufficient when it is not the change order's referenced predecessor.
- IDs, versions, kinds, statuses, predecessor relation, and boolean expectations are fixed literals. No expected value is obtained from database rows, production query results, or another call to `ObjectCardProcessStatePolicy`.
- The focused test has no database, clock, filesystem, or network dependency and is deterministic. It complements the already reviewed public process test: the process test locks the writer invariant, while this test locks the card reader's acceptance of the legitimate prepared-change intermediate state.
- The live card failure was reproduced before policy extraction. At the time of this review, the focused command is green against the concurrently added policy implementation:

```text
php tests/InstallationProcess/object_card_working_change_policy_test.php
PASS object card working change policy
```

  Therefore this focused command is regression evidence for the extracted condition, not a new standalone RED record. Its approval relies on the previously captured live bug/working-state RED for the originating behavior slice.
- Existing registered-order card behavior remains covered by the broader object-card tests; this focused test intentionally distinguishes only the newly admitted prepared-change case and its rejected lookalikes.

No changes are required for the reader-policy test. The ORDER-CHANGE-PDF-001 test suite remains `APPROVED`.

## Findings

- **Specification traceability is incomplete at the named public seam.** The new assertions call `ProductionPdfAssignmentOrderRenderer::renderAssignmentOrder(...)` directly and hand-build `documentInstallers`. They do not establish and register version 1, invoke `InstallationProcess::prepareAssignmentOrder(...)` for version 2, or prove that the system derives the removed installer by comparing against the previous registered version. An implementation that accepts caller-supplied presentation rows while the process never performs the required comparison would pass this test.
- **The saved-composition outcome is untested.** The specification requires version 2 to persist only tab ID `1043`; the test observes no process projection or persistence boundary. An implementation could leave `1042` in the active assignment, include both installers in subsequent calculations, or otherwise append instead of replace while satisfying both PDF marker assertions.
- **Person/status correlation is absent.** The test merely finds `Перемещён` and `Работа` somewhere in the decoded PDF streams. It does not prove that Иванов (`1042`) is paired with `Перемещён` and Сидоров (`1043`) with `Работа`. A legend, unrelated prose, reversed statuses, duplicated statuses, or rows rendered from the wrong installer source can satisfy the current assertions.
- **The no-removal branch is not asserted.** The baseline render does not require absence of `Перемещён`, so it does not enforce the specification rule that a version with no excluded installers contains only the selected composition.
- **Append-only artifact history is not observed.** No assertion captures version 1 artifact bytes/hash before version 2 and verifies that the earlier artifact remains unchanged after creating the change order.
- **The RED is deterministic but too narrow.** The exact failure demonstrates only that the current direct renderer ignores the extra handcrafted `documentInstallers` field. It does not expose the missing process comparison or replacement persistence behavior at the public seam.
- **Expected status values are independent literals from the approved behavior example.** The new assertions do not compute `Работа` or `Перемещён` from implementation output. Existing fixture data and PDF decoding remain deterministic and isolated, but that strength does not compensate for the missing public-seam and correlation coverage.

## Required changes

1. Drive the behavior through `InstallationProcess::prepareAssignmentOrder(...)`: establish and register version 1 with `1042` and `1043`, then prepare version 2 with only `1043`, using independent fixture expectations.
2. Assert the persisted version-2 composition contains exactly `1043` and excludes `1042`, while the removed installer is absent from the active composition used after the command.
3. Inspect the version-2 PDF strongly enough to associate Иванов/`1042` with `Перемещён` and Сидоров/`1043` with `Работа`, not merely detect global words.
4. Add the no-removal case and require that its PDF has no `Перемещён` row/status.
5. Capture the version-1 artifact before the change and prove its bytes or immutable content identity is unchanged afterward.
6. Preserve the focused renderer RED if useful as adapter-level coverage, but it cannot substitute for the required public-seam behavior test. Request fresh independent Gate 3 review after correction.
