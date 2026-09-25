# INSTALLER-UTILIZATION-OBSERVATIONS-001 — ежедневная загрузка и динамика

## Actor и public seams

- Пользовательский actor: активный пользователь с `objects.read` и полной штатной `installers.read`. Guest получает штатный authentication redirect, actor без любого из двух разрешений и actor с ограниченной объектной областью получают `403` на dashboard и на каждый saved-detail URL; частичная сводка не выдаётся никогда.
- Фоновый actor: существующий jobs worker с явным разрешённым job type `installer-utilization.capture`; GET никогда не ставит и не выполняет job.
- Public reads: `GET|HEAD /pilot/dashboard` и локальный исторический drill-down `/pilot/dashboard/installers/observations/{date}/{bucket}`. `HEAD` повторяет status и безопасные headers соответствующего `GET`, возвращая пустое body.
- Business timezone: `Europe/Moscow`. Scheduler создаёт один слот ежедневно в 03:17 MSK, после штатной кадровой синхронизации; первый срез появляется только после санкционированного развёртывания.

## Нормативный контракт

1. Единственный read-owner стадии 1 классифицирует каждого уникального учитываемого трудоустроенного монтажника. Три взаимоисключающие группы: `working` (есть current), `awaiting_start` (нет current, есть confirmed upcoming), `unassigned` (нет обоих). Неоднозначная identity, неполный источник или ошибка делают capture/current summary unavailable, а не нулевыми.
2. Аналитические столбцы: `without_current = awaiting_start + unassigned`; `without_next = unassigned`. Знаменатель — число пригодных уникальных трудоустроенных identities именно наблюдения. Доли рассчитываются от сохранённого знаменателя; нулевой/недоступный знаменатель не даёт `0%`.
3. Наблюдение неизменяемо и содержит дату MSK, exact capture time, source freshness/coverage, denominator, три count, два chart count и минимальные member rows: tab identity, сохранённая группа, основания current/upcoming и object/document identities, достаточные для воспроизводимой детализации без сегодняшнего пересчёта.
4. Capture публикует header и members одной транзакцией. Unique local date делает повтор/гонку idempotent; loser читает уже опубликованный exact result. Любой source/validation/insert failure откатывает всё. Published rows нельзя UPDATE/DELETE штатным owner.
5. История содержит только реальные успешные наблюдения. Нет backfill из сегодняшних назначений; пропущенная дата отсутствует, а не равна нулю. Поздний ПТО, исправление или применение состава влияют только на следующие captures.
6. Current summary читается из текущей единой projection и показывает freshness/coverage и три группы. Historical chart читает сохранённые headers и рисует два соседних bar на каждую реальную дату: «Без текущих работ» и «Из них без следующего назначения».
7. Сравнение использует первое и последнее пригодные наблюдения выбранного ряда. Для каждого показателя показывает count, share, разность percentage points, обе реальные даты и оба denominator. При одной пригодной точке trend/дельта отсутствуют и честно подписаны.
8. Клик/keyboard activation historical bar открывает только сохранённых members exact `(observation date, bucket)`, их сохранённую классификацию и основания. Поздние факты и текущая projection не меняют список. Direct URL повторяет те же полномочия и не раскрывает иных людей/объекты.
9. Dashboard использует штатную grouped bar chart composition `shlz-ui`, без chart library, линий, Ганта, третьего installer-блока или глобального shell/CSS redesign. Узкий экран имеет локальную прокрутку/перенос без page overflow, видимый focus и доступную текстовую легенду.
10. В справочнике и карточке кадровый статус отображается штатным `shlz-ui` status label. Технические provenance-поля «источник интеграции» и «последняя интеграция» пользователю не показываются; они остаются внутренним основанием availability/freshness, не удаляются из источника и не подменяются выдуманной бизнес-подписью. Локальные панели карточки имеют нормальные gap/padding на desktop и narrow viewport.
11. Никакие financial calculations, assignments/PTO writers, calendar, `ChecklistController`, `inspection-schedule.js` или construction-control list не меняются. Прогноз на шесть недель и второй installer-блок остаются стадией 3 #258; issue не закрывается.
12. Saved member имеет стабильный `installer_tab_id`, сохранённые `fio`, `employment_status`, bucket и упорядоченные по `(object_id, document_identity)` основания. Основание содержит только `object_id`, сохранённый регистрационный номер/адрес, `document_identity`, тип (`current|upcoming`) и даты начала/окончания, если они известны. Исчезновение текущей display entity не удаляет и не пересчитывает saved row. History ограничена последними 366 реальными наблюдениями в порядке дат; detail members сортируются по `fio`, затем `installer_tab_id`; неизвестные date/bucket дают `404` без данных.
13. Capture не принимает готовые `working|awaiting_start|unassigned` состояния, флаги полноты или специальные варианты «объекты без назначений». Он получает их только от публичного read-owner стадии 1 над теми же workforce/original/application/opening/PTO facts, что питают справочник и picker. Единственный допустимый тестовый порт — инъекция отказа транзакционной публикации; он не поставляет и не меняет бизнес-классификацию.
14. Планировщик лишь ставит versioned job после успешного штатного workforce-sync slot. Worker имеет явный allowlist `installer-utilization.capture` v1 и системную authority `installer-utilization-observation-daily-v1`; web actor не может вызвать capture. Ошибка источника/транзакции даёт retryable job result, а retry повторяет тот же `(captureDate, dueAtUtc)`. Пропущенные даты не порождают ни observation, ни догоняющий job.
15. Схема наблюдений занимает следующий свободный номер canonical migration catalogue после v34, входит в production runner и соответствующий runtime-recovery profile. На уровне БД уникальна MSK-дата. Штатный observation owner предоставляет только append/capture и read seams: изменение или удаление опубликованного header/member через него отсутствует и не разрешено.

## Acceptance matrix

| ID | Given/action | Expected |
|---|---|---|
| A | draft → accepted original → factual opening → PTO on fixed workforce | draft unchanged; original reduces only `without_next`; opening reduces `without_current`; PTO restores `without_current` |
| B | workforce denominator changes between captures | each date retains real denominator/share; delta is percentage points |
| C | same slot repeated and two workers race | exactly one immutable observation/date and identical receipt |
| D | injected failure after header/member work | no partial published observation |
| E | missing calendar day | no synthetic zero bar |
| F | late PTO/correction after prior capture | old header, members and drill-down byte-equivalent |
| G | historical bar drill-down | saved list/count/bases exactly equal clicked bar, never today's selection |
| H | one suitable observation | values shown, no invented trend |
| I | incomplete/malformed sources or ambiguous identity | unavailable, no zero/free classification and no capture |
| J | guest/denied/partial actor/direct URL | authentication/403, no counts or PII leak; full authorized actor succeeds |
| K | GET/HEAD dashboard and drill-down | no jobs, syncs or observation writes |
| L | desktop/narrow + keyboard/touch | stock grouped bars, readable legend/labels, contained overflow and visible focus |
| M | installer directory/card presentation | workforce status is a status label; integration source/time are absent; panels retain readable gaps/padding |
