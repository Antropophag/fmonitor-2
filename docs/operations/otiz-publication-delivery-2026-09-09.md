# Первый архитектурный срез ОТиЗ — 2026-09-09

Порядок владельца: #32 → первый законченный срез #24 → #33.
ADR0002 и change `atomic-otiz-snapshot-publication` реализованы в отдельном
кандидате; пользовательский стенд и его данные не переключались.

## Изменение

`app/Otiz/SnapshotPublication` владеет подготовкой/публикацией и отдельным
принятием. Транзакция публикует header, objects, allocations, issues, totals,
receipt и audit вместе; stable operation identity обеспечивает replay.
Принятие проверяет фактические rows по manifest, их counts и blockers.
Legacy draft без подтверждения публикации остаётся читаемым, но требует нового
расчёта. Accepted история сохранена. A02 (межсрезовые выплаты) и A03 (округление)
остаются отдельными рисками; финансовая/production готовность не заявляется.

Из `rapid-pilot/Otiz.php` удалены calculate, closedBefore, closureEvidence, issue
и DML/транзакция accept. Чистые формулы и native read inputs перенесены без изменения;
старые имена — тонкие aliases. Shared access policy перемещена в IdentityAccess,
с прежним PilotHttp alias: второй политики полномочий нет.

Канонические миграции20/21 создают receipt и существующие OTIZ/evidence families.
Readiness проверяет InnoDB, columns/types/nullability и ordered unique keys;
runtime ledger/projection ensureSchema выполняет только readiness.
Checker отдельно контролирует DDL в трёх реально вызываемых legacy adapters.

## Проверки

A01 RED на прежнем source b9b77e0: реальный login/CSRF/POST calculate с
контролируемой ошибкой входов оставлял draft, который POST accept переводил в
accepted. Assertion `incomplete calculation MUST NOT be accepted`:
expected draft, actual accepted. Это воспроизведение на disposable DB, не на стенде.

После исправления:

- `php tests/Otiz/snapshot_publication_001_test.php` — GREEN: независимые суммы,
  rollback на трёх точках, отсутствие orphan rows, consistent input cut,
  concurrent replay/accept, manifest golden и same-count corruption, RBAC.
- `php tests/Otiz/snapshot_publication_http_001_test.php` — A01 HTTP GREEN.
- `php tests/Otiz/runtime_schema_001_test.php` — сохранность истории,
  repeat/conflict, нетранзакционный engine и неверный unique key отвергаются.
- `verify-otiz-workflow.php` — все19 прежних assertions; native input и premium
  formula verifiers, migrated evidence decision ledger — PASS.
- Canonical runner, selection registration и inspection-item MariaDB проверки — PASS.
  Expected frontier обновлён19→21, individual migration19 assertions сохранены.
- Unit:77tests,0failures; architecture7rules PASS; visual/focus contracts PASS;
  `git diff --check` и OpenSpec strict validation PASS.

На исходном checkout checks выполнялись до переноса; интеграционный checkout
`../fmonitor-2-otiz-current-20260909` основан на main ad652aec, source31d7c09c.
Там повторно подтверждены app/HTTP, unit77 и architecture; единственный конфликт
cherry-pick в suites.tsv разрешён сохранением всех main и OTIZ entries.
Полный локальный make test перед CI не дублируется. Полный Actions для окончательного
кандидата ещё pending на момент фиксации этого отчёта; фактический результат —
в Actions на exact head. Deferred/pending gate не означает APPROVED.

## Headless browser evidence

Команда: `php tests/Support/OtizBrowserFixture.php`.
Playwright берётся из `../shlz-ui/node_modules/playwright` либо
`FMONITOR_TEST_PLAYWRIGHT_MODULE`. Это отдельный focused local smoke, не молчаливое
обещание Playwright внутри CI. Три основные OTIZ tests входят в canonical integration.

Один непустой synthetic native объект, только DML DB account на disposable database.
Реальные клики: prepare → потеря уже обработанного ответа → reload → restored
operationId/date → replay → accept → XLSX. Один receipt и одно draft_calculated;
accepted snapshot; пул60775000коп; XLSX7380bytes. Console/page/unexpected network
errors отсутствуют. DB и точные временные accounts удалены, оставшихся accounts0.

