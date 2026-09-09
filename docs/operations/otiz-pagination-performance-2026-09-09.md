# Производительность реестра ОТиЗ на 30 000 объектов — 2026-09-09

**Статус: ТРИ ВОСПРОИЗВОДИМЫХ BOUNDED BENCHMARK CHECKPOINT ВЫПОЛНЕНЫ.** Это локальное
синтетическое измерение SQL/application read seam, не production SLA и не замена
browser/HTTP acceptance.

## Контур и воспроизведение

Команда:

```sh
php -d memory_limit=512M tools/benchmarks/otiz-object-register.php 30000 \
  > /tmp/fm2-page17-benchmark-final.json
```

Benchmark использует `ObjectRegisterPagingFixture`: первые 125 объектов сохраняют
разные historical snapshots, states и closures тестового контракта; объекты
126–30000 получают одинаковую валидную норму и синтетические identifiers. Fixture,
DB prefix и база одноразовые, production/preview данные не читаются.

- Git HEAD: `a9810b80f39eca6da9d849eca62e940f50135373` плюс текущий WIP.
- SHA-256 `app/Otiz/MariaDbObjectRegister.php` в начале и конце:
  `f8e621319555740e16ea8f03dffcc39685f0f4270ad3aef4d50a25ba0422cc09`.
- `sourceStable=true`; во время измерения production source не менялся.
- Инструмент: `tools/benchmarks/otiz-object-register.php`.
- Raw evidence: `/tmp/fm2-page17-benchmark-final.json`; файл содержит только
  синтетические plans/metrics и остаётся вне repository.

`memory_reset_peak_usage()` вызывается непосредственно перед каждым public read.
`SHOW SESSION STATUS` выполняется на том же DB connection до и после read; поэтому
`Questions delta raw` включает завершающий status probe. Это session-local счётчик,
он не загрязняется параллельными запросами других соединений. `Bytes_sent` — delta
того же соединения, включая DB result traffic, а не размер HTTP-страницы.

## Результаты

| Сценарий | Время, с | Total / rows | Questions raw | DB Bytes_sent | Peak PHP |
|---|---:|---:|---:|---:|---:|
| Первая страница, `regnumber_asc`, 50 | 5,810 | 30000 / 50 | 9 | 1 016 539 | 8 MiB |
| Последняя страница 600, `regnumber_asc`, 50 | 5,799 | 30000 / 50 | 9 | 1 016 987 | 8 MiB |
| Literal search по уникальной синтетической цели | 5,480 | 1 / 1 | 9 | 995 569 | 8 MiB |
| State `missing_norm` | 15,599 | 1 / 1 | 9 | 995 655 | 8 MiB |

Result digests соответственно:

```text
first  1fc5e46bf05aaf0184b532bfbe82f20e5d48935b0292cf9d2605719defe06386
last   edf96e492108b8f21d29f5b82ab667faca1a2bff8e217f565bf5a9e27f075119
search fd942dfcb4c5fc4b22996183aa110d6513801231228387d00edf345a6624028e
state  6d211299f20d1bd01277c77e8b244cdd5f71ac2ec2e66c5d661dc983ee712c7f
```

Все cardinalities соответствуют fixture. Peak memory остаётся 8 MiB, то есть
bounded-memory fold не материализует 30 000 application rows в PHP.

## EXPLAIN

Benchmark получает private SQL builder только через reflection для диагностики;
это не новый public API и не нормативный тест. `EXPLAIN FORMAT=JSON` выполняется
для фактической page-query каждого сценария.

| Сценарий | Plan SHA-256 | Существенный план |
|---|---|---|
| first | `9f6f714d228b0e4a69284ba00cbd9699570e6e1c14a303b799c5b7fef97c2be2` | `fm_maintable l`: `ALL`, rows 30000; ранний raw-page derived оценивается в 50 строк; details — `eq_ref PRIMARY`. |
| last | `26a76b3a3c9fb182625473ce0e90bac3fe8f7b8d8709d7ebbfa4a22b9c10f7c6` | `l`: `ALL`, rows 30000; из-за sort+OFFSET derived оценивается в 30000 до последних 50. |
| search | `eb5c40db80e280d0af350dd379391922eac72b7147dcdedfb15bf944e6b5bc36` | `l`: `ALL`, rows 30000 с `LOCATE(LOWER(CONCAT(...)))`; после literal filter raw-page ограничен 50. |
| state | `cbb3d3546462c5ea24e60d4a4d2d466bbc8ccc5ec32caaa8075351f2e46a5137` | economics derived и `l`: `ALL`, rows 30000; norm-derived join выполняется над полным набором. |

Во всех планах маленькие snapshot/closure fixtures читаются через их существующие
ключи либо трёхстрочный `ALL`; это не источник scale cost. `JSON_TABLE` имеет
оценку 40 строк только для объектов с calculation trace.

## Вывод

