# Продолжение стабилизации после сна — 8 сентября 2026

Работа начата по `continue-after-sleep-2026-09-07.txt` на HEAD `8c0cc63`.
Исходный WIP `bin/fmonitor2-pilot-demo.php` совпал с handoff SHA256
`b6c91db186aef321359464d97b4b7439df8fc5a2fcdd41a8fa399f0baf6596a1`;
он продолжен, не сброшен. Чужой architecture audit и `.DS_Store` не изменялись.
Persistent goal в новой сессии отсутствовала (`get_goal: null`), поэтому по
инструкции владельца восстановлена ACTIVE-цель без token budget.

Установленный owner stand остаётся `a6d3a7f`, healthy; реальные данные1450/966,
образ и volumes не изменялись. Ни CI publication, ни PR10 merge, ни внешние
Bitrix/import действия не выполнялись. Полный новый verify ещё не запускался. Migration18 исправление и независимые
reviews сохранены в commit `58e2595`.

## Demo bootstrap: подтверждённые причины и работа

Private evidence: `~/.local/state/fmonitor2/manual-pilot-20260907/runtime/`.
Логи `bootstrap-body-001.log`… — последовательные отдельные файлы, не перезапись
одного терминального лога. Tight body diagnostics исключают обязательный child
loop только в private копии; committed test не имеет skip flags. Whole bootstrap
с обязательными children остаётся необходимым перед final approval.

- Исходный launcher падал до HTTP: отсутствовало разрешение имени
  `CanonicalMigrationApplication`. Короткий provision diagnostic это воспроизвёл.
- Тест создавал случайные БД через admin, затем подключал обычного пользователя
  без grants к этим БД. Harness теперь использует creator credential только
  для своих disposable БД; права существующих пользователей не расширяются.
- Expanded workforce schema требует явного column list и текущей synthetic
  provenance. Local actor/roles/permissions и object detail нужны current routes.
- Fresh selection factory требует единый process/legacy prefix. Новые fictional
  поколения используют52 таблицы в одном namespace; прежние split generations
  не переписываются. Их автоматическая миграция/удаление не заявляется.
- `app/demo/PilotDemoDatabase.php` владеет созданием только пустых synthetic
  generations и cleanup с двумя независимыми nonce anchors. Runtime HTTP не
  использует этот модуль. Canonical migrations и case importer переиспользуются.
- `app/demo/router.php` передаёт configured actor и случайный per-server CSRF
  через доверенный контекст в неизменённый native router. Для POST сохранена
  точная demo Host/Origin/Sec-Fetch граница. Подмена identity headers не действует.
- Нативное хранилище сессий использовало отсутствующий здесь Docker path
  `/home/fmonitor`; demo теперь задаёт собственный root внутри поколения.
- Проверка работающего процесса поддерживает macOS через argv-form `ps`,
  сохраняя Linux `/proc`. Running/reset/cleanup assertions проходят.
- Native card показывает ссылку `/prepare`, current coordinator перенаправляет
  её303 на `/selection`. Проверяется реальный переход, без изменения native UI.
- Current selection POST, exact stored members/engineer и restart persistence
  прошли private body. Host/Origin, spoofed actor, canonical catalogue, status
  missing-table sensitivity, occupied reset, foreign marker и prefix collision
  также достигнуты и проходят. После исправления migration10 private body целиком PASS (лог017, SHA256
  `026b91a851303ff21e44b51a915c6d8c56bb28c9e3c144d7a50ab7d4bcab73b6`).

Spec/test reconciliation имеет явную retained/superseded mapping в change
`reconcile-protected-pilot-e2e-current-flow`. Полный original/checklist/completion
journey остаётся обязательным protected child. Старый manual-registration
walkthrough не возвращается в продукт. Независимый reviewer проверяет новые bytes.

## Migration18 collation

Fresh canonical migrations1–17 в `utf8mb4_bin` проходили, migration18 после DDL
не подтверждала readiness. Fingerprint ошибочно заменял literal column collation
`utf8mb4_bin` на `@collation`, когда она совпадала с default collation БД.
Теперь placeholder нормализуется только для колонок с таким expected contract.
Новый public-seam regression (general_ci + bin, apply/readiness/no-op) и соседний
unknown-employment test GREEN; независимые test/code reviews APPROVED:
`MIGRATION-18-COLLATION-REGRESSION-INDEPENDENT-2026-09-07.md` в обоих review каталогах.
Unmodified binary-collation demo provision затем PASS1.6s.

## Migration10 reset

