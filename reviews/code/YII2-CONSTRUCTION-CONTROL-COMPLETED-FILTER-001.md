# Code review: YII2-CONSTRUCTION-CONTROL-COMPLETED-FILTER-001

- Reviewer: `/root/gate3_completed_filter`
- Implementation author: `/root/executor_completed_filter`
- Verdict: `APPROVED`

## Findings

None. Completion derives from native `pto_act + declaration`; PTO-only working cases are excluded; completed rows participate in total/pagination after active work. Existing accepted-original identity predicate and authoritative preopening projection remain intact. Completed-only historical engineer fallback preserves «Мои» without changing active or ready rows. Access remains before read and no facts/schema are written.

The exact test covers identities, ordering, total, pagination, markers, toggle round trip, HEAD, guest, permission denial, repeat GET and unchanged facts. Exact-source CI was still `UNKNOWN` at review.

---

## Latest-stand exact-source rereview — 2026-09-20

- Reviewer: `/root/gate3_completed_filter`
- Implementation author: `/root/executor_completed_filter`
- Base: `c4098c554159754ddd5ebe614406dd80f00222e9`
- Candidate source: `5f7f9b8cb1f3b62afb2b10a021e6de58bac6962482f8b06bf69db24f3aecb189`
- Executable source: `34666ffbf56318608f12ac0c84e2f45faf03faaf6fe65d5affcc83c484a927e4`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T153716Z-f22227aac1/package.json`
- Verification plan SHA-256: `ef9b82a62579e4adf3564fcdc3fdd4c32b02d4561260c3b0b971e68838f83c0f`
- Verdict: `APPROVED`

### Complete findings

None.

The latest-stand candidate preserves the newer server-side pagination rather than reverting to in-memory eligibility. The identical `$active` expression drives both total and row selection; `LIMIT/OFFSET` are applied after completion and preopening eligibility, and ordering keeps completed `pto_act + declaration` rows after active work while preserving activity/object ordering within each group. PTO-only working cases fail the predicate, while fully completed working cases remain in total and pagination with `completed=true`.

The non-working branch retains the full accepted-current-original predicate: the latest selection must reference the original root’s assignment order and match both `composition_identity` and `composition_sha256` under binary comparison. Thus the change does not weaken the newer stand’s selection/original identity boundary. Working rows bypass that preopening predicate exactly as before. Current assignment remains authoritative; the existing completed-only historical engineer fallback is unchanged.

The complete OpenSpec proposal, design, delta requirement, verification input, and tasks agree with the canonical contract and implementation. The approved Gate 3 record retains its corrected active-row and toggle-restoration sensitivity. The package reports no missing tests and retains the governance, unit, e2e, consumer-frontier, and semantic-integration obligations selected by the planner; no production JavaScript, writer, schema, authorization rule, or append-only fact owner changes in this slice.

Fresh record `1789918614455210000-f66c178487bf4f94901e303b217cc390` is source- and environment-bound GREEN for `php tests/Yii2/yii2_construction_control_active_queue_001_test.php` in 8.29 seconds. It proves exact identities, second-page tail, completed-inclusive totals, shared pagination markup, PTO-only transition, completed toggle hidden/visible/hidden, active-row preservation, repeat GET, HEAD, guest, exact permission denial, and unchanged facts. `git diff --check` is clean.

The one exact-source CI consumer remains pending and deployment status is not established by this review; both remain `UNKNOWN` until separately evidenced. No prohibited local full suite was run.

### Required changes

None.

---

## CI-return correction Gate 5 review — 2026-09-20

- Reviewer: `/root/gate3_completed_filter`
- Base: `c4098c554159754ddd5ebe614406dd80f00222e9`
- Candidate source: `3f0ffbf847651e04dcfca67a7a244cceda635be4d0edc337648037f49f629bf2`
- Executable source: `d67c4da1dc3be8062ff6a737463e6bef8205a5045301823bcacafee280bb82d7`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T155707Z-a285fa3a90/package.json`
- Verdict: `APPROVED`

### Complete findings

None.

The correction changes only the stale end-to-end expectations and acceptance mapping. Production code, canonical spec, completed/PTO-only predicates, server-side count and pagination, completed-last ordering, selection/original binary identity gate, historical engineer fallback, authorization, and append-only owners are unchanged from the approved latest-stand candidate.

The full failed-job inventory is coherent with the delta: the former e2e expectation rejected the now-required completed row, and verify failed only because e2e failed; governance, fast, unit, and integration were GREEN. The corrected e2e remains strict on exact queue composition/order, completion marker, default active-only counts, completed reveal, completed search, current and missing assignment behavior, persisted checklist history, account isolation, offline cache isolation, browser errors, asset loading, and legacy-runtime exclusion. Adding it to `verification-input.json` closes the mapping gap without changing execution semantics.

Both mapped tests are fresh GREEN at the exact candidate and executable source: queue record `1789919788567528000-71c513a23efe44bf9c19f42e928965e0` and browser record `1789919788567533000-18f8e0eec26e464cb479bb581818faf2`. The package reports no missing tests and `git diff --check` is clean.

The failed exact-source CI run is not retroactively GREEN. A corrected exact-source CI consumer remains required; deployment status also remains `UNKNOWN` unless separately evidenced.

### Required changes

None.
