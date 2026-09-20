# WEEKLY-FKR-ATTENTION-EMAIL-001 — еженедельный отчёт руководителя ФКР

Версия: 0.1
Статус: Gate 1 candidate
Actor: активный пользователь с действующей ролью «Руководитель ФКР»
Public seams: `WeeklyFkrReport::build(recipientIdentity, generatedAt)`; weekly Jobs scheduler/handler; существующий outbox delivery transport

## Простыми словами

Каждый понедельник руководитель получает письмо только по доступным ему объектам: планы открытия/закрытия, изменение подтверждённого прогресса, фактическую просрочку и раздел «Обратить внимание». Последний не объявляет будущую просрочку: он объясняет, каких оснований или готовности пока не хватает. Отчёт ничего не исправляет в монтажных делах.

## 1. Время и идентичность

1. Scheduler работает в `Europe/Moscow` и создаёт один логический запуск в понедельник не ранее 09:00.
2. `reportWeek` — ISO-дата понедельника; idempotency key: `weekly-fkr-report/v1/<reportWeek>`.
3. `planPeriod = reportWeek..reportWeek+6 days`; `progressPeriod = reportWeek-7 days..reportWeek-1 day`, границы включительны.
4. Для `2026-09-21T09:00:00+03:00`: планы `2026-09-21..2026-09-27`, прогресс `2026-09-14..2026-09-20`.
5. До 09:00 job не создаётся; повтор возвращает тот же job без предметных фактов.

## 2. Получатели и scope

1. Нужны активная identity, действующий role grant точной роли руководителя ФКР и валидный current email.
2. Для №11 canonical scope равен всем объектам, разрешённым действующим глобальным permission `objects.read`. Каждый подходящий руководитель получает один и тот же полный набор доступных объектов; индивидуальные закрепления/фильтры руководителей не вводятся. Permission проверяется в read boundary до materialization.
3. Generation фиксирует identity, reportWeek, scoped payload и template version, но не SMTP password.
4. Перед каждой SMTP-попыткой identity, роль и email разрешаются заново. Отзыв доступа даёт permanent `RECIPIENT_INELIGIBLE` без сети. При смене email используется только новый валидный адрес; дедупликация остаётся по identity.
5. Invalid/missing email учитывается без raw email в обычном output/log.

## 3. Содержание

HTML и plain text строятся из одной immutable view model в порядке: `Плановые открытия`, `Плановые закрытия`, `Прогресс за прошедшую неделю`, `Просрочка`, `Обратить внимание`.

Строка содержит адрес, регистрационный номер и absolute HTTPS link из trusted `FMONITOR_PUBLIC_BASE_URL` плюс canonical object id. Значения экранируются. Сортировка: плановая дата, регистрационный номер bytewise, object id — ascending. Пустой раздел: `Данных за период нет`.

Тема: `FMonitor — недельный отчёт ФКР, <DD.MM.YYYY>–<DD.MM.YYYY>`. Письмо показывает обе границы и `Сформировано: <DD.MM.YYYY HH:MM Europe/Moscow>`.

### 3.1 Outlook- и web-совместимая вёрстка

1. HTML-письмо — самостоятельный документ без JavaScript и внешних запросов, шириной content container не более `680px`, с presentation tables (`role=presentation`), вложенными таблицами для колонок и inline CSS на критических свойствах.
2. Запрещены `<img>`, `<picture>`, `<svg>`, CSS `background-image`, data URI, web-fonts, external stylesheet/font/script и tracking pixel. Отсутствие изображений SHALL NOT оставлять пустые места или alt-заглушки.
3. Используются системные `Arial, Helvetica, sans-serif`, safe solid colors, padding на `td`, явные width/align/valign и layout, сохраняющий читаемый одноколоночный порядок при ширине `320px` и при игнорировании media queries.
4. Основной текст не мельче `14px`, line-height не меньше `1.4`; touch link не меньше `44px` по высоте на узком web viewport. Смысл статуса не кодируется одним цветом: каждый badge/блок имеет текстовую метку.
5. Визуальный язык наследует FMonitor: тёмно-синий заголовок, спокойный светлый фон, белый content canvas, тонкие нейтральные разделители, красный только для «Просрочка», янтарный как дополнительный акцент «Обратить внимание». Иерархия не зависит от gradient, shadow или декоративных карточек.
6. Outlook desktop fallback и web render показывают один порядок, полный текст, рабочие HTTPS-ссылки и те же данные. Unsupported CSS не скрывает контент; явные foreground/background на ключевых cells сохраняют контраст в dark mode.

## 4. Планы

