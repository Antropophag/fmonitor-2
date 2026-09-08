# Checkpoint после исправлений ручного пилота — 7 сентября 2026

## Остановка и глобальная цель

Владелец попросил завершить текущий пакет, сохранить изменения и перезапустить
сессию. Текущий пакет установлен и проверен. **В этой сессии остановиться на
checkpoint; новые задачи не начинать.** В новой сессии возобновить работу по
переходному промпту, начиная с новых замечаний владельца.

Persistent goal проверена: **ACTIVE, без token budget**. Цель не завершена и не
сужена до исправленного интерфейса. Если в новой сессии цель отсутствует,
восстановить через `create_goal` точную формулировку без `token_budget`:

> Довести портал FMonitor 2.0 от фактического состояния repository и remote до проверяемой готовности к запуску в тестовую эксплуатацию, соблюдая все delivery gates, append-only evidence, exact-SHA verification, CI, clean deployment/restart/golden-path и отсутствие launch blockers

Перед планированием/кодом/проверками читать `AGENTS.md`,
`docs/operations/current-delivery-goal.md`, PRODUCT/CONTEXT, pilot spec/data model и
`docs/development-process.md`. Приоритет — ручной пилот сегодня до **22:00 МСК**,
замечания владельца первыми. Полные gates/CI остаются условиями последующей
production integration; рабочий ручной стенд не равен `VERIFY_OK`.

## Обязательное правило сабагентов

**Все сабагенты на всех уровнях: `gpt-5.6-sol`, reasoning `low`.** Это прямое
требование владельца и AGENTS.md. Независимые полезные потоки распараллеливать;
автор не рецензирует собственный код.

При создании явно передавать модель, reasoning и ограниченный контекст:

```json
{
  "task_name": "bounded_task_name",
  "fork_turns": "none",
  "model": "gpt-5.6-sol",
  "reasoning_effort": "low",
  "message": "Конкретная ограниченная задача, обязательные документы, владельцы файлов и ограничения."
}
```

Не использовать `fork_turns: "all"` вместе с override модели: такой fork
наследует модель родителя. Передавать нужные факты/пути явно. Старые агенты
`launch_checks`, `nav_fix_review`, `workforce_schedule_audit` завершили работу;
на доступность этих handles в новой сессии не рассчитывать.

## Репозиторий и установленный стенд

- Репозиторий: `/Users/antropophag/code/fmonitor-2`.
- Ветка: `codex/remove-pilot-work-navigation-v2`.
- Последний коммит runtime: **`711b9a5af99faa51a59d17af750b78a426dcf372`**.
- Перед ним `99e7df5` сохраняет исправления проверочных fixtures. После runtime
  идут только closing handoff/review/operations документы; окончательный HEAD
  указан в финальном ответе и определяется `git rev-parse HEAD`.
- Образ: **`sha256:16582809d95c4eb6a2ad86c46f1879fe3c34845a0c05f0dab10fd8d65e2f2d33`**.
- Label `org.opencontainers.image.revision` совпадает с runtime SHA. SHA-256 всех
  **721** отслеживаемого runtime-файла совпал с работающим контейнером.
- URL: **http://127.0.0.1:8092/pilot/objects**; контейнер healthy.
- Docker project `fmonitor2-manual`, сервисы `pilot` и `mariadb`.
  Контейнеры `fmonitor2-manual-pilot-1`, `fmonitor2-manual-mariadb-1`.
- Рабочие volumes: `fmonitor2-manual_pilot-state`,
  `fmonitor2-manual_mariadb-data`. Не удалять и не пересоздавать данные.
- Старые `fmonitor2-local-preview_mariadb-data` и
  `fmonitor2-local-preview_pilot-state` проверены: существуют, контейнеры к ним
  не подключены. Сохранять как отключённый резерв.
- Предыдущий образ сохранён как `fmonitor2-manual:checkpoint-c81f562`;
  есть и более ранние checkpoint tags. Не сбрасывать браузерные очереди при откате.

