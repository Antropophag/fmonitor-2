# CANONICAL-INTEGRATION-RUNTIME-001 delivery

## Scope and authorship

- Base: `origin/main` `992e32f103b55a968d2744d0918491257b8090d0`.
- Change: `canonical-integration-pdo-mysql`; branch `codex/canonical-integration-pdo-mysql` in isolated worktree.
- Owner authorization: request of 2026-09-16 to deliver this bounded infrastructure slice through PR-ready and stop before merge.
- Root authored OpenSpec artifacts, normative specification, verification input and executable regression.
- Independent Gate 3 reviewer: `/root/cir001_gate3`, `gpt-5.6-sol / low`; verdict `APPROVED` in `reviews/tests/CANONICAL-INTEGRATION-RUNTIME-001.md`.
- Production implementation author: `/root/cir001_executor`, `gpt-5.6-sol / low`; only `tools/delivery/Dockerfile.focused-checks` changed, adding `pdo_mysql` to existing extension installation.
- Independent final reviewer is recorded separately in `reviews/code/CANONICAL-INTEGRATION-RUNTIME-001.md`.

Application/product code, PHP/MariaDB versions, production images, profile topology, launcher behavior and #20 were not changed. Host port `23306` was not adopted by the new fixture; it selects a free isolated port and keeps the container endpoint `test-db:3306`.

## Planner and Gate 2/3

- Planner lane: `CRITICAL`; required reviews: `gate3`, `final`; required categories: `governance`, `integration`; full `make test` reserved for exact-source GitHub CI.
- Gate 3 package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T154754Z-86e49fa955/package.json`; exact candidate `9cd7531d7787fc3e00a3e28b21966eaeaeacb2cf5869e78f471294cf4535fac0`; executable source `9128392fbdc549fe4a4d1ae05fd10bfbfe5447a24b89e5eed9b6fa6f00122796`.
- Intended RED: record `1789573555014518000-9f27c5f2374d4cc1b0c977cb65fce3eb`. Public integration profile returned exit `42` with `extension=false`, PDO drivers `['sqlite']`, PHP `/usr/local/bin/php`, Yii/autoload under `/workspace`, and `INTEGRATION_PDO_MYSQL_MISSING`.
- Required exact command was separately observed against disposable MariaDB at isolated host port `24317`: exit `255` before application success on the missing-driver source. It was diagnostic console evidence; acceptance RED is the retained exact-source harness record above.
- Gate 3 adjacent evidence: #123-A record `1789573555014505000-dd57bdaa6fd44a4ba0c5aa19b7047e35` GREEN; #167 record `1789573555014525000-2e5e2cabd62f4c919ac6cd56876cb890` GREEN.
- One earlier #123-A run had a single `composer install` exit `100` under parallel Docker load while K/L/M and the other seven cases were GREEN. Complete inventory was inspected; an isolated same-source retry was GREEN. Both records remain retained.

## Gate 4 focused GREEN

- New public-profile driver and DB-backed Yii regression: record `1789573977907378000-62d8127a11994fc9b910f63f2c6be02d`, GREEN. It proves `pdo_mysql`, PDO driver `mysql`, `/usr/local/bin/php`, `/workspace` Yii/vendor origins, matching image/source evidence, and executes `php -d display_errors=0 tests/Yii2/yii2_user_access_001_test.php` to its existing PASS behavior with disposable MariaDB.
- #123-A complete A–O including K/L/M: record `1789574202253981000-09205992ff1c4364a6a03001084b5468`, GREEN.
- #167 profile network/bootstrap: record `1789574304983540000-68a60ad884f04fdda62f399aa2f9d6b7`, GREEN.
- Planner governance obligation: record `1789574304983544000-61596ad0b3434f30962c56b97f48ebe0`, GREEN.
- Planner integration-category obligation: record `1789574304983560000-5abbe3001fd74f5abf5a480325205edc`, GREEN.
- `openspec validate canonical-integration-pdo-mysql --strict`, verification inventory validation and `git diff --check` are GREEN.
- Local full `make test`/`make verify` was not run by owner decision.

## Remaining admission state

Independent final review, exact committed-source GitHub CI and separate PR publication remain required until their evidence is appended. `UNKNOWN` is not approval. Stop after PR-ready; merge/deploy/settings are not authorized.
