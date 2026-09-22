# Gate 5 CI-correction delta review: YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001

- Reviewer: independent Gate 5 agent `/root/gate3_review`; authored none of the specification, production implementation/asset, cutover test/contract, verification input, or CI evidence.
- Review date: 2026-09-22.
- Reviewed correction commit: `8847b05750478292d9f13993ea5b23740586a1e4` (`test: refresh construction queue asset contract`).
- Prior approved implementation: `d295c24a4489ed70607449aa62b7c32b005f8c13`; publication source identified by the delivery owner as `6fa2a95`.
- Exact commit delta: `openspec/changes/fix-construction-control-server-filtering/verification-input.json` and `tests/Support/yii2_production_web_cutover_contract.php` only. No production, specification, executable test logic, browser helper, schema, or writer bytes changed.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T015551Z-18f346b9be/package.json`.
- Verification plan SHA-256: `6bd1b4ee72ecd3db04865d71a59ba8002f826a2181e88165dc19ae40ef291bea`.
- Required reviews remain `gate3`, `final`; Gate 3 delta approval: `reviews/tests/YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001-v9.md`.
- Verdict: `APPROVED`.

## Findings

No findings.

## CI failure diagnosis and complete inventory

The supplied complete failed-job inventory contains one primary regression:

- `REGRESSION_FAILURE tests/Runtime/yii2_production_web_cutover_001_test.php`: expected the historical `control-queue.js` SHA-256 `0e80355fa58e3483609cb2486d33796c1dc3c01545fe30a930894bbe5be79f6e`, but the served reviewed asset had SHA-256 `589a7ec832c3d6d31f81ecdeedd6ca475fb904cdaaa084a94e8263b08b7a4b5f`.

The only other failed result was the dependent verification aggregate, which reflected that primary regression rather than a second failure. No authorization, HTTP, SQL, browser, offline, schema, photo, architecture, or unrelated test failure was reported. The correction therefore addresses the complete known CI failure inventory and does not conceal an unresolved parallel regression.

## Exact asset identity and expectation independence

An independent SHA-256 measurement of the committed public asset is:

```text
589a7ec832c3d6d31f81ecdeedd6ca475fb904cdaaa084a94e8263b08b7a4b5f  app/YiiRuntime/Assets/control-queue.js
```

The corrected contract literal matches that measurement, the failed CI actual value, and the asset hash bound by the fresh verification plan. The production asset is unchanged from the previously approved Gate 5 implementation. Its behavior was reviewed directly and covered by the approved PHP/browser matrix; this delta does not retroactively approve unknown bytes by copying a runtime value.

The hash remains one exact value. The change introduces no wildcard, alternate digest, dynamic self-acceptance, skip, conditional, or allow-failure mechanism.

## Cutover-contract strength

Only the expected body digest for `control-queue.js` changes. `tests/Runtime/yii2_production_web_cutover_001_test.php` still fetches the asset through `/pilot/assets/control-queue.js` and independently requires:

- HTTP 200;
- the exact body SHA-256;
- `text/javascript; charset=UTF-8`;
- `public, max-age=3600`;
- `X-Content-Type-Options: nosniff`;
- `Cross-Origin-Resource-Policy: same-origin`;
- exact attributable request/include inventory;
- absence of rapid-pilot includes;
- unchanged database and non-session private files across the public matrix.

A missing, stale, altered, truncated, misrouted, or incorrectly served asset will still fail. The correction does not weaken the production cutover boundary or any application acceptance expectation.

## Verification-input delta

The verification input adds both `tests/Runtime/yii2_production_web_cutover_001_test.php` and its contract helper to planned paths, and maps the executable cutover test into the existing whole-dataset acceptance. This is accurate because the changed queue asset is part of the public deployment boundary exercised by that test. The fresh plan binds the exact corrected contract, executable test, unchanged production asset, implementation, and prior review files; it reports no missing tests or unresolved acceptance mapping.

## Evidence status

The focused local cutover run reportedly passes the corrected `control-queue.js` assertion but cannot complete because unrelated `shlz` assets are absent from the local profile. That incomplete run is not GREEN and is not used as a substitute for CI. The prior CI environment contained those assets and reached the precise old-hash mismatch.

The package contains no harness-owned evidence, so automated evidence enforcement remains `UNKNOWN`. Corrected exact-source CI has not yet been reported and remains mandatory. This delta approval does not mark CI, merge, or deployment GREEN or authorize any of them.

## Verdict

`APPROVED`

Gate 5 approves the bounded correction at `8847b05750478292d9f13993ea5b23740586a1e4`. The corrected candidate may proceed to one exact-source CI run. Any further production, specification, test logic, contract, verification-input, or source-binding change requires applicable independent delta review.
