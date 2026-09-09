## Purpose

Автор PR получает достоверную сводку текущих проверок без повторного исполнения
тестов. Отказы и пропуски различимы, результат привязан к проверенному коммиту.

## ADDED Requirements

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
