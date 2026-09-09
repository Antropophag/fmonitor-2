# #78: измерение и режим работы

## Проверенные исходные факты

Начальная база: main `d9811cdd5e4521457a1d61277069fe3d0793f3fe`.
PR79 merged 2026-09-09 18:27:18 UTC; candidate
`d4a5049cffc760f4eeac96c1433ef47ba382f6c9`, Actions34383652659: все checks SUCCESS.
#76 приостановлена; финансовый #70 сохранён на `9b039afd` в отдельном checkout.
Работа #78 не меняет runtime, БД или volumes.

На этой базе `tools/verification/ci.py plan` вычисляет лишь docs-only/full и
категории CI по diff уже существующего candidate. `quality-graph.yml` описывает
семь CI nodes; `quality-graph-preflight.py` допускает публикацию результатов
завершённого CI. Ни один из этих механизмов не выдаёт acceptance/integration
obligations исполнителю до написания RED. Поэтому нужен repository-owned
pre-Gate-2 planner поверх зафиксированных graph/inventory, а не только изменение
промпта. Это локальное расширение FMonitor; upstream Quality Graph не изменён.

## Выбранный рабочий режим

Норма — [compact execution protocol](../development-process.md).
Один исполнитель ведёт срез до PR candidate; независимый reviewer даёт весь
список findings за проход. Координатор принимает решения и интегрирует.
Свежие короткие задания без полной истории; parallel agents gpt-5.6-sol / low.
Оснований объявлять эту модель экономически оптимальной пока нет.

Требования остаются в normative spec; OpenSpec хранит lifecycle и ссылки;
reviews хранят evidence; delivery record хранит источник/CI и ссылки.
Старый current-goal сохранён байт-в-байт в history; рабочий документ содержит
только актуальную очередь и необходимые pointers.

## Измерение и критерии сравнения

Issue78 содержит прежний агрегат одного основного потока: 261 вызов,
62 781 751 input (62 058 240 cached), 125 468 output, включая 68 047 reasoning.
Это наблюдение владельца воспроизведено ниже через публичную команду агрегатора.
Cached input — часть input; reasoning — часть output. Не складывать их повторно.
Сумма накопительных counters по записям не является расходом.

До/после сравнивать законченные срезы с одинаковой границей, критериями gates,
CI и критичных внешних контрактов. Для каждого сохранить: main/child usage
отдельно, модель/reasoning из метаданных, число запросов/инструментов,
максимальный контекст, длительность, число полных циклов review и исправлений.
Отсутствующие метаданные обозначать unknown, не угадывать роль или модель.

Уменьшение объёма обязательного чтения измеряется отдельно от фактического
расхода. Объём входящего запроса включает и другие инструкции, инструменты,
историю; отношение размеров документов не является процентом экономии токенов.
Разные продуктовые срезы, прерванные потоки и открытая текущая сессия не образуют
контролируемую пару. Денежная стоимость и остаток квоты требуют отдельной
account/billing-разбивки; сырые session logs сами по себе её не доказывают.

## Статус доказательств

Реализация и независимые Gate 3/Gate 5 reviews завершены. Ниже измерена пара
законченных document-review задач. Полный продуктовый PR и сравнение
моделей этими пилотами не измерены; не переносить результат на них автоматически.
Полный exact-source CI кандидата остаётся отдельным обязательным доказательством.

## Direct document-size measurement

Current goal before: 49270 UTF-8 bytes / 511 lines.
After: 2750 UTF-8 bytes / 35 lines.
Archive SHA-256: `201117ead4654f382d41b19142358f26992524473530f679daad6918aa3a9fea`.
This measures one document, not request tokens or billing savings.
Independent process review: reviews/code/DELIVERY-CONTEXT-078.md, APPROVED.
Existing QG checks: workflow 5 PASS; report 9 PASS; preflight PASS; drift checker PASS.
Full candidate CI remains pending. Local uv sync unavailable (uv: command not found); generator not run and published manifest boundaries unchanged.

## Reproduced baseline checkpoint

The bounded main-session prefix 2026-09-09T14:18:10.540Z through
2026-09-09T16:11:59.156Z reproduces the issue observation exactly:

| Metric | Observed |
| --- | ---: |
| Unique logged response IDs | 261 |
| Input tokens | 62,781,751 |
| Cached input (subset of input) | 62,058,240 |
| Uncached input (difference) | 723,511 |
| Output tokens | 125,468 |
| Reasoning (subset of output) | 68,047 |
| Input + output | 62,907,219 |
| Unique tool-call IDs | 261 |
| Maximum per-call input | 438,293 |
| Maximum observed context window | 828,400 |
| Peak observed utilization | 0.529084 |

This prefix has no model/settings metadata or completed-turn duration records.
The issue's astra/medium attribution cannot be verified from this prefix;
later metadata from the same file must not be back-projected onto it. No billing
or quota claim follows. This is one main stream, not all project/subagent usage.
The incomplete current session is excluded. Reproduction instructions and safe
aggregate artifacts live under `tools/usage/`; raw logs remain outside the repo.

## Completed paired document-review pilot

