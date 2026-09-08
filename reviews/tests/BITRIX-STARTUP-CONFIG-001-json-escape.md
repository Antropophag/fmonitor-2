# Test re-review: BITRIX-STARTUP-CONFIG-001 JSON escape rejection

- Reviewer: `/root/review_startup` (independent agent)
- Test author: `/root/startup_tests`
- Reviewed baseline commit: `1dd62c2e24d3abb35f68573c18cc500f17d7d387`
- Specification: `specs/BITRIX-STARTUP-CONFIG-001.md` v0.1, sections 2 and 5
- Public seam: `php bin/fmonitor2-prepare-bitrix-config.php INPUT_ENV_PATH OUTPUT_JSON_PATH`
- Verdict: `APPROVED`

## Reviewed hashes

```text
e6c2afbb3fca4659ad125da733cd5e4e72c813af3eb343b96d38e3ea1981c7b1  tests/Deployment/bitrix_startup_config_001_test.php
aa67207fb1669f48eb2a96b4c5b77dabbb3c833bacfc0c152a33dd513e074843  tests/Verification/verification_inventory_001_test.py
3a71997dbac333852c993527e4ca295e86212750415e521ae0e668c091ac35de  docs/operations/bitrix-startup-config-001-json-escape-red-2026-09-08.md
```

## Findings

The single added fixture is directly traceable to the normative rule that target `.env` values are literal and escape sequences are unsupported. Its independently derived result is rejection: JSON decoding must not turn escaped digits into an accepted department ID. It uses the approved CLI seam, remains synthetic and deterministic, and fails against the current implementation for that exact reason.

The new test was already registered in the unit suite; adding it to the frozen-baseline inventory test's explicit post-baseline allowance is necessary and does not change the protected baseline digest.

## Independent RED evidence

```text
php -l tests/Deployment/bitrix_startup_config_001_test.php
No syntax errors detected in tests/Deployment/bitrix_startup_config_001_test.php
exit 0

php tests/Deployment/bitrix_startup_config_001_test.php
TestFailure: invalid env 8 fails
Expected: true
Actual: false
exit 255

python3 tests/Verification/verification_inventory_001_test.py
Ran 15 tests
OK
exit 0

git diff --check
exit 0
```

## Required changes

None.
