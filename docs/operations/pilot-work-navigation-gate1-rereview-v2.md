# PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001 v2 — independent Gate 1 re-review

Date: 2026-09-03  
Reviewer: `agent:/root/qg_gate1_review`  
Reviewer role: separately tasked Gate 1 reviewer; did not author the executable spec, OpenSpec artifacts, owner decision, tests, or implementation  
Change: `remove-pilot-work-navigation-item`  
Owner decision: `docs/operations/pilot-work-navigation-deep-seam-owner-decision-2026-09-03.md`  
Verdict: **APPROVED**

## Reviewed hashes

```text
ffb72c0602a26e24aa86f7df339bcc209f6b0ce894f8a41988527c62e9db8c65  specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md
44724732faad0fa0aae318ee64df41a53b496b1231b1997aa1f3a793903c4230  openspec/changes/remove-pilot-work-navigation-item/proposal.md
6dd91e84e023b21f82ff5884ca181e228c7e6b43f006ceec4b9490926e7d11b1  openspec/changes/remove-pilot-work-navigation-item/design.md
888bfabec7f079c9a5bc21ebf1093cded10c08dde131e6169fd9f37b24225504  openspec/changes/remove-pilot-work-navigation-item/specs/ui/pilot-work-navigation-item-removal/spec.md
a842dcc3fcc33b7b4bbf0a30e19d84a38dd3c66d602c2881544d7e20319d4365  openspec/changes/remove-pilot-work-navigation-item/tasks.md
e124ec7f5d71eb9121cfc60ce7a7b66503a5dbddd87bee8aa5354f77bfe4c243  docs/operations/pilot-work-navigation-deep-seam-owner-decision-2026-09-03.md
```

This approval applies only to these exact current-worktree bytes. A later change to a reviewed planning artifact requires renewed review/approval under the delivery process.

## Behavior and seam assessment

- Product behavior is unchanged from v1. The only intended production effect remains removal of the shared-navigation item representing `Моя работа`; `/pilot/` remains the same directly addressable work queue. No route, queue data/filter/order, authorization, redirect, error, session, persistence, audit or domain behavior is added or removed.
- The public behavior remains observable in the successful configured HTTP representation. v2 changes how that single shared composition is proved: exhaustive production renderer coverage plus two canonical HTTP sentinels and existing route-specific wiring tests. It does not substitute a private helper or a reconstructed renderer.
- The ten enumerated current-screen inputs exactly cover root, object list/card/prepare/checklist, construction-control queue/checklist, installers, users and roles. Each state has an independently known current item and applicable minimal or broad actor, so exact absence and sibling/current-state expectations are derivable without route implementation knowledge.

## Sensitivity and preservation coverage

- The renderer oracle can fail on visible text, accessible name, `aria-label`, hidden/off-screen duplicate, renamed first work-slot item, icon-only/focusable replacement, `/pilot/` destination, root current marker and disabled/data-route substitute. This is materially sensitive to plausible cosmetic evasions rather than only literal source text.
- Sibling preservation is exact and constructible from the predecessor composition: ordered groups/items, labels, destinations, conditional visibility, `aria-current`, disabled/accessibility state and icon bytes are compared for identical actor/current-screen inputs. Applicable minimal and broad actors exercise conditional construction-control, directory and administration siblings.
- Logo, skip link, breadcrumb, user menu and non-navigation content are explicitly outside the removed item and retain predecessor bytes. This prevents a broad snapshot rewrite from being mistaken for minimal removal.
- Repeat renderer output plus existing DB, filesystem, session and business/audit snapshots prove zero write. The slice has no command, event, schema, lock, deduplication or mutation seam.

## HTTP sentinel composition

- Real `/pilot/` and `/pilot/objects` GET responses are canonical HTTP sentinels. They prove that the actual entrypoint reaches configured shared navigation, that root queue and object-list/RBAC content remain admitted, and that the DOM oracle is not merely a direct-renderer fiction.
- Their paired HEAD checks preserve status and application-controlled headers, including inherited GET `Content-Length`, with an empty body. HEAD correctly does not manufacture HTML solely to reassert DOM absence; paired GET owns that observation.
- Existing route-specific HTTP suites remain responsible for successful admission and content of card, prepare, both checklist families, construction control, installers and administration. Their successful production views call the same renderer covered exhaustively by the ten-state oracle. Requiring eight more database/server fixtures would repeat the shared DOM observation without adding a distinct seam.
- This composition remains sensitive to a caller bypass: the deep oracle proves the renderer result, the two canonical sentinels prove end-to-end composition, and each remaining existing route test proves its production view/wiring and retains its own admission/content assertions. Gate 3 must verify the cited route suites actually remain green and still reach those views; silence or a skipped/blocked suite cannot count as wiring evidence.

## Inherited behavior controls

- Exact `/pilot` GET/HEAD redirect, root success, route-specific authorization, and inherited `401/403/404/405/503` status/body/header behavior remain separate assertions. A successful shell around an error, changed method admission or navigation-driven permission decision cannot satisfy the contract.
- Removal is independent of actor permissions after route admission. Permission predicates continue to affect only approved conditional siblings and route admission, not whether the removed item reappears.
- Icon preservation is bounded to remaining navigation items and can be compared byte-for-byte at the renderer seam; no new asset fixture or network dependency is needed.

## OpenSpec and owner decision

The proposal, design, delta specification and tasks consistently describe the same three-part proof: all ten renderer states, root/object-list HTTP sentinels, and existing route-specific wiring evidence. They explicitly reject duplicate fixture stacks while retaining GET/HEAD, accessibility/icon, sibling, error, authorization and zero-write coverage. `restore-pilot-work-navigation` remains superseded historical evidence and grants no approval to this opposite outcome.

The owner explicitly approved this deep-seam strategy and confirmed that behavior remains the v1 removal contract. No ambiguity affecting the observable product result remains.

## Verification

```text
$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

$ git diff --check -- specs/PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001.md \
    openspec/changes/remove-pilot-work-navigation-item \
    docs/operations/pilot-work-navigation-deep-seam-owner-decision-2026-09-03.md
(no output; exit 0)
```

## Gate decision

Gate 1 passes for the exact hashes above. Gate 2 may use the approved non-duplicating verification topology. This approval does not approve the current tests or RED evidence: a fresh Gate 3 reviewer must verify all ten exact renderer states, full sibling/accessibility/icon sensitivity, both real HTTP sentinels, the named existing route-specific wiring suites, GET/HEAD and error controls, and zero-write evidence before production removal.