Both fresh agents used observed `gpt-5.6-sol / low`, identical frozen document
hashes and the same six review criteria. The long arm loaded the complete
historical goal as startup context; the short arm used the current goal and
verified the archive by hash only. Both independently returned APPROVED with no
findings, verified input hashes before and after, and emitted task-complete events.

| Logged metric | Long context | Short context |
| --- | ---: | ---: |
| Input tokens | 405,187 | 179,019 |
| Cached input (included above) | 367,744 | 160,256 |
| Uncached input | 37,443 | 18,763 |
| Output tokens | 2,933 | 2,062 |
| Reasoning (included in output) | 905 | 647 |
| Unique response IDs | 10 | 6 |
| Logged tool calls | 9 | 5 |
| Maximum input context | 57,970 | 39,843 |
| Completed task duration, ms | 123,201 | 82,798 |
| Review correction rounds | 0 | 0 |

The measured reduction applies to this completed document-review pair. It is not
a full-PR saving, pricing comparison or statistical guarantee. Tool choices and
model output varied; runs were sequential with different concurrent machine load.
Both streams contain one completed task, no skipped records and no counter resets.
Primary logs remain private. Full safe aggregates and frozen hashes:
[token-optimization-78-pilot.json](token-optimization-78-pilot.json).

## Delivery evidence

- `reviews/code/CHANGE-VERIFICATION-001.md`: independent Gate 5 APPROVED after
  four findings were corrected through supplemental RED and Gate 3 reviews.
- `reviews/code/USAGE-AGGREGATION-001.md`: independent Gate 5 APPROVED; literal
  README baseline reproduction matches response IDs and token aggregates.
- Real #78 `plan` + `run --phase focused`: usage, planner (10 tests), and changed
  inventory verifier (15 tests) GREEN; no DB command. `make test` remains required.
- Existing QG workflow/report/preflight checks, verification CI-selection (15),
  architecture (7 rules + HTTP qualification), OpenSpec validation and diff check PASS.
- Full CI and PR identity will be recorded after the frozen candidate is pushed.

#78 remains open for full product-PR and comparative model data; the isolated
implementation pilot below completes the narrower implementation-task comparison. No model-rule change or return to #76 is
inferred from this narrower review experiment. Working stand and financial WIP
remain preserved.

## First CI and a subsequent confirmed privacy finding

[Actions34393067126](https://github.com/Antropophag/fmonitor-2/actions/runs/34393067126)
passed on `6f8fecb8949b4d2c69b1ae699625c2ce0bf44408`: all categories, literal
VERIFY_OK and stock Quality Graph publication SUCCESS in draft PR80.

The subsequent isolated implementation pilot exposed an input-discovery privacy
failure in the usage tool, reproduced independently in that candidate. A locked
directory could report zero usage; an inaccessible ancestor could leak a path in
a traceback. Invalid call_id also masked a valid fallback id. These were fixed
through new RED/Gate3 and independent Gate5; the updated tool passes real permission
and common public-CLI probes. A new full CI is warranted by this executable change
and new confirmed risk; the earlier successful run is historical evidence only.

## Completed isolated implementation pilot

Two disposable repositories started at the identical synthetic base
`f4948e726cb393262901d0377cfc6481f7c6e41d`. Both received the same frozen public
specification, test and required verification planner. Only the long-context
author loaded the historical goal. Each implemented the missing CLI, recorded
RED, passed the fixed test and planner tests, then corrected one independent
review batch. Both final candidates passed the identical additional public probe
and independent Gate5. Their implementations are experimental and were not merged
into FMonitor; the experiment instead exposed the privacy defect corrected above.

| Completed executor + code-review metric | Long startup | Short startup |
| --- | ---: | ---: |
| Logged input tokens | 2,254,515 | 1,134,085 |
| Cached input (included above) | 2,128,384 | 1,062,400 |
| Uncached input | 126,131 | 71,685 |
| Output tokens | 20,614 | 15,637 |
| Reasoning (included in output) | 6,203 | 3,621 |
| Unique logged responses | 43 | 32 |
| Logged tool calls | 39 | 28 |
| Sum of completed-turn durations, ms | 814,122 | 621,880 |
| Correction rounds / final verdict | 1 / APPROVED | 1 / APPROVED |

Both executor and reviewer roles are measured separately in
[token-optimization-78-implementation-pilot.json](token-optimization-78-implementation-pilot.json).
The shared probe Gate3 (167,366 input, 1,219 output tokens) is reported separately,
not silently charged to one arm; its context nevertheless remains in the long
reviewer's later turn. Coordinator work and the production-candidate fix are
excluded from arm totals. Tool choices, generated code and findings differed;
this is an observed completed-task pair, not proof that context size alone caused
the entire difference. Durations sum completed turns and include tool waits,
not the wall-clock critical path. Raw journals stay private; local source bundles
preserve the disposable candidates without storing their conversation logs.

This extends the pilot beyond document review to an isolated implementation and
correction cycle, under the same public checks. Full product-PR cost, other
models/reasoning levels, billing and quota remain unmeasured. The selected sol/low
rule is retained; there is no evidence here for replacing it with another model.
