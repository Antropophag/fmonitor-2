# PR41 baseline CI: focused GREEN

Specification: PILOT-BASELINE-CI-001. Test transfer Gate3 APPROVED at819f6ee.
Восемь test blobs byte-identical к approved/pass source9f53001, runtime files
app/bin/public/rapid-pilot/Dockerfile не менялись относительно PR41base12c96f6.

Baseline workflow сохраняет exact action pins и contents:read; единственный job
готовит Linux зависимости, проверяет setup test и запускает make fresh-test-verify.
uv используется только для pinned Python3.12.11; QG packages/uv sync отсутствуют.
CI setup shell и test Dockerfile побайтно перенесены из9f53001. Make targets
ci-setup/test-tools и rg guard перенесены без подключения QG кunit/architecture.

Focused evidence:
- php tests/Verification/quality_graph_ci_setup_001_test.php: exit0/PASSED;
- bash -n tools/delivery/ci-setup.sh tools/verification/run.sh: exit0;
- tools/architecture/check: exit0, ARCHITECTURE CHECK PASSED (7 rules);
- YAML parse/permissions/triggers/exact pins/terminal command inspection PASS;
- byte comparison reused setup/image and unchanged pilot runtime PASS;
- git diff --check PASS.

Нового полного локального verify нет. Новый полный actual GitHub run PR41 ещё
не выполнен; независимый code review и публикация следуют. Merge не разрешён.

Private setup-green.log SHA-256 `566fb63dbd0c3a689b76d85d78dd325a0e0b24e3892b3e04c01ad26a567336b8`.

Exact implementation file hashes:

- `.github/workflows/repository-verification.yml` `65e4d3be33b0cab0cafd673191493add181323d4653c7fce37bc7c025589f3d1`
- `Makefile` `6d2894b8b533643674bcfb46aafe53d3885552f369905e8e0ebedccc9d5c7cdb`
- `tools/delivery/ci-setup.sh` `b37f7ce00617d55e318c8f53232ffe8002aaf79742de4bb14b30e8e12dfcc4ed`
- `tools/verification/Dockerfile.test` `62d3e360873e22a188337a11d304b0e537c1ee13eb248f3371b42be79b453ea5`
- `tools/verification/run.sh` `7ba4a23e4c494cb41ccbed4bc32b44407bcfeb0fe04e3f241bf3b6522806f89c`
