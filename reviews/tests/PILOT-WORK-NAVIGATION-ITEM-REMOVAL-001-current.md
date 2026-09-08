# Test review: PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 — current focused RED

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, OpenSpec artifacts, owner records, test, RED evidence, or implementation
- Reviewed test: `tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php`
- Verdict: **CHANGES_REQUESTED**

## Blocking findings

1. **The test does not exercise the approved public HTTP seam.** Every entry in `$representations` is only a route-shaped string passed into sentinel content; all rendering calls invoke `PilotView::document()` directly. No canonical router/entrypoint performs route recognition, authorization, current-screen selection or response construction. Section 3 explicitly requires successful configured representations through the canonical HTTP entrypoint, not a reconstructed renderer graph.

2. **Enumerating route names is not route-family coverage.** The array lists all ten families, but it does not prove any listed URL is admitted or successful, uses a real positive object, selects the stated current item, or is accessible to the actor. It also tests only one actor per route rather than minimal and broad actors wherever both are admitted. The RED evidence itself marks tasks 2.1–2.2 open and states that a fresh reviewer must not approve this intermediate state.

3. **GET/HEAD and root-route preservation are absent.** There are no HTTP methods or responses. The test cannot prove paired GET/HEAD status/header/content-length parity, empty HEAD bodies, `/pilot/` remaining the work queue, or `/pilot` retaining its exact redirect. Preserving a locally supplied `<h1>sentinel /pilot/</h1>` inside renderer output is not evidence that the root route, queue data, filtering, authorization or redirect still works.

4. **Inherited error/authorization behavior and zero-write guarantees are absent.** The focused renderer test cannot observe preserved 401/403/404/405/503 outcomes, `Allow`/`Retry-After`, security/redaction headers, session behavior, database/session/artifact/audit snapshots, or repeated HTTP read-only behavior. Byte-identical repeated renderer calls prove only pure output for the same in-memory arguments.

5. **Sibling and substitute sensitivity is incomplete.** Exact remaining sibling sequences are asserted for only two synthetic compositions (`minimal` on Objects and `broad` on Users), not each successful route/actor combination. `pwnItems()` omits icon/SVG/class bytes and accessible-name structure, although section 4 requires byte-equivalent icon and accessibility preservation. A renamed or icon-only substitute conditional on the root/current screen, another route, or an untested actor can pass. The generic absence XPath catches exact visible text, direct `aria-label`/`title`/`img alt`, `aria-labelledby`, and root href/current data, but does not independently establish the rejected first-slot replacement across governed representations.

Complete the already identified canonical HTTP expansion before another Gate 3 review: one successful GET plus paired HEAD per enumerated family, applicable minimal/broad actors, exact primary-navigation absence, exact sibling/accessibility/icon preservation, root queue and redirect controls, inherited error/admission matrix, repeat and zero-write snapshots. Existing route fixtures should be reused rather than duplicating schema/setup.

## Checks that passed

- Gate 1 exact spec/OpenSpec hashes have independent review and explicit owner approval.
- The focused test cites the correct v1 specification and uses the shared configured renderer rather than duplicating navigation markup.
- Its current RED is intended: DOM parsing succeeds and the first `/pilot/` representation finds two matching descendants because production still emits «Моя работа».
- It detects exact visible/hidden descendant text, direct ARIA/title/image labels, resolved `aria-labelledby`, root href variants, selected root substitutes, deterministic repeat bytes and content preservation.
- The two synthetic sibling examples independently fix useful minimal/broad label, destination, current and disabled-state expectations.
- Test syntax passes and setup requires no DB/network/production system.

## Reproduced focused RED

```text
php -l tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
php tests/InstallationProcess/pilot_work_navigation_item_removal_001_test.php
```

Syntax passed. The behavior test exited `255` at:

```text
/pilot/ no visible or hidden work label
Expected: 0
Actual: 2
```

This authorizes no GREEN because the approved seam and acceptance matrix are not yet represented.

## Reviewed authority hashes

```text
17d383f8dc12d2f08789f9f2e196cffd50b5dad1166cdd5ca5722b41dc318626  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
48e95d58aaf9546c955eb22c99c899f19b8554970a3e5f251570431f32e6f6e0  openspec/changes/remove-pilot-work-navigation-item/proposal.md
f8d242f0d3c1888c20117a2d1ffaadb2e89ad9724cc808a9bbb67855005482d0  openspec/changes/remove-pilot-work-navigation-item/design.md
32100798cfae2674f8a7d32880c3cc963373f1df2bb60f3d3e81d020fef73fc1  openspec/changes/remove-pilot-work-navigation-item/specs/ui/pilot-work-navigation-item-removal/spec.md
6bffa599d9233b3e1d9c1af1bebb7c2c62d040be2efaaaebd7d10c88018f9adf  openspec/changes/remove-pilot-work-navigation-item/tasks.md
```

Gate 3 is not approved. No navigation-removal implementation is authorized from the current focused test alone.
