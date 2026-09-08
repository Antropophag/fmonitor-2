# Подготовка распоряжения: задержка TCP-переходника (#50/#51)

Исходный main: `c8e09642ff0e376963ff328e63c5b8c3422fba08`.
Работа изолирована в `codex/issue-50-prepare-performance`. Исходный установленный
образ — `fmonitor2-manual:issue46-1fd20d34`; прежние handoff со stand4990cf1
не описывают текущий runtime. Primary evidence и auth state остаются вне repo,
в `~/.local/state/fmonitor2/issue50/`.

## Воспроизведение и причина

Read-only headless Chromium на установленном стенде: сама форма 0,25–0,53 с,
открытие modal около 30 мс; поиск с 20 результатами 1,43–1,81 с, HTTP 1,16–1,37 с.
Прямой HTTP с тем же actor: 20 результатов — 1298 мс; без результатов — 130/45 мс.
Это уточняет #50: задержка до готового выбора сильнее проявляется в соседнем #51.

Одинаковый production selection API на той же БД из контейнера:

| Операция | Прямое соединение MariaDB | Через штатный socat |
|---|---:|---:|
| readSelectionPortal | 36 мс | 293 мс |
| searchEligibleInstallers, 20 результатов | 43 мс | 1302 мс |

Поиск выполняет 65 prepared statements, включая повторное чтение кадрового proof;
без результатов — 23. Эти повторы усиливают транспортную задержку, но не являются
необходимым предметом данного исправления. SQL быстро исполняется на сервере.
Переходник запускается без `TCP_NODELAY`; отключение накопления малых сообщений
на обеих его TCP-сторонах устраняет основную наблюдаемую задержку.

## Контролируемое доказательство

Отдельная MariaDB `fmonitor2-issue50-test`, порт23350; отдельный socat-контейнер
`fmonitor2-issue50-proxy`, порт23351. Использован socat из того же установленного
образа. Разница команд — только `nodelay` на listener и destination; адрес MariaDB
заменён тестовым. Синтетические fixture DB/state/session принадлежат проверке,
закрываются в finally. Никаких реальных Bitrix-вызовов.

Набор: объект4512, действующий ФКР actor/engineer, 23 работника с неизвестной датой
приёма и полным подтверждённым кадровым снимком. Публичный маршрут:
карточка → подготовка → modal → поиск → 20 результатов → локальный выбор работника.
Снимок бизнес-таблиц до/после побайтно равен; POST запрещён probe-ом.

Медианы пяти browser samples после одного прогрева:

| Вариант | Форма | Поиск до selectable result | HTTP поиска |
|---|---:|---:|---:|
| Прямое соединение | 197,76 мс | 394,70 мс | 130,31 мс |
| Старый переходник | 212,17 мс | 875,43 мс | 604,94 мс |
| Переходник с nodelay | 211,67 мс | 366,63 мс | 106,26 мс |

Paired regression: дополнительная медианная задержка поиска не более200мс
к direct control. Старый переходник: **RED**, +480,74мс (exit1); новый: **GREEN**,
−28,07мс. Отрицательная разница отражает вариацию измерений, не ускорение относительно
физически прямого соединения. Ошибок браузера/запросов нет, history preserved.
Форма на этом малом синтетическом наборе не показала значимого ускорения — не
переносить выигрыш поиска на каждую операцию.

Verifier: `tools/diagnostics/measure-order-prepare.cjs`, SHA256
`ea9d168a6f47ee640a04babd971ac96eccf4b216ac3d7c006d549d38d250569e`.
Private synthetic runner: `synthetic-browser.php`, SHA256
`dd02577b13e1a96d3c1fda4c8cc3aa9e5a485a9cbc4f1883148a8b555cd5004e`.
Сырые timings: `browser-direct.log`, `browser-proxy-red.log`, `browser-proxy-green.log`.
Контрольный API-only прогон также показал search615–976→85–89мс; прикладной код одинаковый.

Повтор live read-only проверки:

```sh
node tools/diagnostics/measure-order-prepare.cjs \
  http://127.0.0.1:8092 /absolute/path/to/shlz-ui/node_modules/playwright \
  /absolute/private/storage-state.json OBJECT_ID SEARCH_QUERY
```

Передавайте абсолютный путь к модулю Playwright: Node разрешает `require()`
относительно verifier, а не shell cwd. Объект должен иметь действие загрузки
распоряжения, запрос — находить работников. Порог1000мс по умолчанию предназначен
для диагностики локального стенда. Для paired evidence установлено
`FMONITOR_PREPARE_SAMPLES=6`; iteration0 исключена при сравнении медиан.

## Проверки и границы

На отдельной БД23350 PASS: selection_http_admission, selection_http_flow,
selection_http_failures, selection_native_outcomes, installer_search_http_manual_pilot,
selection_unknown_employment_manual_pilot, docker_bootstrap_manual_pilot.
Ни один существующий тест или assertion не ослаблен. Timing probe запускается
явно; wall-clock проверки не добавлены в обязательный общий CI.

Повторная ручная проверка владельцем не выполнена агентом и остаётся
отдельным критерием issue. Архитектурное устранение повторных SQL/readiness-проверок
и production runtime не входят в этот транспортный fix.

## Кандидат и независимые проверки

Production/probe commit: `0266baaaaf2225188e8984363af4c73b37d5dc11`.
Gate3 и Gate5 APPROVED; записи `reviews/tests/ORDER-PREPARE-LATENCY-001.md`
и `reviews/code/ORDER-PREPARE-LATENCY-001.md`. Производственный diff — только
comment и `nodelay` в DB relay; адреса, bind, порты, credentials и порядок startup
сохранены. Runtime-код относительно установленного1fd20d34 дополнительно содержит
ранее вмердженное изменение router из main; новый fix его не редактирует.

`make architecture-check`: PASS7rules. `PILOT-E2E-FLOW-001` — полный текущий
protected browser journey PASS на изолированной БД23350. JS/shell syntax и
`git diff --check` PASS. Полный authoritative CI и установка следуют отдельно.

SHA256 сырых синтетических browser timings:

- direct: `1ac76db4acf5a57cb13d7640436331720c1f5869c82b7732cb26cf6a676b2ed8`;
- old proxy RED: `3bba9b4a18a1d6053cf827eb105ff5d61e2c1359a571a4e61df0d9069467d2bc`;
- candidate proxy GREEN: `df07d8a753f7ae5e842241ce3c8abc577883ac233cd0133421e020166385e15e`.
