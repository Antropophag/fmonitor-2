# Rapid visual contract — all-branch stylesheet correction v2

- Date: `2026-09-04`
- Gate: `2` correction after independent Gate 3 finding
- Prior review: `reviews/tests/RAPID-VISUAL-CONTRACT-UPLOAD-FIRST-VERIFIER-CORRECTION-001.md`, `CHANGES_REQUESTED`
- Production changes: none

The v1 verifier compared only the first `strpos()` positions. The corrected
oracle counts every exact shlz and pilot stylesheet tag and requires every shlz
occurrence to participate in the exact adjacent ordered pair
`shlz.css` → `pilot.css`. A missing, extra, separated or reversed tag in either
configured `PilotView` branch now fails.

All upload-first/future-control, CSS ownership, focus, font, calendar, OTIZ and
geometry assertions remain unchanged. Fresh baseline and second-branch reverse
order sensitivity must be reproduced by an independent Gate 3 reviewer.
