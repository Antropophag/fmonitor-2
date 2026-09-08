# Architecture SELECT/markup detector correction — independent review

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the detector or test changes.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `c02f1a23121058fb0046e4b8fec859c5754ecb6d`.
- Scope: only `tools/architecture/check.py` and `tools/architecture/tests/test_php_select_tokens.py` working-tree diffs against that base.
- Test verdict: `APPROVED`.
- Code verdict: `APPROVED`.

## Exact reviewed identities

Working-file SHA-256:

```text
cdcf6a0884532b9b411c3090c470a7b23ebb58a516db88dfec0974434d87920c  tools/architecture/check.py
c89219bbd20e99c321b830530e26e9922a818493781e452c09dec2d8aa96f1ee  tools/architecture/tests/test_php_select_tokens.py
```

SHA-256 of each exact `git diff --binary c02f1a23121058fb0046e4b8fec859c5754ecb6d -- <path>` byte stream:

```text
7f0252731ac1e64877a8f911b8a523365ea42882676ba5de3ccf346da85253e3  tools/architecture/check.py.diff
d58dd064228fd184d01de46569c23a1a84de953e58d8953d8a81f49a7b35c2ab  tools/architecture/tests/test_php_select_tokens.py.diff
```

Combined exact two-file diff SHA-256:

```text
7e2038783ce6cc4cb2a3a749a0749fe28efd2abf246844649aa050a92bc561ee
```

The architecture baseline remained byte-identical:

```text
9a67b19242bc1609d00c8a9e923246096b9730a89c988af6390ceb6541b5a6c8  tools/architecture/baseline.json
```

## Findings

No blocking findings.

The correction addresses two concrete lexical false positives without accepting source obfuscation. Outside quoted PHP strings, `PHP_SELECT_IDENTIFIER` removes only the exact variable token `$select`, case-insensitively and with a trailing word boundary. It does not remove `$selector`, SQL text assigned to that variable, or any quoted SQL verb. The existing enum-case and static-constant exemptions remain unchanged.

Inside quoted PHP strings, the sanitizer replaces only complete public SHLZ select-component atoms: `shlz-field--select`, `shlz-select` plus its documented hyphen/underscore suffixes, and `data-shlz-select` plus suffixes. Both sides require an identifier boundary, so arbitrary words merely containing those atoms are not altered. The replacement happens only in the detector view used for ownership classification; source-normalized fingerprints continue to use the original line and therefore retain exact existing fingerprint behavior.

This exemption cannot hide an SQL statement containing SHLZ markup. `SELECT`, `UPDATE`, `INSERT INTO`, and `DELETE FROM` remain in the sanitized quoted literal and still match the SQL rule. The reviewed tests prove each verb on the same PHP line as the exempt markup, a bare `SELECT`, and SQL containing `widget='shlz-select-root'`. Existing tests also retain split/concatenated SELECT detection, same-line enum plus SQL detection, enum-like text inside SQL, hostile rapid-pilot DDL/DML detection, and quoted array-key behavior.

The test additions reproduce the intended false positives through the public checker CLI rather than calling the sanitizer directly. They assert clean results for `$select` rendering and two actual SHLZ markup forms, then assert nonzero checker results and an `sql_ownership: new violation` for every protected SQL form. Expected failures are therefore independent of the replacement string chosen by the implementation.

No baseline entry, allowlist, SQL-owner rule, DDL rule, rapid-pilot rule, or fingerprint normalizer was weakened.

## Independent verification

```text
python3 -m unittest -v tools.architecture.tests.test_php_select_tokens
Ran 10 tests ... OK

python3 -m unittest discover -s tools/architecture/tests -p 'test_*.py' -v
Ran 43 tests ... OK

PATH=/opt/homebrew/bin:$PATH tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

python3 -m py_compile tools/architecture/check.py tools/architecture/tests/test_php_select_tokens.py
PASS

git diff --check -- tools/architecture/check.py tools/architecture/tests/test_php_select_tokens.py
PASS
```

The review performed no implementation edits, baseline edits, stand/data mutation, deployment, remote action, or Bitrix action. The verdict is bounded to the exact detector and test diffs above; it does not assert full `VERIFY_OK` or production readiness.
