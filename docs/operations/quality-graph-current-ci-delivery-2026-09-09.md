# Интеграция Quality Graph в текущий CI

## Scope and current status

#25, change integrate-current-quality-graph, executable contract
QUALITY-GRAPH-CURRENT-CI-001. Stock action0.1.7 pinned caf5366a04ca01b230f1df5585d0fbd9693d7bef.
Владелец разрешил штатные check/comment/owned-label writes; собственный publisher
не реализуется. Права записи остаются только у trusted workflow.

Existing canonical category matrix сохраняется. Вместо отдельного повторного
runner — seven native reports из существующих job outcomes, интеграция в один
workflow. HTTP/runtime/данные владельца этим срезом не меняются.

## Gate evidence

Spec/tests RED commit f02639ce; approved Gate3 record commit9185c5f0,
reviews/tests/QUALITY-GRAPH-CURRENT-CI-001.md. Три public seams отсутствовали до RED.
Независимый reviewer проверил full/docs-only, failed/cancelled, отсутствующие и
неоднозначные inputs, identity и workflow boundaries. Preflight tests отдельно
допускают failed/cancelled completed runs с полным набором текущих artifacts.

- Report CLI:9 focused tests PASS.
- Реальный pinned native adapter:4 сценария (full/docs/failure/cancelled),28reports
  приняты с exact identities/provenance; skipped остаётся skipped.
- Preflight: public CLI/localhost HTTP focused suite PASS; только GET,
  current seven, retained old+current, duplicates/future/missing/expired и
  API/event identity/pagination failures покрыты.
- Старый stock probe сохранён исторически в tools/delivery/probes,
  не включён как заведомо failing canonical test. Upstream issue69 содержит
  independently runnable repro также на0.1.10.

## Pending delivery evidence

Workflow/manifest integration, focused regression, Gate5, authoritative full CI,
bootstrap merge и actual representative publisher matrix ещё не завершены.
До их подтверждения #25 не закрывается. Отказы до запуска stock видны как failed
publisher job; они не выдаются за stock completed-failure check.

## Точная точка продолжения после лимита reviewers

Implementation commit1e7ac621 содержит report/preflight/renderer/checker,
штатный completed-only publisher и один renamed CI с семью native reports.
Unit83/0 за35.333с, прежний CI-contract15/0, report9/0 и workflow5/0,
архитектура7 PASS; YAML, renderer и checker PASS. Report28/4modes принят stock
native adapter. Эти GREEN относятся к первичному contract.

Supplemental RED commit1f50b8b2 фиксирует новый jobs admission: сам current
quality-results должен быть completed/success. Старый guard этого не проверяет;
тест сейчас намеренно RED на отсутствии GET jobs. Author добавил также duplicate
на второй странице, root повторил intended RED и сохранил свежий test digest.
Существующий successful source verify не должен скрывать failed report delivery.
После нового Gate3 нужно реализовать jobs pagination/admission, regenerate inline
publisher/manifest, повторить focused и получить финальный Gate5.

Root исправил замечание начатого Gate5 в13dc0769: fast declaration совпадает с
полным реальным fast command list; renderer отвергает semantic drift. Положительный
fixture и удаление uv sync из declaration доказали PASS/REJECTED. Новый digest:
ed77954b8e7e5aa796e0384c741919ba3e3b74813e65e9dd4e99c643422c4b56.

Сервис отдельно задаченных gpt-5.6-sol reviewers вернул usage limit; завершённого
supplemental Gate3 и Gate5 нет. У владельца запрошено разрешение другой доступной
модели, ответа на момент checkpoint нет. Self-review не заменяет independent gate.

Новый PR/CI пока не запускался: известный supplemental RED нельзя выдавать за
готовый кандидат или тратить полный CI ради уже известного отказа. Рабочая ветка
codex/qg-current-25-20260909, checkout fmonitor-2-close-24-25-20260909.
После gates: один полный CI, bootstrap merge, реальная positive/negative publisher
matrix на disposable PR, затем архив/закрытие25. Stand/data и66 не изменялись.

## Кандидат после восстановления reviews

