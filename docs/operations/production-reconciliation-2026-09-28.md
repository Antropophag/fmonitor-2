# Production reconciliation — 2026-09-28

Owner request: preserve all fixes already running in production as a separate Git change, verify locally and through GitHub, and keep unshipped issue #257 separate. This change does not authorize production installation, container recreation, migrations, or production-data writes.

## Captured scope and contract

Production checkout HEAD: `a69be7a5b879206f071926d3e270f538da7d03a2`. Parent chain contains six already deployed commits absent from main `145cebd2`: OTIZ reference layout, imported-card norm fallback, native admission, dialog/installer picker layout, isolated opening date-picker styles, and contextual calculation rows/actions. Preserve this history.

Read-only source capture contains 13 modified tracked paths and 20 added guide paths. Application bytes match the running PHP container. Reconcile these existing behaviors:

- Excel KTU is the participant's normalized contribution within the saved object slice, bounded 0–1; Russian status labels; saved financial amounts unchanged.
- Payment register starts with «Все»; installer dashboard shows busy/free bars; object queue has a narrow `%` column using existing completion progress.
- PTO/declaration register and navigation require administrative permission as well as object reading; direct unauthorized access remains denied.
- Calendar is readable by authenticated users; construction-control readers can see all eligible objects, and «Мои» still filters by current assignment. Scheduling/writing permissions remain unchanged.
- OTIZ draft/group rows expose dismissed-recipient warnings without changing payment decisions or money.
- Public `/help/` contains the captured Russian guide and synthetic screenshots, strict static routing and navigation links. Application routes retain authentication.

No new product logic is introduced during capture. Tests characterize the deployed behavior. Issue #257 incident tables, correction logic, new assets and schema v38 are excluded; schema remains v37.

## Provenance and checks

Read-only capture, SHA-256 inventories and original file payloads are retained outside Git:
`/Users/antropophag/.local/share/fmonitor-2/prod-reconcile-20260928/`.
The source branch is `codex/reconcile-production-20260928`, isolated from the active issue #257 worktree. Root restores the existing source and supplies regression checks; original authorship remains in the six commits and earlier delivery records. An independent reviewer checks the assembled candidate.

Focused local checks cover workbook values, admission/portfolio, calendar, construction-control read/write scope, navigation, object queue, dashboard and OTIZ HTTP. Full local `make test` / `make verify` is forbidden; the existing GitHub consumer runs the full matrix for the final committed candidate. Missing/failed checks remain explicit, never overall GREEN.

Production guide files live in the gateway persistent volume, not the PHP image. The source capture retains their checked-in form and Caddy configuration. PHP source matches the host, while worker/scheduler app source has pre-existing differences; the private manifest lists them. This PR does not synchronize those containers. A later release must rehearse consistent application/worker versions and preserve the guide route.