Рабочий compose override и env находятся **вне repo**:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/compose.override.yaml` и
`preview.env`. Использовать compose.yaml + этот override/env и project
`fmonitor2-manual`. Не выводить env/пароли. Вход — прежняя настроенная owner-admin
учётная запись; новые пользователи приглашены только владельцем, legacy users не
импортировались. Роли владельца сохраняются, автоматически их не менять.

Последний безопасный агрегат: 381 дело, 1264 записи кадрового каталога, 1 локальный
пользователь, 1 application, 1 original revision, 11 checklist operations.
Это наблюдение, не значения для восстановления: владелец может продолжить ввод.
Не откатывать более новые данные или изменения после checkpoint.

## Что исправлено и чем подтверждено

1. Меню: карточка больше не теряет полномочия/пункты при переходах.
2. Назначение ролей: форма и POST используют одну локальную сессию; CSRF, Origin,
   точные права и фиксация использованных токенов сохранены.
3. Выбор состава: возвращён существующий дизайн; модалка не загружает каталог при
   открытии. Поиск от двух символов, до 20 записей на запрос, выбранные сотрудники
   сохраняются при поиске/дозагрузке/повторном открытии/сохранении.
4. Оригинал: application authorizer использует активные локальные роли, а не
   устаревшие индивидуальные process-capability rows. Ролевой FKR/manager без
   дополнительных индивидуальных grants может загрузить/исправить PDF.
5. PDF: TCPDF добавлял vendor `/URI` и viewer `/OpenAction`, после чего собственный
   инспектор отвергал шаблон. Генератор теперь не добавляет эти элементы. Парсер
   безопасности **не отключён**; пользователь спрашивал об основании проверки,
   но не дал отдельного указания убрать её. Исходный PDF в Downloads не изменялся.
   Новый шаблон скачан кнопкой и загружен обратно браузером: HTTP 201.
6. Чек-лист: удалена устаревшая runtime-подстановка, из-за которой JS отдавался503;
   документальный блок исключён из инициализации рабочих контролов.
7. Массовая отметка: весь batch сохраняется в IndexedDB и отображается pending
   до первого ответа. Head сохраняет наблюдавшуюся ревизию; никогда не отправленные
   successors используют acceptedRevision предшественника. Attempted payload не
   переписывается. Оба sender (`checklist.js` и `control-queue.js`) поддерживают
   протокол, убирают локальные metadata из запроса, останавливают цепь при ошибке,
   сортируют восстановленный batch независимо от UUID-порядка IndexedDB.
8. Единые статусы: словарь в CONTEXT и `InstallationStatusLabels` содержит шесть
   существующих названий: Требуется распоряжение / Готов к открытию / Монтажные
   работы / Документарное закрытие / Работы завершены / Требуется изменение.
   В интерфейсе это **статус**, не отдельное «состояние». DB `process_state` и
   исторические факты не переименовывались.
9. Карточка читает команду из применённого native состава, оригинал — из точной
   применённой revision. Документы/история используют публичный SHLZ Document Row,
   file-type SVG из `@shlz/icons` dist exports, правильный native download URL и
   фактическое upload time, а не application time.

**Реальная проверка объекта №966:** в таблице и карточке «Монтажные работы»,
во вкладке «Команда» 2 монтажника, в «Документах» 1 SHLZ document row и «Загружен»;
иконка загружена, скачивание native original успешно (100732 bytes), ошибок
JS/HTTP и горизонтального overflow на mobile нет. Производственные факты объекта
при проверке не изменялись. Скриншоты inspected.

**Полный синтетический browser golden:** выбор двух монтажников → original201 →
apply → correction201 → reapply → opening →41 пункт/7 фото/7 завершённых разделов →
85% → ПТО/обязательная декларация →100% после reload. Ошибок нет. При искусственной
задержке первого ответа на700ms весь раздел уже отображается pending:
`bulkPaintedBeforeReply=true`. Это не receipt закрытия реального объекта и не
полный real-stand golden с restart.

Реальные read-only страницы installers, construction-control, OTIZ objects,
payments, history открываются200, с одинаковыми7 пунктами меню, без JS/HTTP ошибок.
Расчёт/выплата ОТиЗ на реальных данных не выполнялись.

Браузер — **только headless Playwright**, реальные Safari/Chrome владельца не
трогать. Приватные runners и evidence:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/`:
`headless-ui.cjs`, `object966-verify.cjs`, `object966-after.json`,
`section-read-smoke.cjs`, `section-read-smoke.json`,
`golden-browser-fixture.php`, `golden-browser.cjs`, `golden-browser-result.json`,
`template-original-browser-fixture.php`, `template-original-browser.cjs`.
Node/PHP доступны через `PATH=/opt/homebrew/bin:$PATH`; Playwright используется из
`/Users/antropophag/code/shlz-ui/node_modules/playwright`. Fixtures создают свои
синтетические БД и убирают их в finally. Не воспроизводить мутации на №966 только
ради smoke без подходящих разрешённых тестовых данных.

## Полная проверка и оставшаяся работа

