# INSTALLER-UTILIZATION-FORECAST-001 — шестинедельный прогноз загрузки

## Простыми словами

Дашборд показывает живой плановый прогноз текущей и следующих пяти недель. История наблюдений остаётся отдельной и неизменяемой; прогноз ничего не записывает и не назначает людей автоматически.

## Actor и public seams

- Actor: прогнозные counts/details доступны активному пользователю с полными `objects.read` и `installers.read`; object-scoped actor не получает частичную аналитику. Сам общий dashboard сохраняет более новый контракт доступа всех аутентифицированных ролей, но без разрешения прогноз показывает только sanitized unavailable state.
- Reads: `GET|HEAD /pilot/dashboard` и `/pilot/dashboard/installers/forecast/{weekStart}/{bucket}`.
- Timezone: `Europe/Moscow`; `weekStart` — понедельник `Y-m-d`; buckets: `busy|free|releasing|conflict|unknown`.

## Нормативный контракт

1. Горизонт — ровно текущая московская неделя понедельник–воскресенье и пять следующих. При cutoff `2026-09-30`: `09-28..10-04`, `10-05..10-11`, `10-12..10-18`, `10-19..10-25`, `10-26..11-01`, `11-02..11-08`.
2. Каждый delivered+employed tabId входит ровно в один base bucket недели: `busy`, если interval пересекает неделю; `free`, если ни один не пересекает; `unknown`, если identity/обязательное начало нельзя вывести. `busy+free+unknown=denominator`.
3. `releasing` — overlay: назначение заканчивается в неделю и после конца нет другого пересекающего назначения. `conflict` — overlay: два назначения пересекаются хотя бы один день недели. Один человек считается не более раза.
4. Для открытого дела start=`actual_start_date`; для применённого, но не открытого — effective planned start. Draft selection/original без application не занимает человека. Latest native application владеет составом; registered fallback допустим только без native application.
5. End — более ранний подтверждённый ПТО или effective planned finish с current deadline certificate. Неизвестный end начатой работы продолжается через горизонт. Системная неполнота source даёт unavailable, не нули.
6. Dashboard разделяет «Прогноз загрузки на 6 недель» и историю. Каждая week/group value ведёт на live detail с bounds, denominator, людьми и основаниями, сортировка `fio,tabId`; новый authoritative fact меняет прогноз, но не observations.
7. Guest получает canonical redirect. Denied/scoped actor получает общий dashboard `200` с sanitized unavailable forecast без counts/PII, а direct detail — `403`. HEAD повторяет status/headers GET и имеет пустое body. Чтение не создаёт jobs, observations, audits или domain facts.
8. UI использует штатную `shlz-ui` composition, текстовую легенду, видимый focus и различители кроме цвета; на 390 px нет page overflow, допускается локальная прокрутка.
9. Bulk query count bounded и не зависит линейно от identities; runtime не загружает `rapid-pilot` или `app/PilotHttp`.
10. No schema migration, backfill, auto-allocation или writer changes. Capture-job correction — отдельный change.

## Acceptance matrix

| ID | Given/action | Expected |
|---|---|---|
| A | cutoff 30.09.2026 | exact six ranges from §1 |
| B | observations empty, sources complete | all six forecast weeks rendered |
| C | no interval / mid-week end / overlap | free; busy+releasing; busy+conflict |
| D | open-ended current interval | busy through horizon, never free |
| E | factual start / future applied / draft | actual start; planned start; draft excluded |
| F | effective finish vs earlier PTO | earliest confirmed end owns interval |
| G | malformed identity or systemic gap | UNKNOWN or unavailable, never false zero/free |
| H | bucket activation | authorized live detail matches count and reasons |
| I | facts change | forecast changes; observations byte-equivalent |
| J | guest/denied/scoped GET|HEAD | guest redirect; denied/scoped dashboard unavailable and detail 403; empty HEAD, no leak |
| K | repeated reads | deterministic and no writes |
| L | 1440/390 keyboard/touch | readable, contained, focus, activation |
| M | 0/50/125/1000 rows | bounded queries/runtime closure |
