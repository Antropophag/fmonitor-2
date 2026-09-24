## 1. Gate 1–3: contract and RED candidate

- [x] 1.1 Root completes the issue #258 stage-one acceptance matrix in `specs/INSTALLER-UTILIZATION-STAGE-ONE-001.md` and verifies strict OpenSpec validation.
- [x] 1.2 Root creates deterministic DB/HTTP tests for current/upcoming semantics, effective lift identity, 3→2→1→0, replacement/history, unavailable/unknown values and read-only behavior; verify each new behavior fails for the intended missing implementation.
- [x] 1.3 Root creates directory/card/picker HTTP and representative browser tests proving server filters/count/pagination and the user route on desktop/narrow viewports; capture bounded RED evidence outside the checkout.
- [x] 1.4 Root completes `verification-input.json`, runs `harness.py prepare`, reads all Quality Graph obligations, resolves coverage gaps and records the selected lane/reviews before Gate 2.
- [x] 1.5 A separate `gpt-5.6-sol / low` reviewer records the planner-required Gate 3 verdict for the complete spec/tests/RED snapshot; implementation starts only after APPROVED when Gate 3 is required.

## 2. Unified read model

- [ ] 2.1 Executor implements one read-only Workforce projection for applied composition + factual opening − active PTO and confirmed-original-before-opening; focused DB tests prove A–H and no writes.
- [ ] 2.2 Executor implements separate lift periods and effective requisites without address grouping; focused tests prove three lifts and sequential PTO 3→2→1→0.
- [ ] 2.3 Executor implements SQL-first search/load predicates, paired count/list queries, stable pagination and explicit unavailable/unknown states; a dataset larger than one page proves case I.

## 3. Working user surfaces

- [ ] 3.1 Executor integrates the projection into the existing installer directory with minimal local registration; focused HTTP/browser tests prove filters, counts, paging, escaping, authorization and links.
- [ ] 3.2 Executor adds the installer card with current/upcoming/completed available periods, known/unknown dates, effective object links and allowlisted return state; focused tests prove H, K and L.
- [ ] 3.3 Executor supplies the same compact read-only context to the existing assignment-order composition picker without changing eligibility or writers; focused tests prove J and M.
- [ ] 3.4 Executor verifies the visible directory→card→object and picker routes in a disposable runtime at desktop and narrow widths, without changing stand 8093.

## 4. Focused verification and independent review

- [ ] 4.1 Run only planner-selected focused local DB/HTTP/browser/architecture checks, including PilotHttp qualification if that boundary changes; record exact commands/results and do not run local full `make test`/`make verify`.
- [ ] 4.2 Capture a reconstructible exact-source snapshot or commit, update task/delivery evidence, and have an independent `gpt-5.6-sol / low` reviewer record Gate 5 findings and verdict.
- [ ] 4.3 Correct all findings against reviewed source, rerun only affected focused checks, and repeat any planner-required review invalidated by code/test/spec changes.

## 5. Publication without closing #258

- [ ] 5.1 Commit the reviewed exact source, push the stage-one branch, open a PR explicitly leaving stages 2–3 of #258 open, and run one selected exact-source GitHub CI matrix.
- [ ] 5.2 Inventory every failed CI job and `REGRESSION_FAILURE` before correction; finish only with green exact-source CI and current independent approval, without merge/deploy/stand mutation.
- [ ] 5.3 Final handoff names PR/head, working screens, applied→start→PTO and 3→2→1→0 evidence, focused checks/CI/reviews, and the remaining daily-snapshot/history-chart/forecast work.
