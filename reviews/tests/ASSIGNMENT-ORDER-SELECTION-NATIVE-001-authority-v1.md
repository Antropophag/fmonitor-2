# Independent Gate 3 test review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 authority v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed test)
- Reviewed commit: `1b53793c7728f9948da34bfc30c3063749762c9d`
- Reviewed test SHA-256: `90d5090c55fdc04ece0bdc1e1b46fecd3b3313dc964fdc73ad614009cada58cf`
- Gate 1 spec SHA-256: `0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09`
- Originating Gate 5 findings: `reviews/code/ASSIGNMENT-ORDER-SELECTION-NATIVE-001-tracer-v1.md`
- RED archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-authority-red-ha93ezz8`
- RED log SHA-256: `40b4de36237f99f6ddf7ab3dd8f6cc5a3e0730b30983fe9e5085cc16a1bb5c00`
- Review date: 2026-09-06

## Findings

No blocking test finding was found in this focused correction tranche.

The local-authority case reaches the public `selectAssignmentOrderComposition` seam. It first creates a real selected terminal result, then makes the canonical local family partial by displacing `fm2_pilot_users` while retaining other local-family tables and installs a fictional but otherwise granting legacy authority. The current adapter incorrectly reauthorizes through legacy and discloses the stored success as `replayed`; the required exact outcome is `failed/dependency_unavailable` with null success and byte-for-byte unchanged observed state. This is a sensitive reproduction of the mode-fallback defect, including absence of audit or other mutation.

The fresh-reader cases exercise the public native verification port with genuinely distinct connections. They independently vary database, authenticated database principal, and connection charset. Each requires rejection, proves the candidate was opened, proves that rejection closed it, and confirms that the caller-owned primary connection remains usable. The wrong-user fixture has full synthetic read grants, so its failure cannot be satisfied by relying on a denied query. The positive control proves that a distinct connection with the same database authority and charset remains accepted and can return a typed `NOT_FOUND` result.

Expected outcomes follow the approved construction contract and originating Gate 5 findings. The test does not implement connection comparison, inspect private adapter state, add a fault hook, or use a production-system dependency. Each case owns a fresh synthetic database and reports both successful setup and cleanup.

## Reproduced RED

The retained command ran five cases and exited `1`. Four intended assertions failed:

- partial local-family damage returned `replayed` with the confidential success payload instead of `failed/dependency_unavailable`;
- wrong database, charset, and database user connections were each accepted instead of rejected and closed.

The same-authority independent-reader control passed. All five cases reported `SETUP_OK` and `CLEANUP_OK`. Production code was unchanged, so the failures are the intended missing fail-closed behavior rather than setup or inherited regression failures.

## Gate decision

Gate 3 is **APPROVED** for the exact focused test commit and hash above. Gate 4 may implement only the two reviewed corrections: any presence of the canonical local identity family requires full-family compatibility before local authorization, and a fresh recovery connection must match factory-captured primary database, authenticated user, and charset metadata while remaining distinct and idle. Rejected fresh candidates must be closed; the primary remains caller-owned. Fresh Gate 5 review remains required.
