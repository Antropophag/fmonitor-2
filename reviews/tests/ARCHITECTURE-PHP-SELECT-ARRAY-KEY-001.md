# Independent Gate 3 test review — ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, test or checker implementation.
- Date: 2026-09-07.
- Reviewed HEAD: `9e9f702dbb43ca3552ef449c2bca17a647bf70ea`.
- Specification: version 0.1, SHA-256 `97eb3e3e7e141a5318d102aa81f58957da07c320c13dc2e2eb66eaca4047b235`; independent Gate 1 is `APPROVED`.
- Test: SHA-256 `27cb24d3c205f9cc848f22a7fb669a3c569a1080f9cc9eedc8bdb419194d0527`.
- Public seam: `python3 tools/architecture/check.py --json` through the established isolated-repository fixture.
- Checker SHA-256: `e56f3f0158a2f82c132d188814354698afcd4b802d29b7a433cce5b992fb665c` (unchanged).
- Baseline SHA-256: `1d20f4bd867e42144c43de462c413ab5e59effb3a88060c41f65543fa596f836` (unchanged).

## Material findings

1. **The required original-source fingerprint is not asserted.** The approved contract requires real violations beside an exemptible key to retain their fingerprint from the original normalized source. In `test_php_select_array_key.py:21-32`, all four relevant SQL-value/same-line cases accept any suffix after `sql|app/Workforce/Request.php|`. The bare case at `:34-37` is looser still. An implementation could remove the array-key text before both detection and fingerprinting, changing debt identity while every new test remains green. Existing generic fingerprint tests do not establish the exact identity for these new key-bearing fixtures. Add literal complete expected errors, or independently calculated exact fingerprints, for representative SQL-in-value and same-line cases containing the `select` key. This is a stated acceptance result, not an implementation detail.

2. **The same-line boundary is not failure-sensitive.** The contract exempts a quoted key only when optional spaces and `=>` occur on the same line. Positive cases at `test_php_select_array_key.py:15-19` cover spaces, nesting, quotes and case, but there is no rejected fixture with a newline between the quoted token and `=>`. A broad implementation using whitespace matching across newlines would pass the suite while expanding the exemption beyond the approved lexical rule. Add the direct multiline negative case and require its exact `sql_ownership` result/fingerprint.

## Sound coverage

The test reuses only the established `inspect` helper from the unchanged SELECT-token suite. That helper copies the actual checker and unchanged baseline into a fresh temporary repository, creates the literal production-path fixture, and invokes the public JSON CLI with a bounded subprocess. It therefore exercises the correct seam without private detector calls or repository mutation.

The four positive subtests independently cover single/double quotes, ASCII case-insensitivity, nesting and optional same-line spaces. Negative fixtures correctly preserve SQL in the value, SQL later on the same line, concatenated SELECT, a longer SQL literal containing `select =>`, and a bare concatenated fragment. The rapid-pilot fixture simultaneously proves DDL, SQL ownership and rapid-pilot mutation rules remain active. Expected status, envelope and rule names are literal contract values rather than derived from checker internals.

## RED evidence

Reviewed `/Users/antropophag/.local/state/fmonitor2-verification/bitrix-delivery-20260907/architecture-key-red.log` and independently reran:

```text
python3 tools/architecture/tests/test_php_select_array_key.py
exit 1; four intended positive-subtest sql_ownership false positives; all negative tests pass
```

Setup, JSON decoding and the public CLI all succeed. The RED is caused by the current checker treating each quoted array key as SQL. No test, checker, baseline or specification was changed during review.

Return to Gate 2 for the two sensitivity gaps, then obtain a new independent Gate 3 review. Checker implementation must not begin on this verdict. Only this review record was added.
