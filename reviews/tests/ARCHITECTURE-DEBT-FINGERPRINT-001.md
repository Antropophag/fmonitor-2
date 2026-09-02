# Test review: ARCHITECTURE-DEBT-FINGERPRINT-001

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/architecture_fingerprint_callable_context_review`
- Test author: another separately tasked agent; this reviewer did not author the test or implementation
- Reviewed ancestry: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`; dirty-tree bytes pinned below
- Specification: `ARCHITECTURE-DEBT-FINGERPRINT-001` contract supplied with the review handoff
- Public seams: `tools/architecture/check.py::finding(...)` and `tools/architecture/check.py::collect()`
- Red command: `python3 tools/architecture/tests/test_debt_fingerprint.py`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Callable-context Gate 3 restart

`APPROVED`. The current twelve-test suite closes the callable-context ambiguity left by the prior tokenizer review. The new method requires a leading global qualifier to remain fingerprint-sensitive when the fully qualified name is a constructor class or PHP 8 attribute class, including an attribute with comma-separated nested constructor and array arguments. It covers each case through `ddl_ownership`, `sql_ownership`, and `rapid_pilot_boundary`. Its ordinary fully qualified function-call control requires the same qualifier to remain fingerprint-neutral through all three buckets. The prior eleven methods remain unchanged in behavior and pass independently.

The PHP semantics are accurately targeted. Independent PHP 8.5.4 `token_get_all()` evidence emits `T_NAME_FULLY_QUALIFIED` followed by `(` for all three spellings, but the constructor is preceded by `T_NEW`, the attribute name follows `T_ATTRIBUTE`, and the direct function call has neither class context. Therefore the production implementation cannot safely classify a removable qualifier solely from the name token and following parenthesis. The nested attribute case also prevents an implementation from losing attribute context at the first nested `)` or comma.

Sensitivity, expected-value independence, and non-tautology pass. The new expectations are relational and contain no copied digest or reimplementation of production normalization. The three unequal pairs defeat the current blanket `T_NAME_FULLY_QUALIFIED`-plus-`(` normalization and a constant fingerprint; the equal direct-call control defeats never-normalizing. Each pair uses the production `collect()` seam with only `files()` patched to select the temporary production-shaped fixture. Both variants must create every requested production bucket before their complete finding lists are compared, and every fixture line contains literal DDL plus mutation SQL, so the assertions exercise detection, normalization, finding construction, and all three collectors rather than a proposed tokenizer helper.

Determinism, isolation, and cleanup pass. The fixture context owns close/unlink during success and assertion unwinding. No `rapid-pilot/FingerprintFixture*.php` existed before the run, after the nine-failure run, or after the prior-eleven run. Test and implementation hashes were identical before and after execution. No database, network, baseline, or implementation mutation is involved.

The reproduced RED is exact and attributable to missing callable-context behavior:

```text
$ python3 tools/architecture/tests/test_debt_fingerprint.py
......FFFFFFFFF.....

Ran 12 tests in 1.759s
FAILED (failures=9)
exit code: 1
```

The failures are exactly the Cartesian product of:

- `constructor-class-name`;
- `attribute-class-name`;
- `attribute-class-name-with-comma-separated-nested-arguments`;

and `ddl_ownership`, `sql_ownership`, `rapid_pilot_boundary`. No direct-function control, import, setup, reachability, or cleanup failure occurred.

Prior-eleven regression evidence:

```text
Ran 11 tests in 1.624s
OK
exit code: 0
```

Current reviewed-input hashes:

```text
51a78d95e39b2f5d9f44a3d3afd648e6f78416dd3016329f8156906e4cb0e27b  tools/architecture/tests/test_debt_fingerprint.py
25c807ccefaa57c18668c0a3fe3781199373511251a485eea0810ce092458f21  tools/architecture/check.py
```

Gate 4 may refine production token-context classification so only genuine direct function-call qualifiers are normalized. Constructor and attribute class qualifiers must remain part of the fingerprint; reviewed expectations and the architecture baseline must not change.

The earlier eleven-test review and RED transcript below are retained as historical evidence and are superseded for the current suite by this restart.

## Findings

No blocking findings.

Traceability and seam choice pass. The executable specification cites `ARCHITECTURE-DEBT-FINGERPRINT-001`. Focused relational cases exercise the public fingerprint-producing `finding(...)` seam. Filesystem-backed cases exercise the production `collect()` path, including production file selection, PHP source reading, SQL/DDL detection, both applicable rule buckets, source normalization and final fingerprint output. The test does not assert through a proposed private lexer or normalization helper.

The revised eleven-test boundary is precise. A leading global-namespace qualifier on an actual direct PHP call is fingerprint-neutral, including a call after a valid lowercase or uppercase PHP opener, after a PHP 8 attribute, and in a reopened PHP region. The same byte change remains fingerprint-sensitive before the opening tag, after a closing tag, in inline HTML, in PHP backtick strings, in ordinary and multiline quoted strings, heredoc, nowdoc, line/block comments and SQL/DDL literal evidence. The cases also require `<?phpfoo` to remain inline text and require `?>` inside either `//` or `#` line-comment syntax to close the PHP region. Namespaced call identity and genuine SQL/DDL text changes remain sensitive.

