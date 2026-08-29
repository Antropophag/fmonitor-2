# FMonitor 2.0 — session handoff

Актуально на `2026-08-29`, ветка `feature/demo-pilot-flow`.

## Режим работы

Продолжать быстрый рабочий пилот без SSD/TDD-церемонии и без субагентов. Правило и визуальные границы закреплены в `rapid-pilot/AGENTS.md`. Не merge-ить `main`; делать checkpoint-коммиты в текущей ветке.

UI-источники истины:

- Service Desk: `/mnt/c/Users/Polly/Downloads/ЩЛЗ - фронт ServiceDesk`;
- `../shlz-ui` — использовать публичные exports и документированные HTML-контракты;
- Figma: <https://www.figma.com/design/x75wCNufIZQuwFOTLhGZi9/Service-desk--Copy-?node-id=0-1&p=f&t=8Qd9tmQOOMJiecQt-0>.

## Последние checkpoint-коммиты

```text
60f0803 feat: filter pilot users by role
ec3fb4b fix: flatten pilot administration navigation
6abcd5c feat: split pilot user administration pages
cd6e785 feat: add rapid pilot user role directory
6b9bbd0 docs: refresh rapid pilot session handoff
7831c5d fix: show selected installers inside picker
fbacb32 feat: redesign installer assignment picker
e618bd1 fix: keep assignment picker available after Bitrix sync
```

## Стенд

Штатный launcher работает, generation `5` готова:

```json
{"ok":true,"running":true,"url":"http://127.0.0.1:8092/pilot/objects","generation":5,"state":"ready"}
```

Проверка: `php bin/fmonitor2-pilot-demo.php status`.

Readiness обновлён: дополнительные реальные process-таблицы и строки разрешены, но обязательные fixture-сентинелы всё ещё проверяются. После reset потребуется повторно импортировать production-объекты, workforce, пользователей и роли.

Локальная MariaDB:

```text
container: fmonitor2-redesign-test-db
127.0.0.1:23306
database/user: fmonitor2_demo
active process prefix: fm2d_78d99d34_g5_
active legacy prefix: fm2l_78d99d34_g5_
```

## Пользователи и роли

Рабочие URL:

- `http://127.0.0.1:8092/pilot/admin/users`;
- `http://127.0.0.1:8092/pilot/admin/roles`;
- старый `/pilot/users` отвечает `303` на `/pilot/admin/users`.

Левая навигация плоская: текстовая группа `Администрирование`, под ней самостоятельные пункты `Пользователи` и `Роли`, без раскрывающейся иерархии.

Последний подтверждённый production-снимок:

```text
41 пользователь
38 активных
3 заблокированных
14 активных ролей
41 исходное назначение legacy-роли
```

Fixture-пользователи и fixture-роли удалены из локального каталога; количество локальных строк совпадает с production-снимком.

На странице пользователей реализованы:

- Service Desk / `shlz-ui` Search field по ФИО, email или телефону;
- статусные фильтры `Все / Активные / Заблокированные`;
- раскрывающийся role-фильтр со всеми 14 ролями и числом пользователей;
- совместная работа поиска, статуса и role-фильтра;
- несколько ролей у одного пользователя;
- назначение роли через список и снятие отдельной кнопкой рядом с presentation `shlz-tag`;
- CSRF и append-only события изменения ролей в локальной pilot-БД.

Страница ролей отдельная и показывает production-название, статус и количество пользователей.

Ключевые файлы:

```text
app/PilotHttp/UserDirectoryView.php
app/PilotHttp/users.js
app/PilotHttp/PilotE2ECoordinator.php
app/PilotHttp/PilotView.php
rapid-pilot/import-production-users.php
rapid-pilot/pilot.css
```

Импортёр `rapid-pilot/import-production-users.php` выполняет против production только `SELECT`, пишет только в локальную `fmonitor2_demo`, строго синхронизирует каталоги и сохраняет дополнительные локальные назначения только для существующих пользователей/ролей.

Production-пароли не запрашиваются и не импортируются. Production использует LDAP; будущая локальная авторизация FMonitor 2.0 является отдельным контуром и пока не реализована.

После reset импорт запускается с read-only credentials только через environment:

```bash
FMONITOR_PILOT_ACTIVE_MANIFEST="$HOME/.local/state/fmonitor2/pilot-demo/78d99d34/active.json" \
FMONITOR_SOURCE_USER='<read-only user>' \
FMONITOR_SOURCE_PASSWORD='<read-only password>' \
php rapid-pilot/import-production-users.php
```

## Объекты и монтажники

- `/pilot/objects` содержит fixture и реальные ещё не открытые объекты, включая объект `444`.
- `/pilot/installers` использует реальный каталог Bitrix/legacy: последняя подтверждённая загрузка — `1259` записей, `930` работающих, `329` уволенных.
- Hourly sync работает через `rapid-pilot/hourly-bitrix-workforce.php`; cron запускается на 7-й минуте каждого часа.
- Модалка распоряжения ищет по ФИО/табельному, ограничивает выдачу 20 строками и хранит выбранных монтажников внутри модалки и формы.

## Production-доступ

Legacy production и Bitrix — read-only источники. Секреты не сохранены в Git или handoff.

```text
fmonitor-db-tunnel
127.0.0.1:13306 → configurator.shlz.ru:3306 через SOCKS
database: c1_fmonitor
```

Если credentials недоступны в новой сессии, запросить их у пользователя. Не записывать credentials в документы, скрипты, логи или коммиты.

## Следующие проверки

1. В браузере проверить `/pilot/admin/users`: открыть role-фильтр, выбрать несколько разных ролей по очереди и совместить с поиском/статусом.
2. Проверить назначение и снятие второй роли у реального пользователя; убедиться, что остальные роли не меняются.
3. Продолжить основной сценарий объекта `444`: `очередь → карточка → распоряжение → номер 1С ДО → открытие работ`.
4. Отдельным следующим срезом спроектировать локальную авторизацию нового FMonitor без переноса production/LDAP-паролей.

Последние проверки UI: PHP/JS syntax OK, `git diff --check` OK, `impeccable` detector вернул `[]`.

## Сохранность пользовательских изменений

Не удалять, не перезаписывать и не включать в checkpoint-коммиты:

```text
 M .gitignore
 M CONTEXT.md
?? docs/fmonitor-2-completed-history-migration-proposal.md
?? docs/fmonitor-2-installer-contractor-mapping-analysis.md
?? docs/fmonitor-2-legacy-mounting-organizations.md
?? docs/fmonitor-2-subcontractor-legal-identity-research.md
?? reviews/tests/PILOT-SERVICEDESK-REDESIGN-001.md
?? tools/
```

Не использовать destructive reset/clean.
