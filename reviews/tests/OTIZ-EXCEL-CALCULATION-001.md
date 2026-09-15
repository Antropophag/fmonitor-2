# Test review: OTIZ-EXCEL-CALCULATION-001

- Reviewer: Codex independent reviewer `/root/review_calc` (gpt-5.6-sol / low)
- Test author: root agent
- Reviewed source: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T174106Z-012b7e5d8c/snapshot`, patch SHA-256 `165fc49f3ab252c845b405d778c2b97143aec30f6f0ef42a58b5511e7ea096f0`; restored and checked at `/private/tmp/fmonitor-issue66-gate3.HeczzP/checkout`
- Agreed review scope / prior findings disposition (for rereview): Gate 3 for the first pure calculation slice only; parent document/publication integration is separately gated. First review, so there are no prior findings.
- Specification: `specs/OTIZ-EXCEL-CALCULATION-001.md` (SHA-256 `64b3c3390c9722298d73ef20208de108c76750f2171651c13cbb2d935a104bbe`)
- Public seam: `FMonitor2\Otiz\PremiumCalculationV2::calculate(array $operands, array $paymentEvidence, array $exclusions=[]): array` and `allocate(int $poolCents, array $participants): array`
- Red command and intended failure: `php tests/Otiz/excel_calculation_001_test.php`; exit 1 because `FMonitor2\Otiz\PremiumCalculationV2` is absent. Reproduced on the restored snapshot. This matches retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789407657184427000-8765e0db9dd04a7b83ddecd281b2e044.json` (`INTENDED_RED`).
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — incomplete rejection matrix for payment evidence** (`specs/OTIZ-EXCEL-CALCULATION-001.md:11`, `tests/Otiz/excel_calculation_001_test.php:41-49`). The contract requires the complete `{closures:list,actualPayouts:list}` evidence grammar, signed integer closure amounts within the per-row bound, exact dates and sources, and rejection of invalid or missing evidence. The test checks one malformed closure date/source and aggregate ranges, but it does not distinguish missing/non-list collections, missing row fields, non-integer or individually out-of-range closure amounts, or any malformed `actualPayouts` row. An implementation could ignore or accept malformed informational payouts and still pass.
2. **HIGH — `formulaTrace` is not tested against the normative operations and results** (`specs/OTIZ-EXCEL-CALCULATION-001.md:13`, `tests/Otiz/excel_calculation_001_test.php:17`). The sole assertion `count(...) >= 5` permits arbitrary placeholders and does not prove that every money operation, order, and result is identified. The trace is a public audit result in this pure slice and needs exact, independently stated expectations for a worked calculation.
3. **MEDIUM — incomplete operand and exclusion validation sensitivity** (`specs/OTIZ-EXCEL-CALCULATION-001.md:9-11`, `tests/Otiz/excel_calculation_001_test.php:41-49`). Operand sources are tested only with empty fields, so uppercase/wrong-length/non-hex SHA-256 values and missing envelope members are not discriminated. The single exclusion `['code'=>'bad']` fails several rules simultaneously and therefore does not prove enforcement of uppercase code, exact effective date, or complete source grammar independently. Add one-fault cases for each rule so permissive implementations cannot pass accidentally.
4. **MEDIUM — allocation input contract is only partly covered** (`specs/OTIZ-EXCEL-CALCULATION-001.md:17`, `tests/Otiz/excel_calculation_001_test.php:50-55`). The rejection cases omit non-integer pool/weight values, missing participant keys, non-string tabs, invalid UTF-8, and the 120-character boundary/overflow. The positive cases establish conservation and ASCII tie order but do not discriminate the required binary lexicographic ordering for non-ASCII valid tabs. These are explicit parts of the public pure seam.

Traceability for the arithmetic examples, date selection, Kss floor, HALF-UP boundaries, recurrence after confirmed payments, signed reversal, informational payout non-subtraction, exclusion zeroing, deterministic replay, input non-mutation, allocation remainders/ties/conservation, and the version identifiers is otherwise direct and uses literal expected values. The test is deterministic, uses the public seam, and has no production-system dependency. The restored hashes match the prepared plan bindings. The intended RED is a clean missing-behavior failure rather than setup failure.

## Required changes

1. Add isolated payment-envelope rejection cases covering both collections and every closure/actual-payout field and type/range rule.
2. Assert an exact `formulaTrace` contract that identifies every specified operation and its result for at least one independently worked example.
3. Add one-fault operand source/envelope and exclusion cases for the explicit date, SHA-256, code, and required-field rules.
4. Complete allocation rejection boundaries/types and add a valid case that distinguishes binary lexicographic tab ordering.

## Correction rereview — 2026-09-14

