# FMonitor 2.0 constitution

## Current priority — manual pilot today

- Before planning, implementing, testing or resuming the autonomous goal, read
  `docs/operations/current-delivery-goal.md`. Owner2026-09-07 requires a working
  manual-test pilot by **2026-09-07 22:00 Europe/Moscow**, first end-to-end stand
  targeted for17:00. This priority overrides older mandatory full-gate sequencing
  for the manual-pilot milestone; keep history, authorization and data preservation.
- Deliver the usable flow first, then fix the owner's manual-test findings. Reuse
  existing code/approvals, use focused smoke checks, and defer rare-case matrices
  and complete architectural migration. Parallel agents use **gpt-5.6-sol / low**.

## Continuing rules

- Read `PRODUCT.md` and `CONTEXT.md` before product work. For pilot behavior also read `docs/fmonitor-2-pilot-spec.md` and `docs/fmonitor-2-pilot-data-model.md`.
- `docs/development-process.md` defines the normal full-gate process and the current owner-authorized manual-pilot exception. Preserve review independence and report deferred gates honestly.
- New migration slices use the lifecycle under `openspec/`; OpenSpec does not replace executable specs, RED evidence, or review records.
- Preserve append-only history. State changes belong to one explicit public application seam; screens, HTTP, imports, and cron do not own domain facts.
- `rapid-pilot/` is a behavioral oracle and temporary adapter, not a destination for new domain logic. Follow its local boundary instructions.
- `../fmonitor` is read-only evidence. Consume only public exports from `../shlz-ui`. Keep primary evidence and secrets outside this repository.
- Check changed boundaries and focused user flows while delivering the manual pilot. Run `make verify` before declaring final production integration complete; an intermediate manual-test stand follows the current delivery goal.

## Navigation

- Current goal, deadline and delivery mode: `docs/operations/current-delivery-goal.md`

- Product truth: `PRODUCT.md`, `CONTEXT.md`
- Pilot contracts: `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`
- Delivery gates: `docs/development-process.md`
- Operations state: `docs/operations/`
- Architecture policy/baseline: `docs/architecture/`
- OpenSpec lifecycle: `openspec/config.yaml`, `openspec/changes/`, `openspec/specs/`
- Reviews: `reviews/tests/`, `reviews/code/`