`demo-reset-per-migration.log` доказывает: первое поколение canonical19 проходит,
второе в той же disposable БД падает на migration10 с MariaDB errno121:
фиксированные имена FK completion corrections конфликтуют между namespaces.
Это не timeout и не повод ослабить reset/preservation assertions.
Исправление создаёт prefix-scoped имена для новых namespaces и сохраняет
совместимость существующих populated schemas без изменения их данных. Public-seam
regression, полный existing completion schema test и исходный двухпоколенный
provision diagnostic GREEN; independent test/code reviews APPROVED. Исправление сохранено в commit `f3e6239`.

Whole original bootstrap001 упёрся в60s deadline migration child. Отдельный
тот же child PASS90.67s. Test-only MariaDB (tmpfs, запущенная3дня назад) содержала
38 disposable schemas/2332tables и занимала5.485GiB; long DB locks не наблюдались.
Пересоздан только project `fmonitor2-test` через `make test-env-down/up`, затем
canonical `make migrate` PASS19. Owner project/volumes не затрагивались.

Whole original bootstrap002 с неизменёнными deadlines и всеми mandatory children
PASS exit0 за144.03s. Лог `runtime/bootstrap-whole-002.log`, SHA256
`9f7ed5164ea7d95e7280d7f4e1f781acf03fdb8e98758213b9e088d16517feaf`.
`tools/architecture/check`: `ARCHITECTURE CHECK PASSED (7 rules)`;
`git diff --check`: PASS. Final independent bootstrap test/code review APPROVED:
`reviews/{tests,code}/PILOT-DEMO-BOOTSTRAP-CURRENT-INDEPENDENT-2026-09-08.md`.
Этот результат не подменяет ещё не запущенный full verify.

## Следующие обязательные результаты

1. Зафиксировать reviewed candidate, перевести clean prepared verify checkout
   на его SHA с pinned vendor и выполнить полный `make verify`.
2. Только после literal VERIFY_OK — exact image, backup/migration19/deployment,
   restart/golden доказательства и дальнейшая CI/Quality Graph интеграция в рамках
   исходных разрешений. Глобальная цель остаётся ACTIVE.

## Exact-SHA full verify — запущен

Reviewed implementation commit `97519bb`, последующий documentation/task-status
commit — candidate `ccc8dfbfd24765f509ae7e95113fb7ea8f0ee6b4`.
Clean checkout `/Users/antropophag/code/fmonitor-2-verify-stabilization` переведён
с795ac3e на этот SHA; tracked/untracked status перед запуском пустой. Pinned
TCPDF6.11.4 сохранён. Запущен ровно `make verify` с `/opt/homebrew/bin` в PATH.

Private log: `runtime/verify-ccc8dfb-001.log`; receipt:
`runtime/verify-ccc8dfb-001.json` (source, checkout, command, start time; final
exit/time добавляются runner после завершения). Первые stages test-db-reset,
migrate и architecture-check PASS. Итог ещё не получен; не запускать параллельный
DB/full verify поверх активного прогона. Наличие этого checkpoint не завершает цель
и не даёт права объявить VERIFY_OK до literal результата.

## Full verify ccc8dfb — завершён, один setup failure

`make verify` завершился exit2 за1169.41s; log SHA256
`f89fe3aadd0cc7fd4ded53b576abc13a957add538d1d40cb16bc7bbc01a166cb`.
PASS: test-db-reset, migrate, architecture-check, lint, unit-test, db-test,
e2e-test, diff-check. Единственный FAIL — characterization-test:
`rapid-pilot/verify-checklist-current-crew.php` не загрузил
`InspectionPhotoContentIndexSchemaMigration` перед `ChecklistSync::ensureSchema()`.
Это setup failure отдельного verifier, не новая ошибка domain behavior.

Исправление — один require canonical `app/autoload.php` в verifier. Историческая
v8 fixture и assertions current crew202 / historical item installer101 не менялись.
Focused run PASS0.2s; log `runtime/checklist-current-crew-autoload-green-001.log`,
SHA256 `883381616a1484cecd6828431a22231f02b3682c2910243ffc2090916d782ff7`.
Independent supplemental test/code reviews APPROVED:
`reviews/{tests,code}/CHECKLIST-CURRENT-CREW-AUTOLOAD-SUPPLEMENTAL-2026-09-08.md`.
Далее новый commit с этим fix и новый полный exact-SHA verify. CCC не объявляется
VERIFY_OK; установленный a6d3a7f и данные владельца остаются прежними.