- Reviewed source: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T174454Z-84dd390100/snapshot`, patch SHA-256 `435bd41feb3b9eac336998f6e1d949b70865bae7449a298d63167c5eadea17dc`; restored and checked at `/private/tmp/fmonitor-issue66-gate3-r2.f764x7/checkout`
- Corrected specification SHA-256: `58b21ab2feb9dcf6ff26f26057f0c1833497d920fbaf4b2a5231d48aab7d106a`
- Corrected test SHA-256: `b59647fdfc02a893e96d73c0d91b7d9aa62892743786e76ab04f4a5d09c0ba56`
- Red command and intended failure: `php tests/Otiz/excel_calculation_001_test.php`; exit 1 because `FMonitor2\Otiz\PremiumCalculationV2` is absent in all seven groups. Reproduced on the restored snapshot and consistent with retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789407888139435000-2e5c61eec2f24cafa5b82d3020191132.json` (`INTENDED_RED`).
- Prior findings disposition: finding 2 resolved by a normative exact ordered trace and exact assertion; finding 3 resolved by isolated missing-envelope, SHA, exclusion-code/date/source cases; findings 1 and 4 substantially corrected but retain the two gaps below.
- Verdict: `CHANGES_REQUESTED`

### Complete rereview findings

1. **MEDIUM — signed informational payout behavior lacks a valid negative-row case** (`specs/OTIZ-EXCEL-CALCULATION-001.md:23`, `tests/Otiz/excel_calculation_001_test.php:59-69`). The corrected contract explicitly gives `actualPayouts` the same signed amount validation as closures while requiring only the aggregate to remain nonnegative. Tests prove a negative aggregate is rejected and a positive informational row is ignored by pool calculation, but an implementation that rejects every negative `actualPayouts.amountCents` would still pass. Add a valid positive-plus-negative informational payout list with a nonnegative aggregate, assert it remains informational, and retain the payment evidence exactly.
2. **MEDIUM — the UTF-8 120-character boundary is tested only with single-byte text** (`specs/OTIZ-EXCEL-CALCULATION-001.md:17`, `tests/Otiz/excel_calculation_001_test.php:71-78`). `str_repeat('x', 120)` cannot distinguish a character-count implementation from a byte-count implementation. Since the contract says valid UTF-8 tabs up to 120 characters, add accepted 120-character and rejected 121-character multibyte tabs. The existing invalid-UTF-8 and non-ASCII binary-order cases cover separate rules and do not exercise this boundary.

The corrected payment collection/list/row rejection matrix, exact trace, missing operand envelopes, SHA grammar, isolated exclusion rules, allocation types and shapes, invalid UTF-8, ASCII length edge, and binary ordering are otherwise sensitive and traceable. No new scope was introduced, and the RED remains deterministic and isolated.

### Required changes for approval

1. Add the valid signed `actualPayouts` case described above.
2. Add multibyte UTF-8 tab cases at 120 and 121 characters.

## Second correction rereview — 2026-09-14

- Reviewed source: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T174700Z-1e44b3d1ee/snapshot`, patch SHA-256 `3b6427d283f053b2aff445d7252b8d9658abd9bcbcfb8208c06aa5982a02865e`; restored and checked at `/private/tmp/fmonitor-issue66-gate3-r3.OrdirO/checkout`
- Specification SHA-256: `58b21ab2feb9dcf6ff26f26057f0c1833497d920fbaf4b2a5231d48aab7d106a`
- Test SHA-256: `fef9233fdccbc853cfa688e1a9055457f1d69f0a911a10786ad9bb2ec67910f1`
- Red command and intended failure: `php tests/Otiz/excel_calculation_001_test.php`; exit 1 because `FMonitor2\Otiz\PremiumCalculationV2` is absent in all seven groups. Reproduced on the restored snapshot and consistent with retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789408013127932000-18be2220f7ce477481b8edbfed61ba1f.json` (`INTENDED_RED`).
- Prior findings disposition: all six findings across the original review and first correction rereview are resolved. The second correction accepts a positive-plus-negative informational payout list with a nonnegative aggregate while proving it does not alter the pool, and distinguishes character count from byte count with accepted 120-character and rejected 121-character multibyte UTF-8 tabs. All earlier scenarios remain present.
- Verdict: `APPROVED`

### Complete findings

None for the agreed pure calculation scope. The normative contract and corrected test now cover the full calculation/date/rounding/replay/evidence/validation/allocation matrix at the two public pure seams. Expected values are literal and independently traceable, validation cases are sensitive to plausible permissive implementations, the exact trace is observable, and the test remains deterministic and isolated. Parent certificate, persistence, publication, HTTP, authorization, concurrency, deployment, and other integration behavior remain outside this Gate 3 decision and require their separately prepared gates.

### Required changes

None.
