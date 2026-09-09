# QUALITY-GRAPH-CURRENT-CI-001

## Простыми словами

Quality Graph показывает результаты наших существующих проверок. Тесты не
запускаются повторно; пропуск не выдаётся за успех. Штатному publisher разрешены
его комментарии, метки и итоговые проверки по решению владельца2026-09-09.

## Public seam

`python3 tools/delivery/quality-graph-report.py --full true|false|unknown
--results-json JSON --graph-digest HEX64 --output-dir RELATIVE_PATH`.
JSON содержит ровно plan,fast,unit,integration,e2e,governance,verify со строковыми
success/failure/cancelled/skipped. Integration — GitHub aggregate обеих matrix частей.
Titles равны plan,fast,unit,Integration,e2e,governance,verify соответственно.

Вход provenance: GITHUB_REPOSITORY, GITHUB_EVENT_NAME=pull_request,
GITHUB_EVENT_PATH, GITHUB_RUN_ID, GITHUB_RUN_ATTEMPT, GITHUB_WORKSPACE.
PR identity и head берутся из event.pull_request, repository сверяется с event.repository.
GITHUB_SHA может быть synthetic merge SHA GitHub; его нельзя выдавать за PR head.
Checkout в CI остаётся exact PR head, как в исходном Repository verification.
Output только внутри workspace, symlinks/path escape запрещены. Существующий
identical набор можно подтвердить повторно; другое содержимое/неполный старый набор
дают conflict без перезаписи. Валидация всего ввода предшествует любой записи.

Mapping success→passed, failure→failed/command, cancelled→cancelled/cancellation,
skipped→skipped. При plan=success/full=true пропущенная категория требует verify=failure;
при docs-only четыре категории обязаны skipped. Успешный verify несовместим с failed/
cancelled обязательным результатом. Неуспешный plan допускает full=unknown и
skipped dependents только с неуспешным verify. Нарушение — input failure.

Output filenames NODE.json, штатный Result v0: schemaVersion0,nodeId,title,status,
summary,provenance; arrays metrics/findings/annotations/diagnostics/controls/notes
пусты. failureKind задан только для failed/cancelled. Summary у skipped явно
указывает docs-only либо dependency not executed, не утверждает выполнение тестов.
Успех печатает QUALITY_GRAPH_REPORT_OK nodes=7, failure —
QUALITY_GRAPH_REPORT_FAILURE category=input|provenance|output с exit1.

## Normative behavior

## Stock publisher admission guard

Stock0.1.7 сохраняется без patch. Перед completed publish выполняется read-only
CLI `python3 tools/delivery/quality-graph-preflight.py`, inline bytes которого
проверены на равенство source в trusted workflow. Нет checkout или fetch PR code.
Input: GITHUB_EVENT_PATH (workflow_run/completed/pull_request), GITHUB_REPOSITORY,
GITHUB_API_URL, GITHUB_TOKEN. API GET only. Run endpoint подтверждает event
run ID и attempt; artifacts endpoint читается полностью с pagination.

Expected nodes: plan,fast,unit,integration,e2e,governance,verify. Каждый имеет ровно
один non-expired quality-result-NODE-CURRENT_ATTEMPT. Unknown quality-result node,
некорректное имя, повтор node/attempt, future attempt, отсутствующий current node
и событие неактуального attempt дают exit1 и QUALITY_GRAPH_PREFLIGHT_FAILURE.
Artifacts предыдущих attempts разрешены только при полном текущем наборе: stock
выбирает current, история не удаляется. Не-QG artifacts игнорируются.
Run/head/repository из API сверяются с event; PR head/run/digest и bytes дополнительно
проверяются штатным publisher. HTTP error, malformed API JSON или цикл pagination
не дают успех. Success печатает QUALITY_GRAPH_PREFLIGHT_OK nodes=7.

При отклонении guard publisher не вызывается; trusted workflow становится failed,
не публикует новый success и не меняет комментарии/метки. Обычный verify остаётся
независимым gate. Это validation integration вокруг штатного publisher,
не новая implementation Check Runs. Отказы guard и stock не скрываются.

## Purpose

Автор PR получает достоверную сводку текущих проверок без повторного исполнения
тестов. Отказы и пропуски различимы, результат привязан к проверенному коммиту.