1. Открытия: незавершённое открытие с canonical plan date внутри planPeriod, дата и current opening status.
2. Закрытия: незавершённое закрытие с plan date внутри planPeriod, `workProgress 0..85`, `documentProgress 0..15`, total и UNKNOWN.
3. Исправленная append-only дата применяется as-of generatedAt. Null/UNKNOWN не превращается в план.

## 5. Прогресс предыдущей недели

1. Источник — подтверждённая append-only история as-of конца дня перед периодом и конца последнего дня периода в `Europe/Moscow`, с действующими корректировками.
2. Строка включается при изменении известных total либо UNKNOWN компонента/границы.
3. `start=(40,0)`, `end=(55,5)` даёт `Работы 40→55 из 85`, `Документы 0→5 из 15`, `Всего 40→60%`, `+20 п.п.`.
4. UNKNOWN не заменяется нулём; невозможный delta отображается как `Недостаточно данных`.

## 6. Просрочка

Plan date строго раньше local generated date, действие не завершено. Показываются тип, дата и полные календарные `daysLate`. Lookback cutoff нет; сегодняшняя дата не просрочена.

## 7. «Обратить внимание»

1. Рассматриваются незавершённые действия со сроком от generated local date до `+6 days` включительно.
2. Для открытия причины предоставляет один native opening eligibility seam; email не копирует правила.
3. Для закрытия причины: `workProgress < 85`, `documentProgress < 15`, каждое active canonical blocking violation.
4. Неопределимая применимость/snapshot даёт строку `Недостаточно данных для оценки`.
5. Полная готовность без violation не включается. Запрещены формулировки `потенциальная просрочка`, `будущая просрочка` и утверждение о просрочке в этом разделе.

## 8. Размер

Максимум subject+HTML+text до SMTP encoding — `5_242_880` UTF-8 bytes. Предел должен превышать measured focused fixture из 1 000 строк во всех секциях минимум вдвое. Строки не обрезаются: overflow даёт permanent `REPORT_TOO_LARGE` без сети, с safe byte count/limit.

## 9. Delivery и concurrency

1. Один intent на `(templateVersion, reportWeek, recipientIdentity)`; confirmed delivered не отправляется повторно.
2. Transient outcome допускает retry; permanent — только explicit operator retry.
3. Конкурентные workers не выполняют более одной одновременной попытки intent.
4. Принятое SMTP письмо с неизвестным ACK остаётся `UNKNOWN_DELIVERY`, не считается delivered и не ретраится автоматически.
5. Меняются только technical jobs/outbox/attempt facts, не domain facts.

## 10. SMTP runtime

Обязательны внешние `FMONITOR_SMTP_HOST`, `PORT`, `ENCRYPTION=tls`, `USERNAME`, `PASSWORD`, `FROM_ADDRESS`, `FROM_NAME`, `TIMEOUT_SECONDS`, `VERIFY_PEER=true`, `FMONITOR_PUBLIC_BASE_URL`. Committed password запрещён. Для `k2-mailer` FROM совпадает с username.

Production readiness false до сети при invalid/missing config, `VERIFY_PEER!=true`, non-HTTPS base URL или sender mismatch. Exceptions/CLI/logs содержат только allowlisted codes.

Non-production `FMONITOR_SMTP_TEST_RECIPIENT` разрешён лишь при `FMONITOR_RUNTIME_ENV!=production`; он меняет transport address, но не identity/deduplication. Исходный email не попадает в тему/тело; safe result содержит `testRecipientOverride=true`.

## 11. Acceptance matrix

| ID | Observable acceptance |
|---|---|
| A1 | Московские периоды, before/at/after 09:00 и scheduler replay |
| A2 | Active role/email/`objects.read`; каждый руководитель получает полный общий набор объектов |
| A3 | Пять секций, labels/order, empty, escaping, HTTPS links, sorting; image-free Outlook/web markup и narrow layout |
| A4 | Current-week boundary dates и corrected plan date as-of |
| A5 | Historical 85/15, correction, UNKNOWN≠0, fixed delta |
| A6 | Old/today overdue, exact days, no cutoff |
| A7 | Seven-day attention eligibility/readiness/violation/UNKNOWN/wording |
| A8 | Intent success/repeat/transient/permanent/unknown/concurrent |
| A9 | Address re-resolution, revoked recipient, email change before retry |
| A10 | Env/TLS/sender/redaction/test override |
| A11 | 5 MiB limit, measured fixture, fail-complete overflow |
| A12 | No domain mutation from build/replay/failed delivery |

## 12. Не входит

Live-send в tests/CI, attachments/BCC, ML forecast, новые роли/permissions, изменение правил открытия/закрытия или 85/15, domain snapshot table, логика в `rapid-pilot`, импорт секрета из `../k2`, изменение UI карточки.