Owner восстановил лимиты и разрешил выбирать модель по сложности. Supplemental
Gate3 APPROVED4db9c6e7 после проверки duplicate на второй странице; implementation
735456630c72362335c3f57f451f8baff6ad01ea проверяет весь jobs endpoint и ровно один
успешный quality-results текущего attempt. Failed/cancelled source categories
остаются допустимым входом при успешной доставке их отчётов.

Независимый Gate5 APPROVED на73545663: reviews/code/QUALITY-GRAPH-CURRENT-CI-001.md.
Finalunit83/0 за40.984с; oldCI15/0, report9/0, workflow5/0, preflight PASS;
HTTPqualification+architecture7 PASS, OpenSpec73/0, diff-check PASS.
Parsed YAML comparison подтверждает сохранение исходных7jobs: fast дополнен
только3командами graph validation, остальные6jobs byte-equivalent по структуре.
Inline publisher Bash из parsed YAML реально исполнен на missing-env fixture:
получен stable preflight failure без IndentationError.

Кандидат готов к authoritative CI/bootstrap PR. После bootstrap merge остаётся
реальный representative publisher proof. Наличие Gate5 не закрывает25 заранее.

## Authoritative CI and bootstrap delivered

PR73 MERGED: f34320ad2f488a02fbe64b4f10863f47f0d97ddf.
Actions34350407125 SUCCESS на exactb6512ba8f370ceb8bfbc388b60785346da647fd0,
все9jobsSUCCESS, literalVERIFY_OK. Первый failure34349144148 сохранён в истории.
Trusted topology установлена на main. DraftPR74 TEST ONLY — DO NOT MERGE
проверяет фактическую publisher matrix.

P0 — настоящий docs-only head452b70e73b26a711e58305062424029f6abeda44,
source34351537845 attempt1 SUCCESS. Publisher34351827368 SUCCESS: stock check,
comment5601910947, триpassed и четыреskipped, ownedfailurelabel отсутствует.
Повтор publisher34351827368 attempt2 SUCCESS сохраняет тот же commentID/body
SHA2562a2eebab6b8e7a9811baf5732758bcc612d5162247174a6851d2781c64ba9ea7.
Stock0.1.7 создаёт дополнительный completed check того же успешного результата;
уникальный CheckRunID между повторениями не обещается. История checkIDs сохранена.

Synthetic F1 head51f7c96c99602e70a5496fc58167a6495d447273:
source34352469527 FAILURE, quality-resultsSUCCESS; publisher34352612973 SUCCESS
публикует именно checkFAILURE, добавляет quality-graph:failed. Для следующих
проверок сохранности чужих меток к draftPR добавлена стандартная documentation.
Synthetic outcomes не выдаются за выполнение production tests.

Матрица продолжается;25 ещё открыта. Private evidence:
/Users/antropophag/.local/state/fmonitor2/qg-phase-b-20260909/.
Генератор и evidence collector проверены независимым reviewer; operational limits
и hashes сохранены в /tmp/fmonitor-qg-phase-b-review.md.

## Historical first CI details

PR73, exacthead4221646f, Actions34349144148 завершён FAILURE. Полный список
терминальных jobs и REGRESSION_FAILURE собран до correction push. Исходный отказ
ровно один: verification_inventory_001_test.py/test_repository_baseline_membership
не учитывал3новых QGunit-теста в added_by_suite. verify отказал вслед за governance.
Fast, unit, обе integration части, E2E и quality-results — SUCCESS.

Семь реально загруженных Result artifacts проверены pinned parser: exacthead/run/
attempt1, governance+verify=failed, остальные5passed; collection не скрыла отказ.
Raw inventory/logs/parsed artifacts: /tmp/fmonitor-qg-ci-inventory.json,
/tmp/fmonitor-qg-ci-first-full.log, /tmp/fmonitor-qg-ci-first-results/.

Локальный полный governance9 воспроизвёл только тот же отказ. Correction028ad2c4
добавляет3literal entries; прежние baselinehashes и assertion exactpresence1
сохранены. Focusedinventory15/0, независимый testreview APPROVED:
reviews/tests/QUALITY-GRAPH-INVENTORY-COMPAT-001.md. Production и workflow bytes
не изменились. Повторный exact-head CI обязателен; старый FAILURE не объявляется
успешным по локальному исправлению.