## Requirements

### Requirement: One authoritative verification run

CI SHALL сохранять текущие команды и full/docs-only selection из VERIFICATION-PR-CYCLE-001.
Quality Graph SHALL публиковать результаты plan, fast, unit, integration, e2e,
governance и verify, не запуская тестовые категории второй раз. Integration
SHALL учитывать outcome обеих изолированных частей существующей matrix.

#### Scenario: Full verification
- **WHEN** кодовый PR проходит успешные plan, fast, четыре категории и verify
- **THEN** все семь nodes имеют passed, каждая категория выполнена один раз,
  обе integration части обязательны; существующий verify выдаёт VERIFY_OK.

#### Scenario: Documentation only
- **WHEN** план выбрал docs-only и fast/verify успешны
- **THEN** четыре категории отображаются skipped с причиной docs-only,
  остальные три passed; результат остаётся DOCS_VERIFY_OK, не full proof.

#### Scenario: Failed or cancelled category
- **WHEN** любая категория, в том числе одна integration часть, отказала или отменена
- **THEN** её результат failed или cancelled; итог не успешен, существующий verify
  продолжает блокировать неполные/неуспешные проверки.

### Requirement: Exact and complete result reports

Система SHALL формировать семь native reports из полного явного набора GitHub
job outcomes. Node identities фиксированы; неизвестные, отсутствующие и повторные
ключи и некорректные значения отвергаются. Report SHALL сохранять repository,
PR head, PR number, run ID, attempt и digest декларации, без пользовательских overrides.
Генерация report не SHALL менять бизнес-данные или исполнять тесты.

#### Scenario: Valid native collection
- **WHEN** GitHub передал полный терминальный набор outcomes и валидное событие PR
- **THEN** штатный native collector принимает все семь reports с точными
  identities и provenance, не меняя failed/cancelled/skipped на passed.

#### Scenario: Invalid report input
- **WHEN** JSON неоднозначен, потерян node, добавлен чужой node, отсутствует
  provenance либо данные события противоречат repository
- **THEN** генерация завершается ненулевым кодом без успешного набора reports;
  причина видна как QUALITY_GRAPH_REPORT_FAILURE, предыдущие результаты не затираются.

#### Scenario: Repeated generation
- **WHEN** повторяется тот же вход в том же run/attempt
- **THEN** bytes reports совпадают; генерация другого входа не может молча
  заменить уже опубликованный набор той же identity.

### Requirement: Standard trusted publication

Штатный publisher SHALL читать topology доверенной base branch и результаты
соответствующего PR/run. Владелец разрешает штатные Check Runs, dashboard-комментарий
и собственные метки. Publisher SHALL не исполнять checkout/code из PR и не
получать deployment/content-write или административные permissions.
Command/approval endpoints не подключаются; результат теста не переутверждается человеком
через команды Quality Graph в этом срезе.

#### Scenario: Current complete publication
- **WHEN** текущий PR/run имеет полный валидный набор результатов
- **THEN** появляется соответствующий итоговый check и штатная сводка,
  собственные метки отражают результат; посторонние метки и история не удаляются.

#### Scenario: Invalid or obsolete evidence
- **WHEN** результаты отсутствуют, malformed, неоднозначно дублированы либо
  относятся к другому head/run/attempt/digest
- **THEN** они не публикуются как успешное доказательство текущей проверки;
  отказ виден в check или publisher workflow. Старый head не перезаписывает новый.

#### Scenario: Repeat event
- **WHEN** повторяется событие того же завершённого запуска
- **THEN** штатная сводка и итог сходятся к тому же результату без новых
  противоречивых approvals; история реальных запусков сохраняется.

### Requirement: Actual verification before completion

Завершение #25 SHALL подтверждаться независимыми reviews, полным exact-head CI
и фактическим publisher на default-branch topology. Локальный transport test
не SHALL выдаваться за проверку GitHub permissions или установки workflow.

#### Scenario: Bootstrap and representative PR
- **WHEN** bootstrap PR проверен и слит, затем выполнен representative PR
- **THEN** записаны exact head/run/attempt/digest и реальные check/comment/label
  результаты positive/negative сценариев; до этого #25 остаётся открытой.
