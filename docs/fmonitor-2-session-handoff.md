# FMonitor 2.0 — session handoff

Актуально на `2026-08-29`, ветка `feature/demo-pilot-flow`.

## Режим работы

Делаем быстрый рабочий пилот без SSD/TDD-церемонии и без субагентов. После проверки его следует переложить на SSD/TDD foundation. Правило закреплено в `rapid-pilot/AGENTS.md`. Не merge-ить `main`; делать checkpoint-коммиты в текущей ветке.

UI-эталоны:

- Service Desk: Windows `C:\Users\Polly\Downloads\ЩЛЗ - фронт ServiceDesk`;
- WSL: `/mnt/c/Users/Polly/Downloads/ЩЛЗ - фронт ServiceDesk`;
- `../shlz-ui` — использовать публичные exports;
- Figma: <https://www.figma.com/design/x75wCNufIZQuwFOTLhGZi9/Service-desk--Copy-?node-id=0-1&p=f&t=8Qd9tmQOOMJiecQt-0>.

Путь Service Desk также записан в `rapid-pilot/AGENTS.md`.

## Последние checkpoint-коммиты

```text
7831c5d fix: show selected installers inside picker
fbacb32 feat: redesign installer assignment picker
e618bd1 fix: keep assignment picker available after Bitrix sync
b53f1f0 feat: add workforce filters and hourly Bitrix refresh
9a8a091 feat: scale installer directory to full Bitrix catalog
630926e feat: add rapid pilot installer directory
2dacb39 docs: save rapid pilot session handoff
bcb6fa4 feat: import production objects into rapid pilot
```

## Текущее состояние пилота

- `http://127.0.0.1:8092/pilot/objects` — `200`;
- `http://127.0.0.1:8092/pilot/objects/444/assignment-order/prepare` — `200`.

HTTP сейчас работает через оставшийся PHP dev-server, но штатная команда сообщает:

```json
{"ok":true,"running":false,"url":"http://127.0.0.1:8092/pilot/objects","generation":null,"state":"incomplete"}
```

Readiness-проверка поколения ожидает точный fixture и считает дополнительные реальные строки отклонением. После перезапуска сначала проверить URL. Если сервер пропал, поднять стенд через `php bin/fmonitor2-pilot-demo.php reset`, затем восстановить реальные объекты и workforce. Не удалять локальную БД без необходимости.

Локальная MariaDB:

```text
container: fmonitor2-redesign-test-db
127.0.0.1:23306
database/user: fmonitor2_demo
```

Локально 101 process case: один fixture и 100 реальных ещё не открытых объектов, включая `444`.

## Справочник монтажников и Bitrix

`/pilot/installers` работает на реальной интеграции Bitrix/legacy. Последняя подтверждённая загрузка:

```text
1259 пользователей с табельным номером
930 работающих
329 уволенных
1 запись без пригодного табельного исключена
```

Реализовано:

- пятизначный табельный отображается с ведущим нулём;
- над таблицей есть фильтры-таблетки;
- таблица показывает текущие закрепления;
- показывается дата последнего списка;
- обновление Bitrix выполняется раз в час.

Cron:

```cron
7 * * * * cd /home/antropophag/code/fmonitor-2 && /usr/bin/php rapid-pilot/hourly-bitrix-workforce.php >> /home/antropophag/.local/state/fmonitor2/pilot-demo/78d99d34/workforce-sync.log 2>&1 # fmonitor2-bitrix-workforce
```

`rapid-pilot/hourly-bitrix-workforce.php` создаёт завершённый sync run и атомарно обновляет каталог с provenance текущего запуска. Несогласованный provenance раньше вызывал `Service unavailable` на форме распоряжения.

## Модалка выбора монтажников

Нативный огромный `<select multiple>` удалён. Теперь:

- поиск по ФИО или шестизначному табельному;
- поиск начинается с двух символов;
- видимая выдача ограничена 20 результатами;
- строка показывает ФИО, табельный, должность и закрепление;
- выбранная строка отмечается галочкой;
- прямо внутри модалки есть блок `Выбрано` с тегами и удалением;
- выбранные также видны на форме после закрытия;
- скрытые `installerTabIds[]` формируются JavaScript.

Ключевые файлы: `app/PilotHttp/PrepareFormView.php`, `app/PilotHttp/picker.js`, `app/PilotHttp/PilotHttp.php`, `app/PilotHttp/PilotE2ECoordinator.php`, `rapid-pilot/pilot.css`.

Скрипт доступен по `/pilot/assets/picker.js`; CSP — `script-src 'self'`. Последние проверки: PHP/JS syntax OK, `git diff --check` OK, UI detector `impeccable` вернул `[]`.

## Production-данные и секреты

Legacy production и Bitrix — read-only источники. Секреты не сохранены ни в Git, ни в handoff.

```text
fmonitor-db-tunnel
127.0.0.1:13306 → configurator.shlz.ru:3306 через SOCKS
database: c1_fmonitor
```

Импортёр объектов: `rapid-pilot/import-production-objects.php`. Он выполняет против production только `SELECT`, пишет только в локальную `fmonitor2_demo` и создаёт process cases через `PilotCaseImporter`.

После reset импорт запускается с read-only реквизитами только через окружение:

```bash
FMONITOR_PILOT_ACTIVE_MANIFEST="$HOME/.local/state/fmonitor2/pilot-demo/78d99d34/active.json" \
FMONITOR_SOURCE_USER='<read-only user>' \
FMONITOR_SOURCE_PASSWORD='<read-only password>' \
php rapid-pilot/import-production-objects.php
```

Если секреты недоступны, попросить пользователя передать их снова. Не записывать credentials в документы, скрипты, логи или коммиты.

## Следующий шаг

Открыть форму объекта `444` и проверить в браузере:

1. поиск по части ФИО;
2. поиск по шестизначному табельному;
3. выбор нескольких монтажников;
4. видимость выбранных внутри модалки;
5. удаление через тег;
6. сохранение после закрытия и формирование распоряжения.

Затем продолжить сценарий `очередь → карточка → распоряжение → номер 1С ДО → открытие работ`. Технический долг: readiness-проверка должна разрешать дополнительные реальные строки при наличии fixture-сентинелов.

## Сохранность пользовательских изменений

Не удалять, не перезаписывать и не включать в checkpoint-коммиты:

```text
 M .gitignore
 M CONTEXT.md
?? docs/fmonitor-2-completed-history-migration-proposal.md
?? docs/fmonitor-2-installer-contractor-mapping-analysis.md
?? reviews/tests/PILOT-SERVICEDESK-REDESIGN-001.md
?? tools/
```

Не использовать destructive reset/clean.
