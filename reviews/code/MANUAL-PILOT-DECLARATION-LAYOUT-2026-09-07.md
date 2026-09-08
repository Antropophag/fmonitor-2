# Manual-pilot declaration layout — independent review

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the CSS change.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `a42d48d521f65e19a0bd1558c16799d50e349daa`.
- Scope: only the `rapid-pilot/pilot.css` working-tree diff against that base.
- Verdict: `APPROVED`.

## Exact reviewed identity

```text
bc1423c36eae777a5d802e75ba91a8d21998a75e441c07d44f86f4916f19ede4  rapid-pilot/pilot.css
73791dc6bba32341b05aada7d10ac220d3f01d34f23808c0ff5aded2256834ce  git diff --binary a42d48d521f65e19a0bd1558c16799d50e349daa -- rapid-pilot/pilot.css
```

The exact diff is 14 added and 3 removed lines.

## Findings

No blocking findings.

The correction removes the nested competing grid columns that compressed the real declaration action into a 40-by-292.5-pixel vertical strip. The completion action now owns one content column. Its form uses two shrinkable field columns, places the action button on its own row, and collapses the fields to one column at a 520-pixel completion-container width. This preserves the declaration workflow and visual hierarchy while giving the public button its intrinsic label width.

The object page now establishes `.fm2-object-data` as an inline-size container and collapses the passport/workspace layout at 720 pixels of actual card width. This correctly handles the tablet scene where a persistent or expanded sidebar leaves much less content width than the viewport media query suggests. The same container rule stacks the existing card heading/registration treatment and removes sticky passport positioning. Existing viewport media rules remain as a compatible fallback and continue to provide the narrower mobile details.

Selector scope is limited to FMonitor-owned layout containers and direct composition of public SHLZ field/button components. The patch does not replace SHLZ typography, paint, padding, height, focus state, or interaction behavior. `min-inline-size: 0` and `max-inline-size: 100%` prevent nested-grid overflow without imposing a new component geometry. No business behavior, form fields, action target, authorization, or persisted data changes.

The supplied headless evidence uses actual object `966` without submitting its form and intercepts only the candidate CSS. At viewport widths 1440, 1024, 768, and 390 pixels, the candidate reports no page overflow, errors, or bad measurements. The action button is 168.125 by 40 pixels at 1440/1024/768 and 168.125 by 44 pixels at 390, compared with the defective 40 by 292.5 pixels at 1440/1024/768. Visual inspection confirms readable labels, orderly field stacking, intact status/progress content, and the incumbent Service Desk/FMonitor visual language at all four widths.

External evidence SHA-256:

```text
735426dfe7a8e913827416afd7e7fefd68b798b182dbe4d7c105823a71946c7d  declaration-before.json
1224654808b42a2f2841b3bb5cd82e6bbc1ca2eda260fcf8cd268d8c11c6977e  declaration-candidate.json
9598b14da489e6c70ab9d076d84106749a4acae410d92f90331325d4ea1c0957  declaration-before-1440.png
126c751a52afe9841588006af5ff7aacda78b4fc933cc2e63e90365ebfe13bbc  declaration-before-1024.png
075f3ce09785af8caa46cb584ec297c6ad78c494e4770fe69aab2ac27d9bc3fe  declaration-before-768.png
e707d95e76e70c112928fd0fd63cd238da36a30d6e070350a66a6bea57be6b00  declaration-before-390.png
19202f84c18e139e4cfc117691376e6934b4e82810d6e1cbfe710dea54bda53f  declaration-candidate-1440.png
2760e1273cfeb5ff1ff53738991228e038c7b4c80dbac24b04e0df27e2cc9ed1  declaration-candidate-1024.png
c81f85da069ced847d8ab92a601f2b4a2e6ca2b5ec311d69c398f84fc356453a  declaration-candidate-768.png
7a1c30a8ccc4c51dccb48692e3de1bfcef6217d9b4a350f06035468b56f58c1c  declaration-candidate-390.png
37517e5f3dc66819f61f5a7bb8ace1921282415f10551d2defa5c3eb0985b570  declaration-layout-detect.json
```

## Independent verification

```text
php rapid-pilot/verify-visual-contract.php
Visual contract OK: shlz-ui ownership, Golos Text delivery, breadcrumbs, and primary actions verified.

php rapid-pilot/verify-focus-contract.php
Focus contract OK: native and shlz-ui interactive families use neutral, visible focus chrome.

/Users/antropophag/.agents/skills/impeccable/scripts/impeccable detect --json rapid-pilot/pilot.css
[]

git diff --check -- rapid-pilot/pilot.css
PASS
```

The review used read-only screenshots and measurements. It did not submit the declaration form, alter object `966`, modify the stand, or perform remote actions. This verdict is bounded to the exact CSS diff and does not claim full `VERIFY_OK` or stand installation.
