# Pilot demo bootstrap protected integration — supplemental code review

Reviewer: `/root/photo_review`; implementation author: `/root/e2e`.
Verdict: **APPROVED** for the bounded bootstrap/launcher correction.

## Standards

No finding. The fixture supplies explicit checkout dependencies, retains isolated
roots and bounded child lifetimes, and cleans symlinks by unlinking the link itself.
The production change is limited to making the existing HTTP helper's return shape
total and preserving the launcher's established redacted failure categories.

## Behavior and safety

No finding. Missing connections and malformed responses now return the same
three-field tuple as successful HTTP reads, eliminating undefined-index failures.
CSS classification uses the already-approved exact graph parser and compares against
the preflight member snapshot. It does not classify an ordinary startup failure as
an asset failure when the graph remains unchanged. Shared `app`, `public` and
`rapid-pilot` targets are never traversed during fixture cleanup.

Exact hashes and focused evidence are pinned in
`reviews/tests/PILOT-DEMO-BOOTSTRAP-PROTECTED-SUPPLEMENTAL-2026-09-07.md`.
The later normal-startup reconciliation, whole bootstrap GREEN, full `make verify`,
deployment and readiness remain open.
