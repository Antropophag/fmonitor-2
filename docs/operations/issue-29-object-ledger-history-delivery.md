# Delivery — #29 object-wide ledger history

## Assignment and source

- Owner assignment: issue #29 update from 2026-09-24, coordinated by #169.
- Isolated branch: `codex/issue-29-object-ledger-history` from `origin/main@b1542f92009b8dc4216a36962ff38a51e0b6c388`.
- Exact delivered source: the PR head commit containing this record; PR/check URLs remain the live source for post-commit CI state.
- Worktree: `/Users/antropophag/code/fmonitor-2-issue-29`; the primary checkout and working stand were not changed.

## Authorship

- Root session: scope, normative/OpenSpec contracts, verification plan and executable HTTP/browser tests.
- Separate `gpt-5.6-sol / low` executor: production implementation and both Gate 5 corrections.
- Independent `gpt-5.6-sol / low` Gate 3 reviewer: test/spec review and correction rereviews.
- Different independent `gpt-5.6-sol / low` Gate 5 reviewer: complete final review and correction rereview.

## Delivered behavior

- The existing snapshot object drawer opens a lazy, object-scoped history without a new route or navigation item.
- The read model returns ten-row SQL pages, stable ordering, full-set signed component totals, source snapshots and same-object reversal navigation under one repeatable-read snapshot.
- Saved snapshot values are separated from the current all-calculations ledger total; no historical cutoff is invented.
- The history is escaped, authorized and action-free. It does not change snapshots, ledger, events, operations, jobs/outbox or external systems.
- Existing current-snapshot ledger, financial actions, writers, formula, schema, XLSX, rights, routes, CSS, calendar and integrations are unchanged.

## Gate evidence

- Verification lane: `CRITICAL`; required reviews: `gate3`, `final`.
- Gate 2 final intended RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790216834410954000-5614aaa233f34d079168854267ea24ca.json` for the server-rendered drawer transition. Gate 5 correction RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790217672486144000-cd312639786444b3b1279e89c2f0e34f.json` for extreme-page overflow.
- Gate 3: `reviews/tests/OTIZ-OBJECT-LEDGER-HISTORY-001.md`, controlling verdict `APPROVED` including cross-page/maximum-page corrections.
- Gate 5: `reviews/code/OTIZ-OBJECT-LEDGER-HISTORY-001.md`, controlling correction verdict `APPROVED`; one low non-blocking duplicated-classification advisory remains.
- Exact focused GREEN: acceptance/browser `1790217880425829000-4e8c78fbbc19457f958471d0b07e9d81`; snapshot publication `1790217885042627000-f2ee16cbcf714e0cb095d648f976d58a`; settlement `1790217886761501000-8929afb6285845ac8150fcef38de293d`; verification `1790217889147231000-fd5f9dabe7444defb6c1808a601cf64a`; architecture `1790217912240041000-12242e03827e4bf4adee5fb10083228c`.
- Browser artifacts are outside the checkout in the acceptance record's reported temporary artifact directory. Fixtures used a disposable database/server/browser and no working-stand data.
- Full local `make test` / `make verify` was not run. Exact-source CI is owned by the PR checks for the final PR head.

## Scope boundaries and remaining #29

No merge, deployment or real financial operation is authorized or performed. This slice explains the current object ledger across calculations. The remaining full #29 still needs separate product acceptance/coverage for installer-level attribution, included work/progress, applied rules and excluded-sum reasons; the PR must not close #29 automatically.
