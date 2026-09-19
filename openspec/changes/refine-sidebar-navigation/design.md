## Context

The incumbent shell is server-rendered Yii HTML plus `pilot.css` and `navigation.js`. `shlz-ui` is pinned into the runtime image and exports appropriate sidebar/chat/chevron/user/book/grid/chart icons.

## Goals / Non-Goals

**Goals:** exact information hierarchy, shlz-ui icon reuse, visible collapse affordance, floating feedback, role-stable output on desktop/mobile.

**Non-Goals:** Calendar (issue #203), scheduling, new permissions, OTIZ workflow changes, schema/data migrations, sidebar redesign beyond the remaining five owner findings, rapid-pilot changes.

## Decisions

- Vendor the required pinned `shlz-ui` SVG bytes into the existing Yii asset surface or render their exact path data through one asset-backed helper; do not redraw symbols. Suitable exports exist for chat, duo chevrons, grid, book, user and chart, so no generated SVG is expected.
- Keep `MainNavigation` as the single membership/group owner. Remove the one-off installer sidebar and make that view consume the shared shell so group/icon behavior cannot drift again.
- Feedback belongs to `ViewSupport`, outside `<nav>`, and is positioned above the mobile safe-area/navigation.
- The existing `<details>` persistence model remains; markup gains exact SVG and dynamic accessible labeling.

## Verification impact

- Focused real-HTTP navigation test covers groups, permissions, feedback outside nav and exact icon provenance markers.
- Browser check covers desktop expanded/collapsed control geometry, keyboard operation, floating feedback hit target/overlap, and mobile placement.
- No DB schema, backup/restore, jobs, imports or external adapters apply; the calendar only reads existing tables.

## Risks / Trade-offs

- Fixed feedback can obscure controls; browser geometry checks both desktop and mobile.
- CSS `:has()` is retained because the incumbent supported shell already depends on it.
