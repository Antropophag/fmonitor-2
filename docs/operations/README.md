# Delivery operations

This directory is the durable control plane for autonomous migration.

Receipt-governed slices add a strict fenced `delivery-metadata` JSON block to
their existing narrative evidence. Immutable receipt chains live under
`delivery/evidence/<slice-id>/` and are checked by
`make delivery-evidence-check`; historical evidence is not rewritten.

- `status.md` — current TEST-USER-READY status and READY/NEEDS_GRILL/BLOCKED_EXTERNAL/IN_PROGRESS/VERIFYING/DONE queues.
- `test-user-ready-release-plan-2026-09-09.md` — living critical path,
  daily checkpoints, verification ladder and GO/NO-GO criteria for the
  Wednesday 2026-09-09 test-user launch.
- `pilot-behavior-inventory.md` — capability-level observations from rapid-pilot.
- `module-map.md` — proposed behavior-owned application modules and public seams.
- `migration-backlog.md` — vertical behavior slices and Done definitions.
- `grill/` — compact owner-decision packages and recorded answers.

Only evidence-supported behavior becomes `ACCEPTED` or `ACCEPTED_WITH_CHANGES`. `UNKNOWN` is discovery input, not a requirement.
