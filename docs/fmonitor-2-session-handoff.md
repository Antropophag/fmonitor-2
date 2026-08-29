# FMonitor 2.0 — session handoff

Актуально на `2026-08-29`, ветка `feature/demo-pilot-flow`.

## Последний UX/UI checkpoint

Чек-лист открытого объекта подключён к фактически раздаваемому `rapid-pilot/pilot.css`. Параллельная реализация уже содержала renderer, route и JS, но её стили находились только в `app/PilotHttp/pilot.css`, тогда как launcher стенда использует `rapid-pilot/pilot.css`; из-за этого экран отображался без layout. Checklist surface перенесён в активный asset, primary actions выровнены по modifiers `shlz-ui`, исправлено отображение скрытого action dock в read-only состоянии. Visual-contract gate теперь требует checklist styles и documented primary modifiers.

Поверхности пилота повторно выровнены по публичным контрактам `shlz-ui` и Service Desk. Удалены глобальные переопределения `.shlz-button`/`.shlz-status`, которые конфликтовали с source-backed hover/active/disabled states; primary actions используют документированные modifiers, статусы — штатную геометрию и paint variants, breadcrumbs получили отдельную компактную application-owned типографику.

Golos Text теперь детерминированно self-hosted из `@fontsource/golos-text` 5.3.0: в репозитории закреплены WOFF2 Cyrillic/Latin для весов 400/500/600 и лицензия, font endpoints отдают `200 font/woff2`. Для предотвращения регрессии добавлен `rapid-pilot/verify-visual-contract.php`: gate проверяет порядок CSS, владение базовыми `shlz-ui` классами, шрифт, breadcrumbs и primary actions. Gate вызывается автоматически launcher-ом и закреплён как обязательный в `rapid-pilot/AGENTS.md`.

Проверены список объектов, карточка и форма распоряжения на desktop/mobile. PHP/JS syntax checks, font endpoint probes, `git diff --check`, visual contract gate и Impeccable detector прошли; detector вернул `[]`.

Страница формирования распоряжения переработана в единый компактный task surface без искусственного степпера. Контекст объекта, выбранный состав, один вход в picker, справочный инженер и зона подтверждения теперь находятся на одной поверхности. Поведение picker не менялось: поиск по ФИО/табельному, ограничение выдачи, кадровые подсказки и синхронизация выбранных людей внутри модалки и формы сохранены.

Карточка объекта больше не скрывает критическую информацию в шести вкладках. На одном обзорном экране видны идентификация и адрес, состояние и ближайшая команда, сроки, команда, распоряжение/регистрация в 1С ДО, проблемы и пять последних значимых событий. Команды и формы назначения инженера, регистрации и открытия сохранены с прежними CSRF/concurrency-полями.

Во время проверки обнаружено, что локальный сценарий после предыдущего хэндоффа уже продолжили: объект `445` теперь имеет сформированное и зарегистрированное распоряжение версии 1, номер 1С ДО `2`, три монтажника и ожидает открытия. Поэтому состояние формы до распоряжения проверено без изменения данных на объекте `619`; открытое состояние — на `444`; зарегистрированное — на `445`.

Выполнен пакетный browser-pass desktop/mobile через живой launcher, затем один подтверждающий mobile-pass после исправления min-width/переносов длинных кадровых данных. PHP/JS syntax checks, `git diff --check` и ручной `impeccable` detector прошли; detector вернул `[]`.

## Режим работы

Продолжать быстрый рабочий пилот без SSD/TDD-церемонии и без субагентов. Правило и визуальные границы закреплены в `rapid-pilot/AGENTS.md`. Не merge-ить `main`; делать checkpoint-коммиты в текущей ветке.

UI-источники истины:

