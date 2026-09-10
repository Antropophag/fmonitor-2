# Code review: YII2-USER-ACCESS-001

- Reviewer: Codex independent sol/low reviewer `/root/review76_access`; authored neither implementation nor specification/tests
- Implementation author: `/root/implement76_access` (all production code); root authored specification, tests and delivery records
- Reviewed source: base commit `2cd9ffa03a6359b41dd598fae741801be5585d76` plus retained snapshot `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-access-gate5-composed`, patch SHA-256 `530493c072cec0b0e2e313a09ee7ba98f7b60160fd31abcf4690dd89c4cd0c21`
- Agreed review scope / prior findings disposition (for rereview): first actual Gate 5 whole-candidate review; no prior Gate 5 findings
- Specification: `specs/YII2-USER-ACCESS-001.md`
- Approved test review: `reviews/tests/YII2-USER-ACCESS-001.md`, final Gate 3 verdict `APPROVED`
- Verification plan: `.local/verification/76-plan.json`, SHA-256 `ff8eabb178e4e1f43c9afba82e18a3330354471e1102b1bed54ad052ef9b2027`; `CHANGE_VERIFICATION_OK`
- Verification commands/evidence: focused main, edges, concurrency and browser suites GREEN in `/tmp/76-access-{main,edges,concurrency,browser}-composed.log`; actual `make architecture-check` including PILOT-HTTP-AUTH qualification passed seven rules in `/tmp/76-access-architecture-composed.log`; required neighboring Yii auth/session/OTIZ and policy/runtime/inventory checks are recorded GREEN in `docs/operations/yii2-user-access-delivery-2026-09-10.md`. No full local run or CI was performed for this gate.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — some public owner commands decide validation/self-target outcomes before the required transactional authorization check.** `app/IdentityAccess/MariaDbUserInvitations.php:13-15` validates invite fields before `MariaDbUserAccessTransaction::run()`, `app/IdentityAccess/MariaDbUserRoleChanges.php:10-11` rejects an unknown action before `run()`, and `app/IdentityAccess/MariaDbUserStatusChanges.php:11-13` rejects an unknown action or self-target before `run()`. The normative admission contract requires the owner to recheck active actor, active role and exact permission inside the transaction for every administrative command. At the confirmed public `YiiUserAccess` seam, an unauthorized caller can therefore receive `invalid` based on invite/action details instead of authorization taking precedence, and self-target denial occurs without proving the actor remains authorized. The HTTP access filter does not repair the owner invariant because other callers can invoke the public application seam directly. Move these checks into `run()` after the live `access.administer` check, preserving no-facts behavior and `access_denied` precedence for unauthorized callers. Add direct-owner regression cases for unauthorized malformed invite/role/status input and unauthorized/self combinations; this concrete Gate 5 test gap restarts the changed test delta at Gate 2/3.

The remaining implementation conforms to the reviewed scope: the facade composes focused MariaDB collaborators on one Yii connection; every entered mutation uses the shared serializable transaction and common lock order; role/status projection and append-only events commit together; activation preserves invitation lineage and credential atomicity; last-superadministrator races are serialized; controllers derive actor identity from Yii, use framework request/session/CSRF behavior, and convert failures safely; views escape dynamic content and use repository assets; no rapid-pilot or old HTTP ownership is introduced.

The raw activation URL remains an acknowledged deployment boundary rather than a new finding against this pre-cutover application snapshot: the approved application harness proves Yii-owned logs do not disclose it, production still serves the rapid runtime, and the delivery record requires nginx request-target protection before final Yii cutover. The later proxy-privacy integration is a changed boundary and must receive its own independent review before authoritative CI.

## Required changes

- Enforce transactional live authorization before all invite/role/status validation and self-target decisions.
- Add the direct-owner regression cases described above and obtain independent review of that test delta before Gate 5 rereview.
- Correct the stale users-view hash in the delivery record (`f0cf16...`); both the live candidate and restored snapshot hash to `9147ccc6ed7611f1283894305bc0619c95e11503e64351c61aea354b5d9143ca`.

## Rereview 1 — authorization-precedence correction

- Reviewer: Codex independent sol/low reviewer `/root/review76_access`; authored neither implementation nor specification/tests
- Implementation author: `/root/implement76_access`
- Reviewed source: base commit `2cd9ffa03a6359b41dd598fae741801be5585d76` plus retained snapshot `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-access-gate5-auth-corrected`, patch SHA-256 `9e91867a466c25217c88c649af01143026e02474057bf6a75494fdb43ae1659e`
- Agreed review scope: production delta in `MariaDbUserInvitations`, `MariaDbUserRoleChanges`, and `MariaDbUserStatusChanges` correcting the sole first-review defect; the associated test/spec delta is independently approved in `reviews/tests/YII2-USER-ACCESS-001.md`
- Verification plan: `.local/verification/76-plan.json`, SHA-256 `410833d961438268e653865ccaf997f6d629b80b97f81d1fffdb3c443acdc057`; `CHANGE_VERIFICATION_OK`
- Verification evidence: approved edges suite GREEN in `/tmp/76-access-edges-auth-precedence-green.log`; main owner/HTTP suite GREEN in `/tmp/76-access-main-auth-precedence-green.log`; actual architecture check and PILOT-HTTP-AUTH qualification GREEN in `/tmp/76-access-architecture-auth-precedence-green.log`. UI, concurrency and neighboring modules are unchanged by this three-adapter delta.
- Prior findings disposition: authorization-precedence defect resolved; direct-owner regression delta approved; delivery record now distinguishes the historical visual hash from final users-view hash `9147ccc6ed7611f1283894305bc0619c95e11503e64351c61aea354b5d9143ca`
- Verdict: `APPROVED`

### Findings

None.

### Disposition

The correction is limited to moving invite field normalization/validation, role action validation, and status action/self-target validation inside the existing shared transaction immediately after the live `access.administer` check. Unauthorized malformed calls now return `access_denied`; an unavailable authorization source throws before validation can mask it; authorized invalid inputs retain their prior results. The common serialization lock, one-connection boundary, projection/history writes, and all later command rules are unchanged.

Gate 5 is approved for the exact retained snapshot above. The separately tracked nginx privacy integration remains a changed boundary requiring its own review before authoritative CI.