Sensitivity and expected-value independence pass. Equality and inequality come directly from the supplied contract; the suite neither copies an expected digest nor derives one with production normalization. A constant fingerprint fails the unequal cases. Never normalizing fails the actual-call equality cases, including the valid-opener control. Blanket slash removal or textual call replacement fails literal, comment, namespace, non-PHP and backtick cases. Treating an entire `.php` file as executable fails the tag-boundary cases. Accepting a mere `<?php` prefix fails `<?phpfoo`; treating line comments as hiding `?>` fails both comment cases. Treating reopened PHP as non-code fails the reopened-call equality case. Every collection comparison first proves that both variants reached `sql_ownership` and `rapid_pilot_boundary`, so equality cannot pass through absent collection.

The collection seam is meaningful rather than tautological. `files()` is patched only to select one temporary `.php` fixture under the real `rapid-pilot` root. Production `collect()` still reads the bytes, applies `production_file`, detects each original SQL/mutation line, invokes production normalization/fingerprinting and constructs both returned buckets. Because the temporary path is identical within each pair and each bucket contains the fixture finding, the asserted list relationship isolates evidence-byte handling without bypassing production collection behavior.

Rejected cases and tokenizer-edge controls are adequate for this slice. PHP 8.5.4 `token_get_all()` independently emits `T_OPEN_TAG` for `<?PHP ` and then `T_STRING` for the direct call, proving that uppercase spelling enters executable PHP. For the attribute fixture it emits `T_ATTRIBUTE` for `#[`, followed by the attribute and function tokens, then `T_STRING` for the direct call; the attribute is not a hash comment. The previously reviewed probes classify `<?phpfoo ...` as `T_INLINE_HTML` and both line-comment close-tag forms with `T_COMMENT`, `T_CLOSE_TAG`, then `T_INLINE_HTML`. The prior nine methods retain SQL/DDL, namespace, single-line and multiline lexical, PHP-region, backtick, opener-boundary, line-comment close-tag, and close/reopen coverage.

Determinism, isolation and cleanup pass. Fixed inputs determine all relationships. `NamedTemporaryFile` is created beneath `rapid-pilot`, and its context manager closes and unlinks the exact fixture during normal completion and assertion unwinding. No matching fixture existed before or after the reproduced full RED run or the prior-nine regression run. Random filename bytes affect only the common repository-relative identity of each compared pair. No database, network or production service is used.

The observed RED is for the intended missing behavior. The full eleven-test suite has exactly four failures: inequality is incorrectly observed for the uppercase PHP opener and for the PHP 8 attribute, across both `sql_ownership` and `rapid_pilot_boundary`. The prior nine test methods pass in a separate run. There is no import, setup, collection-reachability or cleanup failure. The checker and test remained byte-identical across execution.

Gate 4 may implement only PHP-region-aware handling that removes a leading global qualifier from an actual executable PHP direct call while preserving all reviewed literal, comment, backtick and non-PHP evidence unchanged. The reviewed expectations must not change.

## RED evidence

```text
$ python3 tools/architecture/tests/test_debt_fingerprint.py
......FF.FF..

FAIL: test_php_attribute_is_code_as_classified_by_php_tokenization ... (context='php-8-attribute-does-not-start-a-hash-comment', bucket='sql_ownership')
AssertionError: [...] != [...] : actual PHP global-call qualification must be fingerprint-neutral

FAIL: test_php_attribute_is_code_as_classified_by_php_tokenization ... (context='php-8-attribute-does-not-start-a-hash-comment', bucket='rapid_pilot_boundary')
AssertionError: [...] != [...] : actual PHP global-call qualification must be fingerprint-neutral

FAIL: test_php_open_tag_is_case_insensitive_as_classified_by_php_tokenization ... (context='case-insensitive-php-open-tag-enters-code', bucket='sql_ownership')
AssertionError: [...] != [...] : actual PHP global-call qualification must be fingerprint-neutral

FAIL: test_php_open_tag_is_case_insensitive_as_classified_by_php_tokenization ... (context='case-insensitive-php-open-tag-enters-code', bucket='rapid_pilot_boundary')
AssertionError: [...] != [...] : actual PHP global-call qualification must be fingerprint-neutral

Ran 11 tests in 0.024s
FAILED (failures=4)
exit code: 1
```

Prior-nine regression evidence (the two new token-edge methods excluded):

```text
Ran 9 tests in 0.020s
OK
```

Filesystem check before full RED: no `rapid-pilot/FingerprintFixture*.php` files.  
Filesystem check after full RED: no `rapid-pilot/FingerprintFixture*.php` files.  
Filesystem check after prior-nine run: no `rapid-pilot/FingerprintFixture*.php` files.

Independent tokenizer evidence (`PHP 8.5.4`, `token_get_all()`):

```text
CASE uppercase-opener
T_OPEN_TAG|<?PHP 
T_VARIABLE|$rows
...
T_STRING|mysqli_query

CASE php8-attribute
T_OPEN_TAG|<?php 
T_ATTRIBUTE|#[
T_STRING|FingerprintFixture
...
T_FUNCTION|function
...
T_STRING|mysqli_query
```

## SHA-256 reviewed-input manifest

```text
b8e5116b3a12a18c361b1c7900c7326bab612a0bacc25e5290402b081f52279e  tools/architecture/tests/test_debt_fingerprint.py
38103bcac30abb9d049cd43bd6e053ccd8eba71fc3fa02df32b2014fd717812f  tools/architecture/check.py
```

Implementation hash before RED: `38103bcac30abb9d049cd43bd6e053ccd8eba71fc3fa02df32b2014fd717812f`  
Implementation hash after RED: `38103bcac30abb9d049cd43bd6e053ccd8eba71fc3fa02df32b2014fd717812f`

## Required changes

None.
