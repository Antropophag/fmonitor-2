# PILOT-UI-SHELL-001 — consolidated browser evidence lineage correction v7

- Date: `2026-09-04`
- Current evidence HEAD: `b697665b19e783edecc76342bd7292b8ac40ca58`
- Recorded production candidate: `557482811a26b0df6f4bef7d4baba0a47c5baeb5`
- Production comparison: `git diff --quiet 5574828..b697665 -- app/PilotHttp` — exit `0`
- Scope: evidence-only Gate 5 correction
- Verdict: **RED — tablet canonical shell overlap remains**

No production, test, specification or review file was changed. Current
production bytes are identical to the recorded candidate; the external report
therefore pins `557482811a26b0df6f4bef7d4baba0a47c5baeb5` as requested.

## One consolidated evidence set

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-consolidated-5574828/
```

The directory contains one `report.json` and 23 screenshots covering all
surfaces/cases in one lineage. Runtime is Playwright `1.62.1`, Chromium
`151.0.7922.34`, Node `v22.23.1`.

Report SHA-256:

```text
aefe75c68956c86742d73330f25748d26395fcfa12726a47bc021398f36c482a  report.json
```

## Canonical 12-case full oracle

Queue, card and prepare were each exercised at `1440x900`, `768x1024`,
`320x568`, and `320x568` with 200% root text. The single oracle records:

1. page and form horizontal overflow;
2. nonzero and unclipped geometry for every visible heading, navigation,
   breadcrumb, status, database text, label and native control, including
   long/hostile values;
3. DOM/visual primary-region order and pairwise positive-area overlap;
4. complete native sequential Tab traversal, viewport-contained focused rects
   after browser scroll, and visible outlines;
5. long/hostile/control geometry as an explicit report subset.

Native radio groups contribute one sequential Tab stop; the unchecked peer is
not a missing sequential stop. With that browser-native rule, focus traversal
is complete and has zero focus failures.

Result:

```text
layout cases: 12
layout failures: 3
```

All three failures are at `768x1024`:

| Surface | identity/main positive overlap |
|---|---:|
| queue | `2581.875 px²` |
| card | `2581.875 px²` |
| prepare | `2581.875 px²` |

The inspected queue screenshot visibly confirms the same defect: the retained
80px sidebar clips/crushes the identity while main begins beside/under it. This
is a real P0 visual failure, not a screenshot artifact. Desktop and both 320px
classes pass the consolidated oracle.

## Picker oracle

Picker cases at `1440x900`, `320x568`, and `320x568` with 200% root text all
prove:

- open/search/one result;
- every dialog child belongs to the dialog and is inside its internal scroll
  extent without clipped text;
- search/meta/results/result can be scrolled fully inside the padded visible
  content box;
- no page horizontal overflow;
- Escape closes and focus returns to the opener;
- heading/body/search/meta/result fonts scale exactly `2.0` from normal 320px
  to the 200% case.

Result: `picker cases=3`, `picker failures=0`.

## Noncanonical configured shells

Production `PilotView::document` output for construction-control, checklist,
installers and administration was rendered at `320x568` and at 200% root text.
All eight cases show exact actor name/email, nonclipped geometry, correct
navigation → actor → main order, zero peer overlap and no page overflow.

Result: `consumer cases=8`, `consumer failures=0`.

## Evidence lineage correction

The earlier directories split layout, picker containment, scaled typography
and configured-consumer checks across separate reports. Their individual
GREEN summaries did not run the full five-part canonical oracle together and
therefore failed to expose the `768x1024` identity/main overlap.

For any fresh Gate 5 judgement, the following partial reports are superseded
as sufficient visual proof by this consolidated result:

```text
ui-shell-integration-b082bbb
ui-shell-correction-2432746
ui-shell-correction-2432746-picker
ui-shell-css-ownership-cd25498
ui-shell-css-ownership-cd25498-picker
ui-shell-p0-confirm-b78c59e
ui-shell-picker-containment-b4d91a3
ui-shell-scale-consumers-5574828
```

They remain historical evidence and are not deleted or rewritten. Their
underlying observations may still be useful, but they cannot establish overall
visual GREEN. The authoritative consolidated verdict is **RED** until a newly
gated production correction removes the three tablet failures and a fresh
exact-candidate consolidated run passes.

The isolated browser database was dropped and exact task-owned
`.test-artifacts` was removed. `git diff --check` and the working-tree check
were clean before this append-only documentation change.

