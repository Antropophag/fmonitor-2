# Gate 3 CI asset-contract correction review: YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001

- Reviewer: independent Gate 3 agent `/root/gate3_review`; authored none of the specification, production asset, cutover test/contract, verification input, or implementation.
- Review date: 2026-09-22.
- Reviewed commit: `8847b05750478292d9f13993ea5b23740586a1e4` (`test: refresh construction queue asset contract`).
- Reviewed scope: one exact asset-hash value in `tests/Support/yii2_production_web_cutover_contract.php` and the cutover test/support verification-input binding.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T015551Z-18f346b9be/package.json`.
- Verification plan SHA-256: `6bd1b4ee72ecd3db04865d71a59ba8002f826a2181e88165dc19ae40ef291bea`.
- Corrected contract binding: `056ba9427e0ab464ccc41be5e028cc331f7b7816cac2ae5e8f88543790ddcf8d`.
- Planner decision remains `CRITICAL`; required reviews remain `gate3`, `final`; `missing_tests` is empty.
- Complete failed-CI inventory supplied for the preceding source: one primary `REGRESSION_FAILURE` in `tests/Runtime/yii2_production_web_cutover_001_test.php`, caused by expected old `control-queue.js` SHA-256 `0e80355f…` versus actual `589a7ec8…`; the dependent aggregate failed only because of that primary result.
- Verdict: `APPROVED`.

## Findings

No findings.

## Expected-value independence

The new expected SHA-256 is independently reproducible from the reviewed public asset:

```text
589a7ec832c3d6d31f81ecdeedd6ca475fb904cdaaa084a94e8263b08b7a4b5f  app/YiiRuntime/Assets/control-queue.js
```

This equals the asset hash bound by the prepared verification plan and the actual value reported by the failed CI test. It is not copied from a planned implementation calculation at runtime or relaxed to accept arbitrary content. The asset bytes themselves were reviewed and approved at Gate 5: they remove client-side row/count filtering, submit server filter controls, and retain offline sync, IndexedDB painting, prefetch, shipment, and checklist navigation.

Updating an exact asset manifest after an intentional reviewed asset change is the required contract maintenance. Leaving the old hash would assert bytes that no longer represent the approved behavior.

## No masking or weakened cutover boundary

The delta changes only the `control-queue.js` hash literal. It does not change the expected asset path, HTTP status, MIME type, one-hour public cache policy, `nosniff`, same-origin resource policy, request attribution, include inventory, no-rapid-pilot rule, database immutability, or private-file stability.

`yii2_production_web_cutover_001_test.php` still fetches every declared asset from `/pilot/assets/<asset>` through the production web boundary and hashes the response body independently. A missing, stale, substituted, truncated, incorrectly routed, or differently encoded `control-queue.js` will continue to fail. No allow-list broadening, wildcard, alternate hash, conditional skip, or allow-failure behavior was introduced.

The supplied complete CI failure inventory identifies no second primary regression hidden behind this correction. Updating the verification input to include both the executable cutover test and its contract helper makes the changed expectation visible to the planner and future exact-source review.

## Focused evidence and environment limitation

The focused cutover test reportedly passes the corrected `control-queue.js` asset assertion, then cannot complete locally because other `shlz` assets are absent from that local profile. Those missing assets are unrelated to this one-line delta and were present in the CI environment that reached and reported the old-hash mismatch. The incomplete local run is not represented as overall GREEN and cannot substitute for the required corrected exact-source CI run.

The prepared package binds the corrected contract and executable cutover test, reports no missing tests or unresolved acceptance mapping, but contains no harness-owned evidence. Automated evidence enforcement, corrected CI, merge, and deployment therefore remain `UNKNOWN`.

## Verdict

`APPROVED`

The exact asset-contract correction may proceed to focused verification, applicable final delta review, and a corrected exact-source CI run. Any further production asset, contract, test, verification-input, or source-binding change requires applicable independent delta review.
