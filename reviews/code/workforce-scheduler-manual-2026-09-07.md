# Focused review: native workforce scheduler wiring

- Reviewer: `/root`, separate from implementation author `/root/workforce_schedule_audit`.
- Base: `b4edd83`; reviewed worker/configuration/CLI/Compose diff in the working tree.
- Verdict: APPROVED for inactive manual-pilot wiring.

The scheduler invokes the native synchronization seam, forwards the one ready
manifest's prefix and retains its existing minute-07 cadence. It does not own SQL
or workforce facts. Compose activation remains explicitly opt-in. The CLI keeps
its existing explicit native configuration and additionally translates the existing
private webhook document. Tokens are staged in a current-user 0600 single-link
file and removed in finally. Safe CLI output excludes credentials.

Review found and the author corrected an `isset` check that rejected forbidden
URL components only when all were present. Each component is now independently
rejected. The author also removed the scheduler's HOME override and rejected
trailing CLI arguments. Root independently ran the synthetic public CLI test and
shell syntax check: PASS. The test proves failure before network and cleanup; it
does not prove a successful scheduled live import or restart.

No remaining blocking finding for this inactive wiring. This focused review does
not authorize worker activation, a Bitrix call, or a production readiness claim.
Live scheduling evidence and normal full integration gates remain outstanding.
