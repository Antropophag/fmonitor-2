# Test review: VERIFICATION-INVENTORY-001

- Reviewer: `Codex agent /root/audit` (independent; did not author the specification, tests, or planned runner implementation)
- Reviewed base: working tree at HEAD `d5f8f2d7d96171dbc152766b517501c8fe0abaf1`
- Specification: `openspec/changes/explicit-verification-inventory/specs/verification/inventory/spec.md`
- Public seam: `bash tools/verification/run.sh list unit|db|characterization|e2e` and execution of those groups
- RED evidence: `/tmp/fmonitor25-red.log`
- Verdict: `CHANGES_REQUESTED`

## Findings

The proposed seam is appropriate and isolated. The tests copy the real runner into a temporary repository tree, replace interpreters with deterministic tracing executables, and do not use Docker, a live database, production data, or network access. The expected unit and DB fixture membership is test-owned rather than derived from the implementation. The timing case is sensitive to continuation after an exit `7`, exact group/runtime/path attribution, integer duration, and the final nonzero outcome.

The captured run is qualifying RED for removal of the content heuristic and addition of timing: eleven of thirteen cases fail against the unchanged runner, principally because it still requires `rg`, ignores the catalog, and emits no `VERIFY_TIMING` records. The fixture and interpreters are reached in the timing case, so the log is not solely a setup failure. Production was not changed before review.

### Blocking findings

1. **Invalid-catalog coverage currently passes for the wrong reason.** Every malformed-catalog subcase only requires any `SETUP_FAILURE`, empty stdout, and no interpreter invocation. The unchanged runner satisfies that assertion because `rg` is absent from the fixture PATH; it never reads the malformed catalog. The missing-catalog subcase has the same false-positive path. Require a diagnostic tied to the catalog defect (duplicate pair, invalid group, invalid runtime, missing path, traversal/field count, or missing catalog), or provide `rg` for this test so the old runner cannot satisfy it through an unrelated prerequisite failure. Each subcase must prove its intended rejection independently.

2. **The required four-group inventory is not tested.** The fixture catalog contains only `unit` and `db`, and all inherited executable assertions exercise only those two groups. An implementation can ignore `characterization` and `e2e`, fail to accept `python3`, fail to preserve their order, or omit the new inventory test from characterization and still pass. Add test-owned characterization and E2E members, including a Python entry, and assert exact read-only list output and execution dispatch/order for both groups. Assert that the repository inventory registers `tests/Verification/verification_inventory_001_test.py` in characterization, as the specification explicitly requires.

3. **Preservation of the real 117 unit and 120 DB memberships is not protected.** The synthetic four-unit/two-DB fixture proves catalog dispatch mechanics but cannot detect an incomplete or reordered repository `suites.tsv`. The slice states byte-for-byte preservation of the baseline lists and makes this the main migration safety condition. Add a repository-level assertion against fixed, reviewed baseline fixtures (or fixed hashes plus counts and critical membership) for the actual unit and DB lists. Expectations must be independent of the new catalog and must also cover the current characterization/E2E lists. Without this, implementation can register only the synthetic-test-relevant subset and pass focused tests while silently dropping existing verification.

4. **Completeness scanning is only demonstrated for one PHP directory.** `test_unknown_file_fails_before_execution` adds an unregistered file only under `tests/InstallationProcess`. An implementation that scans that directory but omits `tests/AssignmentOrderComposition` or `_test.mjs` files in `tests/Verification` passes. Add separate cases for all three normative discovery families. Each should require the offending path in the diagnostic and zero interpreter invocation.

5. **Duplicate validation is narrower than the declared uniqueness rule.** The design says uniqueness is the pair `group/path`, while the invalid test duplicates the entire first row only. An implementation that rejects identical lines but accepts the same `group/path` with a different runtime would pass. Add that adversarial catalog explicitly.

## Required changes

- Make every malformed/missing-catalog case fail for its own catalog-specific reason rather than the absent-`rg` reason.
- Exercise and assert exact characterization and E2E listing/execution, including `python3` and registration of this new test.
- Protect the complete real baseline membership and ordering for all four groups with independent fixed expectations.
- Add unregistered-file cases for both PHP families and the Verification Node family.
- Add a duplicate `group/path` case whose runtime differs.
- Capture a new RED and request fresh independent Gate 3 review. Gate 4 must not begin yet.

## Re-review after Gate 2 corrections

- Re-reviewed base: working tree at HEAD `d5f8f2d7d96171dbc152766b517501c8fe0abaf1`; production runner remains unchanged.
- Superseding verdict: `APPROVED`

All five blocking findings are resolved without weakening the original scheduler expectations.

The malformed-catalog and unknown-file cases now put real `rg` on the isolated PATH. Against the unchanged runner they therefore reach the old content-based behavior instead of passing on an unrelated missing-tool failure: every malformed catalog and all three unregistered-file families are incorrectly accepted and produce explicit intended RED failures. The invalid matrix includes the same `group/path` under a different runtime, so validation must enforce the specified pair identity rather than merely reject identical lines.

The synthetic catalog now exercises `characterization` and `e2e` through the public list and execution seams. Fixed expectations cover exact order and interpreter dispatch for Python, PHP and E2E; list remains free of interpreter calls. A failing first Python characterization entry proves fail-fast by excluding the following PHP call. The pre-existing unit and DB continuation expectations remain intact.

Repository membership is protected independently of the future catalog. Fixed SHA-256 expectations match the captured d5f8f2d public inventories: 117 unit entries (`ae1c98c...`), 120 DB entries (`ecb69ca...`), 18 characterization entries (`ce1532ec...`) and one E2E entry (`3e71f819...`). The test removes exactly one required new characterization line before comparing its fixed baseline digest and separately requires that line exactly once. Thus a missing, additional, duplicated or reordered real member changes the digest. The reviewer compared the four supplied baseline files and their counts/hashes to these literals.

Completeness rejection now adds an unregistered PHP test independently under both `tests/InstallationProcess` and `tests/AssignmentOrderComposition`, plus an unregistered `_test.mjs` under `tests/Verification`. Each case requires the exact offending path in stderr and proves zero interpreter invocation.

The reviewer independently reran:

```text
$ python3 tests/Verification/verification_inventory_001_test.py
Ran 15 tests in 3.889s
FAILED (failures=23)
exit 1
```

This is qualifying RED. Catalog validation cases fail because the unchanged runner returns success; unknown files fail because they are silently accepted; repository characterization listing fails because the old CLI supports only unit/db; timing fails because no `VERIFY_TIMING` record exists. Other inherited cases fail because the isolated PATH intentionally lacks `rg`, demonstrating that the planned inventory removes that dependency. Setup is otherwise operational and the timing fixture reaches all five trace interpreters.

No blocking test findings remain. Gate 3 is `APPROVED`; Gate 4 may implement the reviewed inventory, validation and timing behavior without changing these expectations.
