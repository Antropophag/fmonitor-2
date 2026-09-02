# Quality Graph representative baseline: pre-existing RED

- Date: 2026-09-03
- Branch: `codex/integrate-quality-graph-governance`
- Comparison: `git diff main...HEAD -- app/PilotHttp/PilotHttp.php tools/architecture/baseline.json` returned empty.
- Command: `make verify`
- Result: nonzero, `FULL_VERIFICATION_FAILURE count=4 stages=architecture-check,unit-test,db-test,e2e-test`.
- New Quality Graph focused checks: all three passed in `characterization-test`.
- Existing architecture failure: `sql_ownership` for `app/PilotHttp/PilotHttp.php` and hotspot `551 -> 552`.
- Existing regression examples: several Pilot HTTP tests return `500`; full output remained visible and the harness continued through all stages.

This record is baseline evidence, not a waiver. The Quality Graph change does not touch the implicated application file or architecture baseline. Positive parity and final Gate 5 cannot be claimed until the repository-owned baseline is green through a separately governed correction. A representative PR may still prove fail-closed negative parity and workflow provenance.