Оптимизированный state-empty page path ограничивает дорогую нормализацию страницы,
но public read всё ещё строит глобальную summary по всем 30 000 объектам. Поэтому
first/last/search имеют общий нижний предел около 5,5–5,8 с независимо от числа
возвращённых строк. Поздняя OFFSET-страница дополнительно сканирует/сортирует полный
legacy register, но на этом fixture её время скрыто общей стоимостью summary.

State filter существенно дороже: текущий read отдельно выполняет полный filtered
count, полный filtered page и полный global summary. Результат 15,599 с согласуется
с тремя полными проходами примерно по 5 секунд. Самый прямой следующий шаг —
получать filtered total вместе с page (`COUNT(*) OVER()`) и оставлять fallback count
только для пустой/out-of-range страницы. После изменения нужен новый stable-source
запуск тех же четырёх сценариев; этот отчёт остаётся точным evidence текущего
измеренного source hash.

Сравнивать прежние HTTP числа напрямую нельзя: они включали login/router/render и
другой instrumentation. Исторически diagnostic baseline с поднятым memory limit
завершался примерно за 5,096 с и отправлял около 43,8 MB HTML; peak memory тогда не
измерялся, а при лимите 128 MiB запрос исчерпывал память. Ранний новый HTTP path
занимал примерно 19,6 с. Эти значения объясняют направление оптимизации, но не
входят в session-local таблицу выше и не являются after/before доказательством
одинакового public seam.

## Финальный structural checkpoint

После первого checkpoint SQL builder вынесен в
`app/Otiz/MariaDbObjectRegisterQuery.php`. Page query теперь присоединяет полные
`payload_json` и `inputs_json` только после `LIMIT`; global summary передаёт в PHP
узкие scalar operands и применяет те же `NativePremiumNorms`/`ObjectEconomy`, не
выполняя полный SQL norm-band join. Повторён тот же benchmark с тем же fixture и
сценариями.

Четыре source SHA-256 были одинаковы в начале и конце запуска:

```text
MariaDbObjectRegister.php      aee8c2ed90045905c00dac7c4f82119e282ccfd6897a773105ff97a02660956d
MariaDbObjectRegisterQuery.php 6dccca2ecfb35f304aaa9c0b25d5f61022a2a9a3b71ae39e0004c9b539f2a976
ObjectEconomy.php              c1efff366f9c5d661c5788f60233d168e3416725b3505d03243b676a2d292e72
NativePremiumNorms.php         2bd20f9c6c401e7e2af9f4f0cabe600f4fa56f1478ce1af9fa925595d66f8429
```

Raw evidence: `/tmp/fm2-page17-benchmark-optimized.json`.

| Сценарий | До, с | После, с | Изменение | Total / rows | Questions raw | DB Bytes_sent | Peak PHP |
|---|---:|---:|---:|---:|---:|---:|---:|
| first | 5,810 | 1,921 | −66,9% | 30000 / 50 | 9 | 2 303 157 | 8 MiB |
| last | 5,799 | 1,789 | −69,1% | 30000 / 50 | 9 | 2 303 605 | 8 MiB |
| search | 5,480 | 1,695 | −69,1% | 1 / 1 | 9 | 2 285 029 | 8 MiB |
| `missing_norm` | 15,599 | 7,345 | −52,9% | 1 / 1 | 9 | 2 285 115 | 8 MiB |

Result digests полностью совпали с предыдущим checkpoint для всех четырёх
сценариев. Это подтверждает те же totals, page object identities и summary при
изменённом плане. Увеличение DB `Bytes_sent` примерно до 2,3 MB ожидаемо: global
summary теперь потоково передаёт узкие scalar rows в PHP. Peak PHP остался 8 MiB.

Новые page-plan SHA-256:

```text
first  2fe0f0407010e8814abe1c68af534686f9e761188a9151d96c6d3f1db1cac4fb
last   8a802635fd45d5562d86c89e835f906c31599fe1af05a15096b4f349009e8cd8
search b002f3e6129b304fb52b6fe89ee77d87f085357dde0cedb19d254e0bd2197c6e
state  f127b121b90370d057506a2e33dc429895ad6d28afc1758a3e77ceac187636aa
```

EXPLAIN подтверждает post-limit blob joins: итоговый derived возвращает 50 строк,
а `fm2_pilot_object_details d` и snapshot object `so` присоединяются к нему как
`eq_ref PRIMARY`, по одной строке. First/search materialize 50-row page после
legacy `ALL 30000`; last по-прежнему проходит 30000 строк из-за OFFSET. State
filter остаётся самым дорогим: derived economics оценивается в 42000 строк поверх
legacy `ALL 30000`, а read всё ещё выполняет filtered count и filtered page
отдельно перед global summary.

На этом checkpoint public-module результат укладывался примерно в 1,7–1,9 с для
state-empty first/last/search при 30 000 объектах и 8 MiB peak PHP. State filter
занимал 7,345 с; следующий frozen checkpoint ниже устраняет его отдельный count.

## Frozen source: filtered total в page query