## Следующий exact-SHA прогон — 4990cf1

Supplemental fix/reviews и результаты первого full сохранены в
`4990cf1afd90813c60f155297f427eb822ae78e9`. Verification checkout чистый и переведён
на этот SHA. Пересоздан только disposable `fmonitor2-test` tmpfs; новый
`make verify` запущен через private `runtime/run-exact-verify.py`.
Текущие log/receipt: `runtime/verify-4990cf1-001.log` и `.json`. Receipt после
завершения содержит exitCode, seconds и literalVerifyOk. Во время записи этого
checkpoint прошли подготовка/migrate/architecture/lint/unit; DB stage ещё выполняется.
Не запускать второй DB/full прогон поверх него.

Private preparation после VERIFY: `runtime/live-deployment-readonly.cjs` выполняет
read-only owner checks и сохраняет auth storageState для проверки после restart;
`runtime/image-full-golden-20260908/` готовит isolated exact-image golden. Эти
новые helpers пока НЕ выполнялись; перед использованием прочитать их и проверить
exact target/source/image, учитывать актуальную финальную версию. Рабочий стенд
по-прежнему a6d3a7f, deployment до первого literal VERIFY_OK не выполнялся.

## Первый literal VERIFY_OK и exact image

`4990cf1afd90813c60f155297f427eb822ae78e9`: полный `make verify` exit0 за1182.39s,
все9 stages PASS, terminal literal `VERIFY_OK`. Начало2026-09-07T21:49:03Z,
окончание22:08:46Z (8сентября01:08МСК). Log SHA256:
`b6c7c983c20d85e3552450fc7a4a31f2c735bdf06aacf31586819ed0b52895db`.
Verification checkout после прогона по-прежнему чистый.

Из exact git archive собран `fmonitor2-manual:verify-4990cf1`, image
`sha256:2bc0b0803182e0ac522fdfb6a90f60d8d4ed5ac0a90cdbdaf601e77cd78977ac`.
OCI revision совпадает с SHA; Linux/arm64. Все736 runtime files в app/bin/public/
rapid-pilot побайтно совпали с архивом. Archive SHA256
`91d37d45043e45d4ba2ea54077b1bd7802eecbb0e1ae58eaa32eb16c2ca4e300`;
image runtime manifest SHA256
`1da185cf3dd1f4cd864a49da22490bc367ac3ea972a802ff56a6ddd1fb5b23b6`.
Build/manifest evidence: `runtime/build-4990cf1-verified/`.

До backup/deployment установленный source всё ещё a6d3a7f. Подготовлен и прошёл
core-only headless read baseline на1450/966, session navigation, installers и
construction control:14 protected GET routes, original966 download, desktop/mobile
screenshots, zero errors/overflow/business writes. Evidence:
`runtime/live-core-before-4990cf1/`; storageState сохранён0600 для reuse после
обновления/restart. Это явно `scope=core`, не глобальное отсутствие ошибок UI.

### Выявленный неблокирующий OTIZ visual defect

Full read-only helper на старомa6 обнаружил CSP `style-src-attr` violations на
`/pilot/otiz`: `rapid-pilot/Otiz.php` выводит inline `width:0.00%` для fund track.
Hash этого литерала совпал с browser CSP report;368 violations возникают ДО
скриншота, сам screenshot не добавляет их. Числа/страницы доступны, core routes
ошибок не имеют; новых бизнес-изменений тест не делал. Это pre-existing visual
limitation, отложенная по owner stabilization scope; CSP не ослаблялась, full
helper FAIL не скрывается. Diagnosis result SHA256
`d774ecad6fa345d6619cbb5c4703fb7d3a3f74513e01882ef26ef7a53040a979`.

### Exact-image golden preparation

Первый image golden001 выявил только test mount setup: host bind directory в
Docker виден какuid0 при processuid501, native session owner правильно вернул
ROOT_INVALID. Изолированная fixture переведена в собственный labelled Docker
volume сuid10001, как рабочий runtime; live volumes не менялись.
Image golden002 выполнил41items/7photos/85%/distinct opener/100%, но дополнительный
network-origin probe ошибочно счёл local `data:` resources с origin `null`
внешним сервером. Probe исправлен: HTTP(S) только exact candidate origin,
data/blob/about учитываются отдельно, неизвестные схемы не принимаются. Original
functional assertions не менялись; final image golden003 выполняется отдельно.

## Установленный runtime — новая актуальная точка

