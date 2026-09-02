# Test review: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v2 deep-seam RED

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, evidence, OpenSpec artifacts, or implementation
- Reviewed RED commit: `a685752d99ce3194e27ca4cc2ff45bfd92902b1b`
- Gate 1 strategy review: `docs/operations/pilot-work-navigation-gate1-rereview-v2.md` — `APPROVED`
- Verdict: **CHANGES_REQUESTED**

## Blocking findings

1. **Exact sibling/accessibility/icon preservation is pinned for only two compositions, not all ten approved current states.** The `$representations` loop renders all ten route/current labels, but it checks only repeat bytes, sentinel content and removed-item absence. `pwnItems()` child-byte SHA expectations are applied only to synthetic minimal/Objects and broad/Users documents. The approved v2 contract requires exact section 4 sibling/accessibility/icon preservation for all ten current-screen states and applicable minimal/broad actors. Current-state regressions on root, card, prepare, checklist, construction-control, installers or roles can pass—for example, losing their correct `aria-current`, changing an SVG/icon, or adding a renamed first-slot substitute only on one state.

2. **The named route-specific wiring suites are asserted by prose, not evidence.** RED v3 lists object-card, prepare-form, inspection-item endpoint, inspection-planning runtime, route-CSP, installer/catalog and identity-access tests but records no exact paths/hashes, commands or results. Gate 1 v2 explicitly requires Gate 3 to verify that each cited suite remains GREEN and still reaches its production view; silence, a skipped test, or a predecessor-blocked suite cannot count. The supplied evidence does not establish that part of the approved three-part topology.

Extend the renderer table so every approved actor/current-state pair has an independently fixed complete ordered child-byte manifest after removal, not merely the two representative lists. Record exact route-specific suite paths/hashes and successful commands/results, including GET/HEAD where those suites own it; identify and resolve any skipped or predecessor failure rather than treating the suite name as evidence. Then recapture the focused RED without changing its removal expectation.

## Coverage that passes review

- The v2 executable spec and deep-seam topology have independent Gate 1 approval and owner decision.
- All ten current labels are at least rendered through the production `PilotView` composition, and every render checks deterministic repeat bytes, content preservation and the common absence oracle.
- The common oracle detects exact visible/hidden text, direct ARIA/title/image labels, resolved `aria-labelledby`, root destinations and root current/disabled data substitutes.
- For the two pinned compositions, hashing each serialized direct navigation child is highly sensitive to order, labels, destinations, accessibility attributes, current/disabled state and inline icon/SVG bytes.
- Real authenticated `/pilot/` and configured `/pilot/objects` sentinels reach the production shared navigation and retain root content/redirect, GET/HEAD, RBAC and existing error/zero-read/zero-write controls.
- Focused syntax is valid and the intended RED reproduces at the first root label absence (`Expected: 0`, `Actual: 2`), not at setup.

## Mutation sensitivity assessment

The direct child hash is an independent predecessor byte oracle and would catch arbitrary remaining-child markup drift where applied. It also makes an inserted renamed/icon-only first slot fail the expected sequence. The gap is coverage, not hash sensitivity: seven other current labels and the root-current state do not receive a child manifest, and the two actor profiles are not applied across all applicable states.

## Reviewed hashes

```text
ffb72c0602a26e24aa86f7df339bcc209f6b0ce894f8a41988527c62e9db8c65  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
c29835d0c9328ebcff33a435da91b2698c76c65ba35fcc1274545ebc4f242ac6  tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
9de51a02c3a3900112c853a5f4dfb55c6195f93a7f4dc127d0e7b86268ba716b  tests/InstallationProcess/pilot_http_auth_001_test.php
861462feb34df7eb107167c314f5605ab1c5e554bb88c1706c0240c05e624f9a  tests/InstallationProcess/pilot_object_list_001_test.php
```

Gate 3 is not approved. No navigation-removal production change is authorized from this test revision.
