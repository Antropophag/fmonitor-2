# Продолжение после ручных замечаний — 7 сентября 2026

Это новое свидетельство после `restart-handoff-after-manual-feedback-2026-09-07.md`;
прежний checkpoint и его результаты сохраняются. Глобальная цель восстановлена
через `create_goal`, ACTIVE, без token budget. Полная готовность не заявляется.

## Установленное состояние при продолжении

HEAD был `c0137ff3cfd6b4c543f104085816b1aeb0e2548d`, ветка
`codex/remove-pilot-work-navigation-v2`; только пользовательские `.DS_Store`
и `docs/.DS_Store` были незастейджены. Контейнер `fmonitor2-manual-pilot-1`
работает и healthy. Образ
`sha256:16582809d95c4eb6a2ad86c46f1879fe3c34845a0c05f0dab10fd8d65e2f2d33`
имеет label runtime revision `711b9a5af99faa51a59d17af750b78a426dcf372`.
Более новые данные владельца не восстанавливались из checkpoint.

Headless Playwright подтвердил вход, пять страниц (монтажники, стройконтроль,
объекты/выплаты/история ОТиЗ), семь пунктов меню на каждой, отсутствие ошибок
JS/HTTP. Отдельно подтверждены выход и redirect защищённого экрана на login.
Использована настроенная учётная запись владельца; роли и пользователи не менялись.
Объект966 не использовался для мутаций. Private evidence:
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/continue-login-logout.json`.

## Сохранённые исправления

Коммит `7a65929` исправляет route admission: `ExecutionHttpCoordinator` теперь
распознаёт свои маршруты до чтения `FMONITOR_FRESH_ORDER_FLOW`. Неизвестный маршрут
возвращает404 без чтения конфигурации; CSS и отсутствие identity сохраняют прежний
порядок. В тесте отображение личности проверяется в существующем боковом меню,
а отказ источника identity воспроизводится на локальной таблице пользователей.
Полный `pilot_http_auth_001_test.php` и global-call qualification PASS.
Независимый review: `reviews/code/MANUAL-PILOT-AUTH-VERIFICATION-2026-09-07.md`.

В том же коммите выровнены canonical18 и разрешённый импорт при некорректной
плановой дате. Добавлены отдельные отрицательные проверки фактического завершения
и ПТО. Подробнее: `verification-fixture-alignment-2026-09-07.md` и независимый
`reviews/tests/MANUAL-PILOT-FIXTURE-ALIGNMENT-2026-09-07.md`.

## Диагноз чек-листа и граница проверки

Исходный `inspection_item_complete_001_mariadb_test.php` воспроизвёл отказ ожидания
двух revision-lock запросов. Read-only PROCESSLIST наблюдал оба worker в
`Execute/Update` на `INSERT IGNORE ... fm2_checklist_revisions`, а не на последующем
`SELECT revision_no ... FOR UPDATE`. Добавленная при manual integration
инициализация выполнялась даже для существующей строки.

Первый кандидат блокирует существующую ревизию до условной инициализации и
вызывает часы только при фактической инициализации. Прежний строгий MariaDB тест
прошёл, включая ACCEPTED1/STALE_REVISION1, полное evidence, replay и conflict.
Независимый reviewer выявил дополнительный риск: при отсутствии строки два
locking-read могут взять совместимые gap locks перед конкурирующими INSERT.
В отдельном RED подтверждено отсутствие case-lock сериализации. Исправление
сохранило предварительный nonlocking probe вне транзакции, затем для первой
ревизии блокирует существующее монтажное дело до authoritative revision lock.
Это предотвращает gap-lock deadlock и не создаёт устаревший RR snapshot перед
ожиданием. Оба режима теста и независимые Gate3/Gate5 PASS/APPROVED. Исправление
сохранено коммитом `a42d48d`, пока не установлено.

Полный unit-набор прошёл (`make unit-test`, exit0). Синтетический headless golden
на первом кандидате прошёл selection → original201 → correction201 → reapply →
opening →41 пункт/7 фото/7 разделов →85% →ПТО/декларация →100% после reload.
`bulkPaintedBeforeReply=true`, ошибок нет; итоговый mobile screenshot inspected.
Это synthetic evidence, не закрытие реального объекта и не production readiness.
Private logs: `continue-unit-20260907.log`, `continue-golden-20260907.log`.

## Остатки

Полный `make verify` запущен для актуального диагноза; лог
`~/.local/state/fmonitor2/manual-pilot-20260907/runtime/continue-verify-20260907.log`.
До завершения это не PASS/VERIFY_OK и не exact-SHA evidence при изменении файлов.
Architecture по-прежнему сообщает три SQL ownership нарушения ObjectQueue и два
hotspot growth (PilotE2ECoordinator, router). Baseline не менялся. Новый узкий
OpenSpec-план готовится отдельно; полные gates, protected E2E, real-stand golden
с restart и отдельно ограниченные Bitrix/CI/remote действия остаются незавершёнными.


## Новые замечания владельца в этой сессии

1. На этапе декларации объект966 показывал кнопку «Завершить работы» вертикально.
   Headless read-only воспроизвёл ширину40/высоту292.5px на desktop. Причина —
   вложенная двухколоночная action grid и трёхколоночная форма с жёсткими минимумами.
   CSS candidate выводит пояснение над формой, кнопку отдельной строкой, адаптирует
   поля и общую карточку по ширине контейнера. На1440/1024/768/390px кнопка168px
   шириной и40/44px высотой; overflow/JS/HTTP errors отсутствуют. Private снимки и
   измерения: `declaration-before*`, `declaration-candidate*`. Изменялся только CSS
   через Playwright interception, бизнес-формы не отправлялись. Visual/focus gates
   PASS; `impeccable detect --scope layout` вернул `[]`. Source Windows ServiceDesk
   недоступен на этом Mac; используются существующий runtime и public shlz exports.
2. При «Отметить всё» после первого отображения галочки исчезают и появляются
   последовательно. Предыдущее доказательство `bulkPaintedBeforeReply` недостаточно:
   новая проверка должна покрыть каждый промежуточный ответ и весь pending batch.
   Причина — серверная проекция очищала checked и восстанавливала только accepted
   пункты, не накладывая оставшиеся локальные операции. Минимальное исправление
   повторно использует существующий overlay N для всех f.operations после T.
   Attempted payload/ревизии/очередь/control-queue не менялись. RED:9→minimum1
   на229 animation frames; GREEN:minimum9 на262frames и все9 после pending reload.
   Синтетический golden дошёл до100%, ошибок нет. Проверки bulk online sequence
   и control queue protocol PASS. Owner966 не использовался для мутаций.
3. Владелец явно потребовал исправить права/работоспособность всех skills.
   У78 shebang scripts в skill roots добавлен owner execute bit без изменения
   содержимого. `impeccable context`, browser diagnostic и OpenAI docs resolver
   запускаются. Обнаруженная недостающая Python-зависимость yaml устраняется
   отдельно в пользовательском окружении, без изменений продукта.

## Завершение диагностического полного прогона

`make verify` завершился exit2, процесс больше не работает. Результат:
`FULL_VERIFICATION_FAILURE count=4 stages=architecture-check,db-test,characterization-test,e2e-test`.
PASS: reset disposable DB, canonical18, lint, unit, diff-check. Это диагностический
прогон по рабочему дереву во время продолжения, **не exact-SHA candidate receipt**.

DB failures теперь9: template_generation_boundaries, original_database_setup,
docker_bootstrap_manual_pilot, pilot_demo_bootstrap, protected pilot_e2e_flow,
pilot_object_card, pilot_object_list, pilot_ui_shell, workforce_canonical_runner.
Characterization failures3: calendar terminal15 expectation, photo-revoke oracle,
object-queue completion status copy expectation. E2E сохраняет прежний semantic-list
blocker; protected файл не менялся.

Точечный read-only диагноз template_generation_boundaries: отсутствие плановой
даты и malformed adjusted больше не требуют отказа по разрешению владельца.
При отсутствии finish renderer получаетnull и печатает прочерк; при malformed
adjusted используется допустимый plan_finish_date. Нужны отдельные success
expectations с проверкой metadata-only audit, clock и отсутствия хранимого PDF;
отрицательные business assertions нельзя заменять механически.
`original_database_setup` теперь падает на повторном seed permissions:
`Duplicate entry '5301-assignment_order.original.upload' for key PRIMARY` в
`MariaDbAssignmentOrderOriginalVerificationFixture::seedExampleA`.
Новые ручные замечания имеют приоритет перед дальнейшим выравниванием этих тестов.

## Установленный пакет ручных исправлений

Runtime source: `670ebc9318bdbdaeda89365ffe6acd94ca9744f9`.
Образ: `sha256:1801ffd0dc3d83c4be1dd6ba080b9b71cafa821292b002014abbb34758ae5772`.
Label `org.opencontainers.image.revision` совпадает с source. Все721 отслеживаемых
runtime-файла app/bin/public/rapid-pilot проверены SHA256 из-под runtime user:
несовпадений0. Контейнер `fmonitor2-manual-pilot-1` running/healthy.
Прежний образ сохранён как `fmonitor2-manual:checkpoint-711b9a5`.
Compose обновил только pilot с `--no-deps`; оба рабочих volume и MariaDB сохранены.

При первой сверке обнаружен mode0600 у изменённого checklist.js: процесс контейнера
не мог прочитать asset. Source и временно работающий контейнер немедленно получили
обычный0644, затем образ пересобран и контейнер заменён заново. Окончательный
HTTP asset200 имеет SHA256 `0238fc9da243c1642221e433c0e9224004f226016ef15df7b1155473926dfb29`,
точно как reviewed source. Это исправление прав исходного asset, не изменение
содержимого или private storage policy.

После установки реальная декларация№966 проверена на1440/1024/768/390px: кнопка
168.125px шириной,40/44px высотой, overflow и JS/HTTP errors отсутствуют.
Private `declaration-installed*` содержат результаты этой проверки. Уточнение
provenance: при post-deploy запуске runner повторно использовал имена
`declaration-before*`, поэтому эти пути теперь также содержат installed view;
исторические RED размеры40x292.5 и hashes исходных captures остаются в review
и истории tool output. Эти пути нельзя выдавать за неизменённые RED captures.

Gate5 декларации и bulk repaint: APPROVED, отдельные records в reviews/code.
Новый полный `make verify` после UI-пакета не запускался; последний диагностический
FAIL и остающиеся ограничения выше сохраняются. Глобальная цель ACTIVE.

## Настроенные локальные навыки

По прямому запросу владельца исправлены execute bits78 shebang scripts,
установлены пользовательские Python dependencies (yaml, Pillow, docx/lxml, numpy,
openpyxl, pptx, pypdf/pdf2image, openai), Poppler26.08.0 и LibreOffice26.8.0.3.
Системный/Homebrew Python не изменялся; Python modules находятся в user site.
Проверены валидаторы, Impeccable context/detect, browser diagnostic, безопасный
запуск image/document/presentation helpers и реальная headless конверсия
синтетического DOCX → одностраничный PDF → PNG. GUI не запускался.
Внешние аккаунты/сервисы навыков этим smoke не вызывались и не подтверждались.
