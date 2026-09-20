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
