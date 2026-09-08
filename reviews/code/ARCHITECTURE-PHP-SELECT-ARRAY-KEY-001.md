# Independent Gate 5 code review — ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001

- Verdict: **APPROVED**
- Reviewer: `/root/bitrix_gate3`, separately tasked agent; not an author of the specification, tests or implementation.
- Date: 2026-09-07.
- Reviewed source commit: `98ded5c64e7b6497be8e871f9e9e796c7c7b5297`.
- Specification: version 0.1, SHA-256 `97eb3e3e7e141a5318d102aa81f58957da07c320c13dc2e2eb66eaca4047b235`; independent Gate 1 `APPROVED`.
- Approved test: SHA-256 `6404c620328f33bb9cd4184372a579b6494d3e181592e57ca6d129c792aaed94`; independent Gate 3 v2 `APPROVED` and unchanged during implementation.
- Checker: SHA-256 `9ca561ae1a4b6c2e394eaebb64e22ec71ec9342af8efbc23ddb2b6e2d005f1b8`.
- Baseline: SHA-256 `1d20f4bd867e42144c43de462c413ab5e59effb3a88060c41f65543fa596f836`, unchanged.

## Findings

The implementation is the minimal lexical correction approved by the contract. In `tools/architecture/check.py:179`, `is_select_key` becomes true only when the complete contents of an already matched PHP quoted literal are ASCII `select` under case normalization and the suffix immediately begins with optional non-newline whitespace followed by `=>`. Line-by-line invocation already prevents cross-line matching; the explicit exclusion of CR/LF further preserves the stated same-line boundary. Escaped, longer, bare and concatenated literals do not satisfy the whole-token comparison.

At `tools/architecture/check.py:180`, only that quoted key token is replaced in the SQL detection copy, through the existing token-by-token path. The value and all remaining text on the same line continue through normal SQL detection. Existing dotted capability atoms and SELECT identifiers retain their prior behavior. DDL and rapid-pilot rules inspect the original line independently and are untouched.

Debt identity remains correct. Detection uses `php_sql_detection_line(line)`, while `finding()` at `:231` still receives the corresponding untouched `fingerprint_lines` entry created before scanning. No implementation path hashes the scrubbed detection line. The approved exact-fingerprint cases therefore protect the original normalized source identity for SQL in a keyed value, beside a key, in concatenation, and across the newline boundary.

The production diff changes only the two lexical statements in the checker; there is no file allowlist, baseline growth, protected-test edit, source ownership relaxation or product/runtime mutation. The OpenSpec task status updates and existing review record do not alter behavior. The unrelated untracked Bitrix operations document was outside this review.

## Verification

Reviewed the external GREEN evidence under `/Users/antropophag/.local/state/fmonitor2-verification/bitrix-delivery-20260907/`:

```text
architecture-key-green.log    5 focused tests PASS
architecture-tests-green.log  40 architecture unittests PASS
architecture-green.log        ARCHITECTURE CHECK PASSED (7 rules)
```

The author also recorded diff-check PASS with the baseline and protected E2E surface unchanged. I independently reran at the reviewed source commit:

```text
python3 tools/architecture/tests/test_php_select_array_key.py
Ran 5 tests — OK

python3 -m unittest discover -s tools/architecture/tests -p 'test_*.py'
Ran 40 tests — OK

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
```

The approved test would catch the plausible regressions relevant to this change: failing to recognize case/quote/nesting variants, erasing SQL values or same-line SQL, accepting a cross-line arrow, suppressing concatenated/long literals, changing source fingerprints, or disabling DDL/rapid-pilot enforcement.

No blocking findings remain. ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001 satisfies Gate 5. Only this code-review record was added.
