# PILOT-BASELINE-CI-001

## Простыми словами

PR41 проверяет установленный пилот на чистом Linux до решения о merge в main.
Переиспользуются уже исправленные и independently reviewed тесты/setup из
PR37/head9f53001. Quality Graph, publisher и их permissions в эту ветку не входят.

## Основание и public seam

Owner в текущей сессии явно разрешил подключить baseline CI и перенести необходимые
Linux fixes в PR41. Сохраняется HARNESS-FULL-AGGREGATION-001 v0.2: публичные команды
make ci-setup, make test-tools, make fresh-test-verify из repository root.
Никаких изменений app/bin/public/rapid-pilot/Dockerfile и рабочих данных.

## Acceptance contract

- pull_request в main и workflow_dispatch запускают один Ubuntu verification job.
- Workflow имеет только contents:read, checkout persist-credentials:false,
  fetch-depth:0 и exact уже проверенные action pins. Нет publisher, deploy,
  approvals, secrets, write permissions или PR-code privileged workflow.
- Setup проверяет PHP8.5 с mysqli/pcntl/dom/mbstring, Node22.22.0, Python3.12.11,
  Docker/Compose; ставит rg; собирает owned test PHP image и pilot test image.
  TCPDF6.11.4 и shlz public build берутся из прежних exact revisions.
- make test-tools поддерживает TEST_TOOL_IMAGE для изолированной проверки и
  org.opencontainers.image.revision=git HEAD; image PHP8.5 содержит mysqli,
  pcntl, util-linux/setpriv. Runtime image и стенд не заменяются.
- run.sh при отсутствии rg возвращает exit1 и exact
  SETUP_FAILURE: required command unavailable: rg до классификации/вывода list.
- make fresh-test-verify исполняет все9существующих этапов, сохраняет failure
  aggregation/teardown. Только полный PASS даёт VERIFY_OK и FRESH_TEST_VERIFY_OK.
- Фактические head/run/attempt/merge-tree и полный stage result сохраняются.
  Результат PR37 не подставляется вместо результата PR41.

## Переиспользование tests/reviews

Восемь test paths переносятся byte-identical из9f53001. Их существующие approvals
остаются источником ожидаемого поведения, а новый независимый review подтверждает
полноту переноса на PR41. Историческое имя quality_graph_ci_setup_001_test.php
сохраняется для exact-byte provenance; этот тест не зависит от Quality Graph.
Workflow запускает его как preflight перед полным harness.

## Границы решения

Это не issue25 optimization: категории, protected assertions, полный состав
verify и дубли E2E не реорганизуются. Полный прогон выполняется в GitHub;
локально достаточно focused/preflight и проверки exact-byte переноса.
Merge PR41/37/10 и branch protection остаются отдельными решениями владельца.
