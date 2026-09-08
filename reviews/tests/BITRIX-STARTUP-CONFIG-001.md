# Test review: BITRIX-STARTUP-CONFIG-001

- Reviewer: `/root/review_startup` (independent agent)
- Test author: `/root/startup_tests`
- Reviewed baseline commit: `1dd62c2e24d3abb35f68573c18cc500f17d7d387`
- Specification: `specs/BITRIX-STARTUP-CONFIG-001.md` v0.1 and OpenSpec change `explicit-bitrix-startup-configuration`
- Public seams: `make up`; `php bin/fmonitor2-prepare-bitrix-config.php INPUT_ENV_PATH OUTPUT_JSON_PATH`
- Verdict: `APPROVED`

## Reviewed artifact hashes

SHA-256:

```text
3cdc972579361cec82f21f5e75cd344bb66e04ae58c06f08250db1a0888b405b  specs/BITRIX-STARTUP-CONFIG-001.md
a9ab3d1ab39dd51fe5d923c7551008258d358aea6ef0ad43b1b2647cf2649b7c  openspec/changes/explicit-bitrix-startup-configuration/proposal.md
07b4f657d107d9031ab75cc6d434807fee57551f37dff15cc9b9595e03fbe789  openspec/changes/explicit-bitrix-startup-configuration/design.md
4a34f445821afbeef1b4d293affbca285400d96b1c5be7c05f4953b705037752  openspec/changes/explicit-bitrix-startup-configuration/tasks.md
38eedccbe219c2322d01d923e60a5f416220d2972921a5553e9c6eb139b5ff89  openspec/changes/explicit-bitrix-startup-configuration/specs/deployment/explicit-bitrix-startup/spec.md
83bfdcc10da201559f3c213a1115222f7c6cd0f529e4f2c19f726951d2aa4103  tests/Deployment/bitrix_startup_config_001_test.php
8fe18de8d0349c88fb7301d193718823554415e788a8f4abcbb75baf86bfddf8  rapid-pilot/verify-deployment-contract.php
a217f1db11d558225626dbe85ab10f11fca9738539ad44807e281f48b4021927  tools/verification/suites.tsv
55d82138588669035064bff1e9c2f7d5d9d14998cc35255c96ecc46991756424  tools/verification/categories.json
0fd773323e0ab820142a9c34792dc61ca03f1f36a6039d0c3a38ebb46203373e  docs/operations/bitrix-startup-config-001-red-2026-09-08.md
```

## Findings

The normative specification resolves the behavior-affecting choices for the two public seams. It states the accepted literal `.env` forms, canonical output, reuse of `WorkerConfiguration`, atomic/private file requirements, exact startup inclusion, failure ordering, safe diagnostics, and the boundaries excluding network, database, scheduler, and synchronization changes. The OpenSpec proposal, design, delta specification, and tasks are coherent with it and with the owner's later decision that standard `make up` includes Bitrix and `up-bitrix` is removed.

The test exercises accepted quoting forms and independently verifies the resulting runtime values through the existing `WorkerConfiguration` public parser. Rejected inputs include absent input, missing and duplicate keys, malformed syntax, invalid URL, malformed/empty/non-positive/duplicate department IDs, and a permissive pre-existing result directory. Expected values come from the worked example rather than planned implementation details.

The deployment harness runs `make up` from an isolated copied Makefile with fake `docker` and forbidden host `php`. It checks build/preparation/start ordering, absence of legacy paths, and marker-secret absence from output and Docker arguments. A distinct fake preparation failure returns nonzero and proves that `make up` does not invoke `compose up`, does not alter the stale private file, and does not disclose either marker. The fixtures use synthetic values and do not contact Bitrix, Docker, the installed stand, production data, or volumes.

Initial review requested direct coverage of preparation failure ordering, an actually absent input file, and Make/Docker marker secrecy. The test author added those cases before this approval. No blocking findings remain.

Suite registration is present in `tools/verification/suites.tsv` and `tools/verification/categories.json`; the focused deployment test is classified for the unit runner and governance category, while the amended deployment contract remains registered in its existing verification path.

## Independent RED evidence

Run on 2026-09-08 in `/Users/antropophag/code/fmonitor-2-issue42` before production implementation:

```text
php -l tests/Deployment/bitrix_startup_config_001_test.php
No syntax errors detected in tests/Deployment/bitrix_startup_config_001_test.php
exit 0

php tests/Deployment/bitrix_startup_config_001_test.php
TestFailure: valid env form 0 succeeds
Expected: 0
Actual: 1
exit 255
```

This is the intended missing-behavior failure: `bin/fmonitor2-prepare-bitrix-config.php` does not exist on the reviewed baseline.

The existing deployment seam independently reports its missing wiring:

```text
php rapid-pilot/verify-deployment-contract.php
RuntimeException: make up does not prepare explicit Bitrix configuration
exit 255
```

Supporting checks:

```text
openspec validate explicit-bitrix-startup-configuration --strict
Change 'explicit-bitrix-startup-configuration' is valid
exit 0

git diff --check
exit 0
```

## Required changes

None.
