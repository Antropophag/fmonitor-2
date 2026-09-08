# Bitrix delivery — Gate4 evidence

Production source `23b257c14aadf07fc349b5b120952e59ff5f9fce`,17 новых файлов
app/Workforce, максимум77строк на файл. Spec v0.2 и утверждённые Gate3-v2 тесты
не изменялись при GREEN. Factory/fetch readonly, нет DB/cron/grants/publication.

Команда с PATH Homebrew/Docker:
`php tests/InstallationProcess/bitrix_workforce_delivery_001_test.php`.
Внешний green-v2.log: exit0,12 SETUP_OK/12 PASS/12 CLEANUP_OK. Доказаны реальные
TLS/HTTP, native missing-function runtimes, retry/counters, paused-worker deadline,
copy/privacy, exact1MiB/16MiB и overflow limits. Все task-owned fixture workers,
ports, keys и token очищены штатным harness. Raw evidence только во внешнем
`/Users/antropophag/.local/state/fmonitor2-verification/bitrix-delivery-20260907`.

Первый green-v1 дал11/12 PASS: stalled TLS timeout не повторялся. Отдельный
synthetic native probe (connect-probe.php, без portal/credentials) установил:
Curl8.22 error28, PRETRANSFER_TIME_T около1000000us при APPCONNECT_TIME_T0,
несмотря на незавершённый TLS handshake. Поэтому timeout classification учитывает
TLS readiness через APPCONNECT_TIME в дополнение к pretransfer. Existing native
connect3attempts/request1attempt tests после исправления оба PASS.
Официальные определения timing проверены2026-09-07:
https://curl.se/libcurl/c/CURLINFO_PRETRANSFER_TIME_T.html
https://curl.se/libcurl/c/CURLINFO_APPCONNECT_TIME_T.html
Документы описывают смысл timestamps; измерение ошибочного запроса — отдельное
локальное evidence, не вывод о готовности реального Bitrix.

PHP lint17 и git diff --check PASS. WORKFORCE-CATALOG-001 regression PASS с
explicit synthetic FMONITOR_TEST_DB_ADMIN_PASSWORD из test fixture config;
первый вызов без этого env остановился до setup на Access denied и не считается
behavioral failure. Секреты preview не читались.

Architecture-check первоначально FAIL только lexical false positive PHP array key
`select` в DeliveryCurlAttempt; baseline не изменялся. Отдельный change
recognize-php-select-array-key проходит Gates1–5. Until its GREEN, architecture
и Bitrix integration не объявляются complete. Bitrix Gate5 reviewer отдельно
назначен `/root/bitrix_gate5` (sol low), verdict ещё ожидается.

Full make verify на этой source ещё не выполнялся. Последний известный full run
из handoff не имеет VERIFY_OK; protected E2E остаётся прежним. Реальный Bitrix,
remote mutations/PR10/CI publication/preview changes не выполнялись.

## Architecture prerequisite — завершён

Source `98ded5c64e7b6497be8e871f9e9e796c7c7b5297`: изменение2строк checker,
без правок baseline или production Bitrix. ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001:
Gate1, Gate3-v2 и Gate5 APPROVED независимо `/root/bitrix_gate3` (sol low).
Первый Gate3 CHANGES_REQUESTED сохранён; дополнены literal original-line
fingerprints и newline-before-arrow negative. RED-v2 exit1 только4 intended
positive false positives. GREEN5tests и весь набор40tests PASS; reviewer также
самостоятельно повторил5/40/make architecture-check, все7rules PASS.

Approved test SHA6404c620328f33bb9cd4184372a579b6494d3e181592e57ca6d129c792aaed94.
Baseline SHA1d20f4bd867e42144c43de462c413ab5e59effb3a88060c41f65543fa596f836
не изменился. Raw architecture-key-{red,red-v2,green}.log,
architecture-tests-green.log и architecture-green.log — только external.
External green-manifest.json содержит exact source/artifact/log hashes.

## Bitrix Gate5 — APPROVED

Независимый `/root/bitrix_gate5` (sol low), не автор production/tests, одобрил
source23b257c, неизменный на integrated98ded5c. Review:
reviews/code/BITRIX-WORKFORCE-DELIVERY-001.md. Findings нет. Reviewer самостоятельно
повторил12/12native tests и подтвердил отсутствие оставшихся fixture directories
и PHP/Python workers. Bitrix change6/6tasks и checker change4/4tasks завершены.
Это не full verification/CI/deployment/live Bitrix или launch approval.

## Новый полный exact-SHA run — terminal FAIL

Clean-start `make verify` на `1ef0f90abe1965bda3d53d827d667970f6844ab3`,
HEAD и clean worktree сохранены на всём run. Exit2. Reset/migrate15/architecture7/
lint/unit/characterization/diff PASS. DB и E2E stages FAIL только вследствие
pilot_demo_bootstrap_001_test.php и protected pilot_e2e_flow_001_test.php:
старое ожидание «launch action visible Сформировать распоряжение» и manual
registration flow. Новых regressions не найдено. Bitrix native suite выполнена
каноническим unit runner и прошла; literal VERIFY_OK отсутствует.

External make-verify-1ef0f90.log SHA256
`a85815083c0d76216ecbcc92a7cf0524aa2fcc3b3dc157690f08d6658ef2df45`;
make-verify-1ef0f90-summary.json содержит stages/exact failed files.
Protected E2E SHA256 остаётся
`8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b`.
Review/slice completion не означает full integration или launch readiness.