- Service Desk: `/mnt/c/Users/Polly/Downloads/ЩЛЗ - фронт ServiceDesk`;
- `../shlz-ui` — использовать публичные exports и документированные HTML-контракты;
- Figma: <https://www.figma.com/design/x75wCNufIZQuwFOTLhGZi9/Service-desk--Copy-?node-id=0-1&p=f&t=8Qd9tmQOOMJiecQt-0>.

## Последние checkpoint-коммиты

```text
54641db fix: connect checklist to rapid pilot assets
1231279 fix: align pilot UI with shlz contracts
38a7749 feat: redesign pilot object workflow surfaces
19553dd docs: hand off pilot object UX redesign
139162a fix: render pilot orders for installer brigades
1cec4bd feat: assign pilot control engineer from object card
3feaed4 docs: update rapid pilot user administration handoff
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

Локальную доверенную учётную запись launcher можно выбрать при старте через `FMONITOR_DEMO_REMOTE_USER=<email>`. Без переменной сохраняется fixture fallback `sidorov@shlz.ru`. Текущий стенд запущен под активным администратором `jonsnow@shlz.ru`, чтобы ролевая доступность чек-листа проверялась в браузере.

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

Инженер строительного контроля больше не выбирается распоряжением. Вкладка `Команда` карточки объекта позволяет без формальной процедуры назначить одного активного пользователя с активной production-ролью `Строительный контроль`. Каждая фактическая замена добавляет событие `control_engineer_changed` со снимками прежнего и нового инженера и актором; legacy `responsstroicontrol` остаётся начальным fallback и не перезаписывается.

Форма распоряжения принимает только монтажников. Текущий инженер подтягивается из карточки и показывается справочно; технический снимок в версии документа пока сохранён для совместимости renderer/process-контракта быстрого пилота и не является основанием назначения инженера.

На локальном объекте `444` проверена замена `Кадушкин Сергей Николаевич → Ветров Константин Константинович`: POST вернул `303`, карточка и форма распоряжения показывают Ветрова, append-only событие содержит обе стороны изменения и actor `18`.

Renderer документов теперь принимает как индивидуальный состав, так и бригаду: приложение формирует отдельную строку для каждого монтажника. Это устраняет `ASSIGNMENT_ORDER_RENDER_FAILED`, который ранее возникал у объекта `445` при выборе трёх монтажников (и у первой попытки объекта `444` при выборе двух).

Текущее локальное состояние сценария:

- объект `444` уже открыт в работу, распоряжение версии 1 зарегистрировано с номером `1`;
- объект `445` имеет состояние «Готов к открытию»: распоряжение версии 1 зарегистрировано с номером `2`, для открытия требуется фактическая дата начала;
- объект `619` имеет состояние «Требуется распоряжение» и подходит для проверки формы до формирования документа.

## Чек-лист быстрого пилота

Рабочий URL: `http://127.0.0.1:8092/pilot/objects/444/checklist`. Ссылка появляется в ближайшем действии открытого объекта. Renderer, route и JS находятся в `app/PilotHttp/ChecklistView.php`, `app/PilotHttp/PilotHttp.php` и `app/PilotHttp/checklist.js`; стили живого launcher обязательно находятся в `rapid-pilot/pilot.css`.

Текущий экран показывает 8 разделов и 42 работы, общий/секционный прогресс, фото раздела и offline/pending состояния. На открытом объекте отметки и фото доступны закреплённому инженеру, а также всем активным пользователям с активной ролью `Строительный контроль`, `Администратор`, `Суперадминистратор` или руководящей ролью, название которой начинается с `Руководитель ` либо `Директор `. Для остальных пользователей экран read-only. HTTP-идентификация сначала использует импортированный pilot-каталог пользователей и сохраняет legacy fallback для launcher. На объекте `444` подтверждён интерактивный режим под production-пользователями ролей `Строительный контроль`, `Руководитель отдела` и `Администратор`.