Последнее store-only изменение получает total непустого state filter через
`COUNT(*) OVER() filtered_total` в той же economics page query. Отдельный full
count остаётся fallback только для пустой/out-of-range filtered page. Все четыре
сценария повторены на frozen source; composite hashes снова стабильны от начала до
конца:

```text
MariaDbObjectRegister.php      dbed3e603c20dcc31edcd1839bb5833204df88facafb60dce2564dc563f8335e
MariaDbObjectRegisterQuery.php 6dccca2ecfb35f304aaa9c0b25d5f61022a2a9a3b71ae39e0004c9b539f2a976
ObjectEconomy.php              c1efff366f9c5d661c5788f60233d168e3416725b3505d03243b676a2d292e72
NativePremiumNorms.php         2bd20f9c6c401e7e2af9f4f0cabe600f4fa56f1478ce1af9fa925595d66f8429
```

Raw evidence: `/tmp/fm2-page17-benchmark-final-count-window.json`.

| Сценарий | Время, с | Total / rows | Questions raw | DB Bytes_sent | Peak PHP |
|---|---:|---:|---:|---:|---:|
| first | 1,325 | 30000 / 50 | 9 | 2 303 157 | 8 MiB |
| last | 1,810 | 30000 / 50 | 9 | 2 303 605 | 8 MiB |
| search | 1,780 | 1 / 1 | 9 | 2 285 029 | 8 MiB |
| `missing_norm` | 4,061 | 1 / 1 | 8 | 2 285 075 | 8 MiB |

State latency снизилась с 7,345 до 4,061 с, а session Questions raw — с 9 до 8.
Остальные времена находятся в ожидаемом разбросе повторного локального запуска.
Result digests снова в точности совпали с обоими предыдущими checkpoints.

Frozen page-plan SHA-256:

```text
first  e2569d5ff88db49e996576ac73721f62b9386f88d8941589343c7eb9c6744028
last   99e214ed3a423a5fadb572f71915317b65751c361979d6248d204032dcb71473
search 5946c7027c01646fff0aa61442be25a7cdfd35754f16ea945a1bc8aa5b07ebf3
state  a39f34629d6c7dde852445f3aedd634993a73b997b65444a46f8cf7d38b414ed
```

State EXPLAIN включает window computation над полным filtered economics set до
`LIMIT`, что необходимо для точного total, но второго полного count больше нет.
Post-limit `d`/`so` blob joins остаются `eq_ref`; first/search ограничивают raw
derived 50 строками, last сохраняет полный OFFSET scan. Итоговый доказанный диапазон
для этих четырёх сценариев — 1,325–4,061 с при 30 000 объектах, 8 MiB peak PHP и
неизменных результатах.

## SQL-only baseline прежнего `objects()`

Для одинаковой 30k fixture отдельно выполнен точный buffered `SELECT` из
`rapid-pilot/Otiz.php` commit `41e39498`, строки 244–270. В SQL заменены только
trusted одноразовые table prefixes. Старый PHP norm/current-progress loop и HTML
render не выполнялись, поэтому это baseline DB-read footprint, а не полное время
старого HTTP.

Финальный запуск сначала измерил четыре candidate-сценария с чистым allocator
high-water mark, затем legacy baseline, чтобы его buffered 30k result не загрязнил
candidate memory metrics. Source composite hashes остались равны frozen values
выше.

| Метрика legacy SQL | Результат |
|---|---:|
| Время exact SELECT + `fetch_all` | 1,567 с |
| Строк | 30000 |
| Questions raw | 2 |
| DB Bytes_sent | 9 181 728 |
| PHP memory before / after | 8 / 64 MiB |
| Peak PHP | 68 MiB |
| SQL SHA-256 с одноразовым prefix | `64cc6ea70d3b19eedd0dc8757da815d8135bbd03c3cc2d4a1abd6dfdd26d0737` |
| Row identity digest | `e3f3b9762a994e83a66addb08f4be1240511c892f358f074218f49ed0ee6e115` |
| EXPLAIN SHA-256 | `c829703265cf3e4b4b81e96a18364ebca00f9f69be966d2e6fc673edc07e72fd` |

EXPLAIN показывает `fm_maintable l` как `ALL` примерно 30197 строк, details как
`eq_ref PRIMARY`, latest snapshot через correlated `NOT EXISTS`; closures на этой
fixture — два трёхстрочных derived scans. Главная измеренная цена legacy read —
получение всех полных rows: примерно 9,18 MB DB traffic и сохранённые 64 MiB PHP
после `unset`, peak 68 MiB. Candidate возвращает 50/1 page rows, потоково сворачивает
summary и остаётся на 8 MiB peak, хотя выполняет больше узких запросов.

На том же финальном запуске candidate дал 1,374 с first, 1,782 с last, 1,328 с
search и 5,186 с state; Questions raw 9/9/9/8, result digests неизменны. Разброс
state между двумя frozen запусками 4,061–5,186 с отражает локальную вариативность;
оба результата сохраняют один запрос меньше, точный total/result и bounded memory.
Raw combined evidence: `/tmp/fm2-page17-benchmark-final-with-baseline.json`.
