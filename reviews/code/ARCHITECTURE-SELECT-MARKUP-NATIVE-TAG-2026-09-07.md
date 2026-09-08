# Architecture native select-tag detector supplement — independent review

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the detector or tests.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `c02f1a23121058fb0046e4b8fec859c5754ecb6d`.
- Scope: the current `tools/architecture/check.py` and `tools/architecture/tests/test_php_select_tokens.py` diffs, with emphasis on the native HTML tag supplement to the earlier approved SELECT-markup correction.
- Test verdict: `APPROVED`.
- Code verdict: `APPROVED`.

## Exact reviewed identities

```text
1984a86eb6527bba5e7776086869f4c3238362dbbe9da3afa365bf40bfdf9d30  tools/architecture/check.py
16f99a3505ca5597b1b36d7a409b7bd20d5c8998e1e927e85131be746c979a9b  tools/architecture/tests/test_php_select_tokens.py
2ce15d15e1c5a0f170f3236b4c31197cb6e1341fabf5a474058ac3c4d6533cdb  check.py diff
b2ae4495e9f04527e7d1c63f85c325f8087154faac9234f44afb46c9405a953a  test diff
519326fa521fe2c84760a0d765c042392ed86c087b784069227b4fe1dc887439  combined diff
9a67b19242bc1609d00c8a9e923246096b9730a89c988af6390ceb6541b5a6c8  unchanged architecture baseline
```

## Findings

No blocking findings.

The supplemental regex recognizes only an opening or closing native `select` tag
inside an already identified quoted PHP string: `<select` or `</select`, followed
by whitespace, `/`, or `>`. It does not erase the word `SELECT`, prefixed/suffixed
identifiers, or SQL outside quoted strings. Replacement occurs only in the
detection view; source-normalized fingerprints retain original bytes.

The added positive fixture uses a real `<select><option>...</option></select>`
fragment through the public checker CLI. The hostile fixture places the same tags
inside an actual quoted SQL statement and still requires `sql_ownership: new
violation`. Existing same-line SELECT/UPDATE/INSERT/DELETE, SQL fragment,
rapid-pilot DDL/DML, array-key, enum, namespace and fingerprint tests remain green.
The correction therefore permits ordinary native form markup without masking SQL.

## Independent verification

```text
python3 -m unittest -v tools.architecture.tests.test_php_select_tokens
Ran 11 tests ... OK

python3 -m unittest discover -s tools/architecture/tests -p 'test_*.py' -v
Ran 44 tests ... OK

PATH=/opt/homebrew/bin:$PATH tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

git diff --check -- tools/architecture/check.py tools/architecture/tests/test_php_select_tokens.py
PASS
```

No baseline, implementation, test, stand/data, deployment, remote, or Bitrix
mutation was performed by the reviewer. This supplement does not expand the prior
approval beyond the exact hashes above or claim full `VERIFY_OK`.