Плавающий action dock «Продолжить раздел» удалён. При ширине до `900px` desktop rail с общим прогрессом переносится после списка разделов, а не перед ним; на телефоне он больше не скрывается. Кнопка «Завершить раздел» одним нажатием отмечает все работы раздела. Если фото ещё нет, массовые отметки сохраняются, а раздел остаётся в состоянии «Нужно фото» до добавления обязательного фото; при наличии фото раздел завершается и открывается следующий.

Заголовок открытого раздела работает как toggle: повторный тап сворачивает раздел, тап по другому разделу сворачивает предыдущий и открывает выбранный. Исправление действует одинаково на mobile и desktop.

Под выполненной работой показывается локальная дата и время отметки в формате `дд.мм.гггг, чч:мм` вместо подписи «Отмечено стройконтролем». Время с устройства сохраняется в IndexedDB как ISO instant как для одиночной отметки, так и для массовой команды «Завершить раздел». Для ранее сохранённых локальных отметок, у которых timestamp отсутствует, интерфейс честно показывает «Дата отметки не сохранена» и не восстанавливает вымышленное время.

Критическая граница: это пока UI-прототип fast-пилота. Отметки и фото сохраняются в IndexedDB браузера, а `sync()` имитирует отправку таймером; серверных команд, загрузки фото и append-only фактов выполнения ещё нет. Не представлять локальное состояние как подтверждённый серверный факт. Следующий вертикальный срез чек-листа должен заменить имитацию синхронизации реальными командами, авторизацией закреплённого инженера, CSRF/concurrency, аудитом и append-only историей.

## Следующий срез

Приоритет новой сессии — определить, продолжать ли чек-лист как server-backed вертикальный срез или вернуться к проверке полного сценария объекта `445`. Для server-backed чек-листа использовать `docs/fmonitor-2-fast-pilot-checklist-spec.md` как незакоммиченный параллельный материал: сначала сверить его с `PRODUCT.md`, `CONTEXT.md` и текущим прототипом, затем сохранить append-only модель фактов.

Redesign формы распоряжения и карточки объекта завершён в `38a7749`; повторять его не нужно. При UI-изменениях использовать `impeccable` Operate, Service Desk source и публичные контракты `../shlz-ui`, затем один пакетный browser-pass desktop/mobile и обязательный `php rapid-pilot/verify-visual-contract.php`.

## Production-доступ

Legacy production и Bitrix — read-only источники. Секреты не сохранены в Git или handoff.

```text
fmonitor-db-tunnel
127.0.0.1:13306 → configurator.shlz.ru:3306 через SOCKS
database: c1_fmonitor
```

Если credentials недоступны в новой сессии, запросить их у пользователя. Не записывать credentials в документы, скрипты, логи или коммиты.

## Следующие проверки

1. Решить направление чек-листа: оставить UI-прототипом либо реализовать server-backed команды и append-only факты.
2. Повторить завершение сценария `445`: указать фактическую дату и открыть работы, если изменение локальных данных допустимо в задаче новой сессии.
3. Проверить `/pilot/admin/users`: role-фильтр вместе с поиском/статусом и назначение второй роли.
4. Будущим отдельным срезом спроектировать локальную авторизацию без переноса production/LDAP-паролей.

Последние проверки UI: PHP/JS syntax OK, `git diff --check` OK, `impeccable` detector вернул `[]`.

## Сохранность пользовательских изменений

Не удалять, не перезаписывать и не включать в checkpoint-коммиты:

```text
 M .gitignore
 M CONTEXT.md
 M docs/fmonitor-2-pilot-spec.md
?? docs/fmonitor-2-completed-history-migration-proposal.md
?? docs/fmonitor-2-fast-pilot-checklist-spec.md
?? docs/fmonitor-2-installer-contractor-mapping-analysis.md
?? docs/fmonitor-2-legacy-mounting-organizations.md
?? docs/fmonitor-2-subcontractor-legal-identity-research.md
?? reviews/tests/PILOT-SERVICEDESK-REDESIGN-001.md
?? tools/
```

Не использовать destructive reset/clean.
