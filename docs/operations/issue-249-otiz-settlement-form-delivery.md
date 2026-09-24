# Issue #249 — OTIZ settlement form recovery delivery

## Scope and authorship

- Owner assignment: issue #249 under roadmap #169, preserving merged #248 history.
- Initial base: `origin/main` `d9dddb31f9c6e07092bcf6d4c04df761a1a13ccd`; before final publication the candidate was cleanly rebased onto `1678f1c7e49d5ed80f44ebda70baf44b8c3c8032` (merged #250), with no #249 production-path overlap.
- Worktree/branch: `/private/tmp/fmonitor-249-otiz-form-errors`, `codex/issue-249-otiz-form-errors`.
- Root session authored OpenSpec artifacts, canonical spec and tests.
- Separate `gpt-5.6-sol/low` executor authored production implementation.
- Independent `gpt-5.6-sol/low` reviewer returned Gate 3 twice; the third exact-source review approved the rebuilt acceptance matrix.

## User before / after

Before: `1000` and `1000,50` were rejected; a known error redirected to a generic message and cleared amount, basis, document and object context. `STALE_CALCULATION` returned plain 409 text. Double activation was not guarded and a lost response gave no operation-aware recovery.

After: supported ordinary ruble strings are converted exactly to integer cents without float/rounding; known failures reopen the exact object form with escaped allowlisted values and the same operation ID; stale payment remains a no-write 409 with a readable return/new-calculation page; one in-flight submit is allowed and an unknown transport outcome is shown without auto-retry or UUID replacement.

## Preserved boundaries

`OtizSettlement`, formulas, owner limits, financial writers, schema, signed ledger, snapshots, XLSX, roles/CSRF and object-wide history #248 are unchanged. #258/#250/#260, working stand, merge and deployment are untouched. No real financial operation was performed.

## Evidence

- Gate 3: `reviews/tests/OTIZ-SETTLEMENT-FORM-RECOVERY-001.md` — APPROVED after two returns and complete rejection-matrix rebuild.
- Planner lane: CRITICAL; required reviews `gate3`, `final`.
- Planner-selected focused run: all eight commands GREEN, including new HTTP/browser, retained settlement/#248, deployment category, verification planner, runtime storage and architecture guard records under the external delivery-harness store.
- Additional `make architecture-check`: GREEN (7 rules; unrelated existing file-size advisories only).
- `pilot_http_auth_001_global_calls_test.php`: GREEN.
- Full local `make test`/`make verify`: intentionally not run by owner policy.
- Final review: `reviews/code/OTIZ-SETTLEMENT-FORM-RECOVERY-001.md` — first return found three UNKNOWN-outcome blockers; corrected exact source `c398117e8071a64ec3d21335a41b7df892b97155ee3aa403ef6f97cf4c16d27a` APPROVED with no remaining findings.
- Post-review delta before commit is documentation/task bookkeeping only; production and tests remain byte-identical to the approved source.
- First PR run `36049900753` on pre-rebase head failed three exact regressions: stale settlement-browser redirect/message oracle, stale `otiz.js` asset digest, and an unrelated one-off inspection JSON setup failure. Complete inventory was inspected; the first two were corrected, while the inspection test passed focused on the rebased source.
- PR and exact-source CI: PENDING.