Derived screenshots/result/XLSX сохранены вне репозитория:
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fmonitor-otiz-browser-5a1f51c63364/`.
Root просмотрел accepted.png: действующая оболочка сохранена. Browser fixture
воспроизводима; абсолютный путь evidence относится к текущей машине.

## Следующее действие

Независимый Gate5 APPROVED: `reviews/code/OTIZ-SNAPSHOT-PUBLICATION-001.md`.
Остаётся один полный CI на кандидате; после его принятия следующий
архитектурный этап #33. Политика rounding/выплат, прочие Otiz actions и весь UI
не переписаны. Production deployment этого среза не выполнялся.

## Первый CI и исправление инфраструктуры

PR61, head167cbaa9, run34279763684: unit и E2E PASS; fast/governance обнаружили
недостающий tests/Otiz в synthetic mini-repos самого test runner. Исправлены только
создатели этих fixtures, добавлен unknown-Otiz fail-closed сценарий и три точных
новых entries в проверку membership (исторические digest сохранены).
Обе focused Python suites:15+15tests PASS. Независимый supplemental review APPROVED
в том же Gate5 record. Результат integration и финального повторного CI фиксируется
по фактическим Actions; первый failed run не объявляется VERIFY_OK.


Первый integration завершился FAIL в6 прежних fixture checks: два пропущенных
terminal21 ожидания/инвентаря, повторное создание уже канонических OTIZ tables,
старый demo provisioning/ready marker19, hocc no-op19 и toy directory inventory.
Все три новых OTIZ теста в GitHub PASS. Исправления прошли focused GREEN и
независимый supplemental Gate5 APPROVED. Для ready marker дополнительно сохранён
RED expected21/actual19; после исправления весь demo launch/walkthrough/persistence/
reset/cleanup тест PASS. Повторный полный CI проверяет собранный исправленный head.


## Предотвращение повторного пропуска HTTP-соглашения

Во втором CI run34281317284 fast, unit, E2E и оба integration shards PASS.
Единственный оставшийся governance FAIL — неквалифицированные dirname/class_alias
в новом совместимом `app/PilotHttp/AccessPolicy.php`. Эта ошибка присутствовала
и в первом логе, но была пропущена при чтении усечённого хвоста. Два вызова
исправлены без изменения политики доступа; полный local governance9tests PASS.

По вопросу владельца о повторении ошибки устранён пробел локальной проверки:
`make architecture-check` теперь сначала вызывает существующий HTTP token oracle,
затем структурный checker. Отдельной реализации правила нет. Отрицательная проба
с временным unqualified strlen остановила make; файл удалён в finally. Положительный
прогон: HTTP qualification PASS и architecture7rules PASS. После изменения Make
повторён governance:9tests,0failures. AGENTS закрепляет path-trigger для HTTP-файлов
и полный перечень CI failures до чтения ограниченных фрагментов. Независимый review
подтвердил это изменение. Следующий CI head включает защиту и исправленные вызовы.


## Финальная сверка поставки — 2026-09-09

Исторические pending-статусы выше описывают моменты до завершения CI.
[PR61](https://github.com/Antropophag/fmonitor-2/pull/61) MERGED, merge commit
`eb88ed2f7edb4ad609662e9268e1d8defcb18ceb`. Проверенный exact head
`4fcebfdf45e3f986681f33c8474b6070436a7e28`,
[Actions34282154511](https://github.com/Antropophag/fmonitor-2/actions/runs/34282154511)
SUCCESS; все восемь jobs успешны, полный VERIFY_OK зафиксирован в PR.
Статус и head повторно прочитаны через GitHub CLI при этой сверке.

Done первого среза подтверждён: A01 RED→GREEN, единственный application owner
`SnapshotPublication` для build/publish/accept, удаление прежних calculate/helpers
и SQL acceptance, миграции20/21 и readiness-only legacy adapters, сохранение
UI/ролей/истории, Gate3v3 и Gate5 с supplemental reviews.
Основные критерии первого сценария #24 выполнены. Полный перенос всех
rapid-pilot routes и новая финансовая формула не являются результатом PR61.

Сохраняющийся архитектурный объём: closures, payments/complete и reverse в
`rapid-pilot/Otiz.php`, session composition через `RapidPilotLocalAuth` в
`ProductionPilotHttpEntrypointFactory`, прочие временные UI/read adapters ADR0002.
Закрытие первого OpenSpec change не объявляет этот объём реализованным.
Формула/справки #66, A02/A03 и эксплуатационные доказательства не получают
автоматического approval от данного документационного завершения.
