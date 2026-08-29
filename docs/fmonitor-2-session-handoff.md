# FMonitor 2.0 — session handoff

Актуально на `2026-08-29`, ветка `feature/demo-pilot-flow`.

## Режим работы

Делаем быстрый функциональный пилот внутри `fmonitor-2`. Ранние SSD/TDD-наработки используются как foundation, новые пилотные итерации выполняются без процессной церемонии. Локальное правило закреплено в `rapid-pilot/AGENTS.md`.

Работать в одной агентской сессии без субагентов: пользователь отдельно попросил не делегировать, чтобы не терять контекст. Делать checkpoint-коммиты в текущую feature-ветку; `main` не merge-ить.

Перед фронтенд-изменениями сверяться с Service Desk:

- `/mnt/c/Users/Polly/Downloads/ЩЛЗ - фронт ServiceDesk`;
- `../shlz-ui` — корпоративная UI-зависимость, использовать её публичные exports;
- Figma: <https://www.figma.com/design/x75wCNufIZQuwFOTLhGZi9/Service-desk--Copy-?node-id=0-1&p=f&t=8Qd9tmQOOMJiecQt-0>.

Продукт — минималистичный портал контроля выполнения монтажа. Не превращать его в универсальный Service Desk.

## Последние checkpoint-коммиты

```text
bcb6fa4 feat: import production objects into rapid pilot
2a04482 fix: keep collapsed sidebar navigation visible
2b71779 fix: align pilot sidebar with service desk
344256a feat: build expandable pilot navigation
bc7ec19 feat: replace pilot menu glyphs with real icons
47e9281 feat: establish rapid pilot visual workspace
13ff05d docs: refresh pilot session handoff
```

## UI на момент handoff

- Сайдбар в свёрнутом виде сохраняет все иконки; в развёрнутом показывает иконку и текст.
- Стрелка раскрытия находится внутри сайдбара снизу, отдельного checkbox-контрола нет.
- Навигация вынесена из закрытого `<details>`: regression probe показал `nav_nested_in_closed_details=0`, `nav_outside_trigger=1`.
- Используются SVG-иконки вместо текстовых glyph; они сделаны визуально тяжелее по образцу Service Desk.
- Логотип минималистичный и рендерится SVG; bitmap-концепт лежит в `rapid-pilot/assets/fmonitor-logo-concept.png`.
- В меню предусмотрены объекты монтажа, распоряжения, монтажники, ОтИЗ и связанные рабочие разделы.
- Очередь, карточка объекта и формы остаются на базе ранее реализованного рабочего process flow.

Ключевые UI-файлы: `app/PilotHttp/PilotView.php`, `ObjectListView.php`, `ObjectCardView.php`, `PrepareFormView.php`, `pilot.css`.

## Production-данные

Пользователь подключил корпоративный VPN и дал отдельную read-only учётку MySQL. Секреты намеренно не сохранены ни в Git, ни в handoff.

Маршрут к production:

```text
fmonitor-db-tunnel
127.0.0.1:13306 → configurator.shlz.ru:3306 через SOCKS
database: c1_fmonitor
```

Импортёр: `rapid-pilot/import-production-objects.php`.

Он выполняет против production только `SELECT`, выбирает ровно 100 ещё не открытых объектов, пишет только в локальную `fmonitor2_demo` и создаёт process cases через `PilotCaseImporter`.

Особенность legacy: у неоткрытых объектов `workdatefinish` содержит будущую плановую дату, а не факт завершения. Импортёр нормализует её в `plan_finish_date`; локальный `workdatefinish` оставляет пустым. Отсутствие старта определяется production-полем `factworkstartdate = '0000-00-00 00:00:00'`.

В поколение 5 успешно загружено:

```text
copied: 100
imported: 100
process cases total: 101
unopened process cases: 100
production object id range in selected ordering: 444 … 1686
```

## Запуск и восстановление стенда

URL: `http://127.0.0.1:8092/pilot/objects`.

Локальная MariaDB:

```text
container: fmonitor2-redesign-test-db
127.0.0.1:23306
database/user: fmonitor2_demo
```

На момент handoff HTTP отвечает, но `php bin/fmonitor2-pilot-demo.php status` сообщает `state: incomplete`: штатный валидатор поколения ожидает точный fixture и считает добавленные production-строки отклонением. Данные не потеряны, однако после остановки обычный `start` может не поднять поколение 5.

Надёжное восстановление после перезапуска сессии:

1. Убедиться, что `fmonitor2-redesign-test-db` и `fmonitor-db-tunnel` запущены.
2. Выполнить `php bin/fmonitor2-pilot-demo.php reset` — будет создано чистое активное поколение и запущен HTTP.
3. Передать read-only реквизиты через окружение, не записывая их в файл.
4. Запустить импорт:

```bash
FMONITOR_PILOT_ACTIVE_MANIFEST="$HOME/.local/state/fmonitor2/pilot-demo/78d99d34/active.json" \
FMONITOR_SOURCE_USER='<read-only user>' \
FMONITOR_SOURCE_PASSWORD='<read-only password>' \
php rapid-pilot/import-production-objects.php
```

5. Проверить URL и локально подтвердить 101 process case.

Если новая сессия не знает секрет, попросить пользователя передать его снова или экспортировать переменные в окружение. Не сохранять credential в handoff, скриптах или коммитах.

## Точный следующий шаг

Сначала открыть живой список объектов и визуально сравнить его с Service Desk/shlz-ui. Главная незакрытая претензия пользователя — профессиональное качество списка: типографика, плотность, палитра, отступы, радиусы и визуальная иерархия должны соответствовать источнику.

Затем:

1. проверить свёрнутый и развёрнутый sidebar на странице с 101 строкой;
2. проверить скролл, sticky header, hover/focus и адаптивность очереди;
3. убедиться, что production-адреса и номера не ломают ширины колонок;
4. продолжить сценарий `очередь → карточка → распоряжение → номер 1С ДО → открытие работ`.

Отдельная техническая задача: ослабить readiness-проверку demo generation так, чтобы она проверяла наличие fixture-сентинелов, но разрешала дополнительные импортированные production-строки. Тогда `start/status` будут корректно работать после импорта без обязательного `reset`.

## Ограничения и сохранность

- `../fmonitor` и production БД — только read-only.
- Любые записи выполняются исключительно в локальную demo DB.
- Не подменять production-данные синтетикой.
- Не раскрывать пароли в документах, коммитах или ответах.
- Не заниматься Bitrix history, CI и архитектурной уборкой вне нужд пилота.
- Неотслеживаемый `reviews/tests/PILOT-SERVICEDESK-REDESIGN-001.md` принадлежит прежнему процессу; не удалять и не включать в коммиты без отдельного решения пользователя.

Ожидаемый Git status после коммита handoff:

```text
## feature/demo-pilot-flow
?? reviews/tests/PILOT-SERVICEDESK-REDESIGN-001.md
```

Не использовать destructive reset/clean.