**Installed source: `4990cf1afd90813c60f155297f427eb822ae78e9`.**
Image `fmonitor2-manual:verify-4990cf1`, immutable ID
`sha256:2bc0b0803182e0ac522fdfb6a90f60d8d4ed5ac0a90cdbdaf601e77cd78977ac`.
URL остаётся http://127.0.0.1:8092/pilot/objects, существующий owner вход.
Старыйa6d3a7f сохранён только как rollback image/история; не откатывать к нему
по старым handoff.

Fresh populated backup:
`runtime/backup-before-4990cf1-20260907T223945Z/`.
- SQL1,466,272bytes, SHA256 `6018157778dece8d0342203148e234bfe9363e81893f6efa717ec87eae30ddf0`.
- State718,469bytes, SHA256 `861d046dc996cb623c9a70e88edcb1c6528c58785d3b124ce8ddd542ddf05798`.
- Manifest SHA256 `fa3760d7a818f6ad185b031a88b532093291a4ccc3088c3c9bb69b85ce206245`.

Остановлен/пересоздан только pilot. MariaDB container и постоянные volumes
`fmonitor2-manual_mariadb-data` / `fmonitor2-manual_pilot-state` сохранены.
Startup сообщил exact `schemaVersion:19, appliedVersions:[19]`, затем healthy.
Бизнес-data dump до/после побайтно одинаковый: SHA256
`cc540205c588016fdaf53d7d13751c2720c23d504e32f28fb338539881e02d07`.
Исключена только transient `fm2_pilot_auth_attempts`, которая по существующей
политике очищается при успешном login. Все10 файлов в artifact storage совпали
по именам/размерам/SHA256. После второго restart business dump также exact.

Core headless proof на15 GET маршрутах после update PASS, включая cookie,
сохранённую ЕЩЁ НАa6d3a7f; повторный login не выполнялся. После отдельного restart
того же image/container — снова PASS с той же auth cookie,15GET, zero business
mutations/HTTP/browser errors/mobile overflow. Startup второго restart —
`schemaVersion:19, appliedVersions:[]`. Desktop/mobile screenshots сохранены;
mobile1450 дополнительно просмотрен визуально. Реальный1450 уже находится в
документарном закрытии85%; это сохранённое состояние, не действие теста.

Evidence hashes:
- post-update core result: `6f21110fd006d2793be018c5aa1e7a6d0470a11afca2b04e1965523fec1df7f2`;
- post-restart core result: `a37fa68fba913ae415d57089f87cf9aed3a7d5a864cfc0a857eb5eb0f7e1eafe`;
- restart proof: `5aac0dc3ded0bf5734067a2508448ccbd84c0869f642120641e71ac13267ff30`;
- data/artifact preservation: `ff3ba393fe8ce35c104aa9a80b98ce75dc21cb27610f718079d5c4d16bd242e9`.

### Exact-image golden: PASS

Final private image golden body004 PASS27.83s:41items,7photos,85%, distinct opener,
100%, errors0. HTTP traffic —407requests, только `http://127.0.0.1:18192`,
привязанный к проверенному container image/revision. Chrome PDF viewer resources
(`chrome:`/`chrome-extension:`) и blob учитываются отдельно как browser-local;
они не считаются вторым application server. Это исправление дополнительного
probe, не ослабление functional assertions. Previous calibration failures001–003
и artifacts сохранены, не превращались задним числом в PASS.

Body является private exact-source derivative с единственным исключением уже
GREEN retained child loop;41/7/85/100/fresh facts и cleanup assertions сохранены.
Все retained contracts отдельно прошли в полном exact-SHA make verify и исходных
whole caller attempts. Никаких committed skip flags нет. Log hash:
`167f6fa5b7e2c3322bb1c9a3c674257d2c2943bbde5d5d07434920f4df8b5157`.
Private body SHA256 `fa748de3d5eb2b70ae2ee8c1f49db214ed9b322eb5b1672d2bad1687d7e9f1b1`;
image browser derivative SHA256
`5560eb55600c35c017c25ad84bca0b0f2c5fc1762bf1bbea1a9dd8e01b77a654`.
Synthetic DB/container/labelled volume очищены только по собственному ownership.
Owner objects1450/966 не изменялись.

Неблокирующее ограничение OTIZ fund-track CSP, описанное выше, остаётся явно
отложенным. Core PASS не означает отсутствие этого известного visual defect.
Дальше — ранее разрешённая CI/Quality Graph интеграция с новыми текущими evidence/
reviews/receipt/parity. Global goal ACTIVE; CI/production readiness ещё не заявлена.
