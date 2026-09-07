# Independent Gate 3 amendment review — ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001

- Verdict: **APPROVED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, test or checker implementation.
- Date: 2026-09-07.
- Reviewed HEAD: `8a49a965d09854a92b4b4370fd99e2deda8d2bad`.
- Specification: version 0.1, SHA-256 `97eb3e3e7e141a5318d102aa81f58957da07c320c13dc2e2eb66eaca4047b235`; independent Gate 1 is `APPROVED`.
- Test: SHA-256 `6404c620328f33bb9cd4184372a579b6494d3e181592e57ca6d129c792aaed94`.
- Prior Gate 3 record: `ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001.md`, `CHANGES_REQUESTED`, retained unchanged.
- Checker SHA-256: `e56f3f0158a2f82c132d188814354698afcd4b802d29b7a433cce5b992fb665c` (unchanged).
- Baseline SHA-256: `1d20f4bd867e42144c43de462c413ab5e59effb3a88060c41f65543fa596f836` (unchanged).
- Public seam: `python3 tools/architecture/check.py --json` through the established isolated-repository fixture.

## Amendment disposition

Both prior findings are resolved. `test_php_select_array_key.py:23-32` now requires complete literal `sql_ownership` errors for SQL in a keyed value, SQL later on the same line, concatenated SELECT, and a longer SQL literal containing `select =>`. I independently calculated SHA-256 over each literal original source line and confirmed the asserted 16-hex prefixes:

```text
aed8be61d25c7b35  $request=['select'=>'SELECT id FROM users'];
3543c41d58574cf5  $request=['select'=>[]]; $db->query('SELECT id FROM users');
8e476761fb489065  $request=['select'=>[]]; $db->query('SELECT'.' id FROM users');
48c115cd75dcc689  $request=['select'=>[]]; $db->query("SELECT id FROM users WHERE label='select => value'");
```

These expected hashes are fixed test literals and are not obtained from the checker or its output. A scanner that fingerprints lexically scrubbed source can no longer pass.

The new case at `test_php_select_array_key.py:39-42` places the arrow on the following line and requires an exact violation fingerprint `763517130677fd92`. Independent calculation confirms that value is the hash prefix of the original violating line `$request=['select'`. This makes the contract's same-line boundary sensitive to an implementation that accidentally lets whitespace matching span newlines.

## Complete test assessment

The test cites the approved specification and invokes the actual public JSON CLI through the unchanged isolated-repository fixture. Each invocation copies the real checker and unchanged baseline into a fresh temporary tree and writes only a synthetic production-path source file. It does not call private scanner functions or derive expectations from implementation behavior.

Positive cases cover single and double quotes, ASCII case variation, nested arrays and optional same-line spaces with exact success status/envelope. Negative cases cover SQL in values and beside the key, bare and concatenated SELECT, longer SQL text containing key-like syntax, and the newline boundary with exact original-source fingerprints. The rapid-pilot fixture keeps DDL, SQL ownership and rapid-pilot mutation rules observable together. The unchanged baseline and exact empty/error envelopes prevent allowlisting or baseline growth from masquerading as the fix.

## RED evidence

Reviewed `/Users/antropophag/.local/state/fmonitor2-verification/bitrix-delivery-20260907/architecture-key-red-v2.log` and independently reran:

```text
python3 tools/architecture/tests/test_php_select_array_key.py
exit 1; four intended positive-subtest sql_ownership false positives; every negative test passes
```

The public CLI, temporary fixture, JSON envelope and fingerprint assertions all execute successfully. Only the four quoted same-line array-key acceptance examples fail because the current checker still detects them as SQL. This is a specific demonstrated RED for the missing behavior.

No blocking Gate 3 findings remain. Gate 4 may apply the minimal checker-only lexical correction without changing the reviewed expectations or baseline. Existing architecture-tool regressions, `make architecture-check`, and independent Gate 5 remain required. Only this v2 review record was added.
