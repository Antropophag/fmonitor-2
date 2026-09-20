# Test review: YII2-CONSTRUCTION-CONTROL-COMPLETED-FILTER-001

- Reviewer: `/root/gate3_completed_filter`
- Test author: root
- Public seam: Yii `GET|HEAD /pilot/construction-control` plus shipped completed toggle
- Initial verdict: `CHANGES_REQUESTED` — active-row preservation and switch-off restoration were missing
- Correction verdict: `APPROVED`

## Findings

None after correction. The corrected browser driver records before/after/restored; active row remains visible and completed row is hidden/visible/hidden. Fixtures independently distinguish active, PTO-only, completed and non-working cases. Literal totals, page tail, permission, guest, HEAD and fact snapshots are deterministic. Intended RED was exact missing completed row: 51 rather than 52.

No local full suite was run. CI remained `UNKNOWN` at Gate 3.

---

## CI-return correction Gate 3 review — 2026-09-20

- Reviewer: `/root/gate3_completed_filter`
- Test author: root
- Candidate source: `3f0ffbf847651e04dcfca67a7a244cceda635be4d0edc337648037f49f629bf2`
- Executable source: `d67c4da1dc3be8062ff6a737463e6bef8205a5045301823bcacafee280bb82d7`
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T155707Z-a285fa3a90/package.json`
- Verification plan SHA-256: `d729f8185997302577f500435c886926e96460d5efa185220523ac1de92222ea`
- Failed-CI inventory reviewed: sole `REGRESSION_FAILURE` was `tests/Yii2/yii2_inspection_browser_001_test.php` in e2e; verify failed only as its aggregate consequence; governance, fast, unit, and integration were GREEN
- Verdict: `APPROVED`

### Complete findings

None.

The corrected browser expectations do not weaken the prior journey. They replace the obsolete assumption that completed object 4514 is absent with stronger exact assertions for the new contract: server DOM order must be `[4513,4512,4514]`, object 4514 must carry `data-completed="true"`, the default Mine/completed-off state must show exactly one active row, All/completed-off must show exactly two active rows, and enabling completed rows must show exactly three. Literal search for `QUEUE-4514` must then expose exactly the completed row, while active search and the no-result empty state remain required.

Assignment and access/history expectations remain intact around the correction. Object 4513 still proves no legacy engineer fallback for an unassigned active row; object 4512 still proves the standalone current engineer is authoritative. The journey continues through logout, another-account 403, offline cross-account cache isolation, zero browser/asset errors, exact eleven-operation history and revision sequence, actor attribution, response-loss replay identity, mixed photo/section ordering, and `noLegacy()`. The mapped queue test separately retains completed hidden/visible/hidden restoration, PTO-only exclusion, pagination/total, guest/HEAD/exact permission, repeated reads, and unchanged completion facts.

The verification input now maps both public-seam tests, so the CI-return regression cannot fall outside the change inventory. Fresh source- and environment-bound records are GREEN: `1789919788567528000-71c513a23efe44bf9c19f42e928965e0` for the queue test and `1789919788567533000-18f8e0eec26e464cb479bb581818faf2` for the full browser journey. `git diff --check` is clean.

### Required changes

None.
