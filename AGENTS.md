# FMonitor 2.0 constitution

## Current priority — architecture transition

- Before planning, implementing, testing or resuming work, read
  `docs/operations/current-delivery-goal.md`. Owner2026-09-08 sets the order:
  **#32 → first complete OTIZ slice #24 → #33**. The pilot works and checks are
  accelerated; architecture does not wait for all manual findings to be closed.
- Only confirmed manual-work blockers or data/access risks interrupt this order.
  Preserve history, authorization, the working stand and independent review.
  Parallel agents use **gpt-5.6-sol / low**.

## Continuing rules

- Read `PRODUCT.md` and `CONTEXT.md` before product work. For pilot behavior also read `docs/fmonitor-2-pilot-spec.md` and `docs/fmonitor-2-pilot-data-model.md`.
- `docs/development-process.md` defines the normal full-gate process and the current owner-authorized manual-pilot exception. Preserve review independence and report deferred gates honestly.
- New migration slices use the lifecycle under `openspec/`; OpenSpec does not replace executable specs, RED evidence, or review records.
- Preserve append-only history. State changes belong to one explicit public application seam; screens, HTTP, imports, and cron do not own domain facts.
- `rapid-pilot/` is a behavioral oracle and temporary adapter, not a destination for new domain logic. Follow its local boundary instructions.
- `../fmonitor` is read-only evidence. Consume only public exports from `../shlz-ui`. Keep primary evidence and secrets outside this repository.
- Check changed boundaries and focused user flows while delivering the manual pilot. Run `make verify` before declaring final production integration complete; an intermediate manual-test stand follows the current delivery goal.

## Focused verification and CI failure triage

- Changes to `app/PilotHttp/*.php`, including compatibility aliases, must pass
  `php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php` before
  commit/push. `make architecture-check` includes this HTTP qualification check;
  the lower-level `tools/architecture/check` alone covers only its own rules.
- When CI fails, first collect the complete failed-job and `REGRESSION_FAILURE`
  inventory. Inspect every failure before pushing a correction; use bounded log
  excerpts only after the complete inventory is known. Record unresolved failures.

## Navigation

- Current goal, deadline and delivery mode: `docs/operations/current-delivery-goal.md`

- Product truth: `PRODUCT.md`, `CONTEXT.md`
- Pilot contracts: `docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`
- Delivery gates: `docs/development-process.md`
- Operations state: `docs/operations/`
- Architecture policy/baseline: `docs/architecture/`
- OpenSpec lifecycle: `openspec/config.yaml`, `openspec/changes/`, `openspec/specs/`
- Reviews: `reviews/tests/`, `reviews/code/`
