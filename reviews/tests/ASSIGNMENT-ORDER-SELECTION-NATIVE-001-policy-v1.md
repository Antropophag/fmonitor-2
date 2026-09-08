# Independent Gate 3 test review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 policy v1

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed test)
- Reviewed commit: `c1e82f7b0bb32b96b2f89c1b097b77c4b0609473`
- Reviewed test: `tools/architecture/tests/test_selection_native_owner.py`
- Test SHA-256: `b94180d596e6cedf298fba0c2d0944c380f523b28bcb5f7f71496fc7224955b0`
- Gate 1 spec SHA-256: `0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09`
- RED archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-policy-red-q8b__5kq`
- RED log SHA-256: `a1e962d0cbdc3b96d0727c935b8d4acc601c25034d47d4cd71b29c14f3fb80c0`
- Review date: 2026-09-06

## Blocking finding

1. **The test does not enforce the required absence of baseline growth.** The helper copies the repository's current `tools/architecture/baseline.json` into its isolated CLI fixture. The three positive DML cases can therefore be made green either by adding the intended narrow `AssignmentOrderComposition/MariaDb*.php` owner rule or by adding these three exact synthetic findings to the baseline. The latter violates the approved contract but satisfies every reviewed assertion; the two prohibition cases would continue to pass. This leaves a concrete unauthorized implementation capable of passing Gate 4.

Add an assertion through the same isolated public CLI fixture that makes baseline suppression impossible or directly proves the baseline contains no entries for the synthetic `app/AssignmentOrderComposition/MariaDbSelectionExample.php` findings. The test must fail if those findings are admitted by baseline entries and pass only when the narrow owner predicate admits DML. Preserve the existing checks that non-`MariaDb` application files cannot own SQL and `MariaDb` native adapters cannot own DDL.

## Passing review points

The existing RED is otherwise well targeted and independently reproducible. `SELECT`, `INSERT`, and `UPDATE` in the exact allowed path all fail under `sql_ownership`; application SQL and native-adapter DDL already pass their rejection assertions. The test uses the established public architecture CLI, creates isolated literal PHP fixtures, does not inspect private scanner functions, and does not require a production-code change outside `check.py`. DDL remains assigned to `InstallationProcess/*SchemaMigration.php`.

Independent reproduction ran three tests and exited `1`: the three DML subcases failed with exact `sql_ownership: new violation` findings, while both prohibition tests passed. This matches the captured archive and demonstrates the intended missing owner rule after valid test setup.

## Gate decision

Gate 3 is **CHANGES_REQUESTED**. Return only this policy test to Gate 2, add sensitivity against baseline suppression, recapture the intended RED, and request fresh independent review. This verdict does not reopen the native construction, core, schema, or tracer approvals and does not assess the separate tracer implementation.
