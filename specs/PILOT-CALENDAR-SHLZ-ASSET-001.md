# PILOT-CALENDAR-SHLZ-ASSET-001 — Calendar Grid in the pilot image

Status: APPROVED by the product owner's request of 2026-08-30 to ship the important-dates calendar on the existing `shlz-ui` calendar component without waiting for further confirmation.

## Observable contract

The built rapid-pilot image uses the fixed public `shlz-ui` revision
`a0a8ca6df60b84aa1fe10a1cb500de32dacd4516`. That revision must export both:

- `packages/behaviors/dist/calendar-grid.js` from the public `calendar-grid` behavior;
- Calendar Grid styles in `packages/styles/dist/shlz.css`.

The application consumes those build artifacts at runtime. It must not copy the
component source or styles into FMonitor.

## Failure cases

- The pinned revision has no public Calendar Grid behavior export.
- The pinned revision has no Calendar Grid stylesheet in the generated bundle.
- The Docker build copies a different checkout into the runtime image.

## Public seam

The seam is the `shlz-ui` artifact set copied into the pilot runtime image by
`Dockerfile`, observed through the fixed revision's package exports and build
inputs.
