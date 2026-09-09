## Context

См. proposal.md. В main один Repository verification workflow с явной матрицей.
Старый QG workflow повторяет full harness; переносить его нельзя. Publisher
0.1.7 читает trusted base quality-graph.yml, artifacts exact run и актуальный PR.
Разрешение владельца снимает прежний блокер comment/label permissions.

## Goals / Non-Goals

**Goals:** граф является представлением авторитетных существующих CI outcomes.
**Non-Goals:** второй runner, обход canonical verify, автоматические approvals,
изменение branch protection, бизнес-данные и собственный publisher.

## Decisions

Владелец integration — tools/delivery. Публичный CLI report получает node id,
full flag, полный JSON GitHub needs outcomes и выводит native report. Результат
не запускает команды повторно. Категория integration представляет общий outcome
двух изолированных shards; любой fail входит в failure aggregate.

Существующий workflow переносится в .github/workflows/quality-graph.yml и
называется Quality Graph, потому что stock publisher ищет этот exact path.
Repository-verification.yml удаляется, второй workflow не запускается.
Сохраняются jobs, commands, matrix и aggregate; их старый executable test меняет
только путь чтения YAML.

После текущего verify запускается read-only reporting job: needs всех семи jobs,
collect каждого native report и immutable artifact с exact run/attempt provenance.
Пропуск категории допустим только при full=false и успешном plan; skipped
не превращается в passed. Missing, cancelled, failed и некорректный input
никогда не дают зелёный полный результат. Существующая команда aggregate
и её публичные тесты сохраняются.

Декларация содержит те же семь nodes и зависимости; checked manifest получается
pinned compiler. Repository checker проверяет точное соответствие job/report/node,
единственный запуск категорий, сохранность CI selection и permissions. Самодельный
publisher не появляется: trusted workflow запускает pinned upstream watch/publish.
Code из PR в publisher не checkout-ится и не исполняется. Комментарии/метки
сохраняются штатным API; command/approval surfaces не подключаются.

Сначала transport probes штатного publisher проверяют PASS/FAIL, missing/malformed/
duplicate и stale results/head/run/attempt/digest. Обнаруженные несовместимости
являются реальным блокером, а не основанием ослабить acceptance. Ранние reviews
старой ветки не считаются approval новой интеграции.

## Risks / Trade-offs

- Штатная публикация требует write permissions → job-only permissions и pinned
  release, независимый review и проверка фактических API writes.
- Base declaration отсутствует до merge → отдельная bootstrap поставка после
  review и зелёного CI; #25 открыта до post-merge actual publisher matrix.
- Результаты старого attempt могут быть выбраны upstream → проверить transport
  и actual rerun; совместимость не объявлять без evidence.
- Reporting добавляет время → измерить отдельно; полного второго harness нет.

## Migration Plan

Offline probe2026-09-09 подтвердил принятие другого attempt/duplicates в stock0.1.7.
Перед publish добавляется read-only preflight по executable spec: current full set,
no future/duplicate/unknown artifact. Старые artifacts сохраняются, если current
набор полон. Содержимое/provenance проверяет сам stock. Preflight source встроен
inline в trusted workflow; checker сравнивает bytes с tested file.
При отказе publisher job failed, stock writes не выполняются. Не обещается
stock completed-failure check при раннем preflight rejection: видимая ошибка —
publisher workflow, canonical verify сохранён.
Watch сопоставляет live titles; matrix integration остаётся aggregate node,
live integration может оставаться waiting до terminal native results. Это предел
визуализации прогресса, не полноты итоговой проверки двух shards.

Сначала spec/RED/Gate3, затем implementation/focused/Gate5 и один полный CI.
После reviewed bootstrap merge выполнить isolated representative PR positive и
negative matrix, сохранить URLs/head/run/attempt/check identities. До завершения
обоих этапов #25 не закрывается. Rollback удаляет только новые reporting/publisher
steps и declaration, сохраняя canonical CI и историю GitHub.