Один полный `make verify` на `b2debaa` (runtime c81f562) **завершился**, не работает
в фоне. Прежние session74931/PID95317 не возобновлять/не считать живыми.
Полный лог с точными падениями:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/verify-current.log`.

PASS: setup/reset disposable DB, migrate canonical18, lint, diff-check.
FAIL: architecture, unit, DB, characterization, E2E. Итог:
`FULL_VERIFICATION_FAILURE count=5 stages=architecture-check,unit-test,db-test,characterization-test,e2e-test`.
**VERIFY_OK не было.** После этого исправлены многие fixtures/проверочные
предпосылки, выполнены focused проверки; полный прогон после них не повторялся.

Сохранены изменения проверок: актуальная локальная role authority, lazy picker,
file-input mock, pin shlz9aaedf50, schema frontier18/ordered1..18, v16/v17 additive
таблицы/поля, bootstrap/readiness fixtures. 84 встроенных вызова в12 PilotHttp
файлах явно квалифицированы; global-call gate PASS. Смысловые отрицательные
assertions не заменялись успехом. Подробности — commits99e7df5/711b9a5 и reviews.

Подтверждённый текущий architecture-check всё ещё FAIL:

- ObjectQueue SQL ownership: `22c603a7f8f2a47d`, `9183136dfb6b49df`, `e193057503705cce`.
- PilotE2ECoordinator:268→308 lines.
- rapid-pilot/router.php:286→289 lines.

PDF renderer hotspot устранён без изменения PDF-контракта. Coordinator/queue
extraction не выполнена; незаконченных новых handler-файлов нет. Долг не rebaseline.

Известные непроверенные/незакрытые остатки после focused выравнивания:

- original attempt-audit schema ещё имеет два ожидания canonical15;
- inspection-item MariaDB test дошёл до PROCESSLIST overlap probe и упал; причина
  следующего уровня ещё не установлена, не ослаблять проверку наугад;
- workforce canonical упирается в architecture;
- pilot_case_import: расхождение поведения старого legacy-date expectation и
  текущего импорта; проверить контракт, не заменять assertion механически;
- pilot_http_auth: после schema setup не совпал ожидаемый HTTP header;
- bootstrap/demo/UI shell/list/card, template boundary и characterization остатки
  смотреть по полному логу и текущему коду, не считать старый список автоматически
  актуальным после уже внесённых исправлений;
- protected E2E ожидает старый semantic-list/registration flow. **Не возвращать
  ручную регистрацию и упрощённый UI ради теста.** Защищённый файл не менялся,
  SHA256 `8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b`.
  Изменения protected expectations требуют прозрачного отдельного процесса.

Native hourly workforce wiring подготовлен (`5d82278`), но профиль **не включён**,
реальный scheduled run/restart не доказан. Не запускать Bitrix/profile/import
автоматически: current delivery goal сохраняет отдельные ограничения. Не начинать
заново старый Gate3 Bitrix — прежние approvals/fetch evidence уже существуют.

Остаются full Gate3/5, `VERIFY_OK`, production/integration/CI/exact-source review,
полный real-stand golden/restart и остальные утверждённые сценарии. Не объявлять
общую цель достигнутой. OpenSpec `deliver-manual-pilot-flow` остаётся незавершённым.
Planning `isComplete` не означает завершение implementation.

## Сохранность и следующий вход

Все изменения кода/тестов сохранены коммитами. Из owner artifacts оставить
незастейдженными только `.DS_Store` и `docs/.DS_Store`. Проверять git status/HEAD
перед продолжением и сохранять более новые изменения владельца.

Не удалять volumes, оригиналы, историю и локальные очереди. Не импортировать
legacy users, открытые дела или объекты с движением чек-листа. Плановая дата не
ограничивает допущенный snapshot import. Secrets/primary evidence вне repo;
`../fmonitor` read-only; `../shlz-ui` — только public exports. Remote mutations,
PR10 merge, CI publication и Bitrix writes не выполнялись и этим handoff не
разрешаются. При новых замечаниях сначала воспроизвести обычный пользовательский
сценарий и исправить его, затем продолжать готовые независимые integration задачи.


## Архивные источники и OpenSpec

Для provenance первоначального импорта/Bitrix fetch использовать
`docs/operations/autonomous-ui-repair-restart-handoff-2026-09-07-1120Z.md`
и указанные там private snapshot/batch/checksum paths. Это архив доказательств,
а не актуальная инструкция deployment или возврата к старому UI. Current handoff
и более новые решения владельца имеют приоритет.

В `deliver-manual-pilot-flow` отмечен подтверждённый пункт2.1; пункт2.8 перенесён
в соответствующий раздел без изменения его содержания. `openspec validate deliver-manual-pilot-flow --strict` PASS. Implementation:9/16, оставшиеся пункты
не объявлены выполненными. Старые non-goals в proposal/design о непереносе объектов
не отменяют более новое разрешение на381 eligible objects, уже отражённое в2.8
и операционных owner decisions; при дальнейшей правке плановых артефактов
согласовать эту формулировку, не откатывать импорт.
